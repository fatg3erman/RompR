<?php
chdir('..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("getid3/getid3.php");

$image = '';
$retval = ['ImgKey' => false];
logger::log('PLONKINGTON', 'Checking file',$_REQUEST['file']);

if ($_REQUEST['type'] == 'podcast') {
	prefs::$database = new poDatabase();
	$image = prefs::$database->check_podcast_trackimage($_REQUEST['file']);
	if ($image) {
		logger::log('PLONKINGTON', 'Found Podcast Track Image', $image);
		$image = 'getRemoteImage.php?url='.rawurlencode($image);
	}
}

if ($image == '') {
	$filepath = imageFunctions::munge_filepath($_REQUEST['unmopfile']);
	logger::log('PLONKINGTON', 'Checking file',$filepath);
	$image = imageFunctions::check_embedded($filepath, $filepath);
	if ($image == '') {
		$player = new base_mpd_player();
		$player->close_mpd_connection();
		$player = new player();
		$image = $player->albumart($_REQUEST['file'], true);
		$player->close_mpd_connection();
		if ($image)
			logger::log('PLONKINGTON', 'Got Image from MPD readpicture');
	} else {
		logger::log('PLONKINGTON', 'Found Embedded Image');
	}
	if ($image) {
		copy($image, 'prefs/playground/trackimage');
		unlink($image);
		$image = 'prefs/playground/trackimage?version='.microtime(true);
	}
}

if ($image) {
	$retval = [
		'ImgKey' => $_REQUEST['ImgKey'],
		'images' => [
			'asdownloaded' => $image
		]
	];
}
print json_encode($retval);
?>