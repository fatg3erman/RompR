<?php
chdir('../..');
include ("includes/vars.php");
logger::mark("SAVEPREFS", "Saving prefs");
$p = json_decode(file_get_contents('php://input'), true);
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
http_response_code(204);
?>
