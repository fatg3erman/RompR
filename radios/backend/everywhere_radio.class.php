<?php

class everywhere_radio extends musicCollection {

	// TYPE fields are a bitmask as a track in this table can be both a
	// TOP_TRACK (to be used as a seed) and a search track (to be used for searching for
	// tracks to play)
	const TYPE_TOP_TRACK = 1;
	const TYPE_USED_AS_SEED = 2;
	const TYPE_RELATED_TRACK = 4;
	const TYPE_USED_FOR_SEARCH = 8;

	public function preparePlaylist() {
		$this->create_toptracks_table();
		$this->create_radio_uri_table();
		$this->prepare();
		// return $this->search_for_track();
	}

	public static function get_seed_table_name() {
		return 'Toptracks_'.prefs::player_name_hash();
	}

	public static function get_uri_table_name() {
		return 'Smart_Uri_'.prefs::player_name_hash();
	}

	protected function prepare() {

	}

	// do_seed_search() takes a thing from TopTracks and searches for it.
	// It returns an array of tracks from fave_finder().
	// You can either use just one, OR call handle_multi_tracks() to put them
	// in the Uri table then call get_one_uri() to return something from that table.
	protected function do_seed_search($type = null) {
		$uris = [];
		$gotseeds = false;
		$query_params = [];
		$rp = prefs::get_radio_params();
		$qstring = "SELECT * FROM ".self::get_seed_table_name()." WHERE ";
		if ($type) {
			$qstring .= "Type & ? > 0 AND ";
			$query_params[] = $type;
		}
		$qstring .= "Type & ? = 0 ORDER BY ".self::SQL_RANDOM_SORT." LIMIT 1";
		$query_params[] = self::TYPE_USED_FOR_SEARCH;
		$seeds = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, [], $qstring, $query_params);
		foreach ($seeds as $seed) {
			$gotseeds = true;
			logger::log('EVRADIO', 'Got Seed', $seed['trackartist'], $seed['Title']);
			$this->sql_prepare_query(true, null, null, null,
				"UPDATE ".self::get_seed_table_name()." SET Type = Type + ? WHERE topindex = ?",
				self::TYPE_USED_FOR_SEARCH,
				$seed['topindex'],
			);
			$seed['Album'] = null;
			$blarg = $this->fave_finder($rp['radiodomains'], false, $seed, true);
			foreach ($blarg as $try) {
				if ($this->is_not_audiobook($try['file']))
					$uris[] = $try;
			}
		}
		return array($uris, $gotseeds);
	}

	protected function handle_multi_tracks($uris) {
		foreach ($uris as $uri) {
			logger::log('EVRADIO', 'Got Uri ',$uri['trackartist'], $uri['Title']);
			$this->add_smart_uri($uri['file'], $uri['trackartist'], $uri['Title']);
		}
	}

	protected function get_one_uri() {
		$table = self::get_uri_table_name();
		$retval = null;
		$r = $this->generic_sql_query("SELECT * FROM ".$table." WHERE used = 0 ORDER BY ".self::SQL_RANDOM_SORT." LIMIT 1");
		if (count($r) > 0) {
			$this->sql_prepare_query(true, null, null, null,
				"UPDATE ".$table." SET used = 1 WHERE uriindex = ?",
				$r[0]['uriindex']
			);
			$retval = $r[0]['Uri'];
		}
		return $retval;
	}

	private function is_not_audiobook($uri) {
		$isaudiobook = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, 'isAudiobook', 0,
			"SELECT isAudiobook FROM Tracktable WHERE Uri = ?",
			$uri
		);
		return ($isaudiobook == 0);
	}

	protected function check_audiobook_artist($name) {
		$nummusic = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, 'num', 0,
			"SELECT COUNT(TTindex) AS num FROM Tracktable
				JOIN Artisttable USING (Artistindex)
				WHERE isAudiobook = 0
				AND Uri NOT LIKE 'http%'
				AND Artistname = ?",
			$name
		);
		if ($nummusic > 0) {
			logger::debug('EVYWHR', 'Artist', $name, 'has', $nummusic,'music tracks');
			return true;
		}
		$numab = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, 'num', 0,
			"SELECT COUNT(TTindex) AS num FROM Tracktable
				JOIN Artisttable USING (Artistindex)
				WHERE isAudiobook > 0
				AND Artistname = ?",
			$name
		);
		if ($numab > 0) {
			logger::trace('EVYWHR', 'Artist', $name, 'has', $numab,'audiobook tracks - Ignoring');
			return false;
		}
		// At this point we know it doesn't have any non-audiobook tracks that aren't http://
		// and it doesn't have any tracks we've been told are Audiobooks.
		// If it DOES have non-audiobook tracks that are http then they're almost certainly
		// hidden tracks counting Podcast playcounts, so ignore it
		$numinternet = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, 'num', 0,
			"SELECT COUNT(TTindex) AS num FROM Tracktable
				JOIN Artisttable USING (Artistindex)
				WHERE isAudiobook = 0
				AND Uri LIKE 'http%'
				AND Artistname = ?",
			$name
		);
		if ($numinternet > 0) {
			logger::log('EVYWHR', 'Artist', $name, 'has', $numinternet,'internet tracks - Ignoring');
			return false;
		}
		return true;
	}

	public function doPlaylist($param, $numtracks, &$player) {
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

	protected function get_fave_artists($type) {

		// Ignore Audiobooks and all internet tracks since those are likely podcasts
		// We might still get some spoken word artists from Hidden tracks, but we can't
		// ignore those because they might be the majority of your listeing.

		$average_plays = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, 'average', 1,
			"SELECT AVG(totalplays) AS average FROM
			(
				SELECT
					SUM(Playcount) AS totalplays,
					Artistindex
				FROM
					Playcounttable
					JOIN Tracktable USING (TTindex)
					JOIN Artisttable USING (Artistindex)
				WHERE isAudiobook = ?
				AND Uri NOT LIKE 'http%'
				GROUP BY Artistindex
			) AS ploppy",
			0
		);

		logger::log('FAVEARTISTS', 'Average Playcount is',$average_plays);

		$artists = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, [],
			"SELECT
				SUM(Playcount) AS totalplays,
				Artistindex,
				Artistname
			FROM
				Playcounttable
				JOIN Tracktable USING (TTindex)
				JOIN Artisttable USING (Artistindex)
			WHERE isAudiobook = ?
			AND Uri NOT LIKE 'http%'
			GROUP BY Artistindex
			HAVING totalplays > ".round($average_plays, 0, PHP_ROUND_HALF_UP),
			0
		);

		logger::log('FAVEARTISTS', 'Found',count($artists),'fave artists');

		foreach ($artists as $artist) {
			logger::log('FARTIST', "Adding Fave Artist", $artist['Artistname']);
			$this->add_toptrack(
				$type,
				$artist['Artistname'],
				''
			);
		}
	}

}

?>