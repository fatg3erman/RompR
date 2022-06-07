<?php
chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
prefs::set_static_pref(['currenthost' => prefs::currenthost()]);
$params = json_decode(file_get_contents('php://input'), true);
foreach ($params as $k => $v) {
	logger::log('SMARTRADIO', $k,'=',$v);
}
prefs::set_radio_params($params);
// prepare_smartradio will save the prefs, so no need to do it here
$player = new base_mpd_player();
$player->prepare_smartradio();

prefs::$database = new collection_radio();
prefs::$database->preparePlaylist();
prefs::$database->close_database();

$player->check_radiomode();
$player->do_command_list(['play']);

header('HTTP/1.1 204 No Content');

?>