<?php

chdir('../..');
// I *think* the FPM process will keep running until nginx kills it, because we start
// a process. The process is detached with nohup so it won't die when the FPM process
// exits, so we want it to exit quickly so it returns to the pool.
set_time_limit(10);
require_once ("includes/vars.php");
require_once ("includes/functions.php");
prefs::$database = new timers();

if (!array_key_exists('poll', $_REQUEST)) {
	$enable = ($_REQUEST['enable'] == 1);
	$sleeptime = (array_key_exists('sleeptime', $_REQUEST)) ? $_REQUEST['sleeptime'] : 1;

	logger::mark("SLEEP", "Using Player ".prefs::$prefs['currenthost']);
	logger::log('SLEEP', 'Enable is',$enable,'Sleeptime is',$sleeptime);

	prefs::$database->set_sleep_timer($enable, $sleeptime);
}

$state = prefs::$database->get_sleep_timer();
header('Content-Type: application/json');
print json_encode($state);

?>