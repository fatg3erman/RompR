<?php

class tuneinplugin {

	public function __construct() {
		$this->url = 'http://opml.radiotime.com/';
		$this->title = '';
	}

	public function doHeader() {
		// print '<div id="tuneinradio">';
		print uibits::albumHeader(array(
			'playable' => false,
			'id' => 'tuneinlist',
			'Image' => 'newimages/tunein-logo.svg',
			'Albumname' => language::gettext('label_tuneinradio'),
			'class' => 'radio tuneinroot',
		));
		print '<div id="tuneinlist" class="dropmenu notfilled is-albumlist">';
		uibits::ui_config_header([
			'label' => 'label_loading'
		]);
		print '</div>';
	}

	public function parseParams() {
		if (array_key_exists('url', $_REQUEST)) {
			$this->url = $_REQUEST['url'];
		} else {
			uibits::directoryControlHeader('tuneinlist', language::gettext('label_tuneinradio'));
			print '<div class="containerbox fullwidth vertical-centre"><div class="expand">
				<input class="enter clearbox tuneinsearchbox" name="tuneinsearcher" type="text" ';
			if (array_key_exists('search', $_REQUEST)) {
				print 'value="'.$_REQUEST['search'].'" ';
			}
			print '/></div><button class="fixed tuneinsearchbutton searchbutton iconbutton clickable tunein"></button></div>';
		}
		if (array_key_exists('title', $_REQUEST)) {
			$this->title = $_REQUEST['title'];
			uibits::directoryControlHeader($_REQUEST['target'], htmlspecialchars($this->title));
		}
		if (array_key_exists('search', $_REQUEST)) {
			uibits::directoryControlHeader('tuneinlist', language::gettext('label_tuneinradio'));
			$this->url .= 'Search.ashx?query='.urlencode($_REQUEST['search']);
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
			$att = $o->attributes();
			logger::core("TUNEIN", "  Text is",$att['text'],", type is",$att['type']);
			switch ($att['type']) {

				case '':
					uibits::ui_config_header([
						'label_text' => $att['text']
					]);
					$this->parse_tree($o, $title);
					break;

				case 'link':
					uibits::printRadioDirectory($att, true, 'tunein');
					break;

				case 'audio':
					switch ($att['item']) {
						case 'station':
							$sname = $att['text'];
							$year = 'Radio Station';
							break;

						case 'topic':
							$sname = $title;
							$year = 'Podcast Episode';
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
						'class' => 'radiochannel'
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
