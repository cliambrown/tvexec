<?php
    session_start();
    include_once '../includes/auth.php';
    if (!isset($_SESSION['tvExecPassword']) || $_SESSION['tvExecPassword'] !== $tvExecPassword) {
        $pdo = false;
        die('Not authorized.');
    }
    
    if (!is_dir($_POST['path'])) die('Folder does not exist.');
    
    // Try windows / linux / mac commands
    exec('explorer.exe "'.$_POST['path'].'"');
    exec('xdg-open "'.$_POST['path'].'"');
    exec('open `'.$_POST['path'].'`');
    
    die();
?>