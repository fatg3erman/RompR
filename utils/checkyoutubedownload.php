<?php
chdir('..');
include('includes/functions.php');
$status = array( 'info' => 'Starting.....');
if (file_exists('prefs/youtubedl/dlprogress')) {
	$stuff = file('prefs/youtubedl/dlprogress');
	$ttindex = trim(array_shift($stuff));
	$lastline = trim(array_pop($stuff));
	if (preg_match('/\[ffmpeg\]\s*Destination:\s*(.+)$/', $lastline, $matches)) {
		$lastline .= '<br />Written '.format_bytes(filesize('prefs/youtubedl/'.$ttindex.'/'.trim($matches[1]))).'bytes';
	}
	$status['info'] = $lastline;
}
print json_encode($status);
?>
