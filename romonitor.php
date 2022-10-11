<?php
const IS_ROMONITOR = true;
require_once ("includes/vars.php");
require_once ("includes/functions.php");
$opts = [
	'currenthost' => null,
	'radiomode' => false,
	'radioparam' => false
];
$params = getopt('', ['currenthost:', 'radiomode::', 'radioparam::']);
$params = array_map('rawurldecode', $params);
$opts = array_merge($opts, $params);
prefs::set_pref(['currenthost' => $opts['currenthost']]);

logger::mark("ROMONITOR", "Using Player ".prefs::currenthost());
if ($opts['radiomode']) {
	logger::mark('ROMONITOR', 'Running',$opts['radiomode'], $opts['radioparam']);
}
// Probe the player type
while (prefs::get_pref('player_backend') == null) {
	logger::warn('ROMONITOR', 'Probing Player type for player',prefs::currenthost());
	$player = new base_mpd_player();
	if (prefs::get_pref('player_backend') == null) {
		logger::warn('ROMONITOR', 'Could not connect to player',prefs::currenthost(),'sleeping for 5 minutes');
		sleep(300);
	}
}

$player->close_mpd_connection();
$player = new player();
register_shutdown_function('close_mpd');
if ($opts['radiomode'] === false)
	$player->set_consume_state();

$player->idle_system_loop($opts['radiomode'], $opts['radioparam']);

function close_mpd() {
	global $player;
	$player->close_mpd_connection();
}

?>
