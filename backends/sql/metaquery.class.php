<?php

class metaquery extends collection_base {

	public function gettags() {

		// gettags
		//		Return a sorted lst of tag names. Used by the UI for creating the tag menu

		$tags = array();
		$result = $this->generic_sql_query("SELECT Name FROM Tagtable ORDER BY LOWER(Name)");
		foreach ($result as $r) {
			$tags[] = $r['Name'];
		}
		return $tags;
	}

	public function getgenres() {
		return $this->sql_get_column("SELECT Genre FROM Genretable ORDER BY Genre ASC", 0);
	}

	public function getartists() {
		$qstring = "SELECT DISTINCT Artistname FROM Tracktable JOIN Artisttable USING (Artistindex)
			WHERE (LinkChecked = 0 OR LinkChecked = 2) AND isAudiobook = 0 AND isSearchResult < 2 AND Hidden = 0 AND Uri IS NOT NULL
			ORDER BY ";
		foreach (prefs::get_pref('artistsatstart') as $a) {
			$qstring .= "CASE WHEN Artistname = '".$a."' THEN 1 ELSE 2 END, ";
		}
		if (count(prefs::get_pref('nosortprefixes')) > 0) {
			$qstring .= "(CASE ";
			foreach(prefs::get_pref('nosortprefixes') AS $p) {
				$phpisshitsometimes = strlen($p)+2;
				$qstring .= "WHEN Artistname LIKE '".$p.
					" %' THEN LOWER(SUBSTR(Artistname,".$phpisshitsometimes.")) ";
			}
			$qstring .= "ELSE LOWER(Artistname) END)";
		} else {
			$qstring .= "LOWER(Artistname)";
		}
		return $this->sql_get_column($qstring, 0);
	}

	public function getalbumartists() {
		$artists = array();
		$sorter = new sortby_artist('aartistroot');
		foreach ($sorter->root_sort_query() as $a) {
			$artists[] = $a['Artistname'];
		}
		return $artists;
	}

	public function getlistenlater() {
		$result = $this->generic_sql_query("SELECT * FROM AlbumsToListenTotable");
		$retval =  array();
		foreach ($result as $r) {
			$d = json_decode($r['JsonData']);
			$d->rompr_index = $r['Listenindex'];
			$retval[] = $d;
		}
		return $retval;
	}

	public function getlinktocheck() {
		// LinkChecked:
		// 0 = Not Checked, Assumed Playable or Playable at last check
		// 1 = Not Checked, Unplayable at last check
		// 2 = Checked, Playable
		// 3 = Checked, Unplayable
		// 4 = marked unplayable by setup screen
		// This function returns true when there are no more tracks to check.
		// The backend daemon polls a chunk of 25 tracks every time it wakes up
		// until it has done them all, then it waits for link_checker_frequency seconds
		$retval = false;
		$ids = [];
		$tracks = $this->generic_sql_query("SELECT TTindex, Uri, LinkChecked FROM Tracktable WHERE Uri LIKE 'spotify:%' AND Hidden = 0 AND isSearchResult < 2 AND LinkChecked < 2 ORDER BY TTindex ASC LIMIT 25");
		foreach ($tracks as $track) {
			$ids[] = preg_replace('/spotify:track:/', '', $track['Uri']);
		}
		if (count($ids) > 0) {
			logger::log('RELINKING', 'Got chunk of',count($ids),'spotify tracks to check');
			$this->open_transaction();
			$retval = true;
			$trackinfo = spotify::track_checklinking(['id' => $ids, 'cache' => false], false);
			$spoti_data = json_decode($trackinfo, true);
			foreach ($tracks as $i => $my_track) {
				$uri = $my_track['Uri'];
				$status = 3;
				$spoti_track = $spoti_data['tracks'][$i];
				if ($spoti_track) {
					if ($spoti_track['is_playable']) {
						logger::log('RELINKING', 'Track',$spoti_track['name'],'is playable');
						if (array_key_exists('linked_from', $spoti_track)) {
							logger::log('RELINKING', '  Track',$spoti_track['name'],'is relinked',$uri, $spoti_track['linked_from']['uri']);
							$uri = $spoti_track['linked_from']['uri'];
						} else {
							$uri = $spoti_track['uri'];
						}
						$status = 2;
					} else {
						logger::log('RELINKING', 'Track',$spoti_track['name'],'is not playable');
						if ($spoti_track['restrictions']) {
							logger::log('RELINKING','  Restrictions',$spoti_track['restrictions']['reason']);
						}
					}
				} else {
					logger::log('RELINKING', 'No data from spotify for TTindex',$my_track['TTindex']);
				}
				$this->updateCheckedLink($my_track['TTindex'], $uri, $status);
			}
			$this->close_transaction();
		} else {
			prefs::set_pref(['link_checker_is_running' => false]);
			prefs::save();
		}
		return $retval;
	}

	public function resetlinkcheck() {
		if (!prefs::get_pref('link_checker_is_running')) {
			$this->generic_sql_query("UPDATE Tracktable SET LinkChecked = 0 WHERE LinkChecked = 2 OR LinkChecked = 4");
			$this->generic_sql_query("UPDATE Tracktable SET LinkChecked = 1 WHERE LinkChecked = 3");
			prefs::set_pref(['link_checker_is_running' => true]);
			prefs::save();
		}
	}

	public function getcharts($data) {
		return [
			'Artists' => $this->get_artist_charts(),
			'Albums' => $this->get_album_charts(),
			'Tracks' => $this->get_track_charts()
		];
	}

	public function get_recommendation_seeds($days, $limit, $top) {

		// 1. Get a list of tracks played in the last $days days, sorted by their OVERALL popularity
		$resultset = $this->generic_sql_query(
			"SELECT SUM(Playcount) AS playtotal, Artistname, Title, Uri
			FROM Playcounttable JOIN Tracktable USING (TTindex)
			JOIN Artisttable USING (Artistindex)
			WHERE ".$this->sql_two_weeks_include($days).
			" AND Uri IS NOT NULL AND Uri NOT LIKE 'http%' AND isAudiobook = 0
			GROUP BY Artistname, Title ORDER BY playtotal DESC LIMIT ".$limit);

		// 2. Get a list of recently played tracks, ignoring popularity
		// $result = generic_sql_query(
		// 	"SELECT 0 AS playtotal, Artistname, Title, Uri
		// 	FROM Playcounttable JOIN Tracktable USING (TTindex)
		// 	JOIN Artisttable USING (Artistindex)
		// 	WHERE ".sql_two_weeks_include(intval($days/2)).
		// 	" AND Uri IS NOT NULL GROUP BY Artistindex ORDER BY ".database::SQL_RANDOM_SORT." LIMIT ".intval($limit/2));
		// $resultset = array_merge($resultset, $result);

		// 3. Get the top tracks overall
		$tracks = $this->get_track_charts(intval($top), CHARTS_MUSIC_ONLY);
		foreach ($tracks as $track) {
			if ($track->Uri) {
				$resultset[] = [
					'playtotal' => $track->soundcloud_plays,
					'Artistname' => $track->label_artist,
					'Title' => $track->label_track,
					'Uri' => $track->Uri
				];
			}
		}

		// 4. Randomise that list and return the first $top.
		shuffle($resultset);
		return array_slice($resultset,0,$top);
	}

	public function addToListenLater($album) {
		$newid = $this->spotifyAlbumId($album);
		$result = $this->generic_sql_query("SELECT * FROM AlbumsToListenTotable");
		foreach ($result as $r) {
			$d = json_decode($r['JsonData'], true);
			$thisid = $this->spotifyAlbumId($d);
			if ($thisid == $newid) {
				logger::warn("LISTENLATER", "Trying to add duplicate album to Listen Later");
				return;
			}
		}
		$d = json_encode($album);
		$this->sql_prepare_query(true, null, null, null, "INSERT INTO AlbumsToListenTotable (JsonData) VALUES (?)", $d);
	}

	public function removeListenLater($id) {
		$this->generic_sql_query("DELETE FROM AlbumsToListenTotable WHERE Listenindex = ".$id, true);
	}

	public function updateCheckedLink($ttindex, $uri, $status) {
		logger::trace("METADATA", "Updating Link Check For TTindex",$ttindex,$uri);
		$this->sql_prepare_query(true, null, null, null,
			"UPDATE Tracktable SET LinkChecked = ?, Uri = ? WHERE TTindex = ?", $status, $uri, $ttindex);
	}

	private function spotifyAlbumId($album) {
		if (array_key_exists('album', $album)) {
			return $album['album']['id'];
		} else {
			return $album['id'];
		}
	}

	private function get_artist_charts() {
		$query = "SELECT
			Artistname AS label_artist,
			SUM(Playcount) AS soundcloud_plays
			FROM
			Tracktable JOIN Playcounttable USING (TTindex)
			JOIN Artisttable USING (Artistindex)
			".$this->charts_include_option()."
			GROUP BY label_artist ORDER BY soundcloud_plays DESC LIMIT 40";
		return $this->generic_sql_query($query, false, PDO::FETCH_OBJ);
	}

	private function get_album_charts() {
		$query = "SELECT
			Artistname AS label_artist,
			Albumname AS label_album,
			SUM(Playcount) AS soundcloud_plays,
			AlbumUri AS Uri
			FROM Playcounttable JOIN Tracktable USING (TTindex)
			JOIN Albumtable USING (Albumindex)
			JOIN Artisttable ON Albumtable.AlbumArtistindex = Artisttable.Artistindex
			".$this->charts_include_option()."
			GROUP BY label_artist, label_album ORDER BY soundcloud_plays DESC LIMIT 40";
		return $this->generic_sql_query($query, false, PDO::FETCH_OBJ);
	}

	private function get_track_charts($limit = 40, $include = null) {
		$query = "SELECT
			Artistname AS label_artist,
			Albumname AS label_album,
			Title AS label_track,
			SUM(Playcount) AS soundcloud_plays,
			Uri
			FROM
			Tracktable
			JOIN Playcounttable USING (TTIndex)
			JOIN Albumtable USING (Albumindex)
			JOIN Artisttable USING (Artistindex)
			".$this->charts_include_option($include)."
			GROUP BY label_artist, label_album, label_track
			ORDER BY soundcloud_plays DESC LIMIT ".$limit;
		return $this->generic_sql_query($query, false, PDO::FETCH_OBJ);
	}

	private function charts_include_option($include = null) {
		if ($include == null)
			$include = prefs::get_pref('chartoption');
		switch ($include) {
			case CHARTS_INCLUDE_ALL:
				return '';
				break;

			case CHARTS_MUSIC_ONLY:
				return ' WHERE isAudiobook = 0 AND Hidden = 0 ';
				break;

			case CHARTS_AUDIOBOOKS_ONLY:
				return ' WHERE isAudiobook > 0 AND Hidden = 0 ';
				break;

			case CHARTS_INTERNET_ONLY:
				return ' WHERE Hidden = 1 ';
				break;
		}
	}

}

?>