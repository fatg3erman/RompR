<?php
chdir('..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("getid3/getid3.php");

$retval = ['ImgKey' => false];
logger::log('PLONKINGTON', 'Checking file',$_REQUEST['file']);
$filepath = imageFunctions::munge_filepath($_REQUEST['file']);
logger::log('PLONKINGTON', 'Checking file',$filepath);
$image = imageFunctions::check_embedded($filepath, $filepath);
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