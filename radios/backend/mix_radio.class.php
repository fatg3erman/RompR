<?php

// recommendationsRadio
//   Recommendations For You

// mixRadio:
//  Favourite Artists and Related Artists

// genreRadio
//   Genre

class mix_radio extends everywhere_radio {

	public function search_for_track() {
		$rp = prefs::get_radio_params();
		$uris = [];
		$gotseeds = true;
		while (count($uris) == 0 && $gotseeds) {
			switch ($rp['radiomode']) {
				case 'mixRadio':
					while (count($uris) == 0 && $gotseeds) {
						$this->get_similar_seeds();
						// With mixRadio we're happy to search for anything whether it be a TOP_TRACK (fave artist)
						// or a RELATED_TRACK (similar artists). do_seed_search() takes care of removing them from
						// the database once they've been searched for
						list($uris, $gotseeds) = $this->do_seed_search();
					}
					break;

				case 'recommendationsRadio':
					// recommendations Radio does NOT play the seeds, only the recommendations (from Spotify)
					while (count($uris) == 0 && $gotseeds) {
						$this->get_recommendations();
						list($uris, $gotseeds) = $this->do_seed_search(self::TYPE_RELATED_TRACK);
					}
					break;

				case 'genreRadio':
					list($uris, $gotseeds) = $this->do_seed_search();
					break;

			}
		}
		if (count($uris) > 0)
			$this->handle_multi_tracks($uris);

		return $this->get_one_uri();
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
				"SELECT * FROM ".self::get_seed_table_name()." WHERE Type & ? > 0 AND Type & ? = 0 ORDER BY ".self::SQL_RANDOM_SORT." LIMIT 1",
				self::TYPE_TOP_TRACK,
				self::TYPE_USED_AS_SEED
			);
			foreach ($seeds as $seed) {
				$this->sql_prepare_query(true, null, null, null,
					"UPDATE ".self::get_seed_table_name()." SET Type = Type + ? WHERE topindex = ?",
					self::TYPE_USED_AS_SEED,
					$seed['topindex']
				);
				$spotify_id = $this->get_spotify_id($seed['trackartist']);
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
					logger::log('MIXRADIO', 'Related Artist', $bobbly['name']);
					$this->add_toptrack(
						self::TYPE_RELATED_TRACK,
						$bobbly['name'],
						''
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
				"SELECT * FROM ".self::get_seed_table_name()." WHERE Type & ? > 0 AND Type & ? = 0 ORDER BY ".self::SQL_RANDOM_SORT." LIMIT 2",
				self::TYPE_TOP_TRACK,
				self::TYPE_USED_AS_SEED
			);
			foreach ($seeds as $seed) {
				$this->sql_prepare_query(true, null, null, null,
					"UPDATE ".self::get_seed_table_name()." SET Type = Type + ? WHERE topindex = ?",
					self::TYPE_USED_AS_SEED,
					$seed['topindex']
				);
				$id = $this->get_spotify_id($seed['trackartist']);
				if ($id !== null)
					$spotify_ids[] = $id;
			}
		}
		if (count($spotify_ids) > 0) {
			$params = [
				'param' => ['seed_artists' => implode(',', $spotify_ids)],
				'cache' => true
			];
			$related = json_decode(spotify::get_recommendations($params, false), true);
			if (array_key_exists('tracks', $related)) {
				foreach ($related['tracks'] as $bobbly) {
					$anames = [];
					foreach ($bobbly['artists'] as $artist) {
						$anames[] = $artist['name'];
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
				// Set them to TYPE_USED_AS_SEED so they get tidied up later.
				$this->add_toptrack(
					self::TYPE_USED_AS_SEED,
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
				if (metaphone_compare($artist, $willies['name'], 0)) {
					logger::debug('MIXRADIO', 'Spotify Artist',$willies['id'],$willies['name'],'matches',$artist);
					return $willies['id'];
				}
			}
		}
		logger::log('MIXRADIO', 'Could not find Spotify Id for',$artist);
		return null;
	}

}

?>