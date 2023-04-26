<?php

class sortby_artist extends sortby_base {

	public function root_sort_query() {
		$sflag = $this->filter_root_on_why();
		// This query gives us album artists only. It also makes sure we only get artists for whom we
		// have actual tracks (no album artists who appear only on the wishlist or who have only hidden tracks)
		// Using GROUP BY is faster than using SELECT DISTINCT
		// USING IN is faster than the double JOIN
		$qstring =
		"SELECT
			Artistname,
			Artistindex ";

		if ($this->why == 'b')
			$qstring .= ", Uri ";

		$qstring .= "FROM Artisttable AS a ";

		if ($this->why == 'b')
			$qstring .= "LEFT JOIN Artistbrowse USING (Artistindex) ";

		$qstring .= "WHERE
		Artistindex IN
			(SELECT AlbumArtistindex FROM Albumtable
			JOIN Tracktable USING (Albumindex)
			WHERE Uri IS NOT NULL
			AND Hidden = 0
			".prefs::$database->track_date_check(prefs::get_pref('collectionrange'), $this->why)."
			".$sflag."
			GROUP BY AlbumArtistindex) ";

		if ($this->why == 'b')
			$qstring .= "OR Uri IS NOT NULL ";

		$qstring .= "ORDER BY ";
		foreach (prefs::get_pref('artistsatstart') as $a) {
			$qstring .= "CASE WHEN a.Artistname = '".$a."' THEN 1 ELSE 2 END, ";
		}
		if (count(prefs::get_pref('nosortprefixes')) > 0) {
			$qstring .= "(CASE ";
			foreach(prefs::get_pref('nosortprefixes') AS $p) {
				$phpisshitsometimes = strlen($p)+2;
				$qstring .= "WHEN a.Artistname LIKE '".$p.
					" %' THEN LOWer(SUBSTR(a.Artistname,".$phpisshitsometimes.")) ";
			}
			$qstring .= "ELSE LOWER(a.Artistname) END)";
		} else {
			$qstring .= "LOWER(a.Artistname)";
		}
		$result = prefs::$database->generic_sql_query($qstring, false, PDO::FETCH_ASSOC);
		foreach ($result as $artist) {
			yield $artist;
		}
	}

	public function album_sort_query($force_artistname) {
		$sflag = $this->filter_album_on_why();
		$qstring =
		"SELECT Albumtable.*, Artisttable.Artistname
			FROM Albumtable
			JOIN Artisttable ON (Albumtable.AlbumArtistindex = Artisttable.Artistindex)
			WHERE ";
		$qstring .= "AlbumArtistindex = '".$this->who."' AND ";
		$qstring .= "Albumindex IN (SELECT Albumindex FROM Tracktable WHERE
				Tracktable.Albumindex = Albumtable.Albumindex AND ";
		$qstring .= "Tracktable.Uri IS NOT NULL AND Tracktable.Hidden = 0 ".
		prefs::$database->track_date_check(prefs::get_pref('collectionrange'), $this->why)." ".
		$sflag.")";
		$qstring .= " ORDER BY ";
		if (prefs::get_pref('sortbydate')) {
			if (prefs::get_pref('notvabydate')) {
				$qstring .= " CASE WHEN Artisttable.Artistname = 'Various Artists' THEN LOWER(Albumname) ELSE Year END,";
			} else {
				$qstring .= ' Year,';
			}
		}
		$qstring .= ' LOWER(Albumname)';
		$result = prefs::$database->generic_sql_query($qstring, false, PDO::FETCH_ASSOC);
		foreach ($result as $album) {
			if (!$force_artistname) {
				$album['Artistname'] = null;
			}
			$album['why'] = $this->why;
			$album['id'] = $this->why.'album'.$album['Albumindex'];
			$album['class'] = 'album';
			yield $album;
		}
	}

	public function output_root_list() {
		logger::debug('SORTBY_ARTIST', 'Generating Artist Root List');
		$count = 0;
		foreach ($this->root_sort_query() as $artist) {
			print uibits::artistHeader($this->why.$this->what.$artist['Artistindex'], $artist['Artistname']);
			$count++;
		}
		return $count;
	}

	public function output_root_fragment($artistindex) {
		logger::log('SORTBY_ARTIST','Generating artist fragment',$this->why,'artist',$artistindex);
		$singleheader = $this->initial_root_insert();
		foreach ($this->root_sort_query() as $artist) {
			if ($artist['Artistindex'] != $artistindex) {
				$singleheader['type'] = 'insertAfter';
				$singleheader['where'] = $this->why.'artist'.$artist['Artistindex'];
			} else {
				$singleheader['html'] = uibits::artistHeader($this->why.'artist'.$artist['Artistindex'], $artist['Artistname']);
				$singleheader['id'] = $this->why.'artist'.$artistindex;
				return $singleheader;
			}
		}
	}

	public function output_album_list($force_artistname = false, $do_controlheader = true) {
		logger::log("SORTBY_ARTIST", "Generating albums for",$this->why,$this->what,$this->who);
		$count = 0;
		if ($do_controlheader) {
			print uibits::albumControlHeader(false, $this->why, 'artist', $this->who, $this->getArtistName());
		}
		foreach ($this->album_sort_query($force_artistname) as $album) {
			print uibits::albumHeader($album);
			$count++;
		}
		if ($count == 0 && $this->why != 'a') {
			uibits::noAlbumsHeader();
		}
		return $count;
	}

	public function output_album_fragment($albumindex, $force_artistname = false, $do_controlheader = true) {
		logger::log("SORTBY_ARTIST", "Generating album header fragment",$this->why,'album',$albumindex);
		$singleheader = $this->initial_album_insert($this->why);
		foreach ($this->album_sort_query($force_artistname) as $album) {
			if ($album['Albumindex'] != $albumindex) {
				$singleheader['where'] = $this->why.'album'.$album['Albumindex'];
				$singleheader['type'] = 'insertAfter';
			} else {
				$singleheader['html'] = uibits::albumHeader($album);
				$singleheader['id'] = $this->why.'album'.$albumindex;
				return $singleheader;
			}
		}
	}

	public function get_modified_root_items() {
		$result = prefs::$database->generic_sql_query('SELECT DISTINCT AlbumArtistindex FROM Albumtable WHERE justUpdated = 1');
		foreach ($result as $mod) {
			$atc = $this->artist_albumcount($mod['AlbumArtistindex']);
			logger::log("SORTBY_ARTIST", "Artist",$mod['AlbumArtistindex'],"has",$atc,$this->why,"albums we need to consider");
			if ($atc == 0) {
				prefs::$database->returninfo['deletedartists'][] = $this->why.'artist'.$mod['AlbumArtistindex'];
			} else {
				prefs::$database->returninfo['modifiedartists'][] = $this->output_root_fragment($mod['AlbumArtistindex']);
			}
		}
	}

	public function get_modified_albums() {
		$result = prefs::$database->generic_sql_query('SELECT Albumindex, AlbumArtistindex FROM Albumtable WHERE justUpdated = 1');
		foreach ($result as $mod) {
			$atc = $this->album_trackcount($mod['Albumindex']);
			logger::log("SORTBY_ARTIST", "Album",$mod['Albumindex'],"has",$atc,$this->why,"tracks we need to consider");
			if ($atc == 0) {
				prefs::$database->returninfo['deletedalbums'][] = $this->why.'album'.$mod['Albumindex'];
			} else {
				$lister = new sortby_artist($this->why.'artist'.$mod['AlbumArtistindex']);
				$r = $lister->output_album_fragment($mod['Albumindex']);
				$lister = new sortby_artist($this->why.'album'.$mod['Albumindex']);
				$r['tracklist'] = $lister->output_track_list(true);
				prefs::$database->returninfo['modifiedalbums'][] = $r;
			}
		}
	}

	private function getArtistName() {
		return prefs::$database->simple_query('Artistname', 'Artisttable', 'Artistindex', $this->who,'');
	}

	public function output_artist_search_results() {

	}

}

?>
