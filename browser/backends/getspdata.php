<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");
include ("includes/spotifyauth.php");

$uri = rawurldecode($_REQUEST['uri']);
debuglog("Downloading ".$uri,"SPOTIFY");
if (file_exists('prefs/jsoncache/spotify/'.md5($uri))) {
    debuglog("Returning cached data","SPOTIFY");
    header("Pragma: From Cache");
    print file_get_contents('prefs/jsoncache/spotify/'.md5($uri));
} else {
	$content = get_spotify_data($uri);
	if ($content['status'] == "200") {
        file_put_contents('prefs/jsoncache/spotify/'.md5($uri), $content['contents']);
        header("Pragma: Not Cached");
        print $content['contents'];
	} else {
		debuglog("Getting Spotify Data FAILED! ".$content['status']);
		$r = array('error' => 'Status Code '.$content['status']);
		print json_encode($r);
	}

}
?>
