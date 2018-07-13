<?php

function musicCollectionUpdate() {
    global $collection;
    debuglog("Starting Music Collection Update", "MPD",4);
    $collection = new musicCollection();
	$monitor = fopen('prefs/monitor','w');
    $dirs = array("/");
    while (count($dirs) > 0) {
        $dir = array_shift($dirs);
        fwrite($monitor, "\n<b>Scanning Directory</b> ".$dir.'<br/>'.count($dirs).' folders remaining');
        doMpdParse('lsinfo "'.format_for_mpd($dir).'"', $dirs, false);
	    $collection->tracks_to_database();
    }
    fwrite($monitor, "\nUpdating Database");
    fclose($monitor);
}

?>
