<?php

class init_database extends init_generic {

	public function check_sql_tables() {

		$vsn = $this->generic_sql_query("SELECT VERSION() AS v");
		$mysql_version = $vsn[0]['v'];
		logger::log('INIT', 'MySQL Version is',$mysql_version);
		prefs::$prefs['mysql_version'] = $mysql_version;
		if (strpos($mysql_version, 'MariaDB') !== false) {
			if (version_compare($mysql_version, ROMPR_MIN_MARIADB_VERSION, '<')) {
				logger::warn('MYSQL', 'Running old version of MariaDB, will have to use old style update query');
				prefs::$prefs['old_style_sql'] = true;
			} else {
				prefs::$prefs['old_style_sql'] = false;
			}
		} else {
			if (version_compare($mysql_version, ROMPR_MIN_MYSQL_VERSION, '<')) {
				logger::warn('MYSQL', 'Running old version of MySQL, will have to use old style update query');
				prefs::$prefs['old_style_sql'] = true;
			} else {
				prefs::$prefs['old_style_sql'] = false;
			}
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Tracktable(".
			"TTindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
			"PRIMARY KEY(TTindex), ".
			"Title VARCHAR(255), ".
			"Albumindex INT UNSIGNED, ".
			"TrackNo SMALLINT UNSIGNED, ".
			"Duration INT UNSIGNED, ".
			"Artistindex INT UNSIGNED, ".
			"Disc TINYINT(3) UNSIGNED, ".
			"Uri VARCHAR(2000), ".
			"LastModified CHAR(32), ".
			"Hidden TINYINT(1) UNSIGNED DEFAULT 0, ".
			"DateAdded TIMESTAMP DEFAULT CURRENT_TIMESTAMP, ".
			"isSearchResult TINYINT(1) UNSIGNED DEFAULT 0, ".
			"justAdded TINYINT(1) UNSIGNED DEFAULT 1, ".
			"Sourceindex INT UNSIGNED DEFAULT NULL, ".
			"LinkChecked TINYINT(1) UNSIGNED DEFAULT 0, ".
			"isAudiobook TINYINT(1) UNSIGNED DEFAULT 0, ".
			"usedInPlaylist TINYINT(1) UNSIGNED DEFAULT 0, ".
			"Genreindex INT UNSIGNED DEFAULT 0, ".
			"TYear YEAR, ".
			"UNIQUE INDEX(Albumindex, Artistindex, TrackNo, Disc, Title)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  Tracktable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Tracktable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Albumtable(".
			"Albumindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
			"PRIMARY KEY(Albumindex), ".
			"Albumname VARCHAR(255), ".
			"AlbumArtistindex INT UNSIGNED, ".
			"AlbumUri VARCHAR(255), ".
			"Year YEAR, ".
			"Searched TINYINT(1) UNSIGNED, ".
			"ImgKey CHAR(32), ".
			"mbid CHAR(40), ".
			"ImgVersion INT UNSIGNED DEFAULT ".ROMPR_IMAGE_VERSION.", ".
			"Domain CHAR(32), ".
			"Image VARCHAR(255), ".
			"randomSort INT DEFAULT 0, ".
			"justUpdated TINYINT(1) UNSIGNED DEFAULT 1, ".
			"useTrackIms TINYINT(1) DEFAULT 0, ".
			"INDEX(Albumname), ".
			"INDEX(AlbumArtistindex), ".
			"INDEX(Domain), ".
			"INDEX(ImgKey)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  Albumtable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Albumtable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Artisttable(".
			"Artistindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
			"PRIMARY KEY(Artistindex), ".
			"Artistname VARCHAR(255), ".
			"INDEX(Artistname)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  Artisttable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Artisttable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Ratingtable(".
			"TTindex INT UNSIGNED NOT NULL REFERENCES Tracktable(TTindex) ON DELETE CASCADE, ".
			"PRIMARY KEY(TTindex), ".
			"Rating TINYINT(1) UNSIGNED) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  Ratingtable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Ratingtable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Bookmarktable(".
			"TTindex INT UNSIGNED NOT NULL REFERENCES Tracktable(TTindex) ON DELETE CASCADE, ".
			"Bookmark INT UNSIGNED, ".
			"Name VARCHAR(128) NOT NULL, ".
			"PRIMARY KEY (TTindex, Name)".
			")", true))
		{
			logger::log("SQLITE", "  Bookmarktable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Bookmarktable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Tagtable(".
			"Tagindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
			"PRIMARY KEY(Tagindex), ".
			"Name VARCHAR(255)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  Tagtable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Tagtable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS TagListtable(".
			"Tagindex INT UNSIGNED NOT NULL REFERENCES Tagtable(Tagindex), ".
			"TTindex INT UNSIGNED NOT NULL REFERENCES Tracktable(TTindex) ON DELETE CASCADE, ".
			"PRIMARY KEY (Tagindex, TTindex)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  TagListtable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking TagListtable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Playcounttable(".
			"TTindex INT UNSIGNED NOT NULL REFERENCES Tracktable(TTindex) ON DELETE CASCADE, ".
			"Playcount INT UNSIGNED NOT NULL, ".
			"SyncCount INT UNSIGNED DEFAULT 0, ".
			"LastPlayed TIMESTAMP, ".
			"PRIMARY KEY (TTindex)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  Playcounttable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Playcounttable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Podcasttable(".
			"PODindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
			"FeedURL TEXT, ".
			"Image VARCHAR(255), ".
			"Title VARCHAR(255), ".
			"Artist VARCHAR(255), ".
			"RefreshOption TINYINT(2) UNSIGNED DEFAULT 0, ".
			"SortMode TINYINT(2) UNSIGNED DEFAULT 0, ".
			"HideDescriptions TINYINT(1) UNSIGNED DEFAULT 0, ".
			"DisplayMode TINYINT(2) UNSIGNED DEFAULT 0, ".
			"DaysToKeep INT UNSIGNED DEFAULT 0, ".
			"NumToKeep INT UNSIGNED DEFAULT 0, ".
			"KeepDownloaded TINYINT(1) UNSIGNED DEFAULT 0, ".
			"AutoDownload TINYINT(1) UNSIGNED DEFAULT 0, ".
			"DaysLive INT, ".
			"Version TINYINT(2), ".
			"Subscribed TINYINT(1) NOT NULL DEFAULT 1, ".
			"Description TEXT, ".
			"LastPubDate INT UNSIGNED DEFAULT NULL, ".
			"NextUpdate INT UNSIGNED DEFAULT 0, ".
			"Category VARCHAR(255) NOT NULL, ".
			"WriteTags TINYINT(1) DEFAULT 0, ".
			"UpRetry INT UNSIGNED DEFAULT 0, ".
			"PRIMARY KEY (PODindex)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  Podcasttable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Podcasttable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS PodcastTracktable(".
			"PODTrackindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
			"JustUpdated TINYINT(1), ".
			"PODindex INT UNSIGNED, ".
			"Title VARCHAR(255), ".
			"Artist VARCHAR(255), ".
			"Duration INT UNSIGNED, ".
			"PubDate INT, ".
			"FileSize INT UNSIGNED, ".
			"Description TEXT, ".
			"Link TEXT, ".
			"Guid TEXT, ".
			"Localfilename VARCHAR(255), ".
			"Downloaded TINYINT(1) UNSIGNED DEFAULT 0, ".
			"Listened TINYINT(1) UNSIGNED DEFAULT 0, ".
			"New TINYINT(1) UNSIGNED DEFAULT 1, ".
			"Deleted TINYINT(1) UNSIGNED DEFAULT 0, ".
			"Image VARCHAR(255) DEFAULT NULL, ".
			"INDEX (PODindex), ".
			"PRIMARY KEY (PODTrackindex), ".
			"INDEX (Title)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  PodcastTracktable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking PodcastTracktable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS PodBookmarktable(".
			"PODTrackindex INT UNSIGNED NOT NULL REFERENCES PodcastTracktable(PODTrackindex) ON DELETE CASCADE, ".
			"Bookmark INT UNSIGNED, ".
			"Name VARCHAR(128) NOT NULL, ".
			"PRIMARY KEY (PODTrackIndex, Name)".
			")", true))
		{
			logger::log("MYSQL", "  PodBookmarktable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking PodBookmarktable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS RadioStationtable(".
			"Stationindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
			"Number SMALLINT UNSIGNED DEFAULT 65535, ".
			"IsFave TINYINT(1), ".
			"StationName VARCHAR(255), ".
			"PlaylistUrl TEXT, ".
			"Image VARCHAR(255), ".
			"PRIMARY KEY (Stationindex)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  RadioStationtable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking RadioStationtable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS RadioTracktable(".
			"Trackindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
			"Stationindex INT UNSIGNED REFERENCES RadioStationtable(Stationindex), ".
			"TrackUri TEXT, ".
			"PrettyStream TEXT, ".
			"PRIMARY KEY (Trackindex)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  RadioTracktable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking RadioTracktable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS WishlistSourcetable(".
			"Sourceindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
			"SourceName VARCHAR(255), ".
			"SourceImage VARCHAR(255), ".
			"SourceUri TEXT, ".
			"PRIMARY KEY (Sourceindex)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  WishlistSourcetable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking WishlistSourcetable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS AlbumsToListenTotable(".
			"Listenindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
			"JsonData TEXT, ".
			"PRIMARY KEY (Listenindex)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  AlbumsToListenTotabletable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking AlbumsToListenTotable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS BackgroundImageTable(".
			"BgImageIndex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
			"Skin VARCHAR(255), ".
			"BrowserID VARCHAR(20) DEFAULT NULL, ".
			"Filename VARCHAR(255), ".
			"Orientation TINYINT(2), ".
			"Used TINYINT(1) DEFAULT 0, ".
			"PRIMARY KEY (BgImageIndex), ".
			"INDEX (Skin), ".
			"INDEX (BrowserID)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL", "  BackgounrdImageTable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking BackgroundImageTable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Genretable(".
			"Genreindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
			"Genre VARCHAR(40), ".
			"PRIMARY KEY (Genreindex), ".
			"INDEX (Genre))", true))
		{
			logger::log("MYSQL", "  Genretable OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Genretable : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Artistbrowse(".
			"Artistindex INT UNSIGNED NOT NULL UNIQUE, ".
			"Uri VARCHAR(255), ".
			"PRIMARY KEY (Artistindex))", true))
		{
			logger::log("MYSQL", "  Artistbrowse OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Artistbrowse : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Sleeptimers(".
			"Pid INT UNSIGNED DEFAULT NULL, ".
			"Player VARCHAR(50) NOT NULL, ".
			"TimeSet INT UNSIGNED NOT NULL, ".
			"SleepTime INT UNSIGNED NOT NULL)", true))
		{
			logger::log("MYSQL", "  Sleeptimers OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Sleeptimers : ".$err);
		}

		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS Alarms(".
			"Alarmindex INT UNSIGNED PRIMARY KEY AUTO_INCREMENT NOT NULL, ".
			// Pid will be NULL if alarm is not enabled
			"Pid INT UNSIGNED DEFAULT NULL, ".
			"SnoozePid INT UNSIGNED DEFAULT NULL, ".
			"Player VARCHAR(50) NOT NULL, ".
			"Running TINYINT(1) UNSIGNED DEFAULT 0, ".
			"Interrupt TINYINT(1) DEFAULT 0, ".
			// MySQL can't have a column called Repeat!!!!
			"Rpt TINYINT(1) UNSIGNED DEFAULT 0, ".
			"Ramp TINYINT(1) UNSIGNED DEFAULT 0, ".
			"Stopafter TINYINT(1) UNSIGNED DEFAULT 0, ".
			"StopMins INT UNSIGNED DEFAULT 60, ".
			"Time CHAR(5), ".
			"Days VARCHAR(100) NOT NULL, ".
			"Name VARCHAR(255), ".
			"PlayItem TINYINT(1) UNSIGNED DEFAULT 0, ".
			"ItemToPlay TEXT NOT NULL, ".
			"PlayCommands TEXT NOT NULL)", true))
		{
			logger::log("MYSQL", "  Alarms OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Alarms : ".$err);
		}

		if (!$this->generic_sql_query("CREATE TABLE IF NOT EXISTS Statstable(Item CHAR(11), PRIMARY KEY(Item), Value INT UNSIGNED) ENGINE=InnoDB", true)) {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Statstable : ".$err);
		}
		// Check schema version and update tables as necessary
		$sv = $this->simple_query('Value', 'Statstable', 'Item', 'SchemaVer', 0);
		if ($sv == 0) {
			logger::mark("MYSQL", "No Schema Version Found - initialising table");
			$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('ListVersion', '0')", true);
			$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('ArtistCount', '0')", true);
			$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('AlbumCount', '0')", true);
			$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('TrackCount', '0')", true);
			$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('TotalTime', '0')", true);
			$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('CollType', '999')", true);
			$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('SchemaVer', '".ROMPR_SCHEMA_VERSION."')", true);
			$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('BookArtists', '0')", true);
			$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('BookAlbums', '0')", true);
			$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('BookTracks', '0')", true);
			$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('BookTime', '0')", true);
			$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('LastCache', '10')", true);
			$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('Updating', '0')", true);
			$sv = ROMPR_SCHEMA_VERSION;
			logger::log("MYSQL", "Statstable populated");
		}

		if (!$this->create_update_triggers()) {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Creating Triggers : ".$err);
		}
		if (!$this->create_conditional_triggers()) {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Creating Triggers : ".$err);
		}
		if (!$this->create_playcount_triggers()) {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Creating Triggers : ".$err);
		}
		if (!$this->create_progress_triggers()) {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Creating Triggers : ".$err);
		}

		if ($sv > ROMPR_SCHEMA_VERSION) {
			logger::warn("MYSQL", "Schema Mismatch! We are version ".ROMPR_SCHEMA_VERSION." but database is version ".$sv);
			return array(false, "Your database has version number ".$sv." but this version of rompr only handles version ".ROMPR_SCHEMA_VERSION);
		}

		while ($sv < ROMPR_SCHEMA_VERSION) {
			switch ($sv) {
				case 0:
					logger::error("SQL", "BIG ERROR! No Schema Version found!!");
					return array(false, "Database Error - could not read schema version. Cannot continue.");
					break;

				case 1:
					logger::log("SQL", "Updating FROM Schema version 1 TO Schema version 2");
					$this->generic_sql_query("ALTER TABLE Albumtable DROP Directory", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 2 WHERE Item = 'SchemaVer'", true);
					break;

				case 2:
					logger::log("SQL", "Updating FROM Schema version 2 TO Schema version 3");
					$this->generic_sql_query("ALTER TABLE Tracktable ADD Hidden TINYINT(1) UNSIGNED DEFAULT 0", true);
					$this->generic_sql_query("UPDATE Tracktable SET Hidden = 0 WHERE Hidden IS NULL", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 3 WHERE Item = 'SchemaVer'", true);
					break;

				case 3:
					logger::log("SQL", "Updating FROM Schema version 3 TO Schema version 4");
					$this->generic_sql_query("UPDATE Tracktable SET Disc = 1 WHERE Disc IS NULL OR Disc = 0", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 4 WHERE Item = 'SchemaVer'", true);
					break;

				case 4:
					logger::log("SQL", "Updating FROM Schema version 4 TO Schema version 5");
					$this->generic_sql_query("UPDATE Albumtable SET Searched = 0 WHERE Image NOT LIKE 'albumart%'", true);
					$this->generic_sql_query("ALTER TABLE Albumtable DROP Image", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 5 WHERE Item = 'SchemaVer'", true);
					break;

				case 5:
					logger::log("SQL", "Updating FROM Schema version 5 TO Schema version 6");
					$this->generic_sql_query("DROP INDEX Disc on Tracktable", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 6 WHERE Item = 'SchemaVer'", true);
					break;

				case 6:
					logger::log("SQL", "Updating FROM Schema version 6 TO Schema version 7");
					// This was going to be a nice datestamp but newer versions of mysql don't work that way
					$this->generic_sql_query("ALTER TABLE Tracktable ADD DateAdded TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", true);
					$this->generic_sql_query("UPDATE Tracktable SET DateAdded = FROM_UNIXTIME(LastModified) WHERE LastModified IS NOT NULL AND LastModified > 0", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 7 WHERE Item = 'SchemaVer'", true);
					break;

				case 7:
					logger::log("SQL", "Updating FROM Schema version 7 TO Schema version 8");
					// Since we've changed the way we're joining artist names together,
					// rather than force the database to be recreated and screw up everyone's
					// tags and rating, just modify the artist data.
					$stmt = $this->sql_prepare_query_later("UPDATE Artisttable SET Artistname = ? WHERE Artistindex = ?");
					if ($stmt !== FALSE) {
						$result = $this->generic_sql_query("SELECT * FROM Artisttable", false, PDO::FETCH_OBJ);
						foreach ($result as $obj) {
							$artist = (string) $obj->Artistname;
							$art = explode(' & ', $artist);
							if (count($art) > 2) {
								$f = array_slice($art, 0, count($art) - 1);
								$newname = implode($f, ", ")." & ".$art[count($art) - 1];
								logger::log("UPGRADE_SCHEMA", "Updating artist name from ".$artist." to ".$newname);
								$stmt->execute(array($newname, $obj->Artistindex));
							}
						}
					}
					$this->generic_sql_query("UPDATE Statstable SET Value = 8 WHERE Item = 'SchemaVer'", true);
					$stmt = null;
					break;

				case 8:
					logger::log("SQL", "Updating FROM Schema version 8 TO Schema version 9");
					// We removed the image column earlier, but I've decided we need it again
					// because some mopidy backends supply images and archiving them all makes
					// creating the collection take waaaaay too long.
					$this->generic_sql_query("ALTER TABLE Albumtable ADD Image VARCHAR(255)", true);
					// So we now need to recreate the image database. Sadly this means that people using Beets will lose their album images.
					$result = $this->generic_sql_query("SELECT Albumindex, ImgKey FROM Albumtable", false, PDO::FETCH_OBJ);
					foreach ($result as $obj) {
						if (file_exists('albumart/small/'.$obj->ImgKey.'.jpg')) {
							$this->generic_sql_query("UPDATE Albumtable SET Image = 'albumart/small/".$obj->ImgKey.".jpg', Searched = 1 WHERE Albumindex = ".$obj->Albumindex, true);
						} else {
							$this->generic_sql_query("UPDATE Albumtable SET Image = '', Searched = 0 WHERE Albumindex = ".$obj->Albumindex, true);
						}
					}
					$this->generic_sql_query("UPDATE Statstable SET Value = 9 WHERE Item = 'SchemaVer'", true);
					break;

				case 9:
					logger::log("SQL", "Updating FROM Schema version 9 TO Schema version 10");
					$this->generic_sql_query("ALTER TABLE Albumtable DROP NumDiscs", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 10 WHERE Item = 'SchemaVer'", true);
					break;

				case 10:
					logger::log("SQL", "Updating FROM Schema version 10 TO Schema version 11");
					$this->generic_sql_query("ALTER TABLE Albumtable DROP IsOneFile", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 11 WHERE Item = 'SchemaVer'", true);
					break;

				case 11:
					logger::log("SQL", "Updating FROM Schema version 11 TO Scheme version 12");
					$this->generic_sql_query("ALTER TABLE Tracktable ADD isSearchResult TINYINT(1) UNSIGNED DEFAULT 0", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 12 WHERE Item = 'SchemaVer'", true);
					break;

				case 12:
					logger::log("SQL", "Updating FROM Schema version 12 TO Scheme version 13");
					$this->generic_sql_query("ALTER TABLE Albumtable CHANGE Spotilink AlbumUri VARCHAR(255)", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 13 WHERE Item = 'SchemaVer'", true);
					break;

				case 13:
					logger::log("SQL", "Updating FROM Schema version 13 TO Scheme version 14");
					// Nothing to do here, this is for SQLite only.
					$this->generic_sql_query("UPDATE Statstable SET Value = 14 WHERE Item = 'SchemaVer'", true);
					break;

				case 14:
					logger::log("SQL", "Updating FROM Schema version 14 TO Scheme version 15");
					$this->generic_sql_query("ALTER TABLE Tracktable MODIFY LastModified CHAR(32)", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 15 WHERE Item = 'SchemaVer'", true);
					break;

				case 15:
					logger::log("SQL", "Updating FROM Schema version 15 TO Schema version 16");
					$this->albumImageBuggery();
					$this->generic_sql_query("UPDATE Statstable SET Value = 16 WHERE Item = 'SchemaVer'", true);
					break;

				case 16:
					logger::log("SQL", "Updating FROM Schema version 16 TO Schema version 17");
					// Early MPD versions had LastModified as an integer value. They changed it to a datestamp some time ago but I didn't notice
					$r = $this->generic_sql_query("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Tracktable' AND COLUMN_NAME = 'LastModified'");
					foreach ($r as $obj) {
						logger::trace("MYSQL_INIT", "Data Type of LastModified is ".$obj['DATA_TYPE']);
						if ($obj['DATA_TYPE'] == 'int') {
							logger::trace("MYSQL_INIT", "Modifying Tracktable");
							$this->generic_sql_query("ALTER TABLE Tracktable ADD LM CHAR(32)", true);
							$this->generic_sql_query("UPDATE Tracktable SET LM = LastModified", true);
							$this->generic_sql_query("ALTER TABLE Tracktable DROP LastModified", true);
							$this->generic_sql_query("ALTER TABLE Tracktable CHANGE LM LastModified CHAR(32)", true);
						}
					}
					$this->generic_sql_query("UPDATE Statstable SET Value = 17 WHERE Item = 'SchemaVer'", true);
					break;

				case 17:
					logger::log("SQL", "Updating FROM Schema version 17 TO Schema version 18");
					include("utils/podcastupgrade.php");
					$this->generic_sql_query("UPDATE Statstable SET Value = 18 WHERE Item = 'SchemaVer'", true);
					break;

				case 18:
					logger::log("SQL", "Updating FROM Schema version 18 TO Schema version 19");
					$result = $this->generic_sql_query('SELECT Tracktable.Uri AS uri, Tracktable.TTindex, Tracktable.Title AS ttit, Albumtable.*, Trackimagetable.Image AS ti FROM Tracktable JOIN Albumtable USING (Albumindex) LEFT JOIN Trackimagetable USING (TTindex) WHERE Tracktable.Uri LIKE "soundcloud:%"', false, PDO::FETCH_OBJ);
					foreach ($result as $obj) {
						logger::trace("SQL", "  Creating new Album ".$obj->ttit." Image ".$obj->ti);
						$ti = $obj->ti;
						if (preg_match('/^http/', $ti)) {
							$ti = 'getRemoteImage.php?url='.$ti;
						}
						if ($this->sql_prepare_query(true, null, null, null,
							"INSERT INTO Albumtable
								(Albumname, AlbumArtistindex, AlbumUri, Year, Searched, ImgKey, mbid, Domain, Image)
							VALUES
								(?, ?, ?, ?, ?, ?, ?, ?, ?)",
								$obj->ttit, $obj->AlbumArtistindex, $obj->uri, $obj->Year, $obj->Searched, $obj->ImgKey, $obj->mbid, $obj->Domain, $ti
							)) {
								$retval = $this->mysqlc->lastInsertId();
								logger::debug("SQL", "    .. success, Albumindex ".$retval);
								$this->generic_sql_query("UPDATE Tracktable SET Albumindex = ".$retval." WHERE TTindex = ".$obj->TTindex, true);
						} else {
							logger::warn("SQL", "    .. ERROR!");
						}
					}
					$this->generic_sql_query("UPDATE Statstable SET Value = 19 WHERE Item = 'SchemaVer'", true);
					break;

				case 19:
					logger::log("SQL", "Updating FROM Schema version 19 TO Schema version 20");
					$result = $this->generic_sql_query('SELECT Tracktable.Uri AS uri, Tracktable.TTindex, Tracktable.Title AS ttit, Albumtable.*, Trackimagetable.Image AS ti FROM Tracktable JOIN Albumtable USING (Albumindex) LEFT JOIN Trackimagetable USING (TTindex) WHERE Tracktable.Uri LIKE "youtube:%"', false, PDO::FETCH_OBJ);
					foreach ($result as $obj) {
						logger::trace("SQL", "  Creating new Album ".$obj->ttit." Image ".$obj->ti);
						$ti = $obj->ti;
						if (preg_match('/^http/', $ti)) {
							$ti = 'getRemoteImage.php?url='.$ti;
						}
						if ($this->sql_prepare_query(true, null, null, null,
							"INSERT INTO Albumtable
								(Albumname, AlbumArtistindex, AlbumUri, Year, Searched, ImgKey, mbid, Domain, Image)
							VALUES
								(?, ?, ?, ?, ?, ?, ?, ?, ?)",
								$obj->ttit, $obj->AlbumArtistindex, $obj->uri, $obj->Year, $obj->Searched, $obj->ImgKey, $obj->mbid, $obj->Domain, $ti
							)) {
								$retval = $this->mysqlc->lastInsertId();
								logger::debug("SQL", "    .. success, Albumindex ".$retval);
								$this->generic_sql_query("UPDATE Tracktable SET Albumindex = ".$retval." WHERE TTindex = ".$obj->TTindex, true);
						} else {
							logger::warn("SQL", "    .. ERROR!");
						}
					}
					$this->generic_sql_query("UPDATE Statstable SET Value = 20 WHERE Item = 'SchemaVer'", true);
					break;

				case 20:
					logger::log("SQL", "Updating FROM Schema version 20 TO Schema version 21");
					$this->generic_sql_query("DROP TABLE Trackimagetable", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 21 WHERE Item = 'SchemaVer'", true);
					break;

				case 21:
					logger::log("SQL", "Updating FROM Schema version 21 TO Schema version 22");
					$this->generic_sql_query("ALTER TABLE Playcounttable ADD LastPlayed TIMESTAMP NULL", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 22 WHERE Item = 'SchemaVer'", true);
					break;

				case 22:
					logger::log("SQL", "Updating FROM Schema version 22 TO Schema version 23");
					$this->generic_sql_query("ALTER TABLE Podcasttable ADD Version TINYINT(2)", true);
					$this->generic_sql_query("ALTER TABLE PodcastTracktable ADD Guid VARCHAR(2000)", true);
					$this->generic_sql_query("ALTER TABLE PodcastTracktable ADD Localfilename VARCHAR(255)", true);
					$this->generic_sql_query("UPDATE Podcasttable SET Version = 1 WHERE PODindex IS NOT NULL", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 23 WHERE Item = 'SchemaVer'", true);
					break;

				case 23:
					logger::log("SQL", "Updating FROM Schema version 23 TO Schema version 24");
					$this->generic_sql_query("ALTER TABLE Tracktable CHANGE DateAdded DateAdded TIMESTAMP DEFAULT CURRENT_TIMESTAMP", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 24 WHERE Item = 'SchemaVer'", true);
					break;

				case 24:
					logger::log("SQL", "Updating FROM Schema version 24 TO Schema version 25");
					$this->generic_sql_query("ALTER DATABASE romprdb CHARACTER SET utf8 COLLATE utf8_unicode_ci", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 25 WHERE Item = 'SchemaVer'", true);
					break;

				case 25:
					logger::log("SQL", "Updating FROM Schema version 25 TO Schema version 26");
					$this->generic_sql_query("ALTER TABLE Tracktable ADD justAdded TINYINT(1) UNSIGNED DEFAULT 1", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 26 WHERE Item = 'SchemaVer'", true);
					break;

				case 26:
					logger::log("SQL", "Updating FROM Schema version 26 TO Schema version 27");
					$this->generic_sql_query("ALTER TABLE Albumtable ADD justUpdated TINYINT(1) UNSIGNED DEFAULT 1", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 27 WHERE Item = 'SchemaVer'", true);
					break;

				case 27:
					logger::log("SQL", "Updating FROM Schema version 27 TO Schema version 28");
					$this->rejig_wishlist_tracks();
					$this->generic_sql_query("UPDATE Statstable SET Value = 28 WHERE Item = 'SchemaVer'", true);
					break;

				case 28:
					logger::log("SQL", "Updating FROM Schema version 28 TO Schema version 29");
					if (!$this->create_update_triggers()) {
						$err = $this->mysqlc->errorInfo()[2];
						return array(false, "Error While Creating Triggers : ".$err);
					}
					$this->generic_sql_query("UPDATE Statstable SET Value = 29 WHERE Item = 'SchemaVer'", true);
					break;

				case 29:
					logger::log("SQL", "Updating FROM Schema version 29 TO Schema version 30");
					include('utils/radioupgrade.php');
					$this->generic_sql_query("UPDATE Statstable SET Value = 30 WHERE Item = 'SchemaVer'", true);
					break;

				case 30:
					logger::log("SQL", "Updating FROM Schema version 30 TO Schema version 31");
					$this->generic_sql_query("ALTER TABLE PodcastTracktable CHANGE Description Description TEXT", true);
					$this->generic_sql_query("ALTER TABLE PodcastTracktable CHANGE Link Link TEXT", true);
					$this->generic_sql_query("ALTER TABLE PodcastTracktable CHANGE Guid Guid TEXT", true);
					$this->generic_sql_query("ALTER TABLE Podcasttable CHANGE FeedURL FeedURL TEXT", true);
					$this->generic_sql_query("ALTER TABLE Podcasttable CHANGE Description Description TEXT", true);
					$this->generic_sql_query("ALTER TABLE Tracktable CHANGE Uri Uri TEXT", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 31 WHERE Item = 'SchemaVer'", true);
					break;

				case 31:
					logger::log("SQL", "Updating FROM Schema version 31 TO Schema version 32");
					$this->generic_sql_query("ALTER TABLE Podcasttable ADD Subscribed TINYINT(1) NOT NULL DEFAULT 1", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 32 WHERE Item = 'SchemaVer'", true);
					break;

				case 32:
					logger::log("SQL", "Updating FROM Schema version 32 TO Schema version 33");
					$this->generic_sql_query("DROP TRIGGER IF EXISTS track_insert_trigger", true);
					$this->generic_sql_query("DROP TRIGGER IF EXISTS track_update_trigger", true);
					if (!$this->create_conditional_triggers()) {
						$err = $this->mysqlc->errorInfo()[2];
						return array(false, "Error While Creating Triggers : ".$err);
					}
					$this->generic_sql_query("UPDATE Statstable SET Value = 33 WHERE Item = 'SchemaVer'", true);
					break;

				case 33:
					logger::log("SQL", "Updating FROM Schema version 33 TO Schema version 34");
					$this->generic_sql_query("ALTER TABLE Albumtable ADD ImgVersion INT UNSIGNED DEFAULT ".ROMPR_IMAGE_VERSION, true);
					$this->generic_sql_query("UPDATE Albumtable SET ImgVersion = 1",true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 34 WHERE Item = 'SchemaVer'", true);
					break;

				case 34:
					logger::log("SQL", "Updating FROM Schema version 34 TO Schema version 35");
					$this->generic_sql_query("ALTER TABLE Tracktable ADD Sourceindex INT UNSIGNED DEFAULT NULL", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 35 WHERE Item = 'SchemaVer'", true);
					break;

				case 35:
					$this->generic_sql_query("UPDATE Statstable SET Value = 36 WHERE Item = 'SchemaVer'", true);
					break;

				case 36:
					logger::log("SQL", "Updating FROM Schema version 35 TO Schema version 37");
					$localpods = $this->generic_sql_query("SELECT PODTrackindex, PODindex, LocalFilename FROM PodcastTracktable WHERE LocalFilename IS NOT NULL");
					foreach ($localpods as $pod) {
						$this->sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET LocalFilename = ? WHERE PODTrackindex = ?", '/prefs/podcasts/'.$pod['PODindex'].'/'.$pod['PODTrackindex'].'/'.$pod['LocalFilename'], $pod['PODTrackindex']);
					}
					$this->generic_sql_query("UPDATE Statstable SET Value = 37 WHERE Item = 'SchemaVer'", true);
					break;

				case 37:
					logger::log("SQL", "Updating FROM Schema version 37 TO Schema version 38");
					$this->generic_sql_query("ALTER TABLE Albumtable MODIFY ImgVersion INT UNSIGNED DEFAULT ".ROMPR_IMAGE_VERSION, true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 38 WHERE Item = 'SchemaVer'", true);
					break;

				case 38:
					logger::log("SQL", "Updating FROM Schema version 38 TO Schema version 39");
					$this->generic_sql_query("ALTER TABLE Podcasttable ADD LastPubDate INT UNSIGNED DEFAULT NULL", true);
					$this->generic_sql_query("CREATE INDEX ptt ON PodcastTracktable (Title)", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 39 WHERE Item = 'SchemaVer'", true);
					break;

				case 39:
					logger::log("SQL", "Updating FROM Schema version 39 TO Schema version 40");
					// Takes too long. It'll happen when they get refreshed anyway.
					// require_once('podcasts/podcastfunctions.php');
					// upgrade_podcast_images();
					$this->generic_sql_query("UPDATE Statstable SET Value = 40 WHERE Item = 'SchemaVer'", true);
					break;

				case 40:
					logger::log("SQL", "Updating FROM Schema version 40 TO Schema version 41");
					$this->generic_sql_query("ALTER TABLE Podcasttable ADD Category VARCHAR(255) NOT NULL", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 41 WHERE Item = 'SchemaVer'", true);
					break;

				case 41:
					logger::log("SQL", "Updating FROM Schema version 41 TO Schema version 42");
					$this->generic_sql_query("ALTER TABLE PodcastTracktable ADD Progress INT UNSIGNED DEFAULT 0", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 42 WHERE Item = 'SchemaVer'", true);
					break;

				case 42:
					logger::log("SQL", "Updating FROM Schema version 42 TO Schema version 43");
					$this->update_stream_images(43);
					$this->generic_sql_query("UPDATE Statstable SET Value = 43 WHERE Item = 'SchemaVer'", true);
					break;

				case 43:
					logger::log("SQL", "Updating FROM Schema version 43 TO Schema version 44");
					$this->empty_modified_cache_dirs(44);
					$this->generic_sql_query("UPDATE Statstable SET Value = 44 WHERE Item = 'SchemaVer'", true);
					break;

				case 44:
					logger::log("SQL", "Updating FROM Schema version 44 TO Schema version 45");
					prefs::upgrade_host_defs(45);
					$this->generic_sql_query("UPDATE Statstable SET Value = 45 WHERE Item = 'SchemaVer'", true);
					break;

				case 45:
					logger::log("SQL", "Updating FROM Schema version 45 TO Schema version 46");
					$this->generic_sql_query("UPDATE Statstable SET Value = 46 WHERE Item = 'SchemaVer'", true);
					break;

				case 46:
					logger::log("SQL", "Updating FROM Schema version 46 TO Schema version 47");
					$this->generic_sql_query("ALTER TABLE Playcounttable ADD SyncCount INT UNSIGNED DEFAULT 0", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 47 WHERE Item = 'SchemaVer'", true);
					$this->create_playcount_triggers();
					break;

				case 47:
					logger::log("SQL", "Updating FROM Schema version 47 TO Schema version 48");
					// Some versions had a default value and an on update for LastPlayed, which is WRONG and fucks things up
					$this->generic_sql_query("ALTER TABLE Playcounttable ALTER LastPlayed DROP DEFAULT", true);
					$this->generic_sql_query("ALTER TABLE Playcounttable CHANGE LastPlayed LastPlayed TIMESTAMP NULL", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 48 WHERE Item = 'SchemaVer'", true);
					break;

				case 48:
					logger::log("SQL", "Updating FROM Schema version 48 TO Schema version 49");
					prefs::upgrade_host_defs(49);
					$this->generic_sql_query("UPDATE Statstable SET Value = 49 WHERE Item = 'SchemaVer'", true);
					break;

				case 49:
					logger::log("SQL", "Updating FROM Schema version 49 TO Schema version 50");
					$this->generic_sql_query("ALTER TABLE Tracktable ADD LinkChecked TINYINT(1) UNSIGNED DEFAULT 0", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 50 WHERE Item = 'SchemaVer'", true);
					break;

				case 50:
					logger::log("SQL", "Updating FROM Schema version 50 TO Schema version 51");
					// Something wierd happened and I lost half my triggers. In case it happens to anyone else...
					$this->generic_sql_query("DROP TRIGGER IF EXISTS track_insert_trigger", true);
					$this->generic_sql_query("DROP TRIGGER IF EXISTS track_update_trigger", true);
					$this->generic_sql_query("DROP TRIGGER IF EXISTS rating_update_trigger", true);
					$this->generic_sql_query("DROP TRIGGER IF EXISTS rating_insert_trigger", true);
					$this->generic_sql_query("DROP TRIGGER IF EXISTS tag_delete_trigger", true);
					$this->generic_sql_query("DROP TRIGGER IF EXISTS tag_insert_trigger", true);
					$this->generic_sql_query("DROP TRIGGER IF EXISTS tag_remove_trigger", true);
					$this->generic_sql_query("DROP TRIGGER IF EXISTS track_delete_trigger", true);
					$this->create_conditional_triggers();
					$this->create_update_triggers();
					$this->generic_sql_query("UPDATE Statstable SET Value = 51 WHERE Item = 'SchemaVer'", true);
					break;

				case 51:
					logger::log("SQL", "Updating FROM Schema version 51 TO Schema version 52");
					$this->generic_sql_query("ALTER TABLE Tracktable ADD isAudiobook TINYINT(1) UNSIGNED DEFAULT 0", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 52 WHERE Item = 'SchemaVer'", true);
					break;

				case 52:
					logger::log("SQL", "Updating FROM Schema version 52 TO Schema version 53");
					if (!$this->create_progress_triggers()) {
						$err = $this->mysqlc->errorInfo()[2];
						return array(false, "Error While Creating Triggers : ".$err);
					}
					$this->generic_sql_query("UPDATE Statstable SET Value = 53 WHERE Item = 'SchemaVer'", true);
					break;

				case 53:
					logger::log("SQL", "Updating FROM Schema version 53 TO Schema version 54");
					$this->generic_sql_query("UPDATE Statstable SET Value = 54 WHERE Item = 'SchemaVer'", true);
					break;

				case 54:
					logger::log("SQL", "Updating FROM Schema version 54 TO Schema version 55");
					$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('PodUpPid', 0)", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 55 WHERE Item = 'SchemaVer'", true);
					break;

				case 55:
					logger::log("SQL", "Updating FROM Schema version 55 TO Schema version 56");
					$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('Updating', '0')", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 56 WHERE Item = 'SchemaVer'", true);
					break;

				case 56:
					logger::log("SQL", "Updating FROM Schema version 56 TO Schema version 57");
					$this->generic_sql_query("UPDATE Statstable SET Value = 57 WHERE Item = 'SchemaVer'", true);
					break;

				case 57:
					logger::log("SQL", "Updating FROM Schema version 57 TO Schema version 58");
					$this->generic_sql_query("ALTER DATABASE romprdb CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci", true);
					logger::trace("SQL", " ... Modifying Tables");
					$this->generic_sql_query("ALTER TABLE Tracktable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Albumtable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Artisttable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Ratingtable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Progresstable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Tagtable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE TagListtable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Playcounttable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Podcasttable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE PodcastTracktable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE RadioStationtable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE RadioTracktable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE WishlistSourcetable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE AlbumsToListenTotable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE BackgroundImageTable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Statstable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					logger::trace("SQL", " ... Modifying Columns");
					$this->generic_sql_query("ALTER TABLE Tracktable MODIFY COLUMN Title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Tracktable MODIFY COLUMN Uri VARCHAR(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Albumtable MODIFY COLUMN Albumname VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Albumtable MODIFY COLUMN AlbumUri VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Albumtable MODIFY COLUMN ImgKey CHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Albumtable MODIFY COLUMN mbid CHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Albumtable MODIFY COLUMN Domain CHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Albumtable MODIFY COLUMN Image VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Artisttable MODIFY COLUMN Artistname VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Tagtable MODIFY COLUMN Name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Podcasttable MODIFY COLUMN FeedURL TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Podcasttable MODIFY COLUMN Image VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Podcasttable MODIFY COLUMN Title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Podcasttable MODIFY COLUMN Artist VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Podcasttable MODIFY COLUMN Description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE Podcasttable MODIFY COLUMN Category VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE PodcastTracktable MODIFY COLUMN Title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE PodcastTracktable MODIFY COLUMN Artist VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE PodcastTracktable MODIFY COLUMN Description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL", true);
					$this->generic_sql_query("ALTER TABLE PodcastTracktable MODIFY COLUMN Link TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE PodcastTracktable MODIFY COLUMN Guid TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE PodcastTracktable MODIFY COLUMN Localfilename VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE RadioStationtable MODIFY COLUMN StationName VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE RadioStationtable MODIFY COLUMN PlaylistUrl TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE RadioStationtable MODIFY COLUMN Image VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE RadioTracktable MODIFY COLUMN TrackUri TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE RadioTracktable MODIFY COLUMN PrettyStream TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE WishlistSourcetable MODIFY COLUMN SourceName VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE WishlistSourcetable MODIFY COLUMN SourceImage VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE WishlistSourcetable MODIFY COLUMN SourceUri TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE AlbumsToListenTotable MODIFY COLUMN JsonData TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE BackgroundImageTable MODIFY COLUMN Skin VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
					$this->generic_sql_query("ALTER TABLE BackgroundImageTable MODIFY COLUMN BrowserID VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL", true);
					$this->generic_sql_query("ALTER TABLE BackgroundImageTable MODIFY COLUMN Filename VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 58 WHERE Item = 'SchemaVer'", true);
					break;

				case 58:
					logger::log("SQL", "Updating FROM Schema version 58 TO Schema version 59");
					$this->update_remote_image_urls();
					$this->generic_sql_query("UPDATE Statstable SET Value = 59 WHERE Item = 'SchemaVer'", true);
					break;

				case 59:
					logger::log("SQL", "Updating FROM Schema version 59 TO Schema version 60");
					$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('BookArtists', '0')", true);
					$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('BookAlbums', '0')", true);
					$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('BookTracks', '0')", true);
					$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('BookTime', '0')", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 60 WHERE Item = 'SchemaVer'", true);
					break;

				case 60:
					logger::log("SQL", "Updating FROM Schema version 60 TO Schema version 61");
					$this->generic_sql_query("ALTER TABLE Tracktable ADD usedInPlaylist TINYINT(1) UNSIGNED DEFAULT 0", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 61 WHERE Item = 'SchemaVer'", true);
					break;

				case 61:
					logger::log("SQL", "Updating FROM Schema version 61 TO Schema version 62");
					$this->generic_sql_query("ALTER TABLE Albumtable ADD randomSort INT DEFAULT 0", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 62 WHERE Item = 'SchemaVer'", true);
					break;

				case 62:
					logger::log("SQL", "Updating FROM Schema version 62 TO Schema version 63");
					upgrade_saved_crazies();
					$this->generic_sql_query("UPDATE Statstable SET Value = 63 WHERE Item = 'SchemaVer'", true);
					break;

				case 63:
					logger::log("SQL", "Updating FROM Schema version 63 TO Schema version 64");
					// $this->generic_sql_query("INSERT INTO Genretable (Genre) VALUES ('None')", true);
					$this->generic_sql_query("ALTER TABLE Tracktable ADD Genreindex INT UNSIGNED DEFAULT 0", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 64 WHERE Item = 'SchemaVer'", true);
					break;

				case 64:
					logger::log("SQL", "Updating FROM Schema version 64 TO Schema version 65");
					$this->generic_sql_query("ALTER TABLE Tracktable ADD TYear YEAR", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 65 WHERE Item = 'SchemaVer'", true);
					break;

				case 65:
					logger::log("SQL", "Updating FROM Schema version 65 TO Schema version 66");
					$this->update_track_dates();
					$this->generic_sql_query("UPDATE Statstable SET Value = 66 WHERE Item = 'SchemaVer'", true);
					break;

				case 66:
					logger::log("SQL", "Updating FROM Schema version 66 TO Schema version 67");
					$this->generic_sql_query("ALTER TABLE Podcasttable ADD WriteTags TINYINT(1) DEFAULT 0", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 67 WHERE Item = 'SchemaVer'", true);
					break;

				case 67:
					logger::log("SQL", "Updating FROM Schema version 67 TO Schema version 68");
					prefs::upgrade_host_defs(68);
					$this->generic_sql_query("UPDATE Statstable SET Value = 68 WHERE Item = 'SchemaVer'", true);
					break;

				case 68:
					logger::log("SQL", "Updating FROM Schema version 68 TO Schema version 69");
					prefs::upgrade_host_defs(69);
					$this->generic_sql_query("UPDATE Statstable SET Value = 69 WHERE Item = 'SchemaVer'", true);
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
					$this->generic_sql_query("UPDATE Statstable SET Value = 70 WHERE Item = 'SchemaVer'", true);
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
					$this->generic_sql_query("UPDATE Statstable SET Value = 71 WHERE Item = 'SchemaVer'", true);
					break;

				case 71:
					logger::log("SQL", "Updating FROM Schema version 71 TO Schema version 72");
					$this->generic_sql_query("DROP TRIGGER IF EXISTS track_update_trigger", true);
					if (!$this->create_conditional_triggers()) {
						$err = $this->mysqlc->errorInfo()[2];
						return array(false, "Error While Creating Triggers : ".$err);
					}
					$this->generic_sql_query("UPDATE Statstable SET Value = 72 WHERE Item = 'SchemaVer'", true);
					break;

				case 72:
					logger::log("SQL", "Updating FROM Schema version 72 TO Schema version 73");
					$this->generic_sql_query("CREATE INDEX track_finder_index ON Tracktable (Uri(768))");
					$this->generic_sql_query("UPDATE Statstable SET Value = 73 WHERE Item = 'SchemaVer'", true);
					break;

				case 73:
					logger::log("SQL", "Updating FROM Schema version 73 TO Schema version 74");
					$this->generic_sql_query("ALTER TABLE Albumtable ADD useTrackIms TINYINT(1) DEFAULT 0", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 74 WHERE Item = 'SchemaVer'", true);
					break;

				case 74:
					logger::log("SQL", "Updating FROM Schema version 74 TO Schema version 75");
					$this->generic_sql_query("UPDATE Statstable SET Value = 75 WHERE Item = 'SchemaVer'", true);
					break;

				case 75:
					logger::log("SQL", "Updating FROM Schema version 75 TO Schema version 76");
					$this->generic_sql_query("DROP TRIGGER IF EXISTS track_update_trigger", true);
					if (!$this->create_conditional_triggers()) {
						$err = $this->mysqlc->errorInfo()[2];
						return array(false, "Error While Creating Triggers : ".$err);
					}
					$this->generic_sql_query("UPDATE Statstable SET Value = 76 WHERE Item = 'SchemaVer'", true);
					break;

				case 76:
					logger::log("SQL", "Updating FROM Schema version 76 TO Schema version 77");
					$this->generic_sql_query("ALTER TABLE BackgroundImageTable ADD Used TINYINT(1) DEFAULT 0", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 77 WHERE Item = 'SchemaVer'", true);
					break;

				case 77:
					logger::log("SQL", "Updating FROM Schema version 77 TO Schema version 78");
					logger::log("SQL", "This may take a long time");
					$this->generic_sql_query("DELETE FROM Playcounttable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable)", true);
					$this->generic_sql_query("RENAME TABLE Playcounttable TO _playcounts_old");
					$this->generic_sql_query(
						"CREATE TABLE Playcounttable(
							TTindex INT UNSIGNED NOT NULL REFERENCES Tracktable(TTindex) ON DELETE CASCADE,
							Playcount INT UNSIGNED NOT NULL,
							SyncCount INT UNSIGNED DEFAULT 0,
							LastPlayed TIMESTAMP,
						PRIMARY KEY (TTindex)) ENGINE=InnoDB"
					);
					$this->generic_sql_query(
						"INSERT INTO Playcounttable (TTindex, Playcount, SyncCount, LastPlayed)
						SELECT TTindex, Playcount, SyncCount, LastPlayed FROM _playcounts_old"
					);
					$this->generic_sql_query("DROP TABLE _playcounts_old");
					$this->generic_sql_query("UPDATE Statstable SET Value = 78 WHERE Item = 'SchemaVer'", true);
					break;

				case 78:
					logger::log("SQL", "Updating FROM Schema version 78 TO Schema version 79");
					logger::log("SQL", "This may take a long time");
					$this->generic_sql_query("DELETE FROM Ratingtable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable WHERE Hidden = 0)", true);
					$this->generic_sql_query("RENAME TABLE Ratingtable TO _ratings_old");
					$this->generic_sql_query(
						"CREATE TABLE Ratingtable(
						TTindex INT UNSIGNED NOT NULL REFERENCES Tracktable(TTindex) ON DELETE CASCADE,
						PRIMARY KEY(TTindex),
						Rating TINYINT(1) UNSIGNED) ENGINE=InnoDB"
					);
					$this->generic_sql_query(
						"INSERT INTO Ratingtable(TTindex, Rating)
						SELECT TTindex, Rating FROM _ratings_old"
					);
					$this->generic_sql_query("DROP TABLE _ratings_old");
					$this->generic_sql_query("UPDATE Statstable SET Value = 79 WHERE Item = 'SchemaVer'", true);
					break;

				case 79:
					logger::log("SQL", "Updating FROM Schema version 79 TO Schema version 80");
					logger::log("SQL", "This may take a long time");
					$this->generic_sql_query("DELETE FROM TagListtable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable WHERE Hidden = 0)", true);
					$this->generic_sql_query("RENAME TABLE TagListtable TO _taglist_old");
					$this->generic_sql_query(
						"CREATE TABLE TagListtable(
						Tagindex INT UNSIGNED NOT NULL REFERENCES Tagtable(Tagindex),
						TTindex INT UNSIGNED NOT NULL REFERENCES Tracktable(TTindex) ON DELETE CASCADE,
						PRIMARY KEY (Tagindex, TTindex)) ENGINE=InnoDB"
					);
					$this->generic_sql_query(
						"INSERT INTO TagListtable(Tagindex, TTindex)
						SELECT Tagindex, TTindex FROM _taglist_old"
					);
					$this->generic_sql_query("DROP TABLE _taglist_old");
					$this->generic_sql_query("UPDATE Statstable SET Value = 80 WHERE Item = 'SchemaVer'", true);
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
					$this->generic_sql_query("DROP TABLE Progresstable");
					$this->create_progress_triggers();
					$this->generic_sql_query("UPDATE Statstable SET Value = 81 WHERE Item = 'SchemaVer'", true);
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

					$this->generic_sql_query("ALTER TABLE PodcastTracktable DROP Progress");

					$this->generic_sql_query("UPDATE Statstable SET Value = 82 WHERE Item = 'SchemaVer'", true);
					break;

				case 82:
					// Fix a probme where Spotify tracks restored from a metadata backup get an album domain of local
					logger::log("SQL", "Updating FROM Schema version 82 TO Schema version 83");
					$this->generic_sql_query("UPDATE Albumtable SET domain = 'spotify' WHERE AlbumUri LIKE 'spotify:%'");
					$this->generic_sql_query("UPDATE Statstable SET Value = 83 WHERE Item = 'SchemaVer'", true);
					break;

				case 83:
					logger::log("SQL", "Updating FROM Schema version 83 TO Schema version 84");
					$this->generic_sql_query("ALTER TABLE PodcastTracktable ADD Image VARCHAR(255) DEFAULT NULL", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 84 WHERE Item = 'SchemaVer'", true);
					break;

				case 84:
					logger::log("SQL", "Updating FROM Schema version 84 TO Schema version 85");
					prefs::upgrade_host_defs(85);
					$this->generic_sql_query("UPDATE Statstable SET Value = 85 WHERE Item = 'SchemaVer'", true);
					break;

				case 85:
					logger::log("SQL", "Updating FROM Schema version 85 TO Schema version 86");
					$this->generic_sql_query("UPDATE Statstable SET Value = 86 WHERE Item = 'SchemaVer'", true);
					break;

				case 86:
					logger::log("SQL", "Updating FROM Schema version 86 TO Schema version 87");
					$this->generic_sql_query("UPDATE Statstable SET Value = 87 WHERE Item = 'SchemaVer'", true);
					break;

				case 87:
					logger::log("SQL", "Updating FROM Schema version 87 TO Schema version 88");
					$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('LastCache', ".time().")", true);
					$u = $this->simple_query('Value', 'Statstable', 'Item', 'Updating', null);
					if ($u === null)
						$this->generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('Updating', '0')", true);
					$this->generic_sql_query("UPDATE Statstable SET Value = 88 WHERE Item = 'SchemaVer'", true);
					break;

				case 88:
					logger::log("SQL", "Updating FROM Schema version 88 TO Schema version 89");
					$this->generic_sql_query("ALTER TABLE Podcasttable ADD NextUpdate INT UNSIGNED DEFAULT 0");
					$this->generic_sql_query("ALTER TABLE Podcasttable DROP LastUpdate");
					$podcasts = $this->generic_sql_query("SELECT * FROM Podcasttable");
					foreach($podcasts as $podcast) {
						$this->sql_prepare_query(true, null, null, null,
							"UPDATE Podcasttable SET NextUpdate = ? WHERE PODindex = ?",
							calculate_best_update_time($podcast),
							$podcast['PODindex']
						);
					}
					$this->generic_sql_query("UPDATE Statstable SET Value = 89 WHERE Item = 'SchemaVer'", true);
					break;

				case 89:
					logger::log("SQL", "Updating FROM Schema version 89 TO Schema version 90");
					$this->generic_sql_query("ALTER TABLE Podcasttable ADD UpRetry INT UNSIGNED DEFAULT 0");
					$this->generic_sql_query("UPDATE Statstable SET Value = 90 WHERE Item = 'SchemaVer'", true);
					break;

				case 90:
					logger::log("SQL", "Updating FROM Schema version 90 TO Schema version 91");
					prefs::upgrade_host_defs(91);
					$this->generic_sql_query("UPDATE Statstable SET Value = 91 WHERE Item = 'SchemaVer'", true);
					break;

				case 91:
					logger::log("SQL", "Updating FROM Schema version 91 TO Schema version 92");
					prefs::upgrade_host_defs(92);
					$this->generic_sql_query("UPDATE Statstable SET Value = 92 WHERE Item = 'SchemaVer'", true);
					break;

				case 92:
					logger::log("SQL", "Updating FROM Schema version 92 TO Schema version 93");
					// The Uri index, which we no longer want, might have been added at creation time
					// or it might have been added retrospectively. It'll have a different name in each case.
					$indices = $this->generic_sql_query("SHOW INDEX FROM Tracktable");
					foreach ($indices as $index) {
						if ($index['Column_name'] == 'Uri') {
							$this->sql_prepare_query(true, null, null, null,
								"DROP INDEX ? FROM Tracktable",
								$index['Key_name']
							);
						}
					}
					$this->generic_sql_query("UPDATE Statstable SET Value = 93 WHERE Item = 'SchemaVer'", true);
					break;

			}
			$sv++;
		}

		return array(true, "");
	}

	protected function create_conditional_triggers() {
		if ($this->trigger_not_exists('Tracktable', 'track_insert_trigger')) {
			$this->generic_sql_query("CREATE TRIGGER track_insert_trigger AFTER INSERT ON Tracktable
								FOR EACH ROW
								BEGIN
									IF (NEW.Hidden=0)
									THEN
									  UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = NEW.Albumindex;
									END IF;
								END;", true);
		}

		if ($this->trigger_not_exists('Tracktable', 'track_update_trigger')) {
			return $this->generic_sql_query("CREATE TRIGGER track_update_trigger AFTER UPDATE ON Tracktable
								FOR EACH ROW
								BEGIN
								IF (NEW.Hidden<>OLD.Hidden OR NEW.isAudiobook<>OLD.isAudiobook)
								THEN
									UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = NEW.Albumindex;
									UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = OLD.Albumindex;
								END IF;
								END;", true);
		}
		return true;

	}

	protected function create_playcount_triggers() {
		if ($this->trigger_not_exists('Playcounttable', 'syncupdatetrigger')) {
			$this->generic_sql_query("CREATE TRIGGER syncupdatetrigger BEFORE UPDATE ON Playcounttable
								FOR EACH ROW
								BEGIN
									IF (NEW.Playcount > OLD.Playcount)
									THEN
										SET NEW.SyncCount = OLD.SyncCount + 1;
									END IF;
								END;", true);
		}

		if ($this->trigger_not_exists('Playcounttable', 'syncinserttrigger')) {
			return $this->generic_sql_query("CREATE TRIGGER syncinserttrigger BEFORE INSERT ON Playcounttable
								FOR EACH ROW
								BEGIN
									SET NEW.SyncCount = 1;
								END;", true);
		}
		return true;
	}

	protected function create_update_triggers() {

		logger::log("MYSQL", "Creating Triggers for update operation");

		if ($this->trigger_not_exists('Ratingtable', 'rating_update_trigger')) {
			$this->generic_sql_query("CREATE TRIGGER rating_update_trigger AFTER UPDATE ON Ratingtable
								FOR EACH ROW
								BEGIN
								UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = NEW.TTindex);
								UPDATE Tracktable SET Hidden = 0, justAdded = 1 WHERE Hidden = 1 AND TTindex = NEW.TTindex;
								UPDATE Tracktable SET isSearchResult = 1, LastModified = NULL, justAdded = 1 WHERE isSearchResult > 1 AND TTindex = NEW.TTindex;
								END;", true);
		}

		if ($this->trigger_not_exists('Ratingtable', 'rating_insert_trigger')) {
			$this->generic_sql_query("CREATE TRIGGER rating_insert_trigger AFTER INSERT ON Ratingtable
								FOR EACH ROW
								BEGIN
								UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = NEW.TTindex);
								UPDATE Tracktable SET Hidden = 0, justAdded = 1 WHERE Hidden = 1 AND TTindex = NEW.TTindex;
								UPDATE Tracktable SET isSearchResult = 1, LastModified = NULL, justAdded = 1 WHERE isSearchResult > 1 AND TTindex = NEW.TTindex;
								END;", true);
		}

		if ($this->trigger_not_exists('Tagtable', 'tag_delete_trigger')) {
			$this->generic_sql_query("CREATE TRIGGER tag_delete_trigger AFTER DELETE ON Tagtable
								FOR EACH ROW
								BEGIN
								DELETE FROM TagListtable WHERE Tagindex = OLD.Tagindex;
								END;", true);
		}

		if ($this->trigger_not_exists('TagListtable', 'tag_insert_trigger')) {
			$this->generic_sql_query("CREATE TRIGGER tag_insert_trigger AFTER INSERT ON TagListtable
								FOR EACH ROW
								BEGIN
								UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = NEW.TTindex);
								UPDATE Tracktable SET Hidden = 0, justAdded = 1 WHERE Hidden = 1 AND TTindex = NEW.TTindex;
								UPDATE Tracktable SET isSearchResult = 1, LastModified = NULL, justAdded = 1 WHERE isSearchResult > 1 AND TTindex = NEW.TTindex;
								END;", true);
		}

		if ($this->trigger_not_exists('TagListtable', 'tag_remove_trigger')) {
			$this->generic_sql_query("CREATE TRIGGER tag_remove_trigger AFTER DELETE ON TagListtable
								FOR EACH ROW
								UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = OLD.TTindex);", true);
		}

		if ($this->trigger_not_exists('Tracktable', 'track_delete_trigger')) {
			return $this->generic_sql_query("CREATE TRIGGER track_delete_trigger AFTER DELETE ON Tracktable
								FOR EACH ROW
								UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = OLD.Albumindex;", true);
		}
		return true;
	}

	protected function create_progress_triggers() {

		if ($this->trigger_not_exists('Bookmarktable', 'bookmark_update_trigger')) {
			$this->generic_sql_query("CREATE TRIGGER bookmark_update_trigger AFTER UPDATE ON Bookmarktable
								FOR EACH ROW
								BEGIN
								UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = NEW.TTindex);
								END;", true);
		}

		if ($this->trigger_not_exists('Bookmarktable', 'bookmark_insert_trigger')) {
			return $this->generic_sql_query("CREATE TRIGGER bookmark_insert_trigger AFTER INSERT ON Bookmarktable
								FOR EACH ROW
								BEGIN
								UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = NEW.TTindex);
								END;", true);
		}
		return true;
	}

	private function trigger_not_exists($table, $trig) {
		// MySQL does not have CREATE TRIGGER IF NOT EXISTS
		$retval = true;
		logger::log('MYSQL', 'Checking triggers on',$table,'for',$trig);
		$r = $this->generic_sql_query("SHOW TRIGGERS LIKE '".$table."'");
		foreach ($r as $trigger) {
			logger::core('MYSQL', ' "'.$trigger['Trigger'].'"');
			if ($trigger['Trigger'] == $trig) {
				logger::core('MYSQL', '   Trigger Exists');
				$retval = false;
			}
		}
		return $retval;
	}

}

?>