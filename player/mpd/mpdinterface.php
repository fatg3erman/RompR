<?php

class base_mpd_player {

    protected $connection;
    private $ip;
    private $port;
    private $socket;
    private $password;
    protected $player_type;
    private $is_slave;
    public $playlist_error;

    public function __construct($ip = null, $port = null, $socket = null, $password = null, $player_type = null, $is_slave = null) {
        global $prefs;

        if ($ip !== null) {
            $this->ip = $ip;
        } else {
            $this->ip = $prefs['multihosts']->{$prefs['currenthost']}->host;
        }
        if ($port !== null) {
            $this->port = $port;
        } else {
            $this->port = $prefs['multihosts']->{$prefs['currenthost']}->port;
        }
        if ($socket !== null) {
            $this->socket = $socket;
        } else {
            $this->socket = $prefs['multihosts']->{$prefs['currenthost']}->socket;
        }
        if ($password !== null) {
            $this->password = $password;
        } else {
            $this->password = $prefs['multihosts']->{$prefs['currenthost']}->password;
        }
        if ($is_slave !== null) {
            $this->is_slave = $is_slave;
        } else {
            if (property_exists($prefs['multihosts']->{$prefs['currenthost']}, 'mopidy_slave')) {
                $this->is_slave = $prefs['multihosts']->{$prefs['currenthost']}->mopidy_slave;
            } else {
                // Catch the case where we haven't yet upgraded the player defs
                $this->is_slave = false;
            }
        }
        logger::trace("MPDPLAYER", "Creating Player for",$this->ip.':'.$this->port);
        $this->open_mpd_connection();
        if ($player_type !== null) {
            $this->player_type = $player_type;
        } else {
            if (array_key_exists('player_backend', $prefs) && $prefs['player_backend'] !== 'none') {
                $this->player_type = $prefs['player_backend'];
            } else {
                $this->player_type = $this->probe_player_type();
            }
        }
    }

    public function __destruct() {
        if ($this->is_connected()) {
            $this->close_mpd_connection();
        }
    }

    public function open_mpd_connection() {
        if ($this->is_connected()) {
            return true;
        }
        if ($this->socket != "") {
            $this->connection = @stream_socket_client('unix://'.$this->socket);
        } else {
            $this->connection = @stream_socket_client('tcp://'.$this->ip.':'.$this->port);
        }

        if($this->is_connected()) {
            stream_set_timeout($this->connection, 65535);
            stream_set_blocking($this->connection, true);
            while(!feof($this->connection)) {
                $gt = fgets($this->connection, 1024);
                if ($this->parse_mpd_var($gt))
                    break;
            }
        }

        if ($this->password != "" && $this->is_connected()) {
            fputs($this->connection, "password ".$this->password."\n");
            while(!feof($this->connection)) {
                $gt = fgets($this->connection, 1024);
                $a = $this->parse_mpd_var($gt);
                if($a === true) {
                    $is_connected = true;
                    break;
                } else if ($a == null) {

                } else {
                    $this->close_mpd_connection();
                    return false;
                }
            }
        }
        return true;
    }

    public function close_mpd_connection() {
        if ($this->is_connected()) {
            stream_socket_shutdown($this->connection, STREAM_SHUT_RDWR);
        }
    }

    public function is_connected() {
        return (isset($this->connection) && is_resource($this->connection));
    }

    private function parse_mpd_var($in_str) {
        $got = trim($in_str);
        if(!isset($got))
            return null;
        if(strncmp("OK", $got, 2) == 0)
            return true;
        if(strncmp("ACK", $got, 3) == 0) {
            return array(0 => false, 1 => $got);
        }
        $key = trim(strtok($got, ":"));
        $val = trim(strtok("\0"));
        return array(0 => $key, 1 => $val);
    }

    protected function getline() {
        $got = fgets($this->connection, 2048);
        $key = trim(strtok($got, ":"));
        $val = trim(strtok("\0"));
        if ($val != '') {
            return array($key, $val);
        } else if (strpos($got, "OK") === 0 || strpos($got, "ACK") === 0) {
            return false;
        } else {
            return true;
        }
    }

    protected function send_command($command) {
        $retries = 5;
        $l = strlen($command."\n");
        do {
            $b = @fputs($this->connection, $command."\n");
            if (!$b || $b < $l) {
                logger::warn("MPD", "Socket Write Error for",$command,"- Retrying");
                $this->close_mpd_connection();
                usleep(500000);
                $this->open_mpd_connection();
                $retries--;
            } else {
                return true;
            }
        } while ($retries > 0);
        return false;
    }

    protected function do_mpd_command($command, $return_array = false, $force_array_results = false) {

        $retarr = array();
        if ($this->is_connected()) {

            logger::debug("MPD", "MPD Command",$command);
            $success = true;
            if ($command != '') {
                $success = $this->send_command($command);
            }
            if ($success) {
                while(!feof($this->connection)) {
                    $var = $this->parse_mpd_var(fgets($this->connection, 1024));
                    if(isset($var)){
                        if($var === true && count($retarr) == 0) {
                            // Got an OK or ACK but - no results or return_array is false
                            return true;
                        }
                        if ($var === true) {
                            break;
                        }
                        if ($var[0] == false) {
                            $sdata = stream_get_meta_data($this->connection);
                            if (array_key_exists('timed_out', $sdata) && $sdata['timed_out']) {
                                $var[1] = 'Timed Out';
                            }
                            logger::warn("MPD", "Error for'",$command,"':",$var[1]);
                            if ($return_array == true) {
                                $retarr['error'] = $var[1];
                            } else {
                                return false;
                            }
                            break;
                        }
                        if ($return_array == true) {
                            if(array_key_exists($var[0], $retarr)) {
                                if(is_array($retarr[($var[0])])) {
                                    $retarr[($var[0])][] = $var[1];
                                } else {
                                    $tmp = $retarr[($var[0])];
                                    $retarr[($var[0])] = array($tmp, $var[1]);
                                }
                            } else {
                                if ($force_array_results) {
                                    $retarr[($var[0])] = array($var[1]);
                                } else {
                                    $retarr[($var[0])] = $var[1];
                                }
                            }
                        }
                    }
                }
            } else {
                logger::error("MPD", "Failure to fput command",$command);
                $retarr['error'] = "There was an error communicating with ".ucfirst($this->player_type)."! (could not write to socket)";
            }
        }
        return $retarr;
    }

    public function do_command_list($cmds) {
        global $prefs;
        $done = 0;
        $cmd_status = null;

        if ($this->is_slave) {
            $this->translate_commands_for_slave($cmds);
        } else if ($this->player_type != $prefs['collection_player']) {
            $this->translate_player_types($cmds);
        }

        if (count($cmds) > 1) {
            $this->send_command("command_list_begin");
            foreach ($cmds as $c) {
                logger::trace("POSTCOMMAND", "Command List:",$c);
                // Note. We don't use send_command because that closes and re-opens the connection
                // if it fails to fputs, and that loses our command list status. Also if this fputs
                // fails it means the connection has dropped anyway, so we're screwed whatever happens.
                fputs($this->connection, $c."\n");
                $done++;
                // Command lists have a maximum length, 50 seems to be the default
                if ($done == 50) {
                    $this->do_mpd_command("command_list_end", true);
                    $this->send_command("command_list_begin");
                    $done = 0;
                }
            }
            $cmd_status = $this->do_mpd_command("command_list_end", true, false);
        } else if (count($cmds) == 1) {
            logger::trace("POSTCOMMAND", "Command :",$cmds[0]);
            $cmd_status = $this->do_mpd_command($cmds[0], true, false);
        }
        return $cmd_status;
    }

    public function parse_list_output($command, &$dirs, $domains) {

        // Generator Function for parsing MPD output for 'list...info', 'search ...' etc type commands
        // Returns MPD_FILE_MODEL

        logger::trace("MPD", "MPD Parse",$command);

        $success = $this->send_command($command);
        $filedata = MPD_FILE_MODEL;
        $parts = true;
        if (is_array($domains) && count($domains) == 0) {
            $domains = false;
        }

        while(  $this->is_connected() &&
                !feof($this->connection) &&
                $parts) {

            $parts = $this->getline();
            if (is_array($parts)) {
                switch ($parts[0]) {
                    case "directory":
                        $dirs[] = trim($parts[1]);
                        break;

                    case "Last-Modified":
                        if ($filedata['file'] != null) {
                            // We don't want the Last-Modified stamps of the directories
                            // to be used for the files.
                            $filedata[$parts[0]] = $parts[1];
                        }
                        break;

                    case 'file':
                        if ($filedata['file'] !== null) {
                            $filedata['domain'] = getDomain($filedata['file']);
                            if ($domains === false || in_array(getDomain($filedata['file']),$domains)) {
                                if ($this->sanitize_data($filedata)) {
                                    yield $filedata;
                                }
                            }
                        }
                        $filedata = MPD_FILE_MODEL;
                        $filedata[$parts[0]] = $parts[1];
                        break;

                    case 'X-AlbumUri':
                        // Mopidy-beets is using SEMICOLONS in its URI schemes.
                        // Surely a typo, but we need to work around it by not splitting the string
                        // Same applies to file.
                        $filedata[$parts[0]] = $parts[1];
                        break;

                    case 'X-AlbumImage':
                        // We can't cope with multiple images, so treat them specially
                        $ims = explode(';',$parts[1]);
                        $filedata[$parts[0]] = $ims[0];
                        break;

                    default:
                        if (in_array($parts[0], MPD_ARRAY_PARAMS)) {
                            $filedata[$parts[0]] = array_unique(explode(';',$parts[1]));
                        } else {
                            $filedata[$parts[0]] = explode(';',$parts[1])[0];
                        }
                        break;
                }
            }
        }

        if ($filedata['file'] !== null) {
            $filedata['domain'] = getDomain($filedata['file']);
            if ($domains === false || in_array(getDomain($filedata['file']),$domains)) {
                if ($this->sanitize_data($filedata)) {
                    yield $filedata;
                }
            }
        }
    }

    protected function sanitize_data(&$filedata) {
        global $dbterms, $numtracks, $totaltime;
        if ($dbterms['tags'] !== null || $dbterms['rating'] !== null) {
            // If this is a search and we have tags or ratings to search for, check them here.
            if (check_url_against_database($filedata['file'], $dbterms['tags'], $dbterms['rating']) == false) {
                return false;
            }
        }
       if (strpos($filedata['Title'], "[unplayable]") === 0) {
            logger::log("COLLECTION", "Ignoring unplayable track ".$filedata['file']);
            return false;
        }
        if (strpos($filedata['Title'], "[loading]") === 0) {
            logger::log("COLLECTION", "Ignoring unloaded track ".$filedata['file']);
            return false;
        }
        $filedata['unmopfile'] = $this->unmopify_file($filedata);

        if ($filedata['Track'] == 0) {
            $filedata['Track'] = format_tracknum(basename(rawurldecode($filedata['file'])));
        } else {
            $filedata['Track'] = format_tracknum(ltrim($filedata['Track'], '0'));
        }

        // cue sheet link (mpd only). We're only doing CUE sheets, not M3U
        if ($filedata['X-AlbumUri'] === null && strtolower(pathinfo($filedata['playlist'], PATHINFO_EXTENSION)) == "cue") {
            $filedata['X-AlbumUri'] = $filedata['playlist'];
            logger::mark("COLLECTION", "Found CUE sheet for album ".$filedata['Album']);
        }

        // Disc Number
        if ($filedata['Disc'] != null) {
            $filedata['Disc'] = format_tracknum(ltrim($filedata['Disc'], '0'));
        }

        $filedata['year'] = getYear($filedata['Date']);

        $this->player_specific_fixups($filedata);

        $numtracks++;
        $totaltime += $filedata['Time'];
        return true;

    }

    private function unmopify_file(&$filedata) {
    	global $prefs;
    	if ($filedata['Pos'] !== null) {
    		// Convert URIs for different player types to be appropriate for the collection
    		// but only when we're getting the playlist
    		if ($this->is_slave && $filedata['domain'] == 'file') {
    			$filedata['file'] = $this->swap_file_for_local($filedata['file']);
    			$filedata['domain'] = 'local';
    		}
    		if ($prefs['collection_player'] == 'mopidy' && $this->player_type == 'mpd') {
    			$filedata['file'] = $this->mpd_to_mopidy($filedata['file']);
    		}
    		if ($prefs['collection_player'] == 'mpd' && $this->player_type == 'mopidy') {
    			$filedata['file'] = $this->mopidy_to_mpd($filedata['file']);
    		}
    	}
    	// eg local:track:some/uri/of/a/file
    	// We want the path, not the domain or type
    	// This is much faster than using a regexp
    	$cock = explode(':', $filedata['file']);
        if (count($cock) > 1) {
            $file = array_pop($cock);
        } else {
    		$file = $filedata['file'];
    	}
    	return $file;
    }

    private function album_from_path($p) {
        $a = rawurldecode(basename(dirname($p)));
        if ($a == ".") {
            $a = '';
        }
        return $a;
    }

    private function artist_from_path($p, $f) {
        $a = rawurldecode(basename(dirname(dirname($p))));
        if ($a == "." || $a == "" || $a == " & ") {
            $a = ucfirst(getDomain(urldecode($f)));
        }
        return $a;
    }

    protected function check_undefined_tags(&$filedata) {
    	if ($filedata['Title'] == null) $filedata['Title'] = rawurldecode(basename($filedata['file']));
    	if ($filedata['Album'] == null) $filedata['Album'] = $this->album_from_path($filedata['unmopfile']);
    	if ($filedata['Artist'] == null) $filedata['Artist'] = array($this->artist_from_path($filedata['unmopfile'], $filedata['file']));
    }

    public function get_status() {
        return $this->do_mpd_command('status', true, false);
    }

    public function wait_for_state($expected_state) {
        if ($expected_state !== null) {
            $status = $this->get_status();
            $retries = 20;
            while ($retries > 0 && array_key_exists('state', $status) && $status['state'] != $expected_state) {
                usleep(500000);
                $retries--;
                $status = $this->get_status();
            }
        }
    }

    public function clear_error() {
        $this->send_command('clearerror');
    }

    public function get_current_song() {
        return $this->do_mpd_command('currentsong', true, false);
    }

    public function get_config() {
        if ($this->socket != '' && $this->player_type == 'mpd') {
            return $this->do_mpd_command('config', true, false);
        } else {
            return array();
        }
    }

    public function get_tagtypes() {
        return $this->do_mpd_command('tagtypes', true, false);
    }

    public function get_commands() {
        return $this->do_mpd_command('commands', true, false);
    }

    public function get_notcommands() {
        return $this->do_mpd_command('notcommands', true, false);
    }

    public function get_decoders() {
        return $this->do_mpd_command('decoders', true, false);
    }

    public function cancel_single_quietly() {
        $this->send_command('single 0');
    }

    public function get_idle_status() {
        return $this->do_mpd_command('idle player', true, false);
    }

    public function dummy_command() {
        return $this->do_mpd_command('', true, false);
    }

    public function get_playlist(&$collection) {
        $dirs = array();
        foreach ($this->parse_list_output('playlistinfo', $dirs, false) as $filedata) {
            // Check the database for extra track info
            $filedata = array_replace($filedata, get_extra_track_info($filedata));
            yield $collection->doNewPlaylistFile($filedata);
        }
    }

    public function get_currentsong_as_playlist(&$collection) {
        $dirs = array();
        $retval = array();
        foreach ($this->parse_list_output('currentsong', $dirs, false) as $filedata) {
            // Check the database for extra track info
            $filedata = array_replace($filedata, get_extra_track_info($filedata));
            $retval = $collection->doNewPlaylistFile($filedata);
        }
        return $retval;
    }

    public function populate_collection($cmd, $domains, &$collection) {
        $dirs = array();
        foreach ($this->parse_list_output($cmd, $dirs, $domains) as $filedata) {
            $collection->newTrack($filedata);
        }
    }

    public function get_uris_for_directory($path) {
        logger::log("PLAYER", "Getting Directory Items For",$path);
        $items = array();
        $parts = true;
        $lines = array();
        $this->send_command('lsinfo "'.format_for_mpd($path).'"');
        // We have to read in the entire response then go through it
        // because we only have the one connection to mpd so this function
        // is not strictly re-entrant and recursing doesn't work unless we do this.
        while(!feof($connection) && $parts) {
            $parts = $this->getline($connection);
            if ($parts === false) {
                logger::debug("PLAYER", "Got OK or ACK from MPD");
            } else {
                $lines[] = $parts;
            }
        }
        foreach ($lines as $parts) {
            if (is_array($parts)) {
                $s = trim($parts[1]);
                if (substr($s,0,1) != ".") {
                    switch ($parts[0]) {
                        case "file":
                            $items[] = $s;
                            break;

                      case "directory":
                            $items = array_merge($items, $this->get_uris_for_directory($s));
                            break;
                    }
                }
            }
        }
        return $items;
    }

    public function get_uri_handlers() {
        $handlers = $this->do_mpd_command('urlhandlers', true);
        if (is_array($handlers) && array_key_exists('handler', $handlers)) {
        	return $handlers['handler'];
        } else {
        	return array();
        }
    }

    public function get_outputs() {
        return $this->do_mpd_command('outputs', true);
    }

    public function get_stored_playlists($only_personal = false) {
        global $PLAYER_TYPE;
        $this->playlist_error = false;
        $retval = array();
        $playlists = $this->do_mpd_command('listplaylists', true, true);
        if (is_array($playlists) && array_key_exists('playlist', $playlists)) {
            $retval = $playlists['playlist'];
            usort($retval, 'sort_playlists');
            if ($only_personal) {
                $retval = array_filter($retval, $PLAYER_TYPE.'::is_personal_playlist');
            }
        } else if (is_array($playlists) && array_key_exists('error', $playlists)) {
            // We frequently get an error getting stored playlists - especially from mopidy
            // This flag is set so that loadplaylists.php doesn't remove all our stored playlist
            // images in the event of that happening.
            $this->playlist_error = true;
        }
        return $retval;
    }

    public function get_stored_playlist_tracks($playlistname, $startpos) {
        $dirs = array();
        $count = 0;
        foreach ($this->parse_list_output('listplaylistinfo "'.$playlistname.'"', $dirs, false) as $filedata) {
            if ($count >= $startpos) {
                list($class, $url) = $this->get_checked_url($filedata['file']);
                yield array($class, $url, $filedata);
            }
            $count++;
        }
    }

    public function get_tracks_for_spotify_artist($artist) {
        $dirs = array();
        $collection = new musicCollection();
        foreach ($this->parse_list_output('find "artist" "'.format_for_mpd($artist).'"', $dirs, array("spotify")) as $filedata) {
            $collection->newTrack($filedata);
        }
        return $collection->getAllTracks('add');
    }

    private function translate_commands_for_slave(&$cmds) {
        //
        // Re-check all add and playlistadd commands if we're using a Mopidy File Backend Slave
        //
        foreach ($cmds as $key => $cmd) {
            // add "local:track:
            // playlistadd "local:track:
            if (substr($cmd, 0, 17) == 'add "local:track:' ||
                substr($cmd, 0,25) == 'playlistadd "local:track:') {
                logger::log("MOPIDY", "Translating tracks for Mopidy Slave");
                $cmds[$key] = $this->swap_local_for_file($cmd);
            }
        }
    }

    private function translate_player_types(&$cmds) {
        //
        // Experimental translation to and from MPD/Mopidy Local URIs
        //
        global $prefs;
        foreach ($cmds as $key => $cmd) {
            if (substr($cmd, 0, 4) == 'add ') {
                logger::log("PLAYER", "Translating Track Uris from",$prefs['collection_player'],'to',$this->player_type);
                if ($prefs['collection_player']== 'mopidy') {
                    $cmds[$key] = $this->mopidy_to_mpd($cmd);
                } else if ($prefs['collection_player']== 'mpd'){
                    $file = trim(substr($cmd, 4), '" ');
                    $cmds[$key] = 'add '.$this->mpd_to_mopidy($file);
                }
            }
        }
    }

    private function mopidy_to_mpd($file) {
        return rawurldecode(preg_replace('#local:track:#', '', $file));
    }

    private function mpd_to_mopidy($file) {
        if (substr($file, 0, 5) != 'http:' && substr($file, 0, 6) != 'https:') {
            return 'local:track:'.implode("/", array_map("rawurlencode", explode("/", $file)));
        } else {
            return $file;
        }
    }

    private function swap_local_for_file($string) {
        // url encode the album art directory
        global $prefs;
        $path = implode("/", array_map("rawurlencode", explode("/", $prefs['music_directory_albumart'])));
        logger::log("MOPIDYSLAVE", "Replacing with",$path);
        return preg_replace('#local:track:#', 'file://'.$path.'/', $string);
    }

    private function swap_file_for_local($string) {
        global $prefs;
        $path = 'file://'.implode("/", array_map("rawurlencode", explode("/", $prefs['music_directory_albumart']))).'/';
        return preg_replace('#'.$path.'#', 'local:track:', $string);
    }

    private function probe_player_type() {
        global $prefs;
        $retval = false;
        if ($this->is_connected()) {
            logger::shout("MPDPLAYER", "Probing Player Type....");
            $r = $this->do_mpd_command('tagtypes', true, true);
            if (is_array($r) && array_key_exists('tagtype', $r)) {
                if (in_array('X-AlbumUri', $r['tagtype'])) {
                    logger::mark("MPDPLAYER", "    ....tagtypes test says we're running Mopidy. Setting cookie");
                    $retval = "mopidy";
                } else {
                    logger::mark("MPDPLAYER", "    ....tagtypes test says we're running MPD. Setting cookie");
                    $retval = "mpd";
                }
            } else {
                logger::mark("MPDPLAYER", "WARNING! No output for 'tagtypes' - probably an old version of Mopidy. RompЯ may not function correctly");
                $retval =  "mopidy";
            }
            setcookie('player_backend',$retval,time()+365*24*60*60*10,'/');
            $prefs['player_backend'] = $retval;
        }
        return $retval;
    }

}
?>