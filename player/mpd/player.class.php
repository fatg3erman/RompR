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

	public function has_specific_search_function($mpdsearch, $domains) {
		return false;
	}

	protected function player_specific_fixups(&$filedata) {
		switch($filedata['domain']) {
			case 'local':
				$this->check_undefined_tags($filedata);
				$filedata['folder'] = dirname($filedata['unmopfile']);
				if (prefs::get_pref('audiobook_directory') != '') {
					$f = rawurldecode($filedata['folder']);
					if (strpos($f, prefs::get_pref('audiobook_directory')) === 0) {
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
			logger::log("STREAMHANDLER", "Found Podcast ".$obj->title);
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
				logger::warn("STREAMHANDLER", "  No information to set Album field");
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

		logger::warn("STREAMHANDLER", "Stream Track",$filedata['file'],"from",$filedata['domain'],"was not found ind atabase");

		if ($filedata['Name']) {
			logger::trace("STREAMHANDLER", "  Setting Album from Name ".$filedata['Name']);
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
			logger::warn("STREAMHANDLER", "  No information to set Album field");
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

	// This does NOT toggle consume. For Mopidy it does. For MPD it returns true,
	// to instruct this class to actually send the consume command instead of
	// using local consume.
	public function toggle_consume($value) {
		return true;
	}

	// Returns the current state of consume. Which is what you should pass in as $value
	// Why does it return what we pass to it? Because the Mopidy version doesn't do that,
	// it returns the current setting of our local consume.
	public function get_consume($value) {
		return $value;
	}

	public function force_consume_state($state) {
		$this->do_command_list(['consume '.$state]);
	}

	public function set_consume_state() {

	}

	public static function is_personal_playlist($pl) {
		return true;
	}

	private function websocket_command() {
		$retval = 	'/player/mpd/mpd_websocket.py'
					.' --currenthost='.prefs::currenthost()
					.' --wsport='.prefs::get_pref('mpd_websocket_port');

		if ($this->socket) {
			$retval .= ' --unix='.$this->socket;
		} else {
			$retval	.= ' --mpdhost='.$this->ip
					.  ' --mpdport='.$this->port;
		}

		if ($this->password != '')
			$retval .= ' --mpdpassword='.$this->password;

		return $retval;
	}

	public function probe_websocket() {
		if (prefs::get_pref('mpd_websocket_port') !== '') {
			if (get_pid($this->websocket_command()) === false) {
				if (($pid = get_pid('mpd_websocket.py --currenthost='.prefs::currenthost())) !== false) {
					logger::info('MPDSOCKET', 'Killing PID',$pid,'of Websocket Server with different config');
					kill_process($pid);
				}
				logger::log('MPDSOCKET', 'Starting MPD Websocket Server');
				$pwd = getcwd();
				$pid = start_process($pwd.$this->websocket_command(), 'python3');
				if (get_pid($this->websocket_command()) === false) {
					logger::warn('MPDSOCKET', 'Failed to start MPD Websocket Server');
					prefs::set_player_param(['websocket' => false]);
					return false;
				}
			} else {
				logger::log('MPDSOCKET', 'MPD Websocket Server already running');
			}
			$http_server = nice_server_address($this->ip);
			prefs::set_player_param(['websocket' => $http_server.':'.prefs::get_pref('mpd_websocket_port').'/']);
			logger::log('MPDSOCKET', 'Using',prefs::get_player_param('websocket'),'for MPD websocket');
		} else {
			logger::log('MPDSOCKET', 'MPD websocket Not Configured');
			if (($pid = get_pid('mpd_websocket.py --currenthost='.prefs::currenthost())) !== false) {
				logger::info('MPDSOCKET', 'Killing PID',$pid,'of Websocket Server with different config');
				kill_process($pid);
			}
			prefs::set_player_param(['websocket' => false]);
		}

	}

	public function search_for_album_image($albumimage) {
		if ($albumimage->trackuri == '')
			return '';

		$filename = '';
		if ($this->check_mpd_version('0.22')) {
			logger::log('GETALBUMCOVER', 'Trying MPD embedded image. TrackURI is', $albumimage->trackuri);
			$filename = $this->albumart($albumimage->trackuri, true);
		}
		if ($filename == '' && $this->check_mpd_version('0.21')) {
			logger::log('GETALBUMCOVER', 'Trying MPD folder image. TrackURI is', $albumimage->trackuri);
			$filename = $this->albumart($albumimage->trackuri, false);
		}
		return $filename;

	}

	public function albumart($uri, $embedded) {
		$offset = 0;
		$size = null;
		$handle = null;
		$filename = '';
		$retries = 3;
		logger::log('ALBUMART', 'Fetching', $embedded?'embedded':'folder', 'albumart for', $uri);
		if ($this->check_mpd_version('0.22.4')) {
			$this->do_mpd_command('binarylimit 1048576');
		}
		while (($size === null || $size > 0) && $retries > 0) {
			logger::debug('MPDPLAYER', '  Reading at offset',$offset);
			$command = $embedded ? 'readpicture' : 'albumart';
			$result = $this->do_mpd_command($command.' "'.$uri.'" '.$offset, true);
			if (is_array($result) && array_key_exists('binary', $result)) {
				if ($size === null) {
					$size = $result['size'];
					logger::debug('MPDPLAYER', 'Size is',$size);

					$filename = 'prefs/temp/'.md5($uri);
					$handle = fopen($filename, 'w');
				}
				if ($result['binary'] == strlen($result['binarydata'])) {
					fwrite($handle, $result['binarydata']);
					$size -= $result['binary'];
					$offset += $result['binary'];
					logger::debug('MPDPLAYER', 'Remaining', $size);
				} else {
					logger::warn('MPDPLAYER', 'Expected', $result['binary'], 'bytes but only got', strlen($result['binarydata']));
					$retries--;
				}
			} else {
				logger::warn('ALBUMART', 'No binary data in response from MPD');
				$retries--;
			}
		}
		if ($handle)
			fclose($handle);

		if ($retries == 0 && $filename != '') {
			unlink($filename);
			$filename = '';
		}

		return $filename;
	}


}

?>