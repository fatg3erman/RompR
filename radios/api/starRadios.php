<?php
chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
// prefs::set_pref(['currenthost' => prefs::currenthost()]);
$params = json_decode(file_get_contents('php://input'), true);
foreach ($params as $k => $v) {
	logger::log('SMARTRADIO', $k,'=',$v);
}
$pwd = getcwd();
prefs::set_radio_params($params);
// prepare_smartradio will save the prefs, so no need to do it here
$player = new player();
$player->prepare_smartradio();

// Only add 1 track to begin with. The Mopidy search stations need to
// start populating and will take a long time to get going if smartradio_chunksize
// is big. We want playback to start ASAP.
$result = $player->do_smartradio(1);
$player->do_command_list(['play']);

if ($result) {
	// We start a new romonitor process to populate the play queue.
	// We do this because the stations that use Mopidy search can be slow and can interfere
	// with the normal operation of romonitor. Why use romonitor? Because we want to re-use
	// idle_system_loop and it's pointless duplicating code.
	// Put radiomode first so it doesn't confuse rompr_backend, which checks for running processes with currenthost first.
	$cmdline = $pwd.'/romonitor.php --radiomode='.rawurlencode($params['radiomode']).' --radioparam='.rawurlencode($params['radioparam']).' --currenthost '.rawurlencode(prefs::currenthost());
	logger::log('SMARTRADIO', 'Starting populator process');
	$result = start_process($cmdline);
}

if ($result) {
	header('HTTP/1.1 204 No Content');
} else {
	header('HTTP/1.1 500 Internal Server Error');
}

?>