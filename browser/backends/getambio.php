<?php
chdir('../..');
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");
$r = json_decode(file_get_contents('php://input'), true);

if (is_array($r) && array_key_exists("url", $r)) {
	$retval = scrape_allmusic($r['url']);
	// get_allmusic_page($r['url']);
	if ($retval === null) {
		http_response_code(404);
	} else {
		print $retval;
		print '<p>Biography courtesy of AllMusic</p>';
	}
} else {
	http_response_code(400);
}
ob_flush();

function scrape_allmusic($url) {
	// Pull the initial page then use the referer and cookies from that
	// response to pull the full bio via a mocked-up ajax request
	logger::log("AMBIO", "Getting allmusic Page",$url);
	$r = null;
	$d = new url_downloader(array(
		'url' => $url,
		'cache' => false
	));
	if ($d->get_data_to_string()) {
		$new_url = $url.'/biographyAjax';
		$headers = ['Referer: '.$url];
		foreach ($d->get_cookies() as $c) {
			$headers[] = 'Cookie: '.$c;
		}
		$nd = new url_downloader(array(
			'url' => $new_url,
			'header' => $headers
		));
		if ($nd->get_data_to_string()) {
			$r = $nd->get_data();
			$r = preg_replace('/data-src/', 'src', $r);
			$r = preg_replace('/<a href.+?>(.+?)<\/a>/s', '$1', $r);
		} else {
			logger::log('AMBIO', 'biographyAjax failed', $nd->get_status());
		}
	} else {
		logger::log('AMBIO', 'Initial download failed');
	}
	return $r;
}

// This is the old one, that permits us to cache the responses but only
// gives us a one-liner.
function get_allmusic_page($url) {
	logger::log("AMBIO", "Getting allmusic Page",$url);
	$r = null;
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
				$r = preg_replace('/\n/', '</p><p>',$r);
			}
		}
	}
	return '<p>'.$r.'</p>';
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
