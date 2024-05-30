<?php
chdir('..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
set_version_string();
header('Content-Type: text/plain');
print $version_string;
?>