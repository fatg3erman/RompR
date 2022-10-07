<?php

class everywhere_radio extends musicCollection {

	const TYPE_TOP_TRACK = 0;
	const TYPE_RELATED_TRACK = 1;
	const TYPE_USED_TOP_TRACK = 2;

	public function preparePlaylist() {
		$this->create_toptracks_table();
		$this->create_radio_uri_table();
		$this->prepare();
		return $this->search_for_track();
	}

	public static function get_seed_table_name() {
		return 'Toptracks_'.prefs::player_name_hash();
	}

	public static function get_uri_table_name() {
		return 'Smart_Uri_'.prefs::player_name_hash();
	}

	protected function prepare() {

	}

	protected function do_seed_search($type = null) {
		$uris = [];
		$gotseeds = false;
		if ($type) {
			$seeds = $this->generic_sql_query("SELECT * FROM ".self::get_seed_table_name()." WHERE Type = ".$type." ORDER BY ".self::SQL_RANDOM_SORT." LIMIT 1");
		} else {
			$seeds = $this->generic_sql_query("SELECT * FROM ".self::get_seed_table_name()." ORDER BY ".self::SQL_RANDOM_SORT." LIMIT 1");
		}
		foreach ($seeds as $seed) {
			$gotseeds = true;
			if ($seed['Type'] != self::TYPE_TOP_TRACK) {
				$this->sql_prepare_query(true, null, null, null,
					"DELETE FROM ".self::get_seed_table_name()." WHERE topindex = ?",
					$seed['topindex']
				);
			}
			$blarg = $this->fave_finder($seed);
			foreach ($blarg as $try) {
				if ($this->is_not_audiobook($try['file']))
					$uris[] = $try;
			}
		}
		return array($uris, $gotseeds);
	}

	protected function handle_multi_tracks($uris) {
		foreach ($uris as $uri) {
			$this->add_smart_uri($uri['file'], $uri['trackartist'], $uri['Title']);
		}
	}

	protected function get_one_uri() {
		$table = self::get_uri_table_name();
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
			logger::log('EVYWHR', 'Artist', $name, 'has', $nummusic,'music tracks');
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
			logger::log('EVYWHR', 'Artist', $name, 'has', $numab,'audiobook tracks - Ignoring');
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

	protected function is_artist_or_album($file) {
		if (
			(static::IGNORE_ALBUMS && strpos($file, ':album:') !== false)
			|| strpos($file, ':artist:') !== false
		) {
			return true;
		}
		return false;
	}

	protected function compare_tracks_with_artist($lookingfor, $track) {
		if ($lookingfor['Artist'] && $lookingfor['Title']) {
			if ($this->strip_track_name($lookingfor['Artist']) == $this->strip_track_name($track['trackartist'])
				&& $this->strip_track_name($lookingfor['Title']) == $this->strip_track_name($track['Title'])) {
				return true;
			}
		} else if ($lookingfor['Artist']) {
			if ($this->strip_track_name($lookingfor['Artist']) == $this->strip_track_name($track['trackartist'])) {
				return true;
			}
		}
		return false;
	}

	protected function strip_track_name($thing) {
		$thing = strtolower($thing);
		$thing = preg_replace('/\s+\&\s+/', ' and ', $thing);
		$thing = preg_replace('/\(.*? mix\)$/', '', $thing);
		$thing = preg_replace("/\pP/", '', $thing);
		return trim($thing);
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
				null
			);
		}
	}

}

?>