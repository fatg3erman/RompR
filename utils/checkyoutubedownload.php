<?php
chdir('..');
include('includes/vars.php');
include('includes/functions.php');
$retval = "Preparing...";
$reader = new minotaur('prefs/youtubedl/dlprogress_'.$_REQUEST['key']);
$r = $reader->get_last_line_of_file();
if ($r !== false) {
	if (preg_match('/\[ffmpeg\]\s*Destination:\s*(.+)$/', $r, $matches)) {
		$retval = 'Writing Audio : Written '.format_bytes(filesize(trim($matches[1]))).'bytes';
	} else {
		$retval = $r;
	}
}
print json_encode(array('info' => $retval));
?>
