<?php
chdir('../..');
include ("includes/vars.php");
logger::log("SAVEPREFS", "Saving new default UI prefs");
$p = json_decode($_POST['prefs'], true);
prefs::save_ui_defaults($p);
header('HTTP/1.1 204 No Content');
?>
