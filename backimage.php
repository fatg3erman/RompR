<?php
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");

foreach($_REQUEST as $i => $r) {
	debuglog($i.' = '.$r,"BACKIMAGE");
}

$output = array();

if (array_key_exists('getbackground', $_REQUEST)) {

	if (is_dir('prefs/userbackgrounds/'.$_REQUEST['getbackground'])) {
		if (is_dir('prefs/userbackgrounds/'.$_REQUEST['getbackground'].'/'.$_REQUEST['browser_id'])) {
			$f = glob('prefs/userbackgrounds/'.$_REQUEST['getbackground'].'/'.$_REQUEST['browser_id'].'/*.*');
			if (count($f) > 0) {
				debuglog("Custom Background exists for ".$_REQUEST['getbackground'].'/'.$_REQUEST['browser_id'],"BACKIMAGE");
				$output = array('images' => $f, 'thisbrowseronly' => true);
			}
		} else {
			$f = glob('prefs/userbackgrounds/'.$_REQUEST['getbackground'].'/*.*');
			if (count($f) > 0) {
				debuglog("Custom Background exists for ".$_REQUEST['getbackground'],"BACKIMAGE");
				$output = array('images' => $f, 'thisbrowseronly' => false);
			}
		}
	}
} else if (array_key_exists('clearbackground', $_REQUEST)) {

	if (is_dir('prefs/userbackgrounds/'.$_REQUEST['clearbackground'])) {
		if (is_dir('prefs/userbackgrounds/'.$_REQUEST['clearbackground'].'/'.$_REQUEST['browser_id'])) {
			rrmdir('prefs/userbackgrounds/'.$_REQUEST['clearbackground'].'/'.$_REQUEST['browser_id']);
		} else {
			delete_files('prefs/userbackgrounds/'.$_REQUEST['clearbackground']);
		}
	}

} else {

	debuglog("Uploading Custom Background Image : ","BACKIMAGE");
	foreach ($_FILES['imagefile'] as $key => $value) {
		debuglog("  ".$key." = ".$value,"BACKIMAGE");
	}

	$file = $_FILES['imagefile']['name'];
	$base = $_REQUEST['currbackground'];
	if (array_key_exists('thisbrowseronly', $_REQUEST)) {
		$base .= '/'.$_REQUEST['browser_id'];
	}
	$download_file = "";
	$fname = basename($file);
	$download_file = get_user_file($file, $fname, $_FILES['imagefile']['tmp_name']);

	if (is_dir('prefs/userbackgrounds/'.$base) && !preg_match('/_landscape/', $fname) && !preg_match('/_portrait/', $fname)) {
		debuglog("Removing All Current Backgrounds For This Path","BACKIMAGE");
		delete_files('prefs/userbackgrounds/'.$base);
	} else if (is_dir('prefs/userbackgrounds/'.$base) && preg_match('/_landscape/', $fname)) {
		debuglog("Removing Current Landcsape Backgrounds For This Path","BACKIMAGE");
		delete_files('prefs/userbackgrounds/'.$base, '*_landscape.*');
	} else if (is_dir('prefs/userbackgrounds/'.$base) && preg_match('/_portarait/', $fname)) {
		debuglog("Removing Current Portrait Backgrounds For This Path","BACKIMAGE");
		delete_files('prefs/userbackgrounds/'.$base, '*_portrait.*');
	} else {
		mkdir('prefs/userbackgrounds/'.$base, 0755, true);
	}
	
	rename($download_file, 'prefs/userbackgrounds/'.$base.'/'.$fname);

	// This isn't actually used by the UI anyway but....
	$output = array('images' => array('prefs/userbackgrounds/'.$base.'/'.$fname), 'thisbrowseronly' => array_key_exists('thisbrowseronly', $_REQUEST));

}

print json_encode($output);

ob_flush();

function delete_files($path, $expr = '*.*') {
	// Prevents file not found or could not stat errors
	$f = glob($path.'/'.$expr);
	foreach ($f as $file) {
		unlink($file);
	}
}

?>
