<?php
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");
include ("utils/backgroundimages.php");
include ("backends/sql/backend.php");

foreach($_REQUEST as $i => $r) {
	logger::log("BACKIMAGE", $i,'=',$r);
}

$retval = array();

if (array_key_exists('getbackground', $_REQUEST)) {

	$images = sql_prepare_query(false, PDO::FETCH_ASSOC, null, null, 'SELECT * FROM BackgroundImageTable WHERE Skin = ? AND BrowserID = ?', $_REQUEST['getbackground'], $_REQUEST['browser_id']);
	$thisbrowseronly = true;
	if (count($images) == 0) {
		logger::log("BACKIMAGE", "No Custom Backgrounds Exist for",$_REQUEST['getbackground'],$_REQUEST['browser_id']);
		$images = sql_prepare_query(false, PDO::FETCH_ASSOC, null, null, 'SELECT * FROM BackgroundImageTable WHERE Skin = ? AND BrowserID IS NULL', $_REQUEST['getbackground']);
		$thisbrowseronly = false;
	} else {
		logger::log("BACKIMAGE", "Custom Backgrounds Exist for",$_REQUEST['getbackground'],$_REQUEST['browser_id']);
	}
	if (count($images) > 0) {
		logger::log("BACKIMAGE", "Custom Backgrounds Exist for",$_REQUEST['getbackground']);
		$retval = array('images' => array('portrait' => array(), 'landscape' => array()), 'thisbrowseronly' => $thisbrowseronly);
		foreach ($images as $image) {
			if ($image['Orientation'] == ORIENTATION_PORTRAIT) {
				$retval['images']['portrait'][] = $image['Filename'];
			} else {
				$retval['images']['landscape'][] = $image['Filename'];
			}
		}
	}

} else if (array_key_exists('clearbackground', $_REQUEST)) {

	sql_prepare_query(true, null, null, null, 'DELETE FROM BackgroundImageTable WHERE Filename = ?', $_REQUEST['clearbackground']);
	unlink($_REQUEST['clearbackground']);
	if (is_numeric(basename(dirname($_REQUEST['clearbackground'])))) {
		check_empty_directory(dirname('clearbackground'));
	}

} else if (array_key_exists('clearallbackgrounds', $_REQUEST)) {

	// Remove these here, just in case the folder has been deleted for some reason
	sql_prepare_query(true, null, null, null, 'DELETE FROM BackgroundImageTable WHERE Skin = ? AND BrowserID = ?', $_REQUEST['clearallbackgrounds'], $_REQUEST['browser_id']);
	if (is_dir('prefs/userbackgrounds/'.$_REQUEST['clearallbackgrounds'].'/'.$_REQUEST['browser_id'])) {
		logger::log("BACKIMAGE", "Removing All Backgrounds For ".$_REQUEST['clearallbackgrounds'].'/'.$_REQUEST['browser_id']);
		delete_files('prefs/userbackgrounds/'.$_REQUEST['clearallbackgrounds'].'/'.$_REQUEST['browser_id']);
		check_empty_directory('prefs/userbackgrounds/'.$_REQUEST['clearallbackgrounds'].'/'.$_REQUEST['browser_id']);
	} else if (is_dir('prefs/userbackgrounds/'.$_REQUEST['clearallbackgrounds'])) {
		logger::log("BACKIMAGE", "Removing All Backgrounds For ".$_REQUEST['clearallbackgrounds']);
		delete_files('prefs/userbackgrounds/'.$_REQUEST['clearallbackgrounds']);
		sql_prepare_query(true, null, null, null, 'DELETE FROM BackgroundImageTable WHERE Skin = ? AND BrowserID IS NULL', $_REQUEST['clearallbackgrounds']);
	}

} else {

	if (!array_key_exists('currbackground', $_REQUEST) || !array_key_exists('imagefile', $_FILES)) {
		if (isset($_SERVER["CONTENT_LENGTH"])) {
			if ($_SERVER["CONTENT_LENGTH"] > ((int) ini_get('post_max_size')*1024*1024)) {
				logger::warn("BACKIMAGE", "Content Length Error");
				header("HTTP/1.1 400 Bad Request", 'BACKIMAGE');
				ob_flush();
				exit(0);
			}
		}
		logger::warn("BACKIMAGE", "Some kind of upload error");
		header("HTTP/1.1 500 Internal Server Error");
		ob_flush();
		exit(0);
	}
	$skin = $_REQUEST['currbackground'];
	$base = $skin;
	$browserid = null;
	if (array_key_exists('thisbrowseronly', $_REQUEST)) {
		$base .= '/'.$_REQUEST['browser_id'];
		$browserid = $_REQUEST['browser_id'];
	}

	$files = make_files_useful($_FILES['imagefile']);
	foreach ($files as $filedata) {
		$file = $filedata['name'];
		logger::log("BACKIMAGE", "Uploading File ".$file);
		$fname = format_for_url(format_for_disc(basename($file)));
		$download_file = get_user_file($file, $fname, $filedata['tmp_name']);
		if (!is_dir('prefs/userbackgrounds/'.$base)) {
			mkdir('prefs/userbackgrounds/'.$base, 0755, true);
		}
		$file = 'prefs/userbackgrounds/'.$base.'/'.$fname;
		if (file_exists($file)) {
			logger::trace("BACKIMAGE", "Image",$file,"already exists");
			unlink($download_file);
		} else {
			rename($download_file, $file);
			$orientation = analyze_background_image($file);
			sql_prepare_query(true, null, null, null, 'INSERT INTO BackgroundImageTable (Skin, BrowserID, Filename, Orientation) VALUES (?, ?, ?, ?)', $skin, $browserid, $file, $orientation);
		}
	}
}

print json_encode($retval);

ob_flush();

function check_empty_directory($dir) {
	if (is_dir($dir) && !(new FilesystemIterator($dir))->valid()) {
		rmdir($dir);
	}
}

function delete_files($path, $expr = '*.*') {
	// Prevents file not found or could not stat errors
	$f = glob($path.'/'.$expr);
	foreach ($f as $file) {
		unlink($file);
	}
}

function make_files_useful($arr) {
	$new = array();
	foreach ($arr as $key => $all) {
		foreach ($all as $i => $val) {
			$new[$i][$key] = $val;
		}
	}
	return $new;
}


?>
