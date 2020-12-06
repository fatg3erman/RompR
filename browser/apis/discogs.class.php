<?php

class discogs {

	const BASE_URL = 'https://api.discogs.com/';

	private static function request($url, $print_data) {
		global $prefs;
		$cache = new cache_handler([
			'url' => $url,
			'cache' => 'discogs',
			'return_value' => !$print_data
		]);
		return $cache->get_cache_data();
	}

	private static function create_url($uri, $params) {
		if (array_key_exists('id', $params)) {
			unset($params['id']);
		}
		$params['key'] = 'qmBviLdmIHhnxXkzWLHR';
		$params['secret'] = 'KAtjSjsJJlfQjdCXUrnbyXAltXDfelaV';
		return self::BASE_URL.$uri.'?'.http_build_query($params);
	}

	public static function artist_search($params, $print_data) {

		//
		// params:
		//		q 	=> artist name
		//

		$params['type'] = 'artist';
		return self::request(self::create_url('database/search', $params), $print_data);
	}

	public static function artist_getinfo($params, $print_data) {

		//
		// params:
		//		id 	=> artist discogs id
		//

		return self::request(self::create_url('artists/'.$params['id'], $params), $print_data);
	}

	public static function artist_getreleases($params, $print_data) {

		//
		// params:
		//		id 		=> artist discogs id (or name???)
		//		page 	=> page (for pagination)
		//

		$params['per_page'] = 25;
		return self::request(self::create_url('artists/'.$params['id'].'/releases', $params), $print_data);
	}

	public static function album_search($params, $print_data) {

		//
		// params:
		//		artist 			=> artist name
		//		release_title 	=> album name
		//

		$params['type'] = 'release';
		return self::request(self::create_url('database/search', $params), $print_data);
	}

	public static function album_getinfo($params, $print_data) {

		//
		// params:
		//		id 		=> album discogs id
		//

		return self::request(self::create_url($params['id'], $params), $print_data);
	}

	public static function track_search($params, $print_data) {

		//
		// params:
		//		artist 			=> artist name
		//		track 			=> track name
		//

		$params['type'] = 'release';
		return self::request(self::create_url('database/search', $params), $print_data);
	}

	public static function label_getinfo($params, $print_data) {

		//
		// params:
		//		id 		=> label discogs id
		//

		return self::request(self::create_url('labels/'.$params['id'], $params), $print_data);
	}

}

?>