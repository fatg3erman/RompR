<?php

check_timezone();

//
//---------------------------------------------------------------------------------------------
//

CONST CLASS_DIRS = array(
	'includes',
	'skins',
	'backends/sql',
	'collection',
	'collection/sortby',
	'player',
	'util_classes',
	'browser/apis',
	'plugins/backend',
	'radios/backend',
	'phpQuery'
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

if (prefs::$prefs['player_backend'] != null) {
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

if (!array_key_exists(prefs::currenthost(), prefs::$prefs['multihosts'])) {
	logger::warn("INIT", prefs::currenthost(),"is not defined in the hosts defs");
	foreach (prefs::$prefs['multihosts'] as $key => $obj) {
		logger::log("INIT", "  Using host ".$key);
		prefs::set_static_pref(['currenthost' => $key]);
		break;
	}
}

logger::core("INIT", "Using MPD Host ".prefs::currenthost());

if (!array_key_exists('currenthost', $_COOKIE)) {
	prefs::set_static_pref(['currenthost' => prefs::currenthost()]);
}

//
// Work out which skin we're using
//

if(array_key_exists('skin', $_REQUEST)) {
	if (is_dir('skins/'.$_REQUEST['skin'])) {
		logger::log("INIT", "Request asked for skin: ".$_REQUEST['skin']);
		prefs::set_static_pref(['skin' => trim($_REQUEST['skin'])]);
	}
} else if (prefs::skin() === null && defined('IS_ROMONITOR')) {
	prefs::set_static_pref(['skin' => 'desktop']);
} else if (prefs::skin() === null) {
	logger::mark("INIT", "Detecting browser...");
	require_once('includes/Mobile_Detect.php');
	$md = new Mobile_Detect;
	if ($md->isMobile() || $md->isTablet()) {
		logger::info('INIT', 'Browser is a mobile browser');
		prefs::set_static_pref(['skin' => 'phone', 'clickmode' => 'single']);
	} else {
		logger::info('INIT', 'Browser is a desktop browser or was not detected');
		prefs::set_static_pref(['skin' => 'desktop', 'clickmode' => 'double']);
	}
}

if (prefs::skin() == 'tablet')
	prefs::set_static_pref(['skin' => 'phone']);

if (prefs::skin() == 'fruit')
	prefs::set_static_pref(['skin' => 'skypotato']);

set_include_path('skins/'.prefs::skin().PATH_SEPARATOR.get_include_path());

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

function check_timezone() {
	if (($dtz = ini_get('date.timezone'))) {
	    // Yes we probably should rely on this first but most people just set it to UTC
	    // if they set it at all.
			// error_log('Timezeon set from ini file to '.$dtz);
	} else {
		$timezone = '';
	    exec('date +%Z', $timezone, $retval);
		if (count($timezone) > 0)
			$timezone = trim(array_shift($timezone));

	    if ($retval == 0 && $timezone != '') {
	    	$zone = timezone_name_from_abbr($timezone);
	        date_default_timezone_set($zone);
			// error_log('Timezeon set from date command to '.$zone);
	    } else {
	        date_default_timezone_set('UTC');
			// error_log('Timezeon set to UTC');
	    }
	}
}


?>
