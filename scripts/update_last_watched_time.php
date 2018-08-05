<?php
    session_start();
    include_once '../includes/auth.php';
    if (!isset($_SESSION['tvExecPassword']) || $_SESSION['tvExecPassword'] !== $tvExecPassword) {
        $pdo = false;
        die('Not authorized.');
    }
    
    $date = new DateTime();
    $now = $date->getTimestamp();
    $query = 'UPDATE shows SET lastWatchedTime=:lastWatchedTime WHERE id=:showID';
    $stmt = $pdo->prepare($query);
    $stmt->bindParam('lastWatchedTime', $now);
    $stmt->bindParam('showID', $_POST['showID']);
    $stmt->execute();

    die();
?>