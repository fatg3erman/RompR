<?php

define('ROMPR_MAX_TRACKS_PER_TRANSACTION', 500);
define('ROMPR_COLLECTION_VERSION', 3);
define('ROMPR_IMAGE_VERSION', 4);
define('ROMPR_SCHEMA_VERSION', 47);
define('ROMPR_VERSION', '1.24');
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

define('COLLECTION_TYPE_MPD', 0);
define('COLLECTION_TYPE_MOPIDY', 1);

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

$connection = null;
$is_connected = false;
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
            'mopidy_slave' => false
        )
    ),
    'dev_mode' => false,
    'live_mode' => false,
    'collection_load_timeout' => 3600000,
    "mpd_host" => "localhost",
    "mpd_port" => 6600,
    "mpd_password" => "",
    "unix_socket" => '',

    // Things that could be set on a per-user basis but need to be known by the backend
    "displaycomposer" => true,
    "artistsatstart" => array("Various Artists","Soundtracks"),
    "nosortprefixes" => array("The"),
    "sortcollectionby" => "artist",
    "showartistbanners" => true,
    "google_api_key" => '',
    "google_search_engine_id" => '',
    "sync_lastfm_playcounts" => false,
    "sync_lastfm_at_start" => false,
    "last_lastfm_synctime" => time(),
    "lfm_importer_start_offset" => 0,
    "lfm_importer_last_import" => 0,

    // Things that are set as Cookies
    "sortbydate" => false,
    "notvabydate" => false,
    "currenthost" => 'Default',
    "player_backend" => "mpd",
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
    "playlistcontrolsvisible" => false,
    "sourceswidthpercent" => 22,
    "playlistwidthpercent" => 22,
    "downloadart" => true,
    "clickmode" => "double",
    "chooser" => "albumlist",
    "hide_albumlist" => false,
    "hide_filelist" => false,
    "hide_radiolist" => false,
    "hide_podcastslist" => false,
    "hide_playlistslist" => false,
    "hidebrowser" => false,
    "shownupdatewindow" => '',
    "scrolltocurrent" => false,
    "alarmtime" => 43200,
    "alarmon" => false,
    "alarmramp" => false,
    "alarm_ramptime" => 30,
    "alarm_snoozetime" => 8,
    "lastfmlang" => "default",
    "user_lang" => "en",
    "synctags" => false,
    "synclove" => false,
    "synclovevalue" => "5",
    "radiomode" => "",
    "radioparam" => "",
    "theme" => "Numismatist.css",
    "icontheme" => "Modern-Dark",
    "coversize" => "40-Large.css",
    "fontsize" => "04-Grande.css",
    "fontfamily" => "Nunito.css",
    "collectioncontrolsvisible" => false,
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
    "ratman_sortby" => 'Rating',
    "ratman_showletters" => false,
    "sleeptime" => 30,
    "sleepon" => false,
    "advanced_search_open" => false,
    "sortwishlistby" => 'artist',
    "player_in_titlebar" => false,
    "communityradiocountry" => 'united kingdom',
	"communityradiolanguage" => '',
	"communityradiotag" => '',
	"communityradiolistby" => 'country',
    "communityradioorderby" => 'name',
    "browser_id" => null,
    "playlistswipe" => true,
    "podcastcontrolsvisible" => true,
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
    "bgimgparms" => (object) array('dummy' => 'baby')
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
    'spotify_token_expires'
);

// ====================================================================
// Load Saved Preferences
loadPrefs();
$logger = new debug_logger($prefs['custom_logfile'], $prefs['debug_enabled']);

if (defined('ROMPR_IS_LOADING')) {
    debuglog("******++++++======------******------======++++++******","INIT",2);
}

if (array_key_exists('REQUEST_URI', $_SERVER)) {
    debuglog($_SERVER['REQUEST_URI'],"REQUEST",9);
}

if (!property_exists($prefs['multihosts'], $prefs['currenthost'])) {
    debuglog($prefs['currenthost']." is not defined in the hosts defs.","INIT",3);
    foreach ($prefs['multihosts'] as $key => $obj) {
        debuglog("  Using host ".$key,"INIT",3);
        $prefs['currenthost'] = $key;
        setcookie('currenthost',$prefs['currenthost'],time()+365*24*60*60*10,'/');
        break;
    }
}

debuglog("Using MPD Host ".$prefs['currenthost'],"INIT",9);

if (!array_key_exists('currenthost', $_COOKIE)) {
    setcookie('currenthost',$prefs['currenthost'],time()+365*24*60*60*10,'/');
}

set_player_connect_params();

// NOTE. skin is NOT saved as a preference on the backend. It is set as a Cookie only.
// This is because saving it once as a preference would change the default for ALL new devices
// and we want to allow devices to intelligently select a default skin using checkwindowsize.php
$skin = null;
if(array_key_exists('skin', $_REQUEST)) {
    $skin = $_REQUEST['skin'];
    debuglog("Request asked for skin: ".$skin,"INIT",9);
} else if (array_key_exists('skin', $_COOKIE)) {
    $skin = $_COOKIE['skin'];
    debuglog("Using skin as set by Cookie: ".$skin,"INIT",9);
}
if ($skin !== null) {
    $skin = trim($skin);
}

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
    global $prefs, $logger;
    if (file_exists('prefs/prefs.var')) {
        $fp = fopen('prefs/prefs.var', 'r');
        if($fp) {
            if (flock($fp, LOCK_SH)) {
                $sp = unserialize(fread($fp, 32768));
                flock($fp, LOCK_UN);
                fclose($fp);
                if ($sp === false) {
                    error_log("ERROR!              : COULD NOT LOAD PREFS");
                    exit(1);
                }
                $prefs = array_replace($prefs, $sp);

                foreach ($_COOKIE as $a => $v) {
                    if (array_key_exists($a, $prefs)) {
                        switch ($a) {
                            case 'debug_enabled':
                                $logger->setLevel($v);
                                // Fall through to default

                            default:
                                if ($v === 'false') { $v = false; }
                                if ($v === 'true') { $v = true; }
                                $prefs[$a] = $v;
                                break;

                        }
                        if ($prefs['debug_enabled'] > 8) {
                            $module = "COOKIEPREFS";
                            $in = str_repeat(" ", 20 - strlen($module));
                            $pid = getmypid();
                            $in2 = str_repeat(" ", 8 - strlen($pid));
                            $out = "Pref ".$a." is set by Cookie  - Value : ".$v;
                            error_log($pid.$in2.$module.$in.": ".$out,0);
                        }
                    }
                }
          } else {
              error_log("ERROR!              : COULD NOT GET READ FILE LOCK ON PREFS FILE");
              exit(1);
          }
      } else {
          error_log("ERROR!              : COULD NOT GET HANDLE FOR PREFS FILE");
          exit(1);
      }
   }
}

function set_music_directory($dir) {
    $prefs['music_directory_albumart'] = rtrim($dir, '/');
    debuglog("Creating Album Art SymLink to ".$dir,"SAVEPREFS");
    if (is_link("prefs/MusicFolders")) {
        system ("unlink prefs/MusicFolders");
    }
    system ('ln -s "'.$dir.'" prefs/MusicFolders');
}

class debug_logger {

    public function __construct($outfile, $level = 8) {
        $this->outfile = $outfile;
        $this->loglevel = intval($level);
        $this->debug_colours = array(
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
            # lihgt grey
            5 => 37,
            # light yellow
            6 => 93,
            # green
            7 => 32,
            # white
            8 => 97,
            # light blue
            9 => 94
        );
    }

    public function log($out, $module, $level) {
        if ($level > $this->loglevel || $level > 9 || $level < 1) return;
        $in = str_repeat(" ", 20 - strlen($module));
        $pid = getmypid();
        $in2 = str_repeat(" ", 8 - strlen($pid));
        if ($this->outfile != "") {
            // Two options here - either colour by level
            // $col = $this->debug_colours[$level];
            // or attempt to have different processes in different colours.
            // This helps to keep track of things when multiple concurrent things are happening at once.
            $col = $this->debug_colours[$pid % 10];
            error_log("\033[90m".strftime('%T').' : '.$in2.$pid." : \033[".$col."m".$module.$in.$out."\033[0m\n",3,$this->outfile);
        } else {
            error_log($pid.$in2.$module.$in.": ".$out,0);
        }

    }

    public function setLevel($level) {
        $this->loglevel = intval($level);
    }
}

function debuglog($text, $module = "JOHN WAYNE", $level = 7) {
    global $logger;
    $logger->log($text, $module, $level);
}

function set_player_connect_params() {
	global $prefs;
	$prefs['mpd_host'] = $prefs['multihosts']->{$prefs['currenthost']}->host;
	$prefs['mpd_port'] = $prefs['multihosts']->{$prefs['currenthost']}->port;
	$prefs['mpd_password'] = $prefs['multihosts']->{$prefs['currenthost']}->password;
	$prefs['unix_socket'] = $prefs['multihosts']->{$prefs['currenthost']}->socket;
    if (property_exists($prefs['multihosts']->{$prefs['currenthost']}, 'mopidy_slave')) {
        $prefs['mopidy_slave'] = $prefs['multihosts']->{$prefs['currenthost']}->mopidy_slave;
    } else {
        // Catch the case where we haven't yet upgraded the player defs
        $prefs['mopidy_slave'] = false;
    }
}

function upgrade_host_defs($ver) {
    global $prefs;
    foreach ($prefs['multihosts'] as $key => $value) {
        switch ($ver) {
            case 45:
                $prefs['multihosts']->{$key}->mopidy_slave = false;
                break;
        }
    }
    savePrefs();
    set_player_connect_params();
}

?>
