<?php
chdir('../..');
include ("includes/vars.php");
logger::log("SAVEPREFS", "Saving prefs");
$p = json_decode($_POST['prefs'], true);
$player =  $_COOKIE['currenthost'];
foreach($p as $key => $value) {
	logger::log("SAVEPREFS", ' ',$key,"=",$value);
	switch ($key) {
		case "radiomode":
		case "radioparam":
		case "radiomaster":
		case "radioconsume":
			prefs::$prefs['multihosts'][$player]['radioparams'][$key] = $value;
			break;

		case 'music_directory_albumart':
			prefs::set_music_directory($value);
			break;

		default:
			prefs::$prefs[$key] = $value;
			break;
	}

}
prefs::save();
header('HTTP/1.1 204 No Content');
?>
