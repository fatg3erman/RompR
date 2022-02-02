<?php

if (array_key_exists('populate', $_REQUEST)) {

	chdir('..');

	include ("includes/vars.php");
	include ("includes/functions.php");

	$getstr = "http://dir.xiph.org/";
	if (array_key_exists('path', $_REQUEST)) {
		$getstr = $getstr . $_REQUEST['path'];
		if (array_key_exists('page', $_REQUEST)) {
			$getstr = $getstr . "&page=" . $_REQUEST['page'];
		}
	} else if (array_key_exists('searchfor', $_REQUEST) && $_REQUEST['searchfor'] != '') {
		logger::log("ICESCRAPER", "Searching For ".$_REQUEST['searchfor']);
		$getstr = $getstr . "search?q=" . $_REQUEST['searchfor'];
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
	uibits::directoryControlHeader('icecastlist', language::gettext('label_icecast'));
	print '<div class="containerbox vertical-centre fullwidth"><div class="expand"><input class="enter clearbox" name="searchfor" type="text"';
	if (array_key_exists("searchfor", $_REQUEST)) {
		print ' value="'.$_REQUEST['searchfor'].'"';
	}
	print ' /></div>';
	print '<button class="fixed searchbutton iconbutton" name="cornwallis"></button></div>';

	print uibits::ui_config_header([
		'label_text' => $page_title
	]);

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
			print uibits::albumHeader(array(
				'openable' => false,
				'Image' => 'newimages/icecast.svg',
				'Artistname' => implode(', ', $stream_tags).' - '.$server_description,
				'Albumname' => htmlspecialchars($server_name).' '.'<i class="'.audioClass($format).' inline-icon fixed"></i>',
				'streamuri' => $listenlink,
				'streamname' => $server_name,
				'streamimg' => '',
				'class' => 'radiochannel'
			));
		}
	}

	$pager = $doc->find('ul.pagination')->children('li.page-item')->not('.disabled');
	print '<div class="containerbox wrap configtitle textcentre">';
	foreach ($pager as $page) {
		$link = pq($page)->children('a.page-link')->attr('href');
		print '<div class="clickable icescraper clickicon clickicepager expand" name="search'.$link.'">'.pq($page)->children('a')->text().'</div>';
	}
	print '</div>';

} else {
	// print '<div id="icecastplugin">';
	print uibits::albumHeader(array(
		'playable' => false,
		'id' => 'icecastlist',
		'Image' => 'newimages/icecast.svg',
		'Albumname' => language::gettext('label_icecast'),
		'class' => 'radio icecastroot'
	));
	print '<div id="icecastlist" class="dropmenu notfilled is-albumlist">';
	print uibits::ui_config_header([
		'label' => 'label_loading'
	]);
	print '</div>';
}

function munge_ice_text($text) {
	$monkeyjesus = preg_replace('/(?<!\/)\/(?!\/)/', '/ ', $text);
	return htmlspecialchars($monkeyjesus);
}

?>
