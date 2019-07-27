<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");

$uri = rawurldecode($_REQUEST['uri']);
logger::mark("GOOGLE", "Getting",$uri);
getCacheData($uri, 'google', true);
?>
