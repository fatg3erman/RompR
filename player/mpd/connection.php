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
    'Searched' => 0,
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

function doCollection($command, $domains = false, $newcollection = true) {
    global $connection, $collection;
    if ($newcollection) {
        $collection = new musicCollection();
    }
    debuglog("Starting Collection Scan ".$command, "MPD",4);
    $dirs = array();
    doMpdParse($command, $dirs, $domains);
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
