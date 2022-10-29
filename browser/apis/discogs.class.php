<?php

class discogs {

	const BASE_URL = 'https://api.discogs.com/';

	private static $timer = 0;

	private static $retval = [
		'artistmeta' => [
			'discogs' => [
				'artistid' => null,
				'possibilities' => []
			]
		],
		'albummeta' => [
			'discogs' => [
				'masterid' => null,
				'releaseid' => null
			]
		],
		'trackmeta' => [
			'discogs' => [
				'masterid' => null,
				'releaseid' => null
			]
		],
		// Don't want to send these back in the above structures because they get sent
		// to updateData() which is recursive and this data is complex.
		'metadata' => [
			'artist' => [],
			'album' => [
				'release' => null,
				'master' => null
			],
			'track' => [
				'release' => null,
				'master' => null
			]
		]
	];

	private static function request($url, $print_data) {
		if (time() < self::$timer+1) {
			sleep(1);
		}
		$cache = new cache_handler([
			'url' => $url,
			'cache' => 'discogs',
			'return_value' => !$print_data
		]);
		$retval = $cache->get_cache_data();

		// We must throttle requests to 1 per second, according to the terms of use
		if ($cache->from_cache === false)
			self::$timer = time();

		return $retval;
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

	private static function find_id($link) {
		$poo = preg_match('/[masters*|releases*|artists*]\/(\d+)/', $link, $matches);
		if ($poo) {
			return $matches[1];
		} else {
			return null;
		}
	}

	public static function verify_data($params, $print_data) {

		logger::log('DISCOGS', 'PARAMS', print_r($params, true));

		if ($params['artist']['artistlink'] == null) {
			logger::log('DISCOGS', 'Searching for Artist');
			self::$retval['artistmeta']['discogs']['possibilities'] = self::artist_search(['q' => $params['artist']['name']], false);
			if (count(self::$retval['artistmeta']['discogs']['possibilities']) > 0) {
				$params['artist']['artistlink'] = self::$retval['artistmeta']['discogs']['possibilities'][0]['link'];
				logger::log('DISCOGS', 'Search found Artist Link', $params['artist']['artistlink']);
			}
		}
		if ($params['artist']['artistlink']) {
			self::$retval['artistmeta']['discogs']['artistid'] = self::find_id($params['artist']['artistlink']);
			logger::log('DISCOGS', 'Getting Artist Data', self::$retval['artistmeta']['discogs']['artistid']);
			self::$retval['metadata']['artist']['artist_'.self::$retval['artistmeta']['discogs']['artistid']] = json_decode(self::artist_getinfo(['id' => self::$retval['artistmeta']['discogs']['artistid']], false), true);
		}

		if ($params['album']['releaselink'] == null && $params['album']['masterlink'] == null && $params['album']['artist'] != 'Radio') {
			logger::log('DISCOGS', 'Searching for Album');
			$links = self::album_search([
				'artist' => $params['album']['artist'],
				'release_title' => $params['album']['name']],
				false
			);
			$params['album']['releaselink'] = $links['releaselink'];
			$params['album']['masterlink'] = $links['masterlink'];
		}

		if ($params['album']['releaselink'] != null) {
			self::$retval['albummeta']['discogs']['releaseid'] = self::find_id($params['album']['releaselink']);
			logger::log('DISCOGS', 'Getting Album Release Data',self::$retval['albummeta']['discogs']['releaseid']);
			self::$retval['metadata']['album']['release'] = json_decode(self::album_getinfo(['id' => 'releases/'.self::$retval['albummeta']['discogs']['releaseid']], false), true);
			if ($params['album']['masterlink'] == null && self::$retval['metadata']['album']['release']['master_id']) {
				// If we don't have a master link we can find one from the release link.
				// The master link is more useful to us
				// TODO maybe if we have a master link we don't bother getting the release data
				// because we probably don't use it (check info_discogs.js)
				$params['album']['masterlink'] = 'masters/'.self::$retval['metadata']['album']['release']['master_id'];
				logger::log('DISCOGS', 'Found Album Master Link from Release Info', $params['album']['masterlink']);
			}
		}

		if ($params['album']['masterlink'] != null) {
			self::$retval['albummeta']['discogs']['masterid'] = self::find_id($params['album']['masterlink']);
			logger::log('DISCOGS', 'Getting Album Master Data',self::$retval['albummeta']['discogs']['masterid']);
			self::$retval['metadata']['album']['master'] = json_decode(self::album_getinfo(['id' => 'masters/'.self::$retval['albummeta']['discogs']['masterid']], false), true);
		}


		if ($params['track']['releaselink'] == null && $params['track']['masterlink'] == null) {
			logger::log('DISCOGS', 'Searching for Track');
			$links = self::track_search([
				'artist' => $params['track']['artist'],
				'albumartist' => $params['album']['artist'],
				'track' => $params['track']['name']],
				false
			);
			$params['track']['releaselink'] = $links['releaselink'];
			$params['track']['masterlink'] = $links['masterlink'];
		}

		if ($params['track']['releaselink'] != null) {
			self::$retval['trackmeta']['discogs']['releaseid'] = self::find_id($params['track']['releaselink']);
			logger::log('DISCOGS', 'Getting Track Release Data',self::$retval['trackmeta']['discogs']['releaseid']);
			self::$retval['metadata']['track']['release'] = json_decode(self::album_getinfo(['id' => 'releases/'.self::$retval['trackmeta']['discogs']['releaseid']], false), true);
			if ($params['track']['masterlink'] == null && self::$retval['metadata']['track']['release']['master_id']) {
				// If we don't have a master link we can find one from the release link.
				// The master link is more useful to us
				// TODO maybe if we have a master link we don't bother getting the release data
				// because we probably don't use it (check info_discogs.js)
				$params['track']['masterlink'] = 'masters/'.self::$retval['metadata']['track']['release']['master_id'];
				logger::log('DISCOGS', 'Found Track Master Link from Release Info', $params['track']['masterlink']);
			}
		}

		if ($params['track']['masterlink'] != null) {
			self::$retval['trackmeta']['discogs']['masterid'] = self::find_id($params['track']['masterlink']);
			logger::log('DISCOGS', 'Getting Track Master Data',self::$retval['trackmeta']['discogs']['masterid']);
			self::$retval['metadata']['track']['master'] = json_decode(self::album_getinfo(['id' => 'masters/'.self::$retval['trackmeta']['discogs']['masterid']], false), true);
		}

		print json_encode(self::$retval);

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
		if ($print_data) {
			print json_encode($possibilities);
		} else {
			return $possibilities;
		}
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

		$retval = ['masterlink' => null, 'releaselink' => null];
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
							$retval = self::albumlink($result);
							break 2;
						} else if (in_array('Album', $result['format']) && metaphone_compare($aname.' - '.$options['release_title'], $result['title'])) {
							$retval = self::albumlink($result);
							break 2;
						}
					}
				}
			}
		}
		if ($print_data) {
			print json_encode($retval);
		} else {
			return $retval;
		}
	}

	private static function albumlink($result) {
		$retval = ['masterlink' => null, 'releaselink' => null];
		if ($result['resource_url'])
			$retval['releaselink'] = $result['resource_url'];

		if ($result['master_url'])
			$retval['masterlink'] = $result['master_url'];

		return $retval;

	}

	public static function album_getinfo($params, $print_data) {

		//
		// params:
		//		id 		=> album discogs id either masters/12345 or releases/12345
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
		$retval = ['masterlink' => null, 'releaselink' => null];
		$artist_options = [$params['artist'], self::mungeartist($params['artist']), $params['albumartist']];

		self::$retval['tracksearch'] = [];

		foreach ($artist_options as $aname) {
			if ($aname == 'Radio')
				continue;

			$options = [
				'type' => 'release',
				'artist' => $aname,
				'track' => $params['track']
			];
			$results = self::request(self::create_url('database/search', $options), false);
			$results = json_decode($results, true);

			self::$retval['tracksearch'][] = $results;

			if ($results['results']) {
				foreach($results['results'] as $result) {
					// logger::log('DISCOGS', $result['title'], $aname, $options['track']);
					if ($result['format'] && $result['resource_url'] && metaphone_compare($aname.' - '.$options['track'], $result['title'])) {
						if (in_array('Single', $result['format'])) {
							$retval = self::albumlink($result);
							break 2;
						}
					}
				}
			}
		}
		if ($print_data) {
			print json_encode($retval);
		} else {
			return $retval;
		}
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