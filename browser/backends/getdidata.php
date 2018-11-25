<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");

$uri = $_POST['url'];
$params = array();
foreach ($_POST as $k => $v) {
    if ($k != 'url') {
        $params[] = $k.'='.rawurlencode($v);
    }
}
if (count($params) > 0) {
    $uri .= "?".implode('&', $params);
}
getCacheData($uri, 'discogs');

?>
