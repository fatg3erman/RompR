<?php

class lastfm_radio extends musicCollection {

	const TYPE_TOP_TRACK = 0;
	const TYPE_RELATED_TRACK = 1;
	const TYPE_USED_TOP_TRACK = 2;

	public function preparePlaylist() {
		$this->create_toptracks_table();
		return $this->search_for_track();
	}

	public static function get_seed_table_name() {
		return 'Toptracks_'.prefs::player_name_hash();
	}

	public function get_top_tracks() {
		$rp = prefs::get_radio_params();
		if ($rp['toptracks_current'] <= $rp['toptracks_total']) {
			$page = $rp['toptracks_current'];
			$options = [
				'period' => $rp['radioparam'],
				'page' => $page
			];
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
					$table_name = 'Toptracks_'.prefs::player_name_hash();
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
	}

	public function get_similar_tracks() {
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
	}

	public function search_for_track() {
		$uri = null;
		$seeds = ['poop'];
		while ($uri == null && count($seeds) > 0) {
			$this->get_top_tracks();
			$this->get_similar_tracks();
			$seeds = $this->generic_sql_query("SELECT * FROM ".self::get_seed_table_name()." ORDER BY ".self::SQL_RANDOM_SORT." LIMIT 1");
			foreach ($seeds as $seed) {
				if ($seed['Type'] != self::TYPE_TOP_TRACK) {
					$this->sql_prepare_query(true, null, null, null,
						"DELETE FROM ".self::get_seed_table_name()." WHERE topindex = ?",
						$seed['topindex']
					);
				}
				$uri = $this->fave_finder($seed);
				$uri = $this->check_audiobook_status($uri);
			}
		}
		logger::log('LASTFM', 'Track Is',$seed,$uri);
		return $uri;
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