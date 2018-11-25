<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");

$uri = rawurldecode($_REQUEST['uri']);
debuglog("Getting ".$uri, "GOOGLE");
getCacheData($uri, 'google', true);
?>
