<?php

// This proxies last.fm POST requests via the webserver
// because scrobbling was starting to give me
// Access-Control-Allow-Origin errors when done via AJAX.

chdir('..');
include('includes/vars.php');
include('includes/functions.php');
debuglog("Handling Last.FM Proxy Request","LAST.FM");
$post_url = "http://ws.audioscrobbler.com/2.0/";
$lastfm_secret="3ddf4cb9937e015ca4f30296a918a2b0";

$postified = '';
$sigstring = '';
$vars = $_POST;
ksort($vars);
foreach ($vars as $key => $value) {
	if ($key != 'api_key' && $key != 'sk') { debuglog($key." = ".$value,"LAST.FM"); }
	if (!is_utf8($key)) {
		debugprint("ERROR! Key ".$key." is not UTF-8","LASTFM",3);
	}
	if (!is_utf8($value)) {
		debugprint("ERROR! Value ".$value." is not UTF-8","LASTFM",3);
	}
    $postified .= $key.'='.$value.'&';
    $sigstring .= $key.$value;
}
$postified .= "api_sig=".md5($sigstring.$lastfm_secret);
//open connection
$ch = curl_init();

//set the url, number of POST vars, POST data
curl_setopt($ch,CURLOPT_URL, $post_url);
curl_setopt($ch,CURLOPT_POST, count($vars)+1);
curl_setopt($ch,CURLOPT_POSTFIELDS, $postified);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_USERAGENT, ROMPR_IDSTRING);
# ignore SSL errors
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 90);
if ($prefs['proxy_host'] != "") {
    curl_setopt($ch, CURLOPT_PROXY, $prefs['proxy_host']);
}
if ($prefs['proxy_user'] != "" && $prefs['proxy_password'] != "") {
    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $prefs['proxy_user'].':'.$prefs['proxy_password']);
}

//execute post
$result = curl_exec($ch);

$status = curl_getinfo($ch,CURLINFO_HTTP_CODE);
$contenttype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($status != 200) {
	$http_codes = parse_ini_file("resources/http_status_codes.txt");
	if (array_key_exists($status, $http_codes)) {
		debuglog("Return Status Was ".$status." ".$http_codes[$status],"LAST.FM");
		$x = simplexml_load_string($result);
		debuglog("LastFM Response was '".trim($x->error)."'","LAST.FM");
		header("HTTP/1.1 ".$status." ".$http_codes[$status]);
	} else {
		debuglog("Return Status Was ".$status,"LAST.FM");
		header("HTTP/1.1 500 Internal Server Error");
	}
} else {
	debuglog("Request Success","LAST.FM");
}
header('Content-Type: '.$contenttype);
print $result;

function is_utf8($str) {
    return (bool) preg_match('//u', $str);
}

?>
