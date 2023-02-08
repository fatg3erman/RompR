<?php
chdir('..');
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");
include ("getid3/getid3.php");
prefs::$database = new collection_base();
logger::debug("GETALBUMCOVER", "------- Searching For Album Art --------");
foreach ($_REQUEST as $k => $v) {
	if ($k == 'base64data') {
		logger::debug("GETALBUMCOVER", "We have base64 data");
	} else {
		logger::trace("GETALBUMCOVER", $k, '=', $v);
	}
}
$opts = $_REQUEST;
if (array_key_exists('ufile', $_FILES)) {
	logger::trace('GETALBUMCOVER', 'File', $_FILES['ufile']['tmp_name']);
	$opts['file'] = basename($_FILES['ufile']['tmp_name']);
}

$albumimage = new albumImage($opts);
$delaytime = 1;
$ignore_local = (array_key_exists('ignorelocal', $_REQUEST) && $_REQUEST['ignorelocal'] == 'true') ? true : false;

// Soundcloud can be first since that function only returns images for soundcloud tracks, and it's the best way to get those images
// Try LastFM twice - first time just to get an MBID since coverartarchive images tend to be bigger.
// We also do the MBID check early on because it will update the MBID in the database which is useful for the info panel
// The requests are cached locally so it's only one request to Last.FM

$searchfunctions = array(
	'tryLocal',
	'tryPlayer',
	'trySoundcloud',
	'tryLastFMForMBID',
	// 'trySpotify',
	'tryMusicBrainz',
	'tryLastFM',
	'tryBing'
);

$player = new player();

if (array_key_exists('source', $opts) || array_key_exists('base64data', $opts) || array_key_exists('file', $opts)) {
	$result = false;
} else {
	$result = $albumimage->check_archive_image_exists();
}
if ($result === false) {
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
				if (strpos($src, 'prefs/temp') === 0) {
					logger::debug('GETALBUMCOVER', 'Deleting temp file',$src);
					unlink($src);
				}
			}
		}
	}
}
if ($result === false) {
	$result = $albumimage->set_default();
}
if ($result === false) {
	logger::debug("GETALBUMCOVER", "No art was found. Try the Tate Modern");
	$result = array();
}
ob_end_clean();
$albumimage->update_image_database();
$result['delaytime'] = $delaytime;
header('Content-Type: application/json; charset=utf-8');
print json_encode($result);
logger::debug("GETALBUMCOVER", "--------------------------------------------");
// ob_flush();

function tryLocal($albumimage) {
	global $ignore_local;
	global $delaytime;
	if ($albumimage->albumpath == "" || $albumimage->albumpath == "." || $albumimage->albumpath === null || $ignore_local) {
		return "";
	}
	logger::debug("GETALBUMCOVER", "  Checking for local images");
	$covernames = array("folder", "cover", "albumart", "thumb", "albumartsmall", "front");
	$files = imageFunctions::scan_for_local_images($albumimage->albumpath);
	foreach ($files as $i => $file) {
		$info = pathinfo($file);
		if (array_key_exists('extension', $info)) {
			$file_name = strtolower(rawurldecode(html_entity_decode(basename($file,'.'.$info['extension']))));
			if ($file_name == $albumimage->get_image_key()) {
				logger::debug("GETALBUMCOVER", "Returning archived image");
				$delaytime = 1;
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
				logger::debug("GETALBUMCOVER", "Returning file matching album name");
				$delaytime = 1;
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
					logger::debug("GETALBUMCOVER", "Returning ".$file);
					$delaytime = 1;
					return $file;
				}
			}
		}
	}
	if (count($files) > 0) {
		logger::debug("GETALBUMCOVER", "Returning ".$files[0]);
		$delaytime = 1;
		return $files[0];
	}
	return "";
}

function trySpotify($albumimage) {
	global $delaytime;
	if ($albumimage->albumuri === null || substr($albumimage->albumuri, 0, 8) != 'spotify:') {
		logger::core('GETALBUMCOVER', 'Not a Spotify album');
		return "";
	}
	$image = "";
	logger::debug("GETALBUMCOVER", "  Trying Spotify for ".$albumimage->albumuri);

	// php strict prevents me from doing end(explode()) because
	// only variables can be passed by reference. Stupid php.
	$spaffy = explode(":", $albumimage->albumuri);
	$spiffy = end($spaffy);
	$boop = $spaffy[1];
	$fn = $boop.'_getinfo';
	$content = spotify::$fn(['id' => $spiffy, 'cache' => true], false);
	$data = json_decode($content, true);
	if (array_key_exists('images', $data)) {
		$width = 0;
		foreach ($data['images'] as $img) {
			if ($img['width'] > $width) {
				$width = $img['width'];
				$image = $img['url'];
				logger::debug("GETALBUMCOVER", "  Found image with width ".$width);
				logger::debug("GETALBUMCOVER", "  URL is ".$image);
			}
		}
	} else {
		logger::debug("GETALBUMCOVER", "    No Spotify Data Found");
	}
	$delaytime = 1000;
	if ($image == "" && $boop == 'artist') {
		// Hackety Hack
		$image = "newimages/artist-icon.png";
		$o = array( 'small' => $image, 'medium' => $image, 'asdownloaded' => $image, 'delaytime' => $delaytime);
		header('Content-Type: application/json; charset=utf-8');
		print json_encode($o);
		logger::debug("GETALBUMCOVER", "--------------------------------------------");
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
	logger::debug("GETALBUMCOVER", "  Trying Soundcloud for ".$albumimage->albumuri);
	$spaffy = preg_match("/\.(\d+$)/", $albumimage->albumuri, $matches);
	if ($spaffy) {
		$result = soundcloud::track_info(['trackid' => $matches[1]], false);
		if ($result) {
			$data = json_decode($result, true);
			if (array_key_exists('artwork_url', $data) && $data['artwork_url']) {
				$image = $data['artwork_url'];
			}
		}
		$delaytime = 1000;
	}
	logger::debug("GETALBUMCOVER", "SoundCloud returning",$image);
	return $image;
}

function tryLastFMForMBID($albumimage) {
	if ($albumimage->mbid !== null) {
		logger::debug("GETALBUMCOVER", "Image already has an MBID, skipping this step");
		return '';
	}
	$options = getLastFMUrl($albumimage);
	$json = json_decode(lastfm::album_getinfo($options, false), true);
	if (array_key_exists('error', $json)) {
		logger::debug("GETALBUMCOVER", "Received error response from Last.FM");
		return "";
	} else {
		if (array_key_exists('album', $json)) {
			if (array_key_exists('mbid', $json['album']) && $json['album']['mbid']) {
				$albumimage->mbid = (string) $json['album']['mbid'];
				logger::debug("GETALBUMCOVER", "Last.FM gave us the MBID of '".$albumimage->mbid."'");
				$db = new musicCollection();
				$db->update_album_mbid($albumimage->mbid, $albumimage->get_image_key());
			}
		}
	}
	// return nothing here so we can try musicbrainz first
	$delaytime = 1000;
	return "";
}

function tryLastFM($albumimage) {

	global $delaytime;
	$retval = "";
	$pic = "";
	$cs = -1;

	$sizeorder = array( 0 => 'small', 1 => 'medium', 2 => 'large', 3  => 'extralarge', 4 => 'mega');

	$options = getLastFMUrl($albumimage);
	$json = json_decode(lastfm::album_getinfo($options, false), true);

	if (array_key_exists('error', $json)) {
		logger::debug("GETALBUMCOVER", "Received error response from Last.FM");
		return "";
	} else {
		if (array_key_exists('album', $json)) {
			if (array_key_exists('image', $json['album'])) {
				foreach ($json['album']['image'] as $image) {
					if ($image['#text'])
						$pic = $image['#text'];
					$s = array_search($image['size'], $sizeorder);
					if ($s > $cs) {
						logger::debug("GETALBUMCOVER", "    Image ".$image['size']." '".$image['#text']."'");
						$retval = $image['#text'];
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
		logger::debug("GETALBUMCOVER", "Last.FM gave us ".$retval);
	} else {
		logger::debug("GETALBUMCOVER", "No cover found on Last.FM");
	}
	$delaytime = 1000;

	return $retval;

}

function getLastFMUrl($albumimage) {
	$al = munge_album_name($albumimage->album);
	$sa = $albumimage->get_artist_for_search();
	if ($sa == '') {
		logger::debug("GETALBUMCOVER", "Trying last.FM for album ".$al);
		$options = ['album' => $al, 'artist' => 'Various Artists', 'autocorrect' => 1, 'cache' => true];
	} else if ($sa == 'Podcast') {
		logger::debug("GETALBUMCOVER", "Trying last.FM for artist ".$al);
		$options = ['artist' => $al, 'autocorrect' => 1, 'cache' => true];
	} else {
		logger::debug("GETALBUMCOVER", "Trying last.FM for ".$sa." ".$al);
		$options = ['album' => $al, 'artist' => $sa, 'autocorrect' => 1, 'cache' => true];
	}
	return $options;
}

function tryMusicBrainz($albumimage) {
	global $delaytime;
	$delaytime = 600;
	if ($albumimage->mbid === null) {
		logger::debug('GETALBUMCOVER', 'No MBID, cannot try Musicbrainz');
		return "";
	}
	logger::debug("GETALBUMCOVER", "Getting MusicBrainz release info for ".$albumimage->mbid);
	$data = musicbrainz::album_getreleases(['mbid' => $albumimage->mbid], false);
	$d = json_decode($data, true);
	if (is_array($d) && array_key_exists('cover-art-archive', $d)) {
		if ($d['cover-art-archive']['artwork'] == 1 && $d['cover-art-archive']['front'] == 1) {
			logger::debug('GETALBUMCOVER', 'Musicbrainz has artwork for this release');
			return musicbrainz::COVER_URL.$albumimage->mbid."/front";
		}
	}
	logger::debug('GETALBUMCOVER', 'Musicbrainz does not have artwork for this album');
	return "";

}

function tryBing($albumimage) {
	global $delaytime;
	$delaytime = 1000;
	$retval = '';
	$searchterm = $albumimage->get_artist_for_search().' '.munge_album_name($albumimage->album);
	logger::debug('GETALBUMCOVER', 'Trying Bing Image Search for',$searchterm);
	$data = bing::image_search(['q' => $searchterm], false);
	$d = json_decode($data, true);
	if (array_key_exists('value', $d) && is_array($d['value']) && count($d['value']) > 0) {
		$retval = $d['value'][0]['contentUrl'];
		logger::debug('GETALBUMCOVER', 'Bing gaves us',$retval);
	} else {
		logger::debug('GETALBUMCOVER', 'Bing came up with nowt');
	}
	return $retval;
}

function tryPlayer($albumimage) {
	global $player, $delaytime;
	$retval = $player->search_for_album_image($albumimage);
	if ($retval != '') {
		$delaytime = 500;
	}
	return $retval;
}

?>
