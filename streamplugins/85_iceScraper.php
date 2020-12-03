<?php

if (array_key_exists('populate', $_REQUEST)) {

	chdir('..');

	include ("includes/vars.php");
	include ("includes/functions.php");
	include ("international.php");
	include ("skins/".$skin."/ui_elements.php");
	include ("utils/phpQuery.php");

	$getstr = "http://dir.xiph.org/";
	if (array_key_exists('path', $_REQUEST)) {
		$getstr = $getstr . $_REQUEST['path'];
		if (array_key_exists('page', $_REQUEST)) {
			$getstr = $getstr . "&page=" . $_REQUEST['page'];
		}
	} else if (array_key_exists('searchfor', $_REQUEST) && $_REQUEST['searchfor'] != '') {
		logger::log("ICESCRAPER", "Searching For ".$_REQUEST['searchfor']);
		$getstr = $getstr . "search?search=" . $_REQUEST['searchfor'];
	}
	logger::log("ICESCRAPER", "Getting ".$getstr);
	// NB Don't use the cache, station links often don't stay live long enough
		$cache = new cache_handler([
		'url' => $getstr,
		'cache' => null,
		'return_value' => true
	]);
	$page = $cache->get_cache_data();
	$icecast_shitty_page = preg_replace('/<\?xml.*?\?>/', '', $page);
	$doc = phpQuery::newDocument($icecast_shitty_page);
	$page_title = $doc->find('main[role="main"]')->children('h2')->text();
	logger::debug("ICESCRAPER", "Page Title Is ".$page_title);

	$list = $doc->find('div.card.shadow-sm');
	$count = 0;
	directoryControlHeader('icecastlist', get_int_text('label_icecast'));
	print '<div class="containerbox dropdown-container fullwidth"><div class="expand"><input class="enter clearbox" name="searchfor" type="text"';
	if (array_key_exists("searchfor", $_REQUEST)) {
		print ' value="'.$_REQUEST['searchfor'].'"';
	}
	print ' /></div>';
	print '<button class="fixed searchbutton iconbutton" name="cornwallis"></button></div>';

	print '<div class="configtitle brick_wide"><div class="textcentre expand"><b>'.$page_title.'</b></div></div>';
	foreach ($list as $server) {
		$server_web_link = '';
		$server_name = pq($server)->find('.card-title')->text();
		logger::debug("ICESCRAPER", "Server Name Is ".$server_name);
		$server_description = munge_ice_text(pq($server)->find('.card-text')->text());

		$stream_tags = array();
		$stream_tags_section = pq($server)->find('.badge.badge-secondary');
		foreach ($stream_tags_section as $tag) {
			$stream_tags[] = pq($tag)->text();
		}

		$format = pq($server)->find('.badge.badge-primary')->text();
		$listenlink = pq($server)->find('a.btn.btn-sm')->attr('href');

		if ($listenlink != '') {
			print albumHeader(array(
				'id' => 'icecast_'.$count,
				'Image' => 'newimages/icecast.svg',
				'Searched' => 1,
				'AlbumUri' => null,
				'Year' => null,
				'Artistname' => implode(', ', $stream_tags),
				'Albumname' => htmlspecialchars($server_name),
				'why' => 'whynot',
				'ImgKey' => 'none',
				'streamuri' => $listenlink,
				'streamname' => $server_name,
				// 'streamimg' => 'newimages/icecast.svg',
				'streamimg' => '',
				'class' => 'radiochannel'
			));
			print '<div id="icecast_'.$count.'" class="dropmenu">';
			trackControlHeader('','','icecast_'.$count, null, array(array('Image' => 'newimages/icecast.svg')));
			print '<div class="containerbox rowspacer"></div>';
			print '<div class="indent">'.$server_description.'</div>';
			print '<div class="containerbox rowspacer"></div>';
			print '<div class="stream-description icescraper clickstream playable draggable indent" name="'.rawurlencode($listenlink).'" streamname="'.$server_name.'" streamimg="">';
			print '<i class="icon-no-response-playbutton collectionicon"></i>';
			print '<b>Listen</b> '.$format;
			print '</div>';
			print '</div>';
		}
		$count++;
	}

	$pager = $doc->find('ul.pager')->children('li');
	print '<div class="containerbox wrap brick_wide configtitle textcentre">';
	foreach ($pager as $page) {
		$link = pq($page)->children('a')->attr('href');
		print '<div class="clickable icescraper clickicon clickicepager expand" name="/search'.$link.'">'.pq($page)->children('a')->text().'</div>';
	}
	print '</div>';

} else {
	print '<div id="icecastplugin">';
	print albumHeader(array(
		'id' => 'icecastlist',
		'Image' => 'newimages/icecast.svg',
		'Searched' => 1,
		'AlbumUri' => null,
		'Year' => null,
		'Artistname' => '',
		'Albumname' => get_int_text('label_icecast'),
		'why' => null,
		'ImgKey' => 'none',
		'class' => 'radio icecastroot',
		'expand' => true
	));
	print '<div id="icecastlist" class="dropmenu notfilled is-albumlist"><div class="configtitle"><div class="textcentre expand"><b>'.get_int_text('label_loading').'</b></div></div></div>';
	print '</div>';
}

function munge_ice_text($text) {
	$monkeyjesus = preg_replace('/(?<!\/)\/(?!\/)/', '/ ', $text);
	return htmlspecialchars($monkeyjesus);
}

?>
