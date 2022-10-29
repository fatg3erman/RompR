<?php

class tuneinplugin {

	private $domains = ['podcasts', 'radio'];
	// private $browsing = true;

	public function __construct() {
		$this->url = 'http://opml.radiotime.com/';
		$this->title = '';
	}

	public function doHeader() {
		// print '<div id="tuneinradio">';
		print uibits::radioChooser([
			'playable' => false,
			'id' => 'tuneinlist',
			'Image' => 'newimages/tunein-logo.svg',
			'Albumname' => language::gettext('label_tuneinradio'),
			'class' => 'radio tuneinroot',
		]);
		print '<div id="tuneinlist" class="dropmenu notfilled is-albumlist">';
		print uibits::ui_config_header([
			'label' => 'label_loading'
		]);
		print '</div>';
	}

	public function parseParams() {
		if (array_key_exists('url', $_REQUEST)) {
			$this->url = $_REQUEST['url'];
		} else if (!array_key_exists('search', $_REQUEST)) {
			uibits::directoryControlHeader('tuneinlist', language::gettext('label_tuneinradio'));
		}
		if (array_key_exists('title', $_REQUEST)) {
			$this->title = $_REQUEST['title'];
			uibits::directoryControlHeader($_REQUEST['target'], htmlspecialchars($this->title));
		}
		if (array_key_exists('search', $_REQUEST)) {
			$this->domains = explode(',', $_REQUEST['domains']);
			// $this->browsing = false;
			// uibits::directoryControlHeader('tunein_search', language::gettext('label_tuneinradio'));
			$this->url .= 'Search.ashx?query='.urlencode($_REQUEST['search']);
		} else {

		}
	}

	public function getUrl() {
		logger::log("TUNEIN", "Getting URL",$this->url);
		$d = new url_downloader(array('url' => $this->url));
		if ($d->get_data_to_string()) {
			$x = simplexml_load_string($d->get_data());
			$v = (string) $x['version'];
			logger::debug("TUNEIN", "OPML version is ".$v);
			$this->parse_tree($x->body, $this->title);
		}
	}

	private function parse_tree($node, $title) {

		foreach ($node->outline as $o) {
			// logger::log('TUNEIN', print_r($o, true));
			$att = $o->attributes();
			switch ($att['type']) {

				case '':
					print uibits::ui_config_header([
						'label_text' => $att['text']
					]);
					$this->parse_tree($o, $title);
					break;

				case 'link':
					if (
						(!isset($att['key']) && in_array('radio', $this->domains))
						|| (isset($att['key']) && in_array('podcasts', $this->domains))
					) {
						uibits::printRadioDirectory($att, true, 'tunein');
					}
					break;

				case 'audio':
					switch ($att['item']) {
						case 'station':
							if (!in_array('radio', $this->domains))
								break 2;

							$sname = $att['text'];
							$year = '<br />(Radio Station)';
							break;

						case 'topic':
							if (!in_array('podcasts', $this->domains))
								break 2;

							$sname = $title;
							$year = '<br />(Podcast Episode)';
							break;

						default:
							$sname = $title;
							$year = ucfirst($att['item']);
							break;

					}

					print uibits::albumHeader(array(
						'openable' => false,
						'Image' => 'getRemoteImage.php?url='.rawurlencode($att['image']),
						'Year' => $year,
						'Artistname' => ((string) $att['playing'] != (string) $att['subtext']) ? $att['subtext'] : null,
						'Albumname' => $att['text'],
						'streamuri' => $att['URL'],
						'streamname' => $sname,
						'streamimg' => 'getRemoteImage.php?url='.rawurlencode($att['image']),
						'class' => 'radiochannel',
						'year_always' => true
					));
					break;

			}
		}

	}

}

if (array_key_exists('populate', $_REQUEST)) {

	chdir('..');

	include ("includes/vars.php");
	include ("includes/functions.php");

	$tunein = new tuneinplugin();
	$tunein->parseParams();
	$tunein->getUrl();

} else {

	$tunein = new tuneinplugin();
	$tunein->doHeader();
}

?>
