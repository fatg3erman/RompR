<?php

define('SQL_RANDOM_SORT', 'RAND()');
define('SQL_TAG_CONCAT', "GROUP_CONCAT(t.Name SEPARATOR ', ') ");

function connect_to_database($sp = true) {
	global $mysqlc, $prefs;
	if ($mysqlc !== null) {
		logger::error("MYSQL", "AWOOOGA! ATTEMPTING MULTIPLE DATABASE CONNECTIONS!");
		return;
	}
	try {
		if (is_numeric($prefs['mysql_port'])) {
			logger::debug("SQL_CONNECT", "Connecting using hostname and port");
			$dsn = "mysql:host=".$prefs['mysql_host'].";port=".$prefs['mysql_port'].";dbname=".$prefs['mysql_database'].";charset=utf8mb4";
		} else {
			logger::debug("SQL_CONNECT", "Connecting using unix socket");
			$dsn = "mysql:unix_socket=".$prefs['mysql_port'].";dbname=".$prefs['mysql_database'].";charset=utf8mb4";
		}
		$mysqlc = new PDO($dsn, $prefs['mysql_user'], $prefs['mysql_password']);
		logger::debug("SQL_CONNECT", "Connected to MySQL");
		// generic_sql_query("SET NAMES utf8mb4", true);
		generic_sql_query('SET SESSION sql_mode="STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION"', true);
		readCollectionPlayer($sp);
	} catch (Exception $e) {
		logger::fail("SQL_CONNECT", "Database connect failure - ".$e);
		sql_init_fail($e->getMessage());
	}
}

function close_database() {
	global $mysqlc;
	$mysqlc = null;
}

function check_sql_tables() {
	global $mysqlc, $prefs;
	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Tracktable(".
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
		"INDEX(Albumindex), ".
		"INDEX(Title), ".
		"INDEX(TrackNo)) ENGINE=InnoDB", true))
	{
		logger::log("MYSQL_CONNECT", "  Tracktable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Tracktable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Albumtable(".
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
		"justUpdated TINYINT(1) UNSIGNED DEFAULT 1, ".
		"INDEX(Albumname), ".
		"INDEX(AlbumArtistindex), ".
		"INDEX(Domain), ".
		"INDEX(ImgKey)) ENGINE=InnoDB", true))
	{
		logger::log("MYSQL_CONNECT", "  Albumtable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Albumtable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Artisttable(".
		"Artistindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
		"PRIMARY KEY(Artistindex), ".
		"Artistname VARCHAR(255), ".
		"INDEX(Artistname)) ENGINE=InnoDB", true))
	{
		logger::log("MYSQL_CONNECT", "  Artisttable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Artisttable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Ratingtable(".
		"TTindex INT UNSIGNED, ".
		"PRIMARY KEY(TTindex), ".
		"Rating TINYINT(1) UNSIGNED) ENGINE=InnoDB", true))
	{
		logger::log("MYSQL_CONNECT", "  Ratingtable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Ratingtable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Progresstable(".
		"TTindex INT UNSIGNED, ".
		"PRIMARY KEY(TTindex), ".
		"Progress INT UNSIGNED) ENGINE=InnoDB", true))
	{
		logger::log("MYSQL_CONNECT", "  Progresstable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Progresstable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Tagtable(".
		"Tagindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
		"PRIMARY KEY(Tagindex), ".
		"Name VARCHAR(255)) ENGINE=InnoDB", true))
	{
		logger::log("MYSQL_CONNECT", "  Tagtable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Tagtable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS TagListtable(".
		"Tagindex INT UNSIGNED NOT NULL REFERENCES Tagtable(Tagindex), ".
		"TTindex INT UNSIGNED NOT NULL REFERENCES Tracktable(TTindex), ".
		"PRIMARY KEY (Tagindex, TTindex)) ENGINE=InnoDB", true))
	{
		logger::log("MYSQL_CONNECT", "  TagListtable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking TagListtable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Playcounttable(".
		"TTindex INT UNSIGNED NOT NULL REFERENCES Tracktable(TTindex), ".
		"Playcount INT UNSIGNED NOT NULL, ".
		"SyncCount INT UNSIGNED DEFAULT 0, ".
		"LastPlayed TIMESTAMP, ".
		"PRIMARY KEY (TTindex)) ENGINE=InnoDB", true))
	{
		logger::log("MYSQL_CONNECT", "  Playcounttable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Playcounttable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Podcasttable(".
		"PODindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
		"FeedURL TEXT, ".
		"LastUpdate INT UNSIGNED, ".
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
		"Category VARCHAR(255) NOT NULL, ".
		"PRIMARY KEY (PODindex)) ENGINE=InnoDB", true))
	{
		logger::log("MYSQL_CONNECT", "  Podcasttable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Podcasttable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS PodcastTracktable(".
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
		"Progress INT UNSIGNED DEFAULT 0, ".
		"INDEX (PODindex), ".
		"PRIMARY KEY (PODTrackindex), ".
		"INDEX (Title)) ENGINE=InnoDB", true))
	{
		logger::log("MYSQL_CONNECT", "  PodcastTracktable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking PodcastTracktable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS RadioStationtable(".
		"Stationindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
		"Number SMALLINT UNSIGNED DEFAULT 65535, ".
		"IsFave TINYINT(1), ".
		"StationName VARCHAR(255), ".
		"PlaylistUrl TEXT, ".
		"Image VARCHAR(255), ".
		"PRIMARY KEY (Stationindex)) ENGINE=InnoDB", true))
	{
		logger::log("MYSQL_CONNECT", "  RadioStationtable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking RadioStationtable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS RadioTracktable(".
		"Trackindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
		"Stationindex INT UNSIGNED REFERENCES RadioStationtable(Stationindex), ".
		"TrackUri TEXT, ".
		"PrettyStream TEXT, ".
		"PRIMARY KEY (Trackindex)) ENGINE=InnoDB", true))
	{
		logger::log("MYSQL_CONNECT", "  RadioTracktable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking RadioTracktable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS WishlistSourcetable(".
		"Sourceindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
		"SourceName VARCHAR(255), ".
		"SourceImage VARCHAR(255), ".
		"SourceUri TEXT, ".
		"PRIMARY KEY (Sourceindex)) ENGINE=InnoDB", true))
	{
		logger::log("MYSQL_CONNECT", "  WishlistSourcetable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking WishlistSourcetable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS AlbumsToListenTotable(".
		"Listenindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
		"JsonData TEXT, ".
		"PRIMARY KEY (Listenindex)) ENGINE=InnoDB", true))
	{
		logger::log("MYSQL_CONNECT", "  AlbumsToListenTotabletable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking AlbumsToListenTotable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS BackgroundImageTable(".
		"BgImageIndex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
		"Skin VARCHAR(255), ".
		"BrowserID VARCHAR(20) DEFAULT NULL, ".
		"Filename VARCHAR(255), ".
		"Orientation TINYINT(2), ".
		"PRIMARY KEY (BgImageIndex), ".
		"INDEX (Skin), ".
		"INDEX (BrowserID)) ENGINE=InnoDB", true))
	{
		logger::log("MYSQL_CONNECT", "  BackgounrdImageTable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking BackgroundImageTable : ".$err);
	}

	if (!generic_sql_query("CREATE TABLE IF NOT EXISTS Statstable(Item CHAR(11), PRIMARY KEY(Item), Value INT UNSIGNED) ENGINE=InnoDB", true)) {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Statstable : ".$err);
	}
	// Check schema version and update tables as necessary
	$sv = simple_query('Value', 'Statstable', 'Item', 'SchemaVer', 0);
	if ($sv == 0) {
		logger::log("SQL_CONNECT", "No Schema Version Found - initialising table");
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('ListVersion', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('ArtistCount', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('AlbumCount', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('TrackCount', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('TotalTime', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('CollType', '999')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('SchemaVer', '".ROMPR_SCHEMA_VERSION."')", true);
		$sv = ROMPR_SCHEMA_VERSION;
		logger::log("MYSQL_CONNECT", "Statstable populated");
		create_update_triggers();
		create_conditional_triggers();
		create_playcount_triggers();
	}

	if ($sv > ROMPR_SCHEMA_VERSION) {
		logger::log("MYSQL_CONNECT", "Schema Mismatch! We are version ".ROMPR_SCHEMA_VERSION." but database is version ".$sv);
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
				generic_sql_query("ALTER TABLE Albumtable DROP Directory", true);
				generic_sql_query("UPDATE Statstable SET Value = 2 WHERE Item = 'SchemaVer'", true);
				break;

			case 2:
				logger::log("SQL", "Updating FROM Schema version 2 TO Schema version 3");
				generic_sql_query("ALTER TABLE Tracktable ADD Hidden TINYINT(1) UNSIGNED DEFAULT 0", true);
				generic_sql_query("UPDATE Tracktable SET Hidden = 0 WHERE Hidden IS NULL", true);
				generic_sql_query("UPDATE Statstable SET Value = 3 WHERE Item = 'SchemaVer'", true);
				break;

			case 3:
				logger::log("SQL", "Updating FROM Schema version 3 TO Schema version 4");
				generic_sql_query("UPDATE Tracktable SET Disc = 1 WHERE Disc IS NULL OR Disc = 0", true);
				generic_sql_query("UPDATE Statstable SET Value = 4 WHERE Item = 'SchemaVer'", true);
				break;

			case 4:
				logger::log("SQL", "Updating FROM Schema version 4 TO Schema version 5");
				generic_sql_query("UPDATE Albumtable SET Searched = 0 WHERE Image NOT LIKE 'albumart%'", true);
				generic_sql_query("ALTER TABLE Albumtable DROP Image", true);
				generic_sql_query("UPDATE Statstable SET Value = 5 WHERE Item = 'SchemaVer'", true);
				break;

			case 5:
				logger::log("SQL", "Updating FROM Schema version 5 TO Schema version 6");
				generic_sql_query("DROP INDEX Disc on Tracktable", true);
				generic_sql_query("UPDATE Statstable SET Value = 6 WHERE Item = 'SchemaVer'", true);
				break;

			case 6:
				logger::log("SQL", "Updating FROM Schema version 6 TO Schema version 7");
				// This was going to be a nice datestamp but newer versions of mysql don't work that way
				generic_sql_query("ALTER TABLE Tracktable ADD DateAdded TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", true);
				generic_sql_query("UPDATE Tracktable SET DateAdded = FROM_UNIXTIME(LastModified) WHERE LastModified IS NOT NULL AND LastModified > 0", true);
				generic_sql_query("UPDATE Statstable SET Value = 7 WHERE Item = 'SchemaVer'", true);
				break;

			case 7:
				logger::log("SQL", "Updating FROM Schema version 7 TO Schema version 8");
				// Since we've changed the way we're joining artist names together,
				// rather than force the database to be recreated and screw up everyone's
				// tags and rating, just modify the artist data.
				$stmt = sql_prepare_query_later("UPDATE Artisttable SET Artistname = ? WHERE Artistindex = ?");
				if ($stmt !== FALSE) {
					$result = generic_sql_query("SELECT * FROM Artisttable", false, PDO::FETCH_OBJ);
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
				generic_sql_query("UPDATE Statstable SET Value = 8 WHERE Item = 'SchemaVer'", true);
				$stmt = null;
				break;

			case 8:
				logger::log("SQL", "Updating FROM Schema version 8 TO Schema version 9");
				// We removed the image column earlier, but I've decided we need it again
				// because some mopidy backends supply images and archiving them all makes
				// creating the collection take waaaaay too long.
				generic_sql_query("ALTER TABLE Albumtable ADD Image VARCHAR(255)", true);
				// So we now need to recreate the image database. Sadly this means that people using Beets will lose their album images.
				$result = generic_sql_query("SELECT Albumindex, ImgKey FROM Albumtable", false, PDO::FETCH_OBJ);
				foreach ($result as $obj) {
					if (file_exists('albumart/small/'.$obj->ImgKey.'.jpg')) {
						generic_sql_query("UPDATE Albumtable SET Image = 'albumart/small/".$obj->ImgKey.".jpg', Searched = 1 WHERE Albumindex = ".$obj->Albumindex, true);
					} else {
						generic_sql_query("UPDATE Albumtable SET Image = '', Searched = 0 WHERE Albumindex = ".$obj->Albumindex, true);
					}
				}
				generic_sql_query("UPDATE Statstable SET Value = 9 WHERE Item = 'SchemaVer'", true);
			    break;

			case 9:
				logger::log("SQL", "Updating FROM Schema version 9 TO Schema version 10");
				generic_sql_query("ALTER TABLE Albumtable DROP NumDiscs", true);
				generic_sql_query("UPDATE Statstable SET Value = 10 WHERE Item = 'SchemaVer'", true);
				break;

			case 10:
				logger::log("SQL", "Updating FROM Schema version 10 TO Schema version 11");
				generic_sql_query("ALTER TABLE Albumtable DROP IsOneFile", true);
				generic_sql_query("UPDATE Statstable SET Value = 11 WHERE Item = 'SchemaVer'", true);
				break;

			case 11:
				logger::log("SQL", "Updating FROM Schema version 11 TO Scheme version 12");
				generic_sql_query("ALTER TABLE Tracktable ADD isSearchResult TINYINT(1) UNSIGNED DEFAULT 0", true);
				generic_sql_query("UPDATE Statstable SET Value = 12 WHERE Item = 'SchemaVer'", true);
				break;

			case 12:
				logger::log("SQL", "Updating FROM Schema version 12 TO Scheme version 13");
				generic_sql_query("ALTER TABLE Albumtable CHANGE Spotilink AlbumUri VARCHAR(255)", true);
				generic_sql_query("UPDATE Statstable SET Value = 13 WHERE Item = 'SchemaVer'", true);
				break;

			case 13:
				logger::log("SQL", "Updating FROM Schema version 13 TO Scheme version 14");
				// Nothing to do here, this is for SQLite only.
				generic_sql_query("UPDATE Statstable SET Value = 14 WHERE Item = 'SchemaVer'", true);
				break;

			case 14:
				logger::log("SQL", "Updating FROM Schema version 14 TO Scheme version 15");
				generic_sql_query("ALTER TABLE Tracktable MODIFY LastModified CHAR(32)", true);
				generic_sql_query("UPDATE Statstable SET Value = 15 WHERE Item = 'SchemaVer'", true);
				break;

			case 15:
				logger::log("SQL", "Updating FROM Schema version 15 TO Schema version 16");
				albumImageBuggery();
				generic_sql_query("UPDATE Statstable SET Value = 16 WHERE Item = 'SchemaVer'", true);
				break;

			case 16:
				logger::log("SQL", "Updating FROM Schema version 16 TO Schema version 17");
				// Early MPD versions had LastModified as an integer value. They changed it to a datestamp some time ago but I didn't notice
				$r = generic_sql_query("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Tracktable' AND COLUMN_NAME = 'LastModified'");
				foreach ($r as $obj) {
					logger::log("MYSQL_INIT", "Data Type of LastModified is ".$obj['DATA_TYPE']);
					if ($obj['DATA_TYPE'] == 'int') {
						logger::log("MYSQL_INIT", "Modifying Tracktable");
						generic_sql_query("ALTER TABLE Tracktable ADD LM CHAR(32)", true);
						generic_sql_query("UPDATE Tracktable SET LM = LastModified", true);
						generic_sql_query("ALTER TABLE Tracktable DROP LastModified", true);
						generic_sql_query("ALTER TABLE Tracktable CHANGE LM LastModified CHAR(32)", true);
					}
				}
				generic_sql_query("UPDATE Statstable SET Value = 17 WHERE Item = 'SchemaVer'", true);
				break;

			case 17:
				logger::log("SQL", "Updating FROM Schema version 17 TO Schema version 18");
				include("utils/podcastupgrade.php");
				generic_sql_query("UPDATE Statstable SET Value = 18 WHERE Item = 'SchemaVer'", true);
				break;

			case 18:
				logger::log("SQL", "Updating FROM Schema version 18 TO Schema version 19");
				$result = generic_sql_query('SELECT Tracktable.Uri AS uri, Tracktable.TTindex, Tracktable.Title AS ttit, Albumtable.*, Trackimagetable.Image AS ti FROM Tracktable JOIN Albumtable USING (Albumindex) LEFT JOIN Trackimagetable USING (TTindex) WHERE Tracktable.Uri LIKE "soundcloud:%"', false, PDO::FETCH_OBJ);
				foreach ($result as $obj) {
					logger::log("SQL", "  Creating new Album ".$obj->ttit." Image ".$obj->ti);
					$ti = $obj->ti;
					if (preg_match('/^http/', $ti)) {
						$ti = 'getRemoteImage.php?url='.$ti;
					}
					if (sql_prepare_query(true, null, null, null,
						"INSERT INTO Albumtable
							(Albumname, AlbumArtistindex, AlbumUri, Year, Searched, ImgKey, mbid, Domain, Image)
						VALUES
							(?, ?, ?, ?, ?, ?, ?, ?, ?)",
							$obj->ttit, $obj->AlbumArtistindex, $obj->uri, $obj->Year, $obj->Searched, $obj->ImgKey, $obj->mbid, $obj->Domain, $ti
						)) {
							$retval = $mysqlc->lastInsertId();
							logger::log("SQL", "    .. success, Albumindex ".$retval);
							generic_sql_query("UPDATE Tracktable SET Albumindex = ".$retval." WHERE TTindex = ".$obj->TTindex, true);
					} else {
						logger::log("SQL", "    .. ERROR!");
					}
				}
				generic_sql_query("UPDATE Statstable SET Value = 19 WHERE Item = 'SchemaVer'", true);
				break;

			case 19:
				logger::log("SQL", "Updating FROM Schema version 19 TO Schema version 20");
				$result = generic_sql_query('SELECT Tracktable.Uri AS uri, Tracktable.TTindex, Tracktable.Title AS ttit, Albumtable.*, Trackimagetable.Image AS ti FROM Tracktable JOIN Albumtable USING (Albumindex) LEFT JOIN Trackimagetable USING (TTindex) WHERE Tracktable.Uri LIKE "youtube:%"', false, PDO::FETCH_OBJ);
				foreach ($result as $obj) {
					logger::log("SQL", "  Creating new Album ".$obj->ttit." Image ".$obj->ti);
					$ti = $obj->ti;
					if (preg_match('/^http/', $ti)) {
						$ti = 'getRemoteImage.php?url='.$ti;
					}
					if (sql_prepare_query(true, null, null, null,
						"INSERT INTO Albumtable
							(Albumname, AlbumArtistindex, AlbumUri, Year, Searched, ImgKey, mbid, Domain, Image)
						VALUES
							(?, ?, ?, ?, ?, ?, ?, ?, ?)",
							$obj->ttit, $obj->AlbumArtistindex, $obj->uri, $obj->Year, $obj->Searched, $obj->ImgKey, $obj->mbid, $obj->Domain, $ti
						)) {
							$retval = $mysqlc->lastInsertId();
							logger::log("SQL", "    .. success, Albumindex ".$retval);
							generic_sql_query("UPDATE Tracktable SET Albumindex = ".$retval." WHERE TTindex = ".$obj->TTindex, true);
					} else {
						logger::error("SQL", "    .. ERROR!");
					}
				}
				generic_sql_query("UPDATE Statstable SET Value = 20 WHERE Item = 'SchemaVer'", true);
				break;

			case 20:
				logger::log("SQL", "Updating FROM Schema version 20 TO Schema version 21");
				generic_sql_query("DROP TABLE Trackimagetable", true);
				generic_sql_query("UPDATE Statstable SET Value = 21 WHERE Item = 'SchemaVer'", true);
				break;

			case 21:
				logger::log("SQL", "Updating FROM Schema version 21 TO Schema version 22");
				generic_sql_query("ALTER TABLE Playcounttable ADD LastPlayed TIMESTAMP NULL", true);
				generic_sql_query("UPDATE Statstable SET Value = 22 WHERE Item = 'SchemaVer'", true);
				break;

			case 22:
				logger::log("SQL", "Updating FROM Schema version 22 TO Schema version 23");
				generic_sql_query("ALTER TABLE Podcasttable ADD Version TINYINT(2)", true);
				generic_sql_query("ALTER TABLE PodcastTracktable ADD Guid VARCHAR(2000)", true);
				generic_sql_query("ALTER TABLE PodcastTracktable ADD Localfilename VARCHAR(255)", true);
				generic_sql_query("UPDATE Podcasttable SET Version = 1 WHERE PODindex IS NOT NULL", true);
				generic_sql_query("UPDATE Statstable SET Value = 23 WHERE Item = 'SchemaVer'", true);
				break;

			case 23:
				logger::log("SQL", "Updating FROM Schema version 23 TO Schema version 24");
				generic_sql_query("ALTER TABLE Tracktable CHANGE DateAdded DateAdded TIMESTAMP DEFAULT CURRENT_TIMESTAMP", true);
				generic_sql_query("UPDATE Statstable SET Value = 24 WHERE Item = 'SchemaVer'", true);
				break;

			case 24:
				logger::log("SQL", "Updating FROM Schema version 24 TO Schema version 25");
				generic_sql_query("ALTER DATABASE romprdb CHARACTER SET utf8 COLLATE utf8_unicode_ci", true);
				generic_sql_query("UPDATE Statstable SET Value = 25 WHERE Item = 'SchemaVer'", true);
				break;

			case 25:
				logger::log("SQL", "Updating FROM Schema version 25 TO Schema version 26");
				generic_sql_query("ALTER TABLE Tracktable ADD justAdded TINYINT(1) UNSIGNED DEFAULT 1", true);
				generic_sql_query("UPDATE Statstable SET Value = 26 WHERE Item = 'SchemaVer'", true);
				break;

			case 26:
				logger::log("SQL", "Updating FROM Schema version 26 TO Schema version 27");
				generic_sql_query("ALTER TABLE Albumtable ADD justUpdated TINYINT(1) UNSIGNED DEFAULT 1", true);
				generic_sql_query("UPDATE Statstable SET Value = 27 WHERE Item = 'SchemaVer'", true);
				break;

			case 27:
				logger::log("SQL", "Updating FROM Schema version 27 TO Schema version 28");
				rejig_wishlist_tracks();
				generic_sql_query("UPDATE Statstable SET Value = 28 WHERE Item = 'SchemaVer'", true);
				break;

			case 28:
				logger::log("SQL", "Updating FROM Schema version 28 TO Schema version 29");
				create_update_triggers();
				generic_sql_query("UPDATE Statstable SET Value = 29 WHERE Item = 'SchemaVer'", true);
				break;

			case 29:
				logger::log("SQL", "Updating FROM Schema version 29 TO Schema version 30");
				include('utils/radioupgrade.php');
				generic_sql_query("UPDATE Statstable SET Value = 30 WHERE Item = 'SchemaVer'", true);
				break;

			case 30:
				logger::log("SQL", "Updating FROM Schema version 30 TO Schema version 31");
				generic_sql_query("ALTER TABLE PodcastTracktable CHANGE Description Description TEXT", true);
				generic_sql_query("ALTER TABLE PodcastTracktable CHANGE Link Link TEXT", true);
				generic_sql_query("ALTER TABLE PodcastTracktable CHANGE Guid Guid TEXT", true);
				generic_sql_query("ALTER TABLE Podcasttable CHANGE FeedURL FeedURL TEXT", true);
				generic_sql_query("ALTER TABLE Podcasttable CHANGE Description Description TEXT", true);
				generic_sql_query("ALTER TABLE Tracktable CHANGE Uri Uri TEXT", true);
				generic_sql_query("UPDATE Statstable SET Value = 31 WHERE Item = 'SchemaVer'", true);
				break;

			case 31:
				logger::log("SQL", "Updating FROM Schema version 31 TO Schema version 32");
				generic_sql_query("ALTER TABLE Podcasttable ADD Subscribed TINYINT(1) NOT NULL DEFAULT 1", true);
				generic_sql_query("UPDATE Statstable SET Value = 32 WHERE Item = 'SchemaVer'", true);
				break;

			case 32:
				logger::log("SQL", "Updating FROM Schema version 32 TO Schema version 33");
				generic_sql_query("DROP TRIGGER IF EXISTS track_insert_trigger", true);
				generic_sql_query("DROP TRIGGER IF EXISTS track_update_trigger", true);
				create_conditional_triggers();
				generic_sql_query("UPDATE Statstable SET Value = 33 WHERE Item = 'SchemaVer'", true);
				break;

			case 33:
				logger::log("SQL", "Updating FROM Schema version 33 TO Schema version 34");
				generic_sql_query("ALTER TABLE Albumtable ADD ImgVersion INT UNSIGNED DEFAULT ".ROMPR_IMAGE_VERSION, true);
				generic_sql_query("UPDATE Albumtable SET ImgVersion = 1",true);
				generic_sql_query("UPDATE Statstable SET Value = 34 WHERE Item = 'SchemaVer'", true);
				break;

			case 34:
				logger::log("SQL", "Updating FROM Schema version 34 TO Schema version 35");
				generic_sql_query("ALTER TABLE Tracktable ADD Sourceindex INT UNSIGNED DEFAULT NULL", true);
				generic_sql_query("UPDATE Statstable SET Value = 35 WHERE Item = 'SchemaVer'", true);
				break;

			case 35:
				generic_sql_query("UPDATE Statstable SET Value = 36 WHERE Item = 'SchemaVer'", true);
				break;

			case 36:
				logger::log("SQL", "Updating FROM Schema version 35 TO Schema version 37");
				$localpods = generic_sql_query("SELECT PODTrackindex, PODindex, LocalFilename FROM PodcastTracktable WHERE LocalFilename IS NOT NULL");
				foreach ($localpods as $pod) {
					sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET LocalFilename = ? WHERE PODTrackindex = ?", '/prefs/podcasts/'.$pod['PODindex'].'/'.$pod['PODTrackindex'].'/'.$pod['LocalFilename'], $pod['PODTrackindex']);
				}
				generic_sql_query("UPDATE Statstable SET Value = 37 WHERE Item = 'SchemaVer'", true);
				break;

			case 37:
				logger::log("SQL", "Updating FROM Schema version 37 TO Schema version 38");
				generic_sql_query("ALTER TABLE Albumtable MODIFY ImgVersion INT UNSIGNED DEFAULT ".ROMPR_IMAGE_VERSION, true);
				generic_sql_query("UPDATE Statstable SET Value = 38 WHERE Item = 'SchemaVer'", true);
				break;

			case 38:
				logger::log("SQL", "Updating FROM Schema version 38 TO Schema version 39");
				generic_sql_query("ALTER TABLE Podcasttable ADD LastPubDate INT UNSIGNED DEFAULT NULL", true);
				generic_sql_query("CREATE INDEX ptt ON PodcastTracktable (Title)", true);
				require_once('podcasts/podcastfunctions.php');
				upgrade_podcasts_to_version();
				generic_sql_query("UPDATE Statstable SET Value = 39 WHERE Item = 'SchemaVer'", true);
				break;

			case 39:
				logger::log("SQL", "Updating FROM Schema version 39 TO Schema version 40");
				// Takes too long. It'll happen when they get refreshed anyway.
				// require_once('podcasts/podcastfunctions.php');
				// upgrade_podcast_images();
				generic_sql_query("UPDATE Statstable SET Value = 40 WHERE Item = 'SchemaVer'", true);
				break;

			case 40:
				logger::log("SQL", "Updating FROM Schema version 40 TO Schema version 41");
				generic_sql_query("ALTER TABLE Podcasttable ADD Category VARCHAR(255) NOT NULL", true);
				generic_sql_query("UPDATE Statstable SET Value = 41 WHERE Item = 'SchemaVer'", true);
				break;

			case 41:
				logger::log("SQL", "Updating FROM Schema version 41 TO Schema version 42");
				generic_sql_query("ALTER TABLE PodcastTracktable ADD Progress INT UNSIGNED DEFAULT 0", true);
				generic_sql_query("UPDATE Statstable SET Value = 42 WHERE Item = 'SchemaVer'", true);
				break;

			case 42:
				logger::log("SQL", "Updating FROM Schema version 42 TO Schema version 43");
				update_stream_images(43);
				generic_sql_query("UPDATE Statstable SET Value = 43 WHERE Item = 'SchemaVer'", true);
				break;

			case 43:
				logger::log("SQL", "Updating FROM Schema version 43 TO Schema version 44");
				empty_modified_cache_dirs(44);
				generic_sql_query("UPDATE Statstable SET Value = 44 WHERE Item = 'SchemaVer'", true);
				break;

			case 44:
				logger::log("SQL", "Updating FROM Schema version 44 TO Schema version 45");
				upgrade_host_defs(45);
				generic_sql_query("UPDATE Statstable SET Value = 45 WHERE Item = 'SchemaVer'", true);
				break;

			case 45:
				logger::log("SQL", "Updating FROM Schema version 45 TO Schema version 46");
				generic_sql_query("UPDATE Statstable SET Value = 46 WHERE Item = 'SchemaVer'", true);
				break;

			case 46:
				logger::log("SQL", "Updating FROM Schema version 46 TO Schema version 47");
				generic_sql_query("ALTER TABLE Playcounttable ADD SyncCount INT UNSIGNED DEFAULT 0", true);
				generic_sql_query("UPDATE Statstable SET Value = 47 WHERE Item = 'SchemaVer'", true);
				create_playcount_triggers();
				break;

			case 47:
				logger::log("SQL", "Updating FROM Schema version 47 TO Schema version 48");
				// Some versions had a default value and an on update for LastPlayed, which is WRONG and fucks things up
				generic_sql_query("ALTER TABLE Playcounttable ALTER LastPlayed DROP DEFAULT", true);
				generic_sql_query("ALTER TABLE Playcounttable CHANGE LastPlayed LastPlayed TIMESTAMP NULL", true);
				generic_sql_query("UPDATE Statstable SET Value = 48 WHERE Item = 'SchemaVer'", true);
				break;

			case 48:
				logger::log("SQL", "Updating FROM Schema version 48 TO Schema version 49");
				upgrade_host_defs(49);
				generic_sql_query("UPDATE Statstable SET Value = 49 WHERE Item = 'SchemaVer'", true);
				break;

			case 49:
				logger::log("SQL", "Updating FROM Schema version 49 TO Schema version 50");
				generic_sql_query("ALTER TABLE Tracktable ADD LinkChecked TINYINT(1) UNSIGNED DEFAULT 0", true);
				generic_sql_query("UPDATE Statstable SET Value = 50 WHERE Item = 'SchemaVer'", true);
				break;

			case 50:
				logger::log("SQL", "Updating FROM Schema version 50 TO Schema version 51");
				// Something wierd happened and I lost half my triggers. In case it happens to anyone else...
				generic_sql_query("DROP TRIGGER IF EXISTS track_insert_trigger", true);
				generic_sql_query("DROP TRIGGER IF EXISTS track_update_trigger", true);
				generic_sql_query("DROP TRIGGER IF EXISTS rating_update_trigger", true);
				generic_sql_query("DROP TRIGGER IF EXISTS rating_insert_trigger", true);
				generic_sql_query("DROP TRIGGER IF EXISTS tag_delete_trigger", true);
				generic_sql_query("DROP TRIGGER IF EXISTS tag_insert_trigger", true);
				generic_sql_query("DROP TRIGGER IF EXISTS tag_remove_trigger", true);
				generic_sql_query("DROP TRIGGER IF EXISTS track_delete_trigger", true);
				create_conditional_triggers();
				create_update_triggers();
				generic_sql_query("UPDATE Statstable SET Value = 51 WHERE Item = 'SchemaVer'", true);
				break;

			case 51:
				logger::log("SQL", "Updating FROM Schema version 51 TO Schema version 52");
				generic_sql_query("ALTER TABLE Tracktable ADD isAudiobook TINYINT(1) UNSIGNED DEFAULT 0", true);
				generic_sql_query("UPDATE Statstable SET Value = 52 WHERE Item = 'SchemaVer'", true);
				break;

			case 52:
				logger::log("SQL", "Updating FROM Schema version 52 TO Schema version 53");
				create_progress_triggers();
				generic_sql_query("UPDATE Statstable SET Value = 53 WHERE Item = 'SchemaVer'", true);
				break;

			case 53:
				logger::log("SQL", "Updating FROM Schema version 53 TO Schema version 54");
				require_once ('utils/backgroundimages.php');
				first_upgrade_of_user_backgrounds();
				generic_sql_query("UPDATE Statstable SET Value = 54 WHERE Item = 'SchemaVer'", true);
				break;

			case 54:
				logger::log("SQL", "Updating FROM Schema version 54 TO Schema version 55");
				generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('PodUpPid', 0)", true);
				generic_sql_query("UPDATE Statstable SET Value = 55 WHERE Item = 'SchemaVer'", true);
				break;

			case 55:
				logger::log("SQL", "Updating FROM Schema version 55 TO Schema version 56");
				generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('Updating', '0')", true);
				generic_sql_query("UPDATE Statstable SET Value = 56 WHERE Item = 'SchemaVer'", true);
				break;

			case 56:
				logger::log("SQL", "Updating FROM Schema version 56 TO Schema version 57");
				generic_sql_query("UPDATE Statstable SET Value = 57 WHERE Item = 'SchemaVer'", true);
				break;

			case 57:
				logger::log("SQL", "Updating FROM Schema version 57 TO Schema version 58");
				generic_sql_query("ALTER DATABASE romprdb CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci", true);
				logger::log("SQL", " ... Modifying Tables");
				generic_sql_query("ALTER TABLE Tracktable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Albumtable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Artisttable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Ratingtable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Progresstable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Tagtable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE TagListtable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Playcounttable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Podcasttable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE PodcastTracktable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE RadioStationtable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE RadioTracktable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE WishlistSourcetable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE AlbumsToListenTotable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE BackgroundImageTable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Statstable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				logger::log("SQL", " ... Modifying Columns");
				generic_sql_query("ALTER TABLE Tracktable MODIFY COLUMN Title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Tracktable MODIFY COLUMN Uri VARCHAR(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Albumtable MODIFY COLUMN Albumname VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Albumtable MODIFY COLUMN AlbumUri VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Albumtable MODIFY COLUMN ImgKey CHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Albumtable MODIFY COLUMN mbid CHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Albumtable MODIFY COLUMN Domain CHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Albumtable MODIFY COLUMN Image VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Artisttable MODIFY COLUMN Artistname VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Tagtable MODIFY COLUMN Name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Podcasttable MODIFY COLUMN FeedURL TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Podcasttable MODIFY COLUMN Image VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Podcasttable MODIFY COLUMN Title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Podcasttable MODIFY COLUMN Artist VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Podcasttable MODIFY COLUMN Description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE Podcasttable MODIFY COLUMN Category VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE PodcastTracktable MODIFY COLUMN Title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE PodcastTracktable MODIFY COLUMN Artist VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE PodcastTracktable MODIFY COLUMN Description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL", true);
				generic_sql_query("ALTER TABLE PodcastTracktable MODIFY COLUMN Link TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE PodcastTracktable MODIFY COLUMN Guid TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE PodcastTracktable MODIFY COLUMN Localfilename VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE RadioStationtable MODIFY COLUMN StationName VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE RadioStationtable MODIFY COLUMN PlaylistUrl TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE RadioStationtable MODIFY COLUMN Image VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE RadioTracktable MODIFY COLUMN TrackUri TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE RadioTracktable MODIFY COLUMN PrettyStream TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE WishlistSourcetable MODIFY COLUMN SourceName VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE WishlistSourcetable MODIFY COLUMN SourceImage VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE WishlistSourcetable MODIFY COLUMN SourceUri TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE AlbumsToListenTotable MODIFY COLUMN JsonData TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE BackgroundImageTable MODIFY COLUMN Skin VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", true);
				generic_sql_query("ALTER TABLE BackgroundImageTable MODIFY COLUMN BrowserID VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL", true);
				generic_sql_query("ALTER TABLE BackgroundImageTable MODIFY COLUMN Filename VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL", true);
				generic_sql_query("UPDATE Statstable SET Value = 58 WHERE Item = 'SchemaVer'", true);
				break;

		}
		$sv++;
	}

	return array(true, "");
}

function delete_oldtracks() {
	// generic_sql_query("DELETE Tracktable FROM Tracktable JOIN Playcounttable USING (TTindex) WHERE Hidden = 1 AND DATE_SUB(CURDATE(), INTERVAL 6 MONTH) > DateAdded AND Playcount < 2", true);
}

function mb4_bodge() {
	return 'SET NAMES utf8mb4;';
}

function delete_orphaned_artists() {
	generic_sql_query("DROP TABLE IF EXISTS Croft", true);
	generic_sql_query("DROP TABLE IF EXISTS Cruft", true);
	generic_sql_query("CREATE TEMPORARY TABLE Croft(Artistindex INT UNSIGNED NOT NULL UNIQUE, PRIMARY KEY(Artistindex)) AS SELECT Artistindex FROM Tracktable UNION SELECT AlbumArtistindex FROM Albumtable", true);
	generic_sql_query("CREATE TEMPORARY TABLE Cruft(Artistindex INT UNSIGNED NOT NULL UNIQUE, PRIMARY KEY(Artistindex)) AS SELECT Artistindex FROM Artisttable WHERE Artistindex NOT IN (SELECT Artistindex FROM Croft)", true);
	generic_sql_query("DELETE Artisttable FROM Artisttable INNER JOIN Cruft ON Artisttable.Artistindex = Cruft.Artistindex", true);
}

function hide_played_tracks() {
	generic_sql_query("CREATE TEMPORARY TABLE Fluff(TTindex INT UNSIGNED NOT NULL UNIQUE, PRIMARY KEY(TTindex)) AS SELECT TTindex FROM Tracktable JOIN Playcounttable USING (TTindex) WHERE isSearchResult = 2", true);
	generic_sql_query("UPDATE Tracktable SET Hidden = 1, isSearchResult = 0 WHERE TTindex IN (SELECT TTindex FROM Fluff)", true);
}

function sql_recent_tracks() {
	global $prefs;
	$qstring = "SELECT TTindex FROM Tracktable WHERE (DATE_SUB(CURDATE(),INTERVAL 60 DAY) <= DateAdded) AND Hidden = 0 AND isSearchResult < 2 AND isAudiobook = 0 AND Uri IS NOT NULL";
	if ($prefs['collection_player'] == 'mopidy' && $prefs['player_backend'] == 'mpd') {
		$qstring .= ' AND Uri LIKE "local:%"';
	}
	return $qstring." ORDER BY RAND()";
}

function sql_recent_albums() {
	global $prefs;
	$qstring = "SELECT TTindex, Albumindex, TrackNo FROM Tracktable WHERE DATE_SUB(CURDATE(),INTERVAL 60 DAY) <= DateAdded AND Hidden = 0 AND isSearchResult < 2 AND isAudiobook = 0 AND Uri IS NOT NULL";
	if ($prefs['collection_player'] == 'mopidy' && $prefs['player_backend'] == 'mpd') {
		$qstring .= ' AND Uri LIKE "local:%"';
	}
	return $qstring;
}

function sql_recently_played() {
	return "SELECT t.Uri, t.Title, a.Artistname, al.Albumname, al.Image, al.ImgKey, UNIX_TIMESTAMP(p.LastPlayed) AS unixtime FROM Tracktable AS t JOIN Playcounttable AS p USING (TTindex) JOIN Albumtable AS al USING (albumindex) JOIN Artisttable AS a ON (a.Artistindex = al.AlbumArtistindex) WHERE DATE_SUB(CURDATE(),INTERVAL 14 DAY) <= p.LastPlayed AND p.LastPlayed IS NOT NULL ORDER BY p.LastPlayed DESC";
}

function recently_played_playlist() {
	$qstring = "SELECT TTindex FROM Playcounttable JOIN Tracktable USING (TTindex) WHERE DATE_SUB(CURDATE(),INTERVAL 14 DAY) <= LastPlayed AND LastPlayed IS NOT NULL AND isAudiobook = 0 AND Hidden = 0";
	return $qstring;
}

function sql_two_weeks() {
	return "DATE_SUB(CURDATE(),INTERVAL 14 DAY) > LastPlayed";
}

function sql_two_weeks_include($days) {
	return "DATE_SUB(CURDATE(),INTERVAL ".$days." DAY) <= LastPlayed  AND LastPlayed IS NOT NULL";
}

function sql_to_unixtime($s) {
	return "UNIX_TIMESTAMP(".$s.")";
}

function track_date_check($range, $flag) {
	if ($flag == 'b') {
		return '';
	}
	switch ($range) {
		case ADDED_ALL_TIME:
			return '';
			break;

		case ADDED_TODAY:
			return 'AND DATE_SUB(CURDATE(), INTERVAL 1 DAY) <= DateAdded';
			break;

		case ADDED_THIS_WEEK:
			return 'AND DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= DateAdded';
			break;

		case ADDED_THIS_MONTH:
			return 'AND DATE_SUB(CURDATE(), INTERVAL 1 MONTH) <= DateAdded';
			break;

		case ADDED_THIS_YEAR:
			return 'AND DATE_SUB(CURDATE(), INTERVAL 1 YEAR) <= DateAdded';
			break;

		default:
			logger::warn("SQL", "ERROR! Unknown Collection Range ".$range);
			return '';
			break;

	}
}

function find_podcast_track_from_url($url) {
	return sql_prepare_query(false, PDO::FETCH_OBJ, null, null,
								"SELECT
									PodcastTracktable.Title AS title,
									PodcastTracktable.Artist AS artist,
									PodcastTracktable.Duration AS duration,
									PodcastTracktable.Description AS comment,
									Podcasttable.Title AS album,
									Podcasttable.Artist AS albumartist,
									Podcasttable.Image AS image
									FROM PodcastTracktable JOIN Podcasttable USING (PODindex)
									WHERE PodcastTracktable.Link=?
									OR ? LIKE CONCAT('%', PodcastTracktable.Localfilename)",
									$url,
									$url);
}

function create_conditional_triggers() {
	generic_sql_query("CREATE TRIGGER track_insert_trigger AFTER INSERT ON Tracktable
						FOR EACH ROW
						BEGIN
							IF (NEW.Hidden=0)
							THEN
							  UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = NEW.Albumindex;
							END IF;
						END;", true);

	generic_sql_query("CREATE TRIGGER track_update_trigger AFTER UPDATE ON Tracktable
						FOR EACH ROW
						BEGIN
						IF (NEW.Hidden=0)
						THEN
							UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = NEW.Albumindex;
							UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = OLD.Albumindex;
						END IF;
						END;", true);

}

function create_playcount_triggers() {
	generic_sql_query("CREATE TRIGGER syncupdatetrigger BEFORE UPDATE ON Playcounttable
						FOR EACH ROW
						BEGIN
							IF (NEW.Playcount > OLD.Playcount)
							THEN
								SET NEW.SyncCount = OLD.SyncCount + 1;
							END IF;
						END;", true);

	generic_sql_query("CREATE TRIGGER syncinserttrigger BEFORE INSERT ON Playcounttable
						FOR EACH ROW
						BEGIN
							SET NEW.SyncCount = 1;
						END;", true);

}

function create_update_triggers() {

	logger::debug("MYSQL", "Creating Triggers for update operation");

	generic_sql_query("CREATE TRIGGER rating_update_trigger AFTER UPDATE ON Ratingtable
						FOR EACH ROW
						BEGIN
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = NEW.TTindex);
						UPDATE Tracktable SET Hidden = 0, justAdded = 1 WHERE Hidden = 1 AND TTindex = NEW.TTindex;
						UPDATE Tracktable SET isSearchResult = 1, LastModified = NULL, justAdded = 1 WHERE isSearchResult > 1 AND TTindex = NEW.TTindex;
						END;", true);

	generic_sql_query("CREATE TRIGGER rating_insert_trigger AFTER INSERT ON Ratingtable
						FOR EACH ROW
						BEGIN
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = NEW.TTindex);
						UPDATE Tracktable SET Hidden = 0, justAdded = 1 WHERE Hidden = 1 AND TTindex = NEW.TTindex;
						UPDATE Tracktable SET isSearchResult = 1, LastModified = NULL, justAdded = 1 WHERE isSearchResult > 1 AND TTindex = NEW.TTindex;
						END;", true);

	generic_sql_query("CREATE TRIGGER tag_delete_trigger AFTER DELETE ON Tagtable
						FOR EACH ROW
						BEGIN
						DELETE FROM TagListtable WHERE Tagindex = OLD.Tagindex;
						END;", true);

	generic_sql_query("CREATE TRIGGER tag_insert_trigger AFTER INSERT ON TagListtable
						FOR EACH ROW
						BEGIN
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = NEW.TTindex);
						UPDATE Tracktable SET Hidden = 0, justAdded = 1 WHERE Hidden = 1 AND TTindex = NEW.TTindex;
						UPDATE Tracktable SET isSearchResult = 1, LastModified = NULL, justAdded = 1 WHERE isSearchResult > 1 AND TTindex = NEW.TTindex;
						END;", true);

	generic_sql_query("CREATE TRIGGER tag_remove_trigger AFTER DELETE ON TagListtable
						FOR EACH ROW
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = OLD.TTindex);", true);

	generic_sql_query("CREATE TRIGGER track_delete_trigger AFTER DELETE ON Tracktable
						FOR EACH ROW
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = OLD.Albumindex;", true);

}

function create_progress_triggers() {

	generic_sql_query("CREATE TRIGGER progress_update_trigger AFTER UPDATE ON Progresstable
						FOR EACH ROW
						BEGIN
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = NEW.TTindex);
						END;", true);

	generic_sql_query("CREATE TRIGGER progress_insert_trigger AFTER INSERT ON Progresstable
						FOR EACH ROW
						BEGIN
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = NEW.TTindex);
						END;", true);


}

?>
