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
set_player_connect_params();
debuglog("Using Player ".$prefs['currenthost'].' of type '.$prefs['player_backend'],"ROMONITOR");

include ("international.php");
include ("collection/collection.php");
include ("player/mpd/connection.php");
include ("backends/sql/backend.php");
include ("backends/sql/metadatafunctions.php");
include ('includes/podcastfunctions.php');
require_once ('utils/imagefunctions.php');
close_database();
$trackbytrack = false;
$current_id = -1;
$current_song = array();
$playcount_updated = false;
$returninfo = array();
$dummydata = array('dummy' => 'baby');

while (true) {

    @open_mpd_connection();
    connect_to_database();
    $mpd_status = do_mpd_command('status', true, false);
    if (array_key_exists('songid', $mpd_status) && array_key_exists('elapsed', $mpd_status)) {
        if ($current_id != $mpd_status['songid']) {
            debuglog("Song has changed","ROMONITOR");
            doCollection('currentsong', array(), false);
            $current_id = $mpd_status['songid'];
            $playcount_updated = false;
			romprmetadata::get($current_song);
            $current_playcount = array_key_exists('Playcount', $returninfo) ? $returninfo['Playcount'] : 0;
			debuglog("Current Playcount is ".$current_playcount,"ROMONITOR",8);
        }
        $progress = $mpd_status['elapsed']/$current_song['duration'];
        if ($current_song['type'] !== 'stream' && $progress > 0.6 && !$playcount_updated) {
            debuglog("Updating Playcount for current song","ROMONITOR");
            $current_song['attributes'] = array(array('attribute' => 'Playcount', 'value' => $current_playcount+1));
            romprmetadata::inc($current_song);
            if ($current_song['type'] == 'podcast') {
                markAsListened($current_song['uri']);
            }
            $playcount_updated = true;
        }
    }
    close_database();
    close_mpd();
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
