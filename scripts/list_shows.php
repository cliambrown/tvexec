<?php
    if (session_status() == PHP_SESSION_NONE) {
        // Ajax call
        session_start();
        include_once '../includes/auth.php';
    }
    if (!isset($_SESSION['tvExecPassword']) || $_SESSION['tvExecPassword'] !== $tvExecPassword) {
        $pdo = false;
        die('Not authorized.');
    }
    
    // Get file positions from registry
    exec('reg query "HKCU\Software\MPC-HC\MPC-HC\Settings" /s', $regKeys);
    $filePositions = [];
    $pathnames = [];
    $positions = [];
    foreach ($regKeys as $regKey) {
        $regKey = trim($regKey);
        if (preg_match('/^File Name ([0-9]+) /', $regKey, $matches) === 1) {
            $pathnames = add_mpc_filename_regkey($pathnames, $regKey, $matches);
        } elseif (preg_match('/^File Position ([0-9]+) /', $regKey, $matches) === 1) {
            $positions = add_mpc_position_regkey($positions, $regKey, $matches);
        }
    }
    foreach ($pathnames as $mpcID => $pathname) {
        if (isset($positions[$mpcID])) $filePositions[$pathname] = $positions[$mpcID];
    }
    
    // Get shows & episodes from mysql
    $query = 'SELECT * FROM `shows` ORDER BY `lastWatchedTime` DESC, SUBSTRING(UPPER(`showName`), IF(`showName` LIKE \'The %\', 5, 1))';
    $showsStmt = $pdo->prepare($query);
    $showsStmt->execute();
    $shows = [];
    $activeCount = 0;
    $inactiveCount = 0;
    $showNum = 0;
    foreach ($showsStmt as $showInfo) {
        $show = $showInfo;
        $show['nextEpNum'] = 0;
        $query = 'SELECT * FROM `episodes` WHERE `showID`=:showID ORDER BY `seasonNum`, `episodeNum`, `epName`';
        $epStmt = $pdo->prepare($query);
        $epStmt->bindParam('showID', $showInfo['id']);
        $epStmt->execute();
        $epNum = 0;
        $episodes = [];
        foreach ($epStmt as $epInfo) {
            $episode = $epInfo;
            $episode['pathname'] = $episode['filepath'].DIRECTORY_SEPARATOR.$episode['filename'];
            if (isset($filePositions[$episode['pathname']])) $episode['position'] = $filePositions[$episode['pathname']];
            else $episode['position'] = '0:00';
            if (!is_null($episode['seasonNum']) && !is_null($episode['episodeNum'])) {
                $episode['epName'] = sprintf('S%02dE%02d', $episode['seasonNum'], $episode['episodeNum']).' '.$episode['epName'];
            }
            $episodes[$epNum] = $episode;
            if ($showInfo['lastEpID'] === $epInfo['id']) $show['nextEpNum'] = $epNum + 1;
            ++$epNum;
        }
        if ($epNum == 0) continue;
        $show['epCount'] = $epNum;
        $show['episodes'] = $episodes;
        $show['isActive'] = ($show['nextEpNum'] < $show['epCount']);
        if ($show['isActive']) ++$activeCount;
        else ++$inactiveCount;
        $shows[$showNum] = $show;
        ++$showNum;
    }
    
    if ($activeCount) {
        echo '<div class="shows cards-container">';
        foreach ($shows as $show) {
            if ($show['isActive']) echo_show($show);
        }
        echo '</div>';
        echo '<hr>';
    }
    
    if ($inactiveCount) {
        echo '<div class="shows cards-container">';
        foreach ($shows as $show) {
            if (!$show['isActive']) echo_show($show);
        }
        echo '</div>';
        echo '<hr>';
    }
    
    echo '<script>';
    echo 'var showData = '.json_encode($shows);
    echo '</script>';
    
    //
    // FUNCTIONS
    //
    
    function echo_show($show) {
        $showName = $show['showName'];
        $showID = $show['id'];
        if ($show['nextEpNum'] == $show['epCount']) {
            $nextEpisode = [
                'epName' => '',
                'position' => '',
                'duration' => ''
            ];
        } else {
            $nextEpisode = $show['episodes'][$show['nextEpNum']];
        }
        $remainingEps = $show['epCount'] - $show['nextEpNum'];
        ?>
            <div class="show-container card-container <?=($show['isActive'] ? 'active' : '');?>">
                <div class="show card" tabindex="0" id="show-<?=$showID;?>" data-nextepnum="<?=$show['nextEpNum'];?>">
                    <div class="show-banner" style="background-image:url('img/banners/<?=htmlspecialchars(str_replace('\'', '\\\'', $showName));?>.jpg');"></div>
                    <div class="show-info">
                        
                        <h2 class="ellipsis" title="<?=htmlspecialchars($showName);?>"><?=htmlspecialchars($showName);?></h2>
                        
                        <div class="wrapper">
                            
                            <p class="grey-text">
                                <span id="show-<?=$showID;?>-remaining-episodes"><?=$remainingEps;?></span>
                                of <?=$show['epCount'];?> episode<?=($show['epCount'] == 1 ? '' : 's');?> remaining
                            </p>
                            
                            <p class="ellipsis" id="show-<?=$showID;?>-next-ep-name" title="<?=esc($nextEpisode['epName']);?>">
                                <?=esc($nextEpisode['epName']);?>
                            </p>
                            
                            <p class="grey-text position">
                                <span id="show-<?=$showID;?>-position"><?=$nextEpisode['position'];?></span>
                                <span id="show-<?=$showID;?>-position-slash"><?=($nextEpisode['position'] ? '/' : '');?></span>
                                <span id="show-<?=$showID;?>-duration"><?=$nextEpisode['duration'];?></span>
                            </p>
                            
                            <div class="toggle-more-info-container">
                                <input type="checkbox" tabindex="-1" class="toggle-more-info" id="show-<?=$showID;?>-toggle-more">
                                <label class="toggle-more-info-label" for="show-<?=$showID;?>-toggle-more">
                                    <span class="expand_more"><?php include 'img/expand_more.svg'; ?></span>
                                    <span class="expand_less"><?php include 'img/expand_less.svg'; ?></span>
                                </label>
                            </div>
                            
                        </div>
                        
                        <div class="more-info">
                            
                            <div class="button-table-container">
                                <table class="button-table">
                                    <tr>
                                        <td>
                                            <button type="button" tabindex="-1" class="ep-nav" id="show-<?=$showID;?>-first" <?=($show['nextEpNum'] == 0 ? 'disabled' : '');?>>
                                                <?php include 'img/first.svg'; ?>
                                            </button>
                                        </td>
                                        <td>
                                            <button type="button" tabindex="-1" class="ep-nav" id="show-<?=$showID;?>-prev" <?=($show['nextEpNum'] > 0 ? '' : 'disabled');?>>
                                                <?php include 'img/prev.svg'; ?>
                                            </button>
                                        </td>
                                        <td>
                                            <button type="button" tabindex="-1" class="ep-nav" id="show-<?=$showID;?>-rand">
                                                <?php include 'img/rand.svg'; ?>
                                            </button>
                                        </td>
                                        <td>
                                            <button type="button" tabindex="-1" class="ep-nav" id="show-<?=$showID;?>-next">
                                                <?php include 'img/next.svg'; ?>
                                            </button>
                                        </td>
                                        <td>
                                            <button type="button" tabindex="-1" class="ep-nav" id="show-<?=$showID;?>-end">
                                                <?php include 'img/last.svg'; ?>
                                            </button>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="episode-list" tabindex="-1">
                                <?php foreach ($show['episodes'] as $epNum => $episode) { ?>
                                    <a tabindex="-1" class="ep-nav ep-list-nav ellipsis ep-<?=$epNum;?>" id="show-<?=$showID;?>-ep-nav-<?=$epNum;?>" data-epnum="<?=$epNum;?>" title="<?=esc($episode['epName']);?>">
                                            <?=esc($episode['epName']);?>
                                    </a>
                                <?php } ?>
                            </div>
                            
                        </div>
                        
                    </div>
                </div>
            </div>
        <?php
    }
    
    function esc($str) {
        return htmlspecialchars($str);
    }
    
    function add_mpc_filename_regkey($pathnames, $regKey, $matches) {
        $pieces = explode('    ', $regKey);
        $pathname = array_slice($pieces, -1)[0];
        $pathnames[intval($matches[1])] = $pathname;
        return $pathnames;
    }
    
    function add_mpc_position_regkey($positions, $regKey, $matches) {
        $position = preg_replace('/.* ([0-9]+)$/', "$1", $regKey);
        // Truncate to seconds
        if (strlen($position) < 6) {
            $seconds = 0;
        } else {
            $position = intval(substr($position, 0, -5));
            $seconds = round($position / 100);
        }
        $hours = floor($seconds / 3600);
        $mins = floor($seconds / 60 % 60);
        $secs = floor($seconds % 60);
        $position = $mins.':'.$secs;
        $position = sprintf('%02d', $secs);
        if ($hours > 0) $position = sprintf('%02d:%02d', $hours, $mins).':'.$position;
        else $position = $mins.':'.$position;
        $positions[intval($matches[1])] = $position;
        return $positions;
    }
    
?>