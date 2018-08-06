<?php
    
    // Add a random string here to help secure your scripts
    $tvExecPassword = 'YOUR_RANDOM_STRING_HERE_asdfghjkl;1234%&*';
    // See "Getting a TVDB API Key" in the README
    $tvdbApiKey = 'YOUR_API_KEY_HERE';
    // The folder where your tv show files are stored
    $tvDir = 'C:\path\to\TV';
    // The full path & filename of the program you want to use to open your video files
    // Note: file positions WILL NOT WORK if you're not using MPC with "remember position" enabled
    $videoPlayerPathname = 'C:\Program Files (x86)\MPC-HC\mpc-hc-gpu.exe';
    // Optional switches for opening files
    $videoPlayerSwitches = '/play /fullscreen';
    // You can add (or remove) file extensions from this list if you need to
    $videoFiletypes = ['avi','mkv','mp4','mov','wmv'];
    
    require_once 'Tvdb.php';
    
    // Mysql Connect
    $host = 'localhost';
	$db   = 'YOUR_TVEXEC_DB_NAME';
	$user = 'YOUR_TVEXEC_MYSQL_USERNAME';
	$pass = 'YOUR_TVEXEC_MYSQL_PASSWORD';
	$charset = 'utf8';
	$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
	$opt = [
		PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_EMULATE_PREPARES   => false,
	];
	$pdo = new PDO($dsn, $user, $pass, $opt);
	mb_internal_encoding('UTF-8');
    
    // Create db tables if necessary
    $pdo->query("CREATE TABLE IF NOT EXISTS `tvexec`.`tvdb_tokens` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `created` BIGINT UNSIGNED NOT NULL,
            `token` TEXT NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8"
    );
    
    $pdo->query("CREATE TABLE IF NOT EXISTS `tvexec`.`shows` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `showName` varchar(50) NOT NULL,
            `tvdbID` int(10) UNSIGNED NOT NULL,
            `lastEpID` int(10) UNSIGNED NOT NULL DEFAULT '0',
            `lastWatchedTime` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
    );
    
    $pdo->query("CREATE TABLE IF NOT EXISTS `tvexec`.`episodes` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `showID` int(10) UNSIGNED NOT NULL,
            `seasonNum` INT(10) UNSIGNED NULL DEFAULT NULL,
            `episodeNum` INT(10) UNSIGNED NULL DEFAULT NULL,
            `fileExists` BOOLEAN NOT NULL DEFAULT TRUE,
            `filepath` varchar(400) NOT NULL,
            `filename` varchar(255) NOT NULL,
            `epName` varchar(255) DEFAULT NULL,
            `duration` VARCHAR(10) NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
    );
    
?>