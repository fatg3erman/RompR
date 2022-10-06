<?php
class fave_artist_radio extends everywhere_radio {

	const IGNORE_ALBUMS = false;

	public function search_for_track() {
		$uris = [];
		$gotseeds = true;
		$retval = null;
		while (count($uris) == 0 && $gotseeds) {
			list($uris, $gotseeds) = $this->do_seed_search();
		}
		if (count($uris) > 0) {
			$this->handle_multi_tracks($uris);
			$retval = $this->get_one_uri();
		}
		return $retval;
	}

	protected function prepare() {
		$this->get_fave_artists(self::TYPE_USED_TOP_TRACK);
		list($uris, $gotseeds) = $this->do_seed_search();
		$this->handle_multi_tracks($uris);
	}

}
?>
