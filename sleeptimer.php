<?php
const IS_ROMONITOR = true;
// This is required for pcntl_signal to work
pcntl_async_signals(true);
require_once ("includes/vars.php");
require_once ("includes/functions.php");
$opts = getopt('', ['currenthost:', 'sleeptime:', 'fadetime:']);
prefs::set_pref($opts);
$ramping = false;
$volume = 100;
$player = null;

// If we are terminated while ramping we need to reset the volume
pcntl_signal(SIGTERM, "term_handler");

logger::mark("SLEEPTIMER", "Using Player",prefs::currenthost());
logger::info('SLEEPTIMER', 'Sleeping for',format_time(prefs::get_pref('sleeptime')));

sleep((int) prefs::get_pref('sleeptime'));

logger::mark(prefs::currenthost(), 'Starting Sleep Timer Volume Ramp over', prefs::get_pref('fadetime'), 'seconds');

$player = new base_mpd_player();
$mpd_status = $player->do_command_list(['status']);
$volume = $mpd_status['volume'];

prefs::$database = new timers();
if ($mpd_status['state'] == 'play') {
	$ramping = true;
	$player->ramp_volume($volume, 0, prefs::get_pref('fadetime'));
	// Mark the timer as finished. The UI will react to the state change
	// callback when we pause it, which will mark the timer as not running.
	prefs::$database->sleep_timer_finished();
	$player->do_command_list(['pause']);
	sleep(1);
	$player->do_command_list(['setvol '.$volume]);
	$ramping = false;
} else {
	prefs::$database->sleep_timer_finished();
}

prefs::$database->close_database();

function term_handler($signo) {
	global $ramping, $volume, $player;
	if ($ramping) {
		logger::log(prefs::currenthost(), "Sleep Timer Terminated while ramping. Resetting volume to", $volume);
		$player->do_command_list(['setvol '.$volume]);
	}
	exit(0);
}

?>
