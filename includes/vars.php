<?php

//
//---------------------------------------------------------------------------------------------
//
// Pretty much everything needs to include this file as the first thing it does
//
//---------------------------------------------------------------------------------------------
//

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

if (prefs::get_pref('collection_type') != null) {
	set_include_path('backends/sql/'.prefs::get_pref('collection_type').PATH_SEPARATOR.get_include_path());
}

// If we know what the player backend is, set the include path.
// This will be known if it's set as a Cookie.
if (prefs::get_pref('player_backend') != null) {
	set_include_path('player/'.prefs::get_pref('player_backend').PATH_SEPARATOR.get_include_path());
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
if (!defined('IS_ROMONITOR')) {
	if (!array_key_exists(prefs::currenthost(), prefs::get_pref('multihosts'))) {
		logger::warn("INIT", prefs::currenthost(),"is not defined in the hosts defs");
		foreach (prefs::get_pref('multihosts') as $key => $obj) {
			logger::log("INIT", "  Using host ".$key);
			prefs::set_pref(['currenthost' => $key]);
			break;
		}
	}

	logger::core("INIT", "Using MPD Host ".prefs::currenthost());

	if (!array_key_exists('currenthost', $_COOKIE)) {
		prefs::set_pref(['currenthost' => prefs::currenthost()]);
	}
}
//
// Work out which skin we're using
//

if (defined('IS_ROMONITOR')) {
	prefs::set_pref(['skin' => 'desktop']);
} else if(array_key_exists('skin', $_REQUEST)) {
	logger::log("INIT", "Request asked for skin: ".$_REQUEST['skin']);
	prefs::set_pref(['skin' => trim($_REQUEST['skin'])]);
} else if (prefs::skin() === null) {
	// This will be run the first time you open RompR. After that, skin will
	// be set as a Cookie, so also set the clickmode here.
	logger::mark("INIT", "Detecting browser...");
	require_once('includes/Mobile_Detect.php');
	$user_defaults = prefs::load_prefs_file('prefs/ui_defaults.var');
	// Create a defaults aray that only contains skin and clickmode and
	// only if they exist in the defaults file.
	$user_defaults = array_intersect_key($user_defaults, ['skin' => null, 'clickmode' => 'double']);
	$md = new Mobile_Detect;
	if ($md->isMobile() || $md->isTablet() || $md->isiOS()) {
		logger::info('INIT', 'Browser is a Mobile browser');
		prefs::set_pref(array_merge(['skin' => 'phone', 'clickmode' => 'single'], $user_defaults));
	} else {
		logger::info('INIT', 'Browser is a desktop browser or was not detected');
		prefs::set_pref(array_merge(['skin' => 'desktop', 'clickmode' => 'double'], $user_defaults));
	}
}

// Detect these because they might be there from an older install, but they won't work
if (prefs::skin() == 'tablet')
	prefs::set_pref(['skin' => 'phone']);

if (prefs::skin() == 'fruit')
	prefs::set_pref(['skin' => 'skypotato']);

if (!is_dir('skins/'.prefs::skin())) {
	prefs::set_pref(['skin' => 'desktop']);
}

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
