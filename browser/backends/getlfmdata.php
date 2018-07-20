<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");

$uri = rawurldecode($_REQUEST['uri']);
$use_cache = $_REQUEST['use_cache'] == 'true' ? true : false;
getCacheData($uri, 'lastfm', $use_cache);

?>
