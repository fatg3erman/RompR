<?php
chdir('..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("getid3/getid3.php");
$donkeymolester = imageFunctions::scan_for_local_images(rawurldecode($_REQUEST['path']));

if (!array_key_exists('error', $donkeymolester)) {
	foreach ($donkeymolester as &$poop) {
		$parts = explode('/', $poop);
		$parts = array_map('rawurlencode', $parts);
		$poop = implode('/', $parts);
	}
}

print json_encode($donkeymolester);
?>
