<?php

class wikidata {

	const BASE_URL = 'https://www.wikidata.org/wiki/Special:EntityData/';

	private static function request($url) {
		$cache = new cache_handler([
			'url' => $url,
			'cache' => 'wikidata',
			'return_value' => true
		]);

		return $cache->get_cache_data();
	}

	private static function create_url($entity) {
		return  self::BASE_URL.$entity.'.json';
	}

	public static function get_links($entity, $type, $language) {
		$data = json_decode(self::request(self::create_url($entity)), true);

		$wikilinks = [
			'user' => null,
			'english' => null,
			'anything' => null
		];

		$retval = [
			'wikipedia' => null,
			'allmusic' => null,
			'discogs' => [
				'artist' => null,
				'release' => null,
				'master' => null
			],
			'spotify' => [
				'artist' => null,
				'album' => null,
				'track' => null
			]
		];

		if (is_array($data)
			&& array_key_exists('entities', $data)
			&& array_key_exists($entity, $data['entities'])
		) {

			if (array_key_exists('sitelinks', $data['entities'][$entity])) {

				foreach ($data['entities'][$entity]['sitelinks'] as $link) {
					if (preg_match('/https*:\/\/'.$language.'/', $link['url'])) {
						$wikilinks['user'] = $link['url'];
					} else if (preg_match('/en\.wikipedia\.org/', $link['url'])) {
						$wikilinks['english'] = $link['url'];
					} else {
						$wikilinks['anything'] = $link['url'];
					}
				}
				$retval['wikipedia'] = self::find_first_non_null($wikilinks);
			}

			// Most of these seem to be scraped from MusicBrainz anyway, but ya never know.
			// We've got the data, why not check it.
			if (array_key_exists('claims', $data['entities'][$entity])) {
				foreach ($data['entities'][$entity]['claims'] as $property => $claim) {
					switch ($property) {
						case 'P1728':
							// AllMusic Artist link
							$retval['allmusic'] = 'https://www.allmusic.com/artist/'.$claim[0]['mainsnak']['datavalue']['value'];
							logger::debug('WIKIDATA', 'Allmusic Artist Link', $retval['allmusic']);
							break;

						case 'P1953':
							// Discogs Artist ID
							$retval['discogs']['artist'] = 'https://www.discogs.com/artist/'.$claim[0]['mainsnak']['datavalue']['value'];
							logger::debug('WIKIDATA', 'Discogs Artist Link', $retval['discogs']['artist']);
							break;

						case 'P1954':
							// Discogs Master ID
							$retval['discogs']['master'] = 'https://www.discogs.com/release/'.$claim[0]['mainsnak']['datavalue']['value'];
							logger::debug('WIKIDATA', 'Discogs Master Link', $retval['discogs']['master']);
							break;

						case 'P2206':
							// Discogs Release ID
							$retval['discogs']['release'] = 'https://www.discogs.com/master/'.$claim[0]['mainsnak']['datavalue']['value'];
							logger::debug('WIKIDATA', 'Discogs Release Link', $retval['discogs']['release']);
							break;

						case 'P1902':
							// Spotify Artist ID
							$retval['spotify']['artist'] = $claim[0]['mainsnak']['datavalue']['value'];
							logger::debug('WIKIDATA', 'Spotify Artist ID', $retval['spotify']['artist']);
							break;

						case 'P2205':
							// Spotify Album ID
							$retval['spotify']['album'] = $claim[0]['mainsnak']['datavalue']['value'];
							logger::debug('WIKIDATA', 'Spotify Album ID', $retval['spotify']['album']);
							break;

						case 'P2207':
							// Spotify Track ID
							$retval['spotify']['track'] = $claim[0]['mainsnak']['datavalue']['value'];
							logger::debug('WIKIDATA', 'Spotify Track ID', $retval['spotify']['track']);
							break;
					}
				}
			}
		}

		return $retval;

	}

	private static function find_first_non_null($w) {
		foreach ($w as $k => $v) {
			if ($v !== null) {
				logger::debug('WIKIDATA', 'Wikipedia Link',$k,$v);
				return $v;
			}
		}
		return null;
	}

}

?>