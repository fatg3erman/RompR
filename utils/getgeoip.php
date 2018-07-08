<?php
chdir('..');
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");
debuglog("Requesting IP address location lookup","GETLOCATION");
$d = new url_downloader(array('url' => "http://extreme-ip-lookup.com/json/"));
if ($d->get_data_to_string()) {
	$arse = json_decode($d->get_data());
	debuglog("Got response from IP location lookup. Country is ".$arse->country,"GETLOCATION");
	print $d->get_data();
} else {
	debuglog("Request to extreme-ip-lookup.com failed with status ".$content['status'],"GETLOCATION");
    print json_encode(array('country' => 'ERROR', 'countryCode' => 'HTTP Status : '.$content['status']));
}
ob_flush();
?>
