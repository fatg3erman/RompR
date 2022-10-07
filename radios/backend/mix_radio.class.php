<?php

class mix_radio extends everywhere_radio {

	const IGNORE_ALBUMS = true;

	public function search_for_track() {
		$rp = prefs::get_radio_params();
		$uris = [];
		$gotseeds = true;
		$retval = null;
		while (count($uris) == 0 && $gotseeds) {
			switch ($rp['radiomode']) {
				case 'mixRadio':
					while (count($uris) == 0 && $gotseeds) {
						$this->get_similar_seeds();
						list($uris, $gotseeds) = $this->do_seed_search();
					}
					break;

				case 'recommendationsRadio':
					while (count($uris) == 0 && $gotseeds) {
						$this->get_recommendations();
						list($uris, $gotseeds) = $this->do_seed_search(self::TYPE_RELATED_TRACK);
					}
					break;

				case 'genreRadio':
					list($uris, $gotseeds) = $this->do_seed_search(self::TYPE_RELATED_TRACK);
					break;

			}
		}
		if (count($uris) > 0) {
			$this->handle_multi_tracks($uris);
			$retval = $this->get_one_uri();
		}
		return $retval;
	}

	protected function prepare() {
		$rp = prefs::get_radio_params();
		switch ($rp['radiomode']) {
			case 'mixRadio':
			case 'recommendationsRadio':
				$this->get_fave_artists(self::TYPE_TOP_TRACK);
				break;

			case 'genreRadio':
				$this->get_genres();
				break;
		}
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

	private function get_recommendations() {
		$spotify_ids = [];
		$seeds = ['bum'];
		while (count($spotify_ids) == 0 && count($seeds) > 0) {
			$seeds = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, [],
				"SELECT * FROM ".self::get_seed_table_name()." WHERE Type = ? ORDER BY ".self::SQL_RANDOM_SORT." LIMIT 5",
				self::TYPE_TOP_TRACK
			);
			foreach ($seeds as $seed) {
				$this->sql_prepare_query(true, null, null, null,
					"UPDATE ".self::get_seed_table_name()." SET Type = ? WHERE topindex = ?",
					self::TYPE_USED_TOP_TRACK,
					$seed['topindex']
				);
				$id = $this->get_spotify_id($seed['Artist']);
				if ($id !== null)
					$spotify_ids[] = $id;
			}
		}
		if (count($spotify_ids) > 0) {
			$params = [
				'param' => ['seed_artists' => implode(',', $spotify_ids)],
				'cache' => true
			];
			// logger::log('BLONG', print_r($params, true));
			$related = json_decode(spotify::get_recommendations($params, false), true);
			if (array_key_exists('tracks', $related)) {
				foreach ($related['tracks'] as $bobbly) {
					$anames = [];
					foreach ($bobbly['artists'] as $artist) {
						$anames = $artist['name'];
					}
					$this->add_toptrack(
						self::TYPE_RELATED_TRACK,
						concatenate_artist_names($anames),
						$bobbly['name']
					);
				}
			}
		}

	}

	private function get_genres() {
		$rp = prefs::get_radio_params();
		$params = [
			'param' => ['seed_genres' => $rp['radioparam'], 'limit' => 100],
			'cache' => true
		];
		// logger::log('BLONG', print_r($params, true));
		$related = json_decode(spotify::get_recommendations($params, false), true);
		if (array_key_exists('tracks', $related)) {
			foreach ($related['tracks'] as $bobbly) {
				$anames = [];
				foreach ($bobbly['artists'] as $artist) {
					$anames = $artist['name'];
				}
				$this->add_toptrack(
					self::TYPE_RELATED_TRACK,
					concatenate_artist_names($anames),
					$bobbly['name']
				);
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