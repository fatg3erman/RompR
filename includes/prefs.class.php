<?php

class prefs {

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
			"radioconsume" => []
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

	public static $prefs = array(
		// Things that only make sense as backend options, not per-user options
		"music_directory_albumart" => "",
		"mysql_host" => "localhost",
		"mysql_database" => "romprdb",
		"mysql_user" => "rompr",
		"mysql_password" => "romprdbpass",
		"mysql_port" => "3306",
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

		// Things that could be set on a per-user basis but need to be known by the backend
		"displaycomposer" => true,
		"artistsatstart" => array("Various Artists","Soundtracks"),
		"nosortprefixes" => array("The"),
		"sortcollectionby" => "artist",
		"sortresultsby" => "sameas",
		"actuallysortresultsby" => 'artist',
		"sync_lastfm_playcounts" => false,
		"sync_lastfm_at_start" => false,
		"next_lastfm_synctime" => 0,
		"lastfm_sync_frequency" => 86400,
		"lfm_importer_start_offset" => 0,
		"lfm_importer_last_import" => 0,
		"bing_api_key" => '',
		"hide_master_volume" => false,

		// Things that are set as Cookies
		"sortbydate" => false,
		"notvabydate" => false,
		"collectionrange" => ADDED_ALL_TIME,

		// These are currently saved in the backend, as the most likely scenario is one user
		// with multiple browsers. But what if it's multiple users?
		"lastfm_user" => "",
		"lastfm_session_key" => "",
		"autotagname" => "",
		"lastfm_logged_in" => false,

		// All of these are saved in the browser, so these are only defaults
		"tradsearch" => false,
		"lastfm_scrobbling" => false,
		"lastfm_autocorrect" => false,
		"sourceshidden" => false,
		"playlisthidden" => false,
		"infosource" => "lastfm",
		"sourceswidthpercent" => 25,
		"playlistwidthpercent" => 25,
		"downloadart" => true,
		"clickmode" => "double",
		"chooser" => "albumlist",
		"hide_albumlist" => false,
		"hide_filelist" => false,
		"hide_radiolist" => false,
		"hide_podcastslist" => false,
		"hide_playlistslist" => false,
		"hide_audiobooklist" => false,
		"hide_searcher" => false,
		"hidebrowser" => false,
		"shownupdatewindow" => '',
		"scrolltocurrent" => false,
		"alarm_ramptime" => 30,
		"alarm_snoozetime" => 8,
		"lastfmlang" => "interface",
		"synctags" => false,
		"synclovevalue" => "0",
		"theme" => "Numismatist.css",
		"icontheme" => "Bobalophagus-Dark",
		"coversize" => 48,
		"fontsize" => 11,
		"fontfamily" => "Nunito.css",
		"displayresultsas" => "collection",
		'crossfade_duration' => 5,
		"newradiocountry" => "countries/GB",
		"search_limit_limitsearch" => false,
		"scrobblepercent" => 50,
		"updateeverytime" => false,
		"fullbiobydefault" => true,
		"mopidy_search_domains" => array("local", "spotify"),
		"mopidy_radio_domains" => array("local", "spotify"),
		"outputsvisible" => false,
		"wheelscrollspeed" => "150",
		"searchcollectiononly" => false,
		"displayremainingtime" => true,
		"cdplayermode" => false,
		"auto_discovembobulate" => false,
		"sleeptime" => 30,
		"sleepon" => false,
		"sortwishlistby" => 'artist',
		"player_in_titlebar" => false,
		"communityradioorderby" => 'name',
		"browser_id" => null,
		"playlistswipe" => true,
		"default_podcast_display_mode" => DISPLAYMODE_ALL,
		"default_podcast_refresh_mode" => REFRESHOPTION_MONTHLY,
		"default_podcast_sort_mode" => SORTMODE_NEWESTFIRST,
		"podcast_mark_new_as_unlistened" => false,
		"use_albumart_in_playlist" => true,
		"podcast_sort_levels" => 4,
		"podcast_sort_0" => 'Title',
		"podcast_sort_1" => 'Artist',
		"podcast_sort_2" => 'Category',
		"podcast_sort_3" => 'new',
		"lastversionchecked" => '1.00',
		"lastversionchecktime" => 0,
		'playlistbuttons_isopen' => false,
		'collectionbuttons_isopen' => false,
		'advsearchoptions_isopen' => false,
		'podcastbuttons_isopen' => false,
		'use_original_releasedate' => false,
		"bgimgparms" => ['dummy' => 'baby'],
		"chartoption" => 0,
		// Need these next two so the player defs can be updated
		"consume_workaround" => false,
		"we_do_consume" => false,
		"somafm_quality" => 'highest_available_quality',
		"spotify_mark_unplayable" => false,
		"spotify_mark_playable" => false
	);

	// Prefs that should not be exposed to the browser for security reasons
	private static $private_prefs = array(
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
	);

	const PREFS_WITHOUT_DEFAULTS = [
		'interface_language' => null,
		'collection_type' => null,
		'spotify_token' => null,
		'spotify_token_expires' => null
	];

	private static $prefs_to_never_save = [
		'currenthost' => 'Default',
		'player_backend' => null,
		'skin' => null
	];

	const COOKIEPREFS = [
		'currenthost',
		'player_backend',
		'clickmode',
		'skin'
	];

	public static function load() {

		// This cannot be declared above because PHP reasons. It seems to want to use the rules
		// for declaring CONST when declaring a static variable.
		self::$prefs["last_lastfm_synctime"] = time();

		if (file_exists('prefs/prefs.var')) {
			$fp = fopen('prefs/prefs.var', 'r');
			if($fp) {
				if (flock($fp, LOCK_SH)) {
					$sp = unserialize(fread($fp, 32768));
					flock($fp, LOCK_UN);
					fclose($fp);
					if ($sp === false) {
						print '<h1>Fatal Error - Could not open the preferences file</h1>';
						error_log("ERROR!              : COULD NOT LOAD PREFS");
						exit(1);
					}
					// Old prefs files might have values we've removed. This removes those values
					$sp = array_intersect_key($sp, array_merge(self::$prefs, self::PREFS_WITHOUT_DEFAULTS));
					self::$prefs = array_replace(self::$prefs, $sp);
					// This ensures that $prefs never contains anything other than the default values
					// for these items when we load it. This is important. The default values can be
					// changed for the session by calling set_session_pref() so that they don't get
					// overwritten if you reload the prefs during the session.
					self::$prefs = array_merge(self::$prefs, self::$prefs_to_never_save);

					logger::setLevel(self::$prefs['debug_enabled']);
					logger::setOutfile(self::$prefs['custom_logfile']);

					// Set any prefs that are supplied as cookies.
					foreach ($_COOKIE as $a => $v) {
						// The UI can st a cookie to '' but doesn't seem able to make it expire
						if ($v == '') {
							setcookie($a, $v, ['expires' => 1, 'path' => '/', 'SameSite' => 'Lax']);
						} else if (array_key_exists($a, self::$prefs)) {
							if ($v === 'false') { $v = false; }
							if ($v === 'true') { $v = true; }
							self::$prefs[$a] = $v;
							logger::core('COOKIEPREFS',"Pref",$a,"is set by Cookie - Value :",$v);
						}
					}
			  } else {
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
	}

	// Pass an array of key => value pairs
	public static function set_session_pref($pref) {
		foreach ($pref as $k => $v) {
			self::$prefs[$k] = $v;
			self::$prefs_to_never_save[$k] = $v;
			if (!defined('IS_ROMONITOR') && in_array($k, self::COOKIEPREFS)) {
				if ($v == '') {
					logger::trace('PREFS', 'Expiring Cookie',$k);
					setcookie($k, $v, ['expires' => 1, 'path' => '/', 'SameSite' => 'Lax']);
				} else {
					logger::trace('PREFS', 'Setting Cookie',$k,'to',$v);
					setcookie($k, $v, ['expires' => time()+365*24*60*60*10, 'path' => '/', 'SameSite' => 'Lax']);
				}
			}
		}
	}

	public static function set_pref($pref) {
		foreach ($pref as $k => $v) {
			self::$prefs[$k] = $v;
		}
	}

	public static function save() {
		$sp = self::$prefs;
		foreach (self::$prefs_to_never_save as $pref => $val) {
			unset($sp[$pref]);
		}
		$ps = serialize($sp);

		$fp = fopen('prefs/prefs.var', 'w');
		if($fp) {
			if (flock($fp, LOCK_EX)) {
				$success = fwrite($fp, $ps);
				flock($fp, LOCK_UN);
				fclose($fp);
				if ($success === false) {
					error_log("ERROR!              : COULD NOT SAVE PREFS");
					exit(1);
				}
			  } else {
				  print '<h1>Fatal Error - Could not write to the preferences file</h1>';
				  error_log("ERROR!              : COULD NOT GET WRITE LOCK ON PREFS FILE");
				  exit(1);
			  }
		  } else {
			  print '<h1>Fatal Error - Could not open the preferences file</h1>';
			  error_log("ERROR!              : COULD NOT GET HANDLE FOR PREFS FILE");
			  exit(1);
		  }
	}

	public static function get_safe_prefs() {
		$safeprefs = array();
		foreach (self::$prefs as $p => $v) {
			if (!in_array($p, self::$private_prefs)) {
				$safeprefs[$p] = $v;
			}
		}
		return $safeprefs;
	}

	public static function redact_private() {
		$redacted = array();
		foreach (self::$private_prefs as $p) {
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

		if (is_object(self::$prefs['bgimgparms'])) {
			self::$prefs['bgimgparms'] = json_decode(json_encode(self::$prefs['bgimgparms']), true);
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

	public static function get_player_def() {
		return self::$prefs['multihosts'][self::$prefs['currenthost']];
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
				self::$prefs[$i] = $value;
			}
			self::set_session_pref(['currenthost' => self::$prefs['currenthost']]);

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