<?php
include ("includes/vars.php");
include ("includes/functions.php");

$url = rawurldecode($_REQUEST['url']);

if (!$url) {
	logger::error("GETREMOTEIMAGE", "Asked to download image but no URL given!");
	header("HTTP/1.1 404 Not Found");
} else {
	$outfile = 'prefs/imagecache/'.md5($url);
	if (!is_file($outfile)) {
		logger::debug("GETREMOTEIMAGE", "Downloading Remote Image ".$url);
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
		logger::core("GETREMOTEIMAGE", "  ... Decoding Base64 Data");
		imageFunctions::create_image_from_base64($url, $outfile);
	} else {
		logger::core("GETREMOTEIMAGE", "  ... Downloading it");
		$d = new url_downloader(array('url' => $url));
		if ($d->get_data_to_file($outfile, true)) {
			logger::core("GETREMOTEIMAGE", "Cached Image ".$outfile);
			$content_type = $d->get_content_type();
			logger::core("GETREMOTEIMAGE", "  ... Content Type is ".$content_type);
			if (substr($content_type,0,5) != 'image' && $content_type != 'application/octet-stream') {
				logger::warn("GETREMOTEIMAGE", $url,"is not an image file! ");
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
				logger::debug("GETREMOTEIMAGE","Sending backup image for stream");
				header('Content-type: image/svg+xml');
				readfile('newimages/broadcast.svg');
				break;
		}
	} else {
		header("HTTP/1.1 404 Not Found");
	}
}

?>
