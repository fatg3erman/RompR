<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");

$uri = rawurldecode($_REQUEST['uri']);
debuglog("Getting ".$uri, "GOOGLE");
// $d = new url_downloader(array(
// 	'url' => $uri,
// 	'cache' => 'google',
// 	'return_data' => true,
// 	'send_cache_headers' => true
// ));
// if ($d->get_data_to_file()) {
// 	print $d->get_data();
// } else {
// 	header("HTTP/1.1 500 Internal Server Error");
// 	print $d->get_data();
// }
getCacheData($uri, 'google', true);
?>
