<?php
chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");

$player = new player();
prefs::$database = new music_loader();

//
// Assemble and format the command list and perform any command-specific backend actions
//

$json = json_decode(file_get_contents("php://input"));

if ($player->is_connected()) {
	$mpd_status = $player->rompr_commands_to_mpd($json);
} else {
	$mpd_status['error'] = "Unable to Connect to ".prefs::currenthost();
}

$mpd_status['smartradio'] = prefs::get_radio_params();

header('Content-Type: application/json');
echo json_encode($mpd_status);

?>
