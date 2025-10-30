<?php

class brave {

	const BASE_URL = 'https://api.search.brave.com/res/v1/';

	private static function request($url, $print_data) {
		if (prefs::get_pref('brave_api_key') != '') {
			$cache = new cache_handler([
				'url' => $url,
				'header' => [
								'X-Subscription-Token: '.prefs::get_pref('brave_api_key'),
								"Accept: application/json",
  								"Accept-Encoding: gzip"
  							],
				'cache' => 'brave',
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

		$url = brave::BASE_URL.'images/search';
		$params['count'] = 20;
		$params['safesearch'] = 'off';
		$params['spellcheck'] = 'false';
		$url .= '?'.http_build_query($params);
		logger::log('BRAVESEARCH', $url);
		return brave::request($url, $print_data);
	}
}

?>