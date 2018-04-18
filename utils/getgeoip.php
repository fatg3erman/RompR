<?php
chdir('..');
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");
debuglog("Requesting IP address location lookup","GETLOCATION");
$content = url_get_contents("http://freegeoip.net/json");
if ($content['status'] == "200") {
	$arse = json_decode($content['contents']);
	debuglog("Got response from freegeoip.net. Country is ".$arse->country_name,"GETLOCATION");
	print $content['contents'];
} else {
	debuglog("Request to freegeoip.net failed with status ".$content['status'],"GETLOCATION");
    print json_encode(array('country' => 'ERROR', 'country_code' => 'unclepeter'));
}
ob_flush();
?>
