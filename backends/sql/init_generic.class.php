<?php

class init_generic extends database {

	//
	// Functions required only at page load time. init_database extends this class
	//

	protected function update_track_dates() {
		$manual_albums = $this->generic_sql_query("
			SELECT DISTINCT Albumindex, Year FROM Albumtable JOIN Tracktable USING (Albumindex) WHERE LastModified IS NULL AND Year IS NOT NULL
		");
		logger::mark('INIT', 'Updating TYear for',count($manual_albums),'albums');
		$this->open_transaction();
		foreach($manual_albums as $album) {
			$this->sql_prepare_query(true, null, null, null, "UPDATE Tracktable SET TYear = ? WHERE Albumindex = ?", $album['Year'], $album['Albumindex']);
			$this->check_transaction();
		}
		$this->close_transaction();
	}

	protected function rejig_wishlist_tracks() {
		$this->generic_sql_query("DELETE FROM Playcounttable WHERE TTindex IN (SELECT TTindex FROM Tracktable WHERE Hidden = 1 AND Uri IS NULL)", true);
		$this->generic_sql_query("DELETE FROM Tracktable WHERE Hidden = 1 AND Uri IS NULL", true);
		$result = $this->generic_sql_query("SELECT * FROM Tracktable WHERE Uri IS NULL");
		foreach ($result as $obj) {
			if ($this->sql_prepare_query(true, null, null, null,
				"INSERT INTO
					Albumtable
					(Albumname, AlbumArtistindex, AlbumUri, Year, Searched, ImgKey, mbid, Domain, Image)
				VALUES
					(?, ?, ?, ?, ?, ?, ?, ?, ?)",
				'rompr_wishlist_'.microtime(true), $obj['Artistindex'], null, 0, 0, null, null, 'local', null)) {

				$albumindex = $this->mysqlc->lastInsertId();
				logger::log("REJIG", "    Created Album with Albumindex ".$albumindex);
				$this->generic_sql_query("UPDATE Tracktable SET Albumindex = ".$albumindex." WHERE TTindex = ".$obj['TTindex'], true);
			}
		}
	}

	protected function update_stream_images($schemaver) {
		switch ($schemaver) {
			case 43:
				$stations = $this->generic_sql_query("SELECT Stationindex, StationName, Image FROM RadioStationtable WHERE Image LIKE 'prefs/userstreams/STREAM_%'");
				foreach ($stations as $station) {
					logger::log("BACKEND", "  Updating Image For Station ".$station['StationName']);
					if (file_exists($station['Image'])) {
						logger::debug("BACKEND", "    Image is ".$station['StationName']);
						$src = get_base_url().'/'.$station['Image'];
						$albumimage = new albumImage(array('artist' => "STREAM", 'album' => $station['StationName'], 'source' => $src));
						if ($albumimage->download_image()) {
							// Can't call $albumimage->update_image_database because the functions that requires are in the backend
							$images = $albumimage->get_images();
							$this->sql_prepare_query(true, null, null, null, "UPDATE RadioStationtable SET Image = ? WHERE StationName = ?",$images['small'],$station['StationName']);
							$this->sql_prepare_query(true, null, null, null, "UPDATE WishlistSourcetable SET Image = ? WHERE Image = ?",$images['small'],$station['Image']);
							unlink($station['Image']);
						} else {
							logger::warn("BACKEND", "  Image Upgrade Failed!");
						}
					} else {
						$this->generic_sql_query("UPDATE RadioStationtable SET IMAGE = NULL WHERE Stationindex = ".$station['Stationindex']);
					}
				}
				break;
		}
	}

	protected function update_remote_image_urls() {
		logger::log('SQL', 'Updating Remote Images in Albumtable');
		$albums = $this->generic_sql_query("SELECT Albumindex, Image FROM Albumtable WHERE Image LIKE 'getRemoteImage%'");
		foreach ($albums as $album) {
			logger::log('SQL', '  Albumindex',$album['Albumindex'],'Image',$album['Image']);
			$newurl = get_encoded_image($album['Image']);
			$this->sql_prepare_query(true, null, null, null,
				"UPDATE Albumtable SET Image = ? WHERE Albumindex = ?",
				$newurl,
				$album['Albumindex']
			);
		}

		logger::log('SQL', 'Updating Remote Images in Podcasttable');
		$albums = $this->generic_sql_query("SELECT PODindex, Image FROM Podcasttable WHERE Image LIKE 'getRemoteImage%'");
		foreach ($albums as $album) {
			logger::log('SQL', '  PODindex',$album['PODindex'],'Image',$album['Image']);
			$newurl = get_encoded_image($album['Image']);
			$this->sql_prepare_query(true, null, null, null,
				"UPDATE Podcasttable SET Image = ? WHERE PODindex = ?",
				$newurl,
				$album['PODindex']
			);
		}

		logger::log('SQL', 'Updating Remote Images in RadioStationtable');
		$albums = $this->generic_sql_query("SELECT Stationindex, Image FROM RadioStationtable WHERE Image LIKE 'getRemoteImage%'");
		foreach ($albums as $album) {
			logger::log('SQL', '  Stationindex',$album['Stationindex'],'Image',$album['Image']);
			$newurl = get_encoded_image($album['Image']);
			$this->sql_prepare_query(true, null, null, null,
				"UPDATE RadioStationtable SET Image = ? WHERE Stationindex = ?",
				$newurl,
				$album['Stationindex']
			);
		}

		logger::log('SQL', 'Updating Remote Images in WishlistSourcetable');
		$albums = $this->generic_sql_query("SELECT Sourceindex, SourceImage FROM WishlistSourcetable WHERE SourceImage LIKE 'getRemoteImage%'");
		foreach ($albums as $album) {
			logger::log('SQL', '  Sourceindex',$album['Sourceindex'],'Image',$album['SourceImage']);
			$newurl = get_encoded_image($album['SourceImage']);
			$this->sql_prepare_query(true, null, null, null,
				"UPDATE WishlistSourcetable SET SourceImage = ? WHERE Sourceindex = ?",
				$newurl,
				$album['Sourceindex']
			);
		}
	}

	public function albumImageBuggery() {
		// This was used to update album art to a new format but it's really old now and we've totally refactored the album image code
		// In the eventuality that someone is still using a version that old we'll keep the function but just use it to remove all album art
		// and start again.
		rrmdir('albumart/small');
		rrmdir('albumart/asdownloaded');
		mkdir('albumart/small', 0755);
		mkdir('albumart/asdownloaded', 0755);
		$this->generic_sql_query("UPDATE Albumtable SET Searched = 0, Image = ''");
	}

	public function check_setupscreen_actions() {
		if (prefs::$prefs['spotify_mark_unplayable']) {
			logger::log('SQLINIT', 'Marking all Spotify tracks as unplayable');
			$this->generic_sql_query("UPDATE Tracktable SET LinkChecked = 3 WHERE Uri LIKE 'spotify:%' AND Hidden = 0");
			prefs::$prefs['spotify_mark_unplayable'] = false;
			prefs::$prefs['linkchecker_nextrun'] = strtotime('2040-01-01');
			logger::log('SQLINIT', 'Time is',time(),'Setting nextrun to',prefs::$prefs['linkchecker_nextrun']);
			prefs::save();
		}
	}

}
?>