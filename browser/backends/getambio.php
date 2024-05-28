<?php
chdir('../..');
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");
$r = json_decode(file_get_contents('php://input'), true);


if (array_key_exists("url", $r)) {
	get_allmusic_page($r['url']);
} else {
	header('HTTP/1.1 400 Bad Request');
}
ob_flush();

function get_allmusic_page($url) {
	logger::log("AMBIO", "Getting allmusic Page",$url);
	$r = '<p></p>';
	$d = new url_downloader(array(
		'url' => $url,
		'cache' => 'allmusic'
	));
	if ($d->get_data_to_file()) {
		$DOM = new DOMDocument;
		@$DOM->loadHTML($d->get_data());
		$el = $DOM->getElementById('bioHeadline');
		if ($el !== null) {
			logger::log("AMBIO", "Found Review Body");
			if (mb_check_encoding($el->nodeValue, 'UTF-8')) {
				logger::core('AMBIO', 'String seems to be valid UTF-8');
				$r = $el->nodeValue;
			} else {
				logger::core('AMBIO', 'String IS NOT valid UTF-8');
				$r = mb_convert_encoding($el->nodeValue, 'UTF-8', mb_detect_encoding($el->nodeValue));
			}
			// $r = '<p>'.$r.'</p><p>Biography courtesy of AllMusic</p>';
		}
	}
	print preg_replace('/\n/', '</p><p>',$r);
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
