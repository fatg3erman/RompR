<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");
include ("includes/spotifyauth.php");

$uri = $_POST['url'];
$cache = $_POST['cache'];
debuglog("Downloading ".$uri,"SPOTIFY");
debuglog("  Cache is ".$cache,"SPOTIFY");
$filename = 'prefs/jsoncache/spotify/'.md5($uri);
if ($cache == 'true' && file_exists($filename)) {
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
        header('HTTP/1.1 '.$status.' '.http_status_code_string($status));
        $r = array('error' => $status, 'message' => $content);
		print json_encode($r);
    }
}
?>
