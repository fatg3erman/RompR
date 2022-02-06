<?php
define('ROMPR_IS_LOADING', true);

header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0");
header("Content-Type: text/html; charset=UTF-8");

require_once ("includes/vars.php");
require_once ("includes/functions.php");

//
// Do some important pre-load checks
//

if (!is_dir('skins/'.$skin)) {
	big_bad_fail('Skin '.$skin.' does not exist!');
}

if (file_exists('collection/collection.php')) {
	big_bad_fail('Remains of an earlier installation still exist. To install this version of RompЯ you must
		delete <b>everything except your albumart and prefs directories</b> and then copy the new version
		into your rompr directory.');
}

check_php_installation();

//
// Check to see if a specific player has been requested in the URL
//

if (isset($_GET['currenthost'])) {
    setcookie('currenthost',$_GET['currenthost'],time()+365*24*60*60*10,'/');
    setcookie('player_backend','',1,'/');
    prefs::$prefs['currenthost'] = $_GET['currenthost'];
    prefs::$prefs['player_backend'] = 'none';
    prefs::save();
	header("HTTP/1.1 307 Temporary Redirect");
   	header("Location: ".get_base_url());
    exit;
}

set_version_string();
upgrade_old_collections();

//
// See if we can use the SQL backend
//

logger::log('INIT', 'Checking Database Connection');

if (!array_key_exists('collection_type', prefs::$prefs)) {
	$success = data_base::probe_database();
	if ($success) {
		set_include_path('backends/sql/'.prefs::$prefs['collection_type'].PATH_SEPARATOR.get_include_path());
	} else {
		sql_init_fail("No Database Connection Was Possible");
	}
}
prefs::$database = new init_database();
list($result, $message) = prefs::$database->check_sql_tables();
if ($result == false) {
	sql_init_fail($message);
}

if (!prefs::$prefs['country_userset']) {
	// Set the country code from the browser, though this may not be accurate.
	// Later on we set it using geoip.
	prefs::$prefs['lastfm_country_code'] = language::get_browser_country();
}

logger::debug("INIT", $_SERVER['SCRIPT_FILENAME']);
logger::debug("INIT", $_SERVER['PHP_SELF']);

//
// Has the user asked for the setup screen?
//

if (array_key_exists('setup', $_REQUEST)) {
	$title = language::gettext("setup_request");
	include("setupscreen.php");
	exit();
}

//
// Attempt a player connection. This will set player_backend if it is not already set
//

logger::mark('INIT','Attempting to connect to player',prefs::$prefs['currenthost']);
if (array_key_exists('player_backend', $_COOKIE) && $_COOKIE['player_backend'] != '') {
	logger::mark('INIT','Player backend cookie is',$_COOKIE['player_backend']);
} else {
	logger::mark('INIT','Player backend cookie is not set');
}
$player = new base_mpd_player();
if ($player->is_connected()) {
	$mpd_status = $player->get_status();
	if (array_key_exists('error', $mpd_status)) {
		logger::warn("INIT", "MPD Password Failed or other status failure");
		$player->clear_error();
		if (strpos($mpd_status['error'], 'Failed to decode') !== false) {
			logger::warn('INIT', 'Looks like the error is a stream decode error. Ignoring it');
		} else {
			connect_fail(language::gettext("setup_connecterror").$mpd_status['error']);
		}
	}
} else {
	logger::error("INIT", "MPD Connection Failure");
	connect_fail(language::gettext("setup_connectfail"));
}
// If we're connected by a local socket we can read the music directory
logger::log('INIT', 'Getting Player Config');
$arse = $player->get_config();
if (is_array($arse) && array_key_exists('music_directory', $arse)) {
	prefs::set_music_directory($arse['music_directory']);
}
$player->close_mpd_connection();
$player->probe_http_api();

prefs::save();

//
// Do some initialisation of the backend directories
//
include ("includes/firstrun.php");

logger::log("INIT", "Initialisation done. Let's Boogie!");
logger::mark("CREATING PAGE", "******++++++======------******------======++++++******");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>RompЯ</title>
<link rel="shortcut icon" sizes="196x196" href="newimages/favicon-196.png" />
<link rel="shortcut icon" sizes="128x128" href="newimages/favicon-128.png" />
<link rel="shortcut icon" sizes="64x64" href="newimages/favicon-64.png" />
<link rel="shortcut icon" sizes="48x48" href="newimages/favicon-48.png" />
<link rel="shortcut icon" sizes="32x32" href="newimages/favicon-32.png" />
<link rel="shortcut icon" sizes="16x16" href="newimages/favicon-16.png" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=0" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="mobile-web-app-capable" content="yes" />
<?php
print '<script type="application/json" name="custom_radio_items">'."\n".json_encode(CUSTOM_RADIO_ITEMS)."\n</script>\n";
print '<script type="application/json" name="radio_combine_options">'."\n".json_encode(RADIO_COMBINE_OPTIONS)."\n</script>\n";
print '<script type="application/json" name="font_sizes">'."\n".json_encode(FONT_SIZES)."\n</script>\n";
print '<script type="application/json" name="cover_sizes">'."\n".json_encode(COVER_SIZES)."\n</script>\n";
print '<link rel="stylesheet" type="text/css" href="get_css.php?version='.$version_string."&skin=".$skin.'" />'."\n";

?>
<link rel="stylesheet" id="theme" type="text/css" />
<?php
logger::mark("INIT", "Reconfiguring the Forward Deflector Array");
$scripts = array(
	"jquery/jquery-3.6.0.min.js",
	// "jquery/jquery-migrate-3.3.2.js",
	"jquery/jquery-migrate-3.3.2.min.js",
	"ui/functions.js",
	"ui/prefs.js",
	"ui/language.php",
	"jquery/jquery-ui.min.js",
	"jshash-2.2/md5-min.js",
	"jquery/imagesloaded.pkgd.min.js",
	"jquery/masonry.pkgd.min.js",
	"includes/globals.js",
	"ui/widgets.js",
	"ui/uihelper.js",
	"skins/".$skin."/skin.js",
	"player/controller.js",
	"ui/collectionhelper.js",
	"player/player.js",
	"ui/playlist.js",
	"ui/readyhandlers.js",
	"ui/debug.js",
	"ui/uifunctions.js",
	"ui/metahandlers.js",
	"ui/clickfunctions.js",
	"ui/nowplaying.js",
	"ui/infobar2.js",
	"ui/coverscraper.js",
	"ui/favefinder.js",
	"ui/podcasts.js",
	"browser/info.js",
	"snapcast/snapcast.js"
);
foreach ($scripts as $i) {
	logger::log("INIT", "Loading ".$i);
	print '<script type="text/javascript" src="'.$i.'?version='.$version_string.'"></script>'."\n";
}
if (prefs::$prefs['mopidy_http_port'] === false) {
	print '<script type="text/javascript" src="player/mpd/checkprogress.js?version='.$version_string.'"></script>'."\n";
} else {
	print '<script type="text/javascript" src="player/mopidy/checkprogress.js?version='.$version_string.'"></script>'."\n";
}
$inc = glob("streamplugins/*.js");
foreach($inc as $i) {
	logger::log("INIT", "Loading ".$i);
	print '<script type="text/javascript" src="'.$i.'?version='.$version_string.'"></script>'."\n";
}

javascript_globals::print_globals();

$inc = glob("browser/helpers/*.js");
foreach($inc as $i) {
	logger::log("INIT", "Including Browser Helper ".$i);
	print '<script type="text/javascript" src="'.$i.'?version='.$version_string.'"></script>'."\n";
}
$inc = glob("browser/plugins/*.js");
ksort($inc);
foreach($inc as $i) {
	logger::log("INIT", "Including Info Panel Plugin ".$i);
	print '<script type="text/javascript" src="'.$i.'?version='.$version_string.'"></script>'."\n";
}
$inc = glob("radios/*.js");
ksort($inc);
foreach($inc as $i) {
	logger::log("INIT", "Including Smart Radio Plugin ".$i);
	print '<script type="text/javascript" src="'.$i.'?version='.$version_string.'"></script>'."\n";
}
$inc = glob("plugins/*.js");
foreach($inc as $i) {
	logger::log("INIT", "Including Plugin ".$i);
	print '<script type="text/javascript" src="'.$i.'?version='.$version_string.'"></script>'."\n";
}
if (prefs::$prefs['load_plugins_at_loadtime']) {
	$inc = glob("plugins/code/*.js");
	foreach($inc as $i) {
		logger::log("INIT", "DEVELOPMENT MODE : Including Plugin ".$i);
		print '<script type="text/javascript" src="'.$i.'?version='.$version_string.'"></script>'."\n";
	}
}

// Load any Javascript from the skin requirements file
$skinrequires = [];
if (file_exists('skins/'.$skin.'/skin.requires')) {
	$skinrequires = file('skins/'.$skin.'/skin.requires');
}
foreach ($skinrequires as $s) {
	$s = trim($s);
	$ext = strtolower(pathinfo($s, PATHINFO_EXTENSION));
	if ($ext == "js") {
		logger::log("INIT", "Including Skin Requirement ".$s);
		print '<script type="text/javascript" src="'.$s.'?version='.$version_string.'"></script>'."\n";
	}
}
?>

</head>

<?php
logger::log("LAYOUT", "Including skins/".$skin.'/skin.php');
include('skins/'.$skin.'/skin.php');
foreach (['post_max_size', 'max_file_uploads', 'upload_max_filesize'] as $i) {
 	print '<input type="hidden" name="'.$i.'" value="'.ini_get($i).'" />'."\n";
}
?>

</body>
</html>
<?php
logger::mark("INIT FINISHED", "******++++++======------******------======++++++******");

function check_php_installation() {
	if (version_compare(phpversion(), ROMPR_MIN_PHP_VERSION, '<' )) {
		big_bad_fail('Your version of PHP is too old. You need at least version '.ROMPR_MIN_PHP_VERSION);
	}
	foreach (['mbstring', 'PDO', 'curl', 'date', 'fileinfo', 'json', 'simpleXML'] as $x) {
		check_php_extension($x);
	}
}

function check_php_extension($x) {
	if (phpversion($x) === false) {
		big_bad_fail('Your installation of PHP is missing the '.$x.' extension');
	}
}

?>
