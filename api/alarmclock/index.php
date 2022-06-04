<?php

chdir('../..');
// I *think* the FPM process will keep running until nginx kills it, because we start
// a process. The process is detached with nohup so it won't die when the FPM process
// exits, so we want it to exit quickly so it returns to the pool.
set_time_limit(10);
require_once ("includes/vars.php");
require_once ("includes/functions.php");
prefs::$database = new timers();
logger::mark("ALARMS", "Using Player ".prefs::currenthost());

if (array_key_exists('enable', $_REQUEST)) {
	prefs::$database->toggle_alarm($_REQUEST['index'], $_REQUEST['enable']);
	header('HTTP/1.1 204 No Content');
} else if (array_key_exists('remove', $_REQUEST)) {
	prefs::$database->remove_alarm($_REQUEST['index']);
	header('HTTP/1.1 204 No Content');
} else if (array_key_exists('stop', $_REQUEST)) {
	prefs::$database->stop_alarms_for_player();
	header('HTTP/1.1 204 No Content');
} else if (array_key_exists('snooze', $_REQUEST)) {
	prefs::$database->snooze_alarms_for_player($_REQUEST['snooze']);
	header('HTTP/1.1 204 No Content');
} else if (array_key_exists('populate', $_REQUEST)) {
	$state = prefs::$database->get_all_alarms();
	header('Content-Type: application/json');
	print json_encode($state);
} else {
	$new_data = json_decode(file_get_contents('php://input'), true);
	prefs::$database->edit_alarm($new_data);
}

?>