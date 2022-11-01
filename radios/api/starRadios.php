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

if ($result) {
	$player->do_command_list(['play']);
	header('HTTP/1.1 204 No Content');
} else {
	header('HTTP/1.1 500 Internal Server Error');
}
$player->close_mpd_connection();

?>