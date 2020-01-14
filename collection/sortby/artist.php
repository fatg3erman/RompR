<?php

require_once('collection/sortby/base.php');

class sortby_artist extends sortby_base {

	public function root_sort_query() {
		global $prefs;
		$sflag = $this->filter_root_on_why();
		// This query gives us album artists only. It also makes sure we only get artists for whom we
		// have actual tracks (no album artists who appear only on the wishlist or who have only hidden tracks)
		// Using GROUP BY is faster than using SELECT DISTINCT
		// USING IN is faster than the double JOIN
		$qstring =
		"SELECT Artistname, Artistindex
			FROM Artisttable AS a
			WHERE
			Artistindex IN
				(SELECT AlbumArtistindex FROM Albumtable
				JOIN Tracktable USING (Albumindex)
				WHERE Uri IS NOT NULL
				AND Hidden = 0
				".track_date_check($prefs['collectionrange'], $this->why)."
				".$sflag."
				GROUP BY AlbumArtistindex)
			ORDER BY ";
		foreach ($prefs['artistsatstart'] as $a) {
			$qstring .= "CASE WHEN LOWER(a.Artistname) = LOWER('".$a."') THEN 1 ELSE 2 END, ";
		}
		if (count($prefs['nosortprefixes']) > 0) {
			$qstring .= "(CASE ";
			foreach($prefs['nosortprefixes'] AS $p) {
				$phpisshitsometimes = strlen($p)+2;
				$qstring .= "WHEN LOWER(a.Artistname) LIKE '".strtolower($p).
					" %' THEN LOWER(SUBSTR(a.Artistname,".$phpisshitsometimes.")) ";
			}
			$qstring .= "ELSE LOWER(a.Artistname) END)";
		} else {
			$qstring .= "LOWER(a.Artistname)";
		}
		$result = generic_sql_query($qstring, false, PDO::FETCH_ASSOC);
		foreach ($result as $artist) {
			yield $artist;
		}
	}

	public function album_sort_query($force_artistname) {
		global $prefs;
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
		global $divtype;
		$count = 0;
		foreach ($this->root_sort_query() as $artist) {
			print artistHeader($this->why.$this->what.$artist['Artistindex'], $artist['Artistname']);
			$count++;
			$divtype = ($divtype == "album1") ? "album2" : "album1";
		}
		return $count;
	}

	public function output_root_fragment($artistindex) {
		global $divtype;
		logger::log('SORTBY_ARTIST','Generating artist fragment',$this->why,'artist',$artistindex);
		$singleheader = $this->initial_root_insert();
		foreach ($this->root_sort_query() as $artist) {
			if ($artist['Artistindex'] != $artistindex) {
				$singleheader['type'] = 'insertAfter';
				$singleheader['where'] = $this->why.'artist'.$artist['Artistindex'];
			} else {
				$singleheader['html'] = artistHeader($this->why.'artist'.$artist['Artistindex'], $artist['Artistname']);
				$singleheader['id'] = $this->why.'artist'.$artistindex;
				return $singleheader;
			}
			$divtype = ($divtype == "album1") ? "album2" : "album1";
		}
	}

	public function output_album_list($force_artistname = false, $do_controlheader = true) {
		logger::log("SORTBY_ARTIST", "Generating albums for",$this->why,$this->what,$this->who);
		$count = 0;
		if ($do_controlheader) {
			print albumControlHeader(false, $this->why, 'artist', $this->who, $this->getArtistName());
		}
		foreach ($this->album_sort_query($force_artistname) as $album) {
			print albumHeader($album);
			$count++;
		}
		if ($count == 0 && $this->why != 'a') {
			noAlbumsHeader();
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
				$singleheader['html'] = albumHeader($album);
				$singleheader['id'] = $this->why.'album'.$albumindex;
				return $singleheader;
			}
		}
	}

	public function get_modified_root_items() {
		global $returninfo;
		$result = generic_sql_query('SELECT DISTINCT AlbumArtistindex FROM Albumtable WHERE justUpdated = 1');
		foreach ($result as $mod) {
			$atc = $this->artist_albumcount($mod['AlbumArtistindex']);
			logger::mark("SORTBY_ARTIST", "  Artist",$mod['AlbumArtistindex'],"has",$atc,$this->why,"albums we need to consider");
			if ($atc == 0) {
				$returninfo['deletedartists'][] = $this->why.'artist'.$mod['AlbumArtistindex'];
			} else {
				$returninfo['modifiedartists'][] = $this->output_root_fragment($mod['AlbumArtistindex']);
			}
		}
	}

	public function get_modified_albums() {
		global $returninfo;
		$result = generic_sql_query('SELECT Albumindex, AlbumArtistindex FROM Albumtable WHERE justUpdated = 1');
		foreach ($result as $mod) {
			$atc = $this->album_trackcount($mod['Albumindex']);
			logger::mark("SORTBY_ARTIST", "  Album",$mod['Albumindex'],"has",$atc,$this->why,"tracks we need to consider");
			if ($atc == 0) {
				$returninfo['deletedalbums'][] = $this->why.'album'.$mod['Albumindex'];
			} else {
				$lister = new sortby_artist($this->why.'artist'.$mod['AlbumArtistindex']);
				$r = $lister->output_album_fragment($mod['Albumindex']);
				$lister = new sortby_artist($this->why.'album'.$mod['Albumindex']);
				$r['tracklist'] = $lister->output_track_list(true);
				$returninfo['modifiedalbums'][] = $r;
			}
		}
	}

	private function getArtistName() {
		return simple_query('Artistname', 'Artisttable', 'Artistindex', $this->who,'');
	}

}

?>