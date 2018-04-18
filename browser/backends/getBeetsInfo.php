<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
$uri = rawurldecode($_REQUEST['uri']);
debuglog("Getting ".$uri, "GETBEETSINFO");

$content = url_get_contents($uri);
$s = $content['status'];
debuglog("Response Status was ".$s, "GETBEETSINFO");
if ($s == "200") {
	print $content['contents'];
} else {
   header("HTTP/1.1 404 Not Found");
}

?>
