<?php
require_once ('player/mpdinterface.php');
$PLAYER_TYPE = 'mopidyPlayer';
class mopidyPlayer extends base_mpd_player {

    public function check_track_load_command($uri) {
        return 'add';
    }

}
?>