<?php
chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
$json = file_get_contents("php://input");
$station = json_decode($json, true);
logger::log('CUSTOMRADIO', 'Saving Station',$station['name']);
$filename = 'prefs/customradio/'.format_for_disc($station['name']).'.json';
file_put_contents($filename, $json);
header('HTTP/1.1 204 No Content');
?>