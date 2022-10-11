<?php

class collection_radio extends musicCollection {

	public function preparePlaylist() {
		$this->generic_sql_query('UPDATE Tracktable SET usedInPlaylist = 0 WHERE usedInPlaylist = 1');
		$this->init_random_albums();
	}

	public function doPlaylist($playlist, $limit, &$player) {
		logger::mark("SMARTRADIO", "Loading Playlist",$playlist,'limit',$limit);
		$sqlstring = "";
		$tags = null;
		$random = true;
		switch($playlist) {
			case "1stars":
				$sqlstring = "SELECT Uri FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Rating > 0";
				break;
			case "2stars":
				$sqlstring = "SELECT Uri FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Rating > 1";
				break;
			case "3stars":
				$sqlstring = "SELECT Uri FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Rating > 2";
				break;
			case "4stars":
				$sqlstring = "SELECT Uri FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Rating > 3";
				break;
			case "5stars":
				$sqlstring = "SELECT Uri FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Rating > 4";
				break;

			case "recentlyadded_byalbum":
				$random = false;
			case "recentlyadded_random":
				$sqlstring = $this->sql_recent_tracks();
				break;

			case "favealbums":
				$avgplays = $this->getAveragePlays();
				$sqlstring = "SELECT Uri FROM Tracktable JOIN Albumtable USING (Albumindex)
					WHERE Albumindex IN
					(SELECT Albumindex FROM Tracktable JOIN Playcounttable USING (TTindex)
					LEFT JOIN Ratingtable USING (TTindex) WHERE Uri IS NOT NULL
					AND (Playcount > ".$avgplays." OR Rating IS NOT NULL))";
				$random = false;
				break;

			case "mostplayed":
				// Used to be tracks with above average playcount, now also includes any rated tracks.
				// Still called mostplayed :)
				$avgplays = $this->getAveragePlays();
				$sqlstring = "SELECT Uri FROM Tracktable JOIN Playcounttable USING (TTindex)
					LEFT JOIN Ratingtable USING (TTindex) WHERE Uri IS NOT NULL
					AND (Playcount > ".$avgplays." OR Rating IS NOT NULL)";
				break;

			case "allrandom":
				$sqlstring = "SELECT Uri FROM Tracktable WHERE Uri IS NOT NULL";
				break;

			case "neverplayed":
				$sqlstring = "SELECT Tracktable.Uri FROM Tracktable LEFT JOIN Playcounttable ON
					Tracktable.TTindex = Playcounttable.TTindex WHERE Playcounttable.TTindex IS NULL";
				break;

			case "recentlyplayed":
				$sqlstring = $this->recently_played_playlist();
				break;

			default:
				if (preg_match('/^(\w+)\+(.+)$/', $playlist, $matches)) {
					$fn = 'smart_radio_'.$matches[1];
					list($sqlstring, $tags) = $this->{$fn}($matches[2]);
				} else {
					logger::warn("SMART RADIO", "Unrecognised playlist",$playlist);
				}
				break;
		}
		$sqlstring .= ' AND (LinkChecked = 0 OR LinkChecked = 2) AND isAudiobook = 0 AND usedInPlaylist = 0 AND isSearchResult < 2 AND Hidden = 0 AND Uri IS NOT NULL';
		if (prefs::get_pref('collection_player') == 'mopidy' && prefs::get_pref('player_backend') == 'mpd') {
			$sqlstring .= ' AND Uri LIKE "local:%"';
		}
		$uris = $this->get_tracks($sqlstring, $limit, $tags, $random);
		$cmds = array();
		foreach ($uris as $u) {
			$cmds[] = join_command_string(array('add', $u));
		}
		$player->do_command_list($cmds);
		return true;
	}

	private function smart_radio_tag($param) {
		$taglist = explode(',', $param);
		$sqlstring = 'SELECT DISTINCT Uri FROM Tracktable JOIN TagListtable USING (TTindex) JOIN Tagtable USING (Tagindex) WHERE ';
		// Concatenate this bracket here otherwise Atom's syntax colouring goes haywire
		$sqlstring .= '(';
		$tags = array();
		foreach ($taglist as $i => $tag) {
			logger::trace("SMART RADIO", "Getting tag playlist for",$tag);
			$tags[] = trim($tag);
			if ($i > 0) {
				$sqlstring .= " OR ";
			}
			$sqlstring .=  "LOWER(Tagtable.Name) = LOWER(?)";
		}
		$sqlstring .= ")";
		return array($sqlstring, $tags);
	}

	private function smart_radio_genre($param) {
		$genrelist = explode(',', $param);
		$sqlstring = 'SELECT DISTINCT Uri FROM Tracktable JOIN Genretable USING (Genreindex) WHERE ';
		// Concatenate this bracket here otherwise Atom's syntax colouring goes haywire
		$sqlstring .= '(';
		$tags = array();
		foreach ($genrelist as $i => $genre) {
			logger::trace("SMART RADIO", "Getting genre playlist for",$genre);
			$tags[] = trim($genre);
			if ($i > 0) {
				$sqlstring .= " OR ";
			}
			$sqlstring .=  "LOWER(Genre) = LOWER(?)";
		}
		$sqlstring .= ")";
		return array($sqlstring, $tags);
	}

	private function smart_radio_artist($param) {
		$artistlist = explode(',', $param);
		$sqlstring = 'SELECT DISTINCT Uri FROM Tracktable JOIN Artisttable USING (Artistindex) WHERE ';
		// Artistname is COLLATE NOCASE in sqlite, and case insensitive by default in mysql
		$sqlstring .= '(';
		$tags = array();
		foreach ($artistlist as $i => $artist) {
			logger::trace("SMART RADIO", "Getting artist playlist for",$artist);
			$tags[] = trim($artist);
			if ($i > 0) {
				$sqlstring .= " OR ";
			}
			$sqlstring .=  "Artistname = ?";
		}
		$sqlstring .= ")";
		return array($sqlstring, $tags);
	}

	private function smart_radio_custom($param) {
		$station = json_decode(file_get_contents('prefs/customradio/'.format_for_disc($param).'.json'), true);
		$tags = array();
		$sqlstring = "SELECT DISTINCT Uri FROM
			Tracktable
			JOIN Artisttable AS ta USING (Artistindex)
			JOIN Albumtable USING (Albumindex)
			JOIN Artisttable AS aa ON (Albumtable.AlbumArtistindex = aa.Artistindex)
			LEFT JOIN Genretable USING (Genreindex)
			LEFT JOIN Ratingtable USING (TTindex)
			LEFT JOIN Playcounttable USING (TTindex)
			LEFT JOIN TagListtable USING (TTindex)
			LEFT JOIN Tagtable USING (Tagindex)
			WHERE (";
		foreach ($station['rules'] as $i => $rule) {
			if ($i > 0) {
				$sqlstring .= $station['combine_option'];
			}
			$values = explode(',', $rule['value']);
			$sqlstring .= '(';
			foreach ($values as $j => $value) {
				logger::log('CUSTOMRADIO',$rule['db_key'],$value);
				if ($j > 0) {
					switch ($rule['option']) {
						case RADIO_RULE_OPTIONS_STRING_IS:
						case RADIO_RULE_OPTIONS_STRING_CONTAINS:
							$sqlstring .= ' OR ';
							break;

						case RADIO_RULE_OPTIONS_STRING_IS_NOT:
						case RADIO_RULE_OPTIONS_STRING_NOT_CONTAINS:
							$sqlstring .= ' AND ';
							break;

						default:
							logger::error('CUSTOMRADIO', 'Multiple values in integer option!');
							break;
					}
				}

				if (preg_match('/db_function_(.+)$/', $rule['db_key'], $matches)) {
					$function = $matches[1];
					$sqlstring .= $this->{$function}($rule['option'], trim($value));
				} else {
					switch ($rule['option']) {
						case RADIO_RULE_OPTIONS_STRING_IS:
							$tags[] = trim($value);
							$sqlstring .= 'LOWER('.$rule['db_key'].') = LOWER(?)';
							break;

						case RADIO_RULE_OPTIONS_STRING_IS_NOT:
							$tags[] = trim($value);
							$sqlstring .= 'LOWER('.$rule['db_key'].') IS NOT LOWER(?)';
							break;
							break;

						case RADIO_RULE_OPTIONS_STRING_CONTAINS:
							$tags[] = "%".strtolower(trim($value))."%";
							$sqlstring .= 'LOWER('.$rule['db_key'].") LIKE ?";
							break;

						case RADIO_RULE_OPTIONS_STRING_NOT_CONTAINS:
							$tags[] = "%".strtolower(trim($value))."%";
							$sqlstring .= 'LOWER('.$rule['db_key'].") NOT LIKE ?";
							break;

						case RADIO_RULE_OPTIONS_INTEGER_LESSTHAN:
							$tags[] = trim($value);
							$sqlstring .= $rule['db_key'].' < ?';
							break;

						case RADIO_RULE_OPTIONS_INTEGER_EQUALS:
							$tags[] = trim($value);
							$sqlstring .= $rule['db_key'].' = ?';
							break;

						case RADIO_RULE_OPTIONS_INTEGER_GREATERTHAN:
							$tags[] = trim($value);
							$sqlstring .= $rule['db_key'].' > ?';
							break;

						case RADIO_RULE_OPTIONS_INTEGER_ISNOT:
							$tags[] = trim($value);
							$sqlstring .= '('.$rule['db_key'].' <> ? OR '.$rule['db_key'].' IS NULL)';
							break;

						case RADIO_RULE_OPTIONS_STRING_EXISTS:
							$sqlstring .= $rule['db_key'].' IS NOT NULL';
							break;

						default:
							logger::error('CUSTOMRADIO', 'Unknown Option Value',$rule['option']);
							break;

					}
				}

			}
			$sqlstring.= ')';

		}
		$sqlstring .= ")";

		return array($sqlstring, $tags);
	}

	private function get_tracks($sqlstring, $limit, $tags, $random = true) {

		// Get all track URIs using a supplied SQL string. For playlist generators
		$uris = array();
		$tries = 0;
		$rndstr = $random ? " ORDER BY ".self::SQL_RANDOM_SORT : " ORDER BY randomSort, Albumindex, Disc, TrackNo";
		$sqlstring .= ' '.$rndstr.' LIMIT '.$limit;
		logger::log('GET TRACKS', $sqlstring);
		if ($tags) {
			foreach ($tags as $t) {
				logger::log('GETALLURILS', '  Param :',$t);
			}
		}
		do {
			if ($tries == 1) {
				logger::info("GET TRACKS", "No URIs found. Resetting history table");
				$this->preparePlaylist();
			}
			if ($tags) {
				$uris = $this->sql_prepare_query(false, PDO::FETCH_COLUMN, 0, null, $sqlstring, $tags);
			} else {
				$uris = $this->sql_get_column($sqlstring, 0);
			}
			$tries++;
		} while (count($uris) == 0 && $tries < 2);
		foreach ($uris as $uri) {
			$this->sql_prepare_query(true, null, null, null, 'UPDATE Tracktable SET usedInPlaylist = 1 WHERE Uri = ?', $uri);
		}
		if (count($uris) == 0) {
			$uris = array('NOTRACKS!');
		}
		return $uris;
	}

	private function getAveragePlays() {
		$avgplays = $this->generic_sql_query('SELECT AVG(Playcount) AS average FROM Playcounttable');
		if (count($avgplays) > 0) {
			$poo = round($avgplays[0]['average'], 0, PHP_ROUND_HALF_DOWN);
			logger::log('AVERAGE', 'Average Playcount is', $poo);
			return $poo;
		} else {
			return 0;
		}
	}

}


?>