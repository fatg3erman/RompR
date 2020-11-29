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

if ($prefs['bing_api_key']) {
	getCacheData($uri, 'bing', true, false, array('Ocp-Apim-Subscription-Key: '.$prefs['bing_api_key']));
} else {
	print json_encode(array('error' => get_int_text('label_image_search')));
}

?>
