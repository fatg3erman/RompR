<?php
chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
$params = json_decode(file_get_contents('php://input'), true);
foreach ($params as $k => $v) {
	logger::info('SMARTRADIO', $k,'=',$v);
}
$pwd = getcwd();
$params['prepared'] = 0;
prefs::set_radio_params($params);
// prepare_smartradio will save the prefs, so no need to do it here
$player = new player();
$player->prepare_smartradio();
prefs::set_radio_params(['prepared' => 1]);

// Only add 1 track to begin with. The Mopidy search stations need to
// start populating and will take a long time to get going if smartradio_chunksize
// is big. We want playback to start ASAP.
$result = $player->do_smartradio(1);

if ($result) {
	$player->do_command_list(['play']);
	http_response_code(204);
} else {
	http_response_code(500);
}
$player->close_mpd_connection();

?>