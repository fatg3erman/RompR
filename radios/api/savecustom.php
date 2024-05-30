<?php
chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");

$json = file_get_contents("php://input");
$station = json_decode($json, true);
$filename = 'prefs/customradio/'.format_for_disc($station['name']).'.json';
if (array_key_exists('delete', $station)) {
	logger::log('CUSTOMRADIO', 'Deleting Station',$station['name']);
	if (file_exists($filename)) {
		unlink($filename);
	}
} else {
	logger::log('CUSTOMRADIO', 'Saving Station',$station['name']);
	file_put_contents($filename, $json);
}
http_response_code(204);
?>