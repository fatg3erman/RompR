<?php

class musicbrainz {

	const BASE_URL = 'http://musicbrainz.org/ws/2/';
	const COVER_URL = 'http://coverartarchive.org/release/';

	private static function request($url, $print_data) {
		global $prefs;
		$cache = new cache_handler([
			'url' => $url,
			'cache' => 'musicbrainz',
			'return_value' => !$print_data
		]);
		return $cache->get_cache_data();
	}

	private static function create_url($uri, $params) {
		$params['fmt'] = 'json';
		unset($params['mbid']);
		return  self::BASE_URL.$uri.'?'.http_build_query($params);
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

		$params['inc'] = 'artist-credits tags ratings url-rels annotation';
		$params['limit'] = 100;
		$params['artist'] = $params['mbid'];
		return  self::request( self::create_url('release-group', $params), $print_data);
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