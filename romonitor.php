<?php
const IS_ROMONITOR = true;
require_once ("includes/vars.php");
require_once ("includes/functions.php");
$opts = getopt('', ['currenthost:']);
if (is_array($opts)) {
	foreach($opts as $key => $value) {
		logger::core("ROMONITOR", $key,'=',$value);
		prefs::$prefs[$key] = $value;
	}
}
logger::mark("ROMONITOR", "Using Player ".prefs::$prefs['currenthost']);

$player = new base_mpd_player();

$player = new player();
define('CURRENTHOST_SAVE', prefs::$prefs['currenthost']);
define('PLAYER_BACKEND_SAVE', prefs::$prefs['player_backend']);
$current_id = -1;
$read_time = 0;
$current_song = array();
register_shutdown_function('close_mpd');
$player->set_consume_state();
// Using the IDLE subsystem of MPD and mopidy reduces repeated connections, which helps a lot

// We use 'elapsed' and a time measurement to make sure we only incrememnt playcounts if
// more than 90% of the track has been played.

// We have to cope with seeking - where we will get an idle player message. The way we handle
// it is that each time we get a message, if we've played more than 90% of the track we INC
// the playcount - but the increment is based on the playcount value the first time we saw
// the track so repeated increments just keep setting it to the same value

while (true) {
	while ($player->is_connected()) {
		$mpd_status = $player->get_status();
		if (array_key_exists('error', $mpd_status))
			break;

		if (array_key_exists('songid', $mpd_status) && array_key_exists('elapsed', $mpd_status)) {
			$read_time = time();
			prefs::$database = new playlistCollection();
			$filedata = $player->get_currentsong_as_playlist();
			$current_song = prefs::$database->doNewPlaylistFile($filedata);
			prefs::$database->close_database();
			prefs::$database = new metaDatabase();
			prefs::$database->sanitise_data($current_song);
			if (array_key_exists('Time', $current_song) && $current_song['Time'] > 0 && $current_song['type'] !== 'stream') {
				if ($mpd_status['songid'] != $current_id) {
					$current_id = $mpd_status['songid'];
					prefs::$database->get($current_song);
					$current_playcount = array_key_exists('Playcount', prefs::$database->returninfo) ? prefs::$database->returninfo['Playcount'] : 0;
					logger::mark(CURRENTHOST_SAVE, "Track has changed - Current ID is",$current_id,"Duration is",$current_song['Time'],"Current Playcount is",$current_playcount);
					lastfm_update_nowplaying($current_song);
				}
			} else {
				$current_id = -1;
			}
			prefs::$database->close_database();
		} else {
			$current_id = -1;
		}
		$timedout = false;
		while (true) {
			if ($timedout) {
				$idle_status = $player->dummy_command();
			} else {
				$idle_status = $player->get_idle_status();
			}
			if (array_key_exists('error', $idle_status) && $idle_status['error'] == 'Timed Out') {
				logger::mark(CURRENTHOST_SAVE, "Idle command timed out, looping back");
				$timedout = true;
				continue;
			} else if (array_key_exists('error', $idle_status)) {
				break 2;
			} else {
				break;
			}
		}
		if (array_key_exists('changed', $idle_status) && $current_id != -1) {
			logger::mark(CURRENTHOST_SAVE,"Player State Has Changed");
			$elapsed = time() - $read_time + $mpd_status['elapsed'];
			$fraction_played = $elapsed/$current_song['Time'];
			if ($fraction_played > 0.9) {
				prefs::$database = new metaDatabase();
				logger::mark(CURRENTHOST_SAVE, "  Played more than 90% of song. Incrementing playcount");
				$current_song['attributes'] = [['attribute' => 'Playcount', 'value' => $current_playcount+1]];
				prefs::$database->inc($current_song);

				if ($current_song['type'] == 'podcast') {
					logger::mark(CURRENTHOST_SAVE, "  Marking podcast episode as listened");
					$temp_db = new poDatabase();
					$temp_db->markAsListened($current_song['file']);
					$temp_db->close_database();
					$temp_db = null;
				}
				prefs::$database->close_database();
			}

			reload_prefs();
			if (($fraction_played*100) > prefs::$prefs['scrobblepercent'])
				scrobble_to_lastfm($current_song);

			$radiomode = prefs::$prefs['multihosts'][CURRENTHOST_SAVE]['radioparams']['radiomode'];
			$radioparam = prefs::$prefs['multihosts'][CURRENTHOST_SAVE]['radioparams']['radioparam'];

			if (prefs::$prefs['multihosts'][CURRENTHOST_SAVE]['do_consume']) {
				$temp_status = $player->get_status();
				if ($temp_status['state'] == 'stop' || $temp_status['songid'] != $current_id) {
					logger::log(CURRENTHOST_SAVE, '  Consuming track ID',$current_id);
					$player->do_command_list(['deleteid "'.$current_id.'"']);
				}
			}

			$playlistlength = $mpd_status['playlistlength'];
			switch ($radiomode) {
				case 'starRadios':
				case 'mostPlayed':
				case 'faveAlbums':
				case 'recentlyaddedtracks':
					if ($playlistlength < 3) {
						// Note : We never actually take over, just keep an eye and top it up if
						// it starts to run out. This way any browser can easily take back control
						// Also, taking over would require us to have write access to prefs.var which is
						// problematic on some systems, especially if something like SELinux is enabled
						logger::trace(CURRENTHOST_SAVE, "Smart Radio Master has gone away. Taking Over");
						$tracksneeded = prefs::$prefs['smartradio_chunksize'] - $playlistlength  + 1;
						logger::trace(CURRENTHOST_SAVE, "Adding",$tracksneeded,"tracks from",$radiomode);
						prefs::$database = new collection_radio();
						$tracks = prefs::$database->doPlaylist($radioparam, $tracksneeded);
						prefs::$database->close_database();
						$cmds = array();
						foreach ($tracks as $track) {
							$cmds[] = join_command_string(array('add', $track['name']));
						}
						$player->do_command_list($cmds);
					}
					break;
			}
		}
	}
	close_mpd();
	logger::mark(CURRENTHOST_SAVE, "** Player connection dropped - retrying in 10 seconds **");
	sleep(10);
	$player->open_mpd_connection();
}
logger::error('ROMONITOR', 'How did we get here?');

function reload_prefs() {
	prefs::load();
	prefs::$prefs['currenthost'] = CURRENTHOST_SAVE;
	prefs::$prefs['player_backend'] = PLAYER_BACKEND_SAVE;
}

function close_mpd() {
	global $player;
	$player->close_mpd_connection();
}

function lastfm_update_nowplaying($currentsong) {
	reload_prefs();
	if (prefs::$prefs['lastfm_scrobbling'] && prefs::$prefs['lastfm_session_key'] != '') {
		logger::mark(CURRENTHOST_SAVE, 'Updating Last.FM Nowplaying');
		$options = array(
			'track' => $currentsong['Title'],
			'artist' => $currentsong['trackartist'],
			'album' => $currentsong['Album']
		);
		lastfm::update_nowplaying($options, false);
	}
}

function scrobble_to_lastfm($currentsong) {
	if (prefs::$prefs['lastfm_scrobbling'] && prefs::$prefs['lastfm_session_key'] != '') {
		logger::mark(CURRENTHOST_SAVE, 'Scrobbling');
		$options = array(
			'timestamp' => time() - $currentsong['Time'],
			'track' => $currentsong['Title'],
			'artist' => $currentsong['trackartist'],
			'album' => $currentsong['Album']
		);
		if ($currentsong['albumartist'] && strtolower($currentsong['albumartist']) != strtolower($currentsong['trackartist'])) {
			$options['albumArtist'] = $currentsong['albumartist'];
		}
		lastfm::scrobble($options, false);
	}
}

?>
