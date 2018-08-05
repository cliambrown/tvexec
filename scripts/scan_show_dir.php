<?php
    session_start();
    include_once '../includes/auth.php';
    if (!isset($_SESSION['tvExecPassword']) || $_SESSION['tvExecPassword'] !== $tvExecPassword) {
        $pdo = false;
        die('Not authorized.');
    }
    
    ini_set('max_execution_time',300);
    $tvdb = new Tvdb($tvdbApiKey, $pdo);
    
    // Get shows from hard drive
    $shows = [];
    $newShows = [];
    $iter = new DirectoryIterator($tvDir);
    foreach ($iter as $fileinfo) {
        if (!$fileinfo->isDir() || $fileinfo->isDot()) continue;
        $showName = $fileinfo->getFilename();
        // Get show info
        $stmt = $pdo->prepare('SELECT * FROM `shows` WHERE `showName`=:showName');
        $stmt->bindParam('showName', $showName);
        $stmt->execute();
        if ($stmt->rowCount()) {
            $show = $stmt->fetch();
            $showID = $show['id'];
        } else {
            $stmt = $pdo->prepare('INSERT INTO `shows` (`showName`) VALUES (:showName)');
            $stmt->bindParam('showName', $showName);
            $stmt->execute();
            $showID = $pdo->lastInsertId();
            $show = [
                'id' => $showID,
                'showName' => $showName,
                'tvdbID' => ''
            ];
        }
        if (!$show['tvdbID']) {
            $newShow = [
                'showName' => $showName,
                'showID' => $showID,
                'tvdbResponseData' => []
            ];
            $url = 'https://api.thetvdb.com/search/series?name='.urlencode($show['showName']);
            $response = $tvdb->get_response($url);
            if ($response && isset($response['data']) && is_array($response['data']) && count($response['data']) > 0) {
                $newShow['tvdbResponseData'] = $response['data'];
            }
            $newShows[] = $newShow;
        }
        $shows[] = $show;
    }
    
    // Return show id form if necessary
    if (count($newShows)) {
        ob_start();
        ?>
            <h1>Verify New Shows</h1>
            <form class="card-container" method="post" action="scripts/save_show_tvdbids.php">
                <?php foreach ($newShows as $newShow) { ?>
                    <div class="new-show card">
                        <h2><?=$newShow['showName'];?></h2>
                        <p>
                            <?php if (file_exists("../img/banners/{$newShow['showName']}.jpg")) { ?>
                                <img src="img/banners/<?=$newShow['showName'];?>.jpg">
                            <?php } else { ?>
                                <span class="red-text">No banner image found!</span>
                            <?php } ?>
                        </p>
                        <?php foreach ($newShow['tvdbResponseData'] as $data) { ?>
                            <?php
                                $seriesName = $data['seriesName'];
                                if (preg_match('/\([1-2][0-9]{3}\)$/', $seriesName) !== 1) {
                                    $year = date('Y', strtotime($data['firstAired']));
                                    $seriesName .= " ($year)";
                                }
                            ?>
                            <p class="ellipis">
                                <input type="radio" id="show-<?=$newShow['showID'];?>-<?=$data['id'];?>" name="show-<?=$newShow['showID'];?>" 
                                    value="<?=$data['id'];?>" <?=(count($newShow['tvdbResponseData']) == 1 ? 'checked' : '');?>/>
                                <label for="show-<?=$newShow['showID'];?>-<?=$data['id'];?>">
                                    <a href="https://www.thetvdb.com/series/<?=$data['slug'];?>" target="_blank">[link]</a>
                                    <?=$seriesName;?>
                                </label>
                            </p>
                        <?php } ?>
                        <p class="ellipis">
                            <input type="radio" id="show-<?=$newShow['showID'];?>-other" name="show-<?=$newShow['showID'];?>" value="other" />
                            <label for="show-<?=$newShow['showID'];?>-other">
                                Other (TVDB ID): <input type="text" name="show-<?=$newShow['showID'];?>-other" class="other-tvdbid">
                            </label>
                        </p>
                    </div>
                <?php } ?>
                <p class="center-text">
                    <button type="submit" class="submit-btn">Save</button><br>
                    (this could take a very long time)
                </p>
            </form>
        <?php
        $html = ob_get_clean();
        header('Content-type: application/json');
        echo json_encode([
            'showOverlay' => true,
            'html' => $html
        ]);
        $tvdb->curl_close();
        die();
    }
    
    // Get episodes
    $epsMissingInfo = [];
    
    // Set up filter to remove episodes whose files have been deleted
    $pdo->query('UPDATE `episodes` SET `fileExists`=0');
    
    foreach ($shows as $key => $show) {
        
        // Get episode files
        $show['episodes'] = [];
        $epPathnames = get_all_files($tvDir.DIRECTORY_SEPARATOR.$show['showName'], ['avi','mkv','mp4','mov','wmv']);
        $epCount = count($epPathnames);
        $show['epCount'] = $epCount;
        if (!$epCount) {
            unset($shows[$key]);
            continue;
        }
        
        foreach ($epPathnames as $pathname) {
            $stmt = $pdo->prepare('SELECT * FROM `episodes` WHERE filename=:filename');
            $filename = basename($pathname);
            $stmt->bindParam('filename', $filename);
            $stmt->execute();
            if ($stmt->rowCount()) {
                $episode = $stmt->fetch();
                $epID = $episode['id'];
            } else {
                $stmt = $pdo->prepare('INSERT INTO `episodes` (`filename`) VALUES (:filename)');
                $stmt->bindParam('filename', $filename);
                $stmt->execute();
                $epID = $pdo->lastInsertId();
                $episode = [
                    'id' => $epID,
                    'showID' => $show['id'],
                    'seasonNum' => null,
                    'episodeNum' => null,
                    'pathname' => $pathname,
                    'epName' => null,
                    'duration' => null
                ];
            }
            
            // Fill in missing episode info
            if ($episode['seasonNum'] === null || $episode['episodeNum'] === null) {
                $regex = '/s([0-9]{1,2})[\.\- ]?e([0-9]{1,2})/i';
                if (preg_match($regex, $filename, $matches) === 1) {
                    $episode['seasonNum'] = intval($matches[1]);
                    $episode['episodeNum'] = intval($matches[2]);
                }
                else {
                    $epsMissingInfo[$epID] = [
                        'pathname' => $pathname,
                        'missingSE' => true
                    ];
                }
            }
            
            if (!$episode['epName'] && $show['tvdbID'] && $episode['seasonNum'] !== null && $episode['episodeNum'] !== null) {
                $url = "https://api.thetvdb.com/series/{$show['tvdbID']}/episodes/query?airedSeason={$episode['seasonNum']}&airedEpisode={$episode['episodeNum']}";
                $response = $tvdb->get_response($url);
                if ($response && isset($response['data'][0]['episodeName'])) {
                    $episode['epName'] = $response['data'][0]['episodeName'];
                }
            }
            if (!$episode['epName']) {
                if (isset($epsMissingInfo[$epID])) $epsMissingInfo[$epID]['missingEpName'] = true;
                else {
                    $epsMissingInfo[$epID] = [
                        'pathname' => $pathname,
                        'missingEpName' => true
                    ];
                }
            }
            
            if ($episode['duration'] === null) {
                $temp = tmpfile();
                $handle = fopen($pathname, 'r');
                $filepart = fread($handle, 6000);
                fwrite($temp, $filepart);
                // Turn off php notices (from getID3 lib)
                require_once '../getid3/getid3.php';
                error_reporting(E_ALL & ~E_NOTICE);
                if (!$getID3) $getID3 = new getID3;
                $id3Info = $getID3->analyze(stream_get_meta_data($temp)['uri']);
                fclose($temp);
                if (!isset($id3Info['playtime_string'])) $id3Info = $getID3->analyze($pathname);
                // Turn notices back on
                error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
                if (!isset($id3Info['playtime_string'])) $id3Info = ['playtime_string' => null];
                $episode['duration'] = $id3Info['playtime_string'];
            }
            if (!$episode['duration']) {
                if (isset($epsMissingInfo[$epID])) $epsMissingInfo[$epID]['missingDuration'] = true;
                else {
                    $epsMissingInfo[$epID] = [
                        'pathname' => $pathname,
                        'missingDuration' => true
                    ];
                }
            }
            
            // Mark this file as existing and other info in case of changes
            $stmt = $pdo->prepare(
                'UPDATE `episodes` SET
                    `showID`=:showID,
                    `seasonNum`=:seasonNum,
                    `episodeNum`=:episodeNum,
                    `fileExists`=1,
                    `filepath`=:filepath,
                    `epName`=:epName,
                    `duration`=:duration
                    WHERE id=:epID
                '
            );
            $stmt->bindParam('showID', $show['id']);
            $stmt->bindParam('seasonNum', $episode['seasonNum']);
            $stmt->bindParam('episodeNum', $episode['episodeNum']);
            $filepath = dirname($pathname);
            $stmt->bindParam('filepath', $filepath);
            $stmt->bindParam('epName', $episode['epName']);
            $stmt->bindParam('duration', $episode['duration']);
            $stmt->bindParam('epID', $epID);
            $stmt->execute();
        }
        
    }
    
    // Remove episodes with missing files
    $pdo->query('DELETE FROM `episodes` WHERE `fileExists`=0');
    
    // Return show id form if necessary
    if (count($epsMissingInfo)) {
        ob_start();
        ?>
            <h1>Add Missing Episode Info</h1>
            <form class="card-container" method="post" action="scripts/save_ep_info.php">
                <?php foreach ($epsMissingInfo as $epID => $episode) { ?>
                    <div class="card episode-info">
                        <p class="grey-text ellipsis" title="<?=htmlspecialchars($episode['pathname']);?>"><?=htmlspecialchars(basename($episode['pathname']));?></p>
                        <p>
                            <a class="open-folder" href="javascript:;" data-path="<?=htmlspecialchars(dirname($episode['pathname']));?>">folder</a> --
                            <a class="open-file" href="javascript:;" data-pathname="<?=htmlspecialchars($episode['pathname']);?>">file</a>
                            <?php if ($show['tvdbID']) { ?>
                                -- <a href="https://www.thetvdb.com/dereferrer/series/<?=$show['tvdbID'];?>" target="_blank">tvdb</a>
                            <?php } ?>
                        </p>
                        <?php if (isset($episode['missingSE']) && $episode['missingSE']) { ?>
                            <p>
                                Season: <input type="text" name="episode-<?=$epID;?>-season" class="season-input"><br>
                                Episode: <input type="text" name="episode-<?=$epID;?>-episodenum" class="epnum-input">
                            </p>
                        <?php } ?>
                        <?php if (isset($episode['missingEpName']) && $episode['missingEpName']) { ?>
                            <p>
                                Episode name:
                                <input type="text" name="episode-<?=$epID;?>-name" class="ep-name-input">
                            </p>
                        <?php } ?>
                        <?php if (isset($episode['missingDuration']) && $episode['missingDuration']) { ?>
                            <p>
                                Duration: <input type="text" name="episode-<?=$epID;?>-duration" value="[?]" class="duration-input">
                            </p>
                        <?php } ?>
                    </div>
                <?php  } ?>
                <p class="center-text">
                    <button type="submit" class="submit-btn">Save</button>
                </p>
            </form>
        <?php
        $html = ob_get_clean();
        header('Content-type: application/json');
        echo json_encode([
            'showOverlay' => true,
            'html' => $html
        ]);
        $tvdb->curl_close();
        die();
    }
    
    header('Content-type: application/json');
    echo json_encode([
        'showOverlay' => false
    ]);
    
    function get_all_files($dir, $exts, $files = []) {
        foreach (new DirectoryIterator($dir) as $fileinfo) {
            if ($fileinfo->isDot()) {
                continue;
            } elseif ($fileinfo->isDir()) {
                $files = get_all_files($dir.'\\'.$fileinfo->getFilename(), $exts, $files);
            } elseif ($fileinfo->isFile() && in_array($fileinfo->getExtension(), $exts, true)) {
                $files[] = $fileinfo->getPathname();
            }
        }
        return $files;
    }
?>