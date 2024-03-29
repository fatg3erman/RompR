<?php
chdir('../..');
include ("includes/vars.php");
logger::mark("SAVEPREFS", "Saving prefs");
$p = json_decode($_POST['prefs'], true);
foreach($p as $key => $value) {
	logger::trace("SAVEPREFS", $key,"=",$value);
	switch ($key) {
		case "radiomode":
		case "radioparam":
		case "radiodomains":
		case "stationname":
			prefs::set_radio_params([$key => $value]);
			unset($p[$key]);
			break;

		case 'music_directory_albumart':
			prefs::set_music_directory($value);
			break;

	}

}
prefs::set_pref($p);
prefs::save();
header('HTTP/1.1 204 No Content');
?>
