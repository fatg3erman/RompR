<?php
class javascript_globals {
	public static function print_globals() {
		global $skin, $version_string;
		print '<script language="javascript">'."\n";
		print "var collection_status = ".prefs::$database->checkCollectionStatus().";\n";
		print "var old_style_albumart = ".prefs::$database->checkAlbumArt().";\n";
		// There is no default language set in prefs, so that we can try to detect
		// it from the browser. This we set a global here so we know what it was.
		print "prefs.interface_language = '".language::get_interface_language()."';\n";
		print "prefs.skin = '".$skin."';\n";
		print "const skin = '".$skin."';\n";
		print "const only_plugins_on_menu = '".uibits::ONLY_PLUGINS_ON_MENU."';\n";
		print "const rompr_version = '".$version_string."';\n";
		print "const browserLanguage = '".language::get_browser_language()."';\n";
		print "const mopidy_min_version = '".ROMPR_MOPIDY_MIN_VERSION."';\n";
		print "const player_ip = '".get_player_ip()."';\n";
		print "const rompr_unknown_stream = '".ROMPR_UNKNOWN_STREAM."';\n";
		// Three translation keys are needed so regularly it makes sense to
		// have them as static variables, instead of looking them up every time
		print "const frequentLabels = {\n";
		print "    of: '".language::gettext("label_of")."',\n";
		print "    by: '".language::gettext("label_by")."',\n";
		print "    on: '".language::gettext("label_on")."'\n";
		print "};\n";
		print '</script>'."\n";
	}
}
?>
