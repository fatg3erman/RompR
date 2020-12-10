<?php

$dtz = ini_get('date.timezone');
if (!$dtz) {
	date_default_timezone_set('UTC');
}

//
//---------------------------------------------------------------------------------------------
//

//
// Using autoloading as part of the code upgrade.
// Things will be slowly shifted into this form as we go on
//

CONST CLASS_DIRS = array(
	'includes',
	'backends/sql',
	'collection',
	'collection/sortby',
	'player',
	'util_classes',
	'browser/apis'
);
foreach (CLASS_DIRS as $d) {
	set_include_path($d.PATH_SEPARATOR.get_include_path());
}
spl_autoload_extensions('.class.php');
spl_autoload_register();

//
//---------------------------------------------------------------------------------------------
//

define('ROMPR_MAX_TRACKS_PER_TRANSACTION', 500);
define('ROMPR_COLLECTION_VERSION', 6);
define('ROMPR_IMAGE_VERSION', 4);
define('ROMPR_SCHEMA_VERSION', 69);
define('ROMPR_VERSION', '1.52');
define('ROMPR_IDSTRING', 'RompR Music Player '.ROMPR_VERSION);
define('ROMPR_MOPIDY_MIN_VERSION', 1.1);
define('ROMPR_UNKNOWN_STREAM', "Unknown Internet Stream");

define('ROMPR_MIN_TRACKS_TO_DETERMINE_COMPILATION', 3);
define('ROMPR_MIN_NOT_COMPILATION_THRESHOLD', 0.6);

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

//
// Mapping of collection/sortby classes to labels
//

const COLLECTION_SORT_MODES = array(
	'artist' 		=> 'label_artists',
	'album' 		=> 'label_albums',
	'albumbyartist' => 'label_albumsbyartist',
	'genre' 		=> 'label_genre',
	'rating' 		=> 'label_rating',
	'tag' 			=> 'label_tag'
);

//
// Constants For Custom Smart Radio Stations
//

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

//
// Load Saved Preferences
//
prefs::load();

if (defined('ROMPR_IS_LOADING')) {
	//
	// Update old prefs to new ones. We do this here so we don't slow
	// things down by checking it every time we load the prefs
	//
	prefs::early_prefs_update();
	logger::mark("INIT", "******++++++======------******------======++++++******");
	logger::mark("INIT", "******++++++===== Hello Music Lovers =====++++++******");
}

if (array_key_exists('REQUEST_URI', $_SERVER)) {
	logger::core("REQUEST", $_SERVER['REQUEST_URI']);
}

//
// Check that the player we've been asked to talk to actually exists
//

if (!array_key_exists(prefs::$prefs['currenthost'], prefs::$prefs['multihosts'])) {
	logger::warn("INIT", prefs::$prefs['currenthost'],"is not defined in the hosts defs");
	foreach (prefs::$prefs['multihosts'] as $key => $obj) {
		logger::log("INIT", "  Using host ".$key);
		prefs::$prefs['currenthost'] = $key;
		setcookie('currenthost',prefs::$prefs['currenthost'],time()+365*24*60*60*10,'/');
		break;
	}
}

logger::core("INIT", "Using MPD Host ".prefs::$prefs['currenthost']);

if (!array_key_exists('currenthost', $_COOKIE)) {
	setcookie('currenthost',prefs::$prefs['currenthost'],time()+365*24*60*60*10,'/');
}

//
// Set the Global $skin which tells us which skin we're using
//

// NOTE. skin is NOT saved as a preference on the backend. It is set as a Cookie only.
// This is because saving it once as a preference would change the default for ALL new devices
// and we want to allow devices to intelligently select a default skin using Mobile_Detect

if(array_key_exists('skin', $_REQUEST)) {
	$skin = $_REQUEST['skin'];
	logger::core("INIT", "Request asked for skin: ".$skin);
} else if (array_key_exists('skin', $_COOKIE)) {
	$skin = $_COOKIE['skin'];
	logger::core("INIT", "Using skin as set by Cookie: ".$skin);
} else {
	logger::mark("INIT", "Detecting browser...");
	require_once('includes/Mobile_Detect.php');
	$md = new Mobile_Detect;
	if ($md->isMobile() || $md->isTablet()) {
		logger::info('INIT', 'Browser is a mobile browser');
		$skin = 'phone';
	} else {
		logger::info('INIT', 'Browser is a desktop browser or was not detected');
		$skin = 'desktop';
	}
}
$skin = trim($skin);
setcookie('skin', $skin, time()+365*24*60*60*10,'/');
logger::debug("INIT", "Using skin : ".$skin);

$romonitor_hack = true;
// ====================================================================

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
