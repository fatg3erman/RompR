<?php
const IS_ROMONITOR = true;

require_once ("includes/vars.php");
require_once ("includes/functions.php");
$monitors = [];
set_version_string();
prefs::$prefs['backend_version'] = $version_string;
prefs::save();
$pwd = getcwd();
logger::log('DAEMON', "Running From",$pwd);

// Run at the start of every minute
// $seconds = date('s');
// $sleeptime = 60-$seconds;
// logger::mark('DAEMON', 'Waiting',$seconds,'seconds');
// sleep($sleeptime);

while (true) {

    prefs::load();
    $players = array_keys(prefs::$prefs['multihosts']);

    foreach ($players as $player) {
        $cmd = $pwd.'/romonitor.php --currenthost '.$player;
        $mon_running = false;
        if (array_key_exists($player, $monitors) && posix_getpgid($monitors[$player]) !== false) {
            // If we started it and it's still running
            logger::core('DAEMON', "Monitor for",$player,"already running");
            if (array_key_exists($player, $players_now) && player_def_changed(prefs::$prefs['multihosts'][$player], $players_now[$player])) {
                // If we started it, it's still running, and the definition has changed since we started it
                logger::trace('DAEMON', "Player",$player,"definition has changed - restarting monitor");
                kill_process($monitors[$player]);
            } else {
                $mon_running = true;
            }
        } else if (($pid = get_pid($cmd)) !== false) {
            // If it's already running but we didn't start it
            logger::warn('DAEMON', "Monitor for",$player,"already started, but not by this Daemon. This may lead to problems.");
            $mon_running = true;
            // kill_process($pid);
        }

        if (!$mon_running) {
            logger::log('DAEMON', "Starting Monitor For",$player);
            $monitors[$player] = start_process($cmd);
            logger::debug('DAEMON', "Started PID", $monitors[$player]);
        }

    }

    foreach ($monitors as $mon => $pid) {
        if (!in_array($mon, $players)) {
            logger::log('DAEMON', "Player $mon no longer exists. Killing PID $pid");
            exec('kill '.$pid);
            unset($monitors[$mon]);
        }
    }

    $players_now = prefs::$prefs['multihosts'];

    check_cache_clean();

    check_podcast_refresh();

    check_lastfm_sync();

    check_unplayable_tracks();

    sleep(60);

}

function player_def_changed($a, $b) {
    foreach (['host', 'mopidy_remote', 'password', 'port', 'socket'] as $p) {
        if ($a[$p] != $b[$p]) {
            return true;
        }
    }
    return false;
}

function check_cache_clean() {
    prefs::$database = new cache_cleaner();
    if (prefs::$database->check_clean_time())
        prefs::$database->clean_cache();

    prefs::$database->close_database();
    prefs::$database = null;
}

function check_podcast_refresh() {
    prefs::$database = new poDatabase();
    prefs::$database->check_podcast_refresh();
    prefs::$database->close_database();
    prefs::$database = null;
}

function check_lastfm_sync() {

    if (prefs::$prefs['sync_lastfm_at_start'] &&
        prefs::$prefs['lastfm_session_key'] != '' &&
        time() >= prefs::$prefs['next_lastfm_synctime']
    ) {
        logger::mark('DAEMON', 'Syncing LastFM Playcounts');
        $page = 1;
        $options = [
            'limit' => 100,
            'from' => prefs::$prefs['last_lastfm_synctime'],
            'extended' => 1
        ];
        prefs::$prefs['last_lastfm_synctime'] = time();
        prefs::$database = new metaDatabase();
        while ($page > 0) {
            $options['page'] = $page;
            $tracks = lastfm::get_recent_tracks($options);
            if (count($tracks) == 0) {
                logger::log('LASTFM-SYNC', 'No Tracks in page',$page);
                $page = 0;
            } else {
                foreach ($tracks as $track) {
                    prefs::$database->syncinc([
                        'Title' => $track['name'],
                        'Album' => $track['album']['#text'],
                        'trackartist' => $track['artist']['name'],
                        'albumartist' => $track['artist']['name'],
                        'lastplayed' => $track['date']['uts'],
                        'attributes' => [['attribute' => 'Playcount', 'value' => 1]]
                    ]);
                }
                $page++;
                sleep(5);
            }
        }
        prefs::$prefs['next_lastfm_synctime'] = time() + prefs::$prefs['lastfm_sync_frequency'];
        prefs::save();
        prefs::$database->close_database();
        prefs::$database = null;

    }

}
function check_unplayable_tracks() {
    if (time() >= prefs::$prefs['linkchecker_nextrun']) {
        prefs::$database = new metaquery();
        prefs::$database->resetlinkcheck();
        if (prefs::$database->getlinktocheck())
            prefs::$prefs['linkchecker_nextrun'] = time() + prefs::$prefs['link_checker_frequency'];

        prefs::$database->close_database();
        prefs::$database = null;
    }
}

?>
