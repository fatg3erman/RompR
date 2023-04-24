<?php

// Spotify Radio uses two modes. The Stations (mix, swim, and surprise) use our database
// to get tracks which are passed to get_recommendations as seed_tracks.
// You can also set radioparam to eg seed_artists:43267463288,545453465577
// or seed_artists:43647326482364,786765757;seed_genres:5655747738383[;etc]
// and they will be used as direct seeds, bypassing the database.
// Note that Spotify will reject any request with more than 5 seeds; we send each
// seed type as a separate request.

class spoti_rec_radio extends everywhere_radio {

	// Minimum number of tracks to have in the URI table before we process more seeds
	const MIN_TRACKS_IN_URI_TABLE = 100;

	const TYPE_TRACK_URI = 0;
	const TYPE_SEED_TRACK = 1;
	// NOTE: TYPE_USED_AS_SEED is defined as 2 in everywhere_radio

	protected function prepare() {
		$rp = prefs::get_radio_params();
		$params = explode(';', $rp['radioparam']);
		foreach ($params as $p) {
			switch ($p) {
				case 'mix':
				case 'swim':
				case 'surprise':
					$this->get_seeds($p);
					break;

				default:
					list($parm, $value) = explode(':', $p);
					$this->get_tracks($parm, $value, 100);
					break;
			}
		}
	}

	private function get_tracks($parm, $value, $limit) {
		$rec_params = [
			'cache' => false,
			'param' => ['limit' => $limit]
		];
		$rec_params['param'][$parm] = $value;
		logger::log('PONGO', print_r($rec_params, true));
		$recs = json_decode(spotify::get_recommendations($rec_params, false), true);
		$bantable = self::get_ban_table_name();
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
		$table = self::get_uri_table_name();
		$num_tracks_remaining = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, 'numt', 0,
			"SELECT COUNT(uriindex) AS numt FROM $table WHERE used = ?",
			0
		);
		logger::log('PONGO', 'There are',$num_tracks_remaining,'tracks in the URI table');
		if ($num_tracks_remaining < self::MIN_TRACKS_IN_URI_TABLE) {
			$this->populate_uri_table();
		}

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

	private function get_seeds($type) {
		switch ($type) {
			case 'mix':
				// Mix gets the 20 most recently played tracks
				$seed_tracks = $this->get_most_recently_played_music(20, 0);
				break;

			case 'swim':
				// Swim gets the 50 most recently played tracks that have been played more than once
				$seed_tracks = $this->get_most_recently_played_music(50, 1);
				break;

			case 'surprise':
				// Surprise gets the top 150 tracks (based on playcount) over the last
				// 365 days, combines that with the top 40 tracks of all time,
				// randomises that list, and returns 100 of them.
				$seed_tracks = $this->get_recommendation_seeds(365, 150, 100);
				break;
		}
		foreach ($seed_tracks as $t) {
			if (strpos($t['Uri'], 'spotify:track:') === 0) {
				logger::log('GETSEEDS', 'Found', $t['Uri']);
				// In the case where this is a spotify track we put its ID directly in the seed
				// table as Title and set Artistname to NONE.
				$this->add_toptrack(self::TYPE_TRACK_URI, 'NONE', substr($t['Uri'], 14));
			} else {
				logger::log('GETSEEDS', 'Found', $t['Artistname'], $t['Title']);
				$this->add_toptrack(self::TYPE_SEED_TRACK, $t['Artistname'], $t['Title']);
			}
		}
	}

	private function populate_uri_table() {
		$seed_table = self::get_seed_table_name();
		$seed_tracks = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, [],
			"SELECT * FROM $seed_table WHERE Type != ?",
			self::TYPE_USED_AS_SEED
		);
		$numseeds = count($seed_tracks);
		logger::log('PONGO', 'There are',$numseeds,'entries in the seed table');
		shuffle($seed_tracks);
		$ids = [];
		while (count($ids) < 5 && count($seed_tracks) > 0) {
			$t = array_shift($seed_tracks);
			switch ($t['Type']) {
				case self::TYPE_TRACK_URI:
					logger::log('PONGO', 'Found', $t['Title']);
					$ids[] = $t['Title'];
					break;

				case self::TYPE_SEED_TRACK:
					$matches = $this->fave_finder(
						['spotify'],
						false,
						[
							'Title' => $t['Title'],
							'trackartist' => $t['trackartist'],
							'Album' => null
						],
						true
					);
					if (count($matches) > 0) {
						logger::log('PONGO', 'Found', $matches[0]['file']);
						$ids[] = substr($matches[0]['file'], 14);
					}
					break;
			}
			$this->sql_prepare_query(true, null, null, null,
				"UPDATE $seed_table SET Type = ? WHERE topindex = ?",
				self::TYPE_USED_AS_SEED,
				$t['topindex']
			);
		}
		if (count($ids) > 0) {
			// Use a limit that depends on the number of seeds we have so we get a good mix
			// of tracks if we have a lot of seeds but don't run out quickly if we have few seeds.
			if ($numseeds < 25) {
				$limit = 100;
			} else if ($numseeds < 50) {
				$limit = 50;
			} else {
				$limit = 25;
			}
			$this->get_tracks('seed_tracks', implode(',', $ids), $limit);
		}
	}

}

?>
