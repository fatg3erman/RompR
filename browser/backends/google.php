<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");

$uri = rawurldecode($_REQUEST['uri']);
debuglog("Getting ".$uri, "GOOGLE");

if (file_exists('prefs/jsoncache/google/'.md5($uri))) {
	debuglog("Returning cached data","GOOGLE");
	header("Pragma: From Cache");
	print file_get_contents('prefs/jsoncache/google/'.md5($uri));
} else {
	$content = url_get_contents($uri);
	$s = $content['status'];
	debuglog("Response Status was ".$s, "GOOGLE");
	if ($s == "200") {
		header("Pragma: Not Cached");
		print $content['contents'];
		file_put_contents('prefs/jsoncache/google/'.md5($uri), $content['contents']);
	} else {
		header("HTTP/1.1 500 Internal Server Error");
		debuglog("Error From Google - status".$s ,"GOOGLE");
		print $content['contents'];
	}
}

?>
