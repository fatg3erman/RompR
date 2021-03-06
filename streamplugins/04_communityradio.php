<?php

if (array_key_exists('populate', $_REQUEST)) {
	chdir('..');

	require_once ("includes/vars.php");
	require_once ("includes/functions.php");

	foreach ($_REQUEST as $i => $r) {
		logger::debug("COMMRADIO", $i,":",$r);
	}
}

class commradioplugin {

	public function __construct() {
		$this->pagination = 50;
		$this->searchterms = array('name', 'country', 'state', 'language', 'tag');
		$this->url = array_key_exists('url', $_REQUEST) ? $_REQUEST['url'] : null;
		$this->page = array_key_exists('page', $_REQUEST) ? $_REQUEST['page'] : 0;
		$this->title = array_key_exists('title', $_REQUEST) ? $_REQUEST['title'] : null;
		$this->order = array_key_exists('order', $_REQUEST) ? $_REQUEST['order'] : 'name';
		$this->populate = array_key_exists('populate', $_REQUEST) ? $_REQUEST['populate'] : 0;
	}

	public function doWhatYoureTold() {

		$this->server = $this->get_server();

		switch ($this->populate) {
			case 0:
				$this->doHeader();
				break;

			case 1:
				$this->doDropdownHeader();
				break;

			case 2:
				if (substr($this->url, 0, 5) == 'json/') {
					$this->browse();
				} else if ($this->url == 'getgenres') {
					$this->doGenreList();
				} else {
					$this->doRequest();
				}
				break;

			case 3:
				$this->doSearch();
				break;

			case 4:
				$this->doBrowseRoot();
				break;

		}
	}

	private function doHeader() {
		print '<div id="communityradioplugin">';
		print uibits::albumHeader(array(
			'id' => 'communityradiolist',
			'Image' => 'newimages/broadcast.svg',
			'Searched' => 1,
			'AlbumUri' => null,
			'Year' => null,
			'Artistname' => '',
			'Albumname' => language::gettext('label_communityradio'),
			'why' => null,
			'ImgKey' => 'none',
			'class' => 'radio commradioroot',
			'expand' => true
		));
		print '<div id="communityradiolist" class="dropmenu notfilled">';
		print '<div class="configtitle"><div class="textcentre expand"><b>'.language::gettext('label_loading').'</b></div></div></div>';
		print '</div>';
	}

	private function doDropdownHeader() {
		uibits::directoryControlHeader('communityradiolist', language::gettext('label_communityradio'));

		print '<div class="fullwidth containerbox dropdown-container">';
		// print '<div class="fixed comm-search-label"><span class="cslt"><b>Order By</b></span></div>';
		print '<div class="selectholder expand">';
		print '<select id="communityradioorderbyselector" class="saveomatic">';
		foreach (array('name', 'country', 'language', 'state', 'tags', 'votes', 'bitrate') as $o) {
			print '<option value="'.$o.'"';
			if (prefs::$prefs['communityradioorderby'] == $o) {
				print ' selected';
			}
			print '>Order By '.ucfirst($o).'</option>';
		}
		print '</select>';
		print '</div>';
		print '</div>';

		print '<div class="fullwidth cleargroupparent">';
		foreach ($this->searchterms as $term) {
			print '<div class="containerbox dropdown-container fullwidth" name="'.$term.'">';
			// print '<div class="fixed comm-search-label"><span class="cslt"><b>'.ucfirst($term).'</b></span></div>';
			print '<div class="expand">';
			print '<input class="comm_radio_searchterm clearbox enter cleargroup" name="'.$term.'" type="text" placeholder="'.ucfirst($term).'"/>';
			print '</div>';
			print '</div>';
		}
		print '<div class="containerbox fullwidth">';
		print '<div class="expand"></div>';
		print '<button class="fixed searchbutton iconbutton cleargroup clickable commradiosearch" name="commradiosearch"></button>';
		print '</div>';
		print '</div>';

		print '<div id="communitystations" class="fullwidth padright holderthing is-albumlist">';

		$this->doBrowseRoot();

		print '</div>';

	}

	private function doBrowseRoot() {
		uibits::printRadioDirectory(array('URL' => 'json/countries', 'text' => 'Country'), false, 'commradio');
		print '</div>';

		uibits::printRadioDirectory(array('URL' => 'json/languages', 'text' => 'Language'), false, 'commradio');
		print '</div>';

		uibits::printRadioDirectory(array('URL' => 'getgenres', 'text' => 'Genres'), false, 'commradio');
		print '</div>';
	}

	private function doGenreList() {
		$genres = array(
			'breakbeat',
			'chart',
			'dance',
			'electronic',
			'jungle',
			'oldschool',
			'techno',
			'trip-hop',
			'50s',
			'60s',
			'70s',
			'80s',
			'90s',
			'00s',
			'contemporary',
			'hits',
			'rock',
			'pop',
			'afrobeat',
			'folk',
			'reggae',
			'dub',
			'acoustic',
			'alt',
			'ambient',
			'bluegrass',
			'blues',
			'brazil',
			'british',
			'chill',
			'classical',
			'comedy',
			'talk',
			'country',
			'dancehall',
			'disco',
			'dnb',
			'dubstep',
			'emo',
			'funk',
			'garage',
			'gospel',
			'goth',
			'grindcore',
			'groove',
			'grunge',
			'hardcore',
			'house',
			'idm',
			'indian',
			'indie',
			'industrial',
			'jazz',
			'metal',
			'rap',
			'progressive',
			'psych',
			'punk',
			'soul',
			'trance',
			'world',
			'public',
			'community',
			'party',
			'local',
			'news',
			'oldies',
			'motown',
			'easy',
			'soundtracks',
			'drama'
		);
		sort($genres);
		uibits::directoryControlHeader('commradio_'.md5('getgenres'), 'Genres');
		foreach ($genres as $g) {
			uibits::printRadioDirectory(array('URL' => 'json/tags/'.$g, 'text' => $g), false, 'commradio');
			print '</div>';

		}
	}

	private function browse() {
		uibits::directoryControlHeader('commradio_'.md5($this->url), $this->title);
		$cache = new cache_handler([
			'url' => 'https://'.$this->server.'/'.$this->url,
			'cache' => 'commradio',
			'return_value' => true
		]);
		$bits = $cache->get_cache_data();
		$bits = json_decode($bits, true);
		if ($this->url == 'json/countries') {
			$map = 'bycountryexact/';
		} else if ($this->url == 'json/languages') {
			$map = 'bylanguageexact/';
		} else {
			$map = 'bytagexact/';
		}
		$this->makeSelector($bits, $map);
	}

	private function doSearch() {
		$url = 'https://'.$this->server.'/json/stations/search?';
		$ourterms = array();
		foreach ($this->searchterms as $t) {
			if (array_key_exists($t, $_REQUEST) && $_REQUEST[$t] != '') {
				$ourterms[] = $t.'='.rawurlencode($_REQUEST[$t]);
			}
		}
		$url .= implode('&', $ourterms).'&';
		$url = $this->addBits($url);
		$cache = new cache_handler([
			'url' => $url,
			'cache' => 'commradio',
			'return_value' => true
		]);
		$stations = $cache->get_cache_data();
		$stations = json_decode($stations, true);
		foreach ($stations as $index => $station) {
			$this->doStation($this->comm_radio_sanitise_station($station), md5($index.$url.$station['stationuuid']));
		}
	}

	private function addBits($url) {
		$url .= 'order='.$this->order;
		switch ($this->order) {
			case 'bitrate':
			case 'votes':
				$url .= '&reverse=true';
				break;
		}
		$url .= '&hidebroken=true';
		return $url;
	}

	private function doRequest() {
		$url = $this->addBits('https://'.$this->server.'/json/stations/'.$this->url.'?');
		logger::log('COMMRADIO','Getting',$url);
		$cache = new cache_handler([
			'url' => $url,
			'cache' => 'commradio',
			'return_value' => true
		]);
		$stations = $cache->get_cache_data();
		$stations = json_decode($stations, true);
		$title = ($this->title) ? rawurldecode($this->title) : language::gettext('label_communityradio');
		uibits::directoryControlHeader('commradio_'.md5($this->url), ucfirst($title));
		print '<input type="hidden" value="'.rawurlencode($this->url).'" />';
		print '<input type="hidden" value="'.rawurlencode($title).'" />';
		$this->comm_radio_do_page_buttons($this->page, count($stations), $this->pagination);
		for ($i = 0; $i < $this->pagination; $i++) {
			$index = $this->page * $this->pagination + $i;
			if ($index >= count($stations)) {
				break;
			}
			$this->doStation($this->comm_radio_sanitise_station($stations[$index]), md5($index.$url.$stations[$index]['stationuuid']));
		}
		$this->comm_radio_do_page_buttons($this->page, count($stations), $this->pagination);
	}

	private function doStation($station, $index) {
		print uibits::albumHeader(array(
			'id' => 'communityradio_'.$index,
			'Image' => $this->comm_radio_get_image($station),
			'Searched' => 1,
			'AlbumUri' => null,
			'Year' => null,
			'Artistname' => preg_replace('/,/', ', ', htmlspecialchars($station['tags'])),
			'Albumname' => htmlspecialchars($station['name']),
			'why' => 'whynot',
			'ImgKey' => 'none',
			'streamuri' => $station['playurl'],
			'streamname' => $station['name'],
			'streamimg' => $this->comm_radio_get_stream_image($station),
			'class' => 'radiochannel'
		));
		print '<div id="communityradio_'.$index.'" class="dropmenu">';
		uibits::trackControlHeader('','','communityradio_'.$index, null, array(array('Image' => $this->comm_radio_get_image($station))));
		// print '<div class="containerbox expand ninesix indent padright"><b>Listen:</b></div>';
		print '<div class="containerbox ninesix indent padright">'.htmlspecialchars($station['state'].$station['country']).'</div>';

		print '<div class="containerbox ninesix indent padright">'.$station['votes'].' Upvotes, '.$station['negativevotes'].' Downvotes</div>';
		if ($station['homepage']) {
			print '<a href="'.$station['homepage'].'" target="_blank">';
			print '<div class="containerbox padright dropdown-container">';
			print '<i class="icon-www collectionicon fixed"></i>';
			print '<div class="expand">'.language::gettext('label_station_website').'</div>';
			print '</div>';
			print '</a>';
		}
		print '<div class="containerbox rowspacer"></div>';
		print '<div class="clickstream playable draggable containerbox padright dropdown-container" name="'.rawurlencode($station['playurl']).'" streamimg="'.$this->comm_radio_get_stream_image($station).'" streamname="'.$station['name'].'">';
		print '<i class="icon-no-response-playbutton collectionicon"></i>';
		print '<i class="'.audioClass($station['codec']).' collectionicon fixed"></i>';
		print '<div class="expand">'.$station['bitrate'].'kbps &nbsp'.$station['codec'].'</div>';
		print '</div>';
		print '</div>';

	}

	private function makeSelector($json, $root) {
		if (is_array($json)) {
			foreach ($json as $thing) {
				$val = strtolower($thing['name']);
				$opts = array(
					'URL' => $root.rawurlencode($val),
					'text' => ucfirst($thing['name']).' ('.$thing['stationcount'].' stations)'
				);
				uibits::printRadioDirectory($opts, true, 'commradio');
			}
		} else {
			print '<b>There was an error</b>';
		}
	}

	private function comm_radio_get_image($station) {
		if ($station['favicon']) {
			if (substr($station['favicon'], 0, 10) == 'data:image') {
				return $station['favicon'];
			} else {
				logger::debug('COMMRADIO', 'Image Is',$station['favicon']);
				if (preg_match('#http://www.bbc.co.uk///(rmp.files.bbci.co.uk/.*)#', $station['favicon'], $matches)) {
					// This appears to be a database fuckup on their part
					$station['favicon'] = 'http://'.$matches[1];
				}
				return 'getRemoteImage.php?url='.rawurlencode($station['favicon']).'&rompr_backup_type=stream';
			}
		} else {
			return 'newimages/broadcast.svg';
		}
	}

	private function comm_radio_get_stream_image($station) {
		if ($station['favicon']) {
			if (substr($station['favicon'], 0, 10) == 'data:image') {
				// Sadly we can't handle base64 data as a stream image in this way. The URLs are too long
				return '';
			} else {
				return 'getRemoteImage.php?url='.rawurlencode($station['favicon']);
			}
		} else {
			return '';
		}
	}

	private function comm_radio_do_page_buttons($page, $count, $per_page) {
		if ($count < $this->pagination) {
			return;
		}
		print '<div class="fullwidth brick_wide"><div class="containerbox padright noselection menuitem">';
		$class = ($page == 0) ? ' button-disabled' : ' clickable clickicon commradio clickcommradiopager';
		print '<i name="'.($page-1).'" class="fixed icon-left-circled medicon'.$class.'"></i>';
		print '<div class="expand textcentre">Showing '.($page*$per_page+1).' to '.min(array(($page*$per_page+$per_page), $count)).' of '.$count.'</div>';
		$class = ((($page+1) * $per_page) >= $count || $count < $per_page) ? ' button-disabled' : ' clickable commradio clickicon clickcommradiopager';
		print '<i name="'.($page+1).'" class="fixed icon-right-circled medicon'.$class.'"></i>';
		print '</div>';

		$firstpage = max(0, $page-4);
		$lastpage = min($firstpage+9, round(($count/$per_page), 0, PHP_ROUND_HALF_DOWN));
		print '<div class="textcentre brick_wide containerbox wrap menuitem">';
		for ($p = $firstpage; $p < $lastpage; $p++) {
			print '<div class="clickable commradio clickicon clickcommradiopager expand';
			if ($p == $page) {
				print ' highlighted';
			}
			print '" name="'.$p.'">'.($p+1).'</div>';
		}
		print '</div>';
		print '</div>';
	}

	private function comm_radio_sanitise_station($station) {
		$blank_station = array(
			'tags' => '',
			'name' => ROMPR_UNKNOWN_STREAM,
			'state' => '',
			'country' => '',
			'votes' => 0,
			'negativevotes' => 0,
			'codec' => 'Unknown Codec',
			'bitrate' => 'Unknown ',
			'favicon' => null,
			'homepage' => null
		);

		$result = array_merge($blank_station, $station);
		if ($result['state'] && $result['country']) {
			$result['state'] .= ', ';
		}
		if ($result['bitrate'] == 0) {
			$result['bitrate'] = 'Unknown ';
		}
		$result['playurl'] = $station['url'];
		return $result;
	}

	private function get_server() {
		// $servers = @dns_get_record('all.api.radio-browser.info');
		// if (is_array($servers)) {
		// 	shuffle($servers);
		// 	foreach ($servers as $server) {
		// 		if (array_key_exists('ip', $server)) {
		// 			$name = gethostbyaddr($server['ip']);
		// 			logger::log('COMMRADIO', 'Using server',$name);
		// 			return $name;
		// 		}
		// 	}
		// }
		logger::warn('COMMRADIO', 'Using fallback server!');
		return 'de1.api.radio-browser.info';
	}

}


$commradio = new commradioplugin();
$commradio->doWhatYoureTold();

?>
