<?php
const IS_ROMONITOR = true;
require_once ("includes/vars.php");
require_once ("includes/functions.php");
$opts = getopt('', ['currenthost:']);
if (is_array($opts)) {
	prefs::set_session_pref($opts);
}
logger::mark("ROMONITOR", "Using Player ".prefs::currenthost());

// Probe the player type
while (prefs::$prefs['player_backend'] == null) {
	logger::warn('ROMONITOR', 'Probing Player type for player',prefs::currenthost());
	$player = new base_mpd_player();
	if (prefs::$prefs['player_backend'] == null) {
		logger::warn('ROMONITOR', 'Could not connect to player',prefs::currenthost(),'sleeping for 5 minutes');
		sleep(300);
	}
}

$player->close_mpd_connection();
$player = new player();
register_shutdown_function('close_mpd');
$player->set_consume_state();
$player->idle_system_loop();

function close_mpd() {
	global $player;
	$player->close_mpd_connection();
}

?>
