<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");
$clientid = "6f43d0d67acd6635273ffd6eeed302aa";
$uri = $_POST['url'];
getCacheData('https://api.soundcloud.com/'.$uri.'?client_id='.$clientid, 'soundcloud', true);
?>
