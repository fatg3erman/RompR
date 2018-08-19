<?php
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");
include ("player/mpd/connection.php");
$skin = 'desktop';
$prefs['currenthost'] = 'Zero';
$prefs['player_backend'] = 'mopidy';
set_player_connect_params();

for ($i = 1; $i <= 50; $i++) {
    open_mpd_connection();
    if ($is_connected) {
        echo "$i OK\n";
    } else {
        echo "$i FAILED\n";
    }
    fputs($connection, 'idle player'."\n");
    sleep(1);
    close_mpd();
}

?>