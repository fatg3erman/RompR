<?php

class sortby_rating extends sortby_base {

	public function root_sort_query() {
		global $prefs;
		$sflag = $this->filter_root_on_why();
		$qstring =
		"SELECT DISTINCT Rating
			FROM Ratingtable
			JOIN Tracktable USING (TTindex)
		WHERE
			Uri IS NOT NULL
			AND Hidden = 0
			AND Rating > 0
			".track_date_check($prefs['collectionrange'], $this->why)."
			".$sflag."
		ORDER BY Rating ASC";
		$result = generic_sql_query($qstring, false, PDO::FETCH_ASSOC);
		foreach ($result as $rating) {
			yield $rating;
		}
	}

	public function album_sort_query($unused) {
		global $prefs;
		$sflag = $this->filter_album_on_why();
		$qstring =
		"SELECT Albumtable.*, Artisttable.Artistname
			FROM Albumtable
			JOIN Artisttable ON (Albumtable.AlbumArtistindex = Artisttable.Artistindex)
			WHERE Albumindex IN
				(SELECT DISTINCT Albumindex FROM
				Tracktable JOIN Ratingtable USING (TTindex)
				WHERE Rating = ".$this->who." AND ";
		$qstring .= "Tracktable.Uri IS NOT NULL AND Tracktable.Hidden = 0 ".
		track_date_check($prefs['collectionrange'], $this->why)." ".
		$sflag.")";
		$qstring .= " ORDER BY ";
		if ($prefs['sortbydate']) {
			if ($prefs['notvabydate']) {
				$qstring .= " CASE WHEN Artisttable.Artistname = 'Various Artists' THEN LOWER(Albumname) ELSE Year END,";
			} else {
				$qstring .= ' Year,';
			}
		}
		$qstring .= ' LOWER(Albumname)';
		$result = generic_sql_query($qstring, false, PDO::FETCH_ASSOC);
		foreach ($result as $album) {
			$album['why'] = $this->why;
			$album['id'] = $this->why.'album'.$album['Albumindex'].'_'.$this->who;
			$album['class'] = 'album';
			yield $album;
		}
	}

	public function output_root_list() {
		global $divtype;
		$count = 0;
		foreach ($this->root_sort_query() as $rating) {
			print artistHeader($this->why.'rating'.$rating['Rating'], '<i class="rating-icon-big icon-'.$rating['Rating'].'-stars"></i>');
			$count++;
			$divtype = ($divtype == "album1") ? "album2" : "album1";
		}
		return $count;
	}

	public function output_root_fragment($rat) {
		global $divtype;
		logger::log('SORTBY_RATING','Generating rating fragment',$this->why,'rating',$rat);
		$singleheader = $this->initial_root_insert();
		foreach ($this->root_sort_query() as $rating) {
			if ($rating['Rating'] != $rat) {
				$singleheader['type'] = 'insertAfter';
				$singleheader['where'] = $this->why.'rating'.$rating['Rating'];
			} else {
				$singleheader['html'] = artistHeader($this->why.'rating'.$rating['Rating'], '<i class="rating-icon-big icon-'.$rating['Rating'].'-stars"></i>');
				$singleheader['id'] = $this->why.'rating'.$rat;
				return $singleheader;
			}
			$divtype = ($divtype == "album1") ? "album2" : "album1";
		}
		logger::error('SORTBY_RATING','Did not find position for',$this->why,'rating',$rating);
	}

	public function output_album_list($unused = false, $do_controlheader = true) {
		logger::log("SORTBY_RATING", "Generating albums for",$this->why,$this->what,$this->who);
		$count = 0;
		if ($do_controlheader) {
			print albumControlHeader(false, $this->why, 'rating', $this->who, '<i class="rating-icon-big icon-'.$this->who.'-stars"></i>');
		}
		foreach ($this->album_sort_query($unused) as $album) {
			print albumHeader($album);
			$count++;
		}
		if ($count == 0 && $this->why != 'a') {
			noAlbumsHeader();
		}
		return $count;
	}

	public function output_album_fragment($albumindex, $unused = true, $do_controlheader = true) {
		logger::log("SORTBY_RATING", "Generating album header fragment",$this->why,'album',$albumindex,$this->who);
		$singleheader = $this->initial_album_insert($this->why);
		foreach ($this->album_sort_query($unused) as $album) {
			if ($album['Albumindex'] != $albumindex) {
				$singleheader['where'] = $this->why.'album'.$album['Albumindex'].'_'.$this->who;
				$singleheader['type'] = 'insertAfter';
			} else {
				$singleheader['html'] = albumHeader($album);
				$singleheader['id'] = $this->why.'album'.$albumindex.'_'.$this->who;
				return $singleheader;
			}
		}
	}

	public function filter_track_on_why() {
		$retval = '';
		switch ($this->why) {
			case 'a':
				// Tracks from Collection //
				$retval = "AND isSearchResult < 2 AND isAudiobook = 0";
				break;

			case 'b':
				// Tracks from Search Results //
				$retval = "AND isSearchResult > 0";
				break;

			case 'r':
				// Only Tracks with Ratings //
				$retval = "AND isSearchResult < 2 AND (LinkChecked = 0 OR LinkChecked = 2) AND r.TTindex IS NOT NULL";
				break;

			case 't':
				// Only Tracks with Tags //
				$retval = "AND isSearchResult < 2 AND (LinkChecked = 0 OR LinkChecked = 2) AND tl.TTindex IS NOT NULL";
				break;

			case 'y':
				// Only Tracks with Tags and Ratings //
				$retval = "AND isSearchResult < 2 AND (LinkChecked = 0 OR LinkChecked = 2) AND tl.TTindex IS NOT NULL AND r.TTindex IS NOT NULL";
				break;

			case 'u':
				// Only Tracks with Tags or Ratings //
				$retval = "AND isSearchResult < 2 AND (LinkChecked = 0 OR LinkChecked = 2) AND (tl.TTindex IS NOT NULL OR r.TTindex IS NOT NULL)";
				break;

			case 'z':
				// Audiobooks //
				$retval = "AND isSearchResult < 2 AND isAudiobook > 0";
				break;

			default:
				logger::error('SORTBY', 'Unknown WHY :',$this->why);
				break;
		}
		if ($this->when) {
			$retval .= " AND Rating = ".$this->when;
		}
		return $retval;
	}

	public function get_modified_root_items() {
		global $returninfo;
		$result = generic_sql_query(
			"SELECT DISTINCT Rating FROM Albumtable
			JOIN Tracktable USING (Albumindex)
			JOIN Ratingtable USING (TTindex)
			WHERE justUpdated = 1 ".$this->filter_root_on_why());
		foreach ($result as $mod) {
			$returninfo['modifiedartists'][] = $this->output_root_fragment($mod['Rating']);
		}
		for ($i = 1; $i < 6; $i++) {
			$qstring = "SELECT COUNT(TTindex) AS num FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Rating = ".$i." ".$this->filter_root_on_why();
			$count = generic_sql_query($qstring, false, null, 'num', 0);
			if ($count == 0) {
				logger::log('SORTBY_RATING', 'Rating',$i,'is empty');
				$returninfo['deletedartists'][] = $this->why.'rating'.$i;
			}
		}
	}

	public function get_modified_albums() {
		global $returninfo;
		$result = generic_sql_query('SELECT Albumindex FROM Albumtable WHERE justUpdated = 1');
		foreach ($result as $mod) {
			// Need to consider every value of rating, otherwise empty ones don't get removed
			foreach (array(1,2,3,4,5) as $rating) {
				$numtracks = $this->album_rating_trackcount($mod['Albumindex'], $rating);
				logger::log('SORTBY_RATING', $this->why.'album'.$mod['Albumindex'].'_'.$rating,'has',$numtracks,'tracks of interest');
				if ($numtracks == 0) {
					$returninfo['deletedalbums'][] = $this->why.'album'.$mod['Albumindex'].'_'.$rating;
				} else {
					$lister = new sortby_rating($this->why.'rating'.$rating);
					$r = $lister->output_album_fragment($mod['Albumindex']);
					$lister = new sortby_rating($this->why.'album'.$mod['Albumindex'].'_'.$rating);
					$r['tracklist'] = $lister->output_track_list(true);
					$returninfo['modifiedalbums'][] = $r;
				}
			}
		}
	}

	private function album_rating_trackcount($albumindex, $rating) {
		$qstring =
		"SELECT COUNT(TTindex) AS num
		FROM Tracktable JOIN Ratingtable USING (TTindex)
		WHERE Albumindex = ".$albumindex." AND Rating = ".$rating.
		" AND Hidden = 0 AND Uri IS NOT NULL AND Rating > 0 ".
		$this->filter_track_on_why();
		return generic_sql_query($qstring, false, null, 'num', 0);
	}

	public function initial_album_insert() {
		return array (
			'type' => 'insertAtStart',
			'where' => $this->why.'rating'.$this->who,
			'why' => $this->why
		);
	}

	public function albums_for_artist() {
		// Usd when adding all tracks for artist to eg the Play Queue
		// Does not filter on r,t,y, or u
		foreach ($this->album_sort_query(false) as $album) {
			yield $this->why.'album'.$album['Albumindex'].'_'.$this->who;
		}
	}

}

?>