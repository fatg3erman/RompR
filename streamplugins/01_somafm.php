<?php

class somafmplugin {

	public function __construct() {

	}

	public function doHeader() {
		// print '<div id="somafmplugin">';
		print uibits::albumHeader(array(
			'playable' => false,
			'id' => 'somafmlist',
			'Image' => 'newimages/somafmlogo.svg',
			'Albumname' => language::gettext('label_somafm'),
			'class' => 'radio somafmroot',
		));
		print '<div id="somafmlist" class="dropmenu notfilled is-albumlist">';
		print '<div class="configtitle"><div class="textcentre expand"><b>'.language::gettext('label_loading').'</b></div></div></div>';
		// print '</div>';
	}

	public function doStationList() {
		uibits::directoryControlHeader('somafmlist', language::gettext('label_somafm'));
		print '<div class="containerbox ninesix bumpad brick_wide">';
		print '<a href="http://somafm.com" target="_blank">'.language::gettext("label_soma_beg").'</a>';
		print '</div>';
		// NB Don't use the cache, it fucks up 'Last Played'
		$cache = new cache_handler([
			'url' => "http://api.somafm.com/channels.xml",
			'cache' => null,
			'return_value' => true
		]);
		$this->doAllStations($cache->get_cache_data());
	}

	// -- Private Functions -- //

	private function getimage($c) {
		$img = (string) $c->xlimage;
		if (!$img) {
			$img = (string) $c->largeimage;
		}
		if (!$img) {
			$img = (string) $c->image;
		}
		return 'getRemoteImage.php?url='.$img;
	}

	private function format_codec($c) {
		switch ($c) {
			case 'mp3':
				return 'MP3';
				break;
			case 'aac':
				return 'AAC';
				break;
			case 'aacp':
				return 'AAC_Plus';
				break;
			default:
				return 'Unknown_Format';
				break;

		}
	}

	private function doAllStations($content) {
		logger::trace("SOMAFM", "Loaded Soma FM channels list");
		try {
			$x = simplexml_load_string($content);
			$this->build_format_list($x);
			$count = 0;
			foreach ($x->channel as $channel) {
				$this->doChannel($count, $channel);
				$count++;
			}
		} catch (Exception $e) {
			print 'There was an error getting the channels from Soma FM';
		}
	}

	private function listenlink_type($t, $p) {
		return $t.'_'.$this->format_codec((string) $p[0]['format']);
	}

	private function build_format_list($x) {
		$highest_formats = [];
		$fast_formats = [];
		$slow_formats = [];
		foreach ($x->channel as $channel) {
			if ($channel->highestpls) {
				$highest_formats[] = $this->listenlink_type('high_quality', $channel->highestpls);
			}
			foreach ($channel->fastpls as $h) {
				$fast_formats[] = $this->listenlink_type('standard_quality', $h);
			}
			foreach ($channel->slowpls as $h) {
				$slow_formats[] = $this->listenlink_type('low_quality', $h);
			}
		}
		$all_formats = array_merge(
			['highest_available_quality'],
			array_unique($highest_formats),
			array_unique($fast_formats),
			array_unique($slow_formats)
		);
		print '<div class="fullwidth containerbox vertical-centre brick_wide">';
		print '<div class="selectholder expand">';
		print '<select id="somafm_qualityselector" class="saveomatic">';
		foreach($all_formats as $format) {
			print '<option value="'.$format.'">'.ucfirst(str_replace('_', ' ', $format)).'</option>';
		}
		print '</select>';
		print '</div>';
		print '</div>';
	}

	private function doChannel($count, $channel) {
		logger::trace("SOMAFM", "Channel :", (string) $channel->title);

		$extralines = [utf8_encode($channel->description)];

		if ($channel->dj)
			$extralines[] = '<b>DJ: </b>'.$channel->dj;

		if ($channel->listeners)
			$extralines[] = $channel->listeners.' '.trim(language::gettext("lastfm_listeners"), ':');

		$formats = [];
		if ($channel->highestpls) {
			$formats[] = [$channel->highestpls, $this->listenlink_type('high_quality', $channel->highestpls)];
		}
		foreach ($channel->fastpls as $h) {
			$formats[] = [$h, $this->listenlink_type('standard_quality', $h)];
		}
		foreach ($channel->slowpls as $h) {
			$formats[] = [$h, $this->listenlink_type('low_quality', $h)];
		}

		$format_info = '<input type="hidden" name="highest_available_quality" value="'.(string) $formats[0][0].'" />';
		foreach ($formats as $format) {
			$format_info .= '<input type="hidden" name="'.$format[1].'" value="'.(string) $format[0].'" />';
		}

		print uibits::albumHeader(array(
			'openable' => false,
			'Image' => $this->getimage($channel),
			'Artistname' => '<i>'.utf8_encode($channel->genre).'</i>',
			'Albumname' => utf8_encode($channel->title),
			'streamuri' => (string) $formats[0][0],
			'streamname' => (string) $channel->title,
			'streamimg' => $this->getimage($channel),
			'class' => 'radiochannel soma-fm',
			'extralines' => $extralines,
			'podcounts' => $format_info
		));
	}
}

if (array_key_exists('populate', $_REQUEST)) {

	chdir('..');

	include ("includes/vars.php");
	include ("includes/functions.php");

	$soma = new somafmplugin();
	$soma->doStationList();

} else {

	$soma = new somafmplugin();
	$soma->doHeader();

}


?>
