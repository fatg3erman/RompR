<?php

class bandcamp {

	const BASE_URL = "https://bandcamp.com/api";
	const IMAGE_BASE = "https://f4.bcbits.com/img/";

	private static function request($url, $data = null) {
		$opts = [
			'url' => $url,
			'cache' => 'bandcamp',
			'return_value' => true,
			'jsondata' => $data
		];
		$cache = new cache_handler($opts);

		return [$cache->get_cache_data(), $cache->from_cache];
	}

	private static function nothing() {
		header('Content-Type: application/json');
		$retval =  json_encode(array('error' => 'Artist Not Found'));
		print $retval;
		exit(0);
	}

	public static function get_artist($params, $pd) {

		$an = $params['artistname'];
		$params = ['q' => $an];
		$url = self::BASE_URL . "/fuzzysearch/1/app_autocomplete?" .http_build_query($params);
		list($result, $c) = self::request($url);

		$search_result = json_decode($result, true);

		$retval = [
			'band_id' => null,
			'albums' => []
		];

		if (array_key_exists('results', $search_result)) {
			foreach ($search_result['results'] as $r) {
				// if ($r['type'] == 'b' && $r['name'] == $an) {
				if ($r['type'] == 'b' && metaphone_compare($an, $r['name'], 0)) {
					$retval['band_id'] = $r['id'];
					$retval['image'] = preg_replace('/_\d+\.jpg/', '_10.jpg', $r['img']);
					$retval['external_urls'] = ['bandcamp' => $r['url']];
					$retval['name'] = $r['name'];
					$retval['location'] = $r['location'];
					$retval['genre'] = $r['genre_name'];
					$retval['tags'] = $r['tag_names'];
					break;
				}
			}
		}

		if ($retval['band_id'] == null) {
			self::nothing();
		}

		$url = self::BASE_URL . "/mobile/24/band_details";
		$data = ["band_id" => $retval['band_id']];
		list($result, $c) = self::request($url, $data);

		$artist_result = json_decode($result, true);
		$retval['bio'] = $artist_result['bio'];

		if (array_key_exists('discography', $artist_result)) {
			foreach ($artist_result['discography'] as $d) {
				$m = preg_match('/ (\d\d\d\d) /', $d['release_date'], $matches);
				$date = ($m) ? $matches[1] : null;
				$retval['albums'][] = [
					'name' => $d['title'],
					'id' => $d['band_id'] . '-' . $d['item_id'],
					'uri' => 'bandcamp:album:'.$d['band_id'] . '-' . $d['item_id'],
					'artists' => [['name' => $d['band_name']]],
					'images' => [[
						'url' => self::IMAGE_BASE . 'a' . $d['art_id'] . '_5.jpg',
						'width' => 700
					]],
					'domain' => 'bandcamp',
					'date' => $date
				];
			}
		}

		if ($c) {
			header("Pragma: From Cache");
		} else {
			header("Pragma: Not Cached");
		}
		header('Content-Type: application/json');
		print json_encode($retval);

	}

	public static function get_album($params, $pd) {
		# /mobile/24/tralbum_details?band_id=2498325343&tralbum_type=a&tralbum_id=2898036781"
		list($band_id, $album_id) = explode('-', $params['id']);
		$p = ['band_id' => $band_id, 'tralbum_type' => 'a', 'tralbum_id' => $album_id];
		$url = self::BASE_URL . '/mobile/24/tralbum_details?' . http_build_query($p);
		list($result, $c) = self::request($url);

		$deets = json_decode($result, true);
		$retval = [
			'external_url' => $deets['bandcamp_url'],
			'link_title' => language::gettext('label_viewbc') ,
			'id' => $band_id.'-'.$album_id,
			'tracks' => ['items' => []]
		];
		foreach ($deets['tracks'] as $d) {
			$retval['tracks']['items'][] = [
				'uri' => 'bandcamp:track:'.$d['band_id'] . '-' . $d['album_id']. '-' . $d['track_id'],
				'name' => $d['title'],
				'track_number' => $d['track_num'],
				'duration_ms' => $d['duration'] * 1000,
				'artists' => [['name' => $d['band_name']]]
			];
		}

		if ($c) {
			header("Pragma: From Cache");
		} else {
			header("Pragma: Not Cached");
		}
		header('Content-Type: application/json');
		print json_encode($retval);
	}

}

?>