<?php

class somafmplugin {

	public function __construct() {

	}

	public function doHeader() {
		// print '<div id="somafmplugin">';
		print uibits::albumHeader(array(
			'id' => 'somafmlist',
			'Image' => 'newimages/somafmlogo.svg',
			'Searched' => 1,
			'AlbumUri' => null,
			'Year' => null,
			'Artistname' => '',
			'Albumname' => language::gettext('label_somafm'),
			'why' => null,
			'ImgKey' => 'none',
			'class' => 'radio somafmroot',
			'expand' => true
		));
		print '<div id="somafmlist" class="dropmenu notfilled is-albumlist">';
		print '<div class="configtitle"><div class="textcentre expand"><b>'.language::gettext('label_loading').'</b></div></div></div>';
		// print '</div>';
	}

	public function doStationList() {
		uibits::directoryControlHeader('somafmlist', language::gettext('label_somafm'));
		print '<div class="containerbox indent ninesix bumpad brick_wide">';
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

	private function format_listenlink($c, $p, $label) {
		$img = $this->getimage($c);
		print '<div class="clickstream playable draggable indent containerbox vertical-centre" name="'.rawurlencode((string) $p).'" streamimg="'.$img.'" streamname="'.$c->title.'">';
		print '<i class="icon-no-response-playbutton inline-icon fixed"></i>';
		print '<i class="'.audioClass($p[0]['format']).' inline-icon fixed"></i>';
		print '<div class="expand">'.$label.'&nbsp';
		switch ($p[0]['format']) {
			case 'mp3':
				print 'MP3';
				break;
			case 'aac':
				print 'AAC';
				break;
			case 'aacp':
				print 'AAC Plus';
				break;
			default:
				print 'Unknown Format';
				break;

		}
		print '</div>';
		print '</div>';
	}

	private function doAllStations($content) {
		logger::trace("SOMAFM", "Loaded Soma FM channels list");
		try {
			$x = simplexml_load_string($content);
			$count = 0;
			foreach ($x->channel as $channel) {
				$this->doChannel($count, $channel);
				$count++;
			}
		} catch (Exception $e) {
			print 'There was an error getting the channels from Soma FM';
		}
	}

	private function doChannel($count, $channel) {
		logger::trace("SOMAFM", "Channel :", (string) $channel->title);
		if ($channel->highestpls) {
			$pls = (string) $channel->highestpls;
		} else {
			$pls = (string) $channel->fastpls[0];
		}

		print uibits::albumHeader(array(
			'id' => 'somafm_'.$count,
			'Image' => $this->getimage($channel),
			'Searched' => 1,
			'AlbumUri' => null,
			'Year' => null,
			'Artistname' => utf8_encode($channel->genre),
			'Albumname' => utf8_encode($channel->title),
			'why' => 'whynot',
			'ImgKey' => 'none',
			'streamuri' => $pls,
			'streamname' => (string) $channel->title,
			'streamimg' => $this->getimage($channel),
			'class' => 'radiochannel'
		));

		print '<div id="somafm_'.$count.'" class="dropmenu">';
		uibits::trackControlHeader('','','somafm_'.$count, null, array(array('Image' => $this->getimage($channel))));
		if ($channel->description) {
			print '<div class="containerbox ninesix indent">'.utf8_encode($channel->description).'</div>';
		}
		if ($channel->listeners) {
			print '<div class="containerbox indent">';
			print '<div class="expand">'.$channel->listeners.' '.trim(language::gettext("lastfm_listeners"),':').'</div>';
			print '</div>';
		}
		print '<div class="containerbox rowspacer"></div>';
		if ($channel->lastPlaying) {
			print '<div class="containerbox indent vertical-centre">';
			print '<b>'.language::gettext('label_last_played').'</b>&nbsp;';
			print $channel->lastPlaying;
			print '</div>';
		}
		if ($channel->twitter && $channel->dj) {
			print '<a href="http://twitter.com/@'.$channel->twitter.'" target="_blank">';
			print '<div class="containerbox indent vertical-centre">';
			print '<i class="icon-twitter-logo inline-icon fixed"></i>';
			print '<div class="expand"><b>DJ: </b>'.$channel->dj.'</div>';
			print '</div></a>';
		}

		print '<div class="containerbox rowspacer"></div>';

		if ($channel->highestpls) {
			$this->format_listenlink($channel, $channel->highestpls, "High Quality");
		}
		foreach ($channel->fastpls as $h) {
			$this->format_listenlink($channel, $h, "Standard Quality");
		}
		foreach ($channel->slowpls as $h) {
			$this->format_listenlink($channel, $h, "Low Quality");
		}
		print '<div class="containerbox rowspacer"></div>';

		print '</div>';
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
