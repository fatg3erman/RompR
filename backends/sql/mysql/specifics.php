<?php

define('SQL_RANDOM_SORT', 'RAND()');
define('SQL_TAG_CONCAT', "GROUP_CONCAT(t.Name SEPARATOR ', ') ");

function connect_to_database() {
	global $mysqlc, $prefs;
	if ($mysqlc !== null) {
		debuglog("AWOOOGA! ATTEMPTING MULTIPLE DATABASE CONNECTIONS!","MYSQL",1);
		return;
	}
	try {
		if (is_numeric($prefs['mysql_port'])) {
			debuglog("Connecting using hostname and port","SQL_CONNECT",9);
			$dsn = "mysql:host=".$prefs['mysql_host'].";port=".$prefs['mysql_port'].";dbname=".$prefs['mysql_database'];
		} else {
			debuglog("Connecting using unix socket","SQL_CONNECT",9);
			$dsn = "mysql:unix_socket=".$prefs['mysql_port'].";dbname=".$prefs['mysql_database'];
		}
		$mysqlc = new PDO($dsn, $prefs['mysql_user'], $prefs['mysql_password']);
		debuglog("Connected to MySQL","SQL_CONNECT",9);
		generic_sql_query("SET NAMES utf8", true);
		generic_sql_query('SET SESSION sql_mode="STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"', true);
	} catch (Exception $e) {
		debuglog("Database connect failure - ".$e,"SQL_CONNECT",1);
		sql_init_fail($e->getMessage());
	}
}

function close_database() {
	global $mysqlc;
	$mysqlc = null;
}

function check_sql_tables() {
	global $mysqlc;
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
		"INDEX(Albumindex), ".
		"INDEX(Title), ".
		"INDEX(TrackNo)) ENGINE=InnoDB", true))
	{
		debuglog("  Tracktable OK","MYSQL_CONNECT");
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
		debuglog("  Albumtable OK","MYSQL_CONNECT");
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
		debuglog("  Artisttable OK","MYSQL_CONNECT");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Artisttable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Ratingtable(".
		"TTindex INT UNSIGNED, ".
		"PRIMARY KEY(TTindex), ".
		"Rating TINYINT(1) UNSIGNED) ENGINE=InnoDB", true))
	{
		debuglog("  Ratingtable OK","MYSQL_CONNECT");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Ratingtable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Tagtable(".
		"Tagindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
		"PRIMARY KEY(Tagindex), ".
		"Name VARCHAR(255)) ENGINE=InnoDB", true))
	{
		debuglog("  Tagtable OK","MYSQL_CONNECT");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Tagtable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS TagListtable(".
		"Tagindex INT UNSIGNED NOT NULL REFERENCES Tagtable(Tagindex), ".
		"TTindex INT UNSIGNED NOT NULL REFERENCES Tracktable(TTindex), ".
		"PRIMARY KEY (Tagindex, TTindex)) ENGINE=InnoDB", true))
	{
		debuglog("  TagListtable OK","MYSQL_CONNECT");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking TagListtable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Playcounttable(".
		"TTindex INT UNSIGNED NOT NULL REFERENCES Tracktable(TTindex), ".
		"Playcount INT UNSIGNED NOT NULL, ".
		"LastPlayed TIMESTAMP, ".
		"PRIMARY KEY (TTindex)) ENGINE=InnoDB", true))
	{
		debuglog("  Playcounttable OK","MYSQL_CONNECT");
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
		"PRIMARY KEY (PODindex)) ENGINE=InnoDB", true))
	{
		debuglog("  Podcasttable OK","MYSQL_CONNECT");
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
		"INDEX (PODindex), ".
		"PRIMARY KEY (PODTrackindex)) ENGINE=InnoDB", true))
	{
		debuglog("  PodcastTracktable OK","MYSQL_CONNECT");
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
		debuglog("  RadioStationtable OK","MYSQL_CONNECT");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking RadioStationtable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS RadioTracktable(".
		"Trackindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
		"Stationindex INT UNSIGNED REFERENCES RadioStationtable(Stationindex), ".
		"TrackUri TEXT, ".
		"PrettyStream TEXT, ".
		"PRIMARY KEY (Trackindex), ".
		"FULLTEXT KEY TrackUri (TrackUri)) ENGINE=InnoDB", true))
	{
		debuglog("  RadioTracktable OK","MYSQL_CONNECT");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking RadioTracktable : ".$err);
	}
	
	if (generic_sql_query("CREATE TABLE IF NOT EXISTS WishlistSourcetable(".
		"Sourceindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
		"SourceName VARCHAR(255), ".
		"SourceImage VARCHAR(255), ".
		"SourceUri TEXT, ".
		"PRIMARY KEY (Sourceindex), ".
		"FULLTEXT KEY SourceUri (SourceUri)) ENGINE=InnoDB", true))
	{
		debuglog("  WishlistSourcetable OK","MYSQL_CONNECT");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking WishlistSourcetable : ".$err);
	}

	if (!generic_sql_query("CREATE TABLE IF NOT EXISTS Statstable(Item CHAR(11), PRIMARY KEY(Item), Value INT UNSIGNED) ENGINE=InnoDB", true)) {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Statstable : ".$err);
	}
	// Check schema version and update tables as necessary
	$sv = simple_query('Value', 'Statstable', 'Item', 'SchemaVer', 0);
	if ($sv == 0) {
		debuglog("No Schema Version Found - initialising table","SQL_CONNECT");
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('ListVersion', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('ArtistCount', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('AlbumCount', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('TrackCount', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('TotalTime', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('SchemaVer', '".ROMPR_SCHEMA_VERSION."')", true);
		$sv = ROMPR_SCHEMA_VERSION;
		debuglog("Statstable populated", "MYSQL_CONNECT");
		create_update_triggers();
		create_conditional_triggers();
	}

	if ($sv > ROMPR_SCHEMA_VERSION) {
		debuglog("Schema Mismatch! We are version ".ROMPR_SCHEMA_VERSION." but database is version ".$sv,"MYSQL_CONNECT");
		return array(false, "Your database has version number ".$sv." but this version of rompr only handles version ".ROMPR_SCHEMA_VERSION);
	}

	while ($sv < ROMPR_SCHEMA_VERSION) {
		switch ($sv) {
			case 0:
				debuglog("BIG ERROR! No Schema Version found!!","SQL");
				return array(false, "Database Error - could not read schema version. Cannot continue.");
				break;

			case 1:
				debuglog("Updating FROM Schema version 1 TO Schema version 2","SQL");
				generic_sql_query("ALTER TABLE Albumtable DROP Directory", true);
				generic_sql_query("UPDATE Statstable SET Value = 2 WHERE Item = 'SchemaVer'", true);
				break;

			case 2:
				debuglog("Updating FROM Schema version 2 TO Schema version 3","SQL");
				generic_sql_query("ALTER TABLE Tracktable ADD Hidden TINYINT(1) UNSIGNED DEFAULT 0", true);
				generic_sql_query("UPDATE Tracktable SET Hidden = 0 WHERE Hidden IS NULL", true);
				generic_sql_query("UPDATE Statstable SET Value = 3 WHERE Item = 'SchemaVer'", true);
				break;

			case 3:
				debuglog("Updating FROM Schema version 3 TO Schema version 4","SQL");
				generic_sql_query("UPDATE Tracktable SET Disc = 1 WHERE Disc IS NULL OR Disc = 0", true);
				generic_sql_query("UPDATE Statstable SET Value = 4 WHERE Item = 'SchemaVer'", true);
				break;

			case 4:
				debuglog("Updating FROM Schema version 4 TO Schema version 5","SQL");
				generic_sql_query("UPDATE Albumtable SET Searched = 0 WHERE Image NOT LIKE 'albumart%'", true);
				generic_sql_query("ALTER TABLE Albumtable DROP Image", true);
				generic_sql_query("UPDATE Statstable SET Value = 5 WHERE Item = 'SchemaVer'", true);
				break;

			case 5:
				debuglog("Updating FROM Schema version 5 TO Schema version 6","SQL");
				generic_sql_query("DROP INDEX Disc on Tracktable", true);
				generic_sql_query("UPDATE Statstable SET Value = 6 WHERE Item = 'SchemaVer'", true);
				break;

			case 6:
				debuglog("Updating FROM Schema version 6 TO Schema version 7","SQL");
				// This was going to be a nice datestamp but newer versions of mysql don't work that way
				generic_sql_query("ALTER TABLE Tracktable ADD DateAdded TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", true);
				generic_sql_query("UPDATE Tracktable SET DateAdded = FROM_UNIXTIME(LastModified) WHERE LastModified IS NOT NULL AND LastModified > 0", true);
				generic_sql_query("UPDATE Statstable SET Value = 7 WHERE Item = 'SchemaVer'", true);
				break;

			case 7:
				debuglog("Updating FROM Schema version 7 TO Schema version 8","SQL");
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
						    debuglog("Updating artist name from ".$artist." to ".$newname,"UPGRADE_SCHEMA");
						    $stmt->execute(array($newname, $obj->Artistindex));
						}
					}
				}
				generic_sql_query("UPDATE Statstable SET Value = 8 WHERE Item = 'SchemaVer'", true);
				$stmt = null;
				break;

			case 8:
				debuglog("Updating FROM Schema version 8 TO Schema version 9","SQL");
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
				debuglog("Updating FROM Schema version 9 TO Schema version 10","SQL");
				generic_sql_query("ALTER TABLE Albumtable DROP NumDiscs", true);
				generic_sql_query("UPDATE Statstable SET Value = 10 WHERE Item = 'SchemaVer'", true);
				break;

			case 10:
				debuglog("Updating FROM Schema version 10 TO Schema version 11","SQL");
				generic_sql_query("ALTER TABLE Albumtable DROP IsOneFile", true);
				generic_sql_query("UPDATE Statstable SET Value = 11 WHERE Item = 'SchemaVer'", true);
				break;

			case 11:
				debuglog("Updating FROM Schema version 11 TO Scheme version 12","SQL");
				generic_sql_query("ALTER TABLE Tracktable ADD isSearchResult TINYINT(1) UNSIGNED DEFAULT 0", true);
				generic_sql_query("UPDATE Statstable SET Value = 12 WHERE Item = 'SchemaVer'", true);
				break;

			case 12:
				debuglog("Updating FROM Schema version 12 TO Scheme version 13","SQL");
				generic_sql_query("ALTER TABLE Albumtable CHANGE Spotilink AlbumUri VARCHAR(255)", true);
				generic_sql_query("UPDATE Statstable SET Value = 13 WHERE Item = 'SchemaVer'", true);
				break;

			case 13:
				debuglog("Updating FROM Schema version 13 TO Scheme version 14","SQL");
				// Nothing to do here, this is for SQLite only.
				generic_sql_query("UPDATE Statstable SET Value = 14 WHERE Item = 'SchemaVer'", true);
				break;

			case 14:
				debuglog("Updating FROM Schema version 14 TO Scheme version 15","SQL");
				generic_sql_query("ALTER TABLE Tracktable MODIFY LastModified CHAR(32)", true);
				generic_sql_query("UPDATE Statstable SET Value = 15 WHERE Item = 'SchemaVer'", true);
				break;

			case 15:
				debuglog("Updating FROM Schema version 15 TO Schema version 16","SQL");
				albumImageBuggery();
				generic_sql_query("UPDATE Statstable SET Value = 16 WHERE Item = 'SchemaVer'", true);
				break;

			case 16:
				debuglog("Updating FROM Schema version 16 TO Schema version 17","SQL",6);
				// Early MPD versions had LastModified as an integer value. They changed it to a datestamp some time ago but I didn't notice
				$r = generic_sql_query("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Tracktable' AND COLUMN_NAME = 'LastModified'");
				foreach ($r as $obj) {
					debuglog("Data Type of LastModified is ".$obj['DATA_TYPE'],"MYSQL_INIT",6);
					if ($obj['DATA_TYPE'] == 'int') {
						debuglog("Modifying Tracktable","MYSQL_INIT",6);
						generic_sql_query("ALTER TABLE Tracktable ADD LM CHAR(32)", true);
						generic_sql_query("UPDATE Tracktable SET LM = LastModified", true);
						generic_sql_query("ALTER TABLE Tracktable DROP LastModified", true);
						generic_sql_query("ALTER TABLE Tracktable CHANGE LM LastModified CHAR(32)", true);
					}
				}
				generic_sql_query("UPDATE Statstable SET Value = 17 WHERE Item = 'SchemaVer'", true);
				break;

			case 17:
				debuglog("Updating FROM Schema version 17 TO Schema version 18","SQL",6);
				include("utils/podcastupgrade.php");
				generic_sql_query("UPDATE Statstable SET Value = 18 WHERE Item = 'SchemaVer'", true);
				break;

			case 18:
				debuglog("Updating FROM Schema version 18 TO Schema version 19","SQL");
				$result = generic_sql_query('SELECT Tracktable.Uri AS uri, Tracktable.TTindex, Tracktable.Title AS ttit, Albumtable.*, Trackimagetable.Image AS ti FROM Tracktable JOIN Albumtable USING (Albumindex) LEFT JOIN Trackimagetable USING (TTindex) WHERE Tracktable.Uri LIKE "soundcloud:%"', false, PDO::FETCH_OBJ);
				foreach ($result as $obj) {
					debuglog("  Creating new Album ".$obj->ttit." Image ".$obj->ti,"SQL");
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
							debuglog("    .. success, Albumindex ".$retval,"SQL");
							generic_sql_query("UPDATE Tracktable SET Albumindex = ".$retval." WHERE TTindex = ".$obj->TTindex, true);
					} else {
						debuglog("    .. ERROR!","SQL");
					}
				}
				generic_sql_query("UPDATE Statstable SET Value = 19 WHERE Item = 'SchemaVer'", true);
				break;

			case 19:
				debuglog("Updating FROM Schema version 19 TO Schema version 20","SQL");
				$result = generic_sql_query('SELECT Tracktable.Uri AS uri, Tracktable.TTindex, Tracktable.Title AS ttit, Albumtable.*, Trackimagetable.Image AS ti FROM Tracktable JOIN Albumtable USING (Albumindex) LEFT JOIN Trackimagetable USING (TTindex) WHERE Tracktable.Uri LIKE "youtube:%"', false, PDO::FETCH_OBJ);
				foreach ($result as $obj) {
					debuglog("  Creating new Album ".$obj->ttit." Image ".$obj->ti,"SQL");
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
							debuglog("    .. success, Albumindex ".$retval,"SQL");
							generic_sql_query("UPDATE Tracktable SET Albumindex = ".$retval." WHERE TTindex = ".$obj->TTindex, true);
					} else {
						debuglog("    .. ERROR!","SQL");
					}
				}
				generic_sql_query("UPDATE Statstable SET Value = 20 WHERE Item = 'SchemaVer'", true);
				break;

			case 20:
				debuglog("Updating FROM Schema version 20 TO Schema version 21","SQL");
				generic_sql_query("DROP TABLE Trackimagetable", true);
				generic_sql_query("UPDATE Statstable SET Value = 21 WHERE Item = 'SchemaVer'", true);
				break;

			case 21:
				debuglog("Updating FROM Schema version 21 TO Schema version 22","SQL");
				generic_sql_query("ALTER TABLE Playcounttable ADD LastPlayed TIMESTAMP NULL", true);
				generic_sql_query("UPDATE Statstable SET Value = 22 WHERE Item = 'SchemaVer'", true);
				break;

			case 22:
				debuglog("Updating FROM Schema version 22 TO Schema version 23","SQL");
				generic_sql_query("ALTER TABLE Podcasttable ADD Version TINYINT(2)", true);
				generic_sql_query("ALTER TABLE PodcastTracktable ADD Guid VARCHAR(2000)", true);
				generic_sql_query("ALTER TABLE PodcastTracktable ADD Localfilename VARCHAR(255)", true);
				generic_sql_query("UPDATE Podcasttable SET Version = 1 WHERE PODindex IS NOT NULL", true);
				generic_sql_query("UPDATE Statstable SET Value = 23 WHERE Item = 'SchemaVer'", true);
				break;

			case 23:
				debuglog("Updating FROM Schema version 23 TO Schema version 24","SQL");
				generic_sql_query("ALTER TABLE Tracktable CHANGE DateAdded DateAdded TIMESTAMP DEFAULT CURRENT_TIMESTAMP", true);
				generic_sql_query("UPDATE Statstable SET Value = 24 WHERE Item = 'SchemaVer'", true);
				break;

			case 24:
				debuglog("Updating FROM Schema version 24 TO Schema version 25","SQL");
				generic_sql_query("ALTER DATABASE romprdb CHARACTER SET utf8 COLLATE utf8_unicode_ci", true);
				generic_sql_query("UPDATE Statstable SET Value = 25 WHERE Item = 'SchemaVer'", true);
				break;

			case 25:
				debuglog("Updating FROM Schema version 25 TO Schema version 26","SQL");
				generic_sql_query("ALTER TABLE Tracktable ADD justAdded TINYINT(1) UNSIGNED DEFAULT 1", true);
				generic_sql_query("UPDATE Statstable SET Value = 26 WHERE Item = 'SchemaVer'", true);
				break;

			case 26:
				debuglog("Updating FROM Schema version 26 TO Schema version 27","SQL");
				generic_sql_query("ALTER TABLE Albumtable ADD justUpdated TINYINT(1) UNSIGNED DEFAULT 1", true);
				generic_sql_query("UPDATE Statstable SET Value = 27 WHERE Item = 'SchemaVer'", true);
				break;

			case 27:
				debuglog("Updating FROM Schema version 27 TO Schema version 28","SQL");
				rejig_wishlist_tracks();
				generic_sql_query("UPDATE Statstable SET Value = 28 WHERE Item = 'SchemaVer'", true);
				break;

			case 28:
				debuglog("Updating FROM Schema version 28 TO Schema version 29","SQL");
				create_update_triggers();
				generic_sql_query("UPDATE Statstable SET Value = 29 WHERE Item = 'SchemaVer'", true);
				break;

			case 29:
				debuglog("Updating FROM Schema version 29 TO Schema version 30","SQL");
				include('utils/radioupgrade.php');
				generic_sql_query("UPDATE Statstable SET Value = 30 WHERE Item = 'SchemaVer'", true);
				break;

			case 30:
				debuglog("Updating FROM Schema version 30 TO Schema version 31","SQL");
				generic_sql_query("ALTER TABLE PodcastTracktable CHANGE Description Description TEXT", true);
				generic_sql_query("ALTER TABLE PodcastTracktable CHANGE Link Link TEXT", true);
				generic_sql_query("ALTER TABLE PodcastTracktable CHANGE Guid Guid TEXT", true);
				generic_sql_query("ALTER TABLE Podcasttable CHANGE FeedURL FeedURL TEXT", true);
				generic_sql_query("ALTER TABLE Podcasttable CHANGE Description Description TEXT", true);
				generic_sql_query("ALTER TABLE Tracktable CHANGE Uri Uri TEXT", true);
				generic_sql_query("UPDATE Statstable SET Value = 31 WHERE Item = 'SchemaVer'", true);
				break;

			case 31:
				debuglog("Updating FROM Schema version 31 TO Schema version 32","SQL");
				generic_sql_query("ALTER TABLE Podcasttable ADD Subscribed TINYINT(1) NOT NULL DEFAULT 1", true);
				generic_sql_query("UPDATE Statstable SET Value = 32 WHERE Item = 'SchemaVer'", true);
				break;

			case 32:
				debuglog("Updating FROM Schema version 32 TO Schema version 33","SQL");
				generic_sql_query("DROP TRIGGER IF EXISTS track_insert_trigger", true);
				generic_sql_query("DROP TRIGGER IF EXISTS track_update_trigger", true);
				create_conditional_triggers();
				generic_sql_query("UPDATE Statstable SET Value = 33 WHERE Item = 'SchemaVer'", true);
				break;
			
			case 33:
				debuglog("Updating FROM Schema version 33 TO Schema version 34","SQL");
				generic_sql_query("ALTER TABLE Albumtable ADD ImgVersion INT UNSIGNED DEFAULT ".ROMPR_IMAGE_VERSION, true);
				generic_sql_query("UPDATE Albumtable SET ImgVersion = 1",true);
				generic_sql_query("UPDATE Statstable SET Value = 34 WHERE Item = 'SchemaVer'", true);
				break;

			case 34:
				debuglog("Updating FROM Schema version 34 TO Schema version 35","SQL");
				generic_sql_query("ALTER TABLE Tracktable ADD Sourceindex INT UNSIGNED DEFAULT NULL", true);
				generic_sql_query("UPDATE Statstable SET Value = 35 WHERE Item = 'SchemaVer'", true);
				break;
				
		}
		$sv++;
	}

	return array(true, "");
}

function delete_oldtracks() {
	// generic_sql_query("DELETE Tracktable FROM Tracktable JOIN Playcounttable USING (TTindex) WHERE Hidden = 1 AND DATE_SUB(CURDATE(), INTERVAL 6 MONTH) > DateAdded AND Playcount < 2", true);
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
	return "SELECT Uri FROM Tracktable WHERE (DATE_SUB(CURDATE(),INTERVAL 30 DAY) <= DateAdded) AND Hidden = 0 AND isSearchResult < 2 AND Uri IS NOT NULL ORDER BY RAND()";
}

function sql_recent_albums() {
	return "SELECT Uri, Albumindex, TrackNo FROM Tracktable WHERE DATE_SUB(CURDATE(),INTERVAL 30 DAY) <= DateAdded AND Hidden = 0 AND isSearchResult < 2 AND Uri IS NOT NULL";
}

function sql_recently_played() {
	return "SELECT t.Uri, t.Title, a.Artistname, al.Albumname, al.Image, al.ImgKey, UNIX_TIMESTAMP(p.LastPlayed) AS unixtime FROM Tracktable AS t JOIN Playcounttable AS p USING (TTindex) JOIN Albumtable AS al USING (albumindex) JOIN Artisttable AS a ON (a.Artistindex = al.AlbumArtistindex) WHERE DATE_SUB(CURDATE(),INTERVAL 14 DAY) <= p.LastPlayed AND p.LastPlayed IS NOT NULL ORDER BY p.LastPlayed DESC";
}

function recently_played_playlist() {
	return "SELECT TTindex FROM Playcounttable AS Tracktable WHERE DATE_SUB(CURDATE(),INTERVAL 14 DAY) <= LastPlayed AND LastPlayed IS NOT NULL";
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

?>
