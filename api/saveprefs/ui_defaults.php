<?php
chdir('../..');
include ("includes/vars.php");
logger::mark("SAVEPREFS", "Saving new default UI prefs");
$p = json_decode(file_get_contents('php://input'), true);
prefs::save_ui_defaults($p);
header('HTTP/1.1 204 No Content');
?>
