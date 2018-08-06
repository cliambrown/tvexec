<?php
    session_start();
    require_once 'includes/auth.php';
    $_SESSION['tvExecPassword'] = $tvExecPassword;
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>TV Exec</title>
    <link rel="stylesheet" href="css/style.css?v=0.2">
</head>
<body>

    <div id="container">
        
        <div id="shows-container">
            <?php include 'scripts/list_shows.php'; ?>
        </div>
        
        <p class="center-text">
            <button type="button" id="scan-directory-btn">Scan directory</button><br>
            (this could take a while)
        </p>
        
    </div>
    
    <script>
        var mpcExe = '<?=json_encode($videoPlayerPathname);?>';
    </script>
    <script src="js/script.js?v=0.2"></script>
</body>
</html>