<?php

define('SQL_RANDOM_SORT', 'RANDOM()');
define('SQL_TAG_CONCAT', "GROUP_CONCAT(t.Name,', ') ");

function connect_to_database() {
	global $mysqlc, $prefs;
	if ($mysqlc !== null) {
		debuglog("AWOOOGA! ATTEMPTING MULTIPLE DATABASE CONNECTIONS!","SQLITE",1);
		return;
	}
	try {
		$dsn = "sqlite:prefs/collection_".$prefs['player_backend'].".sq3";
		$mysqlc = new PDO($dsn);
		debuglog("Connected to SQLite","MYSQL",9);
		// This increases performance
		generic_sql_query('PRAGMA journal_mode=DELETE', true);
		generic_sql_query('PRAGMA cache_size=-4000', true);
		generic_sql_query('PRAGMA synchronous=OFF', true);
		generic_sql_query('PRAGMA threads=4', true);
	} catch (Exception $e) {
		debuglog("Couldn't Connect To SQLite - ".$e,"MYSQL",1);
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
		"TTindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
		"Title VARCHAR(255), ".
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
		"justAdded TINYINT(1) DEFAULT 1)", true))
	{
		debuglog("  Tracktable OK","SQLITE_CONNECT");
		if (generic_sql_query("CREATE INDEX IF NOT EXISTS ai ON Tracktable (Albumindex)", true)) {
		} else {
			$err = $mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Tracktable : ".$err);
		}
		if (generic_sql_query("CREATE INDEX IF NOT EXISTS ti ON Tracktable (Title)", true)) {
		} else {
			$err = $mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Tracktable : ".$err);
		}
		if (generic_sql_query("CREATE INDEX IF NOT EXISTS tn ON Tracktable (TrackNo)", true)) {
		} else {
			$err = $mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Tracktable : ".$err);
		}
		if (generic_sql_query("CREATE INDEX IF NOT EXISTS di ON Tracktable (Disc)", true)) {
		} else {
			$err = $mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Tracktable : ".$err);
		}
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Tracktable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Albumtable(".
		"Albumindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
		"Albumname VARCHAR(255), ".
		"AlbumArtistindex INTEGER, ".
		"AlbumUri VARCHAR(255), ".
		"Year YEAR, ".
		"Searched TINYINT(1), ".
		"ImgKey CHAR(32), ".
		"mbid CHAR(40), ".
		"ImgVersion INTEGER DEFAULT ".ROMPR_IMAGE_VERSION.", ".
		"Domain CHAR(32), ".
		"Image VARCHAR(255), ".
		"justUpdated TINYINT(1) DEFAULT 0)", true))
	{
		debuglog("  Albumtable OK","SQLITE_CONNECT");
		if (generic_sql_query("CREATE INDEX IF NOT EXISTS ni ON Albumtable (Albumname)", true)) {
		} else {
			$err = $mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Albumtable : ".$err);
		}
		if (generic_sql_query("CREATE INDEX IF NOT EXISTS aai ON Albumtable (AlbumArtistindex)", true)) {
		} else {
			$err = $mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Albumtable : ".$err);
		}
		if (generic_sql_query("CREATE INDEX IF NOT EXISTS di ON Albumtable (Domain)", true)) {
		} else {
			$err = $mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Albumtable : ".$err);
		}
		if (generic_sql_query("CREATE INDEX IF NOT EXISTS ii ON Albumtable (ImgKey)", true)) {
		} else {
			$err = $mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Albumtable : ".$err);
		}
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Albumtable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Artisttable(".
		"Artistindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
		"Artistname VARCHAR(255))", true))
	{
		debuglog("  Artisttable OK","SQLITE_CONNECT");
		if (generic_sql_query("CREATE INDEX IF NOT EXISTS ni ON Artisttable (Artistname)", true)) {
		} else {
			$err = $mysqlc->errorInfo()[2];
			return array(false, "Error While Checking Artisttable : ".$err);
		}
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Artisttable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Ratingtable(".
		"TTindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
		"Rating TINYINT(1))", true))
	{
		debuglog("  Ratingtable OK","SQLITE_CONNECT");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Ratingtable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Tagtable(".
		"Tagindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
		"Name VARCHAR(255))", true))
	{
		debuglog("  Tagtable OK","SQLITE_CONNECT");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Tagtable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS TagListtable(".
		"Tagindex INTEGER NOT NULL REFERENCES Tagtable(Tagindex), ".
		"TTindex INTEGER NOT NULL REFERENCES Tracktable(TTindex), ".
		"PRIMARY KEY (Tagindex, TTindex))", true))
	{
		debuglog("  TagListtable OK","SQLITE_CONNECT");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking TagListtable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Playcounttable(".
		"TTindex INTEGER PRIMARY KEY NOT NULL UNIQUE REFERENCES Tracktable(TTindex), ".
		"Playcount INT UNSIGNED NOT NULL, ".
		"LastPlayed TIMESTAMP DEFAULT NULL)", true))
	{
		debuglog("  Playcounttable OK","SQLITE_CONNECT");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Playcounttable : ".$err);
	}

	if (!generic_sql_query("CREATE TABLE IF NOT EXISTS Statstable(Item CHAR(11), Value INTEGER, PRIMARY KEY(Item))", true)) {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Statstable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Podcasttable(".
		"PODindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
		"FeedURL TEXT, ".
		"LastUpdate INTEGER, ".
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
		"Description TEXT)", true))
	{
		debuglog("  Podcasttable OK","SQLITE_CONNECT");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Podcasttable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS PodcastTracktable(".
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
		"Deleted TINYINT(1) DEFAULT 0)", true))
	{
		debuglog("  PodcastTracktable OK","SQLITE_CONNECT");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking PodcastTracktable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS RadioStationtable(".
		"Stationindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
		"Number SMALLINT DEFAULT 65535, ".
		"IsFave TINYINT(1), ".
		"StationName VARCHAR(255), ".
		"PlaylistUrl TEXT, ".
		"Image VARCHAR(255))", true))
	{
		debuglog("  RadioStationtable OK","SQLITE_CONNECT");
		if (generic_sql_query("CREATE INDEX IF NOT EXISTS ui ON RadioStationtable (PlaylistUrl)", true)) {
		} else {
			$err = $mysqlc->errorInfo()[2];
			return array(false, "Error While Checking RadioStationtable : ".$err);
		}
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking RadioStationtable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS RadioTracktable(".
		"Trackindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
		"Stationindex INTEGER REFERENCES RadioStationtable(Stationindex), ".
		"TrackUri TEXT, ".
		"PrettyStream TEXT)", true))
	{
		debuglog("  RadioTracktable OK","SQLITE_CONNECT");
		if (generic_sql_query("CREATE INDEX IF NOT EXISTS uri ON RadioTracktable (TrackUri)", true)) {
		} else {
			$err = $mysqlc->errorInfo()[2];
			return array(false, "Error While Checking RadioTracktable : ".$err);
		}
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking RadioTracktable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS WishlistSourcetable(".
		"Sourceindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
		"SourceName VARCHAR(255), ".
		"SourceImage VARCHAR(255), ".
		"SourceUri TEXT)", true))
	{
		debuglog("  WishlistSourcetable OK","SQLITE_CONNECT");
		if (generic_sql_query("CREATE INDEX IF NOT EXISTS suri ON WishlistSourcetable (SourceUri)", true)) {
		} else {
			$err = $mysqlc->errorInfo()[2];
			return array(false, "Error While Checking WishlistSourcetable : ".$err);
		}
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking WishlistSourcetable : ".$err);
	}

	// Check schema version and update tables as necessary
	$sv = simple_query('Value', 'Statstable', 'Item', 'SchemaVer', 0);
	if ($sv == 0) {
		debuglog("No Schema Version Found - initialising table","SQLITE_CONNECT");
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('ListVersion', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('ArtistCount', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('AlbumCount', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('TrackCount', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('TotalTime', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('SchemaVer', '".ROMPR_SCHEMA_VERSION."')", true);
		$sv = ROMPR_SCHEMA_VERSION;
		debuglog("Statstable populated", "SQLITE_CONNECT");
		create_update_triggers();
		create_conditional_triggers();
	}

	if ($sv > ROMPR_SCHEMA_VERSION) {
		debuglog("Schema Mismatch! We are version ".ROMPR_SCHEMA_VERSION." but database is version ".$sv,"SQLITE_CONNECT");
		return array(false, "Your database has version number ".$sv." but this version of rompr only handles version ".ROMPR_SCHEMA_VERSION);
	}

	while ($sv < ROMPR_SCHEMA_VERSION) {
		switch ($sv) {
			case 0:
				debuglog("BIG ERROR! No Schema Version found!!","SQL");
				return array(false, "Database Error - could not read schema version. Cannot continue.");
				break;

			case 11:
				debuglog("Updating FROM Schema version 11 TO Scheme version 12","SQL");
				generic_sql_query("ALTER TABLE Tracktable ADD isSearchResult TINYINT(1) DEFAULT 0", true);
				generic_sql_query("UPDATE Statstable SET Value = 12 WHERE Item = 'SchemaVer'", true);
				break;

			case 12;
				debuglog("Updating FROM Schema version 12 TO Scheme version 13","SQL");
				// First attempt didn't work
				generic_sql_query("UPDATE Statstable SET Value = 13 WHERE Item = 'SchemaVer'", true);
				break;

			case 13:
				// SQLite doesn't let you rename or remove a column. Holy Shitting heck.
				debuglog("Updating FROM Schema version 13 TO Schema version 14","SQL");
				generic_sql_query("CREATE TABLE Albumtable_New(".
					"Albumindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
					"Albumname VARCHAR(255), ".
					"AlbumArtistindex INTEGER, ".
					"AlbumUri VARCHAR(255), ".
					"Year YEAR, ".
					"Searched TINYINT(1), ".
					"ImgKey CHAR(32), ".
					"mbid CHAR(40), ".
					"Domain CHAR(32), ".
					"Image VARCHAR(255))", true);
				generic_sql_query("INSERT INTO Albumtable_New SELECT Albumindex, Albumname,
					AlbumArtistindex, Spotilink AS AlbumUri, Year, Searched, ImgKey, mbid, Domain, Image
					FROM Albumtable", true);
				generic_sql_query("DROP TABLE Albumtable", true);
				generic_sql_query("ALTER TABLE Albumtable_New RENAME TO Albumtable", true);
				generic_sql_query("CREATE INDEX IF NOT EXISTS ni ON Albumtable (Albumname)", true);
				generic_sql_query("CREATE INDEX IF NOT EXISTS aai ON Albumtable (AlbumArtistindex)", true);
				generic_sql_query("CREATE INDEX IF NOT EXISTS di ON Albumtable (Domain)", true);
				generic_sql_query("CREATE INDEX IF NOT EXISTS ii ON Albumtable (ImgKey)", true);
				generic_sql_query("UPDATE Statstable SET Value = 14 WHERE Item = 'SchemaVer'", true);
				break;

			case 14:
				// SQLite doesn't let you rename or remove a column. Holy Shitting heck.
				debuglog("Updating FROM Schema version 14 TO Schema version 15","SQL");
				generic_sql_query("CREATE TABLE Tracktable_New(".
					"TTindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
					"Title VARCHAR(255), ".
					"Albumindex INTEGER, ".
					"TrackNo SMALLINT, ".
					"Duration INTEGER, ".
					"Artistindex INTEGER, ".
					"Disc TINYINT(3), ".
					"Uri VARCHAR(2000) ,".
					"LastModified CHAR(32), ".
					"Hidden TINYINT(1) DEFAULT 0, ".
					"DateAdded TIMESTAMP DEFAULT CURRENT_TIMESTAMP, ".
					"isSearchResult TINYINT(1) DEFAULT 0)", true);
				generic_sql_query("INSERT INTO Tracktable_New SELECT TTindex, Title, Albumindex,
					TrackNo, Duration, Artistindex, Disc, Uri, LastModified, Hidden, DateAdded, isSearchResult
					FROM Tracktable", true);
				generic_sql_query("DROP TABLE Tracktable", true);
				generic_sql_query("ALTER TABLE Tracktable_New RENAME TO Tracktable", true);
				generic_sql_query("CREATE TRIGGER IF NOT EXISTS updatetime AFTER UPDATE ON Tracktable BEGIN UPDATE Tracktable SET DateAdded = CURRENT_TIMESTAMP WHERE TTindex = old.TTindex; END", true);
				generic_sql_query("CREATE INDEX IF NOT EXISTS ai ON Tracktable (Albumindex)", true);
				generic_sql_query("CREATE INDEX IF NOT EXISTS ti ON Tracktable (Title)", true);
				generic_sql_query("CREATE INDEX IF NOT EXISTS tn ON Tracktable (TrackNo)", true);
				generic_sql_query("CREATE INDEX IF NOT EXISTS di ON Tracktable (Disc)", true);
				generic_sql_query("UPDATE Statstable SET Value = 15 WHERE Item = 'SchemaVer'", true);
				break;

			case 15:
				debuglog("Updating FROM Schema version 15 TO Schema version 16","SQL");
				albumImageBuggery();
				generic_sql_query("UPDATE Statstable SET Value = 16 WHERE Item = 'SchemaVer'", true);
				break;

			case 16:
				//Nothing to do here
				debuglog("Updating FROM Schema version 16 TO Schema version 17","SQL");
				generic_sql_query("UPDATE Statstable SET Value = 17 WHERE Item = 'SchemaVer'", true);
				break;

			case 17:
				debuglog("Updating FROM Schema version 17 TO Schema version 18","SQL");
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
				generic_sql_query("UPDATE Statstable SET Value = 19 WHERE Item = 'SchemaVer'");
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
				generic_sql_query("ALTER TABLE Playcounttable ADD COLUMN LastPlayed TIMESTAMP DEFAULT NULL", true);
				generic_sql_query("UPDATE Statstable SET Value = 22 WHERE Item = 'SchemaVer'", true);
				break;

			case 22:
				debuglog("Updating FROM Schema version 22 TO Schema version 23","SQL");
				generic_sql_query("ALTER TABLE Podcasttable ADD COLUMN Version TINYINT(2)", true);
				generic_sql_query("ALTER TABLE PodcastTracktable ADD COLUMN Guid VARCHAR(2000)", true);
				generic_sql_query("ALTER TABLE PodcastTracktable ADD COLUMN Localfilename VARCHAR(255)", true);
				generic_sql_query("UPDATE Podcasttable SET Version = 1 WHERE PODindex IS NOT NULL", true);
				generic_sql_query("UPDATE Statstable SET Value = 23 WHERE Item = 'SchemaVer'", true);
				break;

			case 23:
				debuglog("Updating FROM Schema version 23 TO Schema version 24","SQL");
				generic_sql_query("DROP TRIGGER IF EXISTS updatetime", true);
				generic_sql_query("UPDATE Statstable SET Value = 24 WHERE Item = 'SchemaVer'", true);
				break;

			case 24:
				debuglog("Updating FROM Schema version 24 TO Schema version 25","SQL");
				// Nothing to do here
				generic_sql_query("UPDATE Statstable SET Value = 25 WHERE Item = 'SchemaVer'", true);
				break;

			case 25:
				debuglog("Updating FROM Schema version 25 TO Schema version 26","SQL");
				generic_sql_query("ALTER TABLE Tracktable ADD justAdded TINYINT(1) DEFAULT 1", true);
				generic_sql_query("UPDATE Statstable SET Value = 26 WHERE Item = 'SchemaVer'", true);
				break;

			case 26:
				debuglog("Updating FROM Schema version 26 TO Schema version 27","SQL");
				generic_sql_query("ALTER TABLE Albumtable ADD justUpdated TINYINT(1) DEFAULT 1", true);
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
				// No need to do anything here
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
				generic_sql_query("ALTER TABLE Albumtable ADD COLUMN ImgVersion INTEGER DEFAULT ".ROMPR_IMAGE_VERSION, true);
				generic_sql_query("UPDATE Albumtable SET ImgVersion = 1",true);
				generic_sql_query("UPDATE Statstable SET Value = 34 WHERE Item = 'SchemaVer'", true);
				break;
				
			case 34:
				debuglog("Updating FROM Schema version 34 TO Schema version 35","SQL");
				generic_sql_query("ALTER TABLE Tracktable ADD COLUMN Sourceindex INTEGER DEFAULT NULL", true);
				generic_sql_query("UPDATE Statstable SET Value = 35 WHERE Item = 'SchemaVer'", true);
				break;

		}
		$sv++;
	}

	return array(true, "");
}

function delete_oldtracks() {
	// generic_sql_query("DROP TABLE IF EXISTS OldTracks", true);
	// generic_sql_query("CREATE TEMPORARY TABLE OldTracks AS SELECT TTindex FROM Tracktable JOIN Playcounttable USING (TTindex) WHERE Hidden = 1 AND DATETIME('now', '-6 MONTH') > DateAdded AND Playcount < 2", true);
	// generic_sql_query("DELETE FROM Tracktable WHERE TTindex IN (SELECT TTindex FROM OldTracks)", true);
}

function delete_orphaned_artists() {
	generic_sql_query("DROP TABLE IF EXISTS Croft", true);
	generic_sql_query("CREATE TEMPORARY TABLE Croft AS SELECT Artistindex FROM Tracktable UNION SELECT AlbumArtistindex FROM Albumtable", true);
	generic_sql_query("DELETE FROM Artisttable WHERE Artistindex NOT IN (SELECT Artistindex FROM Croft)", true);
}

function hide_played_tracks() {
	generic_sql_query("UPDATE Tracktable SET Hidden = 1, isSearchResult = 0 WHERE TTindex IN (SELECT TTindex FROM Tracktable JOIN Playcounttable USING (TTindex) WHERE isSearchResult = 2)", true);
}

function sql_recent_tracks() {
	return "SELECT Uri FROM Tracktable WHERE DATETIME('now', '-1 MONTH') <= DATETIME(DateAdded) AND Hidden = 0 AND isSearchResult < 2 AND Uri IS NOT NULL ORDER BY RANDOM()";
}

function sql_recent_albums() {
	return "SELECT Uri, Albumindex, TrackNo FROM Tracktable WHERE DATETIME('now', '-1 MONTH') <= DATETIME(DateAdded) AND Hidden = 0 AND isSearchResult < 2 AND Uri IS NOT NULL";
}

function sql_recently_played() {
	return "SELECT t.Uri, t.Title, a.Artistname, al.Albumname, al.Image, al.ImgKey, CAST(strftime('%s', p.LastPlayed) AS INT) AS unixtime FROM Tracktable AS t JOIN Playcounttable AS p USING (TTindex) JOIN Albumtable AS al USING (albumindex) JOIN Artisttable AS a ON (a.Artistindex = al.AlbumArtistindex) WHERE DATETIME('now', '-14 DAYS') <= DATETIME(p.LastPlayed) AND p.LastPlayed IS NOT NULL ORDER BY p.LastPlayed DESC";
}

function recently_played_playlist() {
	return "SELECT TTindex FROM Playcounttable AS Tracktable WHERE DATETIME('now', '-14 DAYS') <= DATETIME(LastPlayed) AND LastPlayed IS NOT NULL";
}

function sql_two_weeks() {
	return "DATETIME('now', '-14 DAYS') > DATETIME(LastPlayed)";
}

function sql_two_weeks_include($days) {
	return "DATETIME('now', '-".$days." DAYS') <= DATETIME(LastPlayed) AND LastPlayed IS NOT NULL";
}

function sql_to_unixtime($s) {
	return "CAST(strftime('%s', ".$s.") AS INT)";
}

function create_conditional_triggers() {
	generic_sql_query("CREATE TRIGGER track_insert_trigger AFTER INSERT ON Tracktable
						FOR EACH ROW
						WHEN NEW.Hidden=0
						BEGIN
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = NEW.Albumindex;
						END;", true);

	generic_sql_query("CREATE TRIGGER track_update_trigger AFTER UPDATE ON Tracktable
						FOR EACH ROW
						WHEN NEW.Hidden=0
						BEGIN
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = NEW.Albumindex;
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = OLD.Albumindex;
						END;", true);
}

?>
