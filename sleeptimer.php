<?php
const IS_ROMONITOR = true;
require_once ("includes/vars.php");
require_once ("includes/functions.php");
$opts = getopt('', ['currenthost:', 'sleeptime:']);
if (is_array($opts)) {
	foreach($opts as $key => $value) {
		prefs::$prefs[$key] = $value;
	}
}
logger::mark("SLEEPTIMER", "Using Player",prefs::$prefs['currenthost']);

sleep((int) prefs::$prefs['sleeptime']);

logger::mark(prefs::$prefs['currenthost'], 'Starting Sleep Timer Volume Ramp');

$player = new base_mpd_player();
$player = new player();
$mpd_status = $player->do_command_list(['status']);
$volume = $mpd_status['volume'];

if ($mpd_status['state'] == 'play') {
	$player->ramp_volume($volume, 0, 60);
	$player->do_command_list(['pause']);
	sleep(1);
	$player->do_command_list(['setvol '.$volume]);
}

prefs::$database = new timers();
prefs::$database->sleep_timer_finished(prefs::$prefs['currenthost']);
prefs::$database->close_database();

?>