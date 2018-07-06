<?php
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");
require_once ("utils/imagefunctions.php");
include ("backends/sql/backend.php");
include ("includes/spotifyauth.php");
include ("getid3/getid3.php");

debuglog("------- Searching For Album Art --------","GETALBUMCOVER");
foreach ($_REQUEST as $k => $v) {
    if ($k == 'base64data') {
        debuglog(' Present', ' '.$k ,7);
    } else {
        debuglog(' '.$v, ' '.$k ,7);
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
    debuglog("No art was found. Try the Tate Modern","GETALBUMCOVER");
    $result = array();
}
$albumimage->update_image_database();
$result['delaytime'] = $delaytime;
header('Content-Type: application/json; charset=utf-8');
print json_encode($result);
debuglog("--------------------------------------------","GETALBUMCOVER");
ob_flush();

function tryLocal($albumimage) {
    global $ignore_local;
    global $delaytime;
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
                debuglog("    Returning archived image","GETALBUMCOVER");
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
    debuglog("  Trying Spotify for ".$albumimage->albumuri,"GETALBUMCOVER");

    // php strict prevents me from doing end(explode()) because
    // only variables can be passed by reference. Stupid php.
    $spaffy = explode(":", $albumimage->albumuri);
    $spiffy = end($spaffy);
    $boop = $spaffy[1];
    $url = 'https://api.spotify.com/v1/'.$boop.'s/'.$spiffy;
    debuglog("      Getting ".$url,"GETALBUMCOVER");
    list($success, $content, $status) = get_spotify_data($url);

    if ($success) {
        $data = json_decode($content);
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
        // Hackety Hack
        $image = "newimages/artist-icon.png";
        $o = array( 'small' => $image, 'medium' => $image, 'asdownloaded' => $image, 'delaytime' => $delaytime);
        header('Content-Type: application/json; charset=utf-8');
        print json_encode($o);
        debuglog("--------------------------------------------","GETALBUMCOVER");
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
        debuglog("  Trying last.FM for ".$al,"GETALBUMCOVER");
        $xml = loadXML('lastfm', "http://ws.audioscrobbler.com/2.0/?method=album.getinfo&api_key=15f7532dff0b8d84635c757f9f18aaa3&album=".rawurlencode($al)."&autocorrect=1");
    } else if ($sa == 'Podcast') {
        debuglog("  Trying last.FM for ".$al,"GETALBUMCOVER");
        // Last.FM sometimes works for podcasts if you use Artist
        $xml = loadXML('lastfm', "http://ws.audioscrobbler.com/2.0/?method=album.getinfo&api_key=15f7532dff0b8d84635c757f9f18aaa3&artist=".rawurlencode($al)."&autocorrect=1");
    } else {
        debuglog("  Trying last.FM for ".$sa." ".$al,"GETALBUMCOVER");
        $xml = loadXML('lastfm', "http://ws.audioscrobbler.com/2.0/?method=album.getinfo&api_key=15f7532dff0b8d84635c757f9f18aaa3&album=".rawurlencode($al)."&artist=".rawurlencode($sa)."&autocorrect=1");
    }
    if ($xml === false) {
        debuglog("    Received error response from Last.FM","GETALBUMCOVER");
        $tried_lastfm_once = true;
        return "";
    } else {
        if ($albumimage->mbid === null && $xml->album->mbid) {
            $albumimage->mbid = (string) $xml->album->mbid;
            debuglog("      Last.FM gave us the MBID of '".$albumimage->mbid."'","GETALBUMCOVER");
            if ($mysqlc) {
                if (sql_prepare_query(true, null, null, null, "UPDATE Albumtable SET mbid = ? WHERE ImgKey = ? AND mbid IS NULL", $albumimage->mbid, $albumimage->get_image_key())) {
                    debuglog("        Updated collection with new MBID","GETALBUMCOVER");
                } else {
                    debuglog("        Failed trying to update collection with new MBID","GETALBUMCOVER");
                }
            }
            // return nothing here so we can try musicbrainz first
            return "";
        }

        foreach ($xml->album->image as $i => $image) {
            $attrs = $image->attributes();
            if ($image) { $pic = $image; }
            $s = array_search($attrs['size'], $sizeorder);
            if ($s > $cs) {
                debuglog("    Image ".$attrs['size']." '".$image."'","GETALBUMCOVER");
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

function tryGoogle($albumimage) {
    global $delaytime;
    global $prefs;
    $retval = "";
    if ($prefs['google_api_key'] != '' && $prefs['google_search_engine_id'] != '') {
        $nureek = "https://www.googleapis.com/customsearch/v1?key=".$prefs['google_api_key']."&cx=".$prefs['google_search_engine_id']."&searchType=image&alt=json";
        $sa = trim($albumimage->get_artist_for_search());
        $ma = munge_album_name($albumimage->album);
        if ($sa == '') {
            debuglog("  Trying Google for ".$ma,"GETALBUMCOVER");
            $uri = $nureek."&q=".urlencode($ma);
        } else {
            debuglog("  Trying Google for ".$sa." ".$ma,"GETALBUMCOVER");
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
            debuglog("    Error response from Google : ".$json['error']['errors'][0]['reason'],"GETALBUMCOVER");
        }
        if ($retval != '') {
            debuglog("    Found image ".$retval." from Google","GETALBUMCOVER");
            $delaytime = 1000;
        }
    } else {
        debuglog("  Not trying Google because no API Key or Search Engine ID","GETALBUMCOVER");
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
    debuglog("  Getting MusicBrainz release info for ".$albumimage->mbid,"GETALBUMCOVER");
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
            debuglog("    Musicbrainz has artwork for this release", "GETALBUMCOVER");
            $retval = "http://coverartarchive.org/release/".$albumimage->mbid."/front";
        }
    } else {
        debuglog("    Status code ".$d->get_status()." from Musicbrainz","GETALBUMCOVER");
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
