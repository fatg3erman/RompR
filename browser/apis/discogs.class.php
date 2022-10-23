<?php

class discogs {

	const BASE_URL = 'https://api.discogs.com/';

	private static function request($url, $print_data) {
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

	private static function mungeartist($artist) {
		$artist = preg_replace('/\(\d+\)$/', '', $artist);
		$bits = explode(' ', $artist);
		$pf = array_map('strtolower', prefs::get_pref('nosortprefixes'));
		if (in_array(strtolower($bits[0]), $pf)) {
			array_shift($bits);
		}
		$artist = implode(' ', $bits);
		$artist = preg_replace('/ featuring .+$/i', '', $artist);
		$artist = preg_replace('/ feat\. .+$/i', '', $artist);
		return $artist;
	}

	public static function artist_search($params, $print_data) {

		//
		// params:
		//		q 	=> artist name
		//
		$artist_options = [$params['q'], self::mungeartist($params['q'])];
		$possibilities = [];
		foreach ($artist_options as $aname) {
			$options = [
				'type' => 'artist',
				'q' => $aname
			];
			$results = self::request(self::create_url('database/search', $params), false);
			$results = json_decode($results, true);
			if ($results['results']) {
				foreach($results['results'] as $result) {
					if ($result['type'] == 'artist' && metaphone_compare($aname, $result['title'], 0)) {
						logger::log('DISCOGS', 'Search found artist', $result['title']);
						$possibilities[] = [
							'name' 	=> $result['title'],
							'link' 	=> $result['resource_url'],
							'image'	=> $result['thumb']
						];
					}
				}
			}
			if (count($possibilities) > 0)
				break;
		}
		print json_encode($possibilities);
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

		$retval = ['albumlink' => null];
		$artist_options = [$params['artist'], self::mungeartist($params['artist'])];
		foreach ($artist_options as $aname) {
			$options = [
				'type' => 'release',
				'artist' => $aname,
				'release_title' => $params['release_title']
			];
			$results = self::request(self::create_url('database/search', $options), false);
			$results = json_decode($results, true);
			// logger::log('DISCOGS', print_r($results, true));
			if ($results['results']) {
				foreach($results['results'] as $result) {
					if ($result['format'] && $result['master_url']) {
						if ($aname == 'Various' && in_array('Compilation', $result['format'])) {
							$retval['albumlink'] = self::best_albumlink($result);
							logger::log('DISCOGS', 'Album Link is',$retval['albumlink']);
							break 2;
						} else if (in_array('Album', $result['format']) && metaphone_compare($aname.' - '.$options['release_title'], $result['title'])) {
							$retval['albumlink'] = self::best_albumlink($result);
							logger::log('DISCOGS', 'Album Link is',$retval['albumlink']);
							break 2;
						}
					}
				}
			}
		}
		print json_encode($retval);
	}

	private static function best_albumlink($result) {
		if ($result['resource_url']) {
			return $result['resource_url'];
		} else {
			return $result['master_url'];
		}
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
		//		albumartist		=> artist name
		//		track 			=> track name
		//
		$retval = ['tracklink' => null];
		$artist_options = [$params['artist'], self::mungeartist($params['artist']), $params['albumartist']];
		foreach ($artist_options as $aname) {
			$options = [
				'type' => 'release',
				'artist' => $aname,
				'track' => $params['track']
			];
			$results = self::request(self::create_url('database/search', $options), false);
			$results = json_decode($results, true);
			if ($results['results']) {
				foreach($results['results'] as $result) {
					logger::log('DISCOGS', $result['title'], $aname, $options['track']);
					if ($result['format'] && $result['resource_url'] && metaphone_compare($aname.' - '.$options['track'], $result['title'])) {
						if (in_array('Single', $result['format'])) {
							$retval['tracklink'] = $result['resource_url'];
							break 2;
						}
					}
				}
			}
		}
		print json_encode($retval);
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