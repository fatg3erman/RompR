<?php

class bing {

	const BASE_URL = 'https://api.bing.microsoft.com/v7.0/';

	private static function request($url, $print_data) {
		if (prefs::$prefs['bing_api_key']) {
			$cache = new cache_handler([
				'url' => $url,
				'header' => array('Ocp-Apim-Subscription-Key: '.prefs::$prefs['bing_api_key']),
				'cache' => 'bing',
				'return_value' => !$print_data
			]);
			$retval = $cache->get_cache_data();
		} else {
			$retval = json_encode(array('error' => language::gettext('label_image_search')));
			if ($print_data) {
				print $retval;
			}
		}
		return $retval;
	}

	public static function image_search($params, $print_data) {

		//
		// params:
		//		q 		=> term to search for
		//		offset 	=> offset to start results from (pagination)
		//

		$url = bing::BASE_URL.'images/search';
		$params['safeSearch'] = 'Off';
		$url .= '?'.http_build_query($params);
		logger::log('BING', $url);
		return bing::request($url, $print_data);
	}
}

?>