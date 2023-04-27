<?php

class spotify {
	const BASE_URL = 'https://api.spotify.com/v1';
	const AUTH_KEY = "OThhZWE4M2QwZTJlNGYxMDhmM2U1YzZlOTkyOWRiMGY6NWViYmM2ZWJjODNmNDFkNzk3MzcwZThjMTE3NTIzYmU=";

	private static function request($url, $print_data, $use_cache) {
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
			'header' => array('Authorization: Bearer '.prefs::get_pref('spotify_token'))
		]);
		return $cache->get_cache_data();
	}

	private static function check_spotify_token() {
		// Note for when you're confused by this. time() is always > null
		if (prefs::get_pref('spotify_token') == null || time() > prefs::get_pref('spotify_token_expires')) {
			logger::log("SPOTIFY", "Getting Spotify Credentials");
			$d = new url_downloader(array(
				'url' => 'https://accounts.spotify.com/api/token',
				'header' => array('Authorization: Basic '.SELF::AUTH_KEY),
				'postfields' => array('grant_type'=>'client_credentials')
			));
			if ($d->get_data_to_string()) {
				$stuff = json_decode($d->get_data());
				logger::debug("SPOTIFY", "Token is ".$stuff->{'access_token'}." expires in ".$stuff->{'expires_in'});
				prefs::set_pref([
					'spotify_token' => $stuff->{'access_token'},
					'spotify_token_expires' => time() + $stuff->{'expires_in'}
				]);
				prefs::save();
			} else {
				$stuff = json_decode($d->get_data());
				logger::warn("SPOTIFY", "Getting credentials FAILED!",print_r($stuff, true));
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

		$url = self::BASE_URL.'/tracks/'.$params['id'];
		return self::request($url, $print_data, $params['cache']);
	}

	public static function track_checklinking($params, $print_data) {

		//
		// params:
		// 		id 		: spotify track id or array[spotify track ids]
		// 		cache 	: boolean
		//

		if (is_array($params['id'])) {
			$url = self::BASE_URL.'/tracks?ids='.implode(',', $params['id']).'&market='.prefs::get_pref('lastfm_country_code');
		} else {
			$url = self::BASE_URL.'/tracks/'.$params['id'].'?market='.prefs::get_pref('lastfm_country_code');
		}
		return self::request($url, $print_data, $params['cache']);
	}

	public static function album_getinfo($params, $print_data) {

		//
		// params:
		// 		id 		: spotify album id or array[spotify album ids]
		// 		cache 	: boolean
		//

		if (is_array($params['id'])) {
			$url = self::BASE_URL.'/albums?ids='.implode(',', $params['id']);
		} else {
			$url = self::BASE_URL.'/albums/'.$params['id'];
		}
		return self::request($url, $print_data, $params['cache']);
	}

	public static function artist_getinfo($params, $print_data) {

		//
		// params:
		// 		id 		: spotify artist id
		// 		cache 	: boolean
		//

		$url = self::BASE_URL.'/artists/'.$params['id'];
		return self::request($url, $print_data, $params['cache']);
	}

	public static function artist_getrelated($params, $print_data) {

		//
		// params:
		// 		id 		: spotify artist id
		// 		cache 	: boolean
		//

		$url = self::BASE_URL.'/artists/'.$params['id'].'/related-artists';
		return self::request($url, $print_data, $params['cache']);
	}

	public static function artist_toptracks($params, $print_data) {

		//
		// params:
		// 		id 		: spotify artist id
		// 		cache 	: boolean
		//

		$url = self::BASE_URL.'/artists/'.$params['id'].'/top-tracks/?market='.prefs::get_pref('lastfm_country_code');
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

		$use_cache = $params['cache'];
		unset($params['cache']);
		$url = self::BASE_URL.'/artists/'.$params['id'].'/albums?';
		unset($params['id']);
		$params['market'] = prefs::get_pref('lastfm_country_code');
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

		$params['market'] = prefs::get_pref('lastfm_country_code');
		$use_cache = $params['cache'];
		unset($params['cache']);
		$url = self::BASE_URL.'/search?';
		$url .= http_build_query($params);
		return self::request($url, $print_data, $use_cache);
	}

	public static function find_possibilities($params, $print_data) {
		$search_results = self::search(
			[
				'q'		=> $params['name'],
				'type'	=> 'artist',
				'limit'	=> 50,
				'cache' => $params['cache']
			],
			false
		);
		$candidates = json_decode($search_results, true);
		$possibilities = [];
		if (array_key_exists('artists', $candidates) && array_key_exists('items', $candidates['artists'])) {
			foreach ($candidates['artists']['items'] as $willies) {
				if (metaphone_compare($params['name'], $willies['name'], 0)) {
					$possibilities[] = self::make_poss($willies);
				}
			}
			// Don't do this, it's worse than returning nothing since it's never right.
			// if (count($possibilities) == 0 && is_array($candidates) && count($candidates) == 1) {
			// 	$possibilities[] = self::make_poss(array_shift($candidates['artists']['items']));
			// }
		}
		print json_encode($possibilities);
	}

	private static function make_poss($willies) {
		if ($willies['images'] && count($willies['images']) > 0) {
			$im = array_pop($willies['images']);
			$im = $im['url'];
		} else {
			$im = null;
		}
		return [
			'name'	=> $willies['name'],
			'id'	=> $willies['id'],
			'image'	=> $im
		];
	}

	public static function get_genreseeds($params, $print_data) {

		//
		// params:
		// 		cache 	: boolean
		//

		$url = self::BASE_URL.'/recommendations/available-genre-seeds';
		return self::request($url, $print_data, $params['cache']);

	}

	public static function get_markets() {
		$url = self::BASE_URL.'/markets';
		$m = json_decode(self::request($url, false, true), true);
		if (array_key_exists('markets', $m) && is_array($m['markets'])) {
			return $m['markets'];
		} else {
			return [];
		}

	}

	public static function get_recommendations($params, $print_data) {

		//
		// params:
		// 		param 		: recommendation params (see Spotify API docs)
		// 		cache 		: boolean
		//

		$url = self::BASE_URL.'/recommendations?';
		$params['param']['market'] = prefs::get_pref('lastfm_country_code');
		$url .= http_build_query($params['param']);
		return self::request($url, $print_data, $params['cache']);
	}

}

?>
