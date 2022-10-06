<?php

class prefs {

	private const LOCKFILE = 'prefs/prefs.lock';

	public static $database = null;

	const DEFAULT_PLAYER = [
		'host' => 'localhost',
		'port' => '6600',
		'password' => '',
		'socket' => '',
		'mopidy_remote' => false,
		'do_consume' => false,
		'websocket' => false,
		'radioparams' => [
			"radiomode" => "",
			"radioparam" => "",
			"radioconsume" => [],
			"radiodomains" => ['local', 'spotify', 'ytmusic'],
			"toptracks_current" => 1,
			"toptracks_total" => 1
		]
	];

	// These are the keys from the above array that we check
	// to see if a player definition has changed
	const PLAYER_CONNECTION_PARAMS = [
		'HOST' => 'host',
		'PORT' => 'port',
		'PASSWORD' => 'password',
		'SOCKET' => 'socket',
		'REMOTE' => 'mopidy_remote'
	];

	private static $prefs = [];

	const BACKEND_PREFS = [
		// Things that only make sense as backend options, not per-user options
		"mysql_host" => "localhost",
		"mysql_database" => "romprdb",
		"mysql_user" => "rompr",
		"mysql_password" => "romprdbpass",
		"mysql_port" => "3306",
		"music_directory_albumart" => "",
		"proxy_host" => "",
		"proxy_user" => "",
		"proxy_password" => "",
		"sortbycomposer" => false,
		"composergenre" => false,
		"composergenrename" => array("Classical"),
		"preferlocalfiles" => false,
		"mopidy_collection_folders" => array("Spotify Playlists","Local media","SoundCloud/Liked"),
		"lastfm_country_code" => "GB",
		"country_userset" => false,
		"debug_enabled" => 0,
		"custom_logfile" => "",
		"cleanalbumimages" => true,
		"do_not_show_prefs" => false,
		// This option for plugin debugging ONLY
		"load_plugins_at_loadtime" => false,
		"beets_server_location" => "",
		'dev_mode' => false,
		'live_mode' => false,
		'use_mopidy_scan' => false,
		'collection_load_timeout' => 3600000,
		"smartradio_chunksize" => 5,
		"linkchecker_nextrun" => 0,
		"link_checker_frequency" => 604800,
		"link_checker_is_running" => false,
		"audiobook_directory" => '',
		"collection_player" => null,
		"snapcast_server" => '',
		"snapcast_port" => '1705',
		"snapcast_http" => '1780',
		"http_port_for_mopidy" => "6680",
		"mpd_websocket_port" => "",
		"multihosts" => [
			'Default' => self::DEFAULT_PLAYER
		],
		'old_style_sql' => false,
		'auto_audiobook' => array(),
		'backend_version' => '0',
		"spotify_mark_unplayable" => false,
		"spotify_mark_playable" => false,
		// Need these next two so the player defs can be updated
		"consume_workaround" => false,
		"we_do_consume" => false,

		'interface_language' => null,
		'collection_type' => null,
		'spotify_token' => null,
		'spotify_token_expires' => null,

		// Things that could be set on a per-user basis but need to be known by the backend
		"displaycomposer" => true,
		"artistsatstart" => array("Various Artists","Soundtracks"),
		"nosortprefixes" => array("The"),
		"sync_lastfm_playcounts" => false,
		"sync_lastfm_at_start" => false,
		"next_lastfm_synctime" => 0,
		"lastfm_sync_frequency" => 86400,
		"lfm_importer_start_offset" => 0,
		"lfm_importer_last_import" => 0,
		"bing_api_key" => '',
		"hide_master_volume" => false,
		"alarm_ramptime" => 30,
		"alarm_snoozetime" => 8,
		"communityradioorderby" => 'name',

		"default_podcast_display_mode" => DISPLAYMODE_ALL,
		"default_podcast_refresh_mode" => REFRESHOPTION_MONTHLY,
		"default_podcast_sort_mode" => SORTMODE_NEWESTFIRST,
		"podcast_mark_new_as_unlistened" => false,
		"podcast_sort_levels" => 4,
		"podcast_sort_0" => 'Title',
		"podcast_sort_1" => 'Artist',
		"podcast_sort_2" => 'Category',
		"podcast_sort_3" => 'new',
		"lastversionchecked" => '1.00',
		"lastversionchecktime" => 0,
		'use_original_releasedate' => false,
		"chartoption" => 0,

		// These are currently saved in the backend, as the most likely scenario is one user
		// with multiple browsers. But what if it's multiple users?
		"lastfm_user" => "",
		"lastfm_session_key" => "",
		"autotagname" => "",
		"lastfm_logged_in" => false,
		"lastfm_scrobbling" => false,
		"scrobblepercent" => 50
	];

	public const BROWSER_PREFS = [

		// All of these are saved in the browser, so these are only defaults
		"tradsearch" => false,
		"lastfm_autocorrect" => false,
		"sourceshidden" => false,
		"playlisthidden" => false,
		"infosource" => "lastfm",
		"sourceswidthpercent" => 25,
		"playlistwidthpercent" => 25,
		"downloadart" => true,
		"chooser" => "albumlist",
		"hide_albumlist" => false,
		"hide_filelist" => false,
		"hide_radiolist" => false,
		"hide_podcastslist" => false,
		"hide_playlistslist" => false,
		"hide_audiobooklist" => false,
		"hide_searcher" => false,
		"hide_pluginplaylistslist" => false,
		"hidebrowser" => false,
		"shownupdatewindow" => '',
		"scrolltocurrent" => false,
		"lastfmlang" => "interface",
		"synctags" => false,
		"synclovevalue" => "0",
		"theme" => "Numismatist.css",
		"icontheme" => "Bobalophagus-Dark",
		"coversize" => 64,
		"fontsize" => 11,
		"fontfamily" => "Nunito.css",
		"displayresultsas" => "collection",
		'crossfade_duration' => 5,

		"newradiocountry" => "countries/GB",
		"updateeverytime" => false,
		"fullbiobydefault" => true,
		"mopidy_search_domains" => array("local", "spotify"),
		"outputsvisible" => false,
		"wheelscrollspeed" => "150",
		"displayremainingtime" => true,
		"cdplayermode" => false,
		"auto_discovembobulate" => false,

		"sleeptime" => 30,
		"sleepon" => false,
		"sortwishlistby" => 'artist',
		"player_in_titlebar" => false,

		"use_albumart_in_playlist" => true,
		"bgimgparms" => ['dummy' => 'baby'],
		'playlistbuttons_isopen' => false,
		'collectionbuttons_isopen' => false,
		'advsearchoptions_isopen' => false,
		'podcastbuttons_isopen' => false,
		"somafm_quality" => 'highest_available_quality'

	];

	// Prefs that should not be exposed to the browser for security reasons
	private const PRIVATE_PREFS = [
		'mysql_database',
		'mysql_host',
		'mysql_password',
		'mysql_port',
		'mysql_user',
		'proxy_host',
		'proxy_password',
		'proxy_user',
		'lastfm_session_key',
		'spotify_token',
		'spotify_token_expires',
		'bing_api_key'
	];

	private const COOKIEPREFS = [
		'currenthost' => 'Default',
		'player_backend' => null,
		"browser_id" => null,

		'clickmode' => 'double',
		'skin' => null,
		"sortbydate" => false,
		"notvabydate" => false,
		"collectionrange" => ADDED_ALL_TIME,
		"sortcollectionby" => 'artist',
		"sortresultsby" => 'sameas',
		"actuallysortresultsby" => 'artist',

		// These are prefs that are set by daemon processes and are only of interest to those processes.
		// We don't want to save them or set them as Cookies but we do want them restored after a load().
		// set_pref will add them to session_prefs because they're in ths array but it won't set a Cookie
		// so long as IS_ROMINTOR is defined.
		'alarmindex' => null,
		'sleeptime' => null,
		'snooze' => null
	];

	// Anything that is set via set_pref that is a cookie will be added to this array.
	// This makes load() restore their values when we're running from a daemon process
	// the does not support cookies.
	private static $session_prefs = [ ];


	// Load the prefs. This function ONLY loads the prefs that are required by the backend.
	// It starts with BACKEND_PREFS,
	// then replaces those values with anything read in from prefs.var
	// then adds in all the values from Cookies, using the defaults if not set
	// then replaces those values with anything stored in session_prefs
	public static function load() {

		// Can't set a value in a constant using a function, so it has to be done here.
		$cannot_init = ["last_lastfm_synctime" => time()];
		$sp = self::load_prefs_file('prefs/prefs.var');
		// Old prefs files might have values we've removed. This removes those values
		$sp = array_intersect_key($sp, self::BACKEND_PREFS);

		// Do this here rather than after building $prefs so we can use it right away.
		// Slightly messy but I want the logging in the Cookie part
		$loglevel = array_key_exists('debug_enabled', $sp) ? $sp['debug_enabled'] : self::BACKEND_PREFS['debug_enabled'];
		$log_file = array_key_exists('custom_logfile', $sp) ? $sp['custom_logfile'] : self::BACKEND_PREFS['custom_logfile'];
		logger::setLevel($loglevel);
		logger::setOutfile($log_file);

		$cp = [];
		foreach (self::COOKIEPREFS as $cookie => $default) {
			if (array_key_exists($cookie, $_COOKIE)) {
				$cookie_val = $_COOKIE[$cookie];
				if ($cookie_val == '') {
					// The frontend sesms to be able to set cookies to '' but not expire them
					setcookie($cookie, '', ['expires' => 1, 'path' => '/', 'SameSite' => 'Lax']);
					$cp[$cookie] = $default;
					logger::core('COOKIEPREFS',"Pref",$cookie,"is expired and set to default of",$default);
				} else {
					if ($cookie_val === 'false') { $cookie_val = false; }
					if ($cookie_val === 'true') { $cookie_val = true; }
					$cp[$cookie] = $cookie_val;
					logger::core('COOKIEPREFS',"Pref",$cookie,"is set by Cookie - Value :",$cookie_val);
				}
			} else {
				$cp[$cookie] = $default;
			}
		}

		self::$prefs = array_replace(self::BACKEND_PREFS, $cannot_init, $sp, $cp, self::$session_prefs);

	}

	public static function load_prefs_file($filename) {
		$sp = [];

		self::wait_for_unlock();

		if (file_exists($filename)) {
			$fp = fopen($filename, 'r');
			if($fp) {
				if (flock($fp, LOCK_EX)) {
					$file = fread($fp, 32768);
					if ($file === false) {
						fclose($fp);
						print '<h1>Fatal Error - Could not read the preferences file</h1>';
						error_log("ERROR!              : COULD NOT LOAD PREFS");
						exit(1);
					}
					flock($fp, LOCK_UN);
					fclose($fp);

					$sp = unserialize($file);

				} else {
					fclose($fp);
					print '<h1>Fatal Error - Could not open the preferences file</h1>';
					error_log("ERROR!              : COULD NOT GET READ LOCK ON PREFS FILE");
					exit(1);
				}
			} else {
				print '<h1>Fatal Error - Could not open the preferences file</h1>';
				error_log("ERROR!              : COULD NOT GET HANDLE FOR PREFS FILE");
				exit(1);
			}
		}
		return $sp;
	}

	// Belt and braces locking approach. We've had issues with prefs being corrupted, despite
	// trying to get an exclusinve lock on the file, There can be issues with locks not being
	// exclusive across different processes.
	// So we create an additional lock file and we don't read or write to prefs while it exists.
	private static function wait_for_unlock() {
		while (file_exists(self::LOCKFILE)) {
			usleep(200000);
		}
	}

	private static function lock_prefs() {
		$lockfile = fopen(self::LOCKFILE, 'w');
		fclose($lockfile);
	}

	private static function unlock() {
		unlink(self::LOCKFILE);
	}

	// A lot of browsers are now expiring cookies after 6 months. We don't want that to happen.
	// Every time we load the page we refesh the expiry date of all the cookie prefs that
	// are currently set.
	public static function refresh_cookies() {
		foreach (self::COOKIEPREFS as $cookie => $default) {
			if (array_key_exists($cookie, $_COOKIE))
				setcookie($cookie, $_COOKIE[$cookie], ['expires' => 2147483647, 'path' => '/', 'SameSite' => 'Lax']);
		}
	}

	// Pass an array of key => value pairs
	// This sets the current value of a pref. If a pref is defined as a COOKIEPREF
	// it also sets its value in session_prefs, and sets the Cookie if IS_ROMINITOR is not defined.
	// For non-cookie prefs, this DOES NOT save them. Call save() afterwards if you need to do this.
	public static function set_pref($pref) {
		foreach ($pref as $k => $v) {
			$value = $v;
			if (array_key_exists($k, self::COOKIEPREFS) && ($value == '' || $value == null)) {
				// We tend to pass '' or null to clear a cookie. Rather than have to keep all that in sync,
				// just make sure we set the local pref back to its default value
				$value = self::COOKIEPREFS[$k];
			}
			self::$prefs[$k] = $value;
			if (array_key_exists($k, self::COOKIEPREFS)) {
				self::$session_prefs[$k] = $value;
				self::set_cookie_pref($k, $value);
			}
		}
	}

	private static function set_cookie_pref($cookie, $value) {
		if (defined('IS_ROMONITOR'))
			return;

		if ($value == '' || $value == null) {
			logger::trace('PREFS', 'Expiring Cookie',$cookie);
			setcookie($cookie, '', ['expires' => 1, 'path' => '/', 'SameSite' => 'Lax']);
		} else {
			logger::trace('PREFS', 'Setting Cookie',$cookie,'to',$value);
			// This is the maximum value a 32-Bit system can handle. It's January 2038
			// Brave won't store it more than 6 months anyway
			setcookie($cookie, $value, ['expires' => 2147483647, 'path' => '/', 'SameSite' => 'Lax']);
		}
	}

	private static function save_prefs_file($to_save, $file) {
		$ps = serialize($to_save);

		self::wait_for_unlock();

		self::lock_prefs();

		$fp = fopen($file, 'w');
		if($fp) {
			if (flock($fp, LOCK_EX)) {
				ftruncate($fp, 0);
				$success = fwrite($fp, $ps);
				fflush($fp);
				fclose($fp);
				self::unlock();
				if ($success === false) {
					error_log("ERROR!              : COULD NOT SAVE PREFS TO",$file);
					exit(1);
				}
			} else {
				fclose($fp);
				print '<h1>Fatal Error - Could not write to the preferences file</h1>';
				error_log("ERROR!              : COULD NOT GET WRITE LOCK ON PREFS FILE",$file);
				self::unlock();
				exit(1);
			}
		} else {
			print '<h1>Fatal Error - Could not open the preferences file</h1>';
			error_log("ERROR!              : COULD NOT GET HANDLE FOR PREFS FILE",$file);
			self::unlock();
			exit(1);
		}
	}

	public static function save() {
		$sp = array_diff_key(self::$prefs, self::COOKIEPREFS);
		self::save_prefs_file($sp, 'prefs/prefs.var');
	}

	public static function save_ui_defaults($p) {
		// We're sainvg cookie prefs so vars.php can initialise skin and clickmode
		// We do NOT use cookieprefs values when loading the UI prefs
		$valid = array_merge(self::BROWSER_PREFS, self::COOKIEPREFS);
		$to_save = array_intersect_key($p, $valid);
		// If we save these, the UI gets EXTREMELY confused if we change Player
		// using the ?setup screen.
		unset($to_save['currenthost']);
		unset($to_save['player_backend']);
		unset($to_save['browser_id']);
		self::save_prefs_file($to_save, 'prefs/ui_defaults.var');
	}

	// Get the prefs required for the UI.
	// Take the already loaded prefs, remove PRIVATE_PREFS and merge the defaults from BROWSER_PREFS
	// The browser will replace those values with anything it has in local storage
	public static function get_browser_prefs() {
		$private_prefs = array_combine(self::PRIVATE_PREFS, array_fill(0, count(self::PRIVATE_PREFS), null));
		$browser_prefs = array_diff_key(self::$prefs, $private_prefs);
		$user_defaults = self::load_prefs_file('prefs/ui_defaults.var');
		$user_defaults = array_intersect_key($user_defaults, self::BROWSER_PREFS);
		$browser_prefs = array_merge($browser_prefs, self::BROWSER_PREFS, $user_defaults);
		return $browser_prefs;
	}

	public static function redact_private() {
		$redacted = array();
		foreach (self::PRIVATE_PREFS as $p) {
			if (array_key_exists($p, self::$prefs) && self::$prefs[$p] != '') {
				$redacted[$p] = '[Redacted]';
			}
		}
		return $redacted;
	}

	public static function set_music_directory($dir) {
		logger::mark("SAVEPREFS", "Creating Album Art SymLink to ".$dir);
		if (is_link("prefs/MusicFolders")) {
			system ("unlink prefs/MusicFolders");
		}
		if ($dir) {
			system ('ln -s "'.$dir.'" prefs/MusicFolders');
		}
	}

	public static function currenthost() {
		return self::$prefs['currenthost'];
	}

	public static function skin() {
		return self::$prefs['skin'];
	}

	public static function player_name_hash() {
		return hash('md2', self::$prefs['currenthost'], false);
	}

	public static function get_pref($pref) {
		return (array_key_exists($pref, self::$prefs)) ? self::$prefs[$pref] : null;
	}

	public static function get_player_def() {
		return self::$prefs['multihosts'][self::$prefs['currenthost']];
	}

	public static function get_def_for_player($player) {
		return self::$prefs['multihosts'][$player];
	}

	public static function get_player_param($param) {
		return self::$prefs['multihosts'][self::$prefs['currenthost']][$param];
	}

	public static function set_player_param($param) {
		self::$prefs['multihosts'][self::$prefs['currenthost']] = array_merge(
			self::$prefs['multihosts'][self::$prefs['currenthost']],
			$param
		);
		self::save();
	}

	public static function get_radio_params() {
		return self::$prefs['multihosts'][self::$prefs['currenthost']]['radioparams'];
	}

	public static function set_radio_params($params) {
		self::$prefs['multihosts'][self::$prefs['currenthost']]['radioparams'] = array_merge(
			self::$prefs['multihosts'][self::$prefs['currenthost']]['radioparams'],
			$params
		);
		self::save();
	}

	public static function upgrade_host_defs($ver) {
		foreach (self::$prefs['multihosts'] as $key => $value) {
			switch ($ver) {
				case 45:
					self::$prefs['multihosts'][$key]['mopidy_slave'] = false;
					break;

				case 49:
					self::$prefs['multihosts'][$key]['radioparams'] = [
						"radiomode" => "",
						"radioparam" => "",
						"radiomaster" => "",
						"radioconsume" => 0
					];
					break;

				case 68:
					# Remove outdated, offensive terminology
					if (array_key_exists('mopidy_slave', self::$prefs['multihosts'][$key])) {
						self::$prefs['multihosts'][$key]['mopidy_remote'] = self::$prefs['multihosts'][$key]['mopidy_slave'];
						unset(self::$prefs['multihosts'][$key]['mopidy_slave']);
					}
					break;

				case 69:
					if (self::$prefs['lastfm_session_key'] && self::$prefs['lastfm_user']) {
						self::$prefs['lastfm_logged_in'] = true;
					}
					break;

				case 85:
					if (self::$prefs['consume_workaround'] && self::$prefs['we_do_consume']) {
						self::$prefs['multihosts'][$key]['do_consume'] = true;
					} else {
						self::$prefs['multihosts'][$key]['do_consume'] = false;
					}
					break;

				case 91:
					self::$prefs['multihosts'][$key]['radioparams']['radioconsume'] = [];
					break;

				case 92:
					unset(self::$prefs['multihosts'][$key]['radioparams']['radiomaster']);
					break;

				case 94:
					if (!array_key_exists('mopidy_remote', self::$prefs['multihosts'][$key]))
						self::$prefs['multihosts'][$key]['mopidy_remote'] = false;

					self::$prefs['multihosts'][$key]['websocket'] = false;
					break;

				case 95:
					if (!array_key_exists('radiodomains', self::$prefs['multihosts'][$key]['radioparams']))
						self::$prefs['multihosts'][$key]['radioparams']['radiodomains'] = ['local', 'spotify', 'ytmusic'];


			}
		}
		self::save();
	}

	public static function early_prefs_update() {
		// Change old 'language' pref to new 'interface_language' pref
		if (array_key_exists('language', self::$prefs)) {
			$newlangs = [
				'de' => 'de-DE',
				'en' => 'en-GB',
				'fr' => 'fr-FR',
				'it' => 'it-IT',
				'ru' => 'ru-RU',
				'pirate' => 'pirate'
			];
			self::$prefs['interface_language'] = $newlangs[self::$prefs['language']];
			logger::mark('INIT', 'Upgrading interface language from',self::$prefs['language'],'to',self::$prefs['interface_language']);
			unset(self::$prefs['language']);
		}
		// Change old object prefs into new associative arrays prefs
		// (objects was always an error anyway)
		if (is_object(self::$prefs['multihosts'])) {
			self::$prefs['multihosts'] = json_decode(json_encode(self::$prefs['multihosts']), true);
		}

		// Upgrade from old JavaScript Date.now() value to php time() value
		if (self::$prefs['last_lastfm_synctime'] > 999999999999) {
			logger::log('INIT', 'Swapping lastfm sync time to backend');
			self::$prefs['last_lastfm_synctime'] = round(self::$prefs['last_lastfm_synctime'] / 1000);
			self::$prefs['lastfm_sync_frequency'] = round(self::$prefs['lastfm_sync_frequency'] / 1000);
			if (self::$prefs['next_lastfm_synctime'] > 1000)
				self::$prefs['next_lastfm_synctime'] = round(self::$prefs['next_lastfm_synctime'] / 1000);
		}

		if (self::$prefs['linkchecker_nextrun'] > 999999999999) {
			self::$prefs['linkchecker_nextrun'] = round(self::$prefs['linkchecker_nextrun'] / 1000);
		}

		self::save();
	}

	public static function check_setup_values() {

		//
		// See if there are any POST values from the setup screen
		//

		if (array_key_exists('currenthost', $_POST)) {
			foreach (array('cleanalbumimages', 'do_not_show_prefs', 'use_mopidy_scan', 'spotify_mark_unplayable', 'spotify_mark_playable') as $p) {
				if (array_key_exists($p, $_POST)) {
					$_POST[$p] = true;
				} else {
					$_POST[$p] = false;
				}
			}
			foreach ($_POST as $i => $value) {
				logger::mark("INIT", "Setting Pref ".$i." to ".$value);
				self::set_pref([$i => $value]);
			}

			// Setup screen passes currenthost, mpd_host, mpd_port, mpd_password, and unix_socket
			// Alter the hosts setting for that host, but pull in mopidy_remote and do_consume
			// from the existing settings if they exist.

			$newhost = [
				'host' => self::$prefs['mpd_host'],
				'port' => self::$prefs['mpd_port'],
				'password' => self::$prefs['mpd_password'],
				'socket' => self::$prefs['unix_socket']
			];
			$current_player = self::get_player_def();

			self::$prefs['multihosts'][self::$prefs['currenthost']] = array_merge(self::DEFAULT_PLAYER, $current_player, $newhost);
			self::save();
		}

	}

}

?>