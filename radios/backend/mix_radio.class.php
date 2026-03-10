<?php

// recommendationsRadio
//   Recommendations For You

// yourMixRadio
//   Your Monthly Mix

// mixRadio:
//  Favourite Artists and Related Artists

class mix_radio extends lastfm_radio {

	public function search_for_track() {
		$rp = prefs::get_radio_params();
		$uris = [];
		$gotseeds = true;
		while (count($uris) == 0 && $gotseeds) {
			switch ($rp['radiomode']) {
				case 'mixRadio':
					while (count($uris) == 0 && $gotseeds) {
						$s = $this->get_similar_seeds();
						// With mixRadio we're happy to search for anything whether it be a TOP_TRACK (fave artist)
						// or a RELATED_TRACK (similar artists). do_seed_search() takes care of removing them from
						// the database once they've been searched for
						if ($s > 0)
							list($uris, $gotseeds) = $this->do_seed_search();
					}
					break;

				case 'yourMixRadio':
					// recommendations Radio does NOT play the seeds, only the recommendations
					while (count($uris) == 0 && $gotseeds) {
						$s = $this->get_similar_seeds();
						if ($s > 0)
							list($uris, $gotseeds) = $this->do_seed_search();
					}
					break;

				case 'recommendationsRadio':
					// recommendations Radio does NOT play the seeds, only the recommendations
					while (count($uris) == 0 && $gotseeds) {
						$s = $this->get_similar_seeds();
						if ($s > 0)
							list($uris, $gotseeds) = $this->do_seed_search(self::TYPE_RELATED_TRACK);
					}
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
				$this->get_fave_artists(self::TYPE_TOP_TRACK);
				break;

			case 'recommendationsRadio':
			case 'yourMixRadio':
				$this->get_fave_tracks(self::TYPE_TOP_TRACK);
				break;

		}
	}

	private function get_similar_seeds() {
		$rp = prefs::get_radio_params();
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
			switch ($rp['radiomode']) {
				case 'mixRadio':
					$this->get_similar_artists($seed);
					break;

				case 'recommendationsRadio':
					$this->get_similar_tracks($seed);
					break;
			}
		}
		return count($seeds);
	}

}

?>