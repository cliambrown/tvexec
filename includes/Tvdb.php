<?php

    // Gets TVDB API tokens
    class Tvdb {
        private $apikey;
        private $pdo;
        private $token = false;
        private $ch;
        
        function __construct($apikey, $pdo) {
            $this->apikey = $apikey;
            $this->pdo = $pdo;
            $this->ch = curl_init();
        }
        
        private function get_token() {
            if (!$this->token) {
                $this->get_token_from_mysql();
                if (!$this->token) $this->get_token_from_tvdb();
            }
            return $this->token;
        }
        
        // Check mysql for valid tokens (expire after 24 hours)
        private function get_token_from_mysql() {
            $now = time();
            $yesterday = $now - (23.5 * 60 * 60);
            $stmt = $this->pdo->prepare('SELECT `token` FROM `tvdb_tokens` WHERE `created`>:yesterday ORDER BY `created` DESC LIMIT 1');
            $stmt->bindParam('yesterday', $yesterday);
            $stmt->execute();
            if ($stmt->rowCount()) {
                $row = $stmt->fetch();
                $this->token = $row['token'];
                return true;
            }
            return false;
        }
        
        // Retrieve token from tvdb
        private function get_token_from_tvdb() {
            $response = $this->get_response('https://api.thetvdb.com/login', ['apikey' => $this->apikey]);
            if (!isset($response['token'])) return false;
            $this->token = $response['token'];
            // Save token to mysql
            $now = time();
            $stmt = $this->pdo->prepare('INSERT INTO `tvdb_tokens` (`created`, `token`) VALUES (:now, :token)');
            $stmt->bindParam('now', $now);
            $stmt->bindParam('token', $this->token);
            $stmt->execute();
            // Delete expired tokens
            $yesterday = $now - (23.5 * 60 * 60);
            $stmt = $this->pdo->prepare('DELETE FROM `tvdb_tokens` WHERE `created`<:yesterday');
            $stmt->bindParam('yesterday', $yesterday);
            $stmt->execute();
            return true;
        }
        
        public function get_response($url, $postArr = false) {
            $this->ch = curl_init($url);
            if ($postArr) {
                $postStr = json_encode($postArr);
                curl_setopt($this->ch, CURLOPT_POST, 1);
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $postStr);
                curl_setopt($this->ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($postStr)
                ]);
            } else {
                if (!$this->get_token()) return false;
                curl_setopt($this->ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$this->token}"]);
            }
            curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($this->ch);
            if (curl_errno($this->ch)) return false;
            if (curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200) return false;
            $response = json_decode($response, true);
            if (!$response) return false;
            return $response;
        }
        
        public function curl_close() {
            curl_close($this->ch);
        }
    }

?>