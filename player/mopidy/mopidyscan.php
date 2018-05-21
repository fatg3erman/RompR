<?php

chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");

$output = array('dummy' => 'baby');

if (file_exists($prefs['mopidy_scan_command'])) {

    if (array_key_exists('scan', $_REQUEST)) {
        debuglog("Starting Mopidy Scan Command","COLLECTION", 4);
        exec($prefs['mopidy_scan_command'].' > /dev/null 2>&1 &');
    } else if (array_key_exists('check', $_REQUEST)) {
        debuglog("Checking For Mopidy Scan Process","COLLECTION", 8);
        exec ('ps aux | grep "mopidy_scan.sh"', $result);
        if (count($result) > 2) {
            // There will be 3 matching process if it's running:
            // 1. The actual scan process
            // 2. The sh -c that php uses to execute the grep command
            // 3. The grep command
            debuglog("Scan Process is running","COLLECTION", 8);
            $output['updating_db'] = 1;
        } else {
            debuglog("Mopidy Scan Process has completed","COLLECTION", 4);
        }
    } else {
        debuglog("Unrecognised command for mopidy scan","COLLECTION", 2);
    }
    
} else {
    debuglog("ERROR! Mopidy scan command ".$prefs['mopidy_scan_command']." does not exist","COLLECTION", 1);
}

print json_encode($output);

?>
