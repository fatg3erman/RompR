<?php
chdir('..');
include('includes/vars.php');
$retval = "Preparing...";
$reader = new minotaur('prefs/backupmonitor');
$r = $reader->get_last_line_of_file();
if ($r !== false) {
	$retval = $r;
}
print json_encode(array('current' => $retval));
?>