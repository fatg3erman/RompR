<?php

// This is for getting a remote playlist from a radio station - eg PLS or ASX files
// This script parses that remote playlist and creates a local XSPF which will be
// used for adding the stream(s) to the playlist and for putting the info into the playlist

// Called with : 	url   	:  	The remote playlist to download or stream to add
//					station :	The name of the radio station (Groove Salad)
//					image   :	The image to use in the playlist

// The generated playlists can be updated later if no information is known -
// the playlist will handle that when it gets stream info from mpd

function load_internet_playlist($url, $image, $station, $return_tracks = false) {
	
	$playlist = download_internet_playlist($url, $image, $station);
	if ($playlist !== false) {
		if (preg_match('/opml\.radiotime\.com/', $url)) {
			debuglog("Checking actual stream from radiotime Tune API","RADIO_PLAYLIST");
			$playlist = download_internet_playlist($playlist->get_first_track(), $image, $station);
		}
		if ($playlist !== false) {
			if ($return_tracks) {
				return $playlist->tracks;
			} else {
				$playlist->updateDatabase();
				return $playlist->getTracksToAdd();
			}
		} else {
			return array();
		}
	}

}

function download_internet_playlist($url, $image, $station) {

	$station = ($station == 'null') ? ROMPR_UNKNOWN_STREAM : $station;
	$image = ($image == 'null') ? '' : $image;
	$url = trim($url);
	debuglog("Getting Internet Stream:","RADIO_PLAYLIST");
	debuglog("  url : ".$url,"RADIO_PLAYLIST");
	debuglog("  station : ".$station,"RADIO_PLAYLIST");
	debuglog("  image : ".$image,"RADIO_PLAYLIST");

	if ($url) {

		$path = $url;
		$type = null;

		$content = url_get_contents($url, ROMPR_IDSTRING, false, true, true, null, null, null, 10, 10);
		debuglog("Status Code Is ".$content['status'],"RADIO_PLAYLIST");

		$content_type = $content['info']['content_type'];
		// To cope with charsets in the header...
		// case "audio/x-scpls;charset=UTF-8";
		$content_type = trim_content_type($content_type);
		debuglog("Content Type Is ".$content_type,"RADIO_PLAYLIST");

		switch ($content_type) {
			case "video/x-ms-asf":
				debuglog("Playlist Is ".$content['contents'],"RADIO_PLAYLIST",8);
				$type = asfOrasx($content['contents']);
				break;

			case "audio/x-scpls":
				debuglog("Playlist Is ".$content['contents'],"RADIO_PLAYLIST",8);
				$type = "pls";
				break;

			case "audio/x-mpegurl":
			case "application/x-mpegurl":
				debuglog("Playlist Is ".$content['contents'],"RADIO_PLAYLIST",8);
				$type = "m3u";
				break;

			case "application/xspf+xml":
				debuglog("Playlist Is ".$content['contents'],"RADIO_PLAYLIST",8);
				$type = "xspf";
				break;

			case "audio/mpeg":
				$type = "stream";
				break;
				
			case "text/html":
				debuglog("HTML page returned!","RADIO_PLAYLIST");
				header('HTTP/1.1 404 Not Found');
				exit(0);
		}
		debuglog("Playlist Type From Content Type is ".$type,"RADIO_PLAYLIST");

		if ($type == "" || $type == null) {
			$type = pathinfo($path, PATHINFO_EXTENSION);
			$qpos = strpos($type, "?");
		  	if ($qpos != false) $type = substr($type, 0, $qpos);
			debuglog("Playlist Type From URL is ".$type,"RADIO_PLAYLIST");
		}

		if (($type == "" || $type == null) && preg_match('#www.radio-browser.info/webservice/v2/m3u#', $url)) {
			$type = 'm3u';
			debuglog("Playlist Type From URL is ".$type,"RADIO_PLAYLIST");
		}

		if (($type == "" || $type == null) && preg_match('#www.radio-browser.info/webservice/v2/pls#', $url)) {
			$type = 'pls';
			debuglog("Playlist Type From URL is ".$type,"RADIO_PLAYLIST");
		}

		$playlist = null;

		switch ($content['status']) {
			case '200':
				switch ($type) {
					case "pls":
					case "PLS":
						$playlist = new plsFile($content['contents'], $url, $station, $image);
						break;

					case "asx":
					case "ASX":
						$playlist = new asxFile($content['contents'], $url, $station, $image);
						break;

					case "asf":
					case "ASF":
						$playlist = new asfFile($content['contents'], $url, $station, $image);
						break;

					case "xspf":
					case "XSPF":
						$playlist = new xspfFile($content['contents'], $url, $station, $image);
						break;

					case "m3u":
					case "m3u8":
					case "M3U":
					case "M3U8":
						$playlist = new m3uFile($content['contents'], $url, $station, $image);
						break;

					case "stream":
					case "mp3":
						$playlist = new possibleStreamUrl($url, $station, $image);
						break;

					default;
						debuglog("Unknown Playlist Type - treating as stream URL","RADIO_PLAYLIST");
						$playlist = new possibleStreamUrl($url, $station, $image);
						break;
				}
				break;
			
			case '404':
				debuglog("404 Error trying to download URL","RADIO_PLAYLIST");
				break;

			default:
				debuglog("Unexpected cURL status ".$content['status']." - treating as stream URL","RADIO_PLAYLIST");
				$playlist = new possibleStreamUrl($url, $station, $image);
		}

		if ($playlist) {
			return $playlist;
		} else {
			debuglog("Could not determine playlist type","RADIO_PLAYLIST");
			header("HTTP/1.1 404 Not Found");
			return false;
		}
	}
}

function asfOrasx($s) {
	$type = null;
	if (preg_match('/^\[Reference\]/', $s)) {
		debuglog("Type of playlist determined as asf","RADIO_PLAYLIST");
		$type = "asf";
	} else if (preg_match('/^<ASX /', $s)) {
		debuglog("Type of playlist determined as asx","RADIO_PLAYLIST");
		$type = "asx";
	} else if (preg_match('/^http/', $s)) {
		debuglog("Type of playlist determined as m3u-like","RADIO_PLAYLIST");
		$type = "m3u";
	}
	return $type;
}

?>
