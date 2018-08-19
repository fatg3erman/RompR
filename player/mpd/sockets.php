<?php
$is_connected = false;

function open_mpd_connection() {
    global $prefs, $connection, $is_connected;
    if ($is_connected) {
        return true;
    }
    if ($prefs['unix_socket'] != "") {
        $connection = stream_socket_client('unix://'.$prefs['unix_socket']);
    } else {
        $connection = stream_socket_client('tcp://'.$prefs["mpd_host"].':'.$prefs["mpd_port"]);
    }

    if(isset($connection) && is_resource($connection)) {
        $is_connected = true;
        stream_set_timeout($connection, 65535);
        stream_set_blocking($connection, true);
        while(!feof($connection)) {
            $gt = fgets($connection, 1024);
            if (parse_mpd_var($gt))
                break;
        }
    }

    if ($prefs['mpd_password'] != "" && $is_connected) {
        $is_connected = false;
        fputs($connection, "password ".$prefs['mpd_password']."\n");
        while(!feof($connection)) {
            $gt = fgets($connection, 1024);
            $a = parse_mpd_var($gt);
            if($a === true) {
                $is_connected = true;
                break;
            } else if ($a == null) {

            } else {
                break;
            }
        }
    }
    return $is_connected;
}

function getline($connection) {
    $got = fgets($connection, 2048);
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

function parse_mpd_var($in_str) {
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

function send_command($command) {
    global $connection, $is_connected;
    $retries = 5;
    $l = strlen($command."\n");
    do {
        $b = @fputs($connection, $command."\n");
        if (!$b || $b < $l) {
            debuglog("Socket Write Error for ".$command." - Retrying","LEMSIP",2);
            @fclose($connection);
            $is_connected = false;
            usleep(500000);
            @open_mpd_connection();
            $retries--;
        } else {
            return true;
        }
    } while ($retries > 0);
    return false;
}

function do_mpd_command($command, $return_array = false, $force_array_results = false) {

    global $is_connected, $connection, $prefs;
    $retarr = array();
    if ($is_connected) {

        debuglog("MPD Command ".$command,"MPD",9);

        $success = send_command($command);
        if ($success) {
            while(!feof($connection)) {
                $var = parse_mpd_var(fgets($connection, 1024));
                if(isset($var)){
                    if($var === true && count($retarr) == 0) {
                        // Got an OK or ACK but - no results or return_array is false
                        return true;
                    }
                    if ($var === true) {
                        break;
                    }
                    if ($var[0] == false) {
                        debuglog("Error for '".$command."'' : ".$var[1],"MPD",1);
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
            if (array_key_exists('player_backend', $prefs)) {
                $retarr['error'] = "There was an error communicating with ".ucfirst($prefs['player_backend'])."! (could not write to socket)";
            } else {
                $retarr['error'] = "There was an error communicating with the player! (could not write to socket)";
            }
        }
    }
    return $retarr;
}

function close_mpd() {
    global $connection, $is_connected;
    // @fputs($connection, 'close'."\n");
    // getline($connection);
    @fclose($connection);
    $is_connected = false;
}

?>
