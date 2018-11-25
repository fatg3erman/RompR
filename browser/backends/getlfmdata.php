<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");

$use_cache = $_POST['cache'] == 'true' ? true : false;
$params = array();
foreach ($_POST as $k => $v) {
    if ($k != 'cache') {
        $params[] = $k.'='.rawurlencode($v);
    }
}

$url = "https://ws.audioscrobbler.com/2.0/?";
$url .= implode('&', $params);
getCacheData($url, 'lastfm', $use_cache);
?>
