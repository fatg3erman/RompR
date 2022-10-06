<?php
chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
// prefs::set_pref(['currenthost' => prefs::currenthost()]);
$params = json_decode(file_get_contents('php://input'), true);
foreach ($params as $k => $v) {
	logger::log('SMARTRADIO', $k,'=',$v);
}
prefs::set_radio_params($params);
// prepare_smartradio will save the prefs, so no need to do it here
$player = new player();
$player->prepare_smartradio();

prefs::$database = new mix_radio([
	'doing_search' => true,
	'trackbytrack' => false
]);
$uri = prefs::$database->preparePlaylist();
prefs::$database->close_database();

if ($uri !== null) {
	$cmds = [join_command_string(['add', $uri]), 'play'];
	$player->do_command_list($cmds);
	header('HTTP/1.1 204 No Content');
} else {
	header('HTTP/1.1 500 Internal Server Error');
}
?>