<?php
function get_spotify_data($uri) {
	$authkey = "OThhZWE4M2QwZTJlNGYxMDhmM2U1YzZlOTkyOWRiMGY6NWViYmM2ZWJjODNmNDFkNzk3MzcwZThjMTE3NTIzYmU=";
	global $prefs;
	if (!array_key_exists('spotify_token', $prefs) ||
		(array_key_exists('spotify_token_expires', $prefs)) && time() > $prefs['spotify_token_expires']) {
		debuglog("Getting Spotify Credentials","SPOTIFY");
		$d = new url_downloader(array(
			'url' => 'https://accounts.spotify.com/api/token',
			'header' => array('Authorization: Basic '.$authkey),
			'postfields' => array('grant_type'=>'client_credentials')
		));
		if ($d->get_data_to_string()) {
			$stuff = json_decode($d->get_data());
			debuglog("Token is ".$stuff->{'access_token'}." expires in ".$stuff->{'expires_in'},"SPOTIFY",9);
			$prefs['spotify_token'] = $stuff->{'access_token'};
			$prefs['spotify_token_expires'] = time() + $stuff->{'expires_in'};
			savePrefs();
		} else {
			debuglog("Getting credentials FAILED!" ,"SPOTIFY");
			return array(false, null, "Bad Credentials");
		}
	}

	debuglog("Getting with Authorisation : ".$uri,"SPOTIFY");
	$d = new url_downloader(array(
		'url' => $uri,
		'header' => array('Authorization: Bearer '.$prefs['spotify_token'])
	));
	if ($d->get_data_to_string()) {
		return array(true, $d->get_data(), '200');
	} else {
		return array(false, null, $d->get_status());
	}

}
?>
