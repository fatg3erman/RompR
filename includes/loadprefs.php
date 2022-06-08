<?php
chdir('..');
include("includes/vars.php");
print json_encode(prefs::get_browser_prefs());
?>
