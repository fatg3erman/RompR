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
} else {
	debuglog("Getting Remote Image ".$url,"TOMATO",7);
	$outfile = 'prefs/imagecache/'.md5($url);
	if (!file_exists($outfile)) {
		if (download_image_file($url, $outfile)) {
			output_file($outfile);
		} else {
			send_backup_image();
		}
	} else {
		output_file($outfile);
	}
}

function output_file($outfile) {
	$imagehandler = new imageHandler($outfile);
	$size = array_key_exists('rompr_resize_size', $_REQUEST) ? $_REQUEST['rompr_resize_size'] : 'asdownloaded';
	$imagehandler->outputResizedFile($size);
}

function download_image_file($url, $outfile) {

	debuglog("  ... Downloading it", "TOMATO",8);
	$d = new url_downloader(array('url' => $url));
	if ($d->get_data_to_file($outfile, true)) {
		debuglog("Cached Image ".$outfile,"TOMATO",9);
		$content_type = $d->get_content_type();
		debuglog("  ... Content Type is ".$content_type,"TOMATO", 8);
		if (substr($content_type,0,5) != 'image' && $content_type != 'application/octet-stream') {
			debuglog("      Not an image file! ".$url,"TOMATO",8);
			unlink($outfile);
			return false;
		}
	} else {
		return false;
	}
	return true;
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
