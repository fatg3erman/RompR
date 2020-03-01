<?php

require_once('collection/sortby/base.php');

class sortby_genre extends sortby_base {

	public function root_sort_query() {
		global $prefs;
		$sflag = $this->filter_root_on_why();
		// This query gives us album artists only. It also makes sure we only get artists for whom we
		// have actual tracks (no album artists who appear only on the wishlist or who have only hidden tracks)
		// Using GROUP BY is faster than using SELECT DISTINCT
		// USING IN is faster than the double JOIN
		$qstring =
		"SELECT Genre, Genreindex
			FROM Genretable AS g
			WHERE
			Genreindex IN
				(SELECT Genreindex FROM Tracktable
				WHERE Uri IS NOT NULL
				AND Hidden = 0
				".track_date_check($prefs['collectionrange'], $this->why)."
				".$sflag."
				)
			ORDER BY Genre ASC";
		$result = generic_sql_query($qstring, false, PDO::FETCH_ASSOC);
		foreach ($result as $genre) {
			yield $genre;
		}
	}

	public function album_sort_query($force_artistname) {
		global $prefs;
		$sflag = $this->filter_album_on_why();

		$qstring =
		"SELECT Albumtable.*, Artisttable.Artistname
			FROM Albumtable
			JOIN Artisttable ON (Albumtable.AlbumArtistindex = Artisttable.Artistindex)
			WHERE Albumindex IN
			(SELECT DISTINCT Albumindex FROM Tracktable WHERE
				Tracktable.Albumindex = Albumtable.Albumindex AND
			    Tracktable.Uri IS NOT NULL AND Tracktable.Hidden = 0
			    AND Tracktable.Genreindex = ".$this->who." ".
			track_date_check($prefs['collectionrange'], $this->why)." ".
			$sflag.") ORDER BY";
		if ($prefs['sortbydate']) {
			$qstring .= ' Year,';
		}
		$qstring .= ' LOWER(Albumname)';
		$result = generic_sql_query($qstring, false, PDO::FETCH_ASSOC);
		foreach ($result as $album) {
			$album['why'] = $this->why;
			$album['id'] = $this->why.'album'.$album['Albumindex'];
			$album['class'] = 'album';
			yield $album;
		}
	}

	public function output_root_list() {
		logger::debug('SORTBY_GENRE', 'Generating Artist Root List');
		global $divtype;
		$count = 0;
		foreach ($this->root_sort_query() as $genre) {
			print artistHeader($this->why.$this->what.$genre['Genreindex'], $genre['Genre']);
			$count++;
			$divtype = ($divtype == "album1") ? "album2" : "album1";
		}
		return $count;
	}

	public function output_root_fragment($genreindex) {
		global $divtype;
		logger::log('SORTBY_GENRE','Generating genre fragment',$this->why,'genre',$genreindex);
		$singleheader = $this->initial_root_insert();
		foreach ($this->root_sort_query() as $genre) {
			if ($genre['Genreindex'] != $genreindex) {
				$singleheader['type'] = 'insertAfter';
				$singleheader['where'] = $this->why.'genre'.$genre['Genreindex'];
			} else {
				$singleheader['html'] = artistHeader($this->why.'genre'.$genre['Genreindex'], $genre['Genre']);
				$singleheader['id'] = $this->why.'genre'.$genreindex;
				return $singleheader;
			}
			$divtype = ($divtype == "album1") ? "album2" : "album1";
		}
	}

	public function output_album_list($force_artistname = true, $do_controlheader = true) {
		logger::log("SORTBY_GENRE", "Generating albums for",$this->why,$this->what,$this->who);
		$count = 0;
		if ($do_controlheader) {
			print albumControlHeader(false, $this->why, 'genre', $this->who, $this->getGenreName());
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

	public function output_album_fragment($albumindex, $force_artistname = true, $do_controlheader = true) {
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
		$result = generic_sql_query('SELECT DISTINCT Genreindex FROM Albumtable JOIN Tracktable USING (Albumindex) WHERE justUpdated = 1');
		foreach ($result as $mod) {
			$atc = $this->genre_albumcount($mod['Genreindex']);
			logger::mark("SORTBY_ARTIST", "  Genre",$mod['Genreindex'],"has",$atc,$this->why,"albums we need to consider");
			if ($atc == 0) {
				$returninfo['deletedartists'][] = $this->why.'genre'.$mod['Genreindex'];
			} else {
				$returninfo['modifiedartists'][] = $this->output_root_fragment($mod['Genreindex']);
			}
		}
	}

	public function get_modified_albums() {
		global $returninfo;
		$result = generic_sql_query('SELECT DISTINCT Albumindex, Genreindex FROM Albumtable JOIN Tracktable USING (Albumindex) WHERE justUpdated = 1');
		foreach ($result as $mod) {
			$atc = $this->album_genre_trackcount($mod['Albumindex'], $mod['Genreindex']);
			logger::mark("SORTBY_ARTIST", "  Album",$mod['Albumindex'],"has",$atc,$this->why,"tracks we need to consider");
			if ($atc == 0) {
				$returninfo['deletedalbums'][] = $this->why.'album'.$mod['Albumindex'];
			} else {
				$lister = new sortby_genre($this->why.'genre'.$mod['Genreindex']);
				$r = $lister->output_album_fragment($mod['Albumindex']);
				$lister = new sortby_genre($this->why.'album'.$mod['Albumindex']);
				$r['tracklist'] = $lister->output_track_list(true);
				$returninfo['modifiedalbums'][] = $r;
			}
		}
	}

	private function getGenreName() {
		return simple_query('Genre', 'Genretable', 'Genreindex', $this->who,'');
	}

	public function initial_album_insert() {
		return array (
			'type' => 'insertAtStart',
			'where' => $this->why.'genre'.$this->who,
			'why' => $this->why
		);
	}

	private function genre_albumcount($genreindex) {
		$qstring =
		"SELECT COUNT(Albumindex) AS num
		FROM
		Albumtable LEFT JOIN Tracktable USING (Albumindex)
		WHERE
			Genreindex = ".$genreindex.
			" AND Hidden = 0
			AND Uri IS NOT NULL ".
			$this->filter_track_on_why();
		return generic_sql_query($qstring, false, null, 'num', 0);
	}

	private function album_genre_trackcount($albumindex, $genreindex) {
		$qstring =
		"SELECT
			COUNT(TTindex) AS num
		FROM
			Tracktable
		WHERE
			Albumindex = ".$albumindex.
			" AND Genreindex = ".$genreindex.
			" AND Hidden = 0
			AND Uri IS NOT NULL ".
			$this->filter_track_on_why();
		return generic_sql_query($qstring, false, null, 'num', 0);
	}

}

?>