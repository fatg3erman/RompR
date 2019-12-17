<?php

class somafmplugin {

	public function __construct() {

	}

	public function doHeader() {
		print '<div id="somafmplugin">';
		print albumHeader(array(
			'id' => 'somafmlist',
			'Image' => 'newimages/somafmlogo.svg',
			'Searched' => 1,
			'AlbumUri' => null,
			'Year' => null,
			'Artistname' => '',
			'Albumname' => get_int_text('label_somafm'),
			'why' => null,
			'ImgKey' => 'none',
			'class' => 'radio',
			'expand' => true
		));
		print '<div id="somafmlist" class="dropmenu notfilled">';
		print '<div class="configtitle"><div class="textcentre expand"><b>'.get_int_text('label_loading').'</b></div></div></div>';
		print '</div>';
	}

	public function doStationList() {
		directoryControlHeader('somafmlist', get_int_text('label_somafm'));
		print '<div class="containerbox padright indent ninesix bumpad brick_wide">';
		print '<a href="http://somafm.com" target="_blank">'.get_int_text("label_soma_beg").'</a>';
		print '</div>';
		// NB Don't use the cache, it fucks up 'Last Played'
		$stations = getCacheData("http://api.somafm.com/channels.xml", 'somafm', false, true);
		$this->doAllStations($stations);
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
		print '<div class="clickable clickstream playable draggable indent containerbox padright dropdown-container" name="'.rawurlencode((string) $p).'" streamimg="'.$img.'" streamname="'.$c->title.'">';
		print '<i class="icon-no-response-playbutton collectionicon fixed"></i>';
		print '<i class="'.audioClass($p[0]['format']).' collectionicon fixed"></i>';
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
		logger::log("SOMAFM", "Channel :", (string) $channel->title);
		if ($channel->highestpls) {
			$pls = (string) $channel->highestpls;
		} else {
			$pls = (string) $channel->fastpls[0];
		}

		print albumHeader(array(
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
		trackControlHeader('','','somafm_'.$count, null, array(array('Image' => $this->getimage($channel))));
		if ($channel->description) {
			print '<div class="containerbox ninesix indent padright">'.utf8_encode($channel->description).'</div>';
		}
		if ($channel->listeners) {
			print '<div class="containerbox indent padright">';
			print '<div class="expand">'.$channel->listeners.' '.trim(get_int_text("lastfm_listeners"),':').'</div>';
			print '</div>';
		}
		print '<div class="containerbox rowspacer"></div>';
		if ($channel->lastPlaying) {
			print '<div class="containerbox indent padright dropdown-container">';
			print '<b>'.get_int_text('label_last_played').'</b>&nbsp;';
			print $channel->lastPlaying;
			print '</div>';
		}
		if ($channel->twitter && $channel->dj) {
			print '<a href="http://twitter.com/@'.$channel->twitter.'" target="_blank">';
			print '<div class="containerbox indent padright dropdown-container">';
			print '<i class="icon-twitter-logo collectionicon fixed"></i>';
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
	include ("international.php");
	include ("skins/".$skin."/ui_elements.php");

	$soma = new somafmplugin();
	$soma->doStationList();

} else {

	$soma = new somafmplugin();
	$soma->doHeader();

}


?>
