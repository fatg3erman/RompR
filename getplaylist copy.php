<?php
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");
include ("collection/collection.php");
include ("player/mpd/connection.php");
include ("backends/sql/backend.php");

// BEFORE
// 10:12:36 : TIMTINGS            == Time Spent Reading Socket Data                      : 0.59719300270081
// 10:12:36 : TIMTINGS            == Time Spent Parsing Socket Data                      : 0.073909997940063
// 10:12:36 : TIMTINGS            == Time Spent Checking Database                        : 0
// 10:12:36 : TIMTINGS            == Time Spent Putting Stuff into Collection Structures : 2.8882780075073
// 10:12:36 : TIMINGS             == Whole Process took 00:04
// 10:12:36 : COLLECTION          Peak Memory Used Was 68,518,896 bytes  - meaning we used 67,215,544 bytes.
// 10:12:36 : TIMINGS             ======================================================================


header('Content-Type: application/json; charset=utf-8');
debuglog("======================================================================","TIMINGS",4);
debuglog("== Starting Playlist Update","TIMINGS",4);
$initmem = memory_get_usage();
$now2 = time();
$output = array();
doCollection("playlistinfo");
$peakmem = memory_get_peak_usage();
$ourmem = $peakmem - $initmem;
debuglog("Peak Memory Used bfore JSON encode was ".number_format($peakmem)." bytes  - meaning we used ".number_format($ourmem)." bytes.","COLLECTION",4);
$o = json_encode($output);
print $o;
ob_flush();
file_put_contents(ROMPR_PLAYLIST_FILE, $o);
debuglog("== Time Spent Reading Socket Data             : ".$parse_time,"TIMTINGS",4);
debuglog("== Time Spent Parsing Socket Data             : ".$rtime,"TIMTINGS",4);
debuglog("== Time Spent Checking/Writing to Database    : ".$db_time,"TIMTINGS",4);
debuglog("== Time Spent Creating Playlist Array         : ".$coll_time,"TIMTINGS",4);
debuglog("== Whole Process took ".format_time(time() - $now2),"TIMINGS",4);
$peakmem = memory_get_peak_usage();
$ourmem = $peakmem - $initmem;
debuglog("Peak Memory Used Was ".number_format($peakmem)." bytes  - meaning we used ".number_format($ourmem)." bytes.","COLLECTION",4);
debuglog("======================================================================","TIMINGS",4);

debuglog("Playlist Output Is Done","GETPLAYLIST");

function getImageKey(&$filedata, $albumartist) {
    if ($filedata['ImgKey'] !== null) {
        return $filedata['ImgKey'];
    } else {
        return md5($albumartist." ".$filedata['Album']);
    }
}

function getImageForAlbum(&$filedata, $imagekey) {
    if ($filedata['ImageForPlaylist'] !== null) {
        return preg_replace('#/small/#', '/asdownloaded/',  $filedata['ImageForPlaylist']);
    } else if ($filedata['X-AlbumImage'] !== null) {
        return $filedata['X-AlbumImage'];
    } else {
        return cacheOrDefaultImage($imagekey, 'asdownloaded', $filedata['domain']);
    }
}

function doNewPlaylistFile(&$filedata) {
    global $prefs;
    global $foundartists;
    global $output;

    $t = $filedata['Title'];
    // We can't return NULL in the JSON data for some reason that escapes me
    if ($t === null) $t = "";
    $albumartist = format_sortartist($filedata);
    $imagekey = getImageKey($filedata, $albumartist);
    $info = array(
        "title" => $t,
        "album" => $filedata['Album'],
        "creator" => format_artist($filedata['Artist'],''),
        // Albumartist is always stored as a string, since the metadata bit doesn't really use it
        "albumartist" => $albumartist,
        "duration" => $filedata['Time'],
        "type" => $filedata['type'],
        "date" => getYear($filedata['Date']),
        "tracknumber" => $filedata['Track'],
        "station" => $filedata['station'],
        "disc" => $filedata['Disc'],
        "location" => $filedata['file'],
        "backendid" => (int) $filedata['Id'],
        "dir" => rawurlencode($filedata['folder']),
        "key" => $imagekey,
        "image" => getImageForAlbum($filedata, $imagekey),
        "trackimage" => null,
        "stream" => $filedata['stream'],
        "playlistpos" => $filedata['Pos'],
        "genre" => $filedata['Genre'],
        "metadata" => array(
            "iscomposer" => 'false',
            "artists" => array(),
            "album" => array(
                "name" => $filedata['Album'],
                "artist" => $albumartist,
                "musicbrainz_id" => $filedata['MUSICBRAINZ_ALBUMID'],
                "uri" => $filedata['X-AlbumUri']
            ),
            "track" => array(
                "name" => $filedata['Title'],
                "musicbrainz_id" => $filedata['MUSICBRAINZ_TRACKID'],
            ),
        )
    );

    $foundartists = array();

    // All kinds of places we get artist names from:
    // Composer, Performer, Track Artist, Album Artist
    // Note that we filter duplicates
    // This creates the metadata array used by the info panel and nowplaying -
    // Metadata such as scrobbles and ratings will still use the Album Artist

    if ($prefs['displaycomposer']) {
        // The user has chosen to display Composer/Perfomer information
        // Here check:
        // a) There is composer/performer information AND
        // bi) Specific Genre Selected, Track Has Genre, Genre Matches Specific Genre OR
        // bii) No Specific Genre Selected, Track Has Genre
        if (($filedata['Composer'] !== null || $filedata['Performer'] !== null) &&
            (($prefs['composergenre'] && $filedata['Genre'] &&
                checkComposerGenre($filedata['Genre'], $prefs['composergenrename'])) ||
            (!$prefs['composergenre'] && $filedata['Genre'])))
        {
            // Track Genre matches selected 'Sort By Composer' Genre
            // Display Compoer - Performer - AlbumArtist
            do_composers($filedata, $info);
            do_performers($filedata, $info);
            // The album artist probably won't be required in this case, but use it just in case
            do_albumartist($filedata, $info, $albumartist);
            // Don't do track artist as with things tagged like this this is usually rubbish
        } else {
            // Track Genre Does Not Match Selected 'Sort By Composer' Genre
            // Or there is no composer/performer info
            // Do Track Artist - Album Artist - Composer - Performer
            do_track_artists($filedata, $info);
            do_albumartist($filedata, $info, $albumartist);
            do_performers($filedata, $info);
            do_composers($filedata, $info);
        }
        if ($filedata['Composer'] !== null || $filedata['Performer'] !== null) {
            $info['metadata']['iscomposer'] = 'true';
        }
    } else {
        // The user does not want Composer/Performer information
        do_track_artists($filedata, $info, $albumartist);
        do_albumartist($filedata, $info, $albumartist);
    }

    if (count($info['metadata']['artists']) == 0) {
        array_push($info['metadata']['artists'], array( "name" => "", "musicbrainz_id" => ""));
    }
    array_push($output, $info);

}

function artist_not_found_yet($a) {
    global $foundartists;
    $s = strtolower($a);
    if (in_array($s, $foundartists)) {
        return false;
    } else {
        array_push($foundartists, $s);
        return true;
    }
}

function do_composers(&$filedata, &$info) {
    if ($filedata['Composer'] == null) {
        return;
    }
    foreach ($filedata['Composer'] as $comp) {
        if (artist_not_found_yet($comp)) {
            array_push($info['metadata']['artists'], array( "name" => $comp, "musicbrainz_id" => "", "type" => "composer", "ignore" => "false"));
        }
    }
}

function do_performers(&$filedata, &$info) {
    if ($filedata['Performer'] == null) {
        return;
    }
    foreach ($filedata['Performer'] as $comp) {
        $toremove = null;
        foreach($info['metadata']['artists'] as $i => $artist) {
            if ($artist['type'] == "albumartist" || $artist['type'] == "artist") {
                if (strtolower($artist['name'] ==  strtolower($comp))) {
                    $toremove = $i;
                    break;
                }
            }
        }
        if ($toremove !== null) {
            array_splice($info['metadata']['artists'], $toremove, 1);
        }

        if ($toremove !== null || artist_not_found_yet($comp)) {
            array_push($info['metadata']['artists'], array( "name" => $comp, "musicbrainz_id" => "", "type" => "performer", "ignore" => "false"));
        }
    }
}

function do_albumartist(&$filedata, &$info, $albumartist) {
    $aartist = null;
    if (!($filedata['type'] == "stream" && $albumartist == "Radio") &&
        strtolower($albumartist) != "various artists" &&
        strtolower($albumartist) != "various")
    {
        $aartist = $albumartist;
    }
    if ($aartist !== null && artist_not_found_yet($aartist)) {
        array_push($info['metadata']['artists'], array( "name" => $aartist, "musicbrainz_id" => $filedata['MUSICBRAINZ_ALBUMARTISTID'], "type" => "albumartist", "ignore" => "false"));
    }
}

function do_track_artists(&$filedata, &$info) {
    if ($filedata['Artist'] == null) {
        return;
    }
    $c = $filedata['Artist'];
    if (!is_array($c)) {
        $c = array($filedata['Artist']);
    }
    $m = $filedata['MUSICBRAINZ_ARTISTID'];
    while (count($m) < count($c)) {
        array_push($m, "");
    }
    $a = array();
    foreach ($c as $i => $comp) {
        if (($filedata['type'] != "stream" && $comp != "") || ($filedata['type'] == "stream" && $comp != $filedata['Album'] && $comp != "")) {
            if (artist_not_found_yet($comp)) {
                array_push($info['metadata']['artists'], array( "name" => $comp, "musicbrainz_id" => $m[$i], "type" => "artist", "ignore" => "false"));
                array_push($a, $comp);
            }
        }
    }
    // This is to try and prevent repeated names - eg artists = [Pete, Dud] and albumartist = Pete & Dud or Dud & Pete
    artist_not_found_yet(concatenate_artist_names($a));
    artist_not_found_yet(concatenate_artist_names(array_reverse($a)));
}

?>
