<?php
require_once ("includes/vars.php");
require_once ("includes/functions.php");
require_once ("international.php");
require_once ("player/".$prefs['player_backend']."/player.php");
require_once ("backends/sql/backend.php");
require_once ("utils/imagefunctions.php");
require_once ("collection/playlistcollection.php");

// For speed and memory usage we do two VERY IMPORTANT things
// 1. JSON encode each track one-by-one, print out the result, and then throw that data away
// 2. Don't use the Collection data models.
// This saves potentially hundreds of MB of RAM - json_encode is astonishingly inefficient,
// and the Collection data models use up a lot of memory for things we just don't need here

// This is intended to be a fast pipe to convert MPD data into RompR data.

header('Content-Type: application/json; charset=utf-8');
$doneone = false;

$player = new $PLAYER_TYPE();
$collection = new playlistCollection();
print '[';
foreach ($player->get_playlist($collection) as $info) {
    if ($doneone) {
        print ', ';
    } else {
        $doneone = true;
    }
    print json_encode($info);
};
print ']';
ob_flush();
debuglog("Playlist Output Is Done","GETPLAYLIST");

?>
