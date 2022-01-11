<?php
chdir('..');
include('includes/vars.php');
include('includes/functions.php');
$retval = ['info' => "Preparing..."];
if (file_exists('prefs/youtubedl/dlprogress_'.$_REQUEST['key'])) {
	$reader = new minotaur('prefs/youtubedl/dlprogress_'.$_REQUEST['key']);
	$r = $reader->get_last_line_of_file();
	if ($r !== false) {
		if (preg_match('/\[ffmpeg\]\s*Destination:\s*(.+)$/', $r, $matches) && file_exists($matches[1])) {
			$retval = 'Writing Audio : Written '.format_bytes(filesize(trim($matches[1]))).'bytes';
		} else {
			$retval = $r;
		}
	}
	$retval = ['info' => $retval];
} else if (file_exists('prefs/youtubedl/dlprogress_'.$_REQUEST['key'].'_result')) {
	$result = json_decode(file_get_contents('prefs/youtubedl/dlprogress_'.$_REQUEST['key'].'_result'), true);
	$retval = ['result' => $result];
	unlink('prefs/youtubedl/dlprogress_'.$_REQUEST['key'].'_result');
}
print json_encode($retval);
?>
