<?php

if (array_key_exists('populate', $_REQUEST)) {
	chdir('..');

	require_once ("includes/vars.php");
	require_once ("includes/functions.php");

	foreach ($_REQUEST as $i => $r) {
		logger::debug("COMMRADIO", $i,":",$r);
	}
}

$commradio = new commradioplugin();
$commradio->doWhatYoureTold();

?>

