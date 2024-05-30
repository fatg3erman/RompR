<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
$uri = rawurldecode($_REQUEST['uri']);
$uri = 'http://'.prefs::get_pref('beets_server_location').'/item/'.$uri;
logger::mark("GETBEETSINFO", "Getting",$uri);
$d = new url_downloader(array('url' => $uri));
if ($d->get_data_to_string()) {
	print $d->get_data();
} else {
	http_response_code(404);
}
?>
