<?php

function musicCollectionUpdate() {
	global $prefs, $collection;
    debuglog("Starting Music Collection Update", "MOPIDY",4);
    $collection = new musicCollection();
	$monitor = fopen('prefs/monitor','w');
    $dirs = $prefs['mopidy_collection_folders'];
    while (count($dirs) > 0) {
        $dir = array_shift($dirs);
        if ($dir == "Spotify Playlists") {
        	musicCollectionSpotifyPlaylistHack($monitor);
        } else {
        	fwrite($monitor, "\nScanning Directory ".$dir);
        	doMpdParse('lsinfo "'.format_for_mpd(local_media_check($dir)).'"', $dirs, null);
	    	$collection->tracks_to_database();
	    }
    }
    fwrite($monitor, "\nUpdating Database");
    fclose($monitor);
}

function musicCollectionSpotifyPlaylistHack($monitor) {
	global $collection;
	$dirs = array();
	$playlists = do_mpd_command("listplaylists", true, true);
    if (is_array($playlists) && array_key_exists('playlist', $playlists)) {
        foreach ($playlists['playlist'] as $pl) {
	    	debuglog("Scanning Playlist ".$pl,"COLLECTION",8);
	        fwrite($monitor, "\nScanning Playlist ".$pl);
	    	doMpdParse('listplaylistinfo "'.format_for_mpd($pl).'"',$dirs, array("spotify"));
		    $collection->tracks_to_database();
	    }
	}
}

function local_media_check($dir) {
	if ($dir == "Local media") {
		// Mopidy-Local-SQlite contains a virtual tree sorting things by various keys
		// If we scan the whole thing we scan every file about 8 times. This is stoopid.
		// Check to see if 'Local media/Albums' is browseable and use that instead if it is.
		// Using Local media/Folders causes every file to be re-scanned every time we update
		// the collection, which takes ages and also includes m3u and pls stuff that we don't want
		$r = do_mpd_command('lsinfo "'.$dir.'/Albums"', false, false);
		if ($r === false) {
			return $dir;
		} else {
			return $dir.'/Albums';
		}
	}
	return $dir;
}

?>
