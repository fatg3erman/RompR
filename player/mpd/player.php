<?php
require_once ('player/mpdinterface.php');
$PLAYER_TYPE = 'mpdPlayer';
class mpdPlayer extends mpd_base_player {

    public function check_track_load_command($uri) {
        $ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'm3u':
            case 'm3u8':
            case 'asf':
            case 'asx':
                return 'load';
                break;

            default:
                if (preg_match('#www\.radio-browser\.info/webservice/v2/m3u#', $uri)) {
                    return 'load';
                } else {
                    return 'add';
                }
                break;
        }
    }
}
?>