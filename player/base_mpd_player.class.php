<?php

class base_mpd_player {

	protected $connection;
	private $ip;
	private $port;
	private $socket;
	private $password;
	protected $player_type;
	private $is_remote;
	public $playlist_error;
	private $debug_id;
	public $to_browse;
	private $mpd_version = null;

	public function __construct($ip = null, $port = null, $socket = null, $password = null, $player_type = null, $is_remote = null) {
		$this->debug_id = microtime();
		if ($ip !== null) {
			$this->ip = $ip;
		} else {
			$this->ip = prefs::$prefs['multihosts'][prefs::$prefs['currenthost']]['host'];
		}
		if ($port !== null) {
			$this->port = $port;
		} else {
			$this->port = prefs::$prefs['multihosts'][prefs::$prefs['currenthost']]['port'];
		}
		if ($socket !== null) {
			$this->socket = $socket;
		} else {
			$this->socket = prefs::$prefs['multihosts'][prefs::$prefs['currenthost']]['socket'];
		}
		if ($password !== null) {
			$this->password = $password;
		} else {
			$this->password = prefs::$prefs['multihosts'][prefs::$prefs['currenthost']]['password'];
		}
		if ($is_remote !== null) {
			$this->is_remote = $is_remote;
		} else {
			if (array_key_exists('mopidy_remote', prefs::$prefs['multihosts'][prefs::$prefs['currenthost']])) {
				$this->is_remote = prefs::$prefs['multihosts'][prefs::$prefs['currenthost']]['mopidy_remote'];
			} else {
				// Catch the case where we haven't yet upgraded the player defs
				$this->is_remote = false;
			}
		}
		logger::core("MPDPLAYER", "Creating Player for",$this->ip.':'.$this->port);
		$this->open_mpd_connection();
		if ($player_type !== null) {
			$this->player_type = $player_type;
		} else {
			if (array_key_exists('player_backend', prefs::$prefs) && (prefs::$prefs['player_backend'] == 'mpd' || prefs::$prefs['player_backend'] == 'mopidy')) {
				$this->player_type = prefs::$prefs['player_backend'];
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
		}
		logger::log('MPDPLAYER', 'Version number',$version,'is > MPD version',$matches[1]);
		return false;
	}

	public function open_mpd_connection() {

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
			$b = fputs($this->connection, $command."\n");
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
			if ($command == 'status' || $command == 'currentsong' || $command == 'replay_gain_status') {
				logger::core("MPD", "MPD Command",$command);
			} else {
				logger::trace("MPD", "MPD Command",$command);
			}
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
								$retarr['binarydata'] .= $data;
							}
							fgets($this->connection); // for the trailing newline
						}
					}
				}
			} else {
				logger::error("MPD", "Failure to fput command",$command);
				$retarr['error'] = "There was an error communicating with ".ucfirst($this->player_type)."! (could not write to socket)";
			}
		}
		return $retarr;
	}

	public function do_command_list($cmds) {
		$done = 0;
		$cmd_status = null;

		if ($this->player_type != prefs::$prefs['collection_player']) {
			$this->translate_player_types($cmds);
		}
		if ($this->is_remote) {
			$this->translate_commands_for_remote($cmds);
		}
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

		while(  $this->is_connected() && !feof($this->connection) && $parts) {
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
						if ($filedata['file'] !== null) {
							$filedata['domain'] = getDomain($filedata['file']);
							if ($domains === false || in_array(getDomain($filedata['file']),$domains)) {
								if ($this->sanitize_data($filedata)) {
									yield $filedata;
								}
							}
						}
						$filedata = MPD_FILE_MODEL;
						$filedata[$parts[0]] = $parts[1];
						break;

					case 'X-AlbumUri':
					case 'Title':
					case 'Album':
					case 'Comment':
						// Mopidy-beets is using SEMICOLONS in its URI schemes.
						// Surely a typo, but we need to work around it by not splitting the string
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
	   if (strpos($filedata['Title'], "[unplayable]") === 0) {
			logger::debug("COLLECTION", "Ignoring unplayable track ".$filedata['file']);
			return false;
		}
		if (strpos($filedata['Title'], "[loading]") === 0) {
			logger::debug("COLLECTION", "Ignoring unloaded track ".$filedata['file']);
			return false;
		}
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


		if (prefs::$prefs['use_original_releasedate'] && $filedata['OriginalDate'])
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
			if (prefs::$prefs['collection_player'] == 'mopidy' && $this->player_type == 'mpd') {
				$filedata['file'] = $this->mpd_to_mopidy($filedata['file']);
			}
			if (prefs::$prefs['collection_player'] == 'mpd' && $this->player_type == 'mopidy') {
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
			$status = $this->get_status();
			$retries = 50;
			while ($retries > 0 && array_key_exists('state', $status) && $status['state'] != $expected_state) {
				usleep(100000);
				$retries--;
				$status = $this->get_status();
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
		return $this->do_mpd_command('idle player', true, false);
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

	public function get_currentsong_as_playlist() {
		$dirs = array();
		$retval = array();
		foreach ($this->parse_list_output('currentsong', $dirs, false) as $filedata) {
			$retval = $filedata;
		}
		return $retval;
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
				logger::mark("PLAYER", "Translating Track Uris from",prefs::$prefs['collection_player'],'to',$this->player_type);
				if (prefs::$prefs['collection_player']== 'mopidy') {
					$cmds[$key] = $this->mopidy_to_mpd($cmd);
				} else if (prefs::$prefs['collection_player']== 'mpd'){
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
		$path = implode("/", array_map("rawurlencode", explode("/", prefs::$prefs['music_directory_albumart'])));
		logger::debug("MOPIDYREMOTE", "Replacing with",$path);
		return preg_replace('#local:track:#', 'file://'.$path.'/', $string);
	}

	private function swap_file_for_local($string) {
		$path = 'file://'.implode("/", array_map("rawurlencode", explode("/", prefs::$prefs['music_directory_albumart']))).'/';
		return preg_replace('#'.$path.'#', 'local:track:', $string);
	}

	private function probe_player_type() {
		$retval = false;
		if ($this->is_connected()) {
			logger::mark("MPDPLAYER", "Probing Player Type....");
			$r = $this->do_mpd_command('tagtypes', true, true);
			if (is_array($r) && array_key_exists('tagtype', $r)) {
				if (in_array('X-AlbumUri', $r['tagtype'])) {
					logger::mark("MPDPLAYER", "    ....tagtypes test says we're running Mopidy. Setting cookie");
					$retval = "mopidy";
				} else {
					logger::mark("MPDPLAYER", "    ....tagtypes test says we're running MPD. Setting cookie");
					$retval = "mpd";
				}
			} else {
				logger::warn("MPDPLAYER", "WARNING! No output for 'tagtypes' - probably an old version of Mopidy. RompЯ may not function correctly");
				$retval =  "mopidy";
			}
			setcookie('player_backend', $retval, ['expires' => time()+365*24*60*60*10, 'path' => '/', 'SameSite' => 'Lax']);
			prefs::$prefs['player_backend'] = $retval;
			set_include_path('player/'.prefs::$prefs['player_backend'].PATH_SEPARATOR.get_include_path());
		}
		return $retval;
	}

	public function readpicture($uri) {
		$offset = 0;
		$size = null;
		$handle = null;
		$filename = '';
		$retries = 3;
		logger::log('READPICTURE', 'Reading image from file',$uri);
		if ($this->check_mpd_version('0.22.4')) {
			$this->do_mpd_command('binarylimit 1048576');
		}
		while (($size === null || $size > 0) && $retries > 0) {
			logger::log('MPDPLAYER', '  Reading at offset',$offset);
			$result = $this->do_mpd_command('readpicture "'.$uri.'" '.$offset, true);
			if (is_array($result) && array_key_exists('binary', $result)) {
				if ($size === null) {
					$size = $result['size'];
					logger::log('MPDPLAYER', '    Size is',$size);
					$filename = 'prefs/temp/'.md5($uri);
					$handle = fopen($filename, 'w');
				}
				if ($result['binary'] == strlen($result['binarydata'])) {
					fwrite($handle, $result['binarydata']);
					$size -= $result['binary'];
					$offset += $result['binary'];
					logger::log('MPDPLAYER', '    Remaining', $size);
				} else {
					logger::log('MPDPLAYER', '    Expected', $result['binary'], 'bytes but only got', strlen($result['binarydata']));
					$retries--;
				}
			} else {
				logger::log('READPICTURE', '    No binary data in response from MPD');
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

	//
	// Mopidy-HTTP functions
	//

	// Strictly speaking these belong in mopidy/player.php
	// But at places where they're needed that would entain sometimes creating two players
	// (one of these and one mopidy) which is inefficient and slow, so they're here
	// If we're really playing the pedantic game, just rename this class so these functions fit.

	private function mopidy_http_request($port, $data) {
		if (prefs::$prefs['player_backend'] == 'mopidy') {
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
		} else {
			return false;
		}
	}

	public function probe_http_api() {
		logger::log('MOPIDYHTTP', 'Probing HTTP API');
		$result = $this->mopidy_http_request(
			$this->ip.':'.prefs::$prefs['http_port_for_mopidy'],
			array(
				'method' => 'core.get_version'
			)
		);
		if ($result !== false) {
			logger::log('MOPIDYHTTP', 'Connected to Mopidy HTTP API Successfully');
			$http_server = nice_server_address($this->ip);
			prefs::$prefs['mopidy_http_port'] = $http_server.':'.prefs::$prefs['http_port_for_mopidy'];
		} else {
			logger::log('MOPIDYHTTP', 'Mopidy HTTP API Not Available');
			prefs::$prefs['mopidy_http_port'] = false;
		}
	}

	public function find_album_image($uri) {
		$retval = '';
		$result = $this->mopidy_http_request(
			prefs::$prefs['mopidy_http_port'],
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
			$retval = 'http://'.prefs::$prefs['mopidy_http_port'].$retval;
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
}
?>