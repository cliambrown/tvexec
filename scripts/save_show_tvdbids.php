<?php
    session_start();
    include_once '../includes/auth.php';
    if (!isset($_SESSION['tvExecPassword']) || $_SESSION['tvExecPassword'] !== $tvExecPassword) {
        $pdo = false;
        die('Not authorized.');
    }
    
    foreach ($_POST as $postKey => $postVal) {
        if (preg_match('/^show\-([1-9][0-9]*)$/', $postKey, $matches) === 1) {
            $showID = $matches[1];
            $tvdbID = $postVal;
            if ($tvdbID === 'other') $tvdbID = $_POST["show-$showID-other"];
            $query = 'UPDATE `shows` SET `tvdbID`=:tvdbID WHERE `id`=:showID';
            $stmt = $pdo->prepare($query);
            $stmt->bindParam('tvdbID', $tvdbID);
            $stmt->bindParam('showID', $showID);
            $stmt->execute();
        }
    }
    
    die();
?>