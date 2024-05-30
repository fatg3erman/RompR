<?php
chdir('../..');
include ("includes/vars.php");
logger::mark("SAVEPREFS", "Saving new default UI prefs");
$p = json_decode(file_get_contents('php://input'), true);
prefs::save_ui_defaults($p);
http_response_code(204);
?>
