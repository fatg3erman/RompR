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
	$mpd_status['error'] = "Unable to Connect to ".prefs::$prefs['currenthost'];
}

$p = prefs::$prefs['currenthost'];
$mpd_status['radiomode'] = prefs::$prefs['multihosts'][$p]['radioparams']['radiomode'];
$mpd_status['radioparam'] = prefs::$prefs['multihosts'][$p]['radioparams']['radioparam'];
$mpd_status['radiomaster'] = prefs::$prefs['multihosts'][$p]['radioparams']['radiomaster'];
$mpd_status['radioconsume'] = prefs::$prefs['multihosts'][$p]['radioparams']['radioconsume'];
$mpd_status['mopidy_radio_domains'] = prefs::$prefs['mopidy_radio_domains'];

header('Content-Type: application/json');
echo json_encode($mpd_status);

?>
