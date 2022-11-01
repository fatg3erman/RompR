<?php

class lastfm {

	const BASE_URL = 'https://ws.audioscrobbler.com/2.0/';
	private const LOCK = "15f7532dff0b8d84635c757f9f18aaa3";
	private const DOOR = "3ddf4cb9937e015ca4f30296a918a2b0";

	//
	// signed_request and get_request can be used directly as methods
	// because there's rarely any need for a specific method for last.fm
	// stuff given that lastfm.js already has them. Some specific methods
	// are provided below for convenience when calling from PHP code
	//

	public static function signed_request($params, $print_data, $auth = true) {
		$opts = [
			'api_key' => self::LOCK,
		];
		if ($auth) {
			$opts['sk'] = prefs::get_pref('lastfm_session_key');
		}

		foreach ($params as $k => $v) {
			$opts[$k] = mb_convert_encoding($v, "UTF-8", "auto");
		}

		$keys = array_keys($opts);
		sort($keys);
		$sig = '';
		foreach ($keys as $k) {
			$sig .= $k.$opts[$k];
		}
		$opts['api_sig'] = md5($sig.self::DOOR);
		$opts['format'] = 'json';

		$cache = new cache_handler([
			'url' => self::BASE_URL,
			'cache' => null,
			'return_value' => !$print_data,
			'postfields' => $opts,
			'timeout' => 10
		]);
		return $cache->get_cache_data();
	}

	public static function get_request($params, $print_data) {
		$params['api_key'] = self::LOCK;
		$params['format'] = 'json';
		$cache = $params['cache'] ? 'lastfm' : null;
		unset($params['cache']);

		$cache = new cache_handler([
			'url' => self::BASE_URL.'?'.http_build_query($params),
			'cache' => $cache,
			'return_value' => !$print_data
		]);
		return $cache->get_cache_data();
	}

	//
	// Convenince Methods below
	//

	public static function scrobble($params, $print_data) {

		//
		// params:
		//		timestamp
		//		track
		//		artist
		//		album
		//		[albumArtist]
		//

		$params['method'] = 'track.scrobble';
		return self::signed_request($params, $print_data);
	}

	public static function update_nowplaying($params, $print_data) {

		//
		// params:
		//		timestamp
		//		track
		//		artist
		//		album
		//

		$params['method'] = 'track.updateNowPlaying';
		return self::signed_request($params, $print_data);
	}

	public static function album_getinfo($params, $print_data) {

		//
		// params:
		//		album
		//		artist
		//		[username]
		//		[lang]
		//		[autocorrect] (1/0)
		//

		$params['method'] = 'album.getInfo';
		return self::get_request($params, $print_data);
	}

	public static function get_recent_tracks($params) {

		//
		// params:
		//		limit: int
		//		from: timestamp to start from (UNIX)
		//		extended: 1 or 0
		//		page: page number
		//
		$params['method'] = 'user.getRecentTracks';
		$params['user'] = prefs::get_pref('lastfm_user');
		$params['cache'] = false;
		$data = self::get_request($params, false);
		$decoded = json_decode($data, true);

		// logger::log('LASTFM', print_r($decoded, true));

		if (array_key_exists('recenttracks', $decoded) &&
			array_key_exists('track', $decoded['recenttracks']))
		{
			if (array_key_exists('artist', $decoded['recenttracks']['track'])) {
				// If theres' only one, Last.FM doesn't return an array.....
				return [$decoded['recenttracks']['track']];
			} else {
				return $decoded['recenttracks']['track'];
			}
		}
		return [];

	}

	public static function user_get_top_tracks($params) {
		//
		// params:
		//		period: valid value fo period - eg 7day
		//		page: page to get (starts at 1)
		logger::info('LFMTOPTRACKS', 'Getting page',$params['page'],'for period',$params['period']);
		$params['method'] = 'user.getTopTracks';
		$params['user'] = prefs::get_pref('lastfm_user');
		$params['limit'] = 100;
		$params['cache'] = false;
		$data = self::get_request($params, false);
		$decoded = json_decode($data, true);
		return $decoded;
	}

	public static function user_get_top_artists($params) {
		//
		// params:
		//		period: valid value fo period - eg 7day
		//		page: page to get (starts at 1)
		logger::info('LFMTOPARTISTS', 'Getting page',$params['page'],'for period',$params['period']);
		$params['method'] = 'user.getTopArtists';
		$params['user'] = prefs::get_pref('lastfm_user');
		$params['limit'] = 100;
		$params['cache'] = false;
		$data = self::get_request($params, false);
		$decoded = json_decode($data, true);
		return $decoded;
	}

	public static function track_get_similar($params) {
		//
		// params:
		//		track: Track Title
		//		artist: Artist name
		//		limit: Max number of tracks to return
		logger::info('LFMGETSIMILAR', 'Getting similar tracks for',$params['artist'],$params['track']);
		$params['method'] = 'track.getSimilar';
		$params['cache'] = true;
		$data = self::get_request($params, false);
		$decoded = json_decode($data, true);
		return $decoded;
	}

	public static function artist_get_similar($params) {
		//
		// params:
		//		artist: Artist name
		//		limit: Max number of tracks to return
		logger::info('LFMGETSIMILAR', 'Getting similar artists for',$params['artist']);
		$params['method'] = 'artist.getSimilar';
		$params['cache'] = true;
		$data = self::get_request($params, false);
		$decoded = json_decode($data, true);
		return $decoded;
	}

	//
	// Only for use by the UI for logging in
	//

	public static function start_login($params, $print_data) {
		$params['method'] = 'auth.getToken';
		unset($params['cache']);
		$data = self::signed_request($params, false, false);
		$d = json_decode($data, true);
		if (array_key_exists('token', $d)) {
			print json_encode(array(
				'url' => 'http://www.last.fm/api/auth/?api_key='.self::LOCK.'&token='.$d['token'],
				'token' => $d['token']
			));
		} else {
			header('HTTP/1.1 500 Internal Server Error');
		}
	}

	public static function get_session($params, $print_data) {
		$params['method'] = 'auth.getSession';
		return self::signed_request($params, $print_data, false);
	}

}