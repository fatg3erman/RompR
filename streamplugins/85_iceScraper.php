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
		debuglog("Searching For ".$_REQUEST['searchfor'],"ICESCRAPER");
		$getstr = $getstr . "search?search=" . $_REQUEST['searchfor'];
	}
	debuglog("Getting ".$getstr,"ICESCRAPER");
	$d = new url_downloader(array('url' => $getstr));
	$d->get_data_to_string();
	$icecast_shitty_page = preg_replace('/<\?xml.*?\?>/', '', $d->get_data());
	$doc = phpQuery::newDocument($icecast_shitty_page);
	$list = $doc->find('table.servers-list')->find('tr');
	$page_title = $doc->find('#content')->children('h2')->text();
	debuglog("Page Title Is ".$page_title,"ICESCRAPER");
	$count = 0;
	directoryControlHeader('icecastlist', get_int_text('label_icecast'));
	print '<div class="containerbox brick_wide"><div class="expand"><input class="enter clearbox" name="searchfor" type="text"';
	if (array_key_exists("searchfor", $_REQUEST)) {
		print ' value="'.$_REQUEST['searchfor'].'"';
	}
	print ' /></div>';
	print '<button class="fixed searchbutton iconbutton" name="cornwallis"></button></div>';

	print '<div class="configtitle textcentre brick_wide"><b>'.$page_title.'</b></div>';
	foreach ($list as $server) {
		$server_web_link = '';
		$server_name = pq($server)->find('.stream-name')->children('.name')->children('a');
		$server_web_link = $server_name->attr('href');
		$server_name = $server_name->text();
		debuglog("Server Name Is ".$server_name,"ICESCRAPER");
		$server_description = munge_ice_text(pq($server)->find('.stream-description')->text());
		$stream_tags = array();
		$stream_tags_section = pq($server)->find('.stream-tags')->find('li');
		foreach ($stream_tags_section as $tag) {
			$stream_tags[] = pq($tag)->children('a')->text();
		}
		$listeners = pq($server)->find('.listeners')->text();
		$listenlinks = pq($server)->find('.tune-in');
		$listenlink = '';
		$format = '';
		$ps = $listenlinks->find('p');
		foreach ($ps as $p) {
			if (pq($p)->hasClass('format')) {
				$format = pq($p)->attr('title');
			} else {
				foreach(pq($p)->children('a') as $a) {
					$l = pq($a)->attr('href');
					if (substr($l, -5) == ".xspf") {
				    	$listenlink = 'http://dir.xiph.org'.$l;
				    }
				}
			}
		}

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
			trackControlHeader('','','icecast_'.$count, array(array('Image' => 'newimages/icecast.svg')));
			print '<div class="containerbox rowspacer"></div>';
			print '<div class="indent">'.$server_description.'</div>';
			print '<div class="containerbox rowspacer"></div>';
			print '<div class="indent">'.$listeners.'</div>';
			print '<div class="containerbox rowspacer"></div>';
			print '<div class="stream-description clickable icescraper clickstream playable draggable indent" name="'.$listenlink.'" streamname="'.$server_name.'" streamimg="">';
			print '<b>Listen</b> '.$format;
			print '</div>';
			print '<div class="containerbox rowspacer"></div>';
			print '<a href="'.$server_web_link.'" target="_blank">';
			print '<div class="containerbox indent padright menuitem">';
			print '<i class="icon-www collectionicon fixed"></i>';
			print '<div class="expand">'.get_int_text('label_station_website').'</div>';
			print '</div>';
			print '</a>';
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
		'class' => 'radio',
		'expand' => true
    ));
	print '<div id="icecastlist" class="dropmenu notfilled"><div class="configtitle textcentre"><b>'.get_int_text('label_loading').'</b></div></div>';
	print '</div>';
}

function munge_ice_text($text) {
	$monkeyjesus = preg_replace('/(?<!\/)\/(?!\/)/', '/ ', $text);
	return htmlspecialchars($monkeyjesus);
}

?>
