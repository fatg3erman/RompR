<?php
const IS_ROMONITOR = true;
require_once ("includes/vars.php");
require_once ("includes/functions.php");
$opts = getopt('', ['currenthost:', 'sleeptime:']);
prefs::set_session_pref($opts);

logger::mark("SLEEPTIMER", "Using Player",prefs::currenthost());
logger::log('SLEEPTIMER', 'Sleeping for',format_time(prefs::get_pref('sleeptime')));

sleep((int) prefs::get_pref('sleeptime'));

logger::mark(prefs::currenthost(), 'Starting Sleep Timer Volume Ramp');

$player = new base_mpd_player();
$mpd_status = $player->do_command_list(['status']);
$volume = $mpd_status['volume'];

prefs::$database = new timers();
if ($mpd_status['state'] == 'play') {
	$player->ramp_volume($volume, 0, 60);
	// Mark the timer as finished. The UI will react to the state change
	// callback when we pause it, which will mark the timer as not running.
	prefs::$database->sleep_timer_finished();
	$player->do_command_list(['pause']);
	sleep(1);
	$player->do_command_list(['setvol '.$volume]);
} else {
	prefs::$database->sleep_timer_finished();
}

prefs::$database->close_database();

?>