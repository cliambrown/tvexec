<?php
    session_start();
    include_once '../includes/auth.php';
    if (!isset($_SESSION['tvExecPassword']) || $_SESSION['tvExecPassword'] !== $tvExecPassword) {
        $pdo = false;
        die('Not authorized.');
    }
    
    if (!is_file($_POST['pathname'])) die('File does not exist.');
    
    $pathParts = pathinfo($_POST['pathname']);
    if (!in_array($pathParts['extension'], $videoFiletypes)) die('Not a valid video file extension.');
    
    exec("\"$videoPlayerPathname\" \"{$_POST['pathname']}\" $videoPlayerSwitches");
    
    die();
?>