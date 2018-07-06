<?php

include ("player/mpd/sockets.php");
include ("player/".$prefs['player_backend']."/collectionupdate.php");
$dtz = ini_get('date.timezone');
if (!$dtz) {
    date_default_timezone_set('UTC');
}

// These are the parameters we care about, and suitable default values for them all.
$mpd_file_model = array (
    'file' => null,
    'domain' => 'local',
    'type' => 'local',
    'station' => null,
    'stream' => null,
    'folder' => null,
    'Title' => null,
    'Album' => null,
    'Artist' => null,
    'Track' => null,
    'Name' => null,
    'AlbumArtist' => null,
    'Time' => 0,
    'X-AlbumUri' => null,
    'playlist' => '',
    'X-AlbumImage' => null,
    'Date' => null,
    'Last-Modified' => '0',
    'Disc' => null,
    'Composer' => null,
    'Performer' => null,
    'Genre' => null,
    'ImageForPlaylist' => null,
    'ImgKey' => null,
    'StreamIndex' => null,
    // Never send null in any musicbrainz id as it prevents plugins from
    // waiting on lastfm to find one
    'MUSICBRAINZ_ALBUMID' => '',
    'MUSICBRAINZ_ARTISTID' => array(''),
    'MUSICBRAINZ_ALBUMARTISTID' => '',
    'MUSICBRAINZ_TRACKID' => '',
    'Id' => null,
    'Pos' => null
);

$array_params = array(
    "Artist",
    "AlbumArtist",
    "Composer",
    "Performer",
    "MUSICBRAINZ_ARTISTID",
);

@open_mpd_connection();

$parse_time = 0;
$db_time = 0;
$coll_time = 0;
$rtime = 0;
$cp_time = 0;

function doCollection($command, $domains = null, $newcollection = true) {
    global $connection, $collection;
    if ($newcollection) {
        $collection = new musicCollection();
    }
    debuglog("Starting Collection Scan ".$command, "MPD",4);
    $dirs = array();
    doMpdParse($command, $dirs, $domains);
}

function doMpdParse($command, &$dirs, $domains) {

    global $connection, $collection, $mpd_file_model, $array_params, $parse_time;

    debuglog("MPD Parse ".$command,"MPD",8);

    $success = send_command($command);
    $filedata = $mpd_file_model;
    $parts = true;
    if (!is_array($domains) || count($domains) == 0) {
        $domains = null;
    }

    $pstart = microtime(true);

    while(!feof($connection) && $parts) {
        $parts = getline($connection);
        if (is_array($parts)) {
            switch ($parts[0]) {
                case "directory":
                    $dirs[] = trim($parts[1]);
                    break;

                case "Last-Modified":
                    if ($filedata['file'] != null) {
                        // We don't want the Last-Modified stamps of the directories
                        // to be used for the files.
                        $filedata[$parts[0]] = $parts[1];
                    }
                    break;

                case 'file':
                    if ($filedata['file'] != null && (!is_array($domains) || in_array(getDomain($filedata['file']),$domains))) {
                        $parse_time += microtime(true) - $pstart;
                        process_file($filedata);
                        $pstart = microtime(true);
                    }
                    $filedata = $mpd_file_model;
                    $filedata[$parts[0]] = $parts[1];
                    break;
                    
                case 'X-AlbumUri':
                    // Mopidy-beets is using SEMICOLONS in its URI schemes.
                    // Surely a typo, but we need to work around it by not splitting the string
                    // Same applies to file.
                    $filedata[$parts[0]] = $parts[1];
                    break;

                default:
                    if (in_array($parts[0], $array_params)) {
                        $filedata[$parts[0]] = array_unique(explode(';',$parts[1]));
                    } else {
                        $filedata[$parts[0]] = explode(';',$parts[1])[0];
                    }
                    break;
            }
        }
    }

    if ($filedata['file'] !== null && (!is_array($domains) || in_array(getDomain($filedata['file']),$domains))) {
        $parse_time += microtime(true) - $pstart;
        process_file($filedata);
    }
}

function getDirItems($path) {
    global $connection, $is_connected;
    debuglog("Getting Directory Items For ".$path,"GIBBONS",5);
    $items = array();
    $parts = true;
    $lines = array();
    send_command('lsinfo "'.format_for_mpd($path).'"');
    // We have to read in the entire response then go through it
    // because we only have the one connection to mpd so this function
    // is not strictly re-entrant and recursing doesn't work unless we do this.
    while(!feof($connection) && $parts) {
        $parts = getline($connection);
        if ($parts === false) {
            debuglog("Got OK or ACK from MPD","DIRBROWSER",8);
        } else {
            $lines[] = $parts;
        }
    }
    foreach ($lines as $parts) {
        if (is_array($parts)) {
            $s = trim($parts[1]);
            if (substr($s,0,1) != ".") {
                switch ($parts[0]) {
                    case "file":
                        $items[] = $s;
                        break;

                  case "directory":
                        $items = array_merge($items, getDirItems($s));
                        break;
                }
            }
        }
    }
    return $items;
}

?>
