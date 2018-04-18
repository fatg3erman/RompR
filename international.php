<?php

$languages = array();
$langnames = array();
// Always load English, as this will be used for defaults when the
// translations don't have anything.
include ('international/en.php');
$translations = $languages['en'];

$browser_language = get_browser_language();
$interface_language = array_key_exists("language", $prefs) ? $prefs["language"] : $browser_language;

if ($interface_language != "en") {
	if (file_exists('international/'.$interface_language.'.php')) {
		include ('international/'.$interface_language.'.php');
		$translations = array_merge($languages['en'], $languages[$interface_language]);
	} else {
		debuglog("Translation ".$interface_language." does not exist","INTERNATIONAL");
		$interface_language = "en";
	}
}

function get_int_text($key, $sub = null) {
	global $translations;
	if (array_key_exists($key, $translations)) {
		if (is_array($sub)) {
			return htmlspecialchars(vsprintf($translations[$key], $sub), ENT_QUOTES);
		} else {
			return htmlspecialchars($translations[$key], ENT_QUOTES);
		}
	} else {
		debuglog("ERROR! Translation key ".$key." not found!", "INTERNATIONAL");
		return "UNKNOWN KEY";
	}
}

?>