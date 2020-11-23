<?php
chdir('..');
include("includes/vars.php");
$safeprefs = array();
foreach ($prefs as $p => $v) {
	if (!in_array($p, $private_prefs)) {
		$safeprefs[$p] = $v;
	}
}
print json_encode($safeprefs);
?>
