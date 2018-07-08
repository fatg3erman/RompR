<?php
include ("includes/vars.php");
include ("includes/functions.php");
include ("utils/imagefunctions.php");

$url = $_REQUEST['url'];

foreach ($_GET as $k => $v) {
	if ($k != 'url' && $k != 'rompr_resize_size' && $k != 'rompr_backup_type') {
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
	if (substr($content_type,0,5) != 'image' && $content_type != 'application/octet-stream') {
		debuglog("Not an image file! ".$url,"TOMATO",5);
		send_backup_image();
	} else {
		if (extension_loaded('gd') && array_key_exists('rompr_resize_size', $_REQUEST)) {
			$simpleimage = new SimpleImage($outfile);
			if ($simpleimage->checkImage() === false) {
				header('Content-type: '.$content_type);
				readfile($outfile);
			} else {
				header('Content-type: image/jpeg');
				$simpleimage->outputResizedFile($_REQUEST['rompr_resize_size']);
			}
		} else {
			header('Content-type: '.$content_type);
			readfile($outfile);
		}
	}
}

function download_image_file($url, $outfile) {

	debuglog("  ... Downloading it", "TOMATO",8);
	$d = new url_downloader(array('url' => $url));
	if ($d->get_data_to_file($outfile, true)) {
		debuglog("Cached Image ".$outfile,"TOMATO",9);
		$content_type = $d->get_content_type();
		debuglog("  ... Content Type is ".$content_type,"TOMATO", 9);
		$fileplusmime = $outfile.'_'.rawurlencode($content_type);
		rename($outfile, $fileplusmime);
		return $fileplusmime;
	} else {
		debuglog("Failed to download ".$url." - status was ".$d->get_status(),"TOMATO",5);
		send_backup_image();
		exit(0);
	}
}

function send_backup_image() {
	if (array_key_exists('rompr_backup_type', $_REQUEST)) {
		switch ($_REQUEST['rompr_backup_type']) {
			case 'stream':
				header('Content-type: image/svg+xml');
				readfile('newimages/broadcast.svg');
				break;
		}
	} else {
		header("HTTP/1.1 404 Not Found");
	}
}

?>
