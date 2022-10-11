<?php

// Favourite Artists
// Single Artist

class fave_artist_radio extends everywhere_radio {

	public function search_for_track() {
		$uris = [];
		$gotseeds = true;
		while (count($uris) == 0 && $gotseeds) {
			list($uris, $gotseeds) = $this->do_seed_search();
		}
		if (count($uris) > 0) {
			$this->handle_multi_tracks($uris);
		}
		return $this->get_one_uri();
	}

	protected function prepare() {
		// Set them to TYPE_USED_AS_SEED so they get deleted when they're done with.
		$rp = prefs::get_radio_params();
		if ($rp['radioparam']) {
			// We'll have a param if this is Single Artist Radio
			$this->add_toptrack(
				self::TYPE_USED_AS_SEED,
				$rp['radioparam'],
				''
			);
		} else {
			$this->get_fave_artists(self::TYPE_USED_AS_SEED);
		}
		list($uris, $gotseeds) = $this->do_seed_search();
		$this->handle_multi_tracks($uris);
	}

}
?>
