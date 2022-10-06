<?php

class mix_radio extends everywhere_radio {

	const IGNORE_ALBUMS = true;

	public function search_for_track() {
		$uris = [];
		$gotseeds = true;
		$retval = null;
		while (count($uris) == 0 && $gotseeds) {
			$this->get_similar_seeds();
			list($uris, $gotseeds) = $this->do_seed_search();
		}
		if (count($uris) > 0) {
			$this->handle_multi_tracks($uris);
			$retval = $this->get_one_uri();
		}
		return $retval;
	}

	protected function prepare() {
		$this->get_fave_artists(self::TYPE_TOP_TRACK);
	}

	private function get_similar_seeds() {
		$spotify_id = null;
		$seeds = ['bum'];
		while ($spotify_id === null && count($seeds) > 0) {
			$seeds = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, [],
				"SELECT * FROM ".self::get_seed_table_name()." WHERE Type = ? ORDER BY ".self::SQL_RANDOM_SORT." LIMIT 1",
				self::TYPE_TOP_TRACK
			);
			foreach ($seeds as $seed) {
				$this->sql_prepare_query(true, null, null, null,
					"UPDATE ".self::get_seed_table_name()." SET Type = ? WHERE topindex = ?",
					self::TYPE_USED_TOP_TRACK,
					$seed['topindex']
				);
				$spotify_id = $this->get_spotify_id($seed['Artist']);
			}
		}
		if ($spotify_id !== null) {
			$params = [
				'id' => $spotify_id,
				'cache' => true
			];
			$related = json_decode(spotify::artist_getrelated($params, false), true);
			if (array_key_exists('artists', $related)) {
				foreach ($related['artists'] as $bobbly) {
					logger::trace('MIXRADIO', 'Related Artist', $bobbly['name']);
					$this->add_toptrack(
						self::TYPE_RELATED_TRACK,
						$bobbly['name'],
						null
					);
				}
			}
		}
	}

	private function get_spotify_id($artist) {
		$params = [
			'q' => $artist,
			'type' => 'artist',
			'limit' => 50,
			'cache' => true
		];
		$candidates = json_decode(spotify::search($params, false), true);
		if (array_key_exists('artists', $candidates) && array_key_exists('items', $candidates['artists'])) {
			foreach ($candidates['artists']['items'] as $willies) {
				if ($this->strip_track_name($willies['name']) == $this->strip_track_name($artist)) {
					logger::log('MIXRADIO', 'Spotify Artist',$willies['id'],$willies['name'],'matches',$artist);
					return $willies['id'];
				}
			}
		}
		logger::log('MIXRADIO', 'Could not find Spotify Id for',$artist);
		return null;
	}

}

?>