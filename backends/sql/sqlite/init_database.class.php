<?php
class init_database extends init_generic {

	function check_sql_tables() {

		//
		// Check SQLite Version
		//

		$sqlite_version = $this->generic_sql_query("SELECT sqlite_version() AS v", false, null, 'v', 0);
		logger::info('INIT', 'SQLite Version is',$sqlite_version);
		if (version_compare($sqlite_version, ROMPR_MIN_SQLITE_VERSION, '<')) {
			return array(false, 'Your system has a version of SQLite which is too old. You have '.$sqlite_version.' but RompR needs '.ROMPR_MIN_SQLITE_VERSION.'. Either upgrade your system or use MySQL instead');
		}

		//
		// Create Statstable so we know it's there when we try to check the Schema Version
		//

		if (!$this->generic_sql_query("CREATE TABLE IF NOT EXISTS Statstable(Item CHAR(11), Value INTEGER, PRIMARY KEY(Item))", true)) {
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
			TTindex INTEGER PRIMARY KEY NOT NULL UNIQUE,
			Title VARCHAR(255) COLLATE NOCASE,
			Albumindex INTEGER,
			TrackNo SMALLINT,
			Duration INTEGER,
			Artistindex INTEGER,
			Disc TINYINT(3),
			Uri TEXT,
			LastModified CHAR(32),
			Hidden TINYINT(1) DEFAULT 0,
			DateAdded TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			isSearchResult TINYINT(1) DEFAULT 0,
			Sourceindex INTEGER DEFAULT NULL,
			LinkChecked TINYINT(1) DEFAULT 0,
			isAudiobook TINYINT(1) DEFAULT 0,
			justAdded TINYINT(1) DEFAULT 1,
			usedInPlaylist TINYINT(1) DEFAULT 0,
			Genreindex INT UNSIGNED DEFAULT 0,
			TYear YEAR)", true))
		{
			logger::log("SQLITE", "  Tracktable OK");
			list($success, $msg) = $this->create_tracktable_indexes();
			if (!$success) {
				return array($success, $msg);
			}
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Tracktable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Albumtable(
			Albumindex INTEGER PRIMARY KEY NOT NULL UNIQUE,
			Albumname VARCHAR(255) COLLATE NOCASE,
			AlbumArtistindex INTEGER,
			AlbumUri VARCHAR(255),
			Year YEAR,
			Searched TINYINT(1),
			ImgKey CHAR(32),
			mbid CHAR(40),
			ImgVersion INTEGER DEFAULT {$this->get_constant('ROMPR_IMAGE_VERSION')},
			Domain CHAR(32),
			Image VARCHAR(255),
			useTrackIms TINYINT(1) DEFAULT 0,
			randomSort INT DEFAULT 0,
			justUpdated TINYINT(1) DEFAULT 0)", true))
		{
			logger::log("SQLITE", "  Albumtable OK");
			if ($this->generic_sql_query("CREATE INDEX IF NOT EXISTS ni ON Albumtable (Albumname)", true)) {
			} else {
				$err = $this->mysqlc->errorInfo()[2];
				return array(false, "Error While Checking Albumtable : ".$err);
			}
			if ($this->generic_sql_query("CREATE INDEX IF NOT EXISTS aai ON Albumtable (AlbumArtistindex)", true)) {
			} else {
				$err = $this->mysqlc->errorInfo()[2];
				return array(false, "Error While Checking Albumtable : ".$err);
			}
			if ($this->generic_sql_query("CREATE INDEX IF NOT EXISTS di ON Albumtable (Domain)", true)) {
			} else {
				$err = $this->mysqlc->errorInfo()[2];
				return array(false, "Error While Checking Albumtable : ".$err);
			}
			if ($this->generic_sql_query("CREATE INDEX IF NOT EXISTS ii ON Albumtable (ImgKey)", true)) {
			} else {
				$err = $this->mysqlc->errorInfo()[2];
				return array(false, "Error While Checking Albumtable : ".$err);
			}
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Albumtable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Artisttable(
			Artistindex INTEGER PRIMARY KEY NOT NULL UNIQUE,
			Artistname VARCHAR(255) COLLATE NOCASE)", true))
		{
			logger::log("SQLITE", "  Artisttable OK");
			if ($this->generic_sql_query("CREATE INDEX IF NOT EXISTS ni ON Artisttable (Artistname)", true)) {
			} else {
				$err = $this->mysqlc->errorInfo()[2];
				return array(false, "Error While Checking Artisttable : ".$err);
			}
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Artisttable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Ratingtable(
			TTindex INTEGER PRIMARY KEY NOT NULL UNIQUE REFERENCES Tracktable(TTindex) ON DELETE CASCADE,
			Rating TINYINT(1))", true))
		{
			logger::log("SQLITE", "  Ratingtable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Ratingtable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Bookmarktable(
			TTindex INTEGER NOT NULL REFERENCES Tracktable(TTindex) ON DELETE CASCADE,
			Bookmark INTEGER,
			Name VARCHAR(128) NOT NULL,
			PRIMARY KEY (TTindex, Name))", true))
		{
			logger::log("SQLITE", "  Bookmarktable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Bookmarktable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Tagtable(
			Tagindex INTEGER PRIMARY KEY NOT NULL UNIQUE,
			Name VARCHAR(255))", true))
		{
			logger::log("SQLITE", "  Tagtable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Tagtable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS TagListtable(
			Tagindex INTEGER NOT NULL REFERENCES Tagtable(Tagindex) ON DELETE CASCADE,
			TTindex INTEGER NOT NULL REFERENCES Tracktable(TTindex) ON DELETE CASCADE,
			PRIMARY KEY (Tagindex, TTindex))", true))
		{
			logger::log("SQLITE", "  TagListtable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking TagListtable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Playcounttable(
			TTindex INTEGER PRIMARY KEY NOT NULL UNIQUE REFERENCES Tracktable(TTindex) ON DELETE CASCADE,
			Playcount INT UNSIGNED NOT NULL,
			SyncCount INT UNSIGNED DEFAULT 0,
			LastPlayed TIMESTAMP DEFAULT NULL)", true))
		{
			logger::log("SQLITE", "  Playcounttable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Playcounttable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Podcasttable(
			PODindex INTEGER PRIMARY KEY NOT NULL UNIQUE,
			FeedURL TEXT,
			Image VARCHAR(255),
			Title VARCHAR(255),
			Artist VARCHAR(255),
			RefreshOption TINYINT(2) DEFAULT 0,
			SortMode TINYINT(2) DEFAULT 0,
			HideDescriptions TINYINT(1) DEFAULT 0,
			DisplayMode TINYINT(2) DEFAULT 0,
			DaysToKeep INTEGER DEFAULT 0,
			NumToKeep INTEGER DEFAULT 0,
			KeepDownloaded TINYINT(1) DEFAULT 0,
			AutoDownload TINYINT(1) DEFAULT 0,
			DaysLive INTEGER,
			Version TINYINT(2),
			Subscribed TINYINT(1) NOT NULL DEFAULT 1,
			Description TEXT,
			LastPubDate INTEGER DEFAULT NULL,
			NextUpdate INTEGER DEFAULT 0,
			WriteTags TINYINT(1) DEFAULT 0,
			UpRetry INTEGER DEFAULT 0,
			Category VARCHAR(255))", true))
		{
			logger::log("SQLITE", "  Podcasttable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Podcasttable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS PodcastTracktable(
			PODTrackindex INTEGER PRIMARY KEY NOT NULL UNIQUE,
			JustUpdated TINYINT(1),
			PODindex INTEGER,
			Title VARCHAR(255),
			Artist VARCHAR(255),
			Duration INTEGER,
			PubDate INTEGER,
			FileSize INTEGER,
			Description TEXT,
			Link TEXT,
			Guid TEXT,
			Localfilename VARCHAR(255),
			Downloaded TINYINT(1) DEFAULT 0,
			Listened TINYINT(1) DEFAULT 0,
			New TINYINT(1) DEFAULT 1,
			Deleted TINYINT(1) DEFAULT 0,
			Image VARCHAR(255) DEFAULT NULL)", true))
		{
			logger::log("SQLITE", "  PodcastTracktable OK");
			if ($this->generic_sql_query("CREATE INDEX IF NOT EXISTS ptt ON PodcastTracktable (Title)", true)) {
			} else {
				$err = $this->mysqlc->errorInfo()[2];
				return array(false, "Error While Checking PodcastTracktable : ".$err);
			}
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking PodcastTracktable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS PodBookmarktable(
			PODTrackindex INTEGER NOT NULL REFERENCES PodcastTracktable(PODTrackindex) ON DELETE CASCADE,
			Bookmark INTEGER,
			Name VARCHAR(128) NOT NULL,
			PRIMARY KEY (PODTrackIndex, Name))", true))
		{
			logger::log("SQLITE", "  PodBookmarktable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking PodBookmarktable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS RadioStationtable(
			Stationindex INTEGER PRIMARY KEY NOT NULL UNIQUE,
			Number SMALLINT DEFAULT 65535,
			IsFave TINYINT(1),
			StationName VARCHAR(255),
			PlaylistUrl TEXT,
			Image VARCHAR(255))", true))
		{
			logger::log("SQLITE", "  RadioStationtable OK");
			if ($this->generic_sql_query("CREATE INDEX IF NOT EXISTS ui ON RadioStationtable (PlaylistUrl)", true)) {
			} else {
				$err = $this->mysqlc->errorInfo()[2];
				return array(false, "Error While Checking RadioStationtable : ".$err);
			}
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking RadioStationtable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS RadioTracktable(
			Trackindex INTEGER PRIMARY KEY NOT NULL UNIQUE,
			Stationindex INTEGER REFERENCES RadioStationtable(Stationindex),
			TrackUri TEXT,
			PrettyStream TEXT)", true))
		{
			logger::log("SQLITE", "  RadioTracktable OK");
			if ($this->generic_sql_query("CREATE INDEX IF NOT EXISTS uri ON RadioTracktable (TrackUri)", true)) {
			} else {
				$err = $this->mysqlc->errorInfo()[2];
				return array(false, "Error While Checking RadioTracktable : ".$err);
			}
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking RadioTracktable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS WishlistSourcetable(
			Sourceindex INTEGER PRIMARY KEY NOT NULL UNIQUE,
			SourceName VARCHAR(255),
			SourceImage VARCHAR(255),
			SourceUri TEXT)", true))
		{
			logger::log("SQLITE", "  WishlistSourcetable OK");
			if ($this->generic_sql_query("CREATE INDEX IF NOT EXISTS suri ON WishlistSourcetable (SourceUri)", true)) {
			} else {
				$err = $this->mysqlc->errorInfo()[2];
				return array(false, "Error While Checking WishlistSourcetable : ".$err);
			}
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking WishlistSourcetable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS AlbumsToListenTotable(
			Listenindex INTEGER PRIMARY KEY NOT NULL UNIQUE,
			JsonData TEXT)", true))
		{
			logger::log("SQLITE", "  AlbumsToListenTotabletable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking AlbumsToListenTotable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS BackgroundImageTable(
			BgImageIndex INTEGER PRIMARY KEY NOT NULL UNIQUE,
			Skin VARCHAR(255),
			BrowserID VARCHAR(20) DEFAULT NULL,
			Filename VARCHAR(255),
			Used TINYINT(1) DEFAULT 0,
			Orientation TINYINT(2))", true))
		{
			logger::log("SQLITE", "  BackgounrdImageTable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking BackgroundImageTable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Genretable(
			Genreindex INTEGER PRIMARY KEY NOT NULL UNIQUE,
			Genre VARCHAR(40))", true))
		{
			logger::log("SQLITE", "  Genretable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Genretable : ".$err);
		}
		$this->generic_sql_query("CREATE INDEX IF NOT EXISTS gi ON Genretable (Genre)", true);

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Artistbrowse(
			Artistindex INTEGER PRIMARY KEY NOT NULL UNIQUE,
			Uri VARCHAR(255))", true))
		{
			logger::log("MYSQL", "  Artistbrowse OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Artistbrowse : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Sleeptimers(
			Pid INTEGER DEFAULT NULL,
			Player VARCHAR(50) NOT NULL,
			TimeSet INTEGER NOT NULL,
			SleepTime INTEGER NOT NULL)", true))
		{
			logger::log("SQLITE", "  Sleeptimers OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Sleeptimers : ".$err);
		}

		// Pid will be NULL if alarm is not enabled
		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Alarms(
			Alarmindex INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
			Pid INTEGER DEFAULT NULL,
			SnoozePid INTEGER DEFAULT NULL,
			Player VARCHAR(50) NOT NULL,
			Running TINYINT(1) DEFAULT 0,
			Interrupt TINYINT(1) DEFAULT 0,
			Ramp TINYINT(1) DEFAULT 0,
			Stopafter TINYINT(1) DEFAULT 0,
			StopMins INTEGER DEFAULT 60,
			Time CHARACTER(5),
			Rpt TINYINT(1) DEFAULT 0,
			Days VARCHAR(100) NOT NULL,
			Name VARCHAR(255),
			PlayItem TINYINT(1) DEFAULT 0,
			ItemToPlay TEXT NOT NULL,
			PlayCommands TEXT NOT NULL)", true))
		{
			logger::log("SQLITE", "  Alarms OK");
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
					$this->generic_sql_query("ALTER TABLE Tracktable ADD COLUMN Genreindex INT UNSIGNED DEFAULT 0", true);
					// $this->generic_sql_query("INSERT INTO Genretable (Genre) VALUES ('None')", true);
					$this->set_admin_value('SchemaVer', 64);
					break;

				case 64:
					logger::log("SQL", "Updating FROM Schema version 64 TO Schema version 65");
					$this->generic_sql_query("ALTER TABLE Tracktable ADD COLUMN TYear YEAR", true);
					// $this->generic_sql_query("INSERT INTO Genretable (Genre) VALUES ('None')", true);
					$this->set_admin_value('SchemaVer', 65);
					break;

				case 65:
					logger::log("SQL", "Updating FROM Schema version 65 TO Schema version 66");
					$this->update_track_dates();
					$this->set_admin_value('SchemaVer', 66);
					break;

				case 66:
					logger::log("SQL", "Updating FROM Schema version 66 TO Schema version 67");
					$this->generic_sql_query("ALTER TABLE Podcasttable ADD COLUMN WriteTags TINYINT(1) DEFAULT 0", true);
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
					$this->generic_sql_query("DROP INDEX ai", true);
					$this->generic_sql_query("DROP INDEX ti", true);
					$this->generic_sql_query("DROP INDEX tn", true);
					$this->generic_sql_query("DROP INDEX di", true);
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
					$this->create_conditional_triggers();
					$this->set_admin_value('SchemaVer', 72);
					break;

				case 72:
					logger::log("SQL", "Updating FROM Schema version 72 TO Schema version 73");
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
					$this->create_conditional_triggers();
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
					$this->generic_sql_query("PRAGMA foreign_keys=off");
					$this->generic_sql_query("ALTER TABLE Playcounttable RENAME TO _playcounts_old", true);
					$this->generic_sql_query(
						"CREATE TABLE Playcounttable(
						TTindex INTEGER PRIMARY KEY NOT NULL UNIQUE REFERENCES Tracktable(TTindex) ON DELETE CASCADE,
						Playcount INT UNSIGNED NOT NULL,
						SyncCount INT UNSIGNED DEFAULT 0,
						LastPlayed TIMESTAMP DEFAULT NULL)"
					);
					$this->generic_sql_query(
						"INSERT INTO Playcounttable(TTindex, Playcount, SyncCount, LastPlayed)
						SELECT TTindex, Playcount, SyncCount, LastPlayed FROM _playcounts_old", true
					);
					$this->generic_sql_query("DROP TABLE _playcounts_old", true);
					$this->generic_sql_query("PRAGMA foreign_keys=on");

					$this->set_admin_value('SchemaVer', 78);
					break;

				case 78:
					logger::log("SQL", "Updating FROM Schema version 78 TO Schema version 79");
					logger::log("SQL", "This may take a long time");
					$this->generic_sql_query("PRAGMA foreign_keys=off", true);
					$this->generic_sql_query("DELETE FROM Ratingtable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable WHERE Hidden = 0)", true);
					$this->generic_sql_query("ALTER TABLE Ratingtable RENAME TO _ratings_old", true);
					$this->generic_sql_query(
						"CREATE TABLE Ratingtable(
							TTindex INTEGER PRIMARY KEY NOT NULL UNIQUE REFERENCES Tracktable(TTindex) ON DELETE CASCADE,
							Rating TINYINT(1))", true
					);
					$this->generic_sql_query(
						"INSERT INTO Ratingtable(TTindex, Rating)
						SELECT TTindex, Rating FROM _ratings_old", true
					);
					$this->generic_sql_query("DROP TABLE _ratings_old", true);
					$this->generic_sql_query("PRAGMA foreign_keys=on", true);
					$this->set_admin_value('SchemaVer', 79);
					break;

				case 79:
					logger::log("SQL", "Updating FROM Schema version 79 TO Schema version 80");
					logger::log("SQL", "This may take a long time");
					$this->generic_sql_query("PRAGMA foreign_keys=off", true);
					$this->generic_sql_query("DELETE FROM TagListtable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable WHERE Hidden = 0)", true);
					$this->generic_sql_query("ALTER TABLE TagListtable RENAME TO _taglist_old", true);
					$this->generic_sql_query(
						"CREATE TABLE TagListtable(
							Tagindex INTEGER NOT NULL REFERENCES Tagtable(Tagindex),
							TTindex INTEGER NOT NULL REFERENCES Tracktable(TTindex) ON DELETE CASCADE,
							PRIMARY KEY (Tagindex, TTindex))", true
					);
					$this->generic_sql_query(
						"INSERT INTO TagListtable(Tagindex, TTindex)
						SELECT Tagindex, TTindex FROM _taglist_old", true
					);
					$this->generic_sql_query("DROP TABLE _taglist_old", true);
					$this->generic_sql_query("PRAGMA foreign_keys=on", true);
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
					$this->generic_sql_query("DROP TRIGGER IF EXISTS progress_update_trigger", true);
					$this->generic_sql_query("DROP TRIGGER IF EXISTS progress_insert_trigger", true);
					$this->generic_sql_query("DROP TABLE Progresstable", true);
					$this->create_progress_triggers();
					$this->set_admin_value('SchemaVer', 81);
					break;

				case 81:
					logger::log("SQL", "Updating FROM Schema version 81 TO Schema version 82");

					// Due to a fuckup, I renamed Taglisttable to _taglist_old without first deleting the trigger.
					// This caused the trigged to be moved with the table, but when I then dropped the table it didn't
					// drop the trigger so now we have a trigger that applies to a nonexistent table.
					$this->generic_sql_query('DROP TRIGGER IF EXISTS tag_delete_trigger', true);
					$this->create_update_triggers();
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

					$this->generic_sql_query("CREATE TABLE IF NOT EXISTS PodcastTracktable_New(".
						"PODTrackindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
						"JustUpdated TINYINT(1), ".
						"PODindex INTEGER, ".
						"Title VARCHAR(255), ".
						"Artist VARCHAR(255), ".
						"Duration INTEGER, ".
						"PubDate INTEGER, ".
						"FileSize INTEGER, ".
						"Description TEXT, ".
						"Link TEXT, ".
						"Guid TEXT, ".
						"Localfilename VARCHAR(255), ".
						"Downloaded TINYINT(1) DEFAULT 0, ".
						"Listened TINYINT(1) DEFAULT 0, ".
						"New TINYINT(1) DEFAULT 1, ".
						"Deleted TINYINT(1) DEFAULT 0)", true);
					$this->generic_sql_query("INSERT INTO PodcastTracktable_New
						SELECT PODTrackindex, JustUpdated, PODindex, Title, Artist, Duration,
						PubDate, FileSize, Description, Link, Guid, Localfilename, Downloaded,
						Listened, New, Deleted FROM PodcastTracktable", true);
					$this->generic_sql_query("DROP TABLE PodcastTracktable", true);
					$this->generic_sql_query("ALTER TABLE PodcastTracktable_New RENAME TO PodcastTracktable", true);

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
					$this->generic_sql_query('DROP TRIGGER track_insert_trigger', true);
					$this->generic_sql_query('DROP TRIGGER track_update_trigger', true);
					$this->generic_sql_query('DROP TRIGGER rating_update_trigger', true);
					$this->generic_sql_query('DROP TRIGGER rating_insert_trigger', true);
					$this->generic_sql_query('DROP TRIGGER tag_insert_trigger', true);
					$this->generic_sql_query('DROP TRIGGER tag_remove_trigger', true);
					$this->generic_sql_query('DROP TRIGGER track_delete_trigger', true);
					$this->generic_sql_query('DROP TRIGGER progress_update_trigger', true);
					$this->generic_sql_query('DROP TRIGGER progress_insert_trigger', true);
					$this->generic_sql_query("CREATE TABLE Albumtable_New(".
						"Albumindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
						"Albumname VARCHAR(255) COLLATE NOCASE, ".
						"AlbumArtistindex INTEGER, ".
						"AlbumUri VARCHAR(255), ".
						"Year YEAR, ".
						"Searched TINYINT(1), ".
						"ImgKey CHAR(32), ".
						"mbid CHAR(40), ".
						"ImgVersion INTEGER DEFAULT ".ROMPR_IMAGE_VERSION.", ".
						"Domain CHAR(32), ".
						"Image VARCHAR(255), ".
						"useTrackIms TINYINT(1) DEFAULT 0, ".
						"randomSort INT DEFAULT 0, ".
						"justUpdated TINYINT(1) DEFAULT 0)", true);
					$this->generic_sql_query(
						"INSERT INTO Albumtable_New
							SELECT Albumindex, Albumname, AlbumArtistindex, AlbumUri, Year, Searched, ImgKey, mbid,
							ImgVersion, Domain, Image, useTrackIms, randomSort, justUpdated FROM Albumtable", true);
					$this->generic_sql_query("DROP TABLE Albumtable", true);
					$this->generic_sql_query("ALTER TABLE Albumtable_New RENAME TO Albumtable", true);
					$this->generic_sql_query("CREATE INDEX IF NOT EXISTS ni ON Albumtable (Albumname)", true);
					$this->generic_sql_query("CREATE INDEX IF NOT EXISTS aai ON Albumtable (AlbumArtistindex)", true);
					$this->generic_sql_query("CREATE INDEX IF NOT EXISTS di ON Albumtable (Domain)", true);
					$this->generic_sql_query("CREATE INDEX IF NOT EXISTS ii ON Albumtable (ImgKey)", true);
					$this->create_conditional_triggers();
					$this->create_update_triggers();
					$this->create_progress_triggers();
					$this->set_admin_value('SchemaVer', 86);
					break;

				case 86:
					logger::log("SQL", "Updating FROM Schema version 86 TO Schema version 87");
					$this->generic_sql_query("CREATE TABLE IF NOT EXISTS Artisttable_New(".
						"Artistindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
						"Artistname VARCHAR(255) COLLATE NOCASE)", true);
					$this->generic_sql_query(
						"INSERT INTO Artisttable_New SELECT Artistindex, Artistname FROM Artisttable", true
					);
					$this->generic_sql_query("DROP TABLE Artisttable", true);
					$this->generic_sql_query("ALTER TABLE Artisttable_New RENAME TO Artisttable", true);
					$this->generic_sql_query("CREATE INDEX IF NOT EXISTS ni ON Artisttable (Artistname)", true);
					$this->set_admin_value('SchemaVer', 87);
					break;

				case 87:
					logger::log("SQL", "Updating FROM Schema version 87 TO Schema version 88");
					$this->generic_sql_query('DROP TRIGGER track_insert_trigger', true);
					$this->generic_sql_query('DROP TRIGGER track_update_trigger', true);
					$this->generic_sql_query('DROP TRIGGER rating_update_trigger', true);
					$this->generic_sql_query('DROP TRIGGER rating_insert_trigger', true);
					$this->generic_sql_query('DROP TRIGGER tag_insert_trigger', true);
					$this->generic_sql_query('DROP TRIGGER tag_remove_trigger', true);
					$this->generic_sql_query('DROP TRIGGER track_delete_trigger', true);
					$this->generic_sql_query('DROP TRIGGER progress_update_trigger', true);
					$this->generic_sql_query('DROP TRIGGER progress_insert_trigger', true);
					$this->generic_sql_query("CREATE TABLE Tracktable_New(".
						"TTindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
						"Title VARCHAR(255) COLLATE NOCASE, ".
						"Albumindex INTEGER, ".
						"TrackNo SMALLINT, ".
						"Duration INTEGER, ".
						"Artistindex INTEGER, ".
						"Disc TINYINT(3), ".
						"Uri TEXT,".
						"LastModified CHAR(32), ".
						"Hidden TINYINT(1) DEFAULT 0, ".
						"DateAdded TIMESTAMP DEFAULT CURRENT_TIMESTAMP, ".
						"isSearchResult TINYINT(1) DEFAULT 0, ".
						"Sourceindex INTEGER DEFAULT NULL, ".
						"LinkChecked TINYINT(1) DEFAULT 0, ".
						"isAudiobook TINYINT(1) DEFAULT 0, ".
						"justAdded TINYINT(1) DEFAULT 1, ".
						"usedInPlaylist TINYINT(1) DEFAULT 0, ".
						"Genreindex INT UNSIGNED DEFAULT 0, ".
						"TYear YEAR)", true);
					$this->generic_sql_query(
						"INSERT INTO Tracktable_New SELECT TTindex, Title, Albumindex, TrackNo, Duration, Artistindex, Disc, Uri,
							LastModified, Hidden, DateAdded, isSearchResult, SourceIndex, LinkChecked, isAudioBook,
							justAdded, usedInPlaylist, Genreindex, TYear
						FROM Tracktable", true);
					$this->generic_sql_query("DROP TABLE Tracktable", true);
					$this->generic_sql_query("ALTER TABLE Tracktable_New RENAME TO Tracktable", true);
					$this->create_tracktable_indexes();
					$this->create_conditional_triggers();
					$this->create_update_triggers();
					$this->create_progress_triggers();
					$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('LastCache', ".time().")", true);
					if ($this->get_admin_value('Updating', null) === null)
						$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('Updating', '0')", true);

					$this->set_admin_value('SchemaVer', 88);
					break;

				case 88:
					logger::log("SQL", "Updating FROM Schema version 88 TO Schema version 89");
					$this->generic_sql_query("CREATE TABLE IF NOT EXISTS Podcasttable_New(".
						"PODindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
						"FeedURL TEXT, ".
						"Image VARCHAR(255), ".
						"Title VARCHAR(255), ".
						"Artist VARCHAR(255), ".
						"RefreshOption TINYINT(2) DEFAULT 0, ".
						"SortMode TINYINT(2) DEFAULT 0, ".
						"HideDescriptions TINYINT(1) DEFAULT 0, ".
						"DisplayMode TINYINT(2) DEFAULT 0, ".
						"DaysToKeep INTEGER DEFAULT 0, ".
						"NumToKeep INTEGER DEFAULT 0, ".
						"KeepDownloaded TINYINT(1) DEFAULT 0, ".
						"AutoDownload TINYINT(1) DEFAULT 0, ".
						"DaysLive INTEGER, ".
						"Version TINYINT(2), ".
						"Subscribed TINYINT(1) NOT NULL DEFAULT 1, ".
						"Description TEXT, ".
						"LastPubDate INTEGER DEFAULT NULL, ".
						"NextUpdate INTEGER DEFAULT 0, ".
						"WriteTags TINYINT(1) DEFAULT 0, ".
						"Category VARCHAR(255))", true);
					$this->generic_sql_query(
						"INSERT INTO Podcasttable_New SELECT PODindex, FeedURL, Image, Title, Artist, RefreshOption, SortMode, HideDescriptions,
							DisplayMode, DaysToKeep, NumToKeep, KeepDownloaded, AutoDownload, DaysLive, Version, Subscribed, Description,
							LastPubDate, 0 AS NextUpdate, WriteTags, Category FROM Podcasttable", true
					);
					$this->generic_sql_query("DROP TABLE Podcasttable", true);
					$this->generic_sql_query("ALTER TABLE Podcasttable_New RENAME TO Podcasttable", true);
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
					$this->generic_sql_query("ALTER TABLE Podcasttable ADD UpRetry INTEGER DEFAULT 0", true);
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
					// At this point we didn't want the Uri index
					$this->generic_sql_query("DROP INDEX IF EXISTS track_finder_index", true);
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
					logger::log("SQL", "Updating FROM Schema version 95 TO Schema version 96");
					// At this point we do want the Uri index, because Youtube
					$this->create_tracktable_indexes();
					$this->set_admin_value('SchemaVer', 96);
					break;

				case 96:
					logger::log("SQL", "Updating FROM Schema version 96 TO Schema version 97");
					// This was adding foreign keys in MySQL
					$this->set_admin_value('SchemaVer', 97);
					break;

			}
			$sv++;
		}

		return array(true, "");
	}

	//
	// If creating the unique index fails, it's probably because we have some duplicates. This can happen with YouTube
	// because, you know, it doesn't really have albums etc. So we try to correct the duplicates and then try to create the index again
	//

	private function duplicate_track_check() {
		logger::mark('SQLITE', 'Performing duplicate track check to try to fix this error');
		$duplicates = $this->generic_sql_query(
			"SELECT GROUP_CONCAT(TTindex, ',') AS ttids, Albumindex, Artistindex, TrackNo, Disc, Title
			FROM Tracktable
			GROUP BY Albumindex, Artistindex, TrackNo, Disc, Title
			HAVING COUNT(*) > 1"
		);
		if (count($duplicates) > 0) {
			logger::warn('SQLITE', 'Duplicates found. Attempting to correct');
			foreach ($duplicates as $duplicate) {
				$disc = $duplicate['Disc'];
				$ttids = explode(',', $duplicate['ttids']);
				foreach ($ttids as $ttid) {
					logger::log('SQLITE', 'Setting Disc to',$disc,'on TTindex',$ttid);
					$this->sql_prepare_query(true, null, null, null,
						"UPDATE Tracktable SET Disc = ? WHERE TTindex = ?",
						$disc, $ttid
					);
					$disc++;
				}
			}
		}
	}

	//
	// Function unique to SQLite, we can't create the indexes when we create the tables
	// so we call this whenever we need to check them or upgrade them. Fortunately
	// SQLite supports IF NOT EXISTS for this.
	//

	protected function create_tracktable_indexes() {
		$retries = 2;
		$success = false;
		while ($retries > 0) {
			if ($this->generic_sql_query("CREATE UNIQUE INDEX IF NOT EXISTS trackfinder ON Tracktable (Albumindex, Artistindex, TrackNo, Disc, Title)", true)) {
				logger::log('SQLITE', 'Tracktable Indexes created OK');
				$retries = 0;
				$success = true;
			} else {
				logger::warn('SQLITE', 'Caught exception while trying to create unique index on tracktable.');
				$this->duplicate_track_check();
				$retries--;
			}
		}

		if (!$success) {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, 'Error creating unique index on Tracktable : '.$err);
		}

		if (!$this->generic_sql_query("CREATE INDEX IF NOT EXISTS track_uri ON Tracktable (Uri)", true)) {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error Creating Tracktable Uri Index : ".$err);
		}

		return array(true, '');
	}

}
?>