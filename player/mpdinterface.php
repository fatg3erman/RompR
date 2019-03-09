<?php
class base_mpd_player {

    private $connection;
    private $ip;
    private $port;
    private $socket;
    private $password;
    private $player_type;
    private $is_slave;
    private $array_params = array(
        "Artist",
        "AlbumArtist",
        "Composer",
        "Performer",
        "MUSICBRAINZ_ARTISTID",
    );

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

    private function open_mpd_connection() {
        if ($this->is_connected()) {
            return true;
        }
        if ($this->socket != "") {
            $this->connection = stream_socket_client('unix://'.$this->socket);
        } else {
            $this->connection = stream_socket_client('tcp://'.$this->ip.':'.$this->port);
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
        stream_socket_shutdown($this->connection, STREAM_SHUT_RDWR);
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
                debuglog("Socket Write Error for ".$command." - Retrying","LEMSIP",2);
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

            debuglog("MPD Command ".$command,"MPD",9);
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
                            debuglog("Error for '".$command."' : ".$var[1],"MPD",1);
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
                debuglog("Failure to fput command ".$command,"MPD",2);
                $retarr['error'] = "There was an error communicating with ".ucfirst($this->player_type)."! (could not write to socket)";
            }
        }
        return $retarr;
    }

    public function parse_list_output($command, &$dirs, $domains) {

        debuglog("MPD Parse ".$command,"MPD",8);

        $success = $this->send_command($command);
        $filedata = array();
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
                        if (array_key_exists('file', $filedata) && ($domains === false || in_array(getDomain($filedata['file']),$domains))) {
                            yield $filedata;
                        }
                        $filedata = array();
                        $filedata[$parts[0]] = $parts[1];
                        break;

                    case 'X-AlbumUri':
                        // Mopidy-beets is using SEMICOLONS in its URI schemes.
                        // Surely a typo, but we need to work around it by not splitting the string
                        // Same applies to file.
                        $filedata[$parts[0]] = $parts[1];
                        break;

                    default:
                        if (in_array($parts[0], $this->array_params)) {
                            $filedata[$parts[0]] = array_unique(explode(';',$parts[1]));
                        } else {
                            $filedata[$parts[0]] = explode(';',$parts[1])[0];
                        }
                        break;
                }
            }
        }

        if (array_key_exists('file', $filedata) && ($domains === false || in_array(getDomain($filedata['file']),$domains))) {
            yield $filedata;
        }
    }

    public function get_status() {
        return $this->do_mpd_command('status', true, false);
    }

    public function get_config() {
        return $this->do_mpd_command('config', true, false);
    }

    private function probe_player_type() {
        global $prefs;
        debuglog("Probing Player Type....","INIT",4);
        $r = $this->do_mpd_command('tagtypes', true, true);
        if (is_array($r) && array_key_exists('tagtype', $r)) {
            if (in_array('X-AlbumUri', $r['tagtype'])) {
                debuglog("    ....tagtypes test says we're running Mopidy. Setting cookie","INIT",4);
                setcookie('player_backend','mopidy',time()+365*24*60*60*10,'/');
                $prefs['player_backend'] = 'mopidy';
                return "mopidy";
            } else {
                debuglog("    ....tagtypes test says we're running MPD. Setting cookie","INIT",4);
                setcookie('player_backend','mpd',time()+365*24*60*60*10,'/');
                $prefs['player_backend'] = 'mpd';
                return "mpd";
            }
        } else {
            debuglog("WARNING! No output for 'tagtypes' - probably an old version of Mopidy. RompЯ may not function correctly","INIT",2);
            return false;
        }
    }

}
?>