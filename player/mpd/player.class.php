<?php
class player extends base_mpd_player {

	private $monitor;

	public function check_track_load_command($uri) {
		$ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
		switch ($ext) {
			case 'm3u':
			case 'm3u8':
			case 'asf':
			case 'asx':
				return 'load';
				break;

			default:
				if (preg_match('#www\.radio-browser\.info/webservice/v2/m3u#', $uri)) {
					return 'load';
				} else {
					return 'add';
				}
				break;
		}
	}

	public function musicCollectionUpdate() {
		logger::mark("MPD", "Starting Music Collection Update");
		$this->monitor = fopen('prefs/monitor','w');
		$dirs = array("/");
		while (count($dirs) > 0) {
			$dir = array_shift($dirs);
			fwrite($this->monitor, "\n<b>".language::gettext('label_scanningf', array($dir))."</b><br />".language::gettext('label_fremaining', array(count($dirs)))."\n");
			foreach ($this->parse_list_output('lsinfo "'.format_for_mpd($dir).'"', $dirs, false) as $filedata) {
				yield $filedata;
			}
		}
		fwrite($this->monitor, "\nUpdating Database\n");
	}

	public function collectionUpdateDone() {
		saveCollectionPlayer('mpd');
		fwrite($this->monitor, "\nRompR Is Done\n");
		fclose($this->monitor);
	}

	protected function player_specific_fixups(&$filedata) {
		switch($filedata['domain']) {
			case 'local':
				$this->check_undefined_tags($filedata);
				$filedata['folder'] = dirname($filedata['unmopfile']);
				if (prefs::$prefs['audiobook_directory'] != '') {
					$f = rawurldecode($filedata['folder']);
					if (strpos($f, prefs::$prefs['audiobook_directory']) === 0) {
						$filedata['type'] = 'audiobook';
					}
				}
				break;

			case "soundcloud":
				$this->preprocess_soundcloud($filedata);
				break;

			case 'http':
			case 'https':
			case 'mms':
			case 'mmsh':
			case 'mmst':
			case 'mmsu':
			case 'gopher':
			case 'rtp':
			case 'rtsp':
			case 'rtmp':
			case 'rtmpt':
			case 'rtmps':
				$this->preprocess_stream($filedata);
				break;

			default:
				$this->check_undefined_tags($filedata);
				$filedata['folder'] = dirname($filedata['unmopfile']);
				break;
		}
		return true;
	}

	private function preprocess_stream(&$filedata) {

		$filedata['Track'] = null;

		list (  $filedata['Title'],
				$filedata['Time'],
				$filedata['Artist'],
				$filedata['Album'],
				$filedata['folder'],
				$filedata['type'],
				$filedata['X-AlbumImage'],
				$filedata['station'],
				$filedata['stream'],
				$filedata['AlbumArtist'],
				$filedata['StreamIndex'],
				$filedata['Comment'],
				$filedata['ImgKey']) = $this->check_radio_and_podcasts($filedata);

		if (strrpos($filedata['file'], '#') !== false) {
			# Fave radio stations added by Cantata/MPDroid
			$filedata['Album'] = substr($filedata['file'], strrpos($filedata['file'], '#')+1, strlen($filedata['file']));
		}

	}

	private function preprocess_soundcloud(&$filedata) {
		if ($filedata['Name'] != null) {
			$filedata['Title'] = $filedata['Name'];
			$filedata['Album'] = "SoundCloud";
			$arse = explode(' - ',$filedata['Name']);
			$filedata['Artist'] = array($arse[0]);
		} else {
			$filedata['Artist'] = array("Unknown Artist");
			$filedata['Title'] = "Unknown Track";
			$filedata['Album'] = "SoundCloud";
		}
	}

	private function check_radio_and_podcasts($filedata) {

		$url = $filedata['file'];

		// Do podcasts first. Podcasts played fro TuneIn get added as radio stations, and then if we play that track again
		// via podcasts we want to make sure we pick up the details.

		$result = prefs::$database->find_podcast_track_from_url($url);
		foreach ($result as $obj) {
			logger::log("STREAMHANDLER", "Found PODCAST ".$obj->title);
			return array(
				($obj->title == '') ? $filedata['Title'] : $obj->title,
				$obj->duration,
				($obj->artist == '') ? $filedata['Artist'] : array($obj->artist),
				($obj->album == '') ? $filedata['Album'] : $obj->album,
				md5($obj->album),
				'podcast',
				$obj->image,
				null,
				'',
				($obj->albumartist == '') ? $filedata['AlbumArtist'] : array($obj->albumartist),
				null,
				$obj->comment,
				null
			);
		}

		$result = prefs::$database->find_radio_track_from_url($url);
		foreach ($result as $obj) {
			logger::log("STREAMHANDLER", "Found Radio Station ".$obj->StationName);
			// Munge munge munge to make it looks pretty
			if ($obj->StationName != '') {
				logger::trace("STREAMHANDLER", "  Setting Album from database ".$obj->StationName);
				$album = $obj->StationName;
			} else if ($filedata['Name'] && strpos($filedata['Name'], ' ') !== false) {
				logger::trace("STREAMHANDLER", "  Setting Album from Name ".$filedata['Name']);
				$album = $filedata['Name'];
			} else if ($filedata['Name'] == null && $filedata['Title'] != null && $filedata['Artist'] == null && $filedata['Album'] == null && strpos($filedata['Title'], ' ') !== false) {
				logger::trace("STREAMHANDLER", "  Setting Album from Title ".$filedata['Title']);
				$album = $filedata['Title'];
				$filedata['Title'] = null;
			} else {
				logger::trace("STREAMHANDLER", "  No information to set Album field");
				$album = ROMPR_UNKNOWN_STREAM;
			}
			return array (
				$filedata['Title'] === null ? '' : $filedata['Title'],
				0,
				$filedata['Artist'],
				$album,
				$obj->PlaylistUrl,
				"stream",
				($obj->Image == '') ? $filedata['X-AlbumImage'] : $obj->Image,
				$this->getDummyStation($url),
				$obj->PrettyStream,
				$filedata['AlbumArtist'],
				$obj->Stationindex,
				array_key_exists('Comment', $filedata) ? $filedata['Comment'] : '',
				null
			);
		}

		logger::warn("STREAMHANDLER", "Stream Track",$filedata['file'],"from",$filedata['domain'],"was not found indatabase");

		if ($filedata['Name']) {
			logger::log("STREAMHANDLER", "  Setting Album from Name ".$filedata['Name']);
			$album = $filedata['Name'];
			if ($filedata['Pos'] !== null) {
				prefs::$database->update_radio_station_name(array('streamid' => null,'uri' => $filedata['file'], 'name' => $album));
			}
		} else if ($filedata['Name'] == null && $filedata['Title'] != null && $filedata['Artist'] == null && $filedata['Album'] == null) {
			logger::trace("STREAMHANDLER", "  Setting Album from Title ".$filedata['Title']);
			$album = $filedata['Title'];
			$filedata['Title'] = null;
			if ($filedata['Pos'] !== null) {
				prefs::$database->update_radio_station_name(array('streamid' => null,'uri' => $filedata['file'], 'name' => $album));
			}
		} else {
			logger::trace("STREAMHANDLER", "  No information to set Album field");
			$album = ROMPR_UNKNOWN_STREAM;
		}
		return array(
			$filedata['Title'],
			0,
			$filedata['Artist'],
			$album,
			$this->getStreamFolder(unwanted_array($url)),
			"stream",
			($filedata['X-AlbumImage'] == null) ? '' : $filedata['X-AlbumImage'],
			$this->getDummyStation(unwanted_array($url)),
			null,
			$filedata['AlbumArtist'],
			null,
			array_key_exists('Comment', $filedata) ? $filedata['Comment'] : '',
			null
		);

	}

	public function get_checked_url($url) {
		$matches = array();
		if (preg_match("/api\.soundcloud\.com\/tracks\/(\d+)\//", $url, $matches)) {
			return array('clickcue', "soundcloud://track/".$matches[1]);
		} else {
			return array('clicktrack', $url);
		}
	}

	public function get_replay_gain_state() {
		$arse = $this->do_mpd_command('replay_gain_status', true, false);
		if (array_key_exists('error', $arse)) {
			unset($arse['error']);
			$this->send_command('clearerror');
		}
		return $arse;
	}

	public function toggle_consume($value) {
		return true;
	}

	public function get_consume($value) {
		return $value;
	}

	public function set_consume_state() {

	}

	public static function is_personal_playlist($pl) {
		return true;
	}


}

?>