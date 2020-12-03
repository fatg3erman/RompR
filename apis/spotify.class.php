<?php

class spotify {
	const BASE_URL = 'https://api.spotify.com/';
	const AUTH_KEY = "OThhZWE4M2QwZTJlNGYxMDhmM2U1YzZlOTkyOWRiMGY6NWViYmM2ZWJjODNmNDFkNzk3MzcwZThjMTE3NTIzYmU=";

	private static function request($url, $print_data, $use_cache) {
		global $prefs;
		$cache = new cache_handler(['url' => $url]);
		if (!$use_cache || !$cache->check_cache_file('spotify', $url)) {
			// Slightly messy - we check for the existence of the cache file
			// before we do the cache_handler stuff. But if we don't then we
			// might spend a lot of time unneccesarily getting new Spotify
			// access tokens, which'll get us a bad reputation.
			list($ok, $msg, $status) =  self::check_spotify_token();
			if (!$ok)
				return json_encode(array('error' => $status, 'message' => $msg));
		}

		$cache = new cache_handler([
			'url' => $url,
			'cache' => $use_cache ? 'spotify' : null,
			'return_value' => !$print_data,
			'header' => array('Authorization: Bearer '.$prefs['spotify_token'])
		]);
		return $cache->get_cache_data();
	}

	private static function check_spotify_token() {
		global $prefs;
		if (!array_key_exists('spotify_token', $prefs) ||
			(array_key_exists('spotify_token_expires', $prefs)) && time() > $prefs['spotify_token_expires']) {
			logger::trace("SPOTIFY", "Getting Spotify Credentials");
			$d = new url_downloader(array(
				'url' => 'https://accounts.spotify.com/api/token',
				'header' => array('Authorization: Basic '.SELF::AUTH_KEY),
				'postfields' => array('grant_type'=>'client_credentials')
			));
			if ($d->get_data_to_string()) {
				$stuff = json_decode($d->get_data());
				logger::debug("SPOTIFY", "Token is ".$stuff->{'access_token'}." expires in ".$stuff->{'expires_in'});
				$prefs['spotify_token'] = $stuff->{'access_token'};
				$prefs['spotify_token_expires'] = time() + $stuff->{'expires_in'};
				savePrefs();
			} else {
				logger::warn("SPOTIFY", "Getting credentials FAILED!" );
				$stuff = json_decode($d->get_data());
				logger::log('SPOTIFY', print_r($stuff, true));
				return array(false, $stuff->{'error'}, $d->get_status());
			}
		}
		return array(true, null, null);

	}

	public static function get_url($params, $print_data) {

		// This is provided specifically to cope with spotify's paging objects
		// params:
		//		url 	: full spotify URL to get
		//

		return self::request($params['url'], $print_data, true);
	}

	public static function track_getinfo($params, $print_data) {

		//
		// params:
		// 		id 		: spotify track id
		// 		cache 	: boolean
		//

		$url = self::BASE_URL.'v1/tracks/'.$params['id'];
		return self::request($url, $print_data, $params['cache']);
	}

	public static function track_checklinking($params, $print_data) {

		//
		// params:
		// 		id 		: spotify track id or array[spotify track ids]
		// 		cache 	: boolean
		//

		global $prefs;
		if (is_array($params['id'])) {
			$url = self::BASE_URL.'v1/tracks?ids='.implode(',', $params['id']).'&market='.$prefs['lastfm_country_code'];
		} else {
			$url = self::BASE_URL.'v1/tracks/'.$params['id'].'?market='.$prefs['lastfm_country_code'];
		}
		return self::request($url, $print_data, $params['cache']);
	}

	public static function album_getinfo($params, $print_data) {

		//
		// params:
		// 		id 		: spotify album id or array[spotify album ids]
		// 		cache 	: boolean
		//

		global $prefs;
		if (is_array($params['id'])) {
			$url = self::BASE_URL.'v1/albums?ids='.implode(',', $params['id']);
		} else {
			$url = self::BASE_URL.'v1/albums/'.$params['id'];
		}
		return self::request($url, $print_data, $params['cache']);
	}

	public static function artist_getinfo($params, $print_data) {

		//
		// params:
		// 		id 		: spotify artist id
		// 		cache 	: boolean
		//

		$url = self::BASE_URL.'v1/artists/'.$params['id'];
		return self::request($url, $print_data, $params['cache']);
	}

	public static function artist_getrelated($params, $print_data) {

		//
		// params:
		// 		id 		: spotify artist id
		// 		cache 	: boolean
		//

		$url = self::BASE_URL.'v1/artists/'.$params['id'].'/related-artists';
		return self::request($url, $print_data, $params['cache']);
	}

	public static function artist_toptracks($params, $print_data) {

		//
		// params:
		// 		id 		: spotify artist id
		// 		cache 	: boolean
		//

		$url = self::BASE_URL.'v1/artists/'.$params['id'].'/top-tracks';
		return self::request($url, $print_data, $params['cache']);
	}

	public static function artist_getalbums($params, $print_data) {

		//
		// params:
		// 		id 			: spotify artist id
		//		album_type  : (string) album types
		//		limit 		: (int)
		// 		cache 		: boolean
		//

		global $prefs;
		$use_cache = $params['cache'];
		unset($params['cache']);
		$url = self::BASE_URL.'v1/artists/'.$params['id'].'/albums?';
		unset($params['id']);
		$params['market'] = $prefs['lastfm_country_code'];
		$url .= http_build_query($params);
		return self::request($url, $print_data, $use_cache);
	}

	public static function search($params, $print_data) {

		//
		// params:
		// 		q 			: search term
		//		type  		: search type
		//		limit 		: (int)
		// 		cache 		: boolean
		//

		$use_cache = $params['cache'];
		unset($params['cache']);
		$url = self::BASE_URL.'v1/search?';
		// Original js code had this regexp applied to the artist.
		// name.replace(/&|%|@|:|\+|'|\\|\*|"|\?|\//g,'')
		$url .= http_build_query($params);
		return self::request($url, $print_data, $use_cache);
	}

	public static function get_genreseeds($params, $print_data) {

		//
		// params:
		// 		cache 	: boolean
		//

		$url = self::BASE_URL.'/v1/recommendations/available-genre-seeds';
		return self::request($url, $print_data, $params['cache']);

	}

	public static function get_recommendations($params, $print_data) {

		//
		// params:
		// 		param 		: recommendation params (see Spotify API docs)
		// 		cache 		: boolean
		//

		global $prefs;
		$url = self::BASE_URL.'v1/recommendations?';
		$params['param']['market'] = $prefs['lastfm_country_code'];
		$url .= http_build_query($params['param']);
		return self::request($url, $print_data, $params['cache']);
	}

}

?>
