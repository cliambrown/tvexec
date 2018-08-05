<?php
    session_start();
    include_once '../includes/auth.php';
    if (!isset($_SESSION['tvExecPassword']) || $_SESSION['tvExecPassword'] !== $tvExecPassword) {
        $pdo = false;
        die('Not authorized.');
    }
    
    foreach ($_POST as $postKey => $postVal) {
        if (preg_match('/^episode\-([1-9][0-9]*)\-([a-zA-Z]+)$/', $postKey, $matches) === 1) {
            $epID = $matches[1];
            $valName = $matches[2];
            if ($valName === 'season') $colName = 'seasonNum';
            elseif ($valName === 'episodenum') $colName = 'episodeNum';
            elseif ($valName === 'name') $colName = 'epName';
            elseif ($valName === 'duration') $colName = 'duration';
            else continue;
            // Send to db
            $query = "UPDATE `episodes` SET `$colName`=:colVal WHERE id=:epID";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam('colVal', $postVal);
            $stmt->bindParam('epID', $epID);
            $stmt->execute();
        }
    }
    
    die();
?>