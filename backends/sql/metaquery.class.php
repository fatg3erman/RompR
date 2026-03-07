<?php

class metaquery extends musiccollection {

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
			WHERE isAudiobook = 0 AND isSearchResult < 2 AND Hidden = 0 AND Uri IS NOT NULL
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
		// The data we put in this table might have { album: { the data we want}}
		// or it might have {the data we want}
		// Munge it so it's all the same
		$result = $this->generic_sql_query("SELECT * FROM AlbumsToListenTotable");
		$retval =  array();
		foreach ($result as $r) {
			$d = json_decode($r['JsonData'], true);
			if (array_key_exists('album', $d))
				$d = $d['album'];
			$d['rompr_index'] = $r['Listenindex'];
			$retval[] = $d;
		}
		return $retval;
	}

	public function getcharts($data) {
		return [
			'Artists' => $this->get_artist_charts(),
			'Albums' => $this->get_album_charts(),
			'Tracks' => $this->get_track_charts()
		];
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
		$this->sql_prepare_query(true, null, null, null, "DELETE FROM AlbumsToListenTotable WHERE Listenindex = ?", $id);
	}

	public function getalbumsasspoti($p) {
		$artist = concatenate_artist_names($p['artist']);
		logger::log("JOHN", "Getting albums info for $artist");
		$rawterms = [
			'artist' => $artist
		];
		$this->options['searchterms'] = $rawterms;
		$this->options['doing_search'] = true;
		$this->options['trackbytrack'] = false;

		$collection = new db_collection();
		$t = $collection->doDbCollection($rawterms, [], true, true);
		foreach ($t as $filedata) {
			$this->newTrack($filedata);
		}
		$this->tracks_as_array(true);
	}

	private function spotifyAlbumId($album) {
		if (array_key_exists('album', $album)) {
			return $album['album']['id'];
		} else {
			return $album['id'];
		}
	}

	private function get_artist_charts() {
		// This uses the track charts query as a subquery to remove duplicate
		// tracks - ensures we only count each track once.
		$query = "SELECT
			Artistname AS label_artist,
			SUM(Playcount) AS soundcloud_plays
			FROM (
				SELECT Title, Artistname, Albumname, MAX(Playcount) AS Playcount, MIN(AlbumUri) AS AlbumUri
				FROM Tracktable
				JOIN Playcounttable USING (TTindex)
				JOIN Albumtable USING (albumindex)
				JOIN Artisttable ON Albumtable.AlbumArtistindex = Artisttable.Artistindex
				{$this->charts_include_option()}
				GROUP BY Artistname, Albumname, Title
			) AS nodupes
			GROUP BY label_artist
			ORDER BY soundcloud_plays DESC LIMIT 40";
		return $this->generic_sql_query($query, false, PDO::FETCH_OBJ);
	}

	private function get_album_charts() {
		// This uses the track charts query as a subquery to remove duplicate
		// tracks - ensures we only count each track once.
		$query = "SELECT
			Artistname AS label_artist,
			Albumname AS label_album,
			SUM(Playcount) AS soundcloud_plays,
			MIN(AlbumUri) AS Uri
			FROM (
				SELECT Title, Artistname, Albumname, MAX(Playcount) AS Playcount, MIN(AlbumUri) AS AlbumUri
				FROM Tracktable
				JOIN Playcounttable USING (TTindex)
				JOIN Albumtable USING (albumindex)
				JOIN Artisttable ON Albumtable.AlbumArtistindex = Artisttable.Artistindex
				{$this->charts_include_option()}
				GROUP BY Artistname, Albumname, Title
			) AS nodupes
			GROUP BY label_artist, label_album
			ORDER BY soundcloud_plays DESC LIMIT 40";
		return $this->generic_sql_query($query, false, PDO::FETCH_OBJ);
	}

}

?>
