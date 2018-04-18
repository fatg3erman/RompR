<?php
include ("includes/vars.php");
debuglog("Saving prefs","SAVEPREFS");
$p = json_decode($_POST['prefs']);
foreach($p as $key => $value) {
    debuglog($key."=".print_r($value, true),"SAVEPREFS");
    $prefs[$key] = $value;
    if ($key == "music_directory_albumart") {
    	debuglog("Creating Album Art SymLink","SAVEPREFS");
		if (is_link("prefs/MusicFolders")) {
			system ("unlink prefs/MusicFolders");
		}
		system ('ln -s "'.$prefs[$key].'" prefs/MusicFolders');
    }
}
savePrefs();
?>
<html></html>