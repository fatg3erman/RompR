<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include("international.php");
include ("player/mpd/connection.php");

$handlers = do_mpd_command('urlhandlers', true);
if (array_key_exists('handler', $handlers)) {
	print json_encode($handlers['handler']);
} else {
	print json_encode(array());
}
close_mpd();
?>