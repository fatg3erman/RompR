<?php
include ("includes/vars.php");
include ("includes/functions.php");
$skin = 'desktop';
$opts = getopt('', ['currenthost:', 'player_backend:']);
if (is_array($opts)) {
    foreach($opts as $key => $value) {
	    debuglog($key.' = '.$value,'ROMONITOR');
        $prefs[$key] = $value;
    }
}
$currenthost_save = $prefs['currenthost'];
$player_backend_save = $prefs['player_backend'];
set_player_connect_params();
debuglog("Using Player ".$prefs['currenthost'].' of type '.$prefs['player_backend'],"ROMONITOR");

include ("international.php");
include ("collection/collection.php");
include ("player/mpd/connection.php");
include ("backends/sql/backend.php");
include ("backends/sql/metadatafunctions.php");
include ('includes/podcastfunctions.php');
require_once ('utils/imagefunctions.php');
$trackbytrack = false;
$current_id = -1;
$read_time = 0;
$current_song = array();
$playcount_updated = false;
$returninfo = array();
$dummydata = array('dummy' => 'baby');
$browser_id = 'romonitor'.getmypid().time();
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
    debuglog($prefs['currenthost'].' - '."Connecting To ".$prefs['mpd_host'].":".$prefs['mpd_port'],"ROMONITOR");
    @open_mpd_connection();
    while ($is_connected) {
        $mpd_status = do_mpd_command('status', true, false);
        if (array_key_exists('error', $mpd_status)) {
            break;
        }
        if (array_key_exists('songid', $mpd_status) && array_key_exists('elapsed', $mpd_status)) {
            connect_to_database();
            $read_time = time();
            doCollection('currentsong', array(), false);
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
        $command = 'idle player';
        while (true) {
            $idle_status = do_mpd_command($command, true, false);
            if (array_key_exists('error', $idle_status) && $idle_status['error'] == 'Timed Out') {
                debuglog($prefs['currenthost'].' - '."idle command timed out, looping back","ROMONITOR",9);
                $command = '';
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
            $player = $prefs['currenthost'];
            $radiomode = $prefs['multihosts']->{$player}->radioparams->radiomode;
            $radioparam = $prefs['multihosts']->{$player}->radioparams->radioparam;
            $radiomaster = $prefs['multihosts']->{$player}->radioparams->radiomaster;
            $playlistlength = $mpd_status['playlistlength'];
            debuglog("PLaylist length is ".$playlistlength,"ROMONITOR",9);
            switch ($radiomode) {
                case 'starRadios':
                case 'mostPlayed':
                case 'faveAlbums':
                case 'recentlyaddedtracks':
                    debuglog("Checking Smart Radio Mode","ROMONITOR");
                    if ($playlistlength < 3 && $radiomaster != $browser_id) {
                        $radiomaster = $browser_id;
                        debuglog("Smart Radio Master has gone away. Taking Over","ROMONITOR",5);
                        $prefs['multihosts']->{$player}->radioparams->radiomaster = $browser_id;
                        savePrefs();
                    }
                    $tracksneeded = $prefs['smartradio_chunksize'] - $playlistlength  + 1;
                    if ($radiomaster == $browser_id) {
                        debuglog("Adding ".$tracksneeded." from ".$radiomode,"ROMONITOR",6);
                        $tracks = doPlaylist($radioparam, $tracksneeded);
                        $cmds = array();
                        foreach ($tracks as $track) {
                            $cmds[] = join_command_string(array('add', $track['name']));
                        }
                        do_mpd_command_list($cmds);
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

function doNewPlaylistFile(&$filedata) {
    global $current_song;
    global $prefs;

    $t = $filedata['Title'];
    if ($t === null) $t = "";
    $albumartist = format_sortartist($filedata);
    $tartist = format_artist($filedata['Artist'],'');
    $tartistr = '';
    if (is_array($filedata['Artist'])) {
        $tartistr = format_artist(array_reverse($filedata['Artist']),'');
    }
    if (strtolower($tartistr) == strtolower($albumartist)) {
        $filedata['Artist'] = array_reverse($filedata['Artist']);
        $tartist = $tartistr;
        $albumartist = $tartistr;
    }
	$albumimage = new baseAlbumImage(array(
        'baseimage' => $filedata['X-AlbumImage'],
        'artist' => artist_for_image($filedata['type'], $albumartist),
        'album' => $filedata['Album']
    ));
    $albumimage->check_image($filedata['domain'], $filedata['type'], true);
	$images = $albumimage->get_images();

    $current_song = array(
        "title" => $t,
        "album" => $filedata['Album'],
        "artist" => $tartist,
        "albumartist" => $albumartist,
        "duration" => $filedata['Time'],
        "type" => $filedata['type'],
        "date" => getYear($filedata['Date']),
        "trackno" => $filedata['Track'],
        "disc" => $filedata['Disc'],
        "uri" => $filedata['file'],
        "imagekey" => $albumimage->get_image_key(),
        "domain" => getDomain($filedata['file']),
        "image" => $images['small'],
        "albumuri" => $filedata['X-AlbumUri'],
    );

    romprmetadata::sanitise_data($current_song);
}

?>
