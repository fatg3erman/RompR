<?php

class init_database extends init_generic {

	public function check_sql_tables() {

		//
		// Check MySQL / MariaDB Version
		//

		$mysql_version = $this->generic_sql_query("SELECT VERSION() AS v", false, null, 'v', 0);
		prefs::set_pref(['mysql_version' => $mysql_version]);
		if (strpos($mysql_version, 'MariaDB') !== false) {
			if (version_compare($mysql_version, ROMPR_MIN_MARIADB_VERSION, '<')) {
				logger::warn('MYSQL', 'Running old version of MariaDB',$mysql_version,'- will have to use old style update query. Min version is',ROMPR_MIN_MARIADB_VERSION);
				prefs::set_pref(['old_style_sql' => true]);
			} else {
				logger::info('MYSQL', 'MariaDB version',$mysql_version,'is greater than',ROMPR_MIN_MARIADB_VERSION);
				prefs::set_pref(['old_style_sql' => false]);
			}
		} else {
			if (version_compare($mysql_version, ROMPR_MIN_MYSQL_VERSION, '<')) {
				logger::warn('MYSQL', 'Running old version of MySQL',$mysql_version,'- will have to use old style update query. Min version is',ROMPR_MIN_MYSQL_VERSION);
				prefs::set_pref(['old_style_sql' => true]);
			} else {
				logger::info('MYSQL', 'MySQL version',$mysql_version,'is greater than',ROMPR_MIN_MYSQL_VERSION);
				prefs::set_pref(['old_style_sql' => false]);
			}
		}

		//
		// Create Statstable so we know it's there when we try to check the Schema Version
		//

		if (!$this->generic_sql_query("CREATE TABLE IF NOT EXISTS Statstable(Item CHAR(11), PRIMARY KEY(Item), Value INT UNSIGNED) ENGINE=InnoDB", true)) {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Statstable : ".$err);
		}

		//
		// Verify the Scema version - is it newver than we support or is it too old to upgrade
		//

		list($ok, $message) = $this->verify_schema_version();
		if (!$ok) {
			return array($ok, $message);
		}

		//
		// Create all the database tables if they don't already exist
		//

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Tracktable(
			TTindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			PRIMARY KEY(TTindex),
			Title VARCHAR(255),
			Albumindex INT UNSIGNED,
			TrackNo SMALLINT UNSIGNED,
			Duration INT UNSIGNED,
			Artistindex INT UNSIGNED,
			Disc TINYINT(3) UNSIGNED,
			Uri VARCHAR(2000),
			LastModified CHAR(32),
			Hidden TINYINT(1) UNSIGNED DEFAULT 0,
			DateAdded TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			isSearchResult TINYINT(1) UNSIGNED DEFAULT 0,
			justAdded TINYINT(1) UNSIGNED DEFAULT 1,
			Sourceindex INT UNSIGNED DEFAULT NULL,
			LinkChecked TINYINT(1) UNSIGNED DEFAULT 0,
			isAudiobook TINYINT(1) UNSIGNED DEFAULT 0,
			usedInPlaylist TINYINT(1) UNSIGNED DEFAULT 0,
			Genreindex INT UNSIGNED DEFAULT 0,
			TYear YEAR,
			UNIQUE INDEX(Albumindex, Artistindex, TrackNo, Disc, Title),
			INDEX(Uri (768))) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  Tracktable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Tracktable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Albumtable(
			Albumindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			PRIMARY KEY(Albumindex),
			Albumname VARCHAR(255),
			AlbumArtistindex INT UNSIGNED,
			AlbumUri VARCHAR(255),
			Year YEAR,
			Searched TINYINT(1) UNSIGNED,
			ImgKey CHAR(32),
			mbid CHAR(40),
			ImgVersion INT UNSIGNED DEFAULT {$this->get_constant('ROMPR_IMAGE_VERSION')},
			Domain CHAR(32),
			Image VARCHAR(255),
			randomSort INT DEFAULT 0,
			justUpdated TINYINT(1) UNSIGNED DEFAULT 1,
			useTrackIms TINYINT(1) DEFAULT 0,
			INDEX(Albumname),
			INDEX(AlbumArtistindex),
			INDEX(Domain),
			INDEX(ImgKey)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  Albumtable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Albumtable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Artisttable(
			Artistindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			PRIMARY KEY(Artistindex),
			Artistname VARCHAR(255),
			INDEX(Artistname)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  Artisttable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Artisttable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Ratingtable(
			TTindex INT UNSIGNED NOT NULL,
			PRIMARY KEY(TTindex),
			Rating TINYINT(1) UNSIGNED,
			CONSTRAINT fk_rating FOREIGN KEY (TTindex) REFERENCES Tracktable (TTindex) ON DELETE CASCADE) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  Ratingtable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Ratingtable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Bookmarktable(
			TTindex INT UNSIGNED NOT NULL,
			Bookmark INT UNSIGNED,
			Name VARCHAR(128) NOT NULL,
			CONSTRAINT fk_bookmark FOREIGN KEY (TTindex) REFERENCES Tracktable (TTindex) ON DELETE CASCADE
			PRIMARY KEY (TTindex, Name)) ENGINE=InnoDB", true))
		{
			logger::log("SQLITE", "  Bookmarktable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Bookmarktable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Tagtable(
			Tagindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			PRIMARY KEY(Tagindex),
			Name VARCHAR(255)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  Tagtable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Tagtable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS TagListtable(
			Tagindex INT UNSIGNED NOT NULL,
			TTindex INT UNSIGNED NOT NULL,
			CONSTRAINT fk_taglist_tag FOREIGN KEY (Tagindex) REFERENCES Tagtable (Tagindex) ON DELETE CASCADE,
			CONSTRAINT fk_taglist_track FOREIGN KEY (TTindex) REFERENCES Tracktable (TTindex) ON DELETE CASCADE,
			PRIMARY KEY (Tagindex, TTindex)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  TagListtable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking TagListtable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Playcounttable(
			TTindex INT UNSIGNED NOT NULL,
			Playcount INT UNSIGNED NOT NULL,
			SyncCount INT UNSIGNED DEFAULT 0,
			LastPlayed TIMESTAMP,
			CONSTRAINT fk_playcount FOREIGN KEY (TTindex) REFERENCES Tracktable (TTindex) ON DELETE CASCADE,
			PRIMARY KEY (TTindex)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  Playcounttable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Playcounttable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Podcasttable(
			PODindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			FeedURL TEXT,
			Image VARCHAR(255),
			Title VARCHAR(255),
			Artist VARCHAR(255),
			RefreshOption TINYINT(2) UNSIGNED DEFAULT 0,
			SortMode TINYINT(2) UNSIGNED DEFAULT 0,
			HideDescriptions TINYINT(1) UNSIGNED DEFAULT 0,
			DisplayMode TINYINT(2) UNSIGNED DEFAULT 0,
			DaysToKeep INT UNSIGNED DEFAULT 0,
			NumToKeep INT UNSIGNED DEFAULT 0,
			KeepDownloaded TINYINT(1) UNSIGNED DEFAULT 0,
			AutoDownload TINYINT(1) UNSIGNED DEFAULT 0,
			DaysLive INT,
			Version TINYINT(2),
			Subscribed TINYINT(1) NOT NULL DEFAULT 1,
			Description TEXT,
			LastPubDate INT UNSIGNED DEFAULT NULL,
			NextUpdate INT UNSIGNED DEFAULT 0,
			Category VARCHAR(255) NOT NULL,
			WriteTags TINYINT(1) DEFAULT 0,
			UpRetry INT UNSIGNED DEFAULT 0,
			PRIMARY KEY (PODindex)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  Podcasttable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Podcasttable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS PodcastTracktable(
			PODTrackindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			JustUpdated TINYINT(1),
			PODindex INT UNSIGNED,
			Title VARCHAR(255),
			Artist VARCHAR(255),
			Duration INT UNSIGNED,
			PubDate INT,
			FileSize INT UNSIGNED,
			Description TEXT,
			Link TEXT,
			Guid TEXT,
			Localfilename VARCHAR(255),
			Downloaded TINYINT(1) UNSIGNED DEFAULT 0,
			Listened TINYINT(1) UNSIGNED DEFAULT 0,
			New TINYINT(1) UNSIGNED DEFAULT 1,
			Deleted TINYINT(1) UNSIGNED DEFAULT 0,
			Image VARCHAR(255) DEFAULT NULL,
			INDEX (PODindex),
			PRIMARY KEY (PODTrackindex),
			INDEX (Title)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  PodcastTracktable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking PodcastTracktable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS PodBookmarktable(
			PODTrackindex INT UNSIGNED NOT NULL,
			Bookmark INT UNSIGNED,
			Name VARCHAR(128) NOT NULL,
			CONSTRAINT fk_podbookmark FOREIGN KEY (PODTrackindex) REFERENCES PodcastTracktable (PODTrackindex) ON DELETE CASCADE,
			PRIMARY KEY (PODTrackIndex, Name)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  PodBookmarktable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking PodBookmarktable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS RadioStationtable(
			Stationindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			Number SMALLINT UNSIGNED DEFAULT 65535,
			IsFave TINYINT(1),
			StationName VARCHAR(255),
			PlaylistUrl TEXT,
			Image VARCHAR(255),
			PRIMARY KEY (Stationindex)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  RadioStationtable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking RadioStationtable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS RadioTracktable(
			Trackindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			Stationindex INT UNSIGNED,
			TrackUri TEXT,
			PrettyStream TEXT,
			CONSTRAINT fk_radiotrack FOREIGN KEY (Stationindex) REFERENCES RadioStationtable (Stationindex) ON DELETE CASCADE,
			PRIMARY KEY (Trackindex)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  RadioTracktable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking RadioTracktable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS WishlistSourcetable(
			Sourceindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			SourceName VARCHAR(255),
			SourceImage VARCHAR(255),
			SourceUri TEXT,
			PRIMARY KEY (Sourceindex)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  WishlistSourcetable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking WishlistSourcetable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS AlbumsToListenTotable(
			Listenindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			JsonData TEXT,
			PRIMARY KEY (Listenindex)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  AlbumsToListenTotabletable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking AlbumsToListenTotable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS BackgroundImageTable(
			BgImageIndex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			Skin VARCHAR(255),
			BrowserID VARCHAR(20) DEFAULT NULL,
			Filename VARCHAR(255),
			Orientation TINYINT(2),
			Used TINYINT(1) DEFAULT 0,
			PRIMARY KEY (BgImageIndex),
			INDEX (Skin),
			INDEX (BrowserID)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  BackgounrdImageTable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking BackgroundImageTable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Genretable(
			Genreindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			Genre VARCHAR(40),
			PRIMARY KEY (Genreindex),
			INDEX (Genre)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  Genretable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Genretable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Artistbrowse(
			Artistindex INT UNSIGNED NOT NULL UNIQUE,
			Uri VARCHAR(255),
			PRIMARY KEY (Artistindex)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  Artistbrowse OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Artistbrowse : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Sleeptimers(
			Pid INT UNSIGNED DEFAULT NULL,
			Player VARCHAR(50) NOT NULL,
			TimeSet INT UNSIGNED NOT NULL,
			SleepTime INT UNSIGNED NOT NULL) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  Sleeptimers OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Sleeptimers : ".$err);
		}

		// Pid will be NULL if alarm is not enabled
		// MySQL can't have a column called Repeat!!!!
		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Alarms(
			Alarmindex INT UNSIGNED PRIMARY KEY AUTO_INCREMENT NOT NULL,
			Pid INT UNSIGNED DEFAULT NULL,
			SnoozePid INT UNSIGNED DEFAULT NULL,
			Player VARCHAR(50) NOT NULL,
			Running TINYINT(1) UNSIGNED DEFAULT 0,
			Interrupt TINYINT(1) DEFAULT 0,
			Rpt TINYINT(1) UNSIGNED DEFAULT 0,
			Ramp TINYINT(1) UNSIGNED DEFAULT 0,
			Stopafter TINYINT(1) UNSIGNED DEFAULT 0,
			StopMins INT UNSIGNED DEFAULT 60,
			Time CHAR(5),
			Days VARCHAR(100) NOT NULL,
			Name VARCHAR(255),
			PlayItem TINYINT(1) UNSIGNED DEFAULT 0,
			ItemToPlay TEXT NOT NULL,
			PlayCommands TEXT NOT NULL) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  Alarms OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Alarms : ".$err);
		}

		//
		// Check that all the required triggers exist
		//

		list($ok, $message) = $this->check_triggers();
		if (!$ok)
			return array($ok, $message);

		//
		// Check to see if the Statstable is populated, and populate it if not
		//

		$sv = $this->get_admin_value('SchemaVer', 0);
		if ($sv == 0) {
			$this->initialise_statstable();
			$sv = ROMPR_SCHEMA_VERSION;
		}

		//
		// Upgrade step-by-step from older schema versions.
		// We only support upgrading from version 63 (rompr 1.40) because that's now 2 years old
		// and this code was getting huge.
		//

		while ($sv < ROMPR_SCHEMA_VERSION) {
			switch ($sv) {
				case 63:
					logger::log("SQL", "Updating FROM Schema version 63 TO Schema version 64");
					// $this->generic_sql_query("INSERT INTO Genretable (Genre) VALUES ('None')", true);
					$this->generic_sql_query("ALTER TABLE Tracktable ADD Genreindex INT UNSIGNED DEFAULT 0", true);
					$this->set_admin_value('SchemaVer', 64);
					break;

				case 64:
					logger::log("SQL", "Updating FROM Schema version 64 TO Schema version 65");
					$this->generic_sql_query("ALTER TABLE Tracktable ADD TYear YEAR", true);
					$this->set_admin_value('SchemaVer', 65);
					break;

				case 65:
					logger::log("SQL", "Updating FROM Schema version 65 TO Schema version 66");
					$this->update_track_dates();
					$this->set_admin_value('SchemaVer', 66);
					break;

				case 66:
					logger::log("SQL", "Updating FROM Schema version 66 TO Schema version 67");
					$this->generic_sql_query("ALTER TABLE Podcasttable ADD WriteTags TINYINT(1) DEFAULT 0", true);
					$this->set_admin_value('SchemaVer', 67);
					break;

				case 67:
					logger::log("SQL", "Updating FROM Schema version 67 TO Schema version 68");
					prefs::upgrade_host_defs(68);
					$this->set_admin_value('SchemaVer', 68);
					break;

				case 68:
					logger::log("SQL", "Updating FROM Schema version 68 TO Schema version 69");
					prefs::upgrade_host_defs(69);
					$this->set_admin_value('SchemaVer', 69);
					break;

				case 69:
					logger::log("SQL", "Updating FROM Schema version 69 TO Schema version 70");

					//
					// First check that there are no duplicate entires in the Tracktable that would prevent the
					// new unique key from being created and correct them if there are
					//

					$duplicates = $this->generic_sql_query(
						"SELECT GROUP_CONCAT(TTindex SEPARATOR ',') AS ttids, Albumindex, Artistindex, TrackNo, Disc, Title
						FROM Tracktable
						GROUP BY Albumindex, Artistindex, TrackNo, Disc, Title
						HAVING COUNT(*) > 1"
					);
					if (count($duplicates) > 0) {
						logger::warn('MYSQL', 'Duplicates found. Attempting to correct');
						foreach ($duplicates as $duplicate) {
							$disc = $duplicate['Disc'];
							$ttids = explode(',', $duplicate['ttids']);
							foreach ($ttids as $ttid) {
								logger::log('MYSQL', 'Setting Disc to',$disc,'on TTindex',$ttid);
								$this->sql_prepare_query(true, null, null, null,
									"UPDATE Tracktable SET Disc = ? WHERE TTindex = ?",
									$disc, $ttid
								);
								$disc++;
							}
						}
					}

					$this->generic_sql_query("DROP INDEX Albumindex ON Tracktable", true);
					$this->generic_sql_query("DROP INDEX Title ON Tracktable", true);
					$this->generic_sql_query("DROP INDEX TrackNo ON Tracktable", true);
					if (!$this->generic_sql_query("CREATE UNIQUE INDEX trackfinder ON Tracktable (Albumindex, Artistindex, TrackNo, Disc, Title)", true)) {
						$err = $this->mysqlc->errorInfo()[2];
						return array(false, "Error Creating Tracktable Index : ".$err);
					}
					$this->set_admin_value('SchemaVer', 70);
					break;

				case 70:
					logger::log("SQL", "Updating FROM Schema version 70 TO Schema version 71");
					$index = $this->simple_query('Genreindex', 'Genretable', 'Genre', 'None', null);
					if ($index === null) {
						$this->sql_prepare_query(true, null, null, null, 'INSERT INTO Genretable (Genre) VALUES(?)', 'None');
						$index = $this->mysqlc->lastInsertId();
					}
					$this->sql_prepare_query(true, null, null, null,
						"UPDATE Tracktable SET Genreindex = ? WHERE Genreindex NOT IN (SELECT DISTINCT Genreindex FROM Genretable)",
						$index
					);
					$this->set_admin_value('SchemaVer', 71);
					break;

				case 71:
					logger::log("SQL", "Updating FROM Schema version 71 TO Schema version 72");
					$this->generic_sql_query("DROP TRIGGER IF EXISTS track_update_trigger", true);
					if (!$this->create_conditional_triggers()) {
						$err = $this->mysqlc->errorInfo()[2];
						return array(false, "Error While Creating Triggers : ".$err);
					}
					$this->set_admin_value('SchemaVer', 72);
					break;

				case 72:
					logger::log("SQL", "Updating FROM Schema version 72 TO Schema version 73");
					$this->generic_sql_query("CREATE INDEX track_finder_index ON Tracktable (Uri(768))", true);
					$this->set_admin_value('SchemaVer', 73);
					break;

				case 73:
					logger::log("SQL", "Updating FROM Schema version 73 TO Schema version 74");
					$this->generic_sql_query("ALTER TABLE Albumtable ADD useTrackIms TINYINT(1) DEFAULT 0", true);
					$this->set_admin_value('SchemaVer', 74);
					break;

				case 74:
					logger::log("SQL", "Updating FROM Schema version 74 TO Schema version 75");
					$this->set_admin_value('SchemaVer', 75);
					break;

				case 75:
					logger::log("SQL", "Updating FROM Schema version 75 TO Schema version 76");
					$this->generic_sql_query("DROP TRIGGER IF EXISTS track_update_trigger", true);
					if (!$this->create_conditional_triggers()) {
						$err = $this->mysqlc->errorInfo()[2];
						return array(false, "Error While Creating Triggers : ".$err);
					}
					$this->set_admin_value('SchemaVer', 76);
					break;

				case 76:
					logger::log("SQL", "Updating FROM Schema version 76 TO Schema version 77");
					$this->generic_sql_query("ALTER TABLE BackgroundImageTable ADD Used TINYINT(1) DEFAULT 0", true);
					$this->set_admin_value('SchemaVer', 77);
					break;

				case 77:
					logger::log("SQL", "Updating FROM Schema version 77 TO Schema version 78");
					logger::log("SQL", "This may take a long time");
					$this->generic_sql_query("DELETE FROM Playcounttable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable)", true);
					$this->generic_sql_query("RENAME TABLE Playcounttable TO _playcounts_old", true);
					$this->generic_sql_query(
						"CREATE TABLE Playcounttable(
							TTindex INT UNSIGNED NOT NULL REFERENCES Tracktable(TTindex) ON DELETE CASCADE,
							Playcount INT UNSIGNED NOT NULL,
							SyncCount INT UNSIGNED DEFAULT 0,
							LastPlayed TIMESTAMP,
						PRIMARY KEY (TTindex)) ENGINE=InnoDB", true
					);
					$this->generic_sql_query(
						"INSERT INTO Playcounttable (TTindex, Playcount, SyncCount, LastPlayed)
						SELECT TTindex, Playcount, SyncCount, LastPlayed FROM _playcounts_old", true
					);
					$this->generic_sql_query("DROP TABLE _playcounts_old", true);
					$this->set_admin_value('SchemaVer', 78);
					break;

				case 78:
					logger::log("SQL", "Updating FROM Schema version 78 TO Schema version 79");
					logger::log("SQL", "This may take a long time");
					$this->generic_sql_query("DELETE FROM Ratingtable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable WHERE Hidden = 0)", true);
					$this->generic_sql_query("RENAME TABLE Ratingtable TO _ratings_old", true);
					$this->generic_sql_query(
						"CREATE TABLE Ratingtable(
						TTindex INT UNSIGNED NOT NULL REFERENCES Tracktable(TTindex) ON DELETE CASCADE,
						PRIMARY KEY(TTindex),
						Rating TINYINT(1) UNSIGNED) ENGINE=InnoDB", true
					);
					$this->generic_sql_query(
						"INSERT INTO Ratingtable(TTindex, Rating)
						SELECT TTindex, Rating FROM _ratings_old", true
					);
					$this->generic_sql_query("DROP TABLE _ratings_old", true);
					$this->set_admin_value('SchemaVer', 79);
					break;

				case 79:
					logger::log("SQL", "Updating FROM Schema version 79 TO Schema version 80");
					logger::log("SQL", "This may take a long time");
					$this->generic_sql_query("DELETE FROM TagListtable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable WHERE Hidden = 0)", true);
					$this->generic_sql_query("RENAME TABLE TagListtable TO _taglist_old", true);
					$this->generic_sql_query(
						"CREATE TABLE TagListtable(
						Tagindex INT UNSIGNED NOT NULL REFERENCES Tagtable(Tagindex),
						TTindex INT UNSIGNED NOT NULL REFERENCES Tracktable(TTindex) ON DELETE CASCADE,
						PRIMARY KEY (Tagindex, TTindex)) ENGINE=InnoDB", true
					);
					$this->generic_sql_query(
						"INSERT INTO TagListtable(Tagindex, TTindex)
						SELECT Tagindex, TTindex FROM _taglist_old", true
					);
					$this->generic_sql_query("DROP TABLE _taglist_old", true);
					$this->set_admin_value('SchemaVer', 80);
					break;

				case 80:
					logger::log("SQL", "Updating FROM Schema version 80 TO Schema version 81");
					$progs = $this->generic_sql_query("SELECT * FROM Progresstable WHERE Progress > 0");
					foreach ($progs as $p) {
						logger::log("SQL", "  Adding Resume bookmark for TTindex",$p['TTindex']);
						$this->sql_prepare_query(true, null, null, null,
							"INSERT INTO Bookmarktable (TTindex, Bookmark, Name) VALUES (?, ?, ?)",
							$p['TTindex'],
							$p['Progress'],
							'Resume'
						);
					}
					if (!$this->trigger_not_exists('Progresstable', 'progress_update_trigger')) {
						$this->generic_sql_query("DROP TRIGGER progress_update_trigger", true);
					}
					if (!$this->trigger_not_exists('Progresstable', 'progress_insert_trigger')) {
						$this->generic_sql_query("DROP TRIGGER progress_insert_trigger", true);
					}
					$this->generic_sql_query("DROP TABLE Progresstable", true);
					$this->create_progress_triggers();
					$this->set_admin_value('SchemaVer', 81);
					break;

				case 81:
					logger::log("SQL", "Updating FROM Schema version 81 TO Schema version 82");
					$progs = $this->generic_sql_query("SELECT * FROM PodcastTracktable WHERE Progress > 0");
					foreach ($progs as $p) {
						logger::log("SQL", "  Adding Resume bookmark for PODTrackindex",$p['PODTrackindex']);
						$this->sql_prepare_query(true, null, null, null,
							"INSERT INTO PodBookmarktable (PODTrackindex, Bookmark, Name) VALUES (?, ?, ?)",
							$p['PODTrackindex'],
							$p['Progress'],
							'Resume'
						);
					}

					$this->generic_sql_query("ALTER TABLE PodcastTracktable DROP Progress", true);

					$this->set_admin_value('SchemaVer', 82);
					break;

				case 82:
					// Fix a probme where Spotify tracks restored from a metadata backup get an album domain of local
					logger::log("SQL", "Updating FROM Schema version 82 TO Schema version 83");
					$this->generic_sql_query("UPDATE Albumtable SET domain = 'spotify' WHERE AlbumUri LIKE 'spotify:%'", true);
					$this->set_admin_value('SchemaVer', 83);
					break;

				case 83:
					logger::log("SQL", "Updating FROM Schema version 83 TO Schema version 84");
					$this->generic_sql_query("ALTER TABLE PodcastTracktable ADD Image VARCHAR(255) DEFAULT NULL", true);
					$this->set_admin_value('SchemaVer', 84);
					break;

				case 84:
					logger::log("SQL", "Updating FROM Schema version 84 TO Schema version 85");
					prefs::upgrade_host_defs(85);
					$this->set_admin_value('SchemaVer', 85);
					break;

				case 85:
					logger::log("SQL", "Updating FROM Schema version 85 TO Schema version 86");
					$this->set_admin_value('SchemaVer', 86);
					break;

				case 86:
					logger::log("SQL", "Updating FROM Schema version 86 TO Schema version 87");
					$this->set_admin_value('SchemaVer', 87);
					break;

				case 87:
					logger::log("SQL", "Updating FROM Schema version 87 TO Schema version 88");
					$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('LastCache', ".time().")", true);
					if ($this->get_admin_value('Updating', null) === null)
						$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('Updating', '0')", true);
					$this->set_admin_value('SchemaVer', 88);
					break;

				case 88:
					logger::log("SQL", "Updating FROM Schema version 88 TO Schema version 89");
					$this->generic_sql_query("ALTER TABLE Podcasttable ADD NextUpdate INT UNSIGNED DEFAULT 0", true);
					$this->generic_sql_query("ALTER TABLE Podcasttable DROP LastUpdate", true);
					$podcasts = $this->generic_sql_query("SELECT * FROM Podcasttable");
					foreach($podcasts as $podcast) {
						$this->sql_prepare_query(true, null, null, null,
							"UPDATE Podcasttable SET NextUpdate = ? WHERE PODindex = ?",
							calculate_best_update_time($podcast),
							$podcast['PODindex']
						);
					}
					$this->set_admin_value('SchemaVer', 89);
					break;

				case 89:
					logger::log("SQL", "Updating FROM Schema version 89 TO Schema version 90");
					$this->generic_sql_query("ALTER TABLE Podcasttable ADD UpRetry INT UNSIGNED DEFAULT 0", true);
					$this->set_admin_value('SchemaVer', 90);
					break;

				case 90:
					logger::log("SQL", "Updating FROM Schema version 90 TO Schema version 91");
					prefs::upgrade_host_defs(91);
					$this->set_admin_value('SchemaVer', 91);
					break;

				case 91:
					logger::log("SQL", "Updating FROM Schema version 91 TO Schema version 92");
					prefs::upgrade_host_defs(92);
					$this->set_admin_value('SchemaVer', 92);
					break;

				case 92:
					logger::log("SQL", "Updating FROM Schema version 92 TO Schema version 93");
					// The Uri index, which we no longer want, might have been added at creation time
					// or it might have been added retrospectively. It'll have a different name in each case.
					$indices = $this->generic_sql_query("SHOW INDEX FROM Tracktable");
					foreach ($indices as $index) {
						if ($index['Column_name'] == 'Uri') {
							$this->generic_sql_query("DROP INDEX ".$index['Key_name']." ON Tracktable", true);
						}
					}
					$this->set_admin_value('SchemaVer', 93);
					break;

				case 93:
					logger::log("SQL", "Updating FROM Schema version 93 TO Schema version 94");
					prefs::upgrade_host_defs(94);
					$this->set_admin_value('SchemaVer', 94);
					break;

				case 94:
					logger::log("SQL", "Updating FROM Schema version 94 TO Schema version 95");
					prefs::upgrade_host_defs(95);
					$this->set_admin_value('SchemaVer', 95);
					break;

				case 95:
					// Now we need that Uri index again, because Youtube.
					logger::log("SQL", "Updating FROM Schema version 95 TO Schema version 96");
					$this->generic_sql_query("CREATE INDEX track_uri ON Tracktable (Uri(768))", true);
					$this->set_admin_value('SchemaVer', 96);
					break;

				case 96:
					logger::log("SQL", "Updating FROM Schema version 96 TO Schema version 97");
					$this->check_foreign_keys();
					$this->set_admin_value('SchemaVer', 97);
					break;


			}
			$sv++;
		}

		return array(true, "");
	}

	private function check_foreign_keys() {

		// At some point the REFERENCES definitions in the table creation statements stopped working
		// without throwing an error. So now we don't know if they exist and if they do we don't know
		// what they're called. So we have to do our best to check for them and create them if they're not there.

		$this->generic_sql_query("DELETE FROM Ratingtable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable)", true);
		$this->generic_sql_query("DELETE FROM Bookmarktable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable)", true);
		$this->generic_sql_query("DELETE FROM TagListtable WHERE Tagindex NOT IN (SELECT Tagindex FROM Tagtable)", true);
		$this->generic_sql_query("DELETE FROM TagListtable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable)", true);
		$this->generic_sql_query("DELETE FROM Playcounttable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable)", true);
		$this->generic_sql_query("DELETE FROM PodBookmarktable WHERE PODTrackindex NOT IN (SELECT PODTrackindex FROM PodcastTracktable)", true);
		$this->generic_sql_query("DELETE FROM RadioTracktable WHERE Stationindex NOT IN (SELECT Stationindex FROM RadioStationtable)", true);

		if ($this->key_not_exists('Ratingtable', 'TTindex', 'Tracktable')) {
			logger::info('MYSQL', 'Creating Ratingtable Foreign Key For TTindex');
			$this->generic_sql_query("ALTER TABLE Ratingtable ADD CONSTRAINT fk_rating FOREIGN KEY (TTindex) REFERENCES Tracktable (TTindex) ON DELETE CASCADE", true);
		}

		if ($this->key_not_exists('Bookmarktable', 'TTindex', 'Tracktable')) {
			logger::info('MYSQL', 'Creating Bookmarktable Foreign Key For TTindex');
			$this->generic_sql_query("ALTER TABLE Bookmarktable ADD CONSTRAINT fk_bookmark FOREIGN KEY (TTindex) REFERENCES Tracktable (TTindex) ON DELETE CASCADE", true);
		}

		if ($this->key_not_exists('TagListtable', 'Tagindex', 'Tagtable')) {
			logger::info('MYSQL', 'Creating TagListtable Foreign Key For Tagindex');
			$this->generic_sql_query("ALTER TABLE Bookmarktable ADD CONSTRAINT fk_taglist_tag FOREIGN KEY (Tagindex) REFERENCES Tagtable (Tagindex) ON DELETE CASCADE", true);
		}

		if ($this->key_not_exists('TagListtable', 'TTindex', 'Tracktable')) {
			logger::info('MYSQL', 'Creating TagListtable Foreign Key For TTindex');
			$this->generic_sql_query("ALTER TABLE TagListtable ADD CONSTRAINT fk_taglist_track FOREIGN KEY (TTindex) REFERENCES Tracktable (TTindex) ON DELETE CASCADE", true);
		}

		if ($this->key_not_exists('Playcounttable', 'TTindex', 'Tracktable')) {
			logger::info('MYSQL', 'Creating Playcounttable Foreign Key For TTindex');
			$this->generic_sql_query("ALTER TABLE Playcounttable ADD CONSTRAINT fk_playcount FOREIGN KEY (TTindex) REFERENCES Tracktable (TTindex) ON DELETE CASCADE", true);
		}

		if ($this->key_not_exists('PodBookmarktable', 'PODTrackindex', 'PodcastTracktable')) {
			logger::info('MYSQL', 'Creating PodBookmarktable Foreign Key For PODTrackindex');
			$this->generic_sql_query("ALTER TABLE PodBookmarktable ADD CONSTRAINT fk_podbookmark FOREIGN KEY (PODTrackindex) REFERENCES PodcastTracktable (PODTrackindex) ON DELETE CASCADE", true);
		}

		if ($this->key_not_exists('RadioTracktable', 'Stationindex', 'RadioStationtable')) {
			logger::info('MYSQL', 'Creating RadioStationtable Foreign Key For Stationindex');
			$this->generic_sql_query("ALTER TABLE PodBookmarktable ADD CONSTRAINT fk_radiotrack FOREIGN KEY (Stationindex) REFERENCES RadioStationtable (Stationindex) ON DELETE CASCADE", true);
		}

	}

	private function key_not_exists($table, $ref_column, $ref_table) {
		$count = $this->sql_prepare_query(false, null, 'num', 0,
			"SELECT COUNT(CONSTRAINT_NAME) AS num FROM information_schema.KEY_COLUMN_USAGE
			WHERE TABLE_NAME = ?
			AND REFERENCED_COLUMN_NAME = ?
			AND REFERENCED_TABLE_NAME = ?",
			$table,
			$columns,
			$ref_table
		);
		return ($count == 0);
	}

}

?>