<?php

// These are the functions for building the dropdowns in the file browser

require_once ("includes/vars.php");
require_once ("includes/functions.php");
require_once ("international.php");
require_once ("player/".$prefs['player_backend']."/player.php");
require_once ("player/mpd/filetree.php");
require_once ("skins/".$skin."/ui_elements.php");
$error = 0;
$dbterms = array( 'tags' => null, 'rating' => null );

$path = (array_key_exists('path', $_REQUEST)) ? $_REQUEST['path'] : "";
$prefix = (array_key_exists('prefix', $_REQUEST)) ? $_REQUEST['prefix'].'_' : "dirholder";

$player = new fileCollector();
if ($player->is_connected()) {
    if ($path == "") {
        print '<div class="configtitle textcentre expand"><b>'.get_int_text('button_file_browser').'</b></div>';
    } else {
        directoryControlHeader($prefix);
    }
	$player->doFileBrowse($path, $prefix);
} else {
    header("HTTP/1.1 500 Internal Server Error");
}

?>
