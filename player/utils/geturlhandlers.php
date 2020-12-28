<?php
chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
$player = new player();
print json_encode($player->get_uri_handlers());
?>
