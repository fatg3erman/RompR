<?php
const IS_ROMONITOR = true;
require_once ("includes/vars.php");
require_once ("includes/functions.php");
$opts = getopt('', ['currenthost:']);
if (is_array($opts)) {
	foreach($opts as $key => $value) {
		logger::core("ROMONITOR", $key,'=',$value);
		prefs::$prefs[$key] = $value;
	}
}
logger::mark("ROMONITOR", "Using Player ".prefs::$prefs['currenthost']);

// Probe the player type
while (prefs::$prefs['player_backend'] != 'mpd' && prefs::$prefs['player_backend'] != 'mopidy') {
	logger::warn('ROMONITOR', 'Probing Player type for player',prefs::$prefs['currenthost']);
	$player = new base_mpd_player();
	if (prefs::$prefs['player_backend'] != 'mpd' && prefs::$prefs['player_backend'] != 'mopidy') {
		logger::warn('ROMONITOR', 'Could not connect to player',prefs::$prefs['currenthost'],'sleeping for 5 minutes');
		sleep(300);
	}
}

$player = new player();
define('CURRENTHOST_SAVE', prefs::$prefs['currenthost']);
define('PLAYER_BACKEND_SAVE', prefs::$prefs['player_backend']);
register_shutdown_function('close_mpd');
$player->set_consume_state();
$player->idle_system_loop();

function close_mpd() {
	global $player;
	$player->close_mpd_connection();
}

?>
