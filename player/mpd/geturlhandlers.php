<?php
chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
require_once ("international.php");
require_once ("player/".$prefs['player_backend']."/player.php");
$player = new $PLAYER_TYPE();
print json_encode($player->get_uri_handlers());
?>
