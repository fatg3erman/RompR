<?php

// lastFMTrackRadio
//   4 Last Fm Mix Stations

// lastFMArtistradio
//   The bottom 4 Last FM Stations

// Initial seeds - either Top Tracks or Top Artists (from Last.FM) - get added to TopTracks as TYPE_TOP_TRACK
// When we select a seed we use one that's marked as TYPE_TOP_TRACK and NOT TYPE_USED_AS_SEED
//  then we set it to TYPE_TOP_TRACK + TYPE_USED_AS SEED
// Then we use that to look up similar tracks or similar artists which get set as TYPE_RELATED_TRACK
// Finally we pick a track to search for using the criteria TYPE_TOP_TRACK or TYPE_RELATED_TRACK
// and that gets marked as TYPE_USED_FOR_SEARCH - so if it's a TOP_TRACK that hasnt been USED_AS_SEED it
// can still be used as a seed but won't be used as a search track again.

class lastfm_radio extends everywhere_radio {

	// search_for_track MUST return ONE Uri or NULL

	public function search_for_track() {
		$rp = prefs::get_radio_params();
		logger::log('LASTFM', $rp['radiomode'], $rp['radioparam']);
		$uris = [];
		$gotseeds = true;
		$retval = null;
		while (count($uris) == 0 && $gotseeds) {
			$this->get_seeds();
			$this->get_similar_seeds();
			list($uris, $gotseeds) = $this->do_seed_search(self::TYPE_TOP_TRACK + self::TYPE_RELATED_TRACK);
		}
		if (count($uris) > 0) {
			$this->handle_multi_tracks($uris);
		}
		$retval = $this->get_one_uri();

		logger::log('LASTFM', 'Track Is',$retval);
		return $retval;
	}

	private function get_seeds() {
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
					break;
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
								''
							);
						}
					}
				}
			}
		}
	}

	private function get_similar_seeds() {
		$rp = prefs::get_radio_params();
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
			'artist' => $seed['trackartist'],
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
			'artist' => $seed['trackartist'],
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
							''
						);
					}
				}
			}
		}
	}

}

?>