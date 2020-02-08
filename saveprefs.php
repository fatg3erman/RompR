<?php
include ("includes/vars.php");
logger::log("SAVEPREFS", "Saving prefs");
$p = json_decode($_POST['prefs']);
$player =  $_COOKIE['currenthost'];
foreach($p as $key => $value) {
	logger::log("SAVEPREFS", ' ',$key,"=",$value);
	switch ($key) {
		case "radiomode":
		case "radioparam":
		case "radiomaster":
		case "radioconsume":
			$prefs['multihosts']->{$player}->radioparams->{$key} = $value;
			break;

		case 'music_directory_albumart':
			set_music_directory($prefs[$key]);
			//fall through

		default:
			$prefs[$key] = $value;
			break;
	}

}
savePrefs();
header('HTTP/1.1 204 No Content');
?>
