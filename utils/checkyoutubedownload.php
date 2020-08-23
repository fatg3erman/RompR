<?php
chdir('..');
$status = array( 'info' => 'Starting.....');
if (file_exists('prefs/youtubedl/dlprogress')) {
	exec('tail -n 1 prefs/youtubedl/dlprogress', $output, $cheese);
	$status['info'] = $output;
}
print json_encode($status);
?>
