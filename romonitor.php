<?php
require_once ("includes/vars.php");
require_once ("includes/functions.php");
$skin = 'desktop';
$opts = getopt('', ['currenthost:', 'player_backend:']);
if (is_array($opts)) {
    foreach($opts as $key => $value) {
	    debuglog($key.' = '.$value,'ROMONITOR');
        $prefs[$key] = $value;
    }
}
debuglog("Using Player ".$prefs['currenthost'].' of type '.$prefs['player_backend'],"ROMONITOR");
require_once ("international.php");
require_once ("collection/collection.php");
require_once ("collection/playlistcollection.php");
require_once ("backends/sql/backend.php");
require_once ("backends/sql/metadatafunctions.php");
require_once ('includes/podcastfunctions.php');
require_once ('utils/imagefunctions.php');
require_once ("player/".$prefs['player_backend']."/player.php");
$player = new $PLAYER_TYPE();
$currenthost_save = $prefs['currenthost'];
$player_backend_save = $prefs['player_backend'];
$trackbytrack = false;
$current_id = -1;
$read_time = 0;
$playcount_updated = false;
$current_song = array();
$returninfo = array();
$dummydata = array('dummy' => 'baby');
close_database();
register_shutdown_function('close_mpd');
// Using the IDLE subsystem of MPD and mopidy reduces repeated connections, which helps a lot

// We use 'elapsed' and a time measurement to make sure we only incrememnt playcounts if
// more than 90% of the track has been played.

// We have to cope with seeking - where we will get an idle player message. The way we handle
// it is that each time we get a message, if we've played more than 90% of the track we INC
// the playcount - but the increment is based on the playcount value the first time we saw
// the track so repeated increments just keep setting it to the same value

while (true) {
    $player->open_mpd_connection();
    while ($player->is_connected()) {
        $mpd_status = $player->get_status();
        if (array_key_exists('error', $mpd_status)) {
            break;
        }
        if (array_key_exists('songid', $mpd_status) && array_key_exists('elapsed', $mpd_status)) {
            connect_to_database();
            $read_time = time();
            $collection = new playlistCollection();
            // map_tags will be uneccesary once romprmetadata starts using ROMMPR_FILE_MODEL, ony sanitise_data will be required then
            $current_song = map_tags($player->get_currentsong_as_playlist($collection));
            if (array_key_exists('duration', $current_song) && $current_song['duration'] > 0 && $current_song['type'] !== 'stream') {
                if ($mpd_status['songid'] != $current_id) {
                    debuglog($prefs['currenthost'].' - '."Track has changed","ROMONITOR");
                    $current_id = $mpd_status['songid'];
                    romprmetadata::get($current_song);
                    $current_playcount = array_key_exists('Playcount', $returninfo) ? $returninfo['Playcount'] : 0;
                    debuglog($prefs['currenthost'].' - '."Current ID is ".$current_id,"ROMONITOR",8);
                    debuglog($prefs['currenthost'].' - '."Duration Is ".$current_song['duration'],"ROMONITOR",8);
                    debuglog($prefs['currenthost'].' - '."Current Playcount is ".$current_playcount,"ROMONITOR",8);
                }
            } else {
                $current_id = -1;
            }
            close_database();
        } else {
            $current_id = -1;
        }
        $timedout = false;
        while (true) {
            if ($timedout) {
                $idle_status = $player->dummy_command();
            } else {
                $idle_status = $player->get_idle_status();
            }
            if (array_key_exists('error', $idle_status) && $idle_status['error'] == 'Timed Out') {
                debuglog($prefs['currenthost'].' - '."idle command timed out, looping back","ROMONITOR",9);
                $timedout = true;
                continue;
            } else if (array_key_exists('error', $idle_status)) {
                break 2;
            } else {
                break;
            }
        }
        if (array_key_exists('changed', $idle_status) && $current_id != -1) {
            connect_to_database();
            debuglog($prefs['currenthost'].' - '."Player State Has Changed","ROMONITOR",7);
            $elapsed = time() - $read_time + $mpd_status['elapsed'];
            $fraction_played = $elapsed/$current_song['duration'];
            if ($fraction_played > 0.9) {
                debuglog($prefs['currenthost'].' - '."Played more than 90% of song. Incrementing playcount","ROMONITOR",7);
                romprmetadata::get($current_song);
                $now_playcount = array_key_exists('Playcount', $returninfo) ? $returninfo['Playcount'] : 0;
                if ($now_playcount > $current_playcount) {
                    debuglog($prefs['currenthost'].' - '."Current playcount is bigger than ours, doing nothing","ROMONITOR",7);
                } else {
                    $current_song['attributes'] = array(array('attribute' => 'Playcount', 'value' => $current_playcount+1));
                    romprmetadata::inc($current_song);
                }
                if ($current_song['type'] == 'podcast') {
                    debuglog($prefs['currenthost'].' - '."Marking podcast episode as listened","ROMONITOR",8);
                    markAsListened($current_song['uri']);
                }
            }

            loadPrefs();
            $prefs['currenthost'] = $currenthost_save;
            $prefs['player_backend'] = $player_backend_save;
            $radiomode = $prefs['multihosts']->{$prefs['currenthost']}->radioparams->radiomode;
            $radioparam = $prefs['multihosts']->{$prefs['currenthost']}->radioparams->radioparam;
            $playlistlength = $mpd_status['playlistlength'];
            debuglog("PLaylist length is ".$playlistlength,"ROMONITOR",9);
            switch ($radiomode) {
                case 'starRadios':
                case 'mostPlayed':
                case 'faveAlbums':
                case 'recentlyaddedtracks':
                    debuglog("Checking Smart Radio Mode","ROMONITOR");
                    if ($playlistlength < 3) {
                        // Note : We never actually take over, just keep an eye and top it up if
                        // it starts to run out. This way any browser can easily take back control
                        // Also, taking over would require us to have write access to prefs.var which is
                        // problematic on some systems, especially if something like SELinux is enabled
                        debuglog("Smart Radio Master has gone away. Taking Over","ROMONITOR",5);
                        $radiomaster = $browser_id;
                        $tracksneeded = $prefs['smartradio_chunksize'] - $playlistlength  + 1;
                        debuglog("Adding ".$tracksneeded." from ".$radiomode,"ROMONITOR",6);
                        $tracks = doPlaylist($radioparam, $tracksneeded);
                        $cmds = array();
                        foreach ($tracks as $track) {
                            $cmds[] = join_command_string(array('add', $track['name']));
                        }
                        $player->do_command_list($cmds);
                    }
                    break;
            }
            close_database();
        }
    }
    close_mpd();
    debuglog($prefs['currenthost'].' - '."Player connection dropped - retrying in 10 seconds","ROMONITOR");
    sleep(10);
}

function map_tags($filedata) {

    $current_song = array(
        "title" => $filedata['Title'],
        "album" => $filedata['Album'],
        "artist" => $filedata['trackartist'],
        "albumartist" => $filedata['albumartist'],
        "duration" => $filedata['Time'],
        "type" => $filedata['type'],
        "date" => getYear($filedata['Date']),
        "trackno" => $filedata['Track'],
        "disc" => $filedata['Disc'],
        "uri" => $filedata['file'],
        "imagekey" => $filedata['ImgKey'],
        "domain" => $filedata['domain'],
        "image" => $filedata['images']['small'],
        "albumuri" => $filedata['X-AlbumUri']
    );

    romprmetadata::sanitise_data($current_song);
    return $current_song;
}

function close_mpd() {
    global $player;
    $player->close_mpd_connection();
}

?>
