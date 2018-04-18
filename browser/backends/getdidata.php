<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");

$uri = rawurldecode($_REQUEST['uri']);
$uri = preg_replace('/\s/',"%20",$uri);
getCacheData($uri, 'discogs');

?>
