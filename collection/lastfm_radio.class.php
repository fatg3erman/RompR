<?php

class lastfm_radio extends musicCollection {

	const TYPE_TOP_TRACK = 0;
	const TYPE_RELATED_TRACK = 1;
	const TYPE_USED_TOP_TRACK = 2;

	public function preparePlaylist() {
		$this->create_toptracks_table();
		$this->create_radio_uri_table();
		return $this->search_for_track();
	}

	public static function get_seed_table_name() {
		return 'Toptracks_'.prefs::player_name_hash();
	}

	public static function get_uri_table_name() {
		return 'Smart_Uri_'.prefs::player_name_hash();
	}

	public function get_seeds() {
		$rp = prefs::get_radio_params();
		if ($rp['toptracks_current'] <= $rp['toptracks_total']) {
			$page = $rp['toptracks_current'];
			$options = [
				'period' => $rp['radioparam'],
				'page' => $page
			];
			switch ($rp['radiomode']) {
				case 'lastFMTrackRadio':
					$this->get_top_tracks($options);
					break;

				case 'lastFMArtistRadio':
					$this->get_top_artists($options);
					break;

				default:
					logger::error('LASTFM', 'Unknown Mode',$rp['radiomode']);
			}
		}
	}

	private function get_top_tracks($options) {
		$tracks = lastfm::user_get_top_tracks($options);
		// logger::log('LASTFM', print_r($tracks, true));
		if (array_key_exists('toptracks', $tracks)) {
			$d = $tracks['toptracks'];
			if (array_key_exists('@attr', $d)) {
				logger::log('LASTFM', 'Got Page',$d['@attr']['page'],'of',$d['@attr']['totalPages']);
				prefs::set_radio_params([
					'toptracks_current' => $d['@attr']['page']+1,
					'toptracks_total' => $d['@attr']['totalPages']
				]);
			}
			if (array_key_exists('track', $d)) {
				$table_name = self::get_seed_table_name();
				foreach ($d['track'] as $track) {
					if (
						array_key_exists('name', $track)
						&& array_key_exists('artist', $track)
						&& array_key_exists('name', $track['artist'])
					) {
						logger::log('LASTFM', 'Top Track',$track['name'],$track['artist']['name']);
						$this->add_toptrack(
							self::TYPE_TOP_TRACK,
							$track['artist']['name'],
							$track['name']
						);
					}
				}
			}
		}
	}

	private function get_top_artists($options) {
		$minplays = 1;
		$m = [
			'overall' => 7,
			'12month' => 5,
			'6month'  => 4,
			'3month'  => 3,
			'1month'  => 2
		];
		if (array_key_exists($options['period'], $m))
			$minplays = $m[$options['period']];

		$artists = lastfm::user_get_top_artists($options);
		// logger::log('LASTFM', print_r($tracks, true));
		if (array_key_exists('topartists', $artists)) {
			$d = $artists['topartists'];
			if (array_key_exists('@attr', $d)) {
				logger::log('LASTFM', 'Got Page',$d['@attr']['page'],'of',$d['@attr']['totalPages']);
				prefs::set_radio_params([
					'toptracks_current' => $d['@attr']['page']+1,
					'toptracks_total' => $d['@attr']['totalPages']
				]);
			}
			if (array_key_exists('artist', $d)) {
				$table_name = self::get_seed_table_name();
				foreach ($d['artist'] as $artist) {
					if (
						array_key_exists('name', $artist)
						&& array_key_exists('playcount', $artist)
						&& $artist['playcount'] >= $minplays
					) {
						if ($this->check_audiobook_artist($artist['name'])) {
							logger::log('LASTFM', 'Top Artist',$artist['name']);
							$this->add_toptrack(
								self::TYPE_TOP_TRACK,
								$artist['name'],
								null
							);
						}
					}
				}
			}
		}
	}

	public function get_similar_seeds() {
		$rp = prefs::get_radio_params();
		$seeds = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, [],
			"SELECT * FROM ".self::get_seed_table_name()." WHERE Type = ? ORDER BY ".self::SQL_RANDOM_SORT." LIMIT 2",
			self::TYPE_TOP_TRACK
		);
		foreach ($seeds as $seed) {
			$this->sql_prepare_query(true, null, null, null,
				"UPDATE ".self::get_seed_table_name()." SET Type = ? WHERE topindex = ?",
				self::TYPE_USED_TOP_TRACK,
				$seed['topindex']
			);

			switch ($rp['radiomode']) {
				case 'lastFMTrackRadio':
					$this->get_similar_tracks($seed);
					break;

				case 'lastFMArtistRadio':
					$this->get_similar_artists($seed);
					break;
			}
		}
	}

	private function get_similar_tracks($seed) {
		$similars = lastfm::track_get_similar([
			'track' => $seed['Title'],
			'artist' => $seed['Artist'],
			'limit' => 50
		]);

		// logger::log('LASTFM', print_r($similars, true));
		if (array_key_exists('similartracks', $similars)) {
			$d = $similars['similartracks'];
			if (array_key_exists('track', $d)) {
				foreach ($d['track'] as $track) {
					if (
						array_key_exists('name', $track)
						&& array_key_exists('artist', $track)
						&& array_key_exists('name', $track['artist'])
					) {
						logger::log('LASTFM', 'Related Track',$track['name'],$track['artist']['name']);
						$this->add_toptrack(
							self::TYPE_RELATED_TRACK,
							$track['artist']['name'],
							$track['name']
						);
					}
				}
			}
		}
	}

	private function get_similar_artists($seed) {
		$similars = lastfm::artist_get_similar([
			'artist' => $seed['Artist'],
			'limit' => 50
		]);

		// logger::log('LASTFM', print_r($similars, true));
		if (array_key_exists('similarartists', $similars)) {
			$d = $similars['similarartists'];
			if (array_key_exists('artist', $d)) {
				foreach ($d['artist'] as $artist) {
					if (
						array_key_exists('name', $artist)
					) {
						logger::log('LASTFM', 'Related Artist',$artist['name']);
						$this->add_toptrack(
							self::TYPE_RELATED_TRACK,
							$artist['name'],
							null
						);
					}
				}
			}
		}
	}

	public function search_for_track() {
		$rp = prefs::get_radio_params();
		logger::log('LASTFM', $rp['radiomode'], $rp['radioparam']);
		$uris = [];
		$seeds = ['poop'];
		$retval = null;
		while (count($uris) == 0 && count($seeds) > 0) {
			$this->get_seeds();
			$this->get_similar_seeds();
			$seeds = $this->generic_sql_query("SELECT * FROM ".self::get_seed_table_name()." ORDER BY ".self::SQL_RANDOM_SORT." LIMIT 1");
			foreach ($seeds as $seed) {
				if ($seed['Type'] != self::TYPE_TOP_TRACK) {
					$this->sql_prepare_query(true, null, null, null,
						"DELETE FROM ".self::get_seed_table_name()." WHERE topindex = ?",
						$seed['topindex']
					);
				}
				$blarg = $this->fave_finder($seed);
				foreach ($blarg as $try) {
					$check = $this->check_audiobook_status($try);
					if ($check !== null)
						$uris[] = $check;
				}
			}
		}
		if (count($uris) > 0) {
			switch ($rp['radiomode']) {
				case 'lastFMTrackRadio':
					$retval = $uris[0];
					break;

				case 'lastFMArtistRadio':
					$retval = $this->handle_multi_tracks($uris);
					break;
			}
		}
		logger::log('LASTFM', 'Track Is',$retval);
		return $retval;
	}

	private function handle_multi_tracks($uris) {
		$table = self::get_uri_table_name();
		foreach ($uris as $uri) {
			$this->sql_prepare_query(true, null, null, null,
				"INSERT INTO ".$table." (Uri) VALUES (?)",
				$uri
			);
		}
		$retval = null;
		$r = $this->generic_sql_query("SELECT * FROM ".$table." ORDER BY ".self::SQL_RANDOM_SORT." LIMIT 1");
		if (count($r) > 0) {
			$this->sql_prepare_query(true, null, null, null,
				"DELETE FROM ".$table." WHERE uriindex = ?",
				$r[0]['uriindex']
			);
			$retval = $r[0]['Uri'];
		}
		return $retval;
	}

	private function check_audiobook_status($uri) {
		$albumindex = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, 'Albumindex', null,
			"SELECT Albumindex FROM Tracktable WHERE Uri = ?",
			$uri
		);
		if ($albumindex !== null) {
			$sorter = choose_sorter_by_key('zalbum'.$albumindex);
			$lister = new $sorter('zalbum'.$albumindex);
			if ($lister->album_trackcount($albumindex) > 0) {
				logger::log('LASTFM', $uri,'is from an Audiobook');
				return null;
			}
		}
		return $uri;
	}

	private function check_audiobook_artist($name) {
		$numab = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, 'num', 0,
			"SELECT COUNT(TTindex) AS num FROM Tracktable
				JOIN Artisttable USING (Artistindex)
				WHERE isAudiobook > 0
				AND Hidden = 0
				AND Artistname = ?",
			$name
		);
		$nummusic = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, 'num', 0,
			"SELECT COUNT(TTindex) AS num FROM Tracktable
				JOIN Artisttable USING (Artistindex)
				WHERE isAudiobook = 0
				AND Hidden = 0
				AND Artistname = ?",
			$name
		);
		logger::log('LASTFM', 'Artist',$name,'has',$numab,'audiobook tracks and',$nummusic,'music albums');
		if ($nummusic > 0) {
			return true;
		} else if ($nummusic == 0 && $numab == 0) {
			return true;
		}
		return false;
	}

	public function doPlaylist($numtracks, &$player) {
		while ($numtracks > 0) {
			$uri = $this->search_for_track();
			if ($uri === null) {
				return false;
			} else {
				$cmds = [join_command_string(array('add', $uri))];
				$player->do_command_list($cmds);
				$numtracks--;
			}
		}
		return true;
	}
}

?>