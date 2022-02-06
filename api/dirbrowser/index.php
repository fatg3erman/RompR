<?php

// These are the functions for building the dropdowns in the file browser
chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
$path = (array_key_exists('path', $_REQUEST)) ? $_REQUEST['path'] : "";
$prefix = (array_key_exists('prefix', $_REQUEST)) ? $_REQUEST['prefix'].'_' : "dirholder";

$player = new fileCollector(['tags' => null, 'rating' => null]);
if ($player->is_connected()) {
	if ($path == "") {

	} else {
		uibits::directoryControlHeader($prefix, $path);
	}
	$player->doFileBrowse($path, $prefix);
} else {
	header("HTTP/1.1 500 Internal Server Error");
}

?>
