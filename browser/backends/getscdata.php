<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");
$uri = $_POST['url'];
print download_soundcloud($uri);
?>
