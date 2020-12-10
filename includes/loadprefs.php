<?php
chdir('..');
include("includes/vars.php");
print json_encode(prefs::get_safe_prefs());
?>
