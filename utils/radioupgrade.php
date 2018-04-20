<?php

debuglog("Upgrading User Stream XSPFs","UPGRADE");
$playlists = glob("prefs/userstreams/USERSTREAM*.xspf");
foreach($playlists as $file) {
	debuglog("  Loading ".$file,"UPGRADE");
    $x = simplexml_load_file($file);
    $playlisturl = null;
    $album = ROMPR_UNKNOWN_STREAM;
    $image = 'newimages/broadcast.svg';
    $playlisturl = (string) $x->playlisturl;
    if ($playlisturl == '') {
    	debuglog("    File has no playlist URL! Cannot upgrade this file. Sorry.","UPGRADE");
    	continue;
    }
	debuglog("    Playlist URL is ".$playlisturl,"UPGRADE");
    $tracks = array();
    foreach($x->trackList->track as $track) {
    	if ((string) $track->album != "" && $album == ROMPR_UNKNOWN_STREAM) {
    		$album = (string) $track->album;
    		debuglog("    Station Name is ".$album,"UPGRADE");
    	}
    	if ((string) $track->image != "" && $image == 'newimages/broadcast.svg') {
    		$image = (string) $track->image;
    		debuglog("    Station Image is ".$image,"UPGRADE");
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
		debuglog("    Created Station With Index ".$rindex,"UPGRADE");
		foreach ($tracks as $track) {
			if (sql_prepare_query(true, null, null, null,
				"INSERT INTO RadioTracktable
				(Stationindex, TrackUri, PrettyStream)
				VALUES
				(?, ?, ?)",
				$rindex, $track['uri'], $track['stream']))
			{
				debuglog("    Created Track","UPGRADE");
			} else {
				debuglog("    Failed To Create Track","UPGRADE");
			}
		}
	} else {
		debuglog("    FAILED to create station!","UPGRADE");
	}
	unlink($file);
}

if (file_exists('prefs/userstreams/radioorder.txt')) {
	debuglog("Setting Fave Station Order","UPGRADE");
    $fcontents = file('prefs/userstreams/radioorder.txt');
	$count = 1;
	foreach ($fcontents as $f) {
		$s = trim($f);
		debuglog("  ".$count." - ".$s,"UPGRADE");
		sql_prepare_query(true, null, null, null, "UPDATE RadioStationtable SET Number = ? WHERE StationName = ?",$count, $s);
		$count++;
	}
	unlink('prefs/userstreams/radioorder.txt');
}

debuglog("Upgrading Temp Stream XSPFs","UPGRADE");
$playlists = glob("prefs/tempstreams/STREAM*.xspf");
foreach($playlists as $file) {
	debuglog("  Loading ".$file,"UPGRADE");
    $x = simplexml_load_file($file);
    $playlisturl = null;
    $album = ROMPR_UNKNOWN_STREAM;
    $image = 'newimages/broadcast.svg';
    $playlisturl = (string) $x->playlisturl;
    if ($playlisturl == '') {
    	debuglog("    File has no playlist URL! Cannot upgrade this file. Sorry.","UPGRADE");
    	continue;
    }
    $index = sql_prepare_query(false, null, 'Stationindex', null, "SELECT Stationindex FROM RadioStationtable WHERE PlaylistUrl = ?", $playlisturl);
	if ($index) {
		debuglog("    Station already exists","UPGRADE");
		continue;
    }
	debuglog("    Playlist URL is ".$playlisturl,"UPGRADE");
    $tracks = array();
    foreach($x->trackList->track as $track) {
    	if ((string) $track->album != "" && $album == ROMPR_UNKNOWN_STREAM) {
    		$album = (string) $track->album;
    		debuglog("    Station Name is ".$album,"UPGRADE");
    	}
    	if ((string) $track->image != "" && $image == 'newimages/broadcast.svg') {
    		$image = (string) $track->image;
    		debuglog("    Station Image is ".$image,"UPGRADE");
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
		debuglog("    Created Station With Index ".$rindex,"UPGRADE");
		foreach ($tracks as $track) {
			if (sql_prepare_query(true, null, null, null,
				"INSERT INTO RadioTracktable
				(Stationindex, TrackUri, PrettyStream)
				VALUES
				(?, ?, ?)",
				$rindex, $track['uri'], $track['stream']))
			{
				debuglog("    Created Track","UPGRADE");
			} else {
				debuglog("    Failed To Create Track","UPGRADE");
			}
		}
	} else {
		debuglog("    FAILED to create station!","UPGRADE");
	}
}

?>