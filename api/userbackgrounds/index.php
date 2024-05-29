<?php
chdir('../..');
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");
prefs::$database = new backgroundImages();

foreach($_REQUEST as $i => $r) {
	logger::trace("BACKIMAGE", $i,'=',$r);
}

$retval = array();
if (array_key_exists('get_all_backgrounds', $_REQUEST)) {
	$retval = prefs::$database->get_background_images($_REQUEST['get_all_backgrounds'], prefs::get_pref('browser_id'));
} else if (array_key_exists('get_next_background', $_REQUEST)) {
	$retval = prefs::$database->get_next_background($_REQUEST['get_next_background'], prefs::get_pref('browser_id'), $_REQUEST['random']);
} else if (array_key_exists('deleteimage', $_REQUEST)) {
	prefs::$database->clear_background($_REQUEST['deleteimage']);
} else if (array_key_exists('clearallbackgrounds', $_REQUEST)) {
	prefs::$database->clear_all_backgrounds($_REQUEST['clearallbackgrounds'], prefs::get_pref('browser_id'));
} else if (array_key_exists('switchbrowseronly', $_REQUEST)) {
	prefs::$database->switch_backgrounds($_REQUEST['switchbrowseronly'], prefs::get_pref('browser_id'), $_REQUEST['thisbrowseronly']);
} else {
	if (!array_key_exists('currbackground', $_REQUEST) || !array_key_exists('imagefile', $_FILES)) {
		if (isset($_SERVER["CONTENT_LENGTH"])) {
			if ($_SERVER["CONTENT_LENGTH"] > ((int) ini_get('post_max_size')*1024*1024)) {
				logger::warn("BACKIMAGE", "Content Length Error");
				http_response_code(400);
				ob_flush();
				exit(0);
			}
		}
		logger::warn("BACKIMAGE", "Some kind of upload error");
		http_response_code(500);
		ob_flush();
		exit(0);
	}
	prefs::$database->upload_backgrounds($_REQUEST['currbackground']);
}

print json_encode($retval);

ob_flush();
?>
