<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");
include ("includes/spotifyauth.php");

$uri = rawurldecode($_REQUEST['uri']);
debuglog("Downloading ".$uri,"SPOTIFY");
$filename = 'prefs/jsoncache/spotify/'.md5($uri);
if (file_exists($filename)) {
    debuglog("Returning cached data","SPOTIFY");
    header("Pragma: From Cache");
    print file_get_contents($filename);
} else {
	list($success, $content, $status) = get_spotify_data($uri);
    if ($success) {
        file_put_contents($filename, $content);
        header("Pragma: Not Cached");
        print $content;
    } else {
        $r = array('error' => 'Status Code '.$status);
		print json_encode($r);
    }
}
?>
