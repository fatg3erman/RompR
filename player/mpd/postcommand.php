<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("player/mpd/connection.php");
include ("collection/collection.php");

$mpd_status = array();
$playlist_movefrom = null;
$playlist_moveto = null;
$playlist_moving_within = null;
$playlist_tracksadded = 0;
$expected_state = null;
$do_resume_seek = false;
$do_resume_seek_id = false;

if ($is_connected) {

    $cmd_status = true;

    $cmds = array();

    //
    // Assemble and format the command list and perform any command-specific backend actions
    //

    $json = json_decode(file_get_contents("php://input"));

    if ($json) {

        foreach ($json as $cmd) {

            debuglog("RAW command : ".implode(' ', $cmd),"POSTCOMMAND",9);

            switch ($cmd[0]) {
                case "addtoend":
                    require_once("backends/sql/backend.php");
                    debuglog("Addtoend ".$cmd[1],"POSTCOMMAND");
                    $cmds = array_merge($cmds, playAlbumFromTrack($cmd[1]));
                    break;

                case 'playlisttoend':
                    debuglog("Playing playlist ".$cmd[1]." from position ".$cmd[2]." to end","POSTCOMMAND");
                    $putinplaylistarray = true;
                    doCollection('listplaylistinfo "'.$cmd[1].'"');
                    for ($c = $cmd[2]; $c < count($playlist); $c++) {
                        list($class, $url) = $playlist[$c]->get_checked_url();
                        $cmds[] = 'add "'.$url.'"';
                    }
                    break;

                case "additem":
                    require_once("backends/sql/backend.php");
                    debuglog("Adding Item ".$cmd[1],"POSTCOMMAND");
                    $cmds = array_merge($cmds, getItemsToAdd($cmd[1], null));
                    break;

                case "addartist":
                    require_once("backends/sql/backend.php");
                    debuglog("Getting tracks for Artist ".$cmd[1],"MPD");
                    doCollection('find "artist" "'.format_for_mpd($cmd[1]).'"',array("spotify"));
                    $cmds = array_merge($cmds, $collection->getAllTracks("add"));
                    break;

                case "loadstreamplaylist":
                    require_once ("backends/sql/backend.php");
                    require_once ("player/".$prefs['player_backend']."/streamplaylisthandler.php");
                    require_once ("utils/getInternetPlaylist.php");
                    $cmds = array_merge($cmds, load_internet_playlist($cmd[1], $cmd[2], $cmd[3]));
                    break;

                case "addremoteplaylist":
                    debuglog("  URL is ".$cmd[1],"POSTCOMMAND");
                    // First, see if we can just 'load' the remote playlist. This is better with MPD
                    // as it parses track names from the playlist
                    if (check_track_load_command($cmd[1]) == 'load') {
                        debuglog("Loading remote playlist","POSTCOMMAND");
                        $cmds[] = join_command_string(array('load', $cmd[1]));
                    } else {
                        // Always use the MPD version of the stream playlist handler, since that parses
                        // all tracks (Mopidy's version doesn't because we use Mopidy's playlist parser instead).
                        // Perversely, we need to use this because we can't use 'load' on a remote playlist with Mopidy,
                        // and 'add' only adds the first track. As user remtote playlists can have multiple types of
                        // thing in them, including streams, we need to 'add' every track - unless we're using mpd and
                        // the 'track' is a playlist we need to load..... Crikey.
                        debuglog("Adding remote playlist (track by track)","POSTCOMMAND");
                        require_once ("player/mpd/streamplaylisthandler.php");
                        require_once ("utils/getInternetPlaylist.php");
                        $tracks = load_internet_playlist($cmd[1], '', '', true);
                        foreach ($tracks as $track) {
                            $cmd = check_track_load_command($track['TrackUri']);
                            $cmds[] = join_command_string(array($cmd, $track['TrackUri']));
                        }
                    }
                    break;

                case "rename":
                    require_once ('utils/imagefunctions.php');
                    $oldimage = new albumImage(array('artist' => 'PLAYLIST', 'album' => $cmd[1]));
                    $oldimage->change_name($cmd[2]);
                    $cmds[] = join_command_string($cmd);
                    break;

                case "playlistadd":
                    require_once("backends/sql/backend.php");
                    if (preg_match('/[ab]album\d+|[ab]artist\d+/', $cmd[2])) {
                        $lengthnow = count($cmds);
                        $cmds = array_merge($cmds, getItemsToAdd($cmd[2], $cmd[0].' "'.format_for_mpd($cmd[1]).'"'));
                        check_playlist_add_move($cmd, (count($cmds) - $lengthnow));
                    } else {
                        $cmds[] = join_command_string(array($cmd[0], $cmd[1], $cmd[2]));
                        check_playlist_add_move($cmd, 1);
                    }
                    break;

                case "playlistadddir":
                    $thing = array('searchaddpl',$cmd[1],'base',$cmd[2]);
                    $cmds[] = join_command_string($thing);
                    break;

                case "resume":
                    debuglog("Adding Track ".$cmd[1],"POSTCOMMAND");
                    debuglog("  .. and seeking position ".$cmd[3]." to ".$cmd[2],"POSTCOMMAND");
                    $cmds[] = join_command_string(array('add', $cmd[1]));
                    $cmds[] = join_command_string(array('play', $cmd[3]));
                    $expected_state = 'play';
                    $do_resume_seek = array($cmd[3], $cmd[2]);
                    break;

                case "seekpodcast":
                    $expected_state = 'play';
                    $do_resume_seek_id = array($cmd[1], $cmd[2]);
                    break;

                case 'save':
                case 'rm':
                case "load":
                    $cmds[] = join_command_string($cmd);
                    break;

                case "clear":
                    $cmds[] = join_command_string($cmd);
                    break;

                case "play":
                case "playid":
                    $expected_state = 'play';
                    // Fall through
                default:
                    $cmds[] = join_command_string($cmd);
                    break;
            }
        }

    }

    //
    // If we added tracks to a STORED playlist, move them into the correct position
    //

    while ($playlist_tracksadded > 0 && $playlist_movefrom !== null && $playlist_moveto !== null) {
        $cmds[] = join_command_string(array('playlistmove', $playlist_moving_within, $playlist_movefrom, $playlist_moveto));
        $playlist_moveto++;
        $playlist_movefrom++;
        $playlist_tracksadded--;
    }

    $cmds = check_slave_actions($cmds);

    //
    // Send the command list to mpd
    //

    $cmd_status = do_mpd_command_list($cmds);

    //
    // Wait for the player to start playback if that's what it's supposed to be doing
    //

    wait_for_player_state($expected_state);

    //
    // Work around mopidy play/seek command list bug
    //

    if ($do_resume_seek !== false) {
        do_mpd_command(join_command_string(array('seek', $do_resume_seek[0], $do_resume_seek[1])));
    }
    if ($do_resume_seek_id !== false) {
        do_mpd_command(join_command_string(array('seekid', $do_resume_seek_id[0], $do_resume_seek_id[1])));
    }

    //
    // Query mpd's status
    //

    $mpd_status = do_mpd_command ("status", true, false);

    //
    // If we got an error from the command list and NOT from 'status',
    // make sure we report the command list error back
    //

    if (is_array($cmd_status) && !array_key_exists('error', $mpd_status) && array_key_exists('error', $cmd_status)) {
        debuglog("Command List Error ".$cmd_status['error'],"POSTCOMMAND",1);
        $mpd_status = array_merge($mpd_status, $cmd_status);
    }

    //
    // Add current song and replay gain status to mpd_status
    //

    if (array_key_exists('song', $mpd_status) && !array_key_exists('error', $mpd_status)) {
        $songinfo = array();
        $songinfo = do_mpd_command('currentsong', true, false);
        if (is_array($songinfo)) {
            $mpd_status = array_merge($mpd_status, $songinfo);
        }
    }

    if ($prefs['player_backend'] == 'mpd') {
        $arse = array();
        $arse = do_mpd_command('replay_gain_status', true, false);
        if (array_key_exists('error', $arse)) {
            unset($arse['error']);
            send_command('clearerror');
        }
        $mpd_status = array_merge($mpd_status, $arse);
    }

    //
    // Clear any player error now we've caught it
    //

    if (array_key_exists('error', $mpd_status)) {
        debuglog("Clearing Player Error ".$mpd_status['error'],"MPD",7);
        send_command("clearerror");
    }

    //
    // Disable 'single' if we're stopped or paused (single is used for 'Stop After Current Track')
    //

    if (array_key_exists('single', $mpd_status) && $mpd_status['single'] == 1 && array_key_exists('state', $mpd_status) &&
            ($mpd_status['state'] == "pause" || $mpd_status['state'] == "stop")) {
        debuglog("Cancelling Single Mode","MPD",9);
        send_command('single "0"');
        $mpd_status['single'] = 0;
    }

    //
    // Format any error message more nicely
    //

    if (array_key_exists('error', $mpd_status)) {
        $mpd_status['error'] = preg_replace('/ACK \[.*?\]\s*/','',$mpd_status['error']);
    }

} else {
    $s = (array_key_exists('player_backend', $prefs)) ? ucfirst($prefs['player_backend']).' ' : "Player ";
    if ($prefs['unix_socket'] != "") {
        $mpd_status['error'] = "Unable to Connect to ".$s."server at\n".$prefs["unix_socket"];
    } else {
        $mpd_status['error'] = "Unable to Connect to ".$s."server at\n".$prefs["mpd_host"].":".$prefs["mpd_port"];
    }
}

header('Content-Type: application/json');
echo json_encode($mpd_status);

close_mpd();

function check_playlist_add_move($cmd, $incvalue) {
    global $playlist_moving_within, $playlist_movefrom, $playlist_moveto, $playlist_tracksadded;
    if ($cmd[3] == 0 || $cmd[3]) {
        if ($playlist_moving_within === null) $playlist_moving_within = $cmd[1];
        if ($playlist_movefrom === null) $playlist_movefrom = $cmd[4];
        if ($playlist_moveto === null) $playlist_moveto = $cmd[3];
        $playlist_tracksadded += $incvalue;
    }
}

function check_track_load_command($uri) {
    global $prefs;
    if ($prefs['player_backend'] == 'mopidy') {
        return 'add';
    }
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

?>
