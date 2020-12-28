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
		return $this->sql_get_column("SELECT Genre FROM Genretable ORDER BY Genre ASC", 'Genre');
	}

	public function getartists() {
		$qstring = "SELECT DISTINCT Artistname FROM Tracktable JOIN Artisttable USING (Artistindex)
			WHERE (LinkChecked = 0 OR LinkChecked = 2) AND isAudiobook = 0 AND isSearchResult < 2 AND Hidden = 0 AND Uri IS NOT NULL
			ORDER BY ";
		foreach (prefs::$prefs['artistsatstart'] as $a) {
			$qstring .= "CASE WHEN LOWER(Artistname) = LOWER('".$a."') THEN 1 ELSE 2 END, ";
		}
		if (count(prefs::$prefs['nosortprefixes']) > 0) {
			$qstring .= "(CASE ";
			foreach(prefs::$prefs['nosortprefixes'] AS $p) {
				$phpisshitsometimes = strlen($p)+2;
				$qstring .= "WHEN LOWER(Artistname) LIKE '".strtolower($p).
					" %' THEN LOWER(SUBSTR(Artistname,".$phpisshitsometimes.")) ";
			}
			$qstring .= "ELSE LOWER(Artistname) END)";
		} else {
			$qstring .= "LOWER(Artistname)";
		}
		return $this->sql_get_column($qstring, 'Artistname');
	}

	public function getalbumartists() {
		$artists = array();
		$sorter = new sortby_artist('aartistroot');
		foreach ($sorter->root_sort_query() as $a) {
			$artists[] = $a['Artistname'];
		}
		return $artists;
	}

	public function getfaveartists() {
		// Can we have a tuning slider to increase the 'Playcount > x' value?
		$this->generic_sql_query(
			"CREATE TEMPORARY TABLE aplaytable AS SELECT SUM(Playcount) AS playtotal, Artistindex FROM
			(SELECT Playcount, Artistindex FROM Playcounttable JOIN Tracktable USING (TTindex) WHERE
			Playcount > 10) AS derived GROUP BY Artistindex", true);

		$artists = array();
		$result = $this->generic_sql_query(
			"SELECT playtot, Artistname FROM (SELECT SUM(Playcount) AS playtot, Artistindex FROM
			(SELECT Playcount, Artistindex FROM Playcounttable JOIN Tracktable USING (TTindex)) AS
			derived GROUP BY Artistindex) AS alias JOIN Artisttable USING (Artistindex) WHERE
			playtot > (SELECT AVG(playtotal) FROM aplaytable) ORDER BY ".database::SQL_RANDOM_SORT, false, PDO::FETCH_OBJ);
		foreach ($result as $obj) {
			logger::debug("FAVEARTISTS", "Artist :",$obj->Artistname);
			$artists[] = array( 'name' => $obj->Artistname, 'plays' => $obj->playtot);
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
		$retval = $this->generic_sql_query("SELECT TTindex, Uri, LinkChecked FROM Tracktable WHERE Uri LIKE 'spotify:%' AND Hidden = 0 AND isSearchResult < 2 AND LinkChecked < 2 ORDER BY TTindex ASC LIMIT 25");
		if (count($retval) == 0)
			$retval =  [ 'dummy' => 'baby' ];
		return $retval;
	}

	public function resetlinkcheck() {
		$this->generic_sql_query("UPDATE Tracktable SET LinkChecked = 0 WHERE LinkChecked = 2");
		$this->generic_sql_query("UPDATE Tracktable SET LinkChecked = 1 WHERE LinkChecked = 3");
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
			" AND Uri IS NOT NULL GROUP BY Artistindex ORDER BY playtotal DESC LIMIT ".$limit);

		// 2. Get a list of recently played tracks, ignoring popularity
		// $result = generic_sql_query(
		// 	"SELECT 0 AS playtotal, Artistname, Title, Uri
		// 	FROM Playcounttable JOIN Tracktable USING (TTindex)
		// 	JOIN Artisttable USING (Artistindex)
		// 	WHERE ".sql_two_weeks_include(intval($days/2)).
		// 	" AND Uri IS NOT NULL GROUP BY Artistindex ORDER BY ".database::SQL_RANDOM_SORT." LIMIT ".intval($limit/2));
		// $resultset = array_merge($resultset, $result);

		// 3. Get the top tracks overall
		$tracks = $this->get_track_charts(intval($limit/2));
		foreach ($tracks as $track) {
			if ($track['uri']) {
				$resultset[] = array('playtotal' => $track['soundcloud_plays'],
										'Artistname' => $track['label_artist'],
										'Title' => $track['label_track'],
										'Uri' => $track['uri']);
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
		$artists = array();
		$query = "SELECT SUM(Playcount) AS playtot, Artistindex, Artistname FROM
			 Playcounttable JOIN Tracktable USING (TTindex) JOIN Artisttable USING (Artistindex)";
		$query .= " GROUP BY Artistindex ORDER BY playtot DESC LIMIT 40";
		$result = $this->generic_sql_query($query, false, PDO::FETCH_OBJ);
		foreach ($result as $obj) {
			$artists[] = array( 'label_artist' => $obj->Artistname, 'soundcloud_plays' => $obj->playtot);
		}
		return $artists;
	}

	private function get_album_charts() {
		$albums = array();
		$query = "SELECT SUM(Playcount) AS playtot, Albumname, Artistname, AlbumUri, Albumindex
			 FROM Playcounttable JOIN Tracktable USING (TTindex) JOIN Albumtable USING (Albumindex)
			 JOIN Artisttable ON Albumtable.AlbumArtistindex = Artisttable.Artistindex";
		$query .= " GROUP BY Albumindex ORDER BY playtot DESC LIMIT 40";
		$result = $this->generic_sql_query($query, false, PDO::FETCH_OBJ);
		foreach ($result as $obj) {
			$albums[] = array( 'label_artist' => $obj->Artistname,
				'label_album' => $obj->Albumname,
				'soundcloud_plays' => $obj->playtot, 'uri' => $obj->AlbumUri);
		}
		return $albums;
	}

	private function get_track_charts($limit = 40) {
		$tracks = array();
		// Group by title and sum because we may have the same track on multiple backends
		$query = "SELECT
					Title,
					SUM(Playcount) AS Playcount,
					Artistname,
					".database::SQL_URI_CONCAT." AS Uris
				FROM
					Tracktable
					JOIN Playcounttable USING (TTIndex)
					JOIN Artisttable USING (Artistindex)
				GROUP BY Title, Artistname
				ORDER BY Playcount DESC LIMIT ".$limit;
		$result = $this->generic_sql_query($query, false, PDO::FETCH_OBJ);
		foreach ($result as $obj) {
			$uri = null;
			$uris = explode(',', $obj->Uris);
			foreach ($uris as $u) {
				if ($uri === null) {
					$uri = $u;
				} else if (getDomain($uri) != 'local' && getDomain($u) == 'local') {
					// Prepfer local to internet
					$uri = $u;
				} else if (getDomain($uri) == 'youtube' && strpos($u, 'prefs/youtubedl') !== false) {
					// Prefer downloaded youtube tracks to online ones
					$uri = $u;
				}
			}

			$tracks[] = array(
				'label_artist' => $obj->Artistname,
				'label_track' => $obj->Title,
				'soundcloud_plays' => $obj->Playcount,
				'uri' => $uri);
		}
		return $tracks;
	}

}

?>