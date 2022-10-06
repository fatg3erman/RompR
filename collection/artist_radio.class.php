<?php
class artist_radio extends everywhere_radio {

	const IGNORE_ALBUMS = false;

	public function search_for_track() {
		return $this->get_one_uri();
	}

	protected function prepare() {
		$rp = prefs::get_radio_params();
		$this->add_toptrack(
			self::TYPE_TOP_TRACK,
			$rp['radioparam'],
			null
		);
		list($uris, $gotseeds) = $this->do_seed_search();
		$this->handle_multi_tracks($uris);
	}

}
?>
