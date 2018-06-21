<?php
include ("includes/vars.php");
include ("includes/functions.php");

$url = $_REQUEST['url'];

foreach ($_GET as $k => $v) {
	if ($k != 'url') {
		$url .= '&'.$k.'='.$v;
	}
}

if (!$url) {
	debuglog("Asked to download image but no URL given!","TOMATO",3);
    header("HTTP/1.1 404 Not Found");
    exit(0);
} else {
	debuglog("Getting Remote Image ".$url,"TOMATO",7);
	$outfile = 'prefs/imagecache/'.md5($url);
	$files = glob($outfile.'_*');
	if (count($files) == 1) {
		$outfile = array_shift($files);
	} else {
		$outfile = download_image_file($url, $outfile);
	}
	$bits = explode('_', $outfile);
	$content_type = rawurldecode(array_pop($bits));
	debuglog("  .. Content Type is ".$content_type,"TOMATO",8);
	if (substr($content_type,0,5) != 'image') {
		debuglog("Not an image file! ".$url,"TOMATO",5);
		header("HTTP/1.1 404 Not Found");
	} else {
		header('Content-type: '.$content_type);
		readfile($outfile);
	}
}

function download_image_file($url, $outfile) {

	debuglog("  ... Downloading it", "TOMATO",8);
	$fp = fopen($outfile, 'w');
	$aagh = url_get_contents($url, ROMPR_IDSTRING, false, true, true, $fp);
	fclose($fp);
	if ($aagh['status'] == "200") {
		debuglog("Cached Image ".$outfile,"TOMATO",9);
	} else {
		debuglog("Failed to download ".$url." - status was ".$aagh['status'],"TOMATO",5);
		header("HTTP/1.1 404 Not Found");
		exit(0);
	}
	$content_type = $aagh['info']['content_type'];
	debuglog("  ... Content Type is ".$content_type,"TOMATO", 9);
	$fileplusmime = $outfile.'_'.rawurlencode($content_type);
	system('mv '.$outfile.' '.$fileplusmime);
	return $fileplusmime;

}


?>
