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

function download_internet_playlist($url, $image, $station) {

	$station = ($station == 'null') ? ROMPR_UNKNOWN_STREAM : $station;
	$image = ($image == 'null') ? '' : $image;
	$url = trim($url);
	logger::mark("RADIO_PLAYLIST", "Getting Internet Stream:");
	logger::log("RADIO_PLAYLIST", "  url : ".$url);
	logger::log("RADIO_PLAYLIST", "  station : ".$station);
	logger::log("RADIO_PLAYLIST", "  image : ".$image);

	if ($url) {

		$path = $url;
		$type = null;

		$d = new url_downloader(array(
			'url' => $url,
			'timeout' => 10,
			'connection_timeout' => 10
		));
		$d->get_data_to_string();

		$content_type = $d->get_content_type();
		// To cope with charsets in the header...
		// case "audio/x-scpls;charset=UTF-8";
		$content_type = trim_content_type($content_type);
		logger::trace("RADIO_PLAYLIST", "Content Type Is ".$content_type);

		switch ($content_type) {
			case "video/x-ms-asf":
				logger::debug("RADIO_PLAYLIST", "Playlist Is ".PHP_EOL.$d->get_data());
				$type = asfOrasx($d->get_data());
				break;

			case "audio/x-scpls":
				logger::debug("RADIO_PLAYLIST", "Playlist Is ".PHP_EOL.$d->get_data());
				$type = "pls";
				break;

			case "audio/x-mpegurl":
			case "application/x-mpegurl":
				logger::debug("RADIO_PLAYLIST", "Playlist Is ".PHP_EOL.$d->get_data());
				$type = "m3u";
				break;

			case "application/xspf+xml":
				logger::debug("RADIO_PLAYLIST", "Playlist Is ".PHP_EOL.$d->get_data());
				$type = "xspf";
				break;

			case "audio/mpeg":
				$type = "stream";
				break;

			case "text/html":
				logger::debug("RADIO_PLAYLIST", "HTML page returned!");
				header('HTTP/1.1 404 Not Found');
				exit(0);
		}
		logger::trace("RADIO_PLAYLIST", "Playlist Type From Content Type is ".$type);

		if ($type == "" || $type == null) {
			$type = pathinfo($path, PATHINFO_EXTENSION);
			$qpos = strpos($type, "?");
			if ($qpos != false) $type = substr($type, 0, $qpos);
			logger::trace("RADIO_PLAYLIST", "Playlist Type From URL is ".$type);
		}

		if (($type == "" || $type == null) && preg_match('#www.radio-browser.info/webservice/v2/m3u#', $url)) {
			$type = 'm3u';
			logger::trace("RADIO_PLAYLIST", "Playlist Type From URL is ".$type);
		}

		if (($type == "" || $type == null) && preg_match('#www.radio-browser.info/webservice/v2/pls#', $url)) {
			$type = 'pls';
			logger::trace("RADIO_PLAYLIST", "Playlist Type From URL is ".$type);
		}

		$playlist = null;

		switch ($d->get_status()) {
			case '200':
				switch ($type) {
					case "pls":
					case "PLS":
						$playlist = new plsFile($d->get_data(), $url, $station, $image);
						break;

					case "asx":
					case "ASX":
						$playlist = new asxFile($d->get_data(), $url, $station, $image);
						break;

					case "asf":
					case "ASF":
						$playlist = new asfFile($d->get_data(), $url, $station, $image);
						break;

					case "xspf":
					case "XSPF":
						$playlist = new xspfFile($d->get_data(), $url, $station, $image);
						break;

					case "m3u":
					case "m3u8":
					case "M3U":
					case "M3U8":
						$playlist = new m3uFile($d->get_data(), $url, $station, $image);
						break;

					case "stream":
					case "mp3":
						$playlist = new possibleStreamUrl($url, $station, $image);
						break;

					default;
						logger::info("RADIO_PLAYLIST", "Unknown Playlist Type - treating as stream URL");
						$playlist = new possibleStreamUrl($url, $station, $image);
						break;
				}
				break;

			case '404':
				logger::warn("RADIO_PLAYLIST", "404 Error trying to download URL");
				break;

			default:
				logger::info("RADIO_PLAYLIST", "Unexpected cURL status ".$d->get_status()." - treating as stream URL");
				$playlist = new possibleStreamUrl($url, $station, $image);
		}

		if ($playlist) {
			return $playlist;
		} else {
			logger::warn("RADIO_PLAYLIST", "Could not determine playlist type");
			header("HTTP/1.1 404 Not Found");
			return false;
		}
	}
}

function asfOrasx($s) {
	$type = null;
	if (preg_match('/^\[Reference\]/', $s)) {
		logger::log("RADIO_PLAYLIST", "Type of playlist determined as asf");
		$type = "asf";
	} else if (preg_match('/^<ASX /', $s)) {
		logger::log("RADIO_PLAYLIST", "Type of playlist determined as asx");
		$type = "asx";
	} else if (preg_match('/^http/', $s)) {
		logger::log("RADIO_PLAYLIST", "Type of playlist determined as m3u-like");
		$type = "m3u";
	}
	return $type;
}

?>
