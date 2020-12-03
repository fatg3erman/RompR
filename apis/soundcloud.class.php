<?php

class soundcloud {

	const BASE_URL = 'https://api.soundcloud.com/';
	const ID_THING = '?client_id=6f43d0d67acd6635273ffd6eeed302aa';

	private static function request($url, $print_data) {
		global $prefs;
		$cache = new cache_handler([
			'url' => $url.soundcloud::ID_THING,
			'cache' => 'soundcloud',
			'return_value' => !$print_data
		]);
		return $cache->get_cache_data();
	}

	public static function track_info($params, $print_data) {

		//
		// params:
		//		trackid 	=> id of track to get info for
		//

		$url = soundcloud::BASE_URL.'tracks/'.$params['trackid'].'.json';
		return soundcloud::request($url, $print_data);
	}

	public static function user_info($params, $print_data) {

		//
		// params:
		//		userid 		=> id of user to get info for
		//

		$url = soundcloud::BASE_URL.'users/'.$params['userid'].'.json';
		return soundcloud::request($url, $print_data);
	}

}

?>