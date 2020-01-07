<?php
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");
require_once ("utils/imagefunctions.php");
include ("backends/sql/backend.php");
include ("includes/spotifyauth.php");
include ("getid3/getid3.php");

logger::mark("GETALBUMCOVER", "------- Searching For Album Art --------");
foreach ($_REQUEST as $k => $v) {
	if ($k == 'base64data') {
		logger::log("GETALBUMCOVER", "We have base64 data");
	} else {
		logger::trace("GETALBUMCOVER", $k, '=', $v);
	}
}

$albumimage = new albumImage($_REQUEST);
$delaytime = 1;
$ignore_local = (array_key_exists('ignorelocal', $_REQUEST) && $_REQUEST['ignorelocal'] == 'true') ? true : false;

// Soundcloud/Youtube can be first since that function only returns images for soundcloud tracks, and it's the best way to get those images
if ($albumimage->mbid != "") {
	$searchfunctions = array( 'trySoundcloud', 'tryYoutube','tryLocal', 'trySpotify', 'tryMusicBrainz', 'tryLastFM', 'tryGoogle' );
} else {
	// Try LastFM twice - first time just to get an MBID since coverartarchive images tend to be bigger
	$searchfunctions = array( 'trySoundcloud', 'tryYoutube', 'tryLocal', 'trySpotify', 'tryLastFM', 'tryMusicBrainz', 'tryLastFM', 'tryGoogle' );
}

$result = $albumimage->download_image();
if (!$albumimage->has_source()) {
	// Turn on output buffering in case of PHP notices and errors etc - without this
	// these get sent back to the browser if PHP is in development mode
	ob_start();
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
	logger::mark("GETALBUMCOVER", "No art was found. Try the Tate Modern");
	$result = array();
}
ob_end_clean();
$albumimage->update_image_database();
$result['delaytime'] = $delaytime;
header('Content-Type: application/json; charset=utf-8');
print json_encode($result);
logger::mark("GETALBUMCOVER", "--------------------------------------------");
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
				logger::trace("GETALBUMCOVER", "    Returning archived image");
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
				logger::trace("GETALBUMCOVER", "    Returning file matching album name");
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
					logger::trace("GETALBUMCOVER", "    Returning ".$file);
					return $file;
				}
			}
		}
	}
	if (count($files) > 1) {
		logger::trace("GETALBUMCOVER", "    Returning ".$files[0]);
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
	logger::trace("GETALBUMCOVER", "      Getting ".$url);
	list($success, $content, $status) = get_spotify_data($url);

	if ($success) {
		$data = json_decode($content);
		if (property_exists($data, 'images')) {
			$width = 0;
			foreach ($data->images as $img) {
				if ($img->width > $width) {
					$width = $img->width;
					$image = $img->url;
					logger::trace("GETALBUMCOVER", "  Found image with width ".$width);
					logger::trace("GETALBUMCOVER", "  URL is ".$image);
				}
			}
		} else {
			logger::trace("GETALBUMCOVER", "    No Spotify Data Found");
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
		logger::mark("GETALBUMCOVER", "--------------------------------------------");
		ob_flush();
		exit(0);
	}
	return $image;
}

function trySoundcloud($albumimage) {
	global $delaytime;
	if ($albumimage->albumuri === null || substr($albumimage->albumuri, 0, 11) != 'soundcloud:') {
		return "";
	}
	$image = "newimages/soundcloud-logo.svg";
	logger::mark("GETALBUMCOVER", "  Trying Soundcloud for ".$albumimage->albumuri);
	$spaffy = preg_match("/\.(\d+$)/", $albumimage->albumuri, $matches);
	if ($spaffy) {
		$result = download_soundcloud('tracks/'.$matches[1].'.json');
		if ($result) {
			$data = json_decode($result, true);
			if (array_key_exists('artwork_url', $data) && $data['artwork_url']) {
				$image = $data['artwork_url'];
			}
		}
		$delaytime = 1000;
	}
	return $image;
}

function tryYoutube($albumimage) {
	global $prefs, $delaytime;
	$image = '';
	if ($prefs['google_api_key'] != '' &&
		$albumimage->albumuri !== null &&
		(substr($albumimage->albumuri, 0, 8) == 'youtube:' || substr($albumimage->albumuri, 0, 3 ) == 'yt:')) {

		$image = 'newimages/youtube-logo.svg';
		logger::mark("GETALBUMCOVER", "  Trying Youtube for ".$albumimage->albumuri);
		$spaffy = preg_match("/\.(.+$)/", $albumimage->albumuri, $matches);
		if ($spaffy) {
			$url = 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id='.$matches[1].'&key='.$prefs['google_api_key'];
			$response = getCacheData($url, 'google', true, true);
			if ($response) {
				$data = json_decode($response, true);
				$size = 0;
				// Pain how we can't catch errors if we try to loop something that doesn't exist
				if (is_array($data) &&
					array_key_exists('items', $data) &&
					is_array($data['items']) &&
					count($data['items']) > 0 &&
					array_key_exists('snippet', $data['items'][0]) &&
					array_key_exists('thumbnails', $data['items'][0]['snippet']) &&
					is_array($data['items'][0]['snippet']['thumbnails']))
				{
					foreach ($data['items'][0]['snippet']['thumbnails'] as $thumb) {
						if ($thumb['width'] > $size) {
							$size = $thumb['width'];
							$image = $thumb['url'];
						}
					}
					logger::trace('GETALBUMCOVER', 'Found image on YouTube', $image);
				}
			}
		}
	}
	$delaytime = 1000;
	return $image;
}

function tryLastFM($albumimage) {

	global $delaytime, $mysqlc;
	static $tried_lastfm_once = false;

	if ($tried_lastfm_once) { return ""; }
	$retval = "";
	$pic = "";
	$cs = -1;

	$sizeorder = array( 0 => 'small', 1 => 'medium', 2 => 'large', 3=> 'extralarge', 4 => 'mega');

	$al = munge_album_name($albumimage->album);

	$sa = $albumimage->get_artist_for_search();
	if ($sa == '') {
		logger::mark("GETALBUMCOVER", "  Trying last.FM for ".$al);
		$json = loadJSON('lastfm', "https://ws.audioscrobbler.com/2.0/?album=".rawurlencode($al)."&api_key=15f7532dff0b8d84635c757f9f18aaa3&autocorrect=0&method=album.getinfo&format=json");
	} else if ($sa == 'Podcast') {
		logger::mark("GETALBUMCOVER", "  Trying last.FM for ".$al);
		// Last.FM sometimes works for podcasts if you use Artist
		$json = loadJSON('lastfm', "https://ws.audioscrobbler.com/2.0/?artist=".rawurlencode($al)."&method=album.getinfo&autocorrect=0&api_key=15f7532dff0b8d84635c757f9f18aaa3&format=json");
	} else {
		logger::mark("GETALBUMCOVER", "  Trying last.FM for ".$sa." ".$al);
		$json = loadJSON('lastfm', "https://ws.audioscrobbler.com/2.0/?artist=".rawurlencode($sa)."&album=".rawurlencode($al)."&api_key=15f7532dff0b8d84635c757f9f18aaa3&autocorrect=0&method=album.getinfo&format=json");
	}
	if ($json === false) {
		logger::warn("GETALBUMCOVER", "    Received error response from Last.FM");
		$tried_lastfm_once = true;
		return "";
	} else {
		if (property_exists($json, 'album')) {
			if (property_exists($json->album, 'mbid') && $albumimage->mbid === null && $json->album->mbid) {
				$albumimage->mbid = (string) $json->album->mbid;
				logger::trace("GETALBUMCOVER", "      Last.FM gave us the MBID of '".$albumimage->mbid."'");
				if ($mysqlc) {
					if (sql_prepare_query(true, null, null, null, "UPDATE Albumtable SET mbid = ? WHERE ImgKey = ? AND mbid IS NULL", $albumimage->mbid, $albumimage->get_image_key())) {
						logger::trace("GETALBUMCOVER", "        Updated collection with new MBID");
					} else {
						logger::warn("GETALBUMCOVER", "        Failed trying to update collection with new MBID");
					}
				}
				// return nothing here so we can try musicbrainz first
				return "";
			}

			if (property_exists($json->album, 'image')) {
				foreach ($json->album->image as $image) {
					if ($image->{'#text'}) { $pic = $image->{'#text'}; }
					$s = array_search($image->size, $sizeorder);
					if ($s > $cs) {
						logger::trace("GETALBUMCOVER", "    Image ".$image->size." '".$image->{'#text'}."'");
						$retval = $image->{'#text'};
						$cs = $s;
					}
				}
			}

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
			logger::mark("GETALBUMCOVER", "  Trying Google for",$sa,$ma);
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
			logger::warn("GETALBUMCOVER", "    Error response from Google : ".$json['error']['errors'][0]['reason']);
		}
		if ($retval != '') {
			logger::trace("GETALBUMCOVER", "    Found image ".$retval." from Google");
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
		logger::warn("GETALBUMCOVER", "    Status code ".$d->get_status()." from Musicbrainz");
	}
	return $retval;

}

function loadJSON($domain, $path) {
	$d = new url_downloader(array(
		'url' => $path,
		'cache' => $domain,
		'return_data' => true
	));
	if ($d->get_data_to_file()) {
		return json_decode($d->get_data());
	} else {
		return false;
	}
}

?>
