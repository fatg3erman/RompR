<?php

logger::log("UPGRADE", "Upgrading User Stream XSPFs");
$playlists = glob("prefs/userstreams/USERSTREAM*.xspf");
foreach($playlists as $file) {
	logger::log("UPGRADE", "  Loading ".$file);
	$x = simplexml_load_file($file);
	$playlisturl = null;
	$album = ROMPR_UNKNOWN_STREAM;
	$image = 'newimages/broadcast.svg';
	$playlisturl = (string) $x->playlisturl;
	if ($playlisturl == '') {
		logger::log("UPGRADE", "    File has no playlist URL! Cannot upgrade this file. Sorry.");
		continue;
	}
	logger::log("UPGRADE", "    Playlist URL is ".$playlisturl);
	$tracks = array();
	foreach($x->trackList->track as $track) {
		if ((string) $track->album != "" && $album == ROMPR_UNKNOWN_STREAM) {
			$album = (string) $track->album;
			logger::log("UPGRADE", "    Station Name is ".$album);
		}
		if ((string) $track->image != "" && $image == 'newimages/broadcast.svg') {
			$image = (string) $track->image;
			logger::log("UPGRADE", "    Station Image is ".$image);
		}
		$tracks[] = array('uri' => (string) $track->location, 'stream' => (string) $track->stream);
	}
	if (sql_prepare_query(true, null, null, null,
		"INSERT INTO RadioStationtable
			(IsFave, StationName, PlaylistUrl, Image)
			VALUES
			(1, ?, ?, ?)",
		$album, $playlisturl, $image)) {
		$rindex = $mysqlc->lastInsertId();
		logger::log("UPGRADE", "    Created Station With Index ".$rindex);
		foreach ($tracks as $track) {
			if (sql_prepare_query(true, null, null, null,
				"INSERT INTO RadioTracktable
				(Stationindex, TrackUri, PrettyStream)
				VALUES
				(?, ?, ?)",
				$rindex, $track['uri'], $track['stream']))
			{
				logger::log("UPGRADE", "    Created Track");
			} else {
				logger::log("UPGRADE", "    Failed To Create Track");
			}
		}
	} else {
		logger::log("UPGRADE", "    FAILED to create station!");
	}
	unlink($file);
}

if (file_exists('prefs/userstreams/radioorder.txt')) {
	logger::log("UPGRADE", "Setting Fave Station Order");
	$fcontents = file('prefs/userstreams/radioorder.txt');
	$count = 1;
	foreach ($fcontents as $f) {
		$s = trim($f);
		logger::log("UPGRADE", "  ".$count." - ".$s);
		sql_prepare_query(true, null, null, null, "UPDATE RadioStationtable SET Number = ? WHERE StationName = ?",$count, $s);
		$count++;
	}
	unlink('prefs/userstreams/radioorder.txt');
}

logger::log("UPGRADE", "Upgrading Temp Stream XSPFs");
$playlists = glob("prefs/tempstreams/STREAM*.xspf");
foreach($playlists as $file) {
	logger::log("UPGRADE", "  Loading ".$file);
	$x = simplexml_load_file($file);
	$playlisturl = null;
	$album = ROMPR_UNKNOWN_STREAM;
	$image = 'newimages/broadcast.svg';
	$playlisturl = (string) $x->playlisturl;
	if ($playlisturl == '') {
		logger::log("UPGRADE", "    File has no playlist URL! Cannot upgrade this file. Sorry.");
		continue;
	}
	$index = sql_prepare_query(false, null, 'Stationindex', null, "SELECT Stationindex FROM RadioStationtable WHERE PlaylistUrl = ?", $playlisturl);
	if ($index) {
		logger::log("UPGRADE", "    Station already exists");
		continue;
	}
	logger::log("UPGRADE", "    Playlist URL is ".$playlisturl);
	$tracks = array();
	foreach($x->trackList->track as $track) {
		if ((string) $track->album != "" && $album == ROMPR_UNKNOWN_STREAM) {
			$album = (string) $track->album;
			logger::log("UPGRADE", "    Station Name is ".$album);
		}
		if ((string) $track->image != "" && $image == 'newimages/broadcast.svg') {
			$image = (string) $track->image;
			logger::log("UPGRADE", "    Station Image is ".$image);
		}
		$tracks[] = array('uri' => (string) $track->location, 'stream' => (string) $track->stream);
	}
	if (sql_prepare_query(true, null, null, null,
		"INSERT INTO RadioStationtable
			(IsFave, StationName, PlaylistUrl, Image)
			VALUES
			(0, ?, ?, ?)",
		$album, $playlisturl, $image)) {
		$rindex = $mysqlc->lastInsertId();
		logger::log("UPGRADE", "    Created Station With Index ".$rindex);
		foreach ($tracks as $track) {
			if (sql_prepare_query(true, null, null, null,
				"INSERT INTO RadioTracktable
				(Stationindex, TrackUri, PrettyStream)
				VALUES
				(?, ?, ?)",
				$rindex, $track['uri'], $track['stream']))
			{
				logger::log("UPGRADE", "    Created Track");
			} else {
				logger::log("UPGRADE", "    Failed To Create Track");
			}
		}
	} else {
		logger::log("UPGRADE", "    FAILED to create station!");
	}
}

?>