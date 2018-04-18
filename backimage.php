<?php
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");

$output = array();

if (array_key_exists('getbackground', $_REQUEST)) {

	if (is_dir('prefs/userbackgrounds/'.$_REQUEST['getbackground'])) {
		$f = glob('prefs/userbackgrounds/'.$_REQUEST['getbackground'].'/*.*');
		if (count($f) > 0) {
			$output = array('image' => $f[0]);
		}
	}
} else if (array_key_exists('clearbackground', $_REQUEST)) {

	if (is_dir('prefs/userbackgrounds/'.$_REQUEST['clearbackground'])) {
		system('rm -rf prefs/userbackgrounds/'.$_REQUEST['clearbackground']);
	}

} else {

	$file = $_FILES['imagefile']['name'];
	$base = $_REQUEST['currbackground'];
	$download_file = "";
	$fname = basename($file);
	$download_file = get_user_file($file, $fname, $_FILES['imagefile']['tmp_name']);

	if (is_dir('prefs/userbackgrounds/'.$base)) {
		system('rm prefs/userbackgrounds/'.$base.'/*.*');
	} else {
		system('mkdir prefs/userbackgrounds/'.$base);
	}

	system('mv "'.$download_file.'" "prefs/userbackgrounds/'.$base.'/'.$fname.'"');

	$output = array('image' => 'prefs/userbackgrounds/'.$base.'/'.$fname);

}

print json_encode($output);

ob_flush();

?>