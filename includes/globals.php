<script language="javascript">
<?php
print "var skin = '".$skin."';\n";
print "var small_plugin_icons = '".$small_plugin_icons."';\n";
print "var only_plugins_with_icons = '".$only_plugins_with_icons."';\n";
if ($prefs['dev_mode']) {
    // This adds an extra parameter to the version number - the short
    // hash of the most recent git commit. It's for use in testing,
    // to make sure the browser pulls in the latest version of all the files.
    // DO NOT USE OUTSIDE A git REPO!
    $git_ver = exec("git log --pretty=format:'%h' -n 1", $output);
    if (count($output) == 1) {
        print "var rompr_version = '".ROMPR_VERSION.".".$output[0]."';\n";
    } else {
        print "var rompr_version = '".ROMPR_VERSION."';\n";
    }
} else {
    print "var rompr_version = '".ROMPR_VERSION."';\n";
}
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
