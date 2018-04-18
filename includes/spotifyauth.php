<?php
function get_spotify_data($uri) {
	$authkey = "OThhZWE4M2QwZTJlNGYxMDhmM2U1YzZlOTkyOWRiMGY6NWViYmM2ZWJjODNmNDFkNzk3MzcwZThjMTE3NTIzYmU=";
	global $prefs;
	if (!array_key_exists('spotify_token', $prefs) ||
		(array_key_exists('spotify_token_expires', $prefs)) && time() > $prefs['spotify_token_expires']) {
		debuglog("Getting Spotify Credentials","SPOTIFY");
		$result = url_get_contents('https://accounts.spotify.com/api/token', ROMPR_IDSTRING, false, true,
			false, null, array('Authorization: Basic '.$authkey), array('grant_type'=>'client_credentials'));
		if ($result['status'] == "200") {
			$stuff = json_decode($result['contents']);
			debuglog("Token is ".$stuff->{'access_token'}." expires in ".$stuff->{'expires_in'},"SPOTIFY");
			$prefs['spotify_token'] = $stuff->{'access_token'};
			$prefs['spotify_token_expires'] = time() + $stuff->{'expires_in'};
			savePrefs();
		} else {
			debuglog("Getting credentials FAILED! ".$result['status'],"SPOTIFY");
		}
	}

	debuglog("Getting with Authorisation : ".$uri,"SPOTIFY");
	$content = url_get_contents($uri, ROMPR_IDSTRING, false, true, false, null, 
		array('Authorization: Bearer '.$prefs['spotify_token']));
	return $content;
}
?>