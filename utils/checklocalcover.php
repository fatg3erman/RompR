<?php
chdir('..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("getid3/getid3.php");

$image = '';
$retval = ['ImgKey' => false];
logger::log('PLONKINGTON', 'Checking file',$_REQUEST['file']);

$filepath = imageFunctions::munge_filepath($_REQUEST['unmopfile']);
logger::log('PLONKINGTON', 'Checking file',$filepath);
$image = imageFunctions::check_embedded($filepath, $filepath);

if ($image == '') {
	$player = new base_mpd_player();
	if ($player->check_mpd_version('0.22')) {
		$image = $player->readpicture($_REQUEST['file']);
	}
	$player->close_mpd_connection();
}

if ($image) {
	copy($image, 'prefs/playground/trackimage');
	unlink($image);
	$retval = [
		'ImgKey' => $_REQUEST['ImgKey'],
		'images' => [
			'asdownloaded' => 'prefs/playground/trackimage?version='.microtime(true)
		]
	];
}
print json_encode($retval);
?>