<?php

class sortby_albumbyartist extends sortby_base {

	private $forceartistname = false;

	public function root_sort_query() {
		$sflag = $this->filter_album_on_why();
		$qstring =
		"SELECT Albumtable.*, Artisttable.Artistname
		FROM Albumtable
		JOIN Artisttable ON (Albumtable.AlbumArtistindex = Artisttable.Artistindex)
		WHERE
			Albumindex IN
			(SELECT Albumindex
			FROM Tracktable
			WHERE
			Tracktable.Albumindex = Albumtable.Albumindex
			AND
			Tracktable.Uri IS NOT NULL
			AND
			Tracktable.Hidden = 0 ";
			if ($this->who != 'root') {
				// For browse album 'All Artists Featuring'
				$qstring .= "AND Albumtable.AlbumArtistindex = ".$this->who;
			}
			$qstring .= " ".prefs::$database->track_date_check(prefs::$prefs['collectionrange'], $this->why)."
			".$sflag.")
		ORDER BY";

		foreach (prefs::$prefs['artistsatstart'] as $a) {
			$qstring .= " CASE WHEN LOWER(Artistname) = LOWER('".$a."') THEN 1 ELSE 2 END,";
		}
		if (count(prefs::$prefs['nosortprefixes']) > 0) {
			$qstring .= " (CASE ";
			foreach(prefs::$prefs['nosortprefixes'] AS $p) {
				$phpisshitsometimes = strlen($p)+2;
				$qstring .= "WHEN LOWER(Artistname) LIKE '".strtolower($p).
					" %' THEN LOWER(SUBSTR(Artistname,".$phpisshitsometimes.")) ";
			}
			$qstring .= "ELSE LOWER(Artistname) END), ";
		} else {
			$qstring .= ", LOWER(Artistname), ";
		}

		if (prefs::$prefs['sortbydate']) {
			if (prefs::$prefs['notvabydate']) {
				$qstring .= " CASE WHEN Artisttable.Artistname = 'Various Artists' THEN LOWER(Albumname) ELSE Year END,";
			} else {
				$qstring .= ' Year,';
			}
		}
		$qstring .= ' LOWER(Albumname)';
		$result = prefs::$database->generic_sql_query($qstring);
		foreach ($result as $album) {
			$album['why'] = $this->why;
			$album['id'] = $this->why.'album'.$album['Albumindex'];
			$album['class'] = 'album';
			yield $album;
		}
	}

	public function output_root_list() {
		logger::debug('SORTBY_ALBUMBYARTIST', 'Generating Album Root List');
		$count = 0;
		$current_artist = null;
		foreach ($this->root_sort_query() as $album) {
			if ($album['Artistname'] != $current_artist) {
				$current_artist = $album['Artistname'];
				print $this->artistBanner($current_artist, $album['AlbumArtistindex']);
			}
			if (!$this->forceartistname) {
				// Prevent albumHeader displaying the artist name
				$album['Artistname'] = null;
			}
			print uibits::albumHeader($album);
			$count++;
		}
		return $count;
	}

	public function output_album_list($forceartistname = false, $do_controlheader = false) {
		$this->forceartistname = $forceartistname;
		// In this sort mode this is only used by browse_album
		$this->output_root_list();
	}

	public function output_root_fragment($artistindex) {
		logger::log('SORTBY_ALBUMBYARTIST','Generating artist fragment',$this->why,'artist',$artistindex);
		$singleheader = $this->initial_root_insert();
		foreach ($this->root_sort_query() as $album) {
			if ($album['AlbumArtistindex'] != $artistindex) {
				$singleheader['type'] = 'insertAfter';
				$singleheader['where'] = $this->why.'album'.$album['Albumindex'];
			} else {
				$singleheader['html'] = $this->artistBanner($album['Artistname'], $album['AlbumArtistindex']);
				$singleheader['id'] = $this->why.'artist'.$artistindex;
				return $singleheader;
			}
		}
	}

	public function output_album_fragment($albumindex) {
		logger::log('SORTBY_ALBUMBYARTIST','Generating artist fragment',$this->why,'artist',$albumindex);
		$singleheader = $this->initial_root_insert();
		foreach ($this->root_sort_query() as $album) {
			if ($album['Albumindex'] != $albumindex) {
				$singleheader['type'] = 'insertAfter';
				$singleheader['where'] = $this->why.'album'.$album['Albumindex'];
			} else {
				$album['Artistname'] = null;
				$singleheader['html'] = uibits::albumHeader($album);
				$singleheader['id'] = $album['id'];
				// $singleheader['why'] = $this->why;
				return $singleheader;
			}
		}
	}

	public function get_modified_root_items() {
		$result = prefs::$database->generic_sql_query('SELECT DISTINCT AlbumArtistindex FROM Albumtable WHERE justUpdated = 1');
		foreach ($result as $mod) {
			$atc = $this->artist_albumcount($mod['AlbumArtistindex']);
			logger::mark("SORTBY_ALBUMBYARTIST", "  Artist",$mod['AlbumArtistindex'],"has",$atc,$this->why,"albums we need to consider");
			// Modified artists also go in as deleted - since the 'root' item in this case is an artist banner
			// and we only do inserts after album IDs, we always remove and then re-insert the banners.
			prefs::$database->returninfo['deletedartists'][] = $this->why.'artist'.$mod['AlbumArtistindex'];
			if ($atc > 0) {
				prefs::$database->returninfo['modifiedartists'][] = $this->output_root_fragment($mod['AlbumArtistindex']);
			}
		}
	}

	public function get_modified_albums() {
		$result = prefs::$database->generic_sql_query('SELECT Albumindex, AlbumArtistindex FROM Albumtable WHERE justUpdated = 1');
		foreach ($result as $mod) {
			$atc = $this->album_trackcount($mod['Albumindex']);
			logger::mark("SORTBY_ALBUMBYARTIST", "  Album",$mod['Albumindex'],"has",$atc,$this->why,"tracks we need to consider");
			if ($atc == 0) {
				prefs::$database->returninfo['deletedalbums'][] = $this->why.'album'.$mod['Albumindex'];
			} else {
				$r = $this->output_album_fragment($mod['Albumindex']);
				$lister = new sortby_albumbyartist($this->why.'album'.$mod['Albumindex']);
				$r['tracklist'] = $lister->output_track_list(true);
				prefs::$database->returninfo['modifiedalbums'][] = $r;
			}
		}
	}

	private function artistBanner($a, $i) {
		ob_start();
		uibits::ui_config_header([
			'label_text' => $a,
			'id' => $this->why.'artist'.$i,
			'icon_size' => 'smallicon'
		]);
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
 		// return '<div class="configtitle artistbanner brick brick_wide" id="'.$this->why.'artist'.$i.'"><b>'.$a.'</b></div>';
 	}
}

?>