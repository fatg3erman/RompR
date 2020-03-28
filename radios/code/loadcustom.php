<?php

chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
require_once ("international.php");
$default = format_for_disc(get_int_text('label_createcustom')).'.json';
$stations = array();
$files = glob('prefs/customradio/*.json');
foreach ($files as $file) {
	if (basename($file) != $default) {
		$stations[] = json_decode(file_get_contents($file));
	}
}
print json_encode($stations);
?>