<?php

chdir('../..');
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