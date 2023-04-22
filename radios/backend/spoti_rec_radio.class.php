<?php

class spoti_rec_radio extends musicCollection {

	public function preparePlaylist() {
		$this->create_radio_uri_table();
		$this->create_radio_ban_table();
		$rp = prefs::get_radio_params();
		$params = explode(';', $rp['radioparam']);
		$rec_params = [
			'cache' => false,
			'param' => ['limit' => 200]
		];
		foreach ($params as $p) {
			list($parm, $value) = explode(':', $p);
			$rec_params['param'][$parm] = $value;
		}
		logger::log('PONGO', print_r($rec_params, true));
		$recs = json_decode(spotify::get_recommendations($rec_params, false), true);
		$bantable = everywhere_radio::get_ban_table_name();
		if (array_key_exists('tracks', $recs)) {
			foreach ($recs['tracks'] as $bobbly) {
				$anames = [];
				foreach ($bobbly['artists'] as $artist) {
					$anames[] = $artist['name'];
				}
				$artist = concatenate_artist_names($anames);
				$banned = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, 'banindex', null,
					"SELECT banindex FROM ".$bantable." WHERE trackartist = ? AND Title = ?",
					$artist, $bobbly['name']
				);
				if ($banned !== null) {
					logger::log('PONGO',$artist, $bobbly['name'],'is BANNED');
				} else {
					logger::log('PONGO', 'Got Uri',$artist, $bobbly['name']);
					$this->add_smart_uri($bobbly['uri'], $artist, $bobbly['name'], $bobbly['album']['uri']);
				}
			}
		}
	}

	public function doPlaylist($param, $numtracks, &$player) {
		$rp = prefs::get_radio_params();
		// prepared is set to 0 when we first call starRadios.php
		// and then to 1 AFTER everything has been prepared, which will
		// include called preparePlaylist()
		// This prevents a race condition between us and romonitor which is
		// caused by it reacting to the stop and clear commands
		if ($rp['prepared'] == 0)
			return true;

		$table = everywhere_radio::get_uri_table_name();
		$r = $this->generic_sql_query("SELECT * FROM ".$table." WHERE used = 0 ORDER BY ".self::SQL_RANDOM_SORT." LIMIT ".$numtracks);
		$cmds = [];
		foreach ($r as $track) {
			$this->sql_prepare_query(true, null, null, null,
				"UPDATE ".$table." SET used = 1 WHERE uriindex = ?",
				$track['uriindex']
			);
			$cmds[] = join_command_string(['add', $track['Uri']]);
		}
		if (count($cmds) > 0) {
			$player->do_command_list($cmds);
			return true;
		} else {
			logger::log('PONGO', 'No More Tracks!');
			return false;
		}
	}

}

?>