<?php

function musicCollectionUpdate() {
    global $collection;
    debuglog("Starting Music Collection Update", "MPD",4);
    $collection = new musicCollection();
	$monitor = fopen('prefs/monitor','w');
    $dirs = array("/");
    while (count($dirs) > 0) {
        $dir = array_shift($dirs);
        fwrite($monitor, "\n<b>".get_int_text('label_scanningf', array($dir))."</b><br />".get_int_text('label_fremaining', array(count($dirs))));
        doMpdParse('lsinfo "'.format_for_mpd($dir).'"', $dirs, false);
	    $collection->tracks_to_database();
    }
    fwrite($monitor, "\nUpdating Database");
    fclose($monitor);
}

?>
