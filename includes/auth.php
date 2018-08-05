<?php
    
    // A random string used in session variable to secure scripts (16+ characters recommended)
    $tvExecPassword = 'YOUR_RANDOM_STRING_HERE';
	// See the readme for info on getting a TVDB API key
    $tvdbApiKey = 'YOUR_TVDB_API_KEY';
	// The directory where your files are stored. e.g. 'C:\PATH\TO\TVFILES'
    $tvDir = 'C:\PATH\TO\TVFILES';
	// Pathname for your MPC installation
    $mpcPathname = 'C:\Program Files (x86)\MPC-HC\mpc-hc.exe';
    
    require_once 'Tvdb.php';
    
    // Mysql Connect
    $host = 'localhost';
	$db   = 'TVEXEC_DB_NAME';
	$user = 'A_MYSQL_USERNAME';
	$pass = 'A_MYSQL_PASSWORD';
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