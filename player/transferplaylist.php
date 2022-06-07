<?php
chdir('..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");

class transferCollection {

	private $tracks;

	public function __construct() {
		$this->tracks = array();
	}

	public function doNewPlaylistFile(&$filedata) {
		logger::trace("TRANSFER", "    Track ".$filedata['Pos']." ".$filedata['type']." ".$filedata['file']);
		array_push($this->tracks, array('type' => $filedata['type'], 'uri' => $filedata['file']));
		return true;
	}

	public function get_tracks() {
		foreach ($this->tracks as $track) {
			yield $track;
		}
	}

	public function get_track_type($index) {
		return $this->tracks[$index]['type'];
	}

}

$json = json_decode(file_get_contents("php://input"), true);
logger::mark("TRANSFER", "Transferring Playlist From",prefs::currenthost(),"to",$json['currenthost']);
// Read the playlist from the current player
$player = new base_mpd_player();
$mpd_status = $player->get_status();
prefs::$database = new transferCollection();
foreach ($player->get_playlist() as $r) {
	prefs::$database->doNewPlaylistFile($r);
	// no return value needed
};
$player->do_command_list(array('stop'));
$player->close_mpd_connection();

// Probe the type of the new player
$target = prefs::get_def_for_player($json['currenthost']);
prefs::set_session_pref(['player_backend' => null]);
$target_player = new base_mpd_player(
	$target['host'], $target['port'], $target['socket'], $target['password'], null, $target['mopidy_remote']
);
// probe_player_type has now set player_backend
if ($target_player->is_connected()) {
	prefs::set_session_pref(['currenthost' => $json['currenthost']]);

	// Transfer the playlist to the new player
	$cmds = array('stop', 'clear');
	foreach (prefs::$database->get_tracks() as $track) {
		array_push($cmds, join_command_string(array('add', $track['uri'])));
	}
	$target_player->do_command_list($cmds);

	logger::log("TRANSFER", "  State is ".$mpd_status['state']);
	if (array_key_exists('state', $mpd_status) && $mpd_status['state'] == 'play') {
		$target_player->do_command_list(array(join_command_string(array('play', $mpd_status['song']))));
		// Work around Mopidy bug where it doesn't update the 'state' variable properly
		// after a seek and so doing all this in one command list doesn't work
		$target_player->wait_for_state('play');
		if (prefs::$database->get_track_type($mpd_status['song']) != 'stream') {
			$target_player->do_command_list(array(join_command_string(array('seek', $mpd_status['song'], intval($mpd_status['elapsed'])))));
		}
	}
	$target_player->close_mpd_connection();
} else {
	logger::warn('TRANSFER', 'Could not connect to new player',$json['currenthost']);
	header('HTTP/1.1 500 Internal Server Error');
	print 'Could not connect to Player '.$json['currenthost'];
	exit(0);
}

header('HTTP/1.1 204 No Content');

?>
