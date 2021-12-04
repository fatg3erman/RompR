<?php

class prefs {

	public static $database = null;

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
		"linkchecker_isrunning" => false,
		"linkchecker_frequency" => 604800000,
		"linkchecker_polltime" => 5000,
		"audiobook_directory" => '',
		"collection_player" => null,
		"snapcast_server" => '',
		"snapcast_port" => '1705',
		"snapcast_http" => '1780',
		"http_port_for_mopidy" => "6680",
		"multihosts" => [
			'Default' => [
				'host' => 'localhost',
				'port' => '6600',
				'password' => '',
				'socket' => '',
				'mopidy_remote' => false,
				'radioparams' => [
					"radiomode" => "",
					"radioparam" => "",
					"radiomaster" => "",
					"radioconsume" => 0
				]
			]
		],
		'old_style_sql' => false,
		'auto_audiobook' => array(),

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
		"lastfm_sync_frequency" => 86400000,
		"lfm_importer_start_offset" => 0,
		"lfm_importer_last_import" => 0,
		"bing_api_key" => '',
		"hide_master_volume" => false,

		// Things that are set as Cookies
		"sortbydate" => false,
		"notvabydate" => false,
		"currenthost" => 'Default',
		"player_backend" => "none",
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
		"synclove" => false,
		"synclovevalue" => "5",
		"theme" => "Numismatist.css",
		"icontheme" => "Bobalophagus-Dark",
		"coversize" => "40-Large.css",
		"fontsize" => "04-Grande.css",
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
		"alarms" => array( ),
		"lastversionchecked" => '1.00',
		"lastversionchecktime" => 0,
		'playlistbuttons_isopen' => false,
		'collectionbuttons_isopen' => false,
		'advsearchoptions_isopen' => false,
		'podcastbuttons_isopen' => false,
		'last_cache_clean' => 10,
		'next_podcast_refresh' => 10,
		'use_original_releasedate' => false,
		"bgimgparms" => ['dummy' => 'baby'],
		"chartoption" => 0,
		"consume_workaround" => false,
		"we_do_consume" => false
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

	public static function load() {

		// This cannot be declared above because PHP reasons. It seems to want to use the rules
		// for declaring CONST when declaring a static variable.
		self::$prefs["last_lastfm_synctime"] = time()*1000;

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
					self::$prefs = array_replace(self::$prefs, $sp);
					self::$prefs['player_backend'] = 'none';
					logger::setLevel(self::$prefs['debug_enabled']);
					logger::setOutfile(self::$prefs['custom_logfile']);

					foreach ($_COOKIE as $a => $v) {
						if (array_key_exists($a, self::$prefs)) {
							if ($v === 'false') { $v = false; }
							if ($v === 'true') { $v = true; }
							self::$prefs[$a] = $v;
							logger::core('COOKIEPREFS',"Pref",$a,"is set by Cookie - Value :",$v);
						}
					}
			  } else {
				  print '<h1>Fatal Error - Could not open the preferences file</h1>';
				  error_log("ERROR!              : COULD NOT GET READ FILE LOCK ON PREFS FILE");
				  exit(1);
			  }
		  } else {
			  print '<h1>Fatal Error - Could not open the preferences file</h1>';
			  error_log("ERROR!              : COULD NOT GET HANDLE FOR PREFS FILE");
			  exit(1);
		  }
	   }
	}

	public static function save() {
		$sp = self::$prefs;
		$ps = serialize($sp);
		$r = file_put_contents('prefs/prefs.var', $ps, LOCK_EX);
		if ($r === false) {
			error_log("ERROR!              : COULD NOT SAVE PREFS");
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
		self::save();
	}

	public static function check_setup_values() {

		//
		// See if there are any POST values from the setup screen
		//

		if (array_key_exists('currenthost', $_POST)) {
			foreach (array('cleanalbumimages', 'do_not_show_prefs', 'use_mopidy_scan') as $p) {
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
			setcookie('currenthost',self::$prefs['currenthost'],time()+365*24*60*60*10,'/');

			$mopidy_remote = false;
			if (array_key_exists('mopidy_remote', self::$prefs['multihosts'][self::$prefs['currenthost']])) {
				$mopidy_remote = self::$prefs['multihosts'][self::$prefs['currenthost']]['mopidy_remote'];
			}
			self::$prefs['multihosts'][self::$prefs['currenthost']] = [
					'host' => self::$prefs['mpd_host'],
					'port' => self::$prefs['mpd_port'],
					'password' => self::$prefs['mpd_password'],
					'socket' => self::$prefs['unix_socket'],
					'mopidy_remote' => $mopidy_remote,
					'radioparams' => [
						"radiomode" => "",
						"radioparam" => "",
						"radiomaster" => "",
						"radioconsume" => 0
					]
			];
			self::save();
		}

	}

}

?>