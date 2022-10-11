<?php
class artist_radio extends everywhere_radio {

	public function search_for_track() {
		return $this->get_one_uri();
	}

	protected function prepare() {
		$rp = prefs::get_radio_params();
		$this->add_toptrack(
			self::TYPE_TOP_TRACK,
			$rp['radioparam'],
			''
		);
		list($uris, $gotseeds) = $this->do_seed_search();
		$this->handle_multi_tracks($uris);
	}

}
?>
