<?php

class musicbrainz {

	const BASE_URL = 'http://musicbrainz.org/ws/2/';
	const COVER_URL = 'http://coverartarchive.org/release/';

	private static $timer = 0;

	// To this we need to add
	// metadata
	// artist => [musicbrainz_id] => {artist data}
	// album  => release => [musicbrainz_id] => {release data}
	//		  => release_group => {release group data}
	// track  => recording => {track data}
	//		  => work => {work data}

	// Note this needs to match the base data in info_musicbrainz.collection.populate()
	// and naything we can't find MUST be returned as null or no triggers will fire

	private static $retval = [
		'artistmeta' => [
			'disambiguation' => null,
			'musicbrainz' => [ 'musicbrainz_id' => null ],
			'wikipedia' => ['link' => null],
			'discogs' => [
				'artistlink' => null
			],
			'spotify' => [
				'id' => null
			],
			'allmusic' => ['link' => null]
		],
		'albummeta' => [
			'disambiguation' => null,
			'musicbrainz' => [
				'musicbrainz_id' => null,
				'releasegroup_id' => null
			],
			'wikipedia' => ['link' => null],
			'discogs' => [
				'masterlink' => null,
				'releaselink' => null
			],
			'allmusic' => ['link' => null]
		],
		'trackmeta' => [
			'disambiguation' => null,
			'musicbrainz' => [ 'musicbrainz_id' => null ],
			'wikipedia' => ['link' => null],
			'discogs' => [
				'masterlink' => null,
				'releaselink' => null
			],
			'allmusic' => ['link' => null]
		],
		// Don't want to send these back in the above structures because they get sent
		// to updateData() which is recursive and this data is complex.
		'metadata' => [
			'artist' => null,
			'album' => [
				'release' => null,
				'release_group' => null
			],
			'track' => [
				'recording' => null,
				'work' => null
			]
		]
	];

	private static function request($url, $print_data) {
		if (time() < self::$timer+1) {
			sleep(1);
		}
		$cache = new cache_handler([
			'url' => $url,
			'cache' => 'musicbrainz',
			'return_value' => !$print_data
		]);

		$retval = $cache->get_cache_data();
		// We must throttle requests to 1 per second, according to the terms of use
		if ($cache->from_cache === false)
			self::$timer = time();

		return $retval;
	}

	private static function create_url($uri, $params) {
		$params['fmt'] = 'json';
		unset($params['mbid']);
		return  self::BASE_URL.$uri.'?'.http_build_query($params);
	}

	// $params
	// language: wikipedia language
	// artist {
	// 	name: artistmeta.name,
	// 	musicbrainz_id: artistmeta.musicbrainz_id
	// },
	// album: {
	// 	name: (parent.playlistinfo.type == 'stream') ? null : albummeta.name,
	// 	artist: albummeta.artist,
	// 	musicbrainz_id: albummeta.musicbrainz_id (this is the RELEASE id)
	// },
	// track: {
	// 	name: trackmeta.name,
	// 	musicbrainz_id: trackmeta.musicbrainz_id (this is a recording or work, probably)
	// 	artist: trackmeta.artist
	// },

	public static function verify_data($params, $print_data) {

		// logger::debug('MUSICBRAINZ', print_r($params, true));

		if ($params['artist']['name'] == '' && $params['album']['artist'] == 'Radio' && $params['track']['name'] == '') {
			print json_encode(self::$retval);
			exit(0);
		}

		if ($params['artist']['musicbrainz_id']) {
			self::$retval['artistmeta']['musicbrainz']['musicbrainz_id'] = $params['artist']['musicbrainz_id'];
		} else {
			self::find_artist($params['artist']['name'], $params['album']['name'], $params['album']['artist'], $params['track']['name']);
		}

		if (self::$retval['artistmeta']['musicbrainz']['musicbrainz_id']) {
			logger::debug('MUISCBRAINZ', 'Getting artist info for', self::$retval['artistmeta']['musicbrainz']['musicbrainz_id']);
			self::$retval['metadata']['artist'][self::$retval['artistmeta']['musicbrainz']['musicbrainz_id']] =
				json_decode(self::artist_getinfo(['mbid' => self::$retval['artistmeta']['musicbrainz']['musicbrainz_id']], false), true);
			self::$retval['albummeta']['musicbrainz']['releasegroup_id'] =
				self::releasegroup_search(self::$retval['artistmeta']['musicbrainz']['musicbrainz_id'], $params['album']['name']);
		}

		if (self::$retval['albummeta']['musicbrainz']['releasegroup_id']) {
			logger::debug('MUSICBRAINZ', 'Getting Release Group info for', self::$retval['albummeta']['musicbrainz']['releasegroup_id']);
			self::$retval['metadata']['album']['release_group'] =
				json_decode(self::releasegroup_getinfo(['mbid' => self::$retval['albummeta']['musicbrainz']['releasegroup_id']], false), true);
		}

		if (!$params['album']['musicbrainz_id']) {
			// This will be the release ID, not the release group
			$params['album']['musicbrainz_id'] = self::scan_release_group();
		}

		self::$retval['albummeta']['musicbrainz']['musicbrainz_id'] = $params['album']['musicbrainz_id'];
		if (self::$retval['albummeta']['musicbrainz']['musicbrainz_id']) {
			logger::debug('MUSICBRAINZ', 'Getting Album Release info for',self::$retval['albummeta']['musicbrainz']['musicbrainz_id']);
			self::$retval['metadata']['album']['release'][self::$retval['albummeta']['musicbrainz']['musicbrainz_id']] =
				json_decode(self::album_getinfo(['mbid' => self::$retval['albummeta']['musicbrainz']['musicbrainz_id']], false), true);
		}

		if (!$params['track']['musicbrainz_id']) {
			if (self::$retval['metadata']['album']['release'] == null || self::$retval['albummeta']['musicbrainz']['musicbrainz_id'] == null) {
				$params['track']['musicbrainz_id'] = self::recording_search($params['track']['name'], self::$retval['artistmeta']['musicbrainz']['musicbrainz_id']);
			} else {
				$params['track']['musicbrainz_id'] = self::track_search($params['track']['name']);
			}
		}

		self::$retval['trackmeta']['musicbrainz']['musicbrainz_id'] = $params['track']['musicbrainz_id'];
		if (self::$retval['trackmeta']['musicbrainz']['musicbrainz_id']) {
			logger::debug('MUSICBRAINZ', 'Getting recording info for',self::$retval['trackmeta']['musicbrainz']['musicbrainz_id']);
			self::$retval['metadata']['track']['recording'] =
				json_decode(self::track_getinfo(['mbid' => self::$retval['trackmeta']['musicbrainz']['musicbrainz_id']], false), true);
			self::find_work_data();
		}

		self::scrape_artist_links($params['language']);
		self::scrape_album_links($params['language']);
		self::scrape_track_links($params['language']);

		print json_encode(self::$retval);

	}

	private static function scrape_artist_links($language) {
		if (self::$retval['artistmeta']['musicbrainz']['musicbrainz_id'] !== null) {
			logger::debug('MUSICBRAINZ', 'Scraping artst data');
			self::scan_for_links(
				self::$retval['artistmeta'],
				self::$retval['metadata']['artist'][self::$retval['artistmeta']['musicbrainz']['musicbrainz_id']],
				$language,
				'artist'
			);
		}
	}

	private static function scrape_album_links($language) {
		// Prioritise releasegroup over release, but scan both
		if (self::$retval['metadata']['album']['release'] !== null) {
			logger::debug('MUSICBRAINZ', 'Scraping Album Release Info');
			self::scan_for_links(
				self::$retval['albummeta'],
				self::$retval['metadata']['album']['release'][self::$retval['albummeta']['musicbrainz']['musicbrainz_id']],
				$language,
				'album'
			);
		}

		if (self::$retval['metadata']['album']['release_group'] !== null) {
			logger::debug('MUSICBRAINZ', 'Scraping Album Release Group Info');
			self::scan_for_links(
				self::$retval['albummeta'],
				self::$retval['metadata']['album']['release_group'],
				$language,
				'album'
			);
		}
	}

	private static function scrape_track_links($language) {
		if (self::$retval['metadata']['track']['recording'] !== null) {
			logger::debug('MUSICBRAINZ', 'Scraping Track Recording Info');
			self::scan_for_links(
				self::$retval['trackmeta'],
				self::$retval['metadata']['track']['recording'],
				$language,
				'track'
			);
		}
		if (self::$retval['metadata']['track']['work'] !== null) {
			logger::debug('MUSICBRAINZ', 'Scraping Track Work Info');
			self::scan_for_links(
				self::$retval['trackmeta'],
				self::$retval['metadata']['track']['work'],
				$language,
				'track'
			);
		}
	}

	private static function scan_for_links(&$destination, &$data, $language, $type) {

		logger::log('MUSICBRAINZ', 'Scanning For Links For', $type);

		if ($data['disambiguation'])
			$destination['disambiguation'] = $data['disambiguation'];

		$wikidata = null;

		$destination['wikipedia']['link'] = self::get_wikipedia_link($data['relations'], $language);

		foreach ($data['relations'] as $relation) {

			if ($relation['type'] == 'discogs' && $relation['target-type'] == 'url') {
				// We want a discogs ID, not a name
				if ($type != 'artist' && $destination['discogs']['masterlink'] == null && preg_match('/\/masters*\/\d+/', $relation['url']['resource'])) {
					logger::debug('MUSICBRAINZ', 'Found Discogs Master link',$relation['url']['resource']);
					$destination['discogs']['masterlink'] = $relation['url']['resource'];
				} else if ($type != 'artist' && $destination['discogs']['releaselink'] == null && preg_match('/\/releases*\/\d+/', $relation['url']['resource'])) {
					logger::debug('MUSICBRAINZ', 'Found Discogs Release link',$relation['url']['resource']);
					$destination['discogs']['releaselink'] = $relation['url']['resource'];
				} else if ($type == 'artist' && $destination['discogs']['artistlink'] == null && preg_match('/\/artists*\/\d+/', $relation['url']['resource'])) {
					logger::debug('MUSICBRAINZ', 'Found Discogs Artist link',$relation['url']['resource']);
					$destination['discogs']['artistlink'] = $relation['url']['resource'];
				}
			}

			if ($relation['type'] == 'allmusic' && $relation['target-type'] == 'url') {
				logger::debug('MUSICBRAINZ', 'Found Allmusic Link',$relation['url']['resource']);
				$destination['allmusic']['link'] = $relation['url']['resource'];
			}

			if ($relation['type'] == 'free streaming'
				&& $relation['target-type'] == 'url'
				&& preg_match('/open\.spotify\.com\/.+?\/(.+)/', $relation['url']['resource'], $matches)) {
				$destination['spotify']['id'] = $matches[1];
				logger::debug('MUSICBRAINZ', 'Found Spotify ID', $destination['spotify']['id']);
			}

			if ($relation['type'] == 'wikidata')
				$wikidata = $relation['url']['resource'];
		}

		if ($wikidata !== null &&
			(
				$destination['wikipedia']['link'] == null
				|| $destination['allmusic']['link'] == null
				|| ($type == 'artist' && $destination['spotify']['id'] == null)
				|| ($type == 'artist' && $destination['discogs']['artistlink'] == null)
				|| ($type != 'artist' && ($destination['discogs']['releaselink'] == null || $destination['discogs']['masterlink'] == null))
			))
		{
			if (preg_match('/(Q\d+)/', $wikidata, $matches)) {
				logger::debug('MUSICBRAINZ', 'Using Wikidata link to get more info',$matches[1]);

				$links = wikidata::get_links($matches[1], $type, $language);

				if ($destination['wikipedia']['link'] == null) {
					logger::trace('MUSICBRAINZ', 'Updating Wikipedia Link', $links['wikipedia']);
					$destination['wikipedia']['link'] = $links['wikipedia'];
				}

				if ($destination['allmusic']['link'] == null) {
					logger::trace('MUSICBRAINZ', 'Updating Allmusic Link', $links['allmusic']);
					$destination['allmusic']['link'] = $links['allmusic'];
				}

				if ($type == 'artist' && $destination['spotify']['id'] == null) {
					logger::trace('MUSICBRAINZ', 'Updating Spotify ID', $links['spotify'][$type]);
					$destination['spotify']['id'] = $links['spotify'][$type];
				}

				if ($type == 'artist' && $destination['discogs']['artistlink'] == null) {
					logger::trace('MUSICBRAINZ', 'Updating Discogs Artist Link', $links['discogs']['artist']);
					$destination['discogs']['artistlink'] = $links['discogs']['artist'];
				}

				if ($type != 'artist' && $destination['discogs']['releaselink'] == null) {
					logger::trace('MUSICBRAINZ', 'Updating Discogs Release Link', $links['discogs']['release']);
					$destination['discogs']['releaselink'] = $links['discogs']['release'];
				}

				if ($type != 'artist' && $destination['discogs']['masterlink'] == null) {
					logger::trace('MUSICBRAINZ', 'Updating Discogs Master Link', $links['discogs']['master']);
					$destination['discogs']['masterlink'] = $links['discogs']['master'];
				}

			}
		}

	}

	private static function find_first_non_null($w) {
		foreach ($w as $k => $v) {
			if ($v !== null) {
				logger::debug('MUSICBRAINZ', 'Using',$k,$v,'as wikipedia link');
				return $v;
			}
		}
		return null;
	}

	private static function get_wikipedia_link(&$relations, $language) {
		$wikilinks = [
			'user' => null,
			'english' => null,
			'anything' => null
		];
		foreach ($relations as $relation) {
			if ($relation['type'] == 'wikipedia') {
				if (preg_match('/https*:\/\/'.$language.'/', $relation['url']['resource'])) {
					$wikilinks['user'] = $relation['url']['resource'];
				} else if (preg_match('/en\.wikipedia\.org/', $relation['url']['resource'])) {
					$wikilinks['english'] = $relation['url']['resource'];
				} else {
					$wikilinks['anything'] = $relation['url']['resource'];
				}
			}
		}
		return self::find_first_non_null($wikilinks);
	}

	private static function find_artist($artistname, $album, $albumartist, $track) {
		$candidate = null;
		foreach ([$artistname, $albumartist] as $aname) {
			if ($aname =='Radio')
				continue;

			logger::debug('MYUSICBRAINZ', 'Searching for Artist', $aname);
			$artist_list = self::artist_search($aname);
			// self::$retval['artistsearch'] = $artist_list;
			if (!array_key_exists('artists', $artist_list))
				continue;

			foreach ($artist_list['artists'] as $artist) {
				if (metaphone_compare(strip_prefixes($aname), strip_prefixes($artist['name']), 0)) {
					// We've found a matching artist, now let's get its releases to see if there's a matching album
					// - that way we know we've got the correct artist.
					if ($album) {
						$releasegroup = self::releasegroup_search($artist['id'], $album);
						if ($releasegroup !== null) {
							logger::debug('MUSICBRAINZ', 'Found ID for',$aname,'with matching release group',$album, $artist['id']);
							self::$retval['artistmeta']['musicbrainz']['musicbrainz_id'] = $artist['id'];
							return;
						}
					} else {
						if ($candidate == null)
							$candidate = $artist['id'];

						logger::debug('MUSICBRAINZ', 'Checking artist ID by matching recordings');
						$check = self::recording_search($track, $artist['id']);
						if ($check !== null) {
							logger::debug('MUSICBRAINZ', 'Found ID for artist by checking recordings', $artist['id']);
							self::$retval['artistmeta']['musicbrainz']['musicbrainz_id'] = $artist['id'];
							return;
						}
					}
				}
			}
		}
		// Return the first one we found, if anything.
		logger::debug('MUSIBRAINZ', 'Returning first candidate match', $candidate);
		self::$retval['artistmeta']['musicbrainz']['musicbrainz_id'] = $candidate;
	}

	private static function recording_search($title, $artistid) {
		// We can't possibly search all recordings since some tracks have thousands, but we can attempt to find
		// a match in the first few, which is better then nothing.
		if ($artistid === null)
			return null;

		$params['limit'] = 100;
		$params['offset'] = 0;
		$params['query'] = $title;
		$tries = 3;

		// self::$retval['tracksearch'] = [];

		logger::debug('MUSICBRAINZ', 'Searching for recording',$title,'by artist',$artistid);
		do {
			$r = json_decode(self::request(self::create_url('recording', $params), false), true);

			// self::$retval['tracksearch'][] = $r;

			$gcount = $r['count'];
			foreach ($r['recordings'] as $i => $recording) {
				// logger::trace('MUSICBRAINZ', $i,$recording['title']);
				if (metaphone_compare($title, $recording['title'])) {
					if (!array_key_exists('video', $recording) || $recording['video'] !== true) {
						foreach ($recording['artist-credit'] as $credit) {
							// logger::debug('MUSICBRAINZ', 'Artist is',$credit['artist']['id'],$credit['artist']['name']);
							if ($credit['artist']['id'] == $artistid) {
								logger::debug('MUSICBRAINZ', 'Found recording id',$recording['id']);
								return $recording['id'];
							}
						}
					}
				}
			}
			$params['offset'] += count($r['recordings']);
			$tries--;
			// logger::debug('MUSICBRAINZ', 'Count is',$gcount,'Current is',count($retval['release-groups']));
		} while ($tries >= 0 && $params['offset'] < $gcount);
		return null;
	}

	private static function artist_search($artist) {
		$query = [
			'query'		=> $artist,
			'limit'		=> 100,
			'offset'	=> 0
		];
		$searchresult = self::request(self::create_url('artist/', $query), false);
		return json_decode($searchresult, true);
	}

	private static function releasegroup_search($artistid, $album) {
		$release_groups = self::artist_releases(['mbid' => $artistid], false);
		if (array_key_exists('release-groups', $release_groups)) {
			foreach ($release_groups['release-groups'] as $release) {
				if (metaphone_compare($album, $release['title'])) {
					logger::debug('MUSICBRAINZ', 'Found Release Group for',$album,$release['id']);
					return $release['id'];
					break;
				}
			}
		}
		return null;
	}

	private static function scan_release_group() {
		if (self::$retval['metadata']['album']['release_group'] == null)
			return null;

		// Return the first release id, since we don't know which one it is
		return self::$retval['metadata']['album']['release_group']['releases'][0]['id'];
	}

	private static function track_search($name) {
		foreach (self::$retval['metadata']['album']['release'][self::$retval['albummeta']['musicbrainz']['musicbrainz_id']]['media'] as $medium) {
			foreach ($medium['tracks'] as $track) {
				if (metaphone_compare($name, $track['title'])) {
					logger::debug('MUSICBRAINZ', 'Found recording ID for', $name, $track['recording']['id']);
					return $track['recording']['id'];
				}
			}
		}

		return null;
	}

	private static function find_work_data() {
		if (self::$retval['metadata']['track']['recording'] == null)
			return null;

		foreach (self::$retval['metadata']['track']['recording']['relations'] as $relation) {
			if ($relation['target-type'] == 'work') {
				logger::debug('MUSICBRAINZ', 'Found Work ID for track',$relation['work']['id']);
				self::$retval['metadata']['track']['work'] = json_decode(self::work_getinfo(['mbid' => $relation['work']['id']], false), true);
				break;
			}
		}
	}

	public static function artist_getinfo($params, $print_data) {

		//
		// params:
		//		mbid 	=> artist's MusicBrainz ID
		//

		$params['inc'] = 'aliases tags ratings release-groups artist-rels label-rels url-rels release-group-rels annotation';
		return  self::request( self::create_url('artist/'.$params['mbid'], $params), $print_data);
	}

	public static function artist_releases($params, $print_data) {

		//
		// params:
		//		mbid 	=> artist's MusicBrainz ID
		//

		$retval = ['release-groups' => []];
		$gcount = 0;
		$params['inc'] = 'artist-credits tags ratings url-rels annotation';
		$params['limit'] = 100;
		$params['offset'] = 0;
		$params['artist'] = $params['mbid'];
		$tries = 10;
		do {
			$r = json_decode(self::request( self::create_url('release-group', $params), false), true);
			$gcount = $r['release-group-count'];
			$retval['release-groups'] = array_merge($retval['release-groups'], $r['release-groups']);
			$params['offset'] += count($r['release-groups']);
			$tries--;
			// logger::debug('MUSICBRAINZ', 'Count is',$gcount,'Current is',count($retval['release-groups']));
		} while ($tries >= 0 && count($retval['release-groups']) < $gcount);

		if ($print_data) {
			print json_encode($retval);
		} else {
			return $retval;
		}
	}

	public static function album_getinfo($params, $print_data) {

		//
		// params:
		//		mbid 	=> album MusicBrainz ID
		//

		$params['inc'] = 'annotation tags ratings artists labels recordings release-groups artist-credits url-rels release-group-rels recording-rels artist-rels';
		return  self::request( self::create_url('release/'.$params['mbid'], $params), $print_data);
	}

	public static function album_getcover($params, $print_data) {

		//
		// params:
		//		mbid 	=> album MusicBrainz ID
		//
		return  self::request( self::COVER_URL.$params['mbid'].'/', $print_data);
	}

	public static function album_getreleases($params, $print_data) {

		//
		// params:
		//		mbid 	=> album MusicBrainz ID
		//
		$params['inc'] = 'release-groups';
		return  self::request( self::create_url('release/'.$params['mbid'], $params), $print_data);
	}

	public static function releasegroup_getinfo($params, $print_data) {

		//
		// params:
		//		mbid 	=> release group MusicBrainz ID
		//

		$params['inc'] = 'artists releases artist-rels label-rels url-rels';
		return  self::request( self::create_url('release-group/'.$params['mbid'], $params), $print_data);

	}

	public static function track_getinfo($params, $print_data) {

		//
		// params:
		//		mbid 	=> track (recording) MusicBrainz ID
		//

		$params['inc'] = 'annotation tags ratings releases url-rels work-rels release-rels release-group-rels artist-rels label-rels recording-rels';
		return  self::request( self::create_url('recording/'.$params['mbid'], $params), $print_data);

	}

	public static function work_getinfo($params, $print_data) {

		//
		// params:
		//		mbid 	=> track (work) MusicBrainz ID
		//

		$params['inc'] = 'annotation tags ratings url-rels artist-rels';
		return  self::request( self::create_url('work/'.$params['mbid'], $params), $print_data);

	}

}

?>