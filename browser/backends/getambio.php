<?php
chdir('../..');
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");

const ALLMUSIC_HEADERS = [
	'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
	'Accept-Encoding: gzip, deflate, br, zstd',
	'Accept-Language: en-GB,en;q=0.5',
	'Cache-Control: no-cache',
	'Pragma: no-cache',
	'Priority: u=0, i',
	'Sec-Ch-Ua: "Chromium";v="148", "Brave";v="148", "Not/A)Brand";v="99"',
	'Sec-Ch-Ua-Mobile: ?0',
	'Sec-Ch-Ua-Platform: "Linux"',
	'Sec-Fetch-Dest: document',
	'Sec-Fetch-Mode: navigate',
	'Sec-Fetch-Site: same-origin',
	'Sec-Fetch-User: ?1',
	'Sec-Gpc: 1',
	'Upgrade-Insecure-Requests: 1',
];

const ALLMUSIC_USERAGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36';

$r = json_decode(file_get_contents('php://input'), true);

if (is_array($r) && array_key_exists("url", $r)) {
	$retval = scrape_allmusic($r['url']);
	if ($retval === null) {
		http_response_code(404);
	} else {
		print $retval;
	}
} else if (is_array($r) && array_key_exists("albumurl", $r)) {
	$retval = scrape_allmusic_album($r['albumurl']);
	if ($retval === null) {
		http_response_code(404);
	} else {
		print $retval;
	}
} else if (is_array($r) && array_key_exists("artistimage", $r)) {
	$link = get_image_link($r['artistimage']);
	if ($link !== false) {
		print $link;
	} else {
		http_response_code(400);
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
	$headers = ALLMUSIC_HEADERS;
	$d = new url_downloader(array(
		'useragent' => ALLMUSIC_USERAGENT,
		'url' => $url,
		'cache' => 'allmusic',
		'header' => $headers
	));
	if ($d->get_data_to_file()) {
		$new_url = $url.'/biographyAjax';
		$headers[] = 'Referer: '.$url;
		$nd = new url_downloader(array(
			'useragent' => ALLMUSIC_USERAGENT,
			'url' => $new_url,
			'cache' => 'allmusic',
			'header' => $headers
		));
		if ($nd->get_data_to_file()) {
			$r = $nd->get_data();
			$r = str_replace('data-src', 'src', $r);
			$r = preg_replace('/<a href.+?>(.+?)<\/a>/s', '$1', $r);
			$r = str_replace('h2', 'h3', $r);
		} else {
			logger::log('AMBIO', 'biographyAjax failed', $nd->get_status());
		}
	} else {
		logger::log('AMBIO', 'Initial download failed');
	}
	return $r;
}

function scrape_allmusic_album($url) {
	// Pull the initial page then use the referer and cookies from that
	// response to pull the full bio via a mocked-up ajax request
	logger::log("AMBIO", "Getting allmusic Page",$url);
	$r = null;
	$headers = ALLMUSIC_HEADERS;
	$d = new url_downloader(array(
		'useragent' => ALLMUSIC_USERAGENT,
		'url' => $url,
		'cache' => 'allmusic',
		'header' => $headers
	));
	if ($d->get_data_to_file()) {
		$new_url = $url.'/reviewAjax';
		$headers[] = 'Referer: '.$url;
		// foreach ($d->get_cookies() as $c) {
		// 	logger::log('AMBIO', 'Adding Cookie', $c);
		// 	$headers[] = 'Cookie: '.$c;
		// }
		$nd = new url_downloader(array(
			'useragent' => ALLMUSIC_USERAGENT,
			'url' => $new_url,
			'cache' => 'allmusic',
			'header' => $headers
		));
		if ($nd->get_data_to_file()) {
			$r = $nd->get_data();
			$r = preg_replace('/data-src/', 'src', $r);
			$r = preg_replace('/<a href.+?>(.+?)<\/a>/s', '$1', $r);
		} else {
			logger::log('AMBIO', 'reviewAjax failed', $nd->get_status());
		}
	} else {
		logger::log('AMBIO', 'Initial download failed');
	}
	return $r;
}


function get_image_link($url) {
	logger::log('ALLMUSIC', 'Looking for image from', $url);
	$html = '';
	$d = new url_downloader(array(
		'useragent' => ALLMUSIC_USERAGENT,
		'url' => $url,
		'cache' => 'allmusic',
		'header' => ALLMUSIC_HEADERS
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
		}
		$els = getElementsByClass($DOM, 'div', 'artistPoster');
		if (count($els) > 0) {
			logger::log('ALLMUSIC', 'Found artistPoster');
			return get_image($els[0]);
		}
		return false;
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
