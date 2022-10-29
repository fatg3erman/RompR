<?php
class player extends base_mpd_player {

	private $monitor;

	private const WEBSOCKET_SUFFIX = '/mopidy/ws';

	public function check_track_load_command($uri) {
		return 'add';
	}

	public function musicCollectionUpdate() {
		logger::mark("MOPIDY", "Starting Music Collection Update");
		if (prefs::get_pref('use_mopidy_scan')) {
			logger::mark('MOPIDY', 'Using mopidy local scan');
			$dir = getcwd();
			exec('sudo mopidyctl local scan >> '.$dir.'/prefs/monitor 2>&1');
			logger::mark('MOPIDY', 'Mopidy local scan finished');
		}
		$this->monitor = fopen('prefs/monitor','w');
		$dirs = prefs::get_pref('mopidy_collection_folders');
		logger::log('MOPIDY', 'Collection Folders Are', print_r($dirs, true));
		while (count($dirs) > 0) {
			$dir = array_shift($dirs);
			logger::log('MOPIDY', 'Scanning', $dir);
			if ($dir == "Spotify Playlists") {
				$dirs_dummy = array();
				$playlists = $this->do_mpd_command("listplaylists", true, true);
				logger::log('MOPIDY', 'Playlists are',print_r($playlists, true));
				if (is_array($playlists) && array_key_exists('playlist', $playlists)) {
					foreach ($playlists['playlist'] as $pl) {
						if (preg_match('/\(by spotify\)/', $pl)) {
							logger::info("COLLECTION", "Ignoring Playlist ".$pl);
						} else {
							logger::log("COLLECTION", "Scanning Playlist ".$pl);
							fwrite($this->monitor, "\n<b>".language::gettext('label_scanningp', array($pl))."</b>\n");
							foreach ($this->parse_list_output('listplaylistinfo "'.format_for_mpd($pl).'"', $dirs_dummy, false) as $filedata) {
								yield $filedata;
							}
						}
					}
				} else {
					logger::log('MOPIDY', 'No Spotify Playlists Found');
				}
			} else {
				fwrite($this->monitor, "\n<b>".language::gettext('label_scanningf', array($dir))."</b><br />".language::gettext('label_fremaining', array(count($dirs)))."\n");
				foreach ($this->parse_list_output('lsinfo "'.format_for_mpd($this->local_media_check($dir)).'"', $dirs, false) as $filedata) {
					yield $filedata;
				}
			}
		}
		fwrite($this->monitor, "\nUpdating Database\n");
	}

	public function collectionUpdateDone() {
		saveCollectionPlayer('mopidy');
		fwrite($this->monitor, "\nRompR Is Done\n");
		fclose($this->monitor);
	}

	private function local_media_check($dir) {
		if ($dir == "Local media") {
			// Mopidy-Local-SQlite contains a virtual tree sorting things by various keys
			// If we scan the whole thing we scan every file about 8 times. This is stoopid.
			// Check to see if 'Local media/Albums' is browseable and use that instead if it is.
			// Using Local media/Folders causes every file to be re-scanned every time we update
			// the collection, which takes ages and also includes m3u and pls stuff that we don't want
			$r = $this->do_mpd_command('lsinfo "'.$dir.'/Albums"', false, false);
			if ($r === false) {
				return $dir;
			} else {
				return $dir.'/Albums';
			}
		}
		return $dir;
	}

	protected function player_specific_fixups(&$filedata) {
		if (strpos($filedata['file'], 'spotify:artist:') !== false) {
			$this->to_browse[] = [
				'Uri' => $filedata['file'],
				'Name' => preg_replace('/Artist: /', '', $filedata['Title'])
			];
			logger::log('MOPIDY', 'Marking',$filedata['Title'],$filedata['file'],'as browse artist');
			return false;
		} else if (strpos($filedata['file'], ':album:') !== false) {
			$filedata['X-AlbumUri'] = $filedata['file'];
			$filedata['Disc'] = 0;
			$filedata['Track'] = 0;
		}

		switch($filedata['domain']) {
			case 'local':
				// mopidy-local-sqlite sets album URIs for local albums, but sometimes it gets it very wrong
				// We don't need Album URIs for local tracks, since we can already add an entire album
				$filedata['X-AlbumUri'] = null;
				$this->check_undefined_tags($filedata);
				$filedata['folder'] = dirname($filedata['unmopfile']);
				if (prefs::get_pref('audiobook_directory') != '') {
					$f = rawurldecode($filedata['folder']);
					if (strpos($f, prefs::get_pref('audiobook_directory')) === 0) {
						$filedata['type'] = 'audiobook';
					}
				}
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
			case 'dirble':
			case 'tunein':
			case 'radio-de':
			case 'audioaddict':
			case 'oe1':
			case 'bassdrive':
				$this->preprocess_stream($filedata);
				break;

			case "soundcloud":
				$this->preprocess_soundcloud($filedata);
				break;

			case "youtube":
				$this->preprocess_youtube($filedata);
				break;

			case "ytmusic":
				$this->preprocess_ytmusic($filedata);
				break;

			case "spotify":
				$filedata['folder'] = $filedata['X-AlbumUri'];
				break;

			case "internetarchive":
				$this->check_undefined_tags($filedata);
				$filedata['X-AlbumUri'] = $filedata['file'];
				$filedata['folder'] = $filedata['file'];
				$filedata['AlbumArtist'] = "Internet Archive";
				break;

			case "podcast":
				$filedata['folder'] = $filedata['X-AlbumUri'];
				if ($filedata['Artist'] !== null) {
					$filedata['AlbumArtist'] = $filedata['Artist'];
				}
				if ($filedata['AlbumArtist'] === null) {
					$filedata['AlbumArtist'] = array("Podcasts");
				}
				if (is_array($filedata['Artist']) &&
					($filedata['Artist'][0] == "http" ||
					$filedata['Artist'][0] == "https" ||
					$filedata['Artist'][0] == "ftp" ||
					$filedata['Artist'][0] == "file" ||
					substr($filedata['Artist'][0],0,7) == "podcast")) {
					$filedata['Artist'] = $filedata['AlbumArtist'];
				}
				$filedata['type'] = 'podcast';
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

		if (strpos($filedata['file'], 'bassdrive.com') !== false) {
			$filedata['Album'] = 'Bassdrive';
		}

		// Mopidy's podcast backend
		if ($filedata['Genre'] == "Podcast") {
			$filedata['type'] = "podcast";
		}

	}

	private function preprocess_soundcloud(&$filedata) {
		$filedata['folder'] = concatenate_artist_names($filedata['Artist']);
		if (!$filedata['AlbumArtist'])
			$filedata['AlbumArtist'] = $filedata['Artist'];

		if (!$filedata['X-AlbumUri'])
			$filedata['X-AlbumUri'] = $filedata['file'];

		if ($filedata['Title'] && !$filedata['Album'])
			$filedata['Album'] = $filedata['Title'];

		if ($filedata['X-AlbumImage'])
			$filedata['X-AlbumImage'] = 'getRemoteImage.php?url='.rawurlencode($filedata['X-AlbumImage']);

	}

	private function preprocess_youtube(&$filedata) {
		$filedata['folder'] = hash('md2', $filedata['X-AlbumUri'], false);
		// if (!$filedata['AlbumArtist'])
		// 	$filedata['AlbumArtist'] = $filedata['Artist'];

		// if (!$filedata['X-AlbumUri'])
		// 	$filedata['X-AlbumUri'] = $filedata['file'];

		// if ($filedata['Title'] && !$filedata['Album'])
		// 	$filedata['Album'] = $filedata['Title'];

		if (strpos($filedata['Artist'][0], 'YouTube Playlist') !== false) {
			$filedata['Artist'] = ['YouTube Playlists'];
		}

		if ($filedata['X-AlbumImage'])
			$filedata['X-AlbumImage'] = 'getRemoteImage.php?url='.rawurlencode($filedata['X-AlbumImage']);

	}

	private function preprocess_ytmusic(&$filedata) {
		$filedata['folder'] = hash('md2', $filedata['X-AlbumUri'], false);
		// if (!$filedata['AlbumArtist'])
		// 	$filedata['AlbumArtist'] = $filedata['Artist'];

		// if (!$filedata['X-AlbumUri'])
		// 	$filedata['X-AlbumUri'] = $filedata['file'];

		// if ($filedata['Title'] && !$filedata['Album'])
		// 	$filedata['Album'] = $filedata['Title'];

		if ($filedata['X-AlbumImage'])
			$filedata['X-AlbumImage'] = 'getRemoteImage.php?url='.rawurlencode($filedata['X-AlbumImage']);

	}

	private function check_radio_and_podcasts($filedata) {

		$url = $filedata['file'];

		// Check for any http files added to the collection or downloaded youtube tracks
		$result = prefs::$database->check_stream_in_collection($url);
		foreach ($result as $obj) {
			logger::log("STREAMHANDLER", "Found Track in collection!",$obj->title);
			return array(
				$obj->title,
				$obj->duration,
				array($obj->artist),
				$obj->album,
				md5($obj->album),
				'local',
				$obj->image,
				null,
				'',
				array($obj->albumartist),
				null,
				'',
				$obj->imgkey
			);
		}

		// Do podcasts first. Podcasts played fro TuneIn get added as radio stations, and then if we play that track again
		// via podcasts we want to make sure we pick up the details.

		$result = prefs::$database->find_podcast_track_from_url($url);
		foreach ($result as $obj) {
			logger::log("STREAMHANDLER", "Found PODCAST",$obj->title);
			return array(
				($obj->title == '') ? $filedata['Title'] : $obj->title,
				// Mopidy's estimate of the duration is frequently more accurate than that supplied in the RSS
				(array_key_exists('Time', $filedata) && $filedata['Time'] > 0) ? $filedata['Time'] : $obj->duration,
				($obj->artist == '') ? $filedata['Artist'] : array($obj->artist),
				($obj->album == '') ? $filedata['Album'] : $obj->album,
				md5($obj->album),
				'podcast',
				$obj->image,
				null,
				'',
				($obj->albumartist == '') ? $filedata['AlbumArtist'] : array($obj->albumartist),
				null,
				format_podcast_text($obj->comment),
				null
			);
		}

		$result = prefs::$database->find_radio_track_from_url($url);
		foreach ($result as $obj) {
			logger::log("STREAMHANDLER", "Found Radio Station ".$obj->StationName);
			// Munge munge munge to make it looks pretty
			if ($obj->StationName != '') {
				logger::trace("STREAMHANDLER", "  Setting Album name from database ".$obj->StationName);
				$album = $obj->StationName;
			} else if ($filedata['Name'] && $filedata['Name'] != 'no name' && strpos($filedata['Name'], ' ') !== false) {
				logger::trace("STREAMHANDLER", "  Setting Album from Name ".$filedata['Name']);
				$album = $filedata['Name'];
			} else if ($filedata['Name'] == null && $filedata['Title'] != null && $filedata['Title'] != 'no name' &&
				$filedata['Artist'] == null && $filedata['Album'] == null && strpos($filedata['Title'], ' ') !== false) {
				logger::trace("STREAMHANDLER", "  Setting Album from Title ".$filedata['Title']);
				$album = $filedata['Title'];
				$filedata['Title'] = null;
			} else {
				logger::log("STREAMHANDLER", "  No information to set Album field");
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

		logger::warn("STREAMHANDLER", "Stream Track",$filedata['file'],"from",$filedata['domain'],"was not found in database");

		if ($filedata['Album']) {
			$album = $filedata['Album'];
		} else if ($filedata['Name']) {
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
			logger::log("STREAMHANDLER", "  No information to set Album field");
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
		return array('clicktrack', $url);
	}

	public function get_replay_gain_state() {
		return array();
	}

	public function toggle_consume($value) {
		if ($value == 0) {
			logger::log('MOPIDY', 'Disabling local consume');
			prefs::set_player_param(['do_consume' => false]);
		} else {
			logger::log('POSTCOMMAND', 'Enabling local consume');
			prefs::set_player_param(['do_consume' => true]);
		}
		return false;
	}

	public function get_consume($value) {
		$pd = prefs::get_player_def();
		return $pd['do_consume'] ? 1 : 0;
	}

	// This is here to allow us to force consume to Off when we connect to Mopidy
	// so that our local consume can take over;
	public function set_consume_state() {
		$this->do_command_list(['consume 0']);
	}

	public function force_consume_state($state) {
		$this->toggle_consume($state);
	}

	public static function is_personal_playlist($playlist) {
		if (strpos($playlist, '(by ') !== false) {
			return false;
		}
		return true;
	}

	public function probe_websocket() {
		logger::log('MOPIDYHTTP', 'Probing HTTP API');
		$result = $this->mopidy_http_request(
			$this->ip.':'.prefs::get_pref('http_port_for_mopidy'),
			array(
				'method' => 'core.get_version'
			)
		);
		if ($result !== false) {
			logger::log('MOPIDYHTTP', 'Connected to Mopidy HTTP API Successfully');
			$http_server = nice_server_address($this->ip);
			prefs::set_player_param(['websocket' => $http_server.':'.prefs::get_pref('http_port_for_mopidy').self::WEBSOCKET_SUFFIX]);
			logger::log('MOPIDYHTTP', 'Using',prefs::get_player_param('websocket'),'for Mopidy HTTP');
		} else {
			logger::log('MOPIDYHTTP', 'Mopidy HTTP API Not Available');
			prefs::set_player_param(['websocket' => false]);
		}

	}

	private function mopidy_http_request($port, $data) {
		$url = 'http://'.$port.'/mopidy/rpc';

		$data['jsonrpc'] = '2.0';
		$data['id'] = 1;

		$options = array(
		    'http' => array(
		        'header'  => "Content-Type: application/json\r\n",
		        'method'  => 'POST',
		        'content' => json_encode($data)
		    )
		);
		$context  = stream_context_create($options);
		// Disable reporting of warnings for this call otherwise it spaffs into the error log
		// if the connection doesn't work.
		error_reporting(E_ERROR);
		$cheese = file_get_contents($url, false, $context);
		error_reporting();
		return $cheese;
	}

	public function search_for_album_image($albumimage) {
		$retval = '';
		if ($albumimage->albumuri) {
			logger::log('GETALBUMCOVER', 'Trying Mopidy-Images. AlbumURI is', $albumimage->albumuri);
			$retval = $this->find_album_image($albumimage->albumuri);
		} else if ($albumimage->trackuri) {
			logger::log('GETALBUMCOVER', 'Trying Mopidy-Images. TrackURI is', $albumimage->trackuri);
			$retval = $this->find_album_image($albumimage->trackuri);
		}
		return $retval;
	}

	// So that checklocalcover.php doesn't crash when we're using Mopidy
	public function albumart($uri, $embedded) {
		return '';
	}

	private function strip_http_port() {
		return str_replace(self::WEBSOCKET_SUFFIX, '', prefs::get_player_param('websocket'));
	}

	public function find_album_image($uri) {
		if (prefs::get_player_param('websocket') === false)
			return '';

		$retval = '';
		$result = $this->mopidy_http_request(
			$this->strip_http_port(),
			array(
				'method' => 'core.library.get_images',
				"params" => array(
					"uris" => array($uri)
				)
			)
		);
		if ($result !== false) {
			$biggest = 0;
			logger::log('MOPIDYHTTP', 'Connected to Mopidy HTTP API Successfully');
			logger::log('MOPIDYHTTP', $result);
			$json = json_decode($result, true);
			if (array_key_exists('error', $json)) {
				logger::warn('MOPIDYHTTP', 'Summit went awry');
			} else if (array_key_exists($uri, $json['result']) && is_array($json['result'][$uri])) {
				foreach ($json['result'][$uri] as $image) {
					if (!array_key_exists('width', $image)) {
						$retval = ($retval == '') ? $image['uri'] : $this->compare_images($retval, $image['uri']);
					} else if ($image['width'] > $biggest) {
						$retval = $image['uri'];
						$biggest = $image['width'];
					}
				}
			}
		}
		if (strpos($retval, '/local/') === 0) {
			$retval = 'http://'.$this->strip_http_port().$retval;
		}
		if (basename($retval) == 'default.jpg' && strpos($retval, 'ytimg.com') !== false) {
			logger::log('MOPIDYHTTP', 'Mopidy-Youtube only returned youtube default image. Checking for hqdefault');
			$new_url = dirname($retval).'/hqdefault.jpg';
			$mrchunks = new url_downloader(['url' => $new_url]);
			if ($mrchunks->get_data_to_string()) {
				$retval = $new_url;
			}
		}
		logger::log('MOPIDYHTTP', 'Returning', $retval);
		return $retval;
	}

	private function compare_images($current, $candidate) {
		$retval = $current;
		$ours = strtolower(pathinfo($current, PATHINFO_FILENAME));
		$theirs = strtolower(pathinfo($candidate, PATHINFO_FILENAME));
		if ($ours == 'default' && ($theirs == 'mqdefault' || $theirs == 'hqdefault')) {
			$retval = $candidate;
		}
		if ($ours == 'mqdefault' && $theirs == 'hqdefault') {
			$retval = $candidate;
		}
		return $retval;
	}

}

?>