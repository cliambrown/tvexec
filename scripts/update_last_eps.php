<?php
    session_start();
    include_once '../includes/auth.php';
    if (!isset($_SESSION['tvExecPassword']) || $_SESSION['tvExecPassword'] !== $tvExecPassword) {
        $pdo = false;
        die('Not authorized.');
    }
    
    $query = 'UPDATE shows SET lastEpID=:epID WHERE id=:showID';
    $stmt = $pdo->prepare($query);
    $stmt->bindParam('epID', $_POST['epID']);
    $stmt->bindParam('showID', $_POST['showID']);
    $stmt->execute();
    
    die();
?>