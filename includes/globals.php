<script language="javascript">
<?php
print "var skin = '".$skin."';\n";
print "var small_plugin_icons = '".$small_plugin_icons."';\n";
print "var only_plugins_on_menu = '".$only_plugins_on_menu."';\n";
print "var rompr_version = '".$version_string."';\n";
print "var collection_status = ".checkCollectionStatus().";\n";
print "var old_style_albumart = ".checkAlbumArt().";\n";
print "prefs.skin = '".$skin."';\n";
print "var interfaceLanguage = '".$interface_language."';\n";
print "var browserLanguage = '".$browser_language."';\n";
print "var mopidy_min_version = '".ROMPR_MOPIDY_MIN_VERSION."';\n";
print "var player_ip = '".get_player_ip()."';\n";
print "var rompr_unknown_stream = '".ROMPR_UNKNOWN_STREAM."';\n";
if ($oldmopidy) {
    print "var mopidy_is_old = true;\n";
} else {
    print "var mopidy_is_old = false;\n";
}

// Three translation keys are needed so regularly it makes sense to
// have them as static variables, instead of looking them up every time
print "var frequentLabels = {\n";
print "    of: '".get_int_text("label_of")."',\n";
print "    by: '".get_int_text("label_by")."',\n";
print "    on: '".get_int_text("label_on")."'\n";
print "};\n";
?>
</script>
