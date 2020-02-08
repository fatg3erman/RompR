<?php
chdir('../..');
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");

if(array_key_exists("url", $_POST)) {
	$link = get_bio_link($_POST['url']);
	if ($link !== false) {
		print $link;
	} else {
		header('HTTP/1.1 400 Bad Request');
	}
} else {
	header('HTTP/1.1 400 Bad Request');
}
ob_flush();

function get_bio_link($url) {
	$html = '';
	$d = new url_downloader(array(
		'url' => $url,
		'cache' => 'allmusic',
		'return_data' => true
	));
	if ($d->get_data_to_file()) {
		$DOM = new DOMDocument;
		@$DOM->loadHTML($d->get_data());
		$els = getElementsByClass($DOM, 'div', 'artist-contain');
		if (count($els) > 0) {
			$e = $els[0];
			$links = $e->GetElementsByTagName('img');
			for ($i = 0; $i < $links->length; $i++) {
				$link = $links->item($i)->getAttribute('src');
				logger::log("AMIMAGE", "Found Image",$link);
			}
			return $link;
		} else {
			return false;
		}
	} else {
		return false;
	}
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
