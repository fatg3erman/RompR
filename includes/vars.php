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
	'browser/apis',
	'plugins/backend',
	'radios/backend'
);
foreach (CLASS_DIRS as $d) {
	set_include_path($d.PATH_SEPARATOR.get_include_path());
}
spl_autoload_extensions('.class.php');
spl_autoload_register();

//
//---------------------------------------------------------------------------------------------
//

include('includes/constants.php');

//
// Load Saved Preferences
//
prefs::load();
prefs::check_setup_values();

if (array_key_exists('collection_type', prefs::$prefs)) {
	set_include_path('backends/sql/'.prefs::$prefs['collection_type'].PATH_SEPARATOR.get_include_path());
}

if (prefs::$prefs['player_backend'] == 'mpd' || prefs::$prefs['player_backend'] == 'mopidy') {
	set_include_path('player/'.prefs::$prefs['player_backend'].PATH_SEPARATOR.get_include_path());
} else {
	logger::log('INIT', 'Player backend pref is not set at load time');
}

if (defined('ROMPR_IS_LOADING')) {
	//
	// Update old prefs to new ones. We do this here so we don't slow
	// things down by checking it every time we load the prefs
	//
	logger::mark("INIT", "******++++++======------******------======++++++******");
	prefs::early_prefs_update();
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
if (is_dir('skins/'.$skin)) {
	setcookie('skin', $skin, time()+365*24*60*60*10,'/');
	logger::core("INIT", "Using skin : ".$skin);
	set_include_path('skins/'.$skin.PATH_SEPARATOR.get_include_path());
	// index.php wil take us to the error screen if skin doesn't exist. It's important we don't set the cookie
}
$setup_error = null;
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
