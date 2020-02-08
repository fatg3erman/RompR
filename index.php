<?php
define('ROMPR_IS_LOADING', true);

require_once ("includes/vars.php");

//
// Check to see if this is a mobile browser
//
if ($skin === null) {
	logger::mark("INIT", "Detecting browser...");
	require_once('includes/Mobile_Detect.php');
	$md = new Mobile_Detect;
	if ($md->isMobile() || $md->isTablet()) {
		logger::info('INIT', 'Browser is a mobile browser');
		$skin = 'phone';
	} else {
		logger::info('INIT', 'Browser is a desktop browser');
		$skin = 'desktop';
	}
	setcookie('skin', $skin, time()+365*24*60*60*10,'/');
}

logger::debug("INIT", "Using skin : ".$skin);

if (!is_dir('skins/'.$skin)) {
	print '<h3>Skin '.htmlspecialchars($skin).' does not exist!</h3>';
	exit(0);
}

$skinrequires = array();
if (file_exists('skins/'.$skin.'/skin.requires')) {
	logger::log("INIT", "Loading Skin Requirements File");
	$requires = file('skins/'.$skin.'/skin.requires');
	foreach ($requires as $r) {
		if (substr($r,0,1) != '#') {
			$skinrequires[] = $r;
		}
	}
}

require_once ("includes/functions.php");
require_once ("international.php");
set_version_string();
require_once ("skins/".$skin."/ui_elements.php");

//
// See if there are any POST values from the setup screen
//

if (array_key_exists('currenthost', $_POST)) {
	foreach (array('cleanalbumimages', 'do_not_show_prefs', 'separate_collections') as $p) {
		if (array_key_exists($p, $_POST)) {
			$_POST[$p] = true;
		} else {
			$_POST[$p] = false;
		}
	}
	foreach ($_POST as $i => $value) {
		logger::mark("INIT", "Setting Pref ".$i." to ".$value);
		$prefs[$i] = $value;
	}
	setcookie('currenthost',$prefs['currenthost'],time()+365*24*60*60*10,'/');

	$mopidy_slave = false;
	if (property_exists($prefs['multihosts']->{$prefs['currenthost']}, 'mopidy_slave')) {
		$mopidy_slave = $prefs['multihosts']->{$prefs['currenthost']}->mopidy_slave;
	}
	$prefs['multihosts']->{$prefs['currenthost']} = (object) array(
			'host' => $prefs['mpd_host'],
			'port' => $prefs['mpd_port'],
			'password' => $prefs['mpd_password'],
			'socket' => $prefs['unix_socket'],
			'mopidy_slave' => $mopidy_slave,
			'radioparams' => (object) array (
				"radiomode" => "",
				"radioparam" => "",
				"radiomaster" => "",
				"radioconsume" => 0
			)
	);
	savePrefs();
}

$collections = glob('prefs/collection_{mpd,mopidy}.sq3', GLOB_BRACE);
if (count($collections) > 0) {
	logger::mark('UPGRADE', 'Old-style twin sqlite collections found');
	@mkdir('prefs/oldcollections');
	$time = 0;
	$newest = null;
	foreach ($collections as $file) {
		if (filemtime($file) > $time) {
			$newest = $file;
			$time = filemtime($file);
		}
	}
	logger::mark('UPGRADE', "Newest file is",$newest);
	copy($newest, 'prefs/collection.sq3');
	foreach ($collections as $file) {
		logger::log('UPGRADE', 'Moving',$file,'to','prefs/oldcollections/'.basename($file));
		rename($file, 'prefs/oldcollections/'.basename($file));
	}
}

logger::debug("INIT", $_SERVER['SCRIPT_FILENAME']);
logger::debug("INIT", $_SERVER['PHP_SELF']);

//
// Has the user asked for the setup screen?
//

if (array_key_exists('setup', $_REQUEST)) {
	$title = get_int_text("setup_request");
	include("setupscreen.php");
	exit();
}

require_once ('player/mpd/mpdinterface.php');
logger::mark('INIT','Attempting to connect to player',$prefs['currenthost']);
if (array_key_exists('player_backend', $_COOKIE)) {
	logger::mark('INIT','Player backend cookie is',$_COOKIE['player_backend']);
} else {
	logger::mark('INIT','Player backend cookie is not set');
}
$player = new base_mpd_player();
if ($player->is_connected()) {
	$mpd_status = $player->get_status();
	if (array_key_exists('error', $mpd_status)) {
		logger::warn("INIT", "MPD Password Failed or other status failure");
		connect_fail(get_int_text("setup_connecterror").$mpd_status['error']);
	}
} else {
	logger::error("INIT", "MPD Connection Failure");
	connect_fail(get_int_text("setup_connectfail"));
}
// If we're connected by a local socket we can read the music directory
$arse = $player->get_config();
if (array_key_exists('music_directory', $arse)) {
	set_music_directory($arse['music_directory']);
}
$player->close_mpd_connection();
//
// See if we can use the SQL backend
//

include( "backends/sql/connect.php");
if (array_key_exists('collection_type', $prefs)) {
	connect_to_database();
} else {
	probe_database();
	include("backends/sql/".$prefs['collection_type']."/specifics.php");
}
if (!$mysqlc) {
	sql_init_fail("No Database Connection Was Possible");
}

list($result, $message) = check_sql_tables();
if ($result == false) {
	sql_init_fail($message);
}

savePrefs();
//
// Do some initialisation and cleanup of the Apache backend
//
include ("includes/firstrun.php");
logger::trace("INIT", "Last Last.FM Sync Time is ".$prefs['last_lastfm_synctime'].", ".date('r', $prefs['last_lastfm_synctime']));
logger::log("INIT", "Initialisation done. Let's Boogie!");
logger::mark("CREATING PAGE", "******++++++======------******------======++++++******");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>RompЯ</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />
<link rel="shortcut icon" sizes="196x196" href="newimages/favicon-196.png" />
<link rel="shortcut icon" sizes="128x128" href="newimages/favicon-128.png" />
<link rel="shortcut icon" sizes="64x64" href="newimages/favicon-64.png" />
<link rel="shortcut icon" sizes="48x48" href="newimages/favicon-48.png" />
<link rel="shortcut icon" sizes="16x16" href="newimages/favicon.ico" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=0" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="mobile-web-app-capable" content="yes" />
<?php
print '<script type="application/json" name="translations">'."\n".json_encode($translations)."\n</script>\n";
$safeprefs = array();
foreach ($prefs as $p => $v) {
	if (!in_array($p, $private_prefs)) {
		$safeprefs[$p] = $v;
	}
}
print '<script type="application/json" name="prefs">'."\n".json_encode($safeprefs)."\n</script>\n";
print '<link rel="stylesheet" type="text/css" href="css/layout-january.css?version='.time().'" />'."\n";
print '<link rel="stylesheet" type="text/css" href="skins/'.$skin.'/skin.css?version='.time().'" />'."\n";
if (file_exists('skins/'.$skin.'/controlbuttons.css')) {
	print '<link rel="stylesheet" type="text/css" href="skins/'.$skin.'/controlbuttons.css?version='.time().'" />'."\n";
}
foreach ($skinrequires as $s) {
	$s = trim($s);
	$ext = strtolower(pathinfo($s, PATHINFO_EXTENSION));
	if ($ext == "css") {
		logger::mark("INIT", "Including Skin Requirement ".$s);
		print '<link rel="stylesheet" type="text/css" href="'.$s.'?version='.time().'" />'."\n";
	}
}
?>
<link rel="stylesheet" id="theme" type="text/css" />
<?php
logger::mark("INIT", "Reconfiguring the Forward Deflector Array");
$scripts = array(
	"jquery/jquery-3.4.1.min.js",
	"jquery/jquery-migrate-3.0.1.js",
	"ui/functions.js",
	"ui/prefs.js",
	"ui/language.js",
	"jquery/jquery-ui.min-19.1.18.js",
	"jshash-2.2/md5-min.js",
	"jquery/imagesloaded.pkgd.min.js",
	"jquery/masonry.pkgd.min.js",
	"includes/globals.js",
	"ui/widgets.js",
	"ui/uihelper.js",
	"skins/".$skin."/skin.js",
	"player/mpd/controller.js",
	"ui/collectionhelper.js",
	"player/player.js",
	"ui/playlist.js",
	"ui/readyhandlers.js",
	"ui/debug.js",
	"ui/uifunctions.js",
	"ui/metahandlers.js",
	"ui/clickfunctions.js",
	"ui/lastfm.js",
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
$inc = glob("streamplugins/*.js");
foreach($inc as $i) {
	logger::log("INIT", "Loading ".$i);
	print '<script type="text/javascript" src="'.$i.'?version='.$version_string.'"></script>'."\n";
}

logger::log("LAYOUT", "Including skins/".$skin.'/skinvars.php');
include('skins/'.$skin.'/skinvars.php');
include('includes/globals.php');

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
if ($use_smartradio) {
	$inc = glob("radios/*.js");
	ksort($inc);
	foreach($inc as $i) {
		logger::log("INIT", "Including Smart Radio Plugin ".$i);
		print '<script type="text/javascript" src="'.$i.'?version='.$version_string.'"></script>'."\n";
	}
}
if ($use_plugins) {
	$inc = glob("plugins/*.js");
	foreach($inc as $i) {
		logger::log("INIT", "Including Plugin ".$i);
		print '<script type="text/javascript" src="'.$i.'?version='.$version_string.'"></script>'."\n";
	}
	if ($prefs['load_plugins_at_loadtime']) {
		$inc = glob("plugins/code/*.js");
		foreach($inc as $i) {
			logger::log("INIT", "DEVELOPMENT MODE : Including Plugin ".$i);
			print '<script type="text/javascript" src="'.$i.'?version='.$version_string.'"></script>'."\n";
		}
	}
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
?>

</body>
</html>
<?php
logger::mark("INIT FINISHED", "******++++++======------******------======++++++******");

function connect_fail($t) {
	global $title, $prefs;
	logger::warn("INIT", "MPD Connection Failed");
	$title = $t;
	include("setupscreen.php");
	exit();
}

?>
