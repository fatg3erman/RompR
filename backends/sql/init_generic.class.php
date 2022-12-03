<?php

class init_generic extends database {

	//
	// Functions required only at page load time. init_database extends this class
	//

	protected function initialise_statstable() {
		logger::mark("SQLITE", "No Schema Version Found - initialising table");
		$this->set_admin_value('ListVersion', '0');
		$this->set_admin_value('ArtistCount', '0');
		$this->set_admin_value('AlbumCount', '0');
		$this->set_admin_value('TrackCount', '0');
		$this->set_admin_value('TotalTime', '0');
		$this->set_admin_value('CollType', '999');
		$this->set_admin_value('SchemaVer', ROMPR_SCHEMA_VERSION);
		$this->set_admin_value('BookArtists', '0');
		$this->set_admin_value('BookAlbums', '0');
		$this->set_admin_value('BookTracks', '0');
		$this->set_admin_value('BookTime', '0');
		$this->set_admin_value('LastCache', '10');
		$this->set_admin_value('Updating', '0');
		logger::log("SQLITE", "Statstable populated");
	}

	protected function verify_schema_version() {
		$sver = $this->get_admin_value('SchemaVer', 0);
		if ($sver > ROMPR_SCHEMA_VERSION) {
			logger::warn("MYSQL", "Schema Mismatch! We are version ".ROMPR_SCHEMA_VERSION." but database is version ".$sver);
			return array(false, "Your database has version number ".$sver." but this version of rompr only handles version ".ROMPR_SCHEMA_VERSION);
		}

		if ($sver > 0 && $sver < ROMPR_MIN_SCHEMA_VERSION) {
			logger::warn("MYSQL", "Schema Mismatch! We can only upgrade from version ".ROMPR_MIN_SCHEMA_VERSION." but database is version ".$sver);
			return array(false, "Your database is too old to upgrade (schema version ".$sver."). You cannot upgrade from that version to this version. The oldest version of RompR you can upgrade from is 1.40");
		}
		return array(true, '');
	}

	protected function check_triggers() {
		if (!$this->create_update_triggers()) {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Creating Update Triggers : ".$err);
		}
		if (!$this->create_conditional_triggers()) {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Creating Conditional Triggers : ".$err);
		}
		if (!$this->create_playcount_triggers()) {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Creating Playcount Triggers : ".$err);
		}
		if (!$this->create_progress_triggers()) {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Creating Progress Triggers : ".$err);
		}
		return array(true, '');
	}

	protected function update_track_dates() {
		$manual_albums = $this->generic_sql_query("SELECT DISTINCT Albumindex, Year FROM Albumtable JOIN Tracktable USING (Albumindex) WHERE LastModified IS NULL AND Year IS NOT NULL");
		logger::mark('INIT', 'Updating TYear for',count($manual_albums),'albums');
		$this->open_transaction();
		foreach($manual_albums as $album) {
			$this->sql_prepare_query(true, null, null, null, "UPDATE Tracktable SET TYear = ? WHERE Albumindex = ?", $album['Year'], $album['Albumindex']);
			$this->check_transaction();
		}
		$this->close_transaction();
	}

	public function check_setupscreen_actions() {
		if (prefs::get_pref('spotify_mark_unplayable')) {
			logger::info('SQLINIT', 'Marking all Spotify tracks as unplayable');
			$this->generic_sql_query("UPDATE Albumtable SET AlbumUri = NULL WHERE AlbumUri LIKE 'spotify:%'", true);
			$this->generic_sql_query("UPDATE Tracktable SET LinkChecked = 0, Uri = NULL WHERE Uri LIKE 'spotify:%' AND Hidden = 0", true);
			prefs::set_pref([
				'spotify_mark_unplayable' => false,
				'linkchecker_nextrun' => strtotime('2030-01-01 00:00:00')
			]);
			logger::info('SQLINIT', 'Time is',time(),'Setting nextrun to',prefs::get_pref('linkchecker_nextrun'));
			$this->set_admin_value('ListVersion', 1);
			prefs::save();
		}
		if (prefs::get_pref('clear_update_lock')) {
			$this->set_admin_value('Updating', 0);
			prefs::set_pref(['clear_update_lock', false]);
			prefs::save();
		}
	}

}
?>