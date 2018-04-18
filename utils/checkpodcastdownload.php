<?php
chdir('..');
$status = array( 'filename' => '', 'percent' => 0 );
if (file_exists('prefs/monitor.xml')) {
	$x = simplexml_load_file('prefs/monitor.xml');
	$fname = $x->filename;
	$fsize = $x->filesize;
	if (is_file($fname)) {
		$csize = filesize($fname);
		$status['percent'] = ($csize/$fsize)*100;
		$status['filename'] = $fname;
	}
}

print json_encode($status);

?>
