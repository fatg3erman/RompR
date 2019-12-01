<?php
include ("includes/vars.php");
include ("includes/functions.php");
include ("utils/imagefunctions.php");

$url = rawurldecode($_REQUEST['url']);

if (!$url) {
	logger::error("GETREMOTEIMAGE", "Asked to download image but no URL given!");
    header("HTTP/1.1 404 Not Found");
} else {
	logger::log("GETREMOTEIMAGE", "Getting Remote Image ".$url);
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
	if ($imagehandler->checkImage()) {
		$imagehandler->outputResizedFile($size);
	} else {
		send_backup_image();
	}
}

function download_image_file($url, $outfile) {

	if (substr($url, 0, 10) == 'data:image') {
		logger::trace("GETREMOTEIMAGE", "  ... Decoding Base64 Data");
		create_image_from_base64($url, $outfile);
	} else {
		logger::trace("GETREMOTEIMAGE", "  ... Downloading it");
		$d = new url_downloader(array('url' => $url));
		if ($d->get_data_to_file($outfile, true)) {
			logger::trace("GETREMOTEIMAGE", "Cached Image ".$outfile);
			$content_type = $d->get_content_type();
			logger::trace("GETREMOTEIMAGE", "  ... Content Type is ".$content_type);
			if (substr($content_type,0,5) != 'image' && $content_type != 'application/octet-stream') {
				logger::warn("GETREMOTEIMAGE", "      Not an image file! ",$url);
				unlink($outfile);
				return false;
			}
		} else {
			return false;
		}
	}
	return true;
}

function send_backup_image() {
	if (array_key_exists('rompr_backup_type', $_REQUEST)) {
		switch ($_REQUEST['rompr_backup_type']) {
			case 'stream':
				logger::log("GETREMOTEIMAGE","Sending backup image for stream");
				header('Content-type: image/svg+xml');
				readfile('newimages/broadcast.svg');
				break;
		}
	} else {
		header("HTTP/1.1 404 Not Found");
	}
}

?>
