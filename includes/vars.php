<?php

$dtz = ini_get('date.timezone');
if (!$dtz) {
	date_default_timezone_set('UTC');
}

define('ROMPR_MAX_TRACKS_PER_TRANSACTION', 500);
define('ROMPR_COLLECTION_VERSION', 6);
define('ROMPR_IMAGE_VERSION', 4);
define('ROMPR_SCHEMA_VERSION', 68);
define('ROMPR_VERSION', '1.51');
define('ROMPR_IDSTRING', 'RompR Music Player '.ROMPR_VERSION);
define('ROMPR_MOPIDY_MIN_VERSION', 1.1);
define('ROMPR_UNKNOWN_STREAM', "Unknown Internet Stream");

define('REFRESHOPTION_NEVER', 0);
define('REFRESHOPTION_HOURLY', 1);
define('REFRESHOPTION_DAILY', 2);
define('REFRESHOPTION_WEEKLY', 3);
define('REFRESHOPTION_MONTHLY', 4);

define('SORTMODE_NEWESTFIRST', 0);
define('SORTMODE_OLDESTFIRST', 1);

define('DISPLAYMODE_ALL', 0);
define('DISPLAYMODE_NEW', 1);
define('DISPLAYMODE_UNLISTENED', 2);
define('DISPLAYMODE_DOWNLOADEDNEW', 3);
define('DISPLAYMODE_DOWNLOADED', 4);

define('ROMPR_PODCAST_TABLE_VERSION', 4);

define('ADDED_ALL_TIME', 0);
define('ADDED_TODAY', 1);
define('ADDED_THIS_WEEK', 2);
define('ADDED_THIS_MONTH', 3);
define('ADDED_THIS_YEAR', 4);

// Safe definitions for setups that do not have a full set of image support built in,
// Otherwise we spam the server logs with udefined constant errors.
// These are the MIME types that make it compatible with imagemagick
if (!defined('IMAGETYPE_JPEG')) {
	define('IMAGETYPE_JPEG', 'image/jpeg');
}
if (!defined('IMAGETYPE_GIF')) {
	define('IMAGETYPE_GIF', 'image/gif');
}
if (!defined('IMAGETYPE_PNG')) {
	define('IMAGETYPE_PNG', 'image/png');
}
if (!defined('IMAGETYPE_WBMP')) {
	define('IMAGETYPE_WBMP', 'image/wbmp');
}
if (!defined('IMAGETYPE_XBM')) {
	define('IMAGETYPE_XBM', 'image/xbm');
}
if (!defined('IMAGETYPE_WEBP')) {
	define('IMAGETYPE_WEBP', 'image/webp');
}
if (!defined('IMAGETYPE_BMP')) {
	define('IMAGETYPE_BMP', 'image/bmp');
}
if (!defined('IMAGETYPE_SVG')) {
	define('IMAGETYPE_SVG', 'image/svg+xml');
}

define('IMAGESIZE_SMALL', 100);
define('IMAGESIZE_SMALLISH', 250);
define('IMAGESIZE_MEDIUM', 400);

define('IMAGEQUALITY_SMALL', 75);
define('IMAGEQUALITY_SMALLISH', 70);
define('IMAGEQUALITY_MEDIUM', 70);
define('IMAGEQUALITY_ASDOWNLOADED', 90);

define('ORIENTATION_PORTRAIT', 0);
define('ORIENTATION_LANDSCAPE', 1);

define('MPD_FILE_MODEL', array (
		'file' => null,
		'domain' => 'local',
		'type' => 'local',
		'station' => null,
		'stream' => null,
		'folder' => null,
		'Title' => null,
		'Album' => null,
		'Artist' => null,
		'Track' => 0,
		'Name' => null,
		'AlbumArtist' => null,
		'Time' => 0,
		'X-AlbumUri' => null,
		'playlist' => '',
		'X-AlbumImage' => null,
		'Date' => null,
		'OriginalDate' => null,
		'Last-Modified' => '0',
		'Disc' => null,
		'Composer' => null,
		'Performer' => null,
		'Genre' => 'None',
		'ImgKey' => null,
		'StreamIndex' => null,
		'Searched' => 0,
		'Playcount' => 0,
		'Comment' => '',
		// Never send null in any musicbrainz id as it prevents plugins from
		// waiting on lastfm to find one
		'MUSICBRAINZ_ALBUMID' => '',
		'MUSICBRAINZ_ARTISTID' => array(''),
		'MUSICBRAINZ_ALBUMARTISTID' => '',
		'MUSICBRAINZ_TRACKID' => '',
		'Id' => null,
		'Pos' => null,
		'ttindex' => null,
		'trackartist_index' => null,
		'albumartist_index' => null,
		'album_index' => null,
		'searchflag' => 0,
		'hidden' => 0,
		'isaudiobook' => 0
	)
);

define('MPD_ARRAY_PARAMS', array(
		"Artist",
		"AlbumArtist",
		"Composer",
		"Performer",
		"MUSICBRAINZ_ARTISTID",
	)
);

// Rompr's internal file model used in the Javascript side is a merge of the MPD_FILE_MODEL and ROMPR_FILE_MODEL
// it is created in class playlistCollection

define('ROMPR_FILE_MODEL', array(
		"progress" => 0,
		"year" => null,
		"albumartist" => '',
		"trackartist" => '',
		"images" => '',
		"metadata" => array(
			"iscomposer" => 'false',
			"artists" => array(),
			"album" => array(
				"name" => '',
				"artist" => '',
				"musicbrainz_id" => '',
				"uri" => null
			),
			"track" => array(
				"name" => '',
				"musicbrainz_id" => '',
			),
		)
	)
);

const COLLECTION_SORT_MODES = array(
	'artist' 		=> 'label_artists',
	'album' 		=> 'label_albums',
	'albumbyartist' => 'label_albumsbyartist',
	'genre' 		=> 'label_genre',
	'rating' 		=> 'label_rating',
	'tag' 			=> 'label_tag'
);

// These indices don't start at zero, to make sure json_encode includes them when we encode CUSTOM_RADIO_ITEMS
// Also there are gaps so we can insert new ones
define('RADIO_RULE_OPTIONS_STRING_IS', 10);
define('RADIO_RULE_OPTIONS_STRING_IS_NOT', 20);
define('RADIO_RULE_OPTIONS_STRING_CONTAINS', 30);
define('RADIO_RULE_OPTIONS_STRING_NOT_CONTAINS', 40);
define('RADIO_RULE_OPTIONS_STRING_EXISTS', 45);
define('RADIO_RULE_OPTIONS_INTEGER_LESSTHAN', 50);
define('RADIO_RULE_OPTIONS_INTEGER_EQUALS', 60);
define('RADIO_RULE_OPTIONS_INTEGER_GREATERTHAN', 70);

const RADIO_OPTIONS_STRING = array(
	RADIO_RULE_OPTIONS_STRING_IS 			=> 'label_is',
	RADIO_RULE_OPTIONS_STRING_IS_NOT		=> 'label_is_not',
	RADIO_RULE_OPTIONS_STRING_CONTAINS		=> 'label_contains',
	RADIO_RULE_OPTIONS_STRING_NOT_CONTAINS	=> 'label_does_not_contain'
);

const RADIO_OPTIONS_TAG = array(
	RADIO_RULE_OPTIONS_STRING_IS 			=> 'label_is',
	RADIO_RULE_OPTIONS_STRING_IS_NOT		=> 'label_is_not',
	RADIO_RULE_OPTIONS_STRING_CONTAINS		=> 'label_contains',
	RADIO_RULE_OPTIONS_STRING_NOT_CONTAINS	=> 'label_does_not_contain',
	RADIO_RULE_OPTIONS_STRING_EXISTS		=> 'label_exists'
);

const RADIO_OPTIONS_INTEGER = array(
	RADIO_RULE_OPTIONS_INTEGER_LESSTHAN		=> 'label_lessthan',
	RADIO_RULE_OPTIONS_INTEGER_EQUALS		=> 'label_equals',
	RADIO_RULE_OPTIONS_INTEGER_GREATERTHAN	=> 'label_greaterthan'
);

const RADIO_COMBINE_OPTIONS = array(
	' OR '	=> 'label_any_rule',
	' AND '	=> 'label_all_rules'
);

// NOTE: STRING options need a specific handler in customradio.js::make_value_box()
define('CUSTOM_RADIO_ITEMS', array(
	array(
		'name'		=> 'label_artist',
		'db_key'	=> 'ta.Artistname',
		'options'	=> RADIO_OPTIONS_STRING
	),
	array(
		'name'		=> 'label_albumartist',
		'db_key'	=> 'aa.Artistname',
		'options'	=> RADIO_OPTIONS_STRING
	),
	array(
		'name'		=> 'label_tracktitle',
		'db_key'	=> 'Title',
		'options'	=> RADIO_OPTIONS_STRING
	),
	array(
		'name'		=> 'label_albumtitle',
		'db_key'	=> 'Albumname',
		'options'	=> RADIO_OPTIONS_STRING
	),
	array(
		'name'		=> 'label_genre',
		'db_key'	=> 'Genre',
		'options'	=> RADIO_OPTIONS_STRING
	),
	array(
		'name'		=> 'label_tag',
		'db_key'	=> 'Tagtable.Name',
		'options'	=> RADIO_OPTIONS_TAG
	),
	array(
		'name'		=> 'label_rating',
		'db_key'	=> 'Rating',
		'options'	=> RADIO_OPTIONS_INTEGER
	),
	array(
		'name'		=> 'label_playcount',
		'db_key'	=> 'Playcount',
		'options'	=> RADIO_OPTIONS_INTEGER
	),
	array(
		'name'		=> 'label_duration_seconds',
		'db_key'	=> 'Duration',
		'options'	=> RADIO_OPTIONS_INTEGER
	),
	array(
		'name'		=> 'label_year',
		'db_key'	=> 'TYear',
		'options'	=> RADIO_OPTIONS_INTEGER
	),
	array(
		'name'		=> 'label_tracknumber',
		'db_key'	=> 'TrackNo',
		'options'	=> RADIO_OPTIONS_INTEGER
	),
	array(
		'name'		=> 'label_disc',
		'db_key'	=> 'Disc',
		'options'	=> RADIO_OPTIONS_INTEGER
	),
	array(
		'name'		=> 'label_dayssince',
		'db_key'	=> 'db_function_tracks_played_since',
		'options'	=> RADIO_OPTIONS_INTEGER
	)
));

$mysqlc = null;

$prefs = array(
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
	"multihosts" => (object) array (
		'Default' => (object) array(
			'host' => 'localhost',
			'port' => '6600',
			'password' => '',
			'socket' => '',
			'mopidy_remote' => false,
			'radioparams' => (object) array (
				"radiomode" => "",
				"radioparam" => "",
				"radiomaster" => "",
				"radioconsume" => 0
			)
		)
	),
	'dev_mode' => false,
	'live_mode' => false,
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

	// Things that could be set on a per-user basis but need to be known by the backend
	"displaycomposer" => true,
	"artistsatstart" => array("Various Artists","Soundtracks"),
	"nosortprefixes" => array("The"),
	"sortcollectionby" => "artist",
	"sortresultsby" => "sameas",
	"actuallysortresultsby" => 'artist',
	"sync_lastfm_playcounts" => false,
	"sync_lastfm_at_start" => false,
	"last_lastfm_synctime" => time()*1000,
	"next_lastfm_synctime" => 0,
	"lastfm_sync_frequency" => 86400000,
	"lfm_importer_start_offset" => 0,
	"lfm_importer_last_import" => 0,
	"bing_api_key" => '',

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
	"lastfmlang" => "default",
	"user_lang" => "en",
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
	"bgimgparms" => (object) array('dummy' => 'baby'),
	"alarms" => array( ),
	"lastversionchecked" => '1.00',
	"lastversionchecktime" => 0,
	'playlistbuttons_isopen' => false,
	'collectionbuttons_isopen' => false,
	'advsearchoptions_isopen' => false,
	'podcastbuttons_isopen' => false,
	'last_cache_clean' => 10,
	'next_podcast_refresh' => 10,
	'use_original_releasedate' => false
);

// Prefs that should not be exposed to the browser for security reasons
// lastfm_session_key should really be one of these, but it is needed by the frontend
$private_prefs = array(
	'mysql_database',
	'mysql_host',
	'mysql_password',
	'mysql_port',
	'mysql_user',
	'proxy_host',
	'proxy_password',
	'proxy_user',
	'spotify_token',
	'spotify_token_expires',
	'bing_api_key'
);

// ====================================================================
// Load Saved Preferences
loadPrefs();

if (defined('ROMPR_IS_LOADING')) {
	logger::mark("INIT", "******++++++======------******------======++++++******");
	logger::mark("INIT", "******++++++===== Hello Music Lovers =====++++++******");
}

if (array_key_exists('REQUEST_URI', $_SERVER)) {
	logger::core("REQUEST", $_SERVER['REQUEST_URI']);
}

if (!property_exists($prefs['multihosts'], $prefs['currenthost'])) {
	logger::warn("INIT", $prefs['currenthost'],"is not defined in the hosts defs");
	foreach ($prefs['multihosts'] as $key => $obj) {
		logger::log("INIT", "  Using host ".$key);
		$prefs['currenthost'] = $key;
		setcookie('currenthost',$prefs['currenthost'],time()+365*24*60*60*10,'/');
		break;
	}
}

logger::core("INIT", "Using MPD Host ".$prefs['currenthost']);

if (!array_key_exists('currenthost', $_COOKIE)) {
	setcookie('currenthost',$prefs['currenthost'],time()+365*24*60*60*10,'/');
}

// NOTE. skin is NOT saved as a preference on the backend. It is set as a Cookie only.
// This is because saving it once as a preference would change the default for ALL new devices
// and we want to allow devices to intelligently select a default skin using checkwindowsize.php
$skin = null;
if(array_key_exists('skin', $_REQUEST)) {
	$skin = $_REQUEST['skin'];
	logger::core("INIT", "Request asked for skin: ".$skin);
} else if (array_key_exists('skin', $_COOKIE)) {
	$skin = $_COOKIE['skin'];
	logger::core("INIT", "Using skin as set by Cookie: ".$skin);
}
if ($skin !== null) {
	$skin = trim($skin);
}

$romonitor_hack = true;
// ====================================================================

function savePrefs() {

	global $prefs;
	$sp = $prefs;
	$ps = serialize($sp);
	$r = file_put_contents('prefs/prefs.var', $ps, LOCK_EX);
	if ($r === false) {
		error_log("ERROR!              : COULD NOT SAVE PREFS");
	}
}

function loadPrefs() {
	global $prefs;
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
				$prefs = array_replace($prefs, $sp);
				$prefs['player_backend'] = 'none';
				logger::setLevel($prefs['debug_enabled']);
				logger::setOutfile($prefs['custom_logfile']);

				foreach ($_COOKIE as $a => $v) {
					if (array_key_exists($a, $prefs)) {
						if ($v === 'false') { $v = false; }
						if ($v === 'true') { $v = true; }
						$prefs[$a] = $v;
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

function set_music_directory($dir) {
	global $prefs;
	$prefs['music_directory_albumart'] = rtrim($dir, '/');
	logger::mark("SAVEPREFS", "Creating Album Art SymLink to ".$dir);
	if (is_link("prefs/MusicFolders")) {
		system ("unlink prefs/MusicFolders");
	}
	system ('ln -s "'.$dir.'" prefs/MusicFolders');
}

class logger {

	private static $outfile;
	private static $loglevel = 8;
	private static $debug_colours = array(
		# light red
		0 => 91,
		# red
		1 => 31,
		# yellow
		2 => 33,
		# magenta
		3 => 35,
		# cyan
		4 => 36,
		# light blue
		5 => 94,
		# light yellow
		6 => 93,
		# green
		7 => 32,
		# light grey
		8 => 37,
		# white
		9 => 97
	);

	private static $debug_names = array(
		0 => 'Disabled',
		1 => 'ERROR',
		2 => 'WARN ',
		3 => 'MARK ',
		4 => 'INFO ',
		5 => 'LOG  ',
		6 => 'TRACE',
		7 => 'DEBUG',
		8 => 'CORE '
	);

	public static function setLevel($level) {
		self::$loglevel = intval($level);
	}

	public static function setOutfile($file) {
		self::$outfile  = $file;
	}

	public static function getLevelName($level) {
		return self::$debug_names[$level];
	}

	private static function dothelogging($level, $parms) {
		if ($level > self::$loglevel || $level < 1) return;
		$module = array_shift($parms);
		if (strlen($module) > 18) {
			$in = ' ';
		} else {
			$in = str_repeat(" ", 18 - strlen($module));
		}
		$pid = getmypid();
		array_walk($parms, 'logger::un_array');
		$out = implode(' ', $parms);
		if (self::$outfile != "") {
			// Two options here - either colour by level
			// $col = self::$debug_colours[$level];
			// or attempt to have different processes in different colours.
			// This helps to keep track of things when multiple concurrent things are happening at once.
			$col = self::$debug_colours[$pid % 10];
			error_log("\033[90m".strftime('%T').' : '.self::$debug_names[$level]." : \033[".$col."m".$module.$in.$out."\033[0m\n",3,self::$outfile);
		} else {
			error_log(self::$debug_names[$level].' : '.$module.$in.": ".$out,0);
		}
	}

	public static function un_array(&$a, $i) {
		if (is_array($a)) {
			$a = multi_implode($a);
		} else if (is_object($a)) {
			$a = print_r($a, true);
		}
	}

	// Level 8 - CORE for continuous running commentary
	public static function core() {
		$parms = func_get_args();
		if (!is_array($parms[1])) {
			$dbt=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
	        $caller = isset($dbt[1]['function']) ? '('.$dbt[1]['function'].') ' : '';
	        $parms[1] = $caller.$parms[1];
	    }
		logger::dothelogging(8, $parms);
	}

	// Level 7 - DEBUG for low level complex info
	public static function debug() {
		$parms = func_get_args();
		if (!is_array($parms[1])) {
			$dbt=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
	        $caller = isset($dbt[1]['function']) ? '('.$dbt[1]['function'].') ' : '';
	        $parms[1] = $caller.$parms[1];
	    }
		logger::dothelogging(7, $parms);
	}

	// Level 6 - TRACE for in-function details
	public static function trace() {
		$parms = func_get_args();
		if (!is_array($parms[1])) {
			$dbt=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
	        $caller = isset($dbt[1]['function']) ? '('.$dbt[1]['function'].') ' : '';
	        $parms[1] = $caller.$parms[1];
	    }
		logger::dothelogging(6, $parms);
	}

	// Level 5 - LOG for following code flow
	public static function log() {
		$parms = func_get_args();
		if (!is_array($parms[1])) {
			$dbt=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
	        $caller = isset($dbt[1]['function']) ? '('.$dbt[1]['function'].') ' : '';
	        $parms[1] = $caller.$parms[1];
	    }
		logger::dothelogging(5, $parms);
	}

	// Level 4 - INFO for information
	public static function info() {
		$parms = func_get_args();
		logger::dothelogging(4, $parms);
	}

	// Level 3 - MARK for important information
	public static function mark() {
		$parms = func_get_args();
		logger::dothelogging(3, $parms);
	}

	// Level 2 - WARN for things that go wrong
	public static function warn() {
		$parms = func_get_args();
		logger::dothelogging(2, $parms);
	}

	// Level 1 - ERROR for serious errors
	public static function error() {
		$parms = func_get_args();
		logger::dothelogging(1, $parms);
	}

}

function upgrade_host_defs($ver) {
	global $prefs;
	foreach ($prefs['multihosts'] as $key => $value) {
		switch ($ver) {
			case 45:
				$prefs['multihosts']->{$key}->mopidy_slave = false;
				break;

			case 49:
				$prefs['multihosts']->{$key}->radioparams = (object) array (
					"radiomode" => "",
					"radioparam" => "",
					"radiomaster" => "",
					"radioconsume" => 0
				);
				break;

			case 68:
				# Remove outdated, offensive terminology
				$prefs['multihosts']->{$key}->mopidy_remote = $prefs['multihosts']->{$key}->mopidy_slave;
				unset($prefs['multihosts']->{$key}->mopidy_slave);
				break;


		}
	}
	savePrefs();
}

function multi_implode($array, $glue = ', ') {
	if (!is_array($array)) {
		return $array;
	}
	$ret = '';
	foreach ($array as $key => $item) {
		if (is_array($item)) {
			$ret .= $key . '=[' . multi_implode($item, $glue) . ']' . $glue;
		} else {
			$ret .= $key . '=' . $item . $glue;
		}
	}
	$ret = substr($ret, 0, 0-strlen($glue));
	return $ret;
}

?>
