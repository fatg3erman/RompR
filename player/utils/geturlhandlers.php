<?php
chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
require_once ("player/".prefs::$prefs['player_backend']."/player.php");
$player = new $PLAYER_TYPE();
print json_encode($player->get_uri_handlers());
?>
