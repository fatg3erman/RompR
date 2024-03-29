<?php
const IS_ROMONITOR = true;
const LASTFM_TRACKS_PER_PAGE = 100;
require_once ("includes/vars.php");
require_once ("includes/functions.php");
$monitors = [];
set_version_string();
prefs::set_pref(['backend_version' => $version_string]);
prefs::save();
$pwd = getcwd();
logger::mark('DAEMON', "Running From",$pwd);

while (true) {

    prefs::load();
    $players = array_keys(prefs::get_pref('multihosts'));

    // Check that romonitor daemons are running for all our hosts

    foreach ($players as $player) {
        $cmd = $pwd.'/romonitor.php --currenthost '.rawurlencode($player);
        $mon_running = false;
        if (array_key_exists($player, $monitors) && posix_getpgid($monitors[$player]) !== false) {
            // If we started it and it's still running
            logger::core('DAEMON', "Monitor for",$player,"already running");
            $def_now = prefs::get_def_for_player($player);
            if (array_key_exists($player, $players_now) && player_def_changed($def_now, $players_now[$player])) {
                // If we started it, it's still running, and the definition has changed since we started it
                logger::info('DAEMON', "Player",$player,"definition has changed - restarting monitor");
                kill_process($monitors[$player]);
            } else {
                $mon_running = true;
            }
        } else if (($pid = get_pid($cmd)) !== false) {
            logger::warn('DAEMON', "Monitor for",$player,"already started, but not by this Daemon. This may lead to problems.");
            $mon_running = true;
            $monitors[$player] = $pid;
        }

        if (!$mon_running) {
            logger::log('DAEMON', "Starting Monitor For",$player);
            $monitors[$player] = start_process($cmd);
            logger::trace('DAEMON', "Started PID", $monitors[$player]);
            check_alarms($player, true);
        }

    }

    foreach ($monitors as $mon => $pid) {
        if (!in_array($mon, $players)) {
            logger::log('DAEMON', "Player $mon no longer exists. Killing PID $pid");
            kill_process($pid);
            unset($monitors[$mon]);
            check_alarms($player, false);
        }
    }

    $players_now = prefs::get_pref('multihosts');

    check_cache_clean();

    check_podcast_refresh();

    check_lastfm_sync();

    check_unplayable_tracks();

    sleep(60);

}

function player_def_changed($a, $b) {
    foreach (prefs::PLAYER_CONNECTION_PARAMS as $p) {
        if ($a[$p] != $b[$p]) {
            return true;
        }
    }
    return false;
}

function check_alarms($player, $restart) {
    prefs::$database = new timers($player);
    prefs::$database->check_alarms($restart);
    prefs::$database->close_database();
    prefs::$database = null;
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

    if (!prefs::get_pref('sync_lastfm_at_start') || prefs::get_pref('lastfm_session_key') == '')
        return;

    $next = prefs::get_pref('next_lastfm_synctime') - time();
    if ($next > 0) {
        logger::debug('DAEMON', 'Next LastFM Sync Check is in',$next,'seconds');
    } else {
        logger::mark('DAEMON', 'Syncing LastFM Playcounts');
        $page = 1;
        $options = [
            'limit' => LASTFM_TRACKS_PER_PAGE,
            'from' => prefs::get_pref('last_lastfm_synctime'),
            'extended' => 1
        ];
        prefs::$database = new metaDatabase();
        while ($page > 0) {
            $options['page'] = $page;
            $tracks = lastfm::get_recent_tracks($options);
            if (count($tracks) == 0) {
                logger::debug('LASTFM-SYNC', 'No Tracks in page',$page);
                $page = 0;
            } else {
                foreach ($tracks as $track) {
                    try {
                        if (array_key_exists('date', $track)) {
                            $data = [
                                'Title' => $track['name'],
                                'Album' => $track['album']['#text'],
                                'trackartist' => $track['artist']['name'],
                                'albumartist' => $track['artist']['name'],
                                'lastplayed' => $track['date']['uts'],
                                'attributes' => [['attribute' => 'Playcount', 'value' => 1]]
                            ];
                            if (array_key_exists('mbid', $track['album']) && $track['album']['mbid'] != '') {
                                $data['MUSICBRAINZ_ALBUMID'] = $track['album']['mbid'];
                            }
                            logger::debug('LASTFM-SYNC', 'Syncing', $data['Title']);
                            prefs::$database->syncinc($data);
                        }
                    } catch (Exception $e) {

                    }
                }
                // You'd think we could just loop until we get a page with no results
                // but it doesn't always work that way - if you have a track that is nowplaying,
                // incrementing the page counter just keeps returning that track for every page.
                if (count($tracks) < LASTFM_TRACKS_PER_PAGE) {
                    $page = 0;
                } else {
                    $page++;
                    sleep(5);
                }
            }
        }
        prefs::set_pref([
            'last_lastfm_synctime' => time(),
            'next_lastfm_synctime' => time() + prefs::get_pref('lastfm_sync_frequency')
        ]);
        prefs::save();
        prefs::$database->close_database();
        prefs::$database = null;

    }

}

function check_unplayable_tracks() {
    $next = prefs::get_pref('linkchecker_nextrun') - time();
    if ($next > 0) {
        logger::debug('DAEMON', 'Next Spotify Relinking Check is in',$next,'seconds');
    } else {
        prefs::$database = new metaquery();
        if (!prefs::get_pref('link_checker_is_running')) {
            prefs::$database->resetlinkcheck();
            prefs::set_pref(['link_checker_is_running' => true]);
            prefs::save();
        }
        if (prefs::$database->getlinktocheck()) {
            prefs::set_pref([
                'linkchecker_nextrun' => time() + prefs::get_pref('link_checker_frequency'),
                'link_checker_is_running' => false
            ]);
            prefs::save();
        }

        prefs::$database->close_database();
        prefs::$database = null;
    }
}

?>
