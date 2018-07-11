<?php
chdir('..');
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");
debuglog("Requesting IP address location lookup","GETLOCATION");
// ipinfo.io for some bizzarre reason requires us to NOT set a user agent, otherwise
// it just returns their homepage
$d = new url_downloader(array('url' => "https://ipinfo.io?token=230de83c74e3f3", 'useragent' => null));
if ($d->get_data_to_string()) {
	$arse = json_decode($d->get_data());
	debuglog("Got response from IP location lookup. Country is ".$arse->country,"GETLOCATION");
	print json_encode(array('country' => $arse->region, 'countryCode' => $arse->country));
} else {
	debuglog("Request to geoip service failed with status ".$d->get_status(),"GETLOCATION");
    print json_encode(array('country' => 'ERROR', 'countryCode' => 'HTTP Status : '.$d->get_status()));
}
ob_flush();
?>
