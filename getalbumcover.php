<?php
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");
require_once ("utils/imagefunctions.php");
include ("backends/sql/backend.php");
include ("includes/spotifyauth.php");
include ("getid3/getid3.php");

logger::shout("GETALBUMCOVER", "------- Searching For Album Art --------");
foreach ($_REQUEST as $k => $v) {
    if ($k == 'base64data') {
        logger::log("GETALBUMCOVER", "Base64 Data", $k);
    } else {
        logger::log("GETALBUMCOVER", $v, $k);
    }
}

$albumimage = new albumImage($_REQUEST);
$delaytime = 1;
$ignore_local = (array_key_exists('ignorelocal', $_REQUEST) && $_REQUEST['ignorelocal'] == 'true') ? true : false;
$tried_lastfm_once = false;

if ($albumimage->mbid != "") {
    $searchfunctions = array( 'tryLocal', 'trySpotify', 'tryMusicBrainz', 'tryLastFM', 'tryGoogle' );
} else {
    // Try LastFM twice - first time just to get an MBID since coverartarchive images tend to be bigger
    $searchfunctions = array( 'tryLocal', 'trySpotify', 'tryLastFM', 'tryMusicBrainz', 'tryLastFM', 'tryGoogle' );
}

$result = $albumimage->download_image();
if (!$albumimage->has_source()) {
    while (count($searchfunctions) > 0 && $result === false) {
        $fn = array_shift($searchfunctions);
        $src = $fn($albumimage);
        if ($src != "") {
            $albumimage->set_source($src);
            $result = $albumimage->download_image();
        }
    }
}
if ($result === false) {
    $result = $albumimage->set_default();
}
if ($result === false) {
    logger::shout("GETALBUMCOVER", "No art was found. Try the Tate Modern");
    $result = array();
}
$albumimage->update_image_database();
$result['delaytime'] = $delaytime;
header('Content-Type: application/json; charset=utf-8');
print json_encode($result);
logger::shout("GETALBUMCOVER", "--------------------------------------------");
ob_flush();

function tryLocal($albumimage) {
    global $ignore_local;
    global $delaytime;
    logger::mark("GETALBUMCOVER", "  Checking for local images");
    $covernames = array("cover", "albumart", "thumb", "albumartsmall", "front");
    if ($albumimage->albumpath == "" || $albumimage->albumpath == "." || $albumimage->albumpath === null || $ignore_local) {
        return "";
    }
    $files = scan_for_images($albumimage->albumpath);
    foreach ($files as $i => $file) {
        $info = pathinfo($file);
        if (array_key_exists('extension', $info)) {
            $file_name = strtolower(rawurldecode(html_entity_decode(basename($file,'.'.$info['extension']))));
            if ($file_name == $albumimage->get_image_key()) {
                logger::log("GETALBUMCOVER", "    Returning archived image");
                return $file;
            }
        }
    }
    foreach ($files as $i => $file) {
        $info = pathinfo($file);
        if (array_key_exists('extension', $info)) {
            $file_name = strtolower(rawurldecode(html_entity_decode(basename($file,'.'.$info['extension']))));
            if ($file_name == strtolower($albumimage->artist." - ".$albumimage->album) ||
                $file_name == strtolower($albumimage->album)) {
                logger::log("GETALBUMCOVER", "    Returning file matching album name");
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
                    logger::log("GETALBUMCOVER", "    Returning ".$file);
                    return $file;
                }
            }
        }
    }
    if (count($files) > 1) {
        logger::log("GETALBUMCOVER", "    Returning ".$files[0]);
        $delaytime = 1;
        return $files[0];
    }
    return "";
}

function trySpotify($albumimage) {
    global $delaytime;
    if ($albumimage->albumuri === null || substr($albumimage->albumuri, 0, 8) != 'spotify:') {
        return "";
    }
    $image = "";
    logger::mark("GETALBUMCOVER", "  Trying Spotify for ".$albumimage->albumuri);

    // php strict prevents me from doing end(explode()) because
    // only variables can be passed by reference. Stupid php.
    $spaffy = explode(":", $albumimage->albumuri);
    $spiffy = end($spaffy);
    $boop = $spaffy[1];
    $url = 'https://api.spotify.com/v1/'.$boop.'s/'.$spiffy;
    logger::log("GETALBUMCOVER", "      Getting ".$url);
    list($success, $content, $status) = get_spotify_data($url);

    if ($success) {
        $data = json_decode($content);
        if (property_exists($data, 'images')) {
            $width = 0;
            foreach ($data->images as $img) {
                if ($img->width > $width) {
                    $width = $img->width;
                    $image = $img->url;
                    logger::log("GETALBUMCOVER", "  Found image with width ".$width);
                    logger::log("GETALBUMCOVER", "  URL is ".$image);
                }
            }
        } else {
            logger::log("GETALBUMCOVER", "    No Spotify Data Found");
        }
    } else {
        logger::warn("GETALBUMCOVER", "    Spotify API data not retrieved");
    }
    $delaytime = 1000;
    if ($image == "" && $boop == 'artist') {
        // Hackety Hack
        $image = "newimages/artist-icon.png";
        $o = array( 'small' => $image, 'medium' => $image, 'asdownloaded' => $image, 'delaytime' => $delaytime);
        header('Content-Type: application/json; charset=utf-8');
        print json_encode($o);
        logger::shout("GETALBUMCOVER", "--------------------------------------------");
        ob_flush();
        exit(0);
    }
    return $image;
}

function tryLastFM($albumimage) {

    global $delaytime;
    global $mysqlc;
    global $tried_lastfm_once;
    if ($tried_lastfm_once) { return ""; }
    $retval = "";
    $pic = "";
    $cs = -1;

    $sizeorder = array( 0 => 'small', 1 => 'medium', 2 => 'large', 3=> 'extralarge', 4 => 'mega');

    $al = munge_album_name($albumimage->album);

    $sa = $albumimage->get_artist_for_search();
    if ($sa == '') {
        logger::mark("GETALBUMCOVER", "  Trying last.FM for ".$al);
        $xml = loadXML('lastfm', "https://ws.audioscrobbler.com/2.0/?method=album.getinfo&api_key=15f7532dff0b8d84635c757f9f18aaa3&album=".rawurlencode($al)."&autocorrect=1");
    } else if ($sa == 'Podcast') {
        logger::mark("GETALBUMCOVER", "  Trying last.FM for ".$al);
        // Last.FM sometimes works for podcasts if you use Artist
        $xml = loadXML('lastfm', "https://ws.audioscrobbler.com/2.0/?method=album.getinfo&api_key=15f7532dff0b8d84635c757f9f18aaa3&artist=".rawurlencode($al)."&autocorrect=1");
    } else {
        logger::mark("GETALBUMCOVER", "  Trying last.FM for ".$sa." ".$al);
        $xml = loadXML('lastfm', "https://ws.audioscrobbler.com/2.0/?method=album.getinfo&api_key=15f7532dff0b8d84635c757f9f18aaa3&album=".rawurlencode($al)."&artist=".rawurlencode($sa)."&autocorrect=1");
    }
    if ($xml === false) {
        logger::fail("GETALBUMCOVER", "    Received error response from Last.FM");
        $tried_lastfm_once = true;
        return "";
    } else {
        if ($albumimage->mbid === null && $xml->album->mbid) {
            $albumimage->mbid = (string) $xml->album->mbid;
            logger::log("GETALBUMCOVER", "      Last.FM gave us the MBID of '".$albumimage->mbid."'");
            if ($mysqlc) {
                if (sql_prepare_query(true, null, null, null, "UPDATE Albumtable SET mbid = ? WHERE ImgKey = ? AND mbid IS NULL", $albumimage->mbid, $albumimage->get_image_key())) {
                    logger::trace("GETALBUMCOVER", "        Updated collection with new MBID");
                } else {
                    logger::fail("GETALBUMCOVER", "        Failed trying to update collection with new MBID");
                }
            }
            // return nothing here so we can try musicbrainz first
            return "";
        }

        try {
            if (is_array($xml->album)) {
                foreach ($xml->album->image as $i => $image) {
                    $attrs = $image->attributes();
                    if ($image) { $pic = $image; }
                    $s = array_search($attrs['size'], $sizeorder);
                    if ($s > $cs) {
                        logger::trace("GETALBUMCOVER", "    Image ".$attrs['size']." '".$image."'");
                        $retval = $image;
                        $cs = $s;
                    }
                }
            }
        } catch (Exception $e) {
            logger::fail("GETALBUMCOVER", "    Last.FM response was total monkeys");
        }
        if ($retval == "") {
            $retval = $pic;
        }
    }
    if ($retval != "") {
        logger::log("GETALBUMCOVER", "    Last.FM gave us ".$retval);
    } else {
        logger::log("GETALBUMCOVER", "    No cover found on Last.FM");
    }
    $delaytime = 1000;
    $tried_lastfm_once = true;

    return $retval;

}

function tryGoogle($albumimage) {
    global $delaytime;
    global $prefs;
    $retval = "";
    if ($prefs['google_api_key'] != '' && $prefs['google_search_engine_id'] != '') {
        $nureek = "https://www.googleapis.com/customsearch/v1?key=".$prefs['google_api_key']."&cx=".$prefs['google_search_engine_id']."&searchType=image&alt=json";
        $sa = trim($albumimage->get_artist_for_search());
        $ma = munge_album_name($albumimage->album);
        if ($sa == '') {
            logger::mark("GETALBUMCOVER", "  Trying Google for",$ma);
            $uri = $nureek."&q=".urlencode($ma);
        } else {
            logger::log("GETALBUMCOVER", "  Trying Google for",$sa,$ma);
            $uri = $nureek."&q=".urlencode($sa.' '.$ma);
        }
        $d = new url_downloader(array(
            'url' => $uri,
            'cache' => 'google',
            'return_data' => true
        ));
        $d->get_data_to_file();
        $json = json_decode($d->get_data(), true);
        if (array_key_exists('items', $json)) {
            foreach($json['items'] as $item) {
                $retval = $item['link'];
                break;
            }
        } else if (array_key_exists('error', $json)) {
            logger::fail("GETALBUMCOVER", "    Error response from Google : ".$json['error']['errors'][0]['reason']);
        }
        if ($retval != '') {
            logger::log("GETALBUMCOVER", "    Found image ".$retval." from Google");
            $delaytime = 1000;
        }
    } else {
        logger::mark("GETALBUMCOVER", "  Not trying Google because no API Key or Search Engine ID");
    }
    return $retval;
}

function tryMusicBrainz($albumimage) {
    global $delaytime;
    $delaytime = 600;
    if ($albumimage->mbid === null) {
        return "";
    }
    $retval = "";
    // Let's get some information from musicbrainz about this album
    logger::mark("GETALBUMCOVER", "  Getting MusicBrainz release info for ".$albumimage->mbid);
    $url = 'http://musicbrainz.org/ws/2/release/'.$albumimage->mbid.'?inc=release-groups';
    $d = new url_downloader(array(
        'url' => $url,
        'cache' => 'musicbrainz',
        'return_data' => true
    ));
    if ($d->get_data_to_file()) {
        $x = simplexml_load_string($d->get_data(), 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($x->{'release'}->{'cover-art-archive'}->{'artwork'} == "true" &&
            $x->{'release'}->{'cover-art-archive'}->{'front'} == "true") {
            logger::log("GETALBUMCOVER", "    Musicbrainz has artwork for this release");
            $retval = "http://coverartarchive.org/release/".$albumimage->mbid."/front";
        }
    } else {
        logger::fail("GETALBUMCOVER", "    Status code ".$d->get_status()." from Musicbrainz");
    }
    return $retval;

}

function loadXML($domain, $path) {
    $d = new url_downloader(array(
        'url' => $path,
        'cache' => $domain,
        'return_data' => true
    ));
    if ($d->get_data_to_file()) {
        return simplexml_load_string($d->get_data());
    } else {
        return false;
    }
}

?>
