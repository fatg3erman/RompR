<?php
chdir('../..');
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");
$r = json_decode(file_get_contents('php://input'), true);

if (array_key_exists("url", $r)) {
	$link = get_image_link($r['url']);
	if ($link !== false) {
		print $link;
	} else {
		http_response_code(400);
	}
} else {
	http_response_code(500);
}
ob_flush();

function get_image_link($url) {
	logger::log('ALLMUSIC', 'Looking for image from', $url);
	$html = '';
	$d = new url_downloader(array(
		'url' => $url,
		'cache' => 'allmusic'
	));
	if ($d->get_data_to_file()) {
		logger::log('ALLMUSIC', 'Downloaded OK');
		$DOM = new DOMDocument;
		try {
			@$DOM->loadHTML($d->get_data());
		} catch (ValueError $e) {
			return false;
		}
		logger::log('ALLMUSIC', 'Looking for artistPoster');
		$el = $DOM->getElementById('artistPoster');
		if ($el !== null) {
			logger::log('ALLMUSIC', 'Found artistPoster');
			return get_image($el);
		// $els = getElementsByClass($DOM, 'div', 'artistPoster');
		// if (count($els) > 0) {
		// 	logger::log('ALLMUSIC', 'Found artistPoster');
		// 	return get_image_link($els[0]);
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function get_image($e) {
	$link = false;
	$links = $e->GetElementsByTagName('img');
	for ($i = 0; $i < $links->length; $i++) {
		$link = $links->item($i)->getAttribute('src');
		logger::log("AMIMAGE", "Found Image",$link);
	}
	return $link;
}

function getElementsByClass(&$parentNode, $tagName, $className) {
	$nodes=array();

	$childNodeList = $parentNode->getElementsByTagName($tagName);
	for ($i = 0; $i < $childNodeList->length; $i++) {
		$temp = $childNodeList->item($i);
		if (stripos($temp->getAttribute('class'), $className) !== false) {
			$nodes[]=$temp;
		}
	}

	return $nodes;
}

?>
