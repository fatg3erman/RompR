<?php
chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
require_once ("player/".$prefs['player_backend']."/player.php");
require_once ("collection/collection.php");
require_once ('backends/sql/backend.php');
$mpd_status = array();
$playlist_movefrom = null;
$playlist_moveto = null;
$playlist_moving_within = null;
$playlist_tracksadded = 0;
$expected_state = null;
$do_resume_seek = false;
$do_resume_seek_id = false;
$moveallto = null;
$current_playlist_length = 0;
$player = new $PLAYER_TYPE();

if ($player->is_connected()) {

	$cmd_status = true;

	$cmds = array();

	//
	// Assemble and format the command list and perform any command-specific backend actions
	//

	$json = json_decode(file_get_contents("php://input"));

	if ($json) {

		foreach ($json as $cmd) {

			logger::debug("POSTCOMMAND", "RAW command : ".multi_implode($cmd, " "));

			switch ($cmd[0]) {
				case "addtoend":
					logger::log("POSTCOMMAND", "Addtoend ".$cmd[1]);
					$cmds = array_merge($cmds, playAlbumFromTrack($cmd[1]));
					break;

				case 'playlisttoend':
					logger::log("POSTCOMMAND", "Playing playlist ".$cmd[1]." from position ".$cmd[2]." to end");
					foreach($player->get_stored_playlist_tracks($cmd[1], $cmd[2]) as list($class, $uri, $filedata)) {
						if ($class == 'clicktrack') {
							$cmds[] = 'add "'.$uri.'"';
						} else {
							$cmds[] = 'load "'.$uri.'"';
						}
					}
					break;

				case "additem":
					logger::log("POSTCOMMAND", "Adding Item ".$cmd[1]);
					$cmds = array_merge($cmds, getItemsToAdd($cmd[1], null));
					break;

				case "loadstreamplaylist":
					require_once ("player/".$prefs['player_backend']."/streamplaylisthandler.php");
					$cmds = array_merge($cmds, internetPlaylist::load_internet_playlist($cmd[1], $cmd[2], $cmd[3]));
					break;

				case "addremoteplaylist":
					logger::log("POSTCOMMAND", "Remote Playlist URL is",$cmd[1]);
					// First, see if we can just 'load' the remote playlist. This is better with MPD
					// as it parses track names from the playlist
					if ($player->check_track_load_command($cmd[1]) == 'load') {
						logger::trace("POSTCOMMAND", "Loading remote playlist");
						$cmds[] = join_command_string(array('load', $cmd[1]));
					} else {
						// Always use the MPD version of the stream playlist handler, since that parses
						// all tracks (Mopidy's version doesn't because we use Mopidy's playlist parser instead).
						// Perversely, we need to use this because we can't use 'load' on a remote playlist with Mopidy,
						// and 'add' only adds the first track. As user remtote playlists can have multiple types of
						// thing in them, including streams, we need to 'add' every track - unless we're using mpd and
						// the 'track' is a playlist we need to load..... Crikey.
						logger::trace("POSTCOMMAND", "Adding remote playlist (track by track)");
						require_once ("player/mpd/streamplaylisthandler.php");
						$tracks = internetPlaylist::load_internet_playlist($cmd[1], '', '', true);
						foreach ($tracks as $track) {
							$cmd = $player->check_track_load_command($track['TrackUri']);
							$cmds[] = join_command_string(array($cmd, $track['TrackUri']));
						}
					}
					break;

				case "rename":
					$oldimage = new albumImage(array('artist' => 'PLAYLIST', 'album' => $cmd[1]));
					$oldimage->change_name($cmd[2]);
					$cmds[] = join_command_string($cmd);
					break;

				case "playlistadd":
					if (preg_match('/^(a|b|r|t|y|u|z)(.*?)(\d+|root)/', $cmd[2])) {
						// Add a whole album/artist
						$lengthnow = count($cmds);
						$cmds = array_merge($cmds, getItemsToAdd($cmd[2], $cmd[0].' "'.format_for_mpd($cmd[1]).'"'));
						check_playlist_add_move($cmd, (count($cmds) - $lengthnow));
					} else {
						logger::trace('POSTCOMMAND',$cmd[0], $cmd[1], $cmd[2]);
						$cmds[] = join_command_string(array($cmd[0], $cmd[1], $cmd[2]));
						check_playlist_add_move($cmd, 1);
					}
					break;

				case "playlistmove":
					// Expects playlistname, then arrays of from and to
					foreach ($cmd[2] as $i => $v) {
						$cmds[] = join_command_string(array($cmd[0], $cmd[1], $v, $cmd[3][$i]));
					}
					break;

				case "moveallto":
					$moveallto = $cmd[1];
					$temp_status = $player->get_status();
					if (array_key_exists('playlistlength', $temp_status)) {
						$current_playlist_length = $temp_status['playlistlength'];
					}
					break;

				case "playlistadddir":
					$thing = array('searchaddpl',$cmd[1],'base',$cmd[2]);
					$cmds[] = join_command_string($thing);
					break;

				case "resume":
					logger::log("POSTCOMMAND", "Adding Track ".$cmd[1]);
					logger::log("POSTCOMMAND", "  .. and seeking position ".$cmd[3]." to ".$cmd[2]);
					if ($cmd[4] == 'yes') {
						logger::log('POSTCOMMAND', "  .. CD player mode was also requested");
						$cmds = array_merge($cmds, playAlbumFromTrack($cmd[1]));
					} else {
						$cmds[] = join_command_string(array('add', $cmd[1]));
					}
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

	//
	// Send the command list to mpd
	//

	$cmd_status = $player->do_command_list($cmds);

	//
	// If we added tracks to the play queue, move them into position if we need to
	//

	if ($moveallto !== null) {
		logger::trace("MPD", "Moving Tracks into position");
		$temp_status = $player->get_status();
		$new_playlist_length = $temp_status['playlistlength'];
		$player->do_command_list(array(join_command_string(array('move', $current_playlist_length.':'.$new_playlist_length, $moveallto))));
	}

	//
	// Wait for the player to start playback if that's what it's supposed to be doing
	//

	$player->wait_for_state($expected_state);

	//
	// Work around mopidy play/seek command list bug
	//

	if ($do_resume_seek !== false) {
		$player->do_command_list(array(join_command_string(array('seek', $do_resume_seek[0], $do_resume_seek[1]))));
	}
	if ($do_resume_seek_id !== false) {
		$player->do_command_list(array(join_command_string(array('seekid', $do_resume_seek_id[0], $do_resume_seek_id[1]))));
	}

	//
	// Query mpd's status
	//

	$mpd_status = $player->get_status();

	//
	// If we got an error from the command list and NOT from 'status',
	// make sure we report the command list error back
	//

	if (is_array($cmd_status) && !array_key_exists('error', $mpd_status) && array_key_exists('error', $cmd_status)) {
		logger::warn("POSTCOMMAND", "Command List Error",$cmd_status['error']);
		$mpd_status = array_merge($mpd_status, $cmd_status);
	}

	//
	// Add current song and replay gain status to mpd_status
	// We use currentsong for streams. It is NOT merged with database data as the playlist data is
	// so should not be used for metadata at any other point except for filename.
	//

	if (array_key_exists('song', $mpd_status) && !array_key_exists('error', $mpd_status)) {
		$songinfo = $player->get_current_song();
		if (is_array($songinfo)) {
			$mpd_status = array_merge($mpd_status, $songinfo);
		}
	}

	$mpd_status = array_merge($mpd_status, $player->get_replay_gain_state());

	//
	// Clear any player error now we've caught it
	//

	if (array_key_exists('error', $mpd_status)) {
		logger::trace("MPD", "Clearing Player Error ".$mpd_status['error']);
		$player->clear_error();
	}

	//
	// Disable 'single' if we're stopped or paused (single is used for 'Stop After Current Track')
	//

	if (array_key_exists('single', $mpd_status) && $mpd_status['single'] == 1 && array_key_exists('state', $mpd_status) &&
			($mpd_status['state'] == "pause" || $mpd_status['state'] == "stop")) {
		logger::trace("MPD", "Cancelling Single Mode");
		$player->cancel_single_quietly();
		$mpd_status['single'] = 0;
	}

	//
	// Format any error message more nicely
	//

	if (array_key_exists('error', $mpd_status)) {
		$mpd_status['error'] = preg_replace('/ACK \[.*?\]\s*/','',$mpd_status['error']);
	}

} else {
	$mpd_status['error'] = "Unable to Connect to ".$prefs['currenthost'];
}

$p = $prefs['currenthost'];
$mpd_status['radiomode'] = $prefs['multihosts']->{$p}->radioparams->radiomode;
$mpd_status['radioparam'] = $prefs['multihosts']->{$p}->radioparams->radioparam;
$mpd_status['radiomaster'] = $prefs['multihosts']->{$p}->radioparams->radiomaster;
$mpd_status['radioconsume'] = $prefs['multihosts']->{$p}->radioparams->radioconsume;

header('Content-Type: application/json');
echo json_encode($mpd_status);

function check_playlist_add_move($cmd, $incvalue) {
	global $playlist_moving_within, $playlist_movefrom, $playlist_moveto, $playlist_tracksadded;
	if ($cmd[3] == 0 || $cmd[3]) {
		if ($playlist_moving_within === null) $playlist_moving_within = $cmd[1];
		if ($playlist_movefrom === null) $playlist_movefrom = $cmd[4];
		if ($playlist_moveto === null) $playlist_moveto = $cmd[3];
		$playlist_tracksadded += $incvalue;
	}
}


?>
