<?php
const IS_ROMONITOR = true;
require_once ("includes/vars.php");
require_once ("includes/functions.php");
$opts = getopt('', ['snooze:']);
prefs::set_pref($opts);

logger::mark("SNOOZE", "Initialising Snooze For Index", prefs::get_pref('snooze'));

prefs::$database = new timers();
$alarm = prefs::$database->get_alarm(prefs::get_pref('snooze'));
prefs::$database->update_snooze_pid_for_alarm(prefs::get_pref('snooze'), getmypid());
prefs::$database->close_database();
prefs::$database = null;
logger::mark("SNOOZE", "Player is",$alarm['Player']);
prefs::set_session_pref(['currenthost' => $alarm['Player']]);

logger::log('SNOOZE', 'Sleeping For',prefs::get_pref('alarm_snoozetime'),'minutes');

$sleeptime = (int) prefs::get_pref('alarm_snoozetime') * 60;
sleep($sleeptime);

// Update the database now so the UI reads the snooze PID when it reacts
prefs::$database = new timers();
prefs::$database->update_snooze_pid_for_alarm(prefs::get_pref('snooze'), null);
prefs::$database->close_database();

$player = new base_mpd_player();
$player->do_command_list(['play']);
$player->close_mpd_connection();

?>
