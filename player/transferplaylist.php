<?php
chdir('..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
require_once ("international.php");
require_once ("collection/collection.php");
require_once ("player/".$prefs['player_backend']."/player.php");
require_once ("backends/sql/backend.php");
require_once ("utils/imagefunctions.php");

class transferCollection {

    private $tracks;

    public function __construct() {
        $this->tracks = array();
    }

    public function doNewPlaylistFile(&$filedata) {
        debuglog("    Track ".$filedata['Pos']." ".$filedata['type']." ".$filedata['file'], "TRANSFER");
        array_push($this->tracks, array('type' => $filedata['type'], 'uri' => $filedata['file']));
        return true;
    }

    public function get_tracks() {
        foreach ($this->tracks as $track) {
            yield $track;
        }
    }

    public function get_track_type($index) {
        return $tracks[$index]['type'];
    }

}

$json = json_decode(file_get_contents("php://input"), true);
debuglog("Transferring Playlist From ".$prefs['currenthost']." to ".$json['currenthost'], "TRANSFER", 5);
// Read the playlist from the current player
$player = new $PLAYER_TYPE();
$mpd_status = $player->get_status();
$collection = new transferCollection();
foreach ($player->get_playlist($collection) as $r) {
    // no return value needed
};
$player->do_command_list(array('stop'));
$player->close_mpd_connection();

// Probe the type of the new player
$target = $prefs['multihosts']->{$json['currenthost']};
$prefs['player_backend'] = 'none';
$target_player = new mpd_base_player(
    $target->host, $target->port, $target->socket, $target->password, null, $target->mopidy_slave
);
// probe_player_type has now set $prefs['player_backend']
$target_player->close_mpd_connection();

// Connect properly to the new player
require_once ("player/".$prefs['player_backend']."/player.php");
$target_player = new $PLAYER_TYPE();

// Transfer the playlist to the new player
$cmds = array('stop', 'clear');
foreach ($collection->get_tracks() as $track) {
    array_push($cmds, join_command_string(array('add', $track['uri'])));
}
$target_player->do_command_list($cmds);

// Work around Mopidy bug where it doesn't update the 'state' variable properly
// after a seek and so doing all this in one command list doesn't work
debuglog("  State is ".$mpd_status['state'],"TRANSFER");
if (array_key_exists('state', $mpd_status) && $mpd_status['state'] == 'play') {
    $target_player->do_command_list(array(join_command_string(array('play', $mpd_status['song']))));
    $target_player->wait_for_state('play');
    if ($collection->get_track_type($mpd_status['song']) != 'stream') {
        $target_player->do_command_list(array(join_command_string(array('seek', $mpd_status['song'], intval($mpd_status['elapsed'])))));
    }
}
$target_player->close_mpd_connection();

header('HTTP/1.1 204 No Content');

?>
