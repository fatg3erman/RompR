<?php
chdir('..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("getid3/getid3.php");
$donkeymolester = scan_for_images($_REQUEST['path']);
print json_encode($donkeymolester);
?>
