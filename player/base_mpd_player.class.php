<?php

class base_mpd_player {

	protected $connection;
	protected $ip;
	protected $port;
	protected $socket;
	protected $password;
	protected $player_type;
	private $is_remote;
	public $playlist_error;
	private $debug_id;
	public $to_browse;
	private $mpd_version = null;

	protected $mpd_status;
	protected $moveallto;
	protected $current_playlist_length;
	protected $do_resume_seek;
	protected $do_resume_seek_id;
	protected $playlist_movefrom;
	protected $playlist_moveto;
	protected $playlist_moving_within;
	protected $playlist_tracksadded;
	protected $expected_state;

	private $db_updated = 'no';
	private $radiomode = false;
	private $radioparam = false;
	private $current_status = [];
	public $current_song = [];

	public function __construct($ip = null, $port = null, $socket = null, $password = null, $player_type = null, $is_remote = null) {
		$this->debug_id = microtime();
		$pdef = prefs::get_player_def();
		if ($ip !== null) {
			$this->ip = $ip;
		} else {
			$this->ip = $pdef['host'];
		}
		if ($port !== null) {
			$this->port = $port;
		} else {
			$this->port = $pdef['port'];
		}
		if ($socket !== null) {
			$this->socket = $socket;
		} else {
			$this->socket = $pdef['socket'];
		}
		if ($password !== null) {
			$this->password = $password;
		} else {
			$this->password = $pdef['password'];
		}
		if ($is_remote !== null) {
			$this->is_remote = $is_remote;
		} else {
			$this->is_remote = $pdef['mopidy_remote'];
		}
		logger::core("MPDPLAYER", "Creating Player for",$this->ip.':'.$this->port);
		$this->open_mpd_connection();

		if ($player_type !== null) {
			$this->player_type = $player_type;
		} else {
			if (prefs::get_pref('player_backend') != null) {
				$this->player_type = prefs::get_pref('player_backend');
			} else {
				$this->player_type = $this->probe_player_type();
			}
		}
	}

	public function __destruct() {
		if ($this->is_connected()) {
			$this->close_mpd_connection();
		}
	}

	public function check_mpd_version($version) {
		if (preg_match('/(\d+\.\d+\.\d+)/', $this->mpd_version, $matches)) {
			if (version_compare($version, $matches[1], '<=')) {
				logger::log('MPDPLAYER', 'Version number',$version,'is <= MPD version',$matches[1]);
				return true;
			}
		} else {
			logger::warn('MPD', 'Could not compare version numbers',$this->mpd_version,'and',$version);
		}
		logger::log('MPDPLAYER', 'Version number',$version,'is > MPD version',$matches[1]);
		return false;
	}

	public function open_mpd_connection() {

		if ($this->is_connected())
			return true;

		$retries = 10;
		$errno = null;
		$errstr = null;

		while (!$this->is_connected() && $retries > 0) {
			if ($this->socket != "") {
				logger::core('MPDPLAYER', 'Opening connection to',$this->socket);
				$this->connection = @stream_socket_client('unix://'.$this->socket, $errno, $errstr, 10, STREAM_CLIENT_CONNECT);
			} else {
				logger::core('MPDPLAYER', 'Opening connection to',$this->ip.':'.$this->port);
				$this->connection = @stream_socket_client('tcp://'.$this->ip.':'.$this->port, $errno, $errstr, 10, STREAM_CLIENT_CONNECT);
			}
			if ($errno != 0) {
				logger::warn('MPD', 'Connection result: Error is',$errno,'Error String is',$errstr);
				$this->connection = false;
			}
			$retries--;
		}

		if($this->is_connected()) {
			stream_set_timeout($this->connection, 65535);
			stream_set_blocking($this->connection, true);
			while(!feof($this->connection)) {
				$gt = fgets($this->connection);
				if ($this->parse_mpd_var($gt)) {
					$this->mpd_version = $gt;
					break;
				}
			}
		} else {
			logger::error('MPD', 'Failed to connect to player', $errno, $errstr);
			return false;
		}

		if ($this->password != "" && $this->is_connected()) {
			fputs($this->connection, "password ".$this->password."\n");
			while(!feof($this->connection)) {
				$gt = fgets($this->connection);
				$a = $this->parse_mpd_var($gt);
				if($a === true) {
					break;
				} else if ($a == null) {

				} else {
					$this->close_mpd_connection();
					return false;
				}
			}
		}
		return true;
	}

	public function get_mpd_version() {
		if (preg_match('/OK MPD (.+$)/', $this->mpd_version, $matches)) {
			return $matches[1];
		} else {
			return 'Unknown. Got '.$this->mpd_version;
		}
	}

	public function close_mpd_connection() {
		if ($this->is_connected()) {
			logger::core('MPD', 'Closing Connection for',$this->debug_id);
			stream_socket_shutdown($this->connection, STREAM_SHUT_RDWR);
		}
	}

	public function is_connected() {
		if (isset($this->connection) && is_resource($this->connection) && !is_bool($this->connection)) {
			return true;
		} else {
			return false;
		}
	}

	private function parse_mpd_var($in_str) {
		$got = trim($in_str);
		if(!isset($got))
			return null;
		if(strncmp("OK", $got, 2) == 0)
			return true;
		if(strncmp("ACK", $got, 3) == 0) {
			return array(0 => false, 1 => $got);
		}
		$key = trim(strtok($got, ":"));
		$val = trim(strtok("\0"));
		return array(0 => $key, 1 => $val);
	}

	protected function getline() {
		$got = fgets($this->connection);
		$key = trim(strtok($got, ":"));
		$val = trim(strtok("\0"));
		if ($val != '') {
			return array($key, $val);
		} else if (strpos($got, "OK") === 0 || strpos($got, "ACK") === 0) {
			return false;
		} else {
			return true;
		}
	}

	protected function send_command($command) {
		if(!$this->is_connected())
			return false;

		$retries = 3;
		$l = strlen($command."\n");
		do {
			$b = @fputs($this->connection, $command."\n");
			if ((!$b || $b < $l) && $command != 'close') {
				logger::warn("MPD", "Socket Write Error for",$command,"- Wrote",$b,"bytes of",$l,"- Retrying");
				stream_socket_shutdown($this->connection, STREAM_SHUT_RDWR);
				$this->connection = false;
				usleep(500000);
				$this->open_mpd_connection();
				$retries--;
			} else {
				return true;
			}
		} while ($retries > 0);
		return false;
	}

	protected function do_mpd_command($command, $return_array = false, $force_array_results = false) {

		$retarr = array();
		if ($this->is_connected()) {
			logger::core("MPD", "MPD Command",$command);
			$success = true;
			if ($command != '') {
				$success = $this->send_command($command);
			}
			if ($success) {
				while(!feof($this->connection)) {
					$var = $this->parse_mpd_var(fgets($this->connection));
					if(isset($var)){
						// Got an OK or ACK but - no results or return_array is false
						if($var === true && count($retarr) == 0)
							return true;

						if ($var === true)
							break;

						if ($var[0] == false) {
							$sdata = stream_get_meta_data($this->connection);
							if (array_key_exists('timed_out', $sdata) && $sdata['timed_out']) {
								$var[1] = 'Timed Out';
							}
							logger::warn("MPD", "Error for'",$command,"':",$var[1]);
							if ($return_array == true) {
								$retarr['error'] = $var[1];
							} else {
								return false;
							}
							break;
						}
						if ($return_array == true) {
							if(array_key_exists($var[0], $retarr)) {
								if(is_array($retarr[($var[0])])) {
									$retarr[($var[0])][] = $var[1];
								} else {
									$tmp = $retarr[($var[0])];
									$retarr[($var[0])] = array($tmp, $var[1]);
								}
							} else {
								if ($force_array_results) {
									$retarr[($var[0])] = array($var[1]);
								} else {
									$retarr[($var[0])] = $var[1];
								}
							}
						}
						if ($var[0] == 'binary') {
							$toread = $retarr['binary'];
							logger::log('MPDPLAYER', 'Reading', $toread, 'bytes of binary data');
							while ($toread > 0) {
								$data = fread($this->connection, $toread);
								$datalen = strlen($data);
								$toread -= $datalen;
								logger::trace('MPDPLAYER', 'Read', $datalen, 'bytes,', $toread, 'bytes more to go.');
								if (array_key_exists('binarydata', $retarr)) {
									$retarr['binarydata'] .= $data;
								} else {
									$retarr['binarydata'] = $data;
								}
							}
							fgets($this->connection); // for the trailing newline
						}
					}
				}
			} else {
				logger::error("MPD", "Failure to fput command",$command);
				$retarr['error'] = "There was an error communicating with ".ucfirst($this->player_type)."! (could not write to socket)";
			}
		} else {
			logger::warn('MPD', 'Attempting Command',$command,'while not connected!');
		}
		return $retarr;
	}

	// If we're doing certain actions we might want to set a Resume bookmark. It's better to do it here
	// in the backend because then we handle setting the bookmark even if it's the alarm clock
	// that is interrupting playback. Note that we DON'T do this as part of the idle system loop
	// because we specifically want to be able to update the UI by returning db_updated;
	// It's a tad convoluted
	private function check_set_resume_bookmark($cmds) {
		$temp_status = $this->get_status();
		if ($temp_status['state'] == 'play' || $temp_status['state'] == 'pause') {
			foreach ($cmds as $c) {
				if ($c == 'stop' || $c == 'next' || $c == 'previous' || strpos($c, 'play') === 0 || $c == 'clear') {
					$this->set_resume($temp_status);
					break;
				}
			}
		}
	}

	// If we do something, we set $this->db_updated, which, if this is in response to something from the UI
	// gets returned to controller.js and that will use it to call 'getreturninfo' via metaDatabase
	private function set_resume($temp_status) {
		$temp_db = new metaDatabase();
		$dirs = [];
		foreach ($this->parse_list_output('currentsong', $dirs, false) as $d) {
			$currentsong = $d;
		}
		$info = $temp_db->doNewPlaylistFile($currentsong);
		if (!$info['Time']) {
			logger::warn('PLAYER', "Track Time is not set, cannot store resume position");
			return;
		}
		$durationfraction = $temp_status['elapsed']/$info['Time'];
		$progresstostore = ($durationfraction > 0.05 && $durationfraction < 0.95) ? $temp_status['elapsed'] : 0;
		if ($info['type'] == 'audiobook') {
			logger::log('PLAYER', 'Storing Resume progress of',intval($progresstostore),'on an Audiobook');
			$info['attributes'] = [['attribute' => 'Bookmark', 'value' => [intval($progresstostore), 'Resume']]];
			$temp_db->sanitise_data($info);
			$temp_db->create_foundtracks();
			$temp_db->set($info);
			$this->db_updated = 'track';
		} else if ($info['type'] == 'podcast') {
			logger::log('PLAYER', 'Storing Resume progress of',intval($progresstostore),'on a Podcast');
			$temp_db = new poDatabase();
			$this->db_updated = $temp_db->setPlaybackProgress(intval($progresstostore), $info['file'], 'Resume');
		}
	}

	public function do_command_list($cmds) {
		$done = 0;
		$cmd_status = null;

		$this->check_set_resume_bookmark($cmds);

		if ($this->player_type != prefs::get_pref('collection_player')) {
			$this->translate_player_types($cmds);
		}
		if ($this->is_remote) {
			$this->translate_commands_for_remote($cmds);
		}

		// foreach ($cmds as $cmd) {
		// 	if (preg_match('/add "(youtube:video:.+)"/', $cmd, $matches)) {
		// 		if (prefs::$database === null) {
		// 			prefs::$database = new database();
		// 		}
		// 		$albumuri = prefs::$database->get_album_uri($matches[1]);
		// 		if ($albumuri) {
		// 			logger::log('PLAYER', 'Making Mopidy lookup album',$albumuri);
		// 			$this->send_command('find file "'.$albumuri.'"');
		// 		}
		// 	}
		// }

		$retries = 3;
		if (count($cmds) > 1) {
			do {
				$error = false;
				$this->send_command("command_list_begin");
				foreach ($cmds as $c) {
					logger::trace("MPD", "Command List:",$c);
					$l = strlen($c."\n");
					// Note. We don't use send_command because that closes and re-opens the connection
					// if it fails to fputs, and that loses our command list status, so we have to do our
					// own error handling here
					$b = fputs($this->connection, $c."\n");
					if ((!$b || $b < $l)) {
						logger::warn("MPD", "Command List Socket Write Error for",$c,"- Retrying");
						stream_socket_shutdown($this->connection, STREAM_SHUT_RDWR);
						$this->connection = false;
						usleep(500000);
						$this->open_mpd_connection();
						$retries--;
						$done = 0;
						$error = true;
						break;
					} else {
						$done++;
						// Command lists have a maximum length, 50 seems to be the default
						if ($done == 50) {
							$this->do_mpd_command("command_list_end", true);
							$this->send_command("command_list_begin");
							$done = 0;
						}
					}
				}
			} while ($retries > 0 && $error == true);
			$cmd_status = $this->do_mpd_command("command_list_end", true, false);
		} else if (count($cmds) == 1) {
			logger::debug("MPD", "Single Command :",$cmds[0]);
			$cmd_status = $this->do_mpd_command($cmds[0], true, false);
		}
		return $cmd_status;
	}

	public function parse_list_output($command, &$dirs, $domains) {
		// Generator Function for parsing MPD output for 'list...info', 'search ...' etc type commands
		// Returns MPD_FILE_MODEL

		logger::log("MPD", "MPD Parse",$command);

		$success = $this->send_command($command);
		$filedata = MPD_FILE_MODEL;
		$parts = true;
		if (is_array($domains) && count($domains) == 0) {
			$domains = false;
		}

		while($this->is_connected() && !feof($this->connection) && $parts) {
			$parts = $this->getline();
			if (is_array($parts)) {
				switch ($parts[0]) {
					case "directory":
						$dirs[] = trim($parts[1]);
						break;

					case "Last-Modified":
						if ($filedata['file'] != null) {
							// We don't want the Last-Modified stamps of the directories
							// to be used for the files.
							$filedata[$parts[0]] = $parts[1];
						}
						break;

					case 'file':
					case 'playlist':
						if ($filedata['file'] !== null) {
							$filedata['domain'] = getDomain($filedata['file']);
							if ($domains === false || in_array(getDomain($filedata['file']),$domains)) {
								if ($this->sanitize_data($filedata)) {
									yield $filedata;
								}
							}
						}
						$filedata = MPD_FILE_MODEL;
						if ($parts[0] == 'file')
							$filedata[$parts[0]] = $parts[1];
						break;

					case 'X-AlbumUri':
					case 'Title':
					case 'Album':
					case 'Comment':
						$filedata[$parts[0]] = $parts[1];
						break;

					default:
						if (in_array($parts[0], MPD_ARRAY_PARAMS)) {
							$filedata[$parts[0]] = array_unique(explode(';',$parts[1]));
						} else {
							$filedata[$parts[0]] = explode(';',$parts[1])[0];
						}
						break;
				}
			}
		}

		if ($filedata['file'] !== null) {
			$filedata['domain'] = getDomain($filedata['file']);
			if ($domains === false || in_array(getDomain($filedata['file']),$domains)) {
				if ($this->sanitize_data($filedata)) {
					yield $filedata;
				}
			}
		}
	}

	protected function sanitize_data(&$filedata) {
	 //   if (strpos($filedata['Title'], "[unplayable]") === 0) {
		// 	logger::debug("COLLECTION", "Ignoring unplayable track ".$filedata['file']);
		// 	return false;
		// }
		// if (strpos($filedata['Title'], "[loading]") === 0) {
		// 	logger::debug("COLLECTION", "Ignoring unloaded track ".$filedata['file']);
		// 	return false;
		// }
		$filedata['unmopfile'] = $this->unmopify_file($filedata);

		if ($filedata['Track'] == 0) {
			$filedata['Track'] = format_tracknum(basename(rawurldecode($filedata['file'])));
		} else {
			$filedata['Track'] = format_tracknum(ltrim($filedata['Track'], '0'));
		}

		// cue sheet link (mpd only). We're only doing CUE sheets, not M3U
		if ($filedata['X-AlbumUri'] === null && strtolower(pathinfo($filedata['playlist'], PATHINFO_EXTENSION)) == "cue") {
			$filedata['X-AlbumUri'] = $filedata['playlist'];
			logger::log("COLLECTION", "Found CUE sheet for album ".$filedata['Album']);
		}

		// Disc Number
		if ($filedata['Disc'] != null)
			$filedata['Disc'] = format_tracknum(ltrim($filedata['Disc'], '0'));

		if (prefs::get_pref('use_original_releasedate') && $filedata['OriginalDate'])
			$filedata['Date'] = $filedata['OriginalDate'];


		$filedata['year'] = getYear($filedata['Date']);

		return $this->player_specific_fixups($filedata);

	}

	protected function player_specific_fixups(&$filedata) {
		// This is here for transferplaylist
		switch($filedata['domain']) {
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
				$filedata['type'] = 'stream';
				break;

			default:
				$filedata['type'] = 'local';
				break;

		}

		return true;

	}

	private function unmopify_file(&$filedata) {
		if ($filedata['Pos'] !== null) {
			// Convert URIs for different player types to be appropriate for the collection
			// but only when we're getting the playlist
			if ($this->is_remote && $filedata['domain'] == 'file') {
				$filedata['file'] = $this->swap_file_for_local($filedata['file']);
				$filedata['domain'] = 'local';
			}
			if (prefs::get_pref('collection_player') == 'mopidy' && $this->player_type == 'mpd') {
				$filedata['file'] = $this->mpd_to_mopidy($filedata['file']);
			}
			if (prefs::get_pref('collection_player') == 'mpd' && $this->player_type == 'mopidy') {
				$filedata['file'] = $this->mopidy_to_mpd($filedata['file']);
			}
		}
		// eg local:track:some/uri/of/a/file
		// We want the path, not the domain or type
		// This is much faster than using a regexp
		$cock = explode(':', $filedata['file']);
		if (count($cock) > 1) {
			$file = array_pop($cock);
		} else {
			$file = $filedata['file'];
		}
		return $file;
	}

	private function album_from_path($p) {
		$a = rawurldecode(basename(dirname($p)));
		if ($a == ".") {
			$a = '';
		}
		return $a;
	}

	private function artist_from_path($p, $f) {
		$a = rawurldecode(basename(dirname(dirname($p))));
		if ($a == "." || $a == "" || $a == " & ") {
			$a = ucfirst(getDomain(urldecode($f)));
		}
		return $a;
	}

	protected function check_undefined_tags(&$filedata) {
		if ($filedata['Title'] == null) $filedata['Title'] = rawurldecode(basename($filedata['file']));
		if ($filedata['Album'] == null) $filedata['Album'] = $this->album_from_path($filedata['unmopfile']);
		if ($filedata['Artist'] == null) $filedata['Artist'] = array($this->artist_from_path($filedata['unmopfile'], $filedata['file']));
	}

	public function get_status() {
		return $this->do_mpd_command('status', true, false);
	}

	public function wait_for_state($expected_state) {
		if ($expected_state !== null) {
			logger::log('PLAYER', 'Waiting for state', $expected_state);
			$state = $this->get_status_value('state');
			$retries = 50;
			while ($retries > 0 && ($state === null || $state != $expected_state)) {
				usleep(100000);
				$retries--;
				$state = $this->get_status_value('state');
			}
		}
	}

	public function clear_error() {
		$this->send_command('clearerror');
	}

	public function get_current_song() {
		return $this->do_mpd_command('currentsong', true, false);
	}

	public function get_config() {
		if ($this->socket != '' && $this->player_type == 'mpd') {
			return $this->do_mpd_command('config', true, false);
		} else {
			return array();
		}
	}

	public function get_tagtypes() {
		return $this->do_mpd_command('tagtypes', true, false);
	}

	public function get_commands() {
		return $this->do_mpd_command('commands', true, false);
	}

	public function get_notcommands() {
		return $this->do_mpd_command('notcommands', true, false);
	}

	public function get_decoders() {
		return $this->do_mpd_command('decoders', true, false);
	}

	public function cancel_single_quietly() {
		$this->send_command('single 0');
	}

	public function get_idle_status() {
		// If there's a smart playlist running we need to respond to playlist changed commands
		// so we top up the tracklist when something is removed by a human
		$rp = prefs::get_radio_params();
		$command = ($rp['radiomode'] == '') ? 'idle player' : 'idle player playlist';
		return $this->do_mpd_command($command, true, false);
	}

	public function dummy_command() {
		return $this->do_mpd_command('', true, false);
	}

	public function get_playlist() {
		$dirs = array();
		foreach ($this->parse_list_output('playlistinfo', $dirs, false) as $filedata) {
			yield $filedata;
		}
	}

	public function get_uris_for_directory($path) {
		logger::log("PLAYER", "Getting Directory Items For",$path);
		$items = array();
		$parts = true;
		$lines = array();
		$this->send_command('lsinfo "'.format_for_mpd($path).'"');
		// We have to read in the entire response then go through it
		// because we only have the one connection to mpd so this function
		// is not strictly re-entrant and recursing doesn't work unless we do this.
		while(!feof($connection) && $parts) {
			$parts = $this->getline($connection);
			if ($parts === false) {
				logger::core("PLAYER", "Got OK or ACK from MPD");
			} else {
				$lines[] = $parts;
			}
		}
		foreach ($lines as $parts) {
			if (is_array($parts)) {
				$s = trim($parts[1]);
				if (substr($s,0,1) != ".") {
					switch ($parts[0]) {
						case "file":
							$items[] = $s;
							break;

					  case "directory":
							$items = array_merge($items, $this->get_uris_for_directory($s));
							break;
					}
				}
			}
		}
		return $items;
	}

	public function get_uri_handlers() {
		$handlers = $this->do_mpd_command('urlhandlers', true);
		if (is_array($handlers) && array_key_exists('handler', $handlers)) {
			return $handlers['handler'];
		} else {
			return array();
		}
	}

	public function get_outputs() {
		return $this->do_mpd_command('outputs', true);
	}

	public function get_stored_playlists($only_personal = false) {
		global $PLAYER_TYPE;
		$this->playlist_error = false;
		$retval = array();
		$playlists = $this->do_mpd_command('listplaylists', true, true);
		if (is_array($playlists) && array_key_exists('playlist', $playlists)) {
			$retval = $playlists['playlist'];
			usort($retval, function($a, $b) {
				if (strpos($a, '(by spotify)')) return -1;
				if (strpos($b, '(by spotify)')) return 1;
				return (strtolower($a) < strtolower($b)) ? -1 : 1;
			});
			if ($only_personal) {
				$retval = array_filter($retval, 'player::is_personal_playlist');
			}
		} else if (is_array($playlists) && array_key_exists('error', $playlists)) {
			// We frequently get an error getting stored playlists - especially from mopidy
			// This flag is set so that loadplaylists.php doesn't remove all our stored playlist
			// images in the event of that happening.
			$this->playlist_error = true;
		}
		return $retval;
	}

	public function get_stored_playlist_tracks($playlistname, $startpos) {
		$dirs = array();
		$count = 0;
		foreach ($this->parse_list_output('listplaylistinfo "'.$playlistname.'"', $dirs, false) as $filedata) {
			if ($count >= $startpos) {
				list($class, $url) = $this->get_checked_url($filedata['file']);
				yield array($class, $url, $filedata);
			}
			$count++;
		}
	}

	public function initialise_search() {
		$this->to_browse = array();
	}

	private function translate_commands_for_remote(&$cmds) {
		//
		// Re-check all add and playlistadd commands if we're using a Mopidy File Backend Remote
		//
		foreach ($cmds as $key => $cmd) {
			// add "local:track:
			// playlistadd "local:track:
			if (substr($cmd, 0, 17) == 'add "local:track:' ||
				substr($cmd, 0,25) == 'playlistadd "local:track:') {
				logger::mark("MOPIDY", "Translating tracks for Mopidy Remote");
				$cmds[$key] = $this->swap_local_for_file($cmd);
			}
		}
	}

	private function translate_player_types(&$cmds) {
		//
		// Experimental translation to and from MPD/Mopidy Local URIs
		//
		foreach ($cmds as $key => $cmd) {
			if (substr($cmd, 0, 4) == 'add ') {
				logger::mark("PLAYER", "Translating Track Uris from",prefs::get_pref('collection_player'),'to',$this->player_type);
				if (prefs::get_pref('collection_player') == 'mopidy') {
					$cmds[$key] = $this->mopidy_to_mpd($cmd);
				} else if (prefs::get_pref('collection_player') == 'mpd'){
					$file = trim(substr($cmd, 4), '" ');
					$cmds[$key] = 'add '.$this->mpd_to_mopidy($file);
				}
			}
		}
	}

	private function mopidy_to_mpd($file) {
		if (strpos($file, 'local:track') !== false) {
			// local:track could be anywhere in the string, as we might be translating
			// a command, not just a URI
			return rawurldecode(preg_replace('#local:track:#', '', $file));
		} else {
			return $file;
		}
	}

	private function mpd_to_mopidy($file) {
		if (substr($file, 0, 5) != 'http:' && substr($file, 0, 6) != 'https:') {
			return 'local:track:'.implode("/", array_map("rawurlencode", explode("/", $file)));
		} else {
			return $file;
		}
	}

	private function swap_local_for_file($string) {
		// url encode the album art directory
		$path = implode("/", array_map("rawurlencode", explode("/", prefs::get_pref('music_directory_albumart'))));
		logger::debug("MOPIDYREMOTE", "Replacing with",$path);
		return preg_replace('#local:track:#', 'file://'.$path.'/', $string);
	}

	private function swap_file_for_local($string) {
		$path = 'file://'.implode("/", array_map("rawurlencode", explode("/", prefs::get_pref('music_directory_albumart')))).'/';
		return preg_replace('#'.$path.'#', 'local:track:', $string);
	}

	private function probe_player_type() {
		$retval = null;
		if ($this->is_connected()) {
			$r = $this->do_mpd_command('tagtypes', true, true);
			if (is_array($r) && array_key_exists('tagtype', $r)) {
				if (in_array('X-AlbumUri', $r['tagtype'])) {
					logger::mark("MPDPLAYER", "Player Type Probe : tagtypes test says we're running Mopidy");
					$retval = "mopidy";
				} else {
					logger::mark("MPDPLAYER", "Player Type Probe : tagtypes test says we're running MPD");
					$retval = "mpd";
				}
			} else {
				logger::warn("MPDPLAYER", "WARNING! No output for 'tagtypes' - probably an old version of Mopidy. RompЯ may not function correctly");
				$retval =  "mopidy";
			}
			prefs::set_pref(['player_backend' => $retval]);
			set_include_path('player/'.prefs::get_pref('player_backend').PATH_SEPARATOR.get_include_path());
		}
		return $retval;
	}

	protected function getStreamFolder($url) {
		$f = dirname($url);
		if ($f == "." || $f == "") $f = $url;
		return $f;
	}

	protected function getDummyStation($url) {
		$f = getDomain($url);
		switch ($f) {
			case "http":
			case "https":
			case "mms":
			case "mmsh":
			case "mmst":
			case "mmsu":
			case "gopher":
			case "rtp":
			case "rtsp":
			case "rtmp":
			case "rtmpt":
			case "rtmps":
				return "Radio";
				break;

			default:
				return ucfirst($f);
				break;
		}
	}

	public function get_status_value($status) {
		$mpd_status = $this->do_command_list(['status']);
		return array_key_exists($status, $mpd_status) ? $mpd_status[$status] : null;
	}

	private function calculate_ramp($volume, $seconds) {
		// MPD/Mopidy will only accept integer values for volume.
		// But we want it to take a specific number of seconds so we need to
		// calculate a delay time between calls to setvol.
		// We also want to make sure that the sleep time isn't ridiculously short
		// so this may mean our volume steps are bigger than 1
		$inc = 1;
		while (($sleep = $seconds/$volume) < 0.1) {
			$inc = $inc * 2;
			$seconds = $seconds * 2;
		}
		// sleep() only accepts integers and usleep() may not work with very large numbers
		// hence we're going to use both.
		$sleep_seconds = floor($sleep);
		$sleep_microseconds = ($sleep - $sleep_seconds) * 1000000;
		return array($sleep_seconds, $sleep_microseconds, $inc);
	}

	// Don't call this if the player is stopped or paused, it waits for the play state.
	// There is a timeout in that function but even so...
	public function ramp_volume($start, $end, $seconds) {
		$start = intval($start);
		$end = intval($end);
		logger::log('PLAYER', 'Ramping volume from',$start,'to',$end,'over',$seconds,'seconds');
		if ($seconds == 0 || $start == $end) {
			$this->do_command_list(['setvol '.$end]);
			return;
		}
		list($seconds, $microseconds, $inc) = $this->calculate_ramp(abs($start - $end), $seconds);
		$inc = ($start > $end) ? (0 - $inc) : $inc;
		$this->wait_for_state('play');

		// We seem to have to do this. Very odd.
		if ($this->player_type == 'mpd')
			sleep(1);

		$this->do_command_list(['setvol '.$start]);

		while (($volume = $this->get_status_value('volume')) != $end) {
			$newvol = $volume + $inc;
			if ($inc < 0 && $newvol < $end) {
				$newvol = $end;
			} else if ($inc > 0 && $newvol > $end) {
				$newvol = $end;
			}
			$this->do_command_list(['setvol '.$newvol]);
			sleep($seconds);
			usleep($microseconds);
		}
	}

	public function rompr_commands_to_mpd($json) {

		// For this method, prefs::$database needs to be a music_loader()
		// This also calls methods from mopidy / mpd player, so it needs to be
		// called in the context of a new player() not a new base_mpd_player()

		$this->mpd_status = [];
		$this->moveallto = null;
		$this->current_playlist_length = 0;
		$this->do_resume_seek = false;
		$this->do_resume_seek_id = false;
		$this->playlist_movefrom = null;
		$this->playlist_moveto = null;
		$this->playlist_moving_within = null;
		$this->playlist_tracksadded = 0;
		$this->expected_state = null;

		$cmds = $this->translate_rompr_commands($json);

		//
		// If we added tracks to a STORED playlist, move them into the correct position
		//

		while ($this->playlist_tracksadded > 0 && $this->playlist_movefrom !== null && $this->playlist_moveto !== null) {
			$cmds[] = join_command_string(array('playlistmove', $this->playlist_moving_within, $this->playlist_movefrom, $this->playlist_moveto));
			$this->playlist_moveto++;
			$this->playlist_movefrom++;
			$this->playlist_trackadded--;
		}

		//
		// Send the command list to mpd
		//

		$cmd_status = $this->do_command_list($cmds);

		//
		// If we added tracks to the play queue, move them into position if we need to
		//

		if ($this->moveallto !== null) {
			logger::trace("MPD", "Moving Tracks into position");
			$temp_status = $this->get_status();
			$new_playlist_length = $temp_status['playlistlength'];
			$this->do_command_list(array(join_command_string(array('move', $this->current_playlist_length.':'.$new_playlist_length, $this->moveallto))));
		}

		//
		// Wait for the player to start playback if that's what it's supposed to be doing
		//

		$this->wait_for_state($this->expected_state);

		//
		// Work around mopidy play/seek command list bug
		//

		if ($this->do_resume_seek !== false) {
			$this->do_command_list(array(join_command_string(array('seek', $this->do_resume_seek[0], $this->do_resume_seek[1]))));
		}
		if ($this->do_resume_seek_id !== false) {
			$this->do_command_list(array(join_command_string(array('seekid', $this->do_resume_seek_id[0], $this->do_resume_seek_id[1]))));
		}

		//
		// Query mpd's status
		//

		$this->mpd_status = $this->get_status();
		$this->mpd_status['consume'] = $this->get_consume($this->mpd_status['consume']);

		//
		// If we got an error from the command list and NOT from 'status',
		// make sure we report the command list error back
		//

		if (is_array($cmd_status) && !array_key_exists('error', $this->mpd_status) && array_key_exists('error', $cmd_status)) {
			logger::warn("POSTCOMMAND", "Command List Error",$cmd_status['error']);
			$this->mpd_status = array_merge($this->mpd_status, $cmd_status);
		}

		//
		// Add current song and replay gain status to mpd_status
		// We use currentsong for streams. It is NOT merged with database data as the playlist data is
		// so should not be used for metadata at any other point except for filename.
		//

		if (array_key_exists('song', $this->mpd_status) && !array_key_exists('error', $this->mpd_status)) {
			$songinfo = $this->get_current_song();
			if (is_array($songinfo)) {
				$this->mpd_status = array_merge($this->mpd_status, $songinfo);
			}
		}

		$this->mpd_status = array_merge($this->mpd_status, $this->get_replay_gain_state());

		//
		// Clear any player error now we've caught it
		//

		if (array_key_exists('error', $this->mpd_status)) {
			logger::trace("MPD", "Clearing Player Error ".$this->mpd_status['error']);
			$this->clear_error();
		}

		//
		// Disable 'single' if we're stopped or paused (single is used for 'Stop After Current Track')
		//

		if (array_key_exists('single', $this->mpd_status) && $this->mpd_status['single'] == 1 && array_key_exists('state', $this->mpd_status) &&
				($this->mpd_status['state'] == "pause" || $this->mpd_status['state'] == "stop")) {
			logger::trace("MPD", "Cancelling Single Mode");
			$this->cancel_single_quietly();
			$this->mpd_status['single'] = 0;
		}

		//
		// Format any error message more nicely
		//

		if (array_key_exists('error', $this->mpd_status)) {
			$this->mpd_status['error'] = preg_replace('/ACK \[.*?\]\s*/','',$this->mpd_status['error']);
		}

		$this->mpd_status['db_updated'] = $this->db_updated;
		return $this->mpd_status;

	}

	private function translate_rompr_commands($json) {

		$cmds = array();

		if (!$json) return $cmds;

		foreach ($json as $cmd) {

			logger::debug("POSTCOMMAND", "RAW command : ".multi_implode($cmd, " "));

			switch ($cmd[0]) {
				case "addtoend":
					logger::log("POSTCOMMAND", "Addtoend ".$cmd[1]);
					$cmds = array_merge($cmds, prefs::$database->playAlbumFromTrack($cmd[1]));
					break;

				case 'playlisttoend':
					logger::log("POSTCOMMAND", "Playing playlist ".$cmd[1]." from position ".$cmd[2]." to end");
					foreach($this->get_stored_playlist_tracks($cmd[1], $cmd[2]) as list($class, $uri, $filedata)) {
						if ($class == 'clicktrack') {
							$cmds[] = 'add "'.$uri.'"';
						} else {
							$cmds[] = 'load "'.$uri.'"';
						}
					}
					break;

				case "additem":
					logger::log("POSTCOMMAND", "Adding Item ".$cmd[1]);
					$cmds = array_merge($cmds, prefs::$database->getItemsToAdd($cmd[1], null));
					break;

				case "loadstreamplaylist":
					require_once ("player/".$this->player_type."/streamplaylisthandler.php");
					$cmds = array_merge($cmds, internetPlaylist::load_internet_playlist($cmd[1], $cmd[2], $cmd[3]));
					break;

				case "addremoteplaylist":
					logger::log("POSTCOMMAND", "Remote Playlist URL is",$cmd[1]);
					// First, see if we can just 'load' the remote playlist. This is better with MPD
					// as it parses track names from the playlist
					if ($this->check_track_load_command($cmd[1]) == 'load') {
						logger::trace("POSTCOMMAND", "Loading remote playlist");
						$cmds[] = join_command_string(array('load', $cmd[1]));
					} else {
						// Always use the MPD version of the stream playlist handler, since that parses
						// all tracks (Mopidy's version doesn't because we use Mopidy's playlist parser instead).
						// Perversely, we need to use this because we can't use 'load' on a remote playlist with Mopidy,
						// and 'add' only adds the first track. As user remtote playlists can have multiple types of
						// thing in them, including streams, we need to 'add' every track - unless we're using mpd and
						// the 'track' is a playlist we need to load..... Crikey.
						logger::trace("POSTCOMMAND", "Adding remote playlist (track by track)");
						require_once ("player/mpd/streamplaylisthandler.php");
						$tracks = internetPlaylist::load_internet_playlist($cmd[1], '', '', true);
						foreach ($tracks as $track) {
							$cmd = $this->check_track_load_command($track['TrackUri']);
							$cmds[] = join_command_string(array($cmd, $track['TrackUri']));
						}
					}
					break;

				case "rename":
					$oldimage = new albumImage(array('artist' => 'PLAYLIST', 'album' => $cmd[1]));
					$oldimage->change_name($cmd[2]);
					$cmds[] = join_command_string($cmd);
					break;

				case "playlistadd":
					if (preg_match('/^(a|b|r|t|y|u|z)(.*?)(\d+|root)/', $cmd[2])) {
						// Add a whole album/artist
						$lengthnow = count($cmds);
						$cmds = array_merge($cmds, prefs::$database->getItemsToAdd($cmd[2], $cmd[0].' "'.format_for_mpd($cmd[1]).'"'));
						$this->check_playlist_add_move($cmd, (count($cmds) - $lengthnow));
					} else {
						logger::trace('POSTCOMMAND',$cmd[0], $cmd[1], $cmd[2]);
						$cmds[] = join_command_string(array($cmd[0], $cmd[1], $cmd[2]));
						$this->check_playlist_add_move($cmd, 1);
					}
					break;

				case "playlistmove":
					// Expects playlistname, then arrays of from and to
					foreach ($cmd[2] as $i => $v) {
						$cmds[] = join_command_string(array($cmd[0], $cmd[1], $v, $cmd[3][$i]));
					}
					break;

				case "moveallto":
					$this->moveallto = $cmd[1];
					$temp_status = $this->get_status();
					if (array_key_exists('playlistlength', $temp_status)) {
						$this->current_playlist_length = $temp_status['playlistlength'];
					}
					break;

				case "playlistadddir":
					$thing = array('searchaddpl',$cmd[1],'base',$cmd[2]);
					$cmds[] = join_command_string($thing);
					break;

				case "resume":
					logger::log("POSTCOMMAND", "Adding Track ".$cmd[1]);
					logger::log("POSTCOMMAND", "  .. and seeking position ".$cmd[3]." to ".$cmd[2]);
					if ($cmd[4] == 'yes') {
						logger::log('POSTCOMMAND', "  .. CD player mode was also requested");
						$cmds = array_merge($cmds, prefs::$database->playAlbumFromTrack($cmd[1]));
					} else {
						$cmds[] = join_command_string(array('add', $cmd[1]));
					}
					$cmds[] = join_command_string(array('play', $cmd[3]));
					$this->expected_state = 'play';
					$this->do_resume_seek = array($cmd[3], $cmd[2]);
					break;

				case "seekpodcast":
					$this->expected_state = 'play';
					$this->do_resume_seek_id = array($cmd[1], $cmd[2]);
					break;

				case 'save':
				case 'rm':
				case "load":
					$cmds[] = join_command_string($cmd);
					break;

				case "clear":
					$cmds[] = join_command_string($cmd);
					break;

				case "consume":
					if ($this->toggle_consume($cmd[1])) {
						$cmds[] = join_command_string($cmd);
					}
					break;

				case "play":
				case "playid":
					$this->expected_state = 'play';
					// Fall through
				default:
					$cmds[] = join_command_string($cmd);
					break;
			}
		}

		return $cmds;
	}

	private function check_playlist_add_move($cmd, $incvalue) {
		if ($cmd[3] == 0 || $cmd[3]) {
			if ($this->playlist_moving_within === null) $this->playlist_moving_within = $cmd[1];
			if ($this->playlist_movefrom === null) $this->playlist_movefrom = $cmd[4];
			if ($this->playlist_moveto === null) $this->playlist_moveto = $cmd[3];
			$this->playlist_tracksadded += $incvalue;
		}
	}

	public function prepare_smartradio() {
		$status = $this->get_status();
		// This should be in a state where it can be passed direct to do_command_list()
		// via controller.js - whichever browser is used to stop the radio will set these back
		prefs::set_radio_params(
			[
				'radioconsume' => [
					['consume', $this->get_consume($status['consume'])],
					['repeat', $status['repeat']],
					['random', $status['random']]
				],
				'toptracks_current' => 1,
				'toptracks_total' => 1
			]
		);
		$this->do_command_list(['stop']);
		$this->do_command_list(['clear']);
		$this->do_command_list(['repeat 0']);
		$this->do_command_list(['random 0']);
		$this->force_consume_state(1);
		$this->get_smartradio_database();
		prefs::$database->preparePlaylist();
	}

	private function get_smartradio_database() {
		$rp = prefs::get_radio_params();
		$populator = smart_radio::RADIO_CLASSES[$rp['radiomode']];
		prefs::$database = new $populator([
			'doing_search' => true,
			'trackbytrack' => false
		]);
	}

	//
	// romonitor functions using Idle subsystem
	//

	public function idle_system_loop($radiomode, $radioparam) {
		$this->radiomode = $radiomode;
		$this->radioparam = $radioparam;
		$this->get_current_song_status();
		if ($this->radiomode !== false) {
			$this->check_radiomode();
		}
		while (true) {
			while ($this->is_connected()) {
				if ($this->is_error())
					break;

				// Sit in this loop until the player state changes
				$timedout = false;
				while (true) {
					if ($timedout) {
						$idle_status = $this->dummy_command();
					} else {
						$idle_status = $this->get_idle_status();
					}
					if (array_key_exists('error', $idle_status) && $idle_status['error'] == 'Timed Out') {
						logger::mark(prefs::currenthost(), "Idle command timed out, looping back");
						$timedout = true;
						continue;
					} else if (array_key_exists('error', $idle_status)) {
						// Serious error. Try to reconnect.
						break 2;
					} else {
						// Idle Subsystem has sent us something
						break;
					}
				}

				$this->check_idle_status($idle_status);

			}
			$this->close_mpd_connection();
			logger::mark(prefs::currenthost(), "** Player connection dropped - retrying in 10 seconds **");
			sleep(10);
			$this->open_mpd_connection();
		}
		logger::error('ROMONITOR', 'How did we get here?');
	}

	private function get_currentsong_as_playlist() {
		$dirs = array();
		prefs::$database = new metaDatabase();
		foreach ($this->parse_list_output('currentsong', $dirs, false) as $filedata) {
			$this->current_song = prefs::$database->doNewPlaylistFile($filedata);
		}
		// Although doNewPlaylistFile does return a Playcount, it only does so for non-Hidden tracks
		// and we don't really want to be messing around with get_extra_track_info() because that's
		// used when doing a search and creating the tracklist and the way we need to widen that query to work here
		// really doesn't look good.
		prefs::$database->sanitise_data($this->current_song);
		$this->current_song['Playcount'] = prefs::$database->get($this->current_song, 'Playcount');
		prefs::$database->close_database();
		$this->current_song['readtime'] = time();
	}

	private function is_error() {
		$status = $this->get_status();
		return (array_key_exists('error', $status));
	}

	private function get_current_song_status() {
		$myid = (array_key_exists('songid', $this->current_status)) ? $this->current_status['songid'] : null;
		$this->current_status = $this->get_status();
		$this->current_song = [];
		// NOTE When Stopped, Mopidy may return a songid but MPD never will. Neither return an elapsed.
		if (array_key_exists('songid', $this->current_status) &&
			array_key_exists('elapsed', $this->current_status)
		) {
			logger::debug(prefs::currenthost(), '-----------------------------------------------');
			logger::log(prefs::currenthost(), 'Status : Songid is',$this->current_status['songid'],'Elapsed is',$this->current_status['elapsed']);

			// $filedata = $this->get_currentsong_as_playlist();
			$this->get_currentsong_as_playlist();

			logger::mark(prefs::currenthost(), "Status : Duration is",$this->current_song['Time'],"Current Playcount is",$this->current_song['Playcount']);

			// Prevent multiple calls to updatenowplaying in the case where we keep coming
			// back here during playing the same track.
			if ($this->current_status['songid'] != $myid && $this->current_status['state'] == 'play' && $this->current_song['Time'] > 0)
				$this->lastfm_update_nowplaying();
		}
	}

	private function check_idle_status($idle_status) {
		logger::core(prefs::currenthost(), 'Idle Status is',print_r($idle_status, true));
		$status = $this->get_status();

		if (array_key_exists('changed', $idle_status)) {
			logger::mark(prefs::currenthost(),"Player State Has Changed from",$this->current_status['state'],"to", $status['state']);
			// So we only get here if we were playing a song before we sent the idle command
			if ($this->radiomode === false) {
				if ($status['state'] != 'pause' && array_key_exists('Time', $this->current_song)) {
					if ($status['state'] == 'stop') {
						// Ensure, if we go from paused to stop, that we use only the value of 'elapsed' to calculate how much we played.
						if ($this->current_status['state'] == 'pause')
							$this->current_song['readtime'] = time();

						$this->check_playcount_increment();
					} else if ($status['state'] == 'play') {
						if ($this->current_status['state'] == 'pause' && $this->current_status['songid'] == $status['songid']) {
							logger::log(prefs::currenthost(), 'Song is being restarted after being paused');
						} else if ($this->current_status['songid'] == $status['songid']) {
							logger::log(prefs::currenthost(), 'Same track being played. Must have been restarted or seeked');
							$this->check_playcount_increment();
						} else {
							$this->check_playcount_increment();
						}
					}
				}

				prefs::load();
				if ($this->get_consume(0) && array_key_exists('songid', $this->current_status)) {
					if (
						($status['state'] == 'stop' && $this->current_status['state'] !== 'stop') ||
						($status['songid'] != $this->current_status['songid'])
					) {
						logger::log(prefs::currenthost(), 'Consuming track ID',$this->current_status['songid']);
						$this->do_command_list(['deleteid "'.$this->current_status['songid'].'"']);
					}
				}
			} else {
				$this->check_radiomode();
			}

		}
		$this->get_current_song_status();
	}

	private function check_playcount_increment() {
		$elapsed = time() - $this->current_song['readtime'] + $this->current_status['elapsed'];
		$fraction_played = ($this->current_song['Time'] == 0) ? 0 : $elapsed / $this->current_song['Time'];
		if ($fraction_played > 0.95) {
			logger::mark(prefs::currenthost(), "Played more than 95% of song. Incrementing playcount");
			prefs::$database = new metaDatabase();
			$this->current_song['attributes'] = [['attribute' => 'Playcount', 'value' => $this->current_song['Playcount']+1]];
			prefs::$database->inc($this->current_song);

			if ($this->current_song['type'] == 'podcast') {
				prefs::$database->close_database();
				logger::mark(prefs::currenthost(), "Marking podcast episode as listened");
				prefs::$database = new poDatabase();
				prefs::$database->markAsListened($this->current_song['file']);
			}
			prefs::$database->close_database();

		}

		prefs::load();
		if (($fraction_played*100) > prefs::get_pref('scrobblepercent'))
			$this->scrobble_to_lastfm();
	}

	private function lastfm_update_nowplaying() {
		prefs::load();
		if (prefs::get_pref('lastfm_scrobbling') && prefs::get_pref('lastfm_session_key') != '') {
			logger::mark(prefs::currenthost(), 'Updating Last.FM Nowplaying');
			$options = array(
				'track' => $this->current_song['Title'],
				'artist' => $this->current_song['trackartist'],
				'album' => $this->current_song['Album']
			);
			lastfm::update_nowplaying($options, false);
		}
	}

	private function scrobble_to_lastfm() {
		if (prefs::get_pref('lastfm_scrobbling') && prefs::get_pref('lastfm_session_key') != '') {
			logger::mark(prefs::currenthost(), 'Scrobbling');
			$options = array(
				'timestamp' => time() - $this->current_song['Time'],
				'track' => $this->current_song['Title'],
				'artist' => $this->current_song['trackartist'],
				'album' => $this->current_song['Album']
			);
			if ($this->current_song['albumartist'] &&
				strtolower($this->current_song['albumartist']) != strtolower($this->current_song['trackartist'])) {
				$options['albumArtist'] = $this->current_song['albumartist'];
			}
			lastfm::scrobble($options, false);
		}
	}

	public function check_radiomode() {
		prefs::load();
		$rp = prefs::get_radio_params();

		if ($rp['radiomode'] != $this->radiomode || $rp['radioparam'] != $this->radioparam)
			exit(0);

		// Need to recheck the playlist length, because $this->current_status *might* have the value
		// from *before* the last track was consumed. Plus, when we initially call in to set the radio
		// up, $this->current_status isn't initialised anyway.
		$playlistlength = $this->get_status_value('playlistlength');

		logger::trace(prefs::currenthost(), 'Radio Params Are',$rp['radiomode'], $rp['radioparam'],'Playlist Length is',$playlistlength);

		if ($playlistlength >= prefs::get_pref('smartradio_chunksize'))
			return;

		$tracksneeded = prefs::get_pref('smartradio_chunksize') - $playlistlength;
		logger::trace(prefs::currenthost(), "Adding",$tracksneeded,"tracks from",$rp['radiomode'],$rp['radioparam']);

		$this->get_smartradio_database();
		$this->do_smartradio($tracksneeded);
	}

	public function do_smartradio($tracksneeded) {
		$rp = prefs::get_radio_params();
		$result = prefs::$database->doPlaylist($rp['radioparam'], $tracksneeded, $this);
		prefs::$database->close_database();
		if (!$result) {
			logger::log('SMARTRADIO', 'Found no tracks. Stopping');
			prefs::set_radio_params([
				'radiomode' => '',
				'radioparam' => ''
			]);
			$this->do_command_list($rp['radioconsume']);
			return false;
		}
		return true;
	}
}
?>