<?php
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");
include ("utils/imagefunctions.php");
debuglog("------- Searching For Album Art --------","GETALBUMCOVER");
include ("backends/sql/backend.php");
include ("includes/spotifyauth.php");
include ("getid3/getid3.php");
$stream = "";
$src = "";
$error = 0;
$file = "";
$artist = "";
$album = "";
$mbid = "";
$albumpath = "";
$albumuri = "";
$small_file = "";
$big_file = "";
$base64data = "";
$delaytime = 1;
$ignore_local = (array_key_exists('ignorelocal', $_REQUEST) && $_REQUEST['ignorelocal'] == 'true') ? true : false;
$fp = null;
$in_collection = false;
$found = false;
$tried_lastfm_once = false;
$covernames = array("cover", "albumart", "thumb", "albumartsmall", "front");

$imgkey = $_REQUEST['imgkey'];
if (array_Key_exists('stream', $_REQUEST)) {
    // 'Stream' is used when we're updating the image for a favourite radio station.
    $stream = $_REQUEST["stream"];
}

$findalbum = array("get_imagesearch_info", "check_stream", "check_playlist");

if (array_key_exists("src", $_REQUEST)) {
    $src = $_REQUEST['src'];
} else if (array_key_exists("ufile", $_FILES)) {
    $file = $_FILES['ufile']['name'];
} else if (array_key_exists("base64data", $_REQUEST)) {
    $base64data = $_REQUEST['base64data'];
} else {
    while ($found === false && count($findalbum) > 0) {
        $fn = array_shift($findalbum);
        list($in_collection, $artist, $album, $mbid, $albumpath, $albumuri, $found) = $fn($imgkey);
    }
    if (!$found) {
        debuglog("Image key could not be found!","GETALBUMCOVER");
        header("HTTP/1.1 404 Not Found");
        ob_flush();
        exit(0);
    }
}

if (preg_match('/\d+/', $mbid) && !preg_match('/-/', $mbid)) {
    debuglog(" Supplied MBID of ".$mbid." looks more like a Discogs ID","GETALBUMCOVER");
    $mbid = "";
}
if ($prefs['player_backend'] == 'mopidy') {
    $albumpath = urldecode($albumpath);
}

if ($mbid != "") {
    $searchfunctions = array( 'tryLocal', 'trySpotify', 'tryMusicBrainz', 'tryLastFM', 'tryGoogle' );
} else {
    // Try LastFM twice - first time just to get an MBID since coverartarchive images tend to be bigger
    $searchfunctions = array( 'tryLocal', 'trySpotify', 'tryLastFM', 'tryMusicBrainz', 'tryLastFM', 'tryGoogle' );
}

debuglog("  KEY          : ".$imgkey,"GETALBUMCOVER");
debuglog("  SOURCE       : ".$src,"GETALBUMCOVER");
debuglog("  UPLOAD       : ".$file,"GETALBUMCOVER");
debuglog("  STREAM       : ".$stream,"GETALBUMCOVER");
debuglog("  ARTIST       : ".$artist,"GETALBUMCOVER");
debuglog("  ALBUM        : ".$album,"GETALBUMCOVER");
debuglog("  MBID         : ".$mbid,"GETALBUMCOVER");
debuglog("  PATH         : ".$albumpath,"GETALBUMCOVER");
debuglog("  ALBUMURI     : ".$albumuri,"GETALBUMCOVER");
debuglog("  IGNORE LOCAL : ".$ignore_local,"GETALBUMCOVER");

// Attempt to download an image file

$convert_path = find_executable("convert");
if ($convert_path === false) {
    header("HTTP/1.1 404 Not Found");
    ob_flush();
    exit(0);
}

$download_file = "";
if ($file != "") {
    $in_collection = ($stream == "") ? 1 : false;
    $download_file = get_user_file($file, $imgkey, $_FILES['ufile']['tmp_name']);
} elseif ($src != "") {
    $in_collection = ($stream == "") ? 1 : false;
    $download_file = download_file($src, $imgkey, $convert_path);
} elseif ($base64data != "") {
    $in_collection = ($stream == "") ? 1 : false;
    $download_file = save_base64_data($base64data, $imgkey);
} else {
    while (count($searchfunctions) > 0 && $src == "") {
        $fn = array_shift($searchfunctions);
        $src = $fn();
        if ($src != "") {
            $download_file = download_file($src, $imgkey, $convert_path);
            if ($error == 1) {
                $error = 0;
                $src = "";
            }
        }
    }
    if ($src == "") {
        $error = 1;
        debuglog("  No art was found. Try the Tate Modern","GETALBUMCOVER");
    }
}

if ($error == 0) {
    list ($small_file, $big_file) = saveImage($imgkey, $in_collection, $stream);
}

// Now that we've attempted to retrieve an image, even if it failed,
// we need to edit the cached albums list so it doesn't get searched again
// and edit the URL so it points to the correct image if one was found
if ($in_collection !== false) {
    // We only put small_file in the image db. The rest can be calculated from that.
    update_image_db($imgkey, $error, $small_file);
} else if ($error == 0 && $stream != "" && $stream != ROMPR_PLAYLIST_KEY) {
    update_stream_image($stream, $big_file);
}

if ($download_file != "" && file_exists($download_file)) {
    debuglog("Removing downloaded file ".$download_file,"GETALBUMCOVER");
    unlink($download_file);
}

$o = array( 'url' => $small_file, 'origimage' => $big_file, 'delaytime' => $delaytime, 'stream' => $stream);
header('Content-Type: application/json; charset=utf-8');
print json_encode($o);
debuglog("--------------------------------------------","GETALBUMCOVER");
ob_flush();

function check_stream($imgkey) {
    global $stream;
    $retval = array(false, null, null, null, null, null, false);
    if ($stream != "" && $stream != ROMPR_PLAYLIST_KEY) {
        $index = stream_index_from_key($stream);
        $retval[2] = find_stream_name_from_index($index);
        $retval[6] = true;
        debuglog("Found radio station ".$retval[2],"GETALBUMCOVER");
    }
    return $retval;
}

function check_playlist($imgkey) {

    $retval = array(false, null, null, null, null, null, false);
    if (array_key_exists('artist', $_REQUEST)) {
        debuglog("Album is in playlist","GETALBUMCOVER");
        $retval[0] = 1;
        $retval[1] = $_REQUEST['artist'];
        $retval[2] = $_REQUEST['album'];
        $retval[3] = $_REQUEST['mbid'];
        $retval[4] = $_REQUEST['dir'];
        $retval[4] = rawurldecode($_REQUEST['dir']);
        $retval[5] = rawurldecode($_REQUEST['albumuri']);
        $retval[6] = true;
    }
    return $retval;
}

function tryLocal() {
    global $albumpath;
    global $covernames;
    global $album;
    global $artist;
    global $imgkey;
    global $ignore_local;
    if ($albumpath == "" || $albumpath == "." || $albumpath === null || $ignore_local) {
        return "";
    }
    $files = scan_for_images($albumpath);
    foreach ($files as $i => $file) {
        $info = pathinfo($file);
        if (array_key_exists('extension', $info)) {
            $file_name = strtolower(rawurldecode(html_entity_decode(basename($file,'.'.$info['extension']))));
            if ($file_name == $imgkey) {
                debuglog("    Returning archived image","GETALBUMCOVER");
                return $file;
            }
        }
    }
    foreach ($files as $i => $file) {
        $info = pathinfo($file);
        if (array_key_exists('extension', $info)) {
            $file_name = strtolower(rawurldecode(html_entity_decode(basename($file,'.'.$info['extension']))));
            if ($file_name == strtolower($artist." - ".$album) ||
                $file_name == strtolower($album)) {
                debuglog("    Returning file matching album name","GETALBUMCOVER");
                return $file;
            }
        }
    }
    foreach ($covernames as $j => $name) {
        foreach ($files as $i => $file) {
            $info = pathinfo($file);
            if (array_key_exists('extension', $info)) {
                $file_name = strtolower(rawurldecode(html_entity_decode(basename($file,'.'.$info['extension']))));
                if ($file_name == $name) {
                    debuglog("    Returning ".$file,"GETALBUMCOVER");
                    return $file;
                }
            }
        }
    }
    if (count($files) > 1) {
        debuglog("    Returning ".$files[0],"GETALBUMCOVER");
        return $files[0];
    }
    return "";
}

function trySpotify() {
    global $albumuri;
    global $delaytime;
    if ($albumuri == "" || substr($albumuri, 0, 8) != 'spotify:') {
        return "";
    }
    $image = "";
    debuglog("  Trying Spotify for ".$albumuri,"GETALBUMCOVER");

    // php strict prevents me from doing end(explode()) because
    // only variables can be passed by reference. Stupid php.
    $spaffy = explode(":", $albumuri);
    $spiffy = end($spaffy);
    $boop = $spaffy[1];
    $url = 'https://api.spotify.com/v1/'.$boop.'s/'.$spiffy;
    debuglog("      Getting ".$url,"GETALBUMCOVER");
    $content = get_spotify_data($url);

    if ($content['contents'] && $content['contents'] != "") {
        $data = json_decode($content['contents']);
        if (property_exists($data, 'images')) {
            $width = 0;
            foreach ($data->images as $img) {
                if ($img->width > $width) {
                    $width = $img->width;
                    $image = $img->url;
                    debuglog("  Found image with width ".$width,"GETALBUMCOVER");
                    debuglog("  URL is ".$image,"GETALBUMCOVER");
                }
            }
        } else {
            debuglog("    No Spotify Data Found","GETALBUMCOVER");
        }
    } else {
        debuglog("    Spotify API data not retrieved","GETALBUMCOVER");
    }
    $delaytime = 1000;
    if ($image == "" && $boop == 'artist') {
        $image = "newimages/artist-icon.png";
        $o = array( 'url' => $image, 'origimage' => $image, 'delaytime' => $delaytime);
        header('Content-Type: application/json; charset=utf-8');
        print json_encode($o);
        debuglog("--------------------------------------------","GETALBUMCOVER");
        ob_flush();
        exit(0);
    }
    return $image;
}

function tryLastFM() {

    global $artist;
    global $album;
    global $mbid;
    global $delaytime;
    global $mysqlc;
    global $imgkey;
    global $tried_lastfm_once;
    if ($tried_lastfm_once) { return ""; }
    $retval = "";
    $pic = "";
    $cs = -1;

    $sizeorder = array( 0 => 'small', 1 => 'medium', 2 => 'large', 3=> 'extralarge', 4 => 'mega');

    $al = munge_album_name($album);

    debuglog("  Trying last.FM for ".$artist." ".$al,"GETALBUMCOVER");
    $xml = loadXML("http://ws.audioscrobbler.com", "/2.0/?method=album.getinfo&api_key=15f7532dff0b8d84635c757f9f18aaa3&album=".rawurlencode($al)."&artist=".rawurlencode($artist)."&autocorrect=1");
    if ($xml === false) {
        debuglog("    Received error response from Last.FM","GETALBUMCOVER");
        return "";
    } else {
        if ($mbid == "") {
            $mbid = $xml->album->mbid;
            if ($mbid) {
                debuglog("      Last.FM gave us the MBID of '".$mbid."'","GETALBUMCOVER");
                if ($mysqlc) {
                    if (sql_prepare_query(true, null, null, null, "UPDATE Albumtable SET mbid = ? WHERE ImgKey = ? AND mbid IS NULL", (string) $mbid, $imgkey)) {
                        debuglog("        Updated collection with new MBID","GETALBUMCOVER");
                    } else {
                        debuglog("        Failed trying to update collection with new MBID","GETALBUMCOVER");
                    }
                }
                // return nothing here so we can try musicbrainz first
                return "";
            }
        }

        if ($mbid != (string) $xml->album->mbid && $xml->album->mbid) {
            debuglog("    POINT OF INTEREST! LastFM gave us a different MBID - '".$mbid."'' vs '".$xml->album->mbid."'","GETALBUMCOVER");
        }

        foreach ($xml->album->image as $i => $image) {
            $attrs = $image->attributes();
            if ($image) { $pic = $image; }
            $s = array_search($attrs['size'], $sizeorder);
            if ($s > $cs) {
                debuglog("    Image ".$attrs['size']." '".$image."'");
                $retval = $image;
                $cs = $s;
            }
        }
        if ($retval == "") {
            $retval = $pic;
        }
    }
    if ($retval != "") {
        debuglog("    Last.FM gave us ".$retval,"GETALBUMCOVER");
    } else {
        debuglog("    No cover found on Last.FM","GETALBUMCOVER");
    }
    $delaytime = 1000;
    $tried_lastfm_once = true;

    return $retval;

}

function tryGoogle() {
    global $artist;
    global $album;
    global $delaytime;
    $retval = "";
    $squlookle = "AIzaSyDAErKEr1g1J3yqHA0x6Ckr5jubNIF2YX4";
    $nureek = "https://www.googleapis.com/customsearch/v1?key=".$squlookle."&cx=013407992060439718401:d3vpz2xaljs&searchType=image&alt=json";
    $result = url_get_contents($nureek."&q=".urlencode(trim($artist.' '.$album)));
    $json = json_decode($result['contents'], true);
    if (array_key_exists('items', $json)) {
        foreach($json['items'] as $item) {
            $retval = $item['link'];
            break;
        }
    }
    if ($retval != '') {
        debuglog("Found image ".$retval." from Google","GETALBUMCOVER");
    }
    return $retval;
}

function tryMusicBrainz() {
    global $mbid;
    global $delaytime;
    $delaytime = 600;
    if ($mbid == "") {
        return "";
    }
    $retval = "";
    // Let's get some information from musicbrainz about this album
    debuglog("  Getting MusicBrainz release info for ".$mbid,"GETALBUMCOVER");
    $release_info = url_get_contents('http://musicbrainz.org/ws/2/release/'.$mbid.'?inc=release-groups');
    if ($release_info['status'] != "200") {
        debuglog("    Error response from musicbrainz","GETALBUMCOVER");
        return "";
    }
    $x = simplexml_load_string($release_info['contents'], 'SimpleXMLElement', LIBXML_NOCDATA);

    if ($x->{'release'}->{'cover-art-archive'}->{'artwork'} == "true" &&
        $x->{'release'}->{'cover-art-archive'}->{'front'} == "true") {
        debuglog("    Musicbrainz has artwork for this release", "GETALBUMCOVER");
        $retval = "http://coverartarchive.org/release/".$mbid."/front";
    }

    return $retval;

}

function loadXML($domain, $path) {

    $t = url_get_contents($domain.$path);
    if ($t['status'] == "200") {
        return simplexml_load_string($t['contents']);
    }
    return false;

}

function save_base64_data($data, $imgkey) {
    global $error;
    global $convert_path;
    debuglog("  Saving base64 data","GETALBUMCOVER");
    $image = explode('base64,',$data);
    $download_file = "albumart/".$imgkey;
    file_put_contents($download_file, base64_decode($image[1]));
    $o = array();
    $c = $convert_path."identify \"".$download_file."\" 2>&1";
    $r = exec( $c, $o);
    debuglog("    Return value from identify was ".$r,"GETALBUMCOVER");
    $error = 0;
    return $download_file;
}

?>
