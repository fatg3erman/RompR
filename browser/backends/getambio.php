<?php
chdir('../..');
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");

if(array_key_exists("url", $_POST)) {
	$link = get_bio_link($_POST['url']);
	if ($link !== false) {
		get_allmusic_page($link);
	} else {
		print '<p></p>';
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
		$els = getElementsByClass($DOM, 'li', 'biography');
		if (count($els) > 0) {
			$e = $els[0];
			$links = $e->GetElementsByTagName('a');
			for ($i = 0; $i < $links->length; $i++) {
				$link = $links->item($i)->getAttribute('href');
				logger::trace("AMBIO", "Found Bio Link",$link);
			}
			return 'http://www.allmusic.com'.$link;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function get_allmusic_page($url) {
	logger::trace("AMBIO", "Getting allmusic Page",$url);
	$r = '<p></p>';
	$d = new url_downloader(array(
		'url' => $url,
		'cache' => 'allmusic',
		'return_data' => true
	));
	if ($d->get_data_to_file()) {
		$DOM = new DOMDocument;
		@$DOM->loadHTML($d->get_data());
		$els = getElementsByClass($DOM, 'div', 'text');
		foreach ($els as $el) {
			$a = $el->getAttribute('itemprop');
			if ($a == 'reviewBody') {
				logger::log("AMBIO", "Found Review Body");
				$r = $el->nodeValue;
			}
		}
		$r = '<p>'.$r.'</p><p>Biography courtesy of AllMusic</p>';
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
