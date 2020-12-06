<?php

chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
$default = format_for_disc(language::gettext('label_createcustom')).'.json';
$stations = array();
$files = glob('prefs/customradio/*.json');
foreach ($files as $file) {
	if (basename($file) != $default) {
		$stations[] = json_decode(file_get_contents($file));
	}
}
print json_encode($stations);
?>