<?php
include ("includes/vars.php");
debuglog("Saving prefs","SAVEPREFS");
$p = json_decode($_POST['prefs']);
foreach($p as $key => $value) {
    debuglog('  '.$key." = ".print_r($value, true),"SAVEPREFS");
    $prefs[$key] = $value;
    if ($key == "music_directory_albumart") {
        set_music_directory($prefs[$key]);
    }
}
savePrefs();
header('HTTP/1.1 204 No Content');
?>
