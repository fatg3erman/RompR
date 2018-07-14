<?php
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");
include ("utils/imagefunctions.php");

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
				$output = analyze_bg_images($f, true);
			}
		} else {
			$f = glob('prefs/userbackgrounds/'.$_REQUEST['getbackground'].'/*.*');
			if (count($f) > 0) {
				debuglog("Custom Background exists for ".$_REQUEST['getbackground'],"BACKIMAGE");
				$output = analyze_bg_images($f, false);
			}
		}
	}
	
} else if (array_key_exists('clearbackground', $_REQUEST)) {

	unlink($_REQUEST['clearbackground']);
	if (is_numeric(basename(dirname($_REQUEST['clearbackground'])))) {
		check_empty_directory(dirname('clearbackground'));
	}
	
} else if (array_key_exists('clearallbackgrounds', $_REQUEST)) {
	
	if (is_dir('prefs/userbackgrounds/'.$_REQUEST['clearallbackgrounds'].'/'.$_REQUEST['browser_id'])) {
		debuglog("Removing All Backgrounds For ".$_REQUEST['clearallbackgrounds'].'/'.$_REQUEST['browser_id'],"BACKIMAGE");
		delete_files('prefs/userbackgrounds/'.$_REQUEST['clearallbackgrounds'].'/'.$_REQUEST['browser_id']);
		check_empty_directory('prefs/userbackgrounds/'.$_REQUEST['clearallbackgrounds'].'/'.$_REQUEST['browser_id']);
	} else if (is_dir('prefs/userbackgrounds/'.$_REQUEST['clearallbackgrounds'])) {
		debuglog("Removing All Backgrounds For ".$_REQUEST['clearallbackgrounds'],"BACKIMAGE");
		delete_files('prefs/userbackgrounds/'.$_REQUEST['clearallbackgrounds']);
	}
		
} else {

	$base = $_REQUEST['currbackground'];
	if (array_key_exists('thisbrowseronly', $_REQUEST)) {
		$base .= '/'.$_REQUEST['browser_id'];
	}
	
	$files = make_files_useful($_FILES['imagefile']);
	foreach ($files as $filedata) {
		$file = $filedata['name'];
		debuglog("Uploading File ".$file,"BACKIMAGE");
		$fname = format_for_url(format_for_disc(basename($file)));
		$download_file = get_user_file($file, $fname, $filedata['tmp_name']);
		if (!is_dir('prefs/userbackgrounds/'.$base)) {
			mkdir('prefs/userbackgrounds/'.$base, 0755, true);
		}
		rename($download_file, 'prefs/userbackgrounds/'.$base.'/'.$fname);
	}
}

print json_encode($output);

ob_flush();

function check_empty_directory($dir) {
	if (is_dir($dir) && !(new \FilesystemIterator($dir))->valid()) {
		rmdir($dir);
	}
}

function analyze_bg_images($im, $tbo) {
	
	$retval = array('images' => array('portrait' => array(), 'landscape' => array()), 'thisbrowseronly' => $tbo);
	foreach ($im as $image) {
		$ih = new imageHandler($image);
		$size = $ih->get_image_dimensions();
		if ($size['width'] > $size['height']) {
			debuglog("  Landscape Image ".$image,"BACKIMAGE",8);
			$retval['images']['landscape'][] = $image;
		} else {
			debuglog("  Portrait Image ".$image,"BACKIMAGE",8);
			$retval['images']['portrait'][] = $image;
		}
		$ih->destroy();
	}
	return $retval;
	
}

function delete_files($path, $expr = '*.*') {
	// Prevents file not found or could not stat errors
	$f = glob($path.'/'.$expr);
	foreach ($f as $file) {
		unlink($file);
	}
}

function make_files_useful($arr) {
    foreach ($arr as $key => $all) {
        foreach ($all as $i => $val) {
            $new[$i][$key] = $val;
        }
    }
    return $new;
}


?>
