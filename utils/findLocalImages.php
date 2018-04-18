<?php
chdir('..');
include ("includes/vars.php");
include ("includes/functions.php");
$donkeymolester = scan_for_images($_REQUEST['path']);
print json_encode($donkeymolester);
?>
