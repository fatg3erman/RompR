<?php
const IS_ROMONITOR = true;
require_once ("includes/vars.php");
require_once ("includes/functions.php");
$opts = getopt('', ['snooze:']);
if (is_array($opts)) {
	foreach($opts as $key => $value) {
		prefs::$prefs[$key] = $value;
	}
}

logger::mark("SNOOZE", "Initialising Snooze For Index", prefs::$prefs['snooze']);

prefs::$database = new timers();
$alarm = prefs::$database->get_alarm(prefs::$prefs['snooze']);
prefs::$database->update_snooze_pid_for_alarm(prefs::$prefs['snooze'], getmypid());
prefs::$database->close_database();
prefs::$database = null;
logger::mark("SNOOZE", "Player is",$alarm['Player']);
prefs::set_session_pref(['currenthost' => $alarm['Player']]);

logger::log('SNOOZE', 'Sleeping For',prefs::$prefs['alarm_snoozetime'],'minutes');

$sleeptime = (int) prefs::$prefs['alarm_snoozetime'] * 60;
sleep($sleeptime);

// Update the database now so the UI reads the snooze PID when it reacts
prefs::$database = new timers();
prefs::$database->update_snooze_pid_for_alarm(prefs::$prefs['snooze'], null);
prefs::$database->close_database();

$player = new base_mpd_player();
$player->do_command_list(['play']);
$player->close_mpd_connection();

?>
