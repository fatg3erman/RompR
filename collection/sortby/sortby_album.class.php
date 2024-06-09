<?php

class sortby_album extends sortby_base {

	public function root_sort_query() {
		$sflag = $this->filter_album_on_why();

		$qstring =
		"SELECT Albumtable.*, Artisttable.Artistname, '{$this->why}' AS why
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
			$qstring .= prefs::$database->track_domain_check(prefs::get_pref('collectiondomains'), $this->why);
			$qstring .= " ".prefs::$database->track_date_check(prefs::get_pref('collectionrange'), $this->why)."
			".$sflag.")
		ORDER BY ";
		$qstring .= $this->year_sort();
		$qstring .= $this->album_sort(true);
		$result = prefs::$database->generic_sql_query($qstring);
		foreach ($result as $album) {
			$album['why'] = $this->why;
			$album['id'] = $this->why.'album'.$album['Albumindex'];
			$album['class'] = 'album';
			yield $album;
		}
	}

	public function output_root_list() {
		logger::debug('SORTBY_ALBUM', 'Generating Album Root List');
		$count = 0;
		foreach ($this->root_sort_query() as $album) {
			print uibits::albumHeader($album);
			$count++;
		}
		return $count;
	}

	public function output_album_list($unused = false, $do_controlheader = false) {
		// In this sort mode this is only used by browse_album
		$this->output_root_list();
	}

	public function output_root_fragment($albumindex) {
		logger::log('SORTBY_ALBUM', "Generating album fragment",$this->why,'album',$this->who);
		$singleheader = $this->initial_root_insert();
		foreach ($this->root_sort_query() as $album) {
			if ($album['Albumindex'] != $albumindex) {
				$singleheader['where'] = $this->why.'album'.$album['Albumindex'];
				$singleheader['type'] = 'insertAfter';
			} else {
				$singleheader['html'] = uibits::albumHeader($album);
				$singleheader['id'] = $album['id'];
				// $singleheader['why'] = $this->why;
				return $singleheader;
			}
		}
	}

	public function get_modified_root_items() {
		$result = prefs::$database->generic_sql_query('SELECT Albumindex, AlbumArtistindex FROM Albumtable WHERE justUpdated = 1');
		foreach ($result as $mod) {
			$atc = $this->album_trackcount($mod['Albumindex']);
			logger::log("SORTBY_ALBUM", "Album",$mod['Albumindex'],"has",$atc,$this->why,"tracks we need to consider");
			if ($atc == 0) {
				prefs::$database->returninfo['deletedalbums'][] = $this->why.'album'.$mod['Albumindex'];
			} else {
				$r = $this->output_root_fragment($mod['Albumindex']);
				$lister = new sortby_album($this->why.'album'.$mod['Albumindex']);
				$r['tracklist'] = $lister->output_track_list(true);
				prefs::$database->returninfo['modifiedalbums'][] = $r;
			}
		}
	}

	public function get_modified_albums() {

	}

}

?>