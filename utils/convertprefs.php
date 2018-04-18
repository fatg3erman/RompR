<?php

loadOldPrefs();
unlink('prefs/prefs');
savePrefs();


function loadOldPrefs() {
    global $prefs;
    if (file_exists('prefs/prefs')) {
        $fp = fopen('prefs/prefs', 'r');
        if($fp) {
            $crap = true;
            if (flock($fp, LOCK_EX, $crap)) {
                $fcontents = array();
                while (!feof($fp)) {
                    array_push($fcontents, fgets($fp));
                }
                flock($fp, LOCK_UN);
                if(!fclose($fp)) {
                    error_log("ERROR!              : Couldn't close the prefs file.");
                    exit(1);
                }
                if (count($fcontents) > 0) {
                    foreach($fcontents as $line) {
                        $a = explode("||||", $line);
                        if (is_array($a) && count($a) > 1) {
                        	if ($a[0] != "use_mopidy_beets_backend" &&
                        		$a[0] != "use_mopidy_file_backend" &&
                        		$a[0] != "use_mopidy_tagcache" &&
                        		$a[0] != "music_directory" &&
                        		$a[0] != "mopidy_minversion" &&
                        		$a[0] != "roxy_host" &&
                        		$a[0] != "historylength" &&
                        		$a[0] != "keep_search_open" &&
                        		$a[0] != "hide_lastfmlist" &&
                        		$a[0] != "dontscrobbleradio" &&
                        		$a[0] != "spotify_playlists_auto" &&
                        		$a[0] != "remote" &&
                        		$a[0] != "showfileinfo" &&
                        		$a[0] != "apache_backend" &&
                        		$a[0] != "player_backend") {
		                            $prefs[$a[0]] = trim($a[1]);
		                            if ($prefs[$a[0]] === "false" || $prefs[$a[0]] === 0) {
		                            	$prefs[$a[0]] = false;
		                            }
		                            if ($prefs[$a[0]] === "true" || $prefs[$a[0]] === 1) {
		                            	$prefs[$a[0]] = true;
		                            }
			            			error_log("UPGRADE             : Pref ".$a[0]." = ".$prefs[$a[0]]);
		            		}
		            	}
                    }
                } else {
                    error_log("===============================================");
                    error_log("ERROR!              : COULD NOT READ PREFS FILE");
                    error_log("===============================================");
                    exit(1);
                }
            } else {
                error_log("================================================================");
                error_log("ERROR!              : COULD NOT GET READ FILE LOCK ON PREFS FILE");
                error_log("================================================================");
                fclose($fp);
                exit(1);
            }
        } else {
            error_log("=========================================================");
            error_log("ERROR!              : COULD NOT GET HANDLE FOR PREFS FILE");
            error_log("=========================================================");
            exit(1);
        }
    }
}

?>
