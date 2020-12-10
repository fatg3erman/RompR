<?php

define('SQL_RANDOM_SORT', 'RANDOM()');
define('SQL_TAG_CONCAT', "GROUP_CONCAT(t.Name,', ') ");
define('SQL_URI_CONCAT', "GROUP_CONCAT(Uri,',') ");
define('STUPID_CONCAT_THING', "SELECT PODindex, PODTrackindex FROM PodcastTracktable WHERE Link = ? OR ? LIKE '%' || Localfilename");

function connect_to_database($sp = true) {
	global $mysqlc;
	if ($mysqlc !== null) {
		logger::error("SQLITE", "AWOOOGA! ATTEMPTING MULTIPLE DATABASE CONNECTIONS!");
		return;
	}
	try {
		$dsn = "sqlite:prefs/collection.sq3";
		logger::core('SQLITE','Opening collection',$dsn);
		$mysqlc = new PDO($dsn);
		logger::core("SQLITE", "Connected to SQLite");
		// This increases performance
		generic_sql_query('PRAGMA journal_mode=DELETE', true);
		generic_sql_query('PRAGMA cache_size=-4000', true);
		generic_sql_query('PRAGMA synchronous=OFF', true);
		generic_sql_query('PRAGMA threads=4', true);
		readCollectionPlayer($sp);
	} catch (Exception $e) {
		logger::error("SQLITE", "Couldn't Connect To SQLite - ".$e);
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
		"LinkChecked TINYINT(1) DEFAULT 0, ".
		"isAudiobook TINYINT(1) DEFAULT 0, ".
		"justAdded TINYINT(1) DEFAULT 1, ".
		"usedInPlaylist TINYINT(1) DEFAULT 0, ".
		"Genreindex INT UNSIGNED DEFAULT 0, ".
		"TYear YEAR)", true))
	{
		logger::log("SQLITE", "  Tracktable OK");
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
		"randomSort INT DEFAULT 0, ".
		"justUpdated TINYINT(1) DEFAULT 0)", true))
	{
		logger::log("SQLITE", "  Albumtable OK");
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
		logger::log("SQLITE", "  Artisttable OK");
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
		logger::log("SQLITE", "  Ratingtable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Ratingtable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Progresstable(".
		"TTindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
		"Progress INTEGER)", true))
	{
		logger::log("SQLITE", "  Progresstable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Progresstable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Tagtable(".
		"Tagindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
		"Name VARCHAR(255))", true))
	{
		logger::log("SQLITE", "  Tagtable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Tagtable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS TagListtable(".
		"Tagindex INTEGER NOT NULL REFERENCES Tagtable(Tagindex), ".
		"TTindex INTEGER NOT NULL REFERENCES Tracktable(TTindex), ".
		"PRIMARY KEY (Tagindex, TTindex))", true))
	{
		logger::log("SQLITE", "  TagListtable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking TagListtable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Playcounttable(".
		"TTindex INTEGER PRIMARY KEY NOT NULL UNIQUE REFERENCES Tracktable(TTindex), ".
		"Playcount INT UNSIGNED NOT NULL, ".
		"SyncCount INT UNSIGNED DEFAULT 0, ".
		"LastPlayed TIMESTAMP DEFAULT NULL)", true))
	{
		logger::log("SQLITE", "  Playcounttable OK");
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
		"Description TEXT, ".
		"LastPubDate INTEGER DEFAULT NULL, ".
		"WriteTags TINYINT(1) DEFAULT 0, ".
		"Category VARCHAR(255))", true))
	{
		logger::log("SQLITE", "  Podcasttable OK");
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
		"Progress INTEGER DEFAULT 0, ".
		"Deleted TINYINT(1) DEFAULT 0)", true))
	{
		logger::log("SQLITE", "  PodcastTracktable OK");
		if (generic_sql_query("CREATE INDEX IF NOT EXISTS ptt ON PodcastTracktable (Title)", true)) {
		} else {
			$err = $mysqlc->errorInfo()[2];
			return array(false, "Error While Checking PodcastTracktable : ".$err);
		}
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
		logger::log("SQLITE", "  RadioStationtable OK");
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
		logger::log("SQLITE", "  RadioTracktable OK");
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
		logger::log("SQLITE", "  WishlistSourcetable OK");
		if (generic_sql_query("CREATE INDEX IF NOT EXISTS suri ON WishlistSourcetable (SourceUri)", true)) {
		} else {
			$err = $mysqlc->errorInfo()[2];
			return array(false, "Error While Checking WishlistSourcetable : ".$err);
		}
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking WishlistSourcetable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS AlbumsToListenTotable(".
		"Listenindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
		"JsonData TEXT)", true))
	{
		logger::log("SQLITE", "  AlbumsToListenTotabletable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking AlbumsToListenTotable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS BackgroundImageTable(".
		"BgImageIndex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
		"Skin VARCHAR(255), ".
		"BrowserID VARCHAR(20) DEFAULT NULL, ".
		"Filename VARCHAR(255), ".
		"Orientation TINYINT(2))", true))
	{
		logger::log("SQLITE", "  BackgounrdImageTable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking BackgroundImageTable : ".$err);
	}

	if (generic_sql_query("CREATE TABLE IF NOT EXISTS Genretable(".
		"Genreindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
		"Genre VARCHAR(40))", true))
	{
		logger::log("SQLITE", "  Genretable OK");
	} else {
		$err = $mysqlc->errorInfo()[2];
		return array(false, "Error While Checking Genretable : ".$err);
	}
	generic_sql_query("CREATE INDEX IF NOT EXISTS gi ON Genretable (Genre)", true);

	// Check schema version and update tables as necessary
	$sv = simple_query('Value', 'Statstable', 'Item', 'SchemaVer', 0);
	if ($sv == 0) {
		logger::mark("SQLITE", "No Schema Version Found - initialising table");
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('ListVersion', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('ArtistCount', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('AlbumCount', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('TrackCount', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('TotalTime', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('CollType', '999')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('SchemaVer', '".ROMPR_SCHEMA_VERSION."')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('BookArtists', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('BookAlbums', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('BookTracks', '0')", true);
		generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('BookTime', '0')", true);
		$sv = ROMPR_SCHEMA_VERSION;
		logger::log("SQLITE", "Statstable populated");
		create_update_triggers();
		create_conditional_triggers();
		create_playcount_triggers();
	}

	if ($sv > ROMPR_SCHEMA_VERSION) {
		logger::warn("SQLITE", "Schema Mismatch! We are version ".ROMPR_SCHEMA_VERSION." but database is version ".$sv);
		return array(false, "Your database has version number ".$sv." but this version of rompr only handles version ".ROMPR_SCHEMA_VERSION);
	}

	while ($sv < ROMPR_SCHEMA_VERSION) {
		switch ($sv) {
			case 0:
				logger::log("SQL", "BIG ERROR! No Schema Version found!!");
				return array(false, "Database Error - could not read schema version. Cannot continue.");
				break;

			case 11:
				logger::log("SQL", "Updating FROM Schema version 11 TO Scheme version 12");
				generic_sql_query("ALTER TABLE Tracktable ADD isSearchResult TINYINT(1) DEFAULT 0", true);
				generic_sql_query("UPDATE Statstable SET Value = 12 WHERE Item = 'SchemaVer'", true);
				break;

			case 12;
				logger::log("SQL", "Updating FROM Schema version 12 TO Scheme version 13");
				// First attempt didn't work
				generic_sql_query("UPDATE Statstable SET Value = 13 WHERE Item = 'SchemaVer'", true);
				break;

			case 13:
				// SQLite doesn't let you rename or remove a column. Holy Shitting heck.
				logger::log("SQL", "Updating FROM Schema version 13 TO Schema version 14");
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
				logger::log("SQL", "Updating FROM Schema version 14 TO Schema version 15");
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
				logger::log("SQL", "Updating FROM Schema version 15 TO Schema version 16");
				albumImageBuggery();
				generic_sql_query("UPDATE Statstable SET Value = 16 WHERE Item = 'SchemaVer'", true);
				break;

			case 16:
				//Nothing to do here
				logger::log("SQL", "Updating FROM Schema version 16 TO Schema version 17");
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
					logger::trace("SQL", "  Creating new Album ".$obj->ttit." Image ".$obj->ti);
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
							logger::debug("SQL", "    .. success, Albumindex ".$retval);
							generic_sql_query("UPDATE Tracktable SET Albumindex = ".$retval." WHERE TTindex = ".$obj->TTindex, true);
					} else {
						logger::warn("SQL", "    .. ERROR!");
					}
				}
				generic_sql_query("UPDATE Statstable SET Value = 19 WHERE Item = 'SchemaVer'");
				break;

			case 19:
				logger::log("SQL", "Updating FROM Schema version 19 TO Schema version 20");
				$result = generic_sql_query('SELECT Tracktable.Uri AS uri, Tracktable.TTindex, Tracktable.Title AS ttit, Albumtable.*, Trackimagetable.Image AS ti FROM Tracktable JOIN Albumtable USING (Albumindex) LEFT JOIN Trackimagetable USING (TTindex) WHERE Tracktable.Uri LIKE "youtube:%"', false, PDO::FETCH_OBJ);
				foreach ($result as $obj) {
					logger::trace("SQL", "  Creating new Album ".$obj->ttit." Image ".$obj->ti);
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
							logger::debug("SQL", "    .. success, Albumindex ".$retval);
							generic_sql_query("UPDATE Tracktable SET Albumindex = ".$retval." WHERE TTindex = ".$obj->TTindex, true);
					} else {
						logger::warn("SQL", "    .. ERROR!");
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
				generic_sql_query("ALTER TABLE Playcounttable ADD COLUMN LastPlayed TIMESTAMP DEFAULT NULL", true);
				generic_sql_query("UPDATE Statstable SET Value = 22 WHERE Item = 'SchemaVer'", true);
				break;

			case 22:
				logger::log("SQL", "Updating FROM Schema version 22 TO Schema version 23");
				generic_sql_query("ALTER TABLE Podcasttable ADD COLUMN Version TINYINT(2)", true);
				generic_sql_query("ALTER TABLE PodcastTracktable ADD COLUMN Guid VARCHAR(2000)", true);
				generic_sql_query("ALTER TABLE PodcastTracktable ADD COLUMN Localfilename VARCHAR(255)", true);
				generic_sql_query("UPDATE Podcasttable SET Version = 1 WHERE PODindex IS NOT NULL", true);
				generic_sql_query("UPDATE Statstable SET Value = 23 WHERE Item = 'SchemaVer'", true);
				break;

			case 23:
				logger::log("SQL", "Updating FROM Schema version 23 TO Schema version 24");
				generic_sql_query("DROP TRIGGER IF EXISTS updatetime", true);
				generic_sql_query("UPDATE Statstable SET Value = 24 WHERE Item = 'SchemaVer'", true);
				break;

			case 24:
				logger::log("SQL", "Updating FROM Schema version 24 TO Schema version 25");
				// Nothing to do here
				generic_sql_query("UPDATE Statstable SET Value = 25 WHERE Item = 'SchemaVer'", true);
				break;

			case 25:
				logger::log("SQL", "Updating FROM Schema version 25 TO Schema version 26");
				generic_sql_query("ALTER TABLE Tracktable ADD justAdded TINYINT(1) DEFAULT 1", true);
				generic_sql_query("UPDATE Statstable SET Value = 26 WHERE Item = 'SchemaVer'", true);
				break;

			case 26:
				logger::log("SQL", "Updating FROM Schema version 26 TO Schema version 27");
				generic_sql_query("ALTER TABLE Albumtable ADD justUpdated TINYINT(1) DEFAULT 1", true);
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
				// No need to do anything here
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
				generic_sql_query("ALTER TABLE Albumtable ADD COLUMN ImgVersion INTEGER DEFAULT ".ROMPR_IMAGE_VERSION, true);
				generic_sql_query("UPDATE Albumtable SET ImgVersion = 1",true);
				generic_sql_query("UPDATE Statstable SET Value = 34 WHERE Item = 'SchemaVer'", true);
				break;

			case 34:
				logger::log("SQL", "Updating FROM Schema version 34 TO Schema version 35");
				generic_sql_query("ALTER TABLE Tracktable ADD COLUMN Sourceindex INTEGER DEFAULT NULL", true);
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
				generic_sql_query("CREATE TABLE IF NOT EXISTS Albumtable_New(".
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
					"justUpdated TINYINT(1) DEFAULT 0)", true);
				generic_sql_query("INSERT INTO Albumtable_New SELECT Albumindex, Albumname, AlbumArtistindex,
					AlbumUri, Year, Searched, ImgKey, mbid, ImgVersion, Domain, Image, justUpdated
					FROM Albumtable", true);
				generic_sql_query("DROP TABLE Albumtable", true);
				generic_sql_query("ALTER TABLE Albumtable_New RENAME TO Albumtable", true);
				generic_sql_query("CREATE INDEX IF NOT EXISTS ni ON Albumtable (Albumname)", true);
				generic_sql_query("CREATE INDEX IF NOT EXISTS aai ON Albumtable (AlbumArtistindex)", true);
				generic_sql_query("CREATE INDEX IF NOT EXISTS di ON Albumtable (Domain)", true);
				generic_sql_query("CREATE INDEX IF NOT EXISTS ii ON Albumtable (ImgKey)", true);
				generic_sql_query("UPDATE Statstable SET Value = 38 WHERE Item = 'SchemaVer'", true);
				break;

			case 38:
				logger::log("SQL", "Updating FROM Schema version 38 TO Schema version 39");
				generic_sql_query("ALTER TABLE Podcasttable ADD LastPubDate INTEGER DEFAULT NULL", true);
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
				generic_sql_query("CREATE TABLE IF NOT EXISTS Podcasttable_New(".
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
					"Description TEXT, ".
					"LastPubDate INTEGER DEFAULT NULL, ".
					"Category VARCHAR(255))", true);
				generic_sql_query("INSERT INTO Podcasttable_New SELECT PODindex, FeedURL, LastUpdate, Image, Title, Artist,
					RefreshOption, SortMode, HideDescriptions, DisplayMode, DaysToKeep, NumToKeep, KeepDownloaded, AutoDownload,
					DaysLive, Version, Subscribed, Description, LastPubDate, '' AS Category
					FROM Podcasttable", true);
				generic_sql_query("DROP TABLE Podcasttable", true);
				generic_sql_query("ALTER TABLE Podcasttable_New RENAME TO Podcasttable", true);
				generic_sql_query("UPDATE Statstable SET Value = 41 WHERE Item = 'SchemaVer'", true);
				break;

			case 41:
				logger::log("SQL", "Updating FROM Schema version 41 TO Schema version 42");
				generic_sql_query("CREATE TABLE IF NOT EXISTS PodcastTracktable_New(".
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
					"Progress INTEGER DEFAULT 0, ".
					"Deleted TINYINT(1) DEFAULT 0)", true);
				generic_sql_query("INSERT INTO PodcastTracktable_New SELECT PODTrackindex, JustUpdated, PODindex, Title, Artist,
					Duration, PubDate, FileSize, Description, Link, Guid, Localfilename, Downloaded, Listened, New, 0 AS Progress, Deleted
					FROM PodcastTracktable", true);
				generic_sql_query("DROP TABLE PodcastTracktable", true);
				generic_sql_query("ALTER TABLE PodcastTracktable_New RENAME TO PodcastTracktable", true);
				generic_sql_query("CREATE INDEX IF NOT EXISTS ptt ON PodcastTracktable (Title)", true);
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
				prefs::upgrade_host_defs(45);
				generic_sql_query("UPDATE Statstable SET Value = 45 WHERE Item = 'SchemaVer'", true);
				break;

			case 45:
				logger::log("SQL", "Updating FROM Schema version 45 TO Schema version 46");
				generic_sql_query("UPDATE Statstable SET Value = 46 WHERE Item = 'SchemaVer'", true);
				break;

			case 46:
				logger::log("SQL", "Updating FROM Schema version 46 TO Schema version 47");
				generic_sql_query("ALTER TABLE Playcounttable ADD COLUMN SyncCount INT UNSIGNED DEFAULT 0", true);
				generic_sql_query("UPDATE Statstable SET Value = 47 WHERE Item = 'SchemaVer'", true);
				create_playcount_triggers();
				break;

			case 47:
				logger::log("SQL", "Updating FROM Schema version 47 TO Schema version 48");
				generic_sql_query("UPDATE Statstable SET Value = 48 WHERE Item = 'SchemaVer'", true);
				break;

			case 48:
				logger::log("SQL", "Updating FROM Schema version 48 TO Schema version 49");
				prefs::upgrade_host_defs(49);
				generic_sql_query("UPDATE Statstable SET Value = 49 WHERE Item = 'SchemaVer'", true);
				break;

			case 49:
				logger::log("SQL", "Updating FROM Schema version 49 TO Schema version 50");
				generic_sql_query("ALTER TABLE Tracktable ADD COLUMN LinkChecked TINYINT(1) DEFAULT 0", true);
				generic_sql_query("UPDATE Statstable SET Value = 50 WHERE Item = 'SchemaVer'", true);
				break;

			case 50:
				logger::log("SQL", "Updating FROM Schema version 50 TO Schema version 51");
				generic_sql_query("UPDATE Statstable SET Value = 51 WHERE Item = 'SchemaVer'", true);
				break;

			case 51:
				logger::log("SQL", "Updating FROM Schema version 51 TO Schema version 52");
				generic_sql_query("ALTER TABLE Tracktable ADD COLUMN isAudiobook TINYINT(1) DEFAULT 0", true);
				generic_sql_query("UPDATE Statstable SET Value = 52 WHERE Item = 'SchemaVer'", true);
				break;

			case 52:
				logger::log("SQL", "Updating FROM Schema version 52 TO Schema version 53");
				create_progress_triggers();
				generic_sql_query("UPDATE Statstable SET Value = 53 WHERE Item = 'SchemaVer'", true);
				break;

			case 53:
				logger::log("SQL", "Updating FROM Schema version 53 TO Schema version 54");
				backgroundImages::first_upgrade_of_user_backgrounds();
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
				generic_sql_query("UPDATE Statstable SET Value = 57 WHERE Item = 'SchemaVer'", true);
				break;

			case 57:
				generic_sql_query("UPDATE Statstable SET Value = 58 WHERE Item = 'SchemaVer'", true);
				break;

			case 58:
				logger::log("SQL", "Updating FROM Schema version 58 TO Schema version 59");
				update_remote_image_urls();
				generic_sql_query("UPDATE Statstable SET Value = 59 WHERE Item = 'SchemaVer'", true);
				break;

			case 59:
				logger::log("SQL", "Updating FROM Schema version 59 TO Schema version 60");
				generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('BookArtists', '0')", true);
				generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('BookAlbums', '0')", true);
				generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('BookTracks', '0')", true);
				generic_sql_query("INSERT INTO Statstable (Item, Value) VALUES ('BookTime', '0')", true);
				generic_sql_query("UPDATE Statstable SET Value = 60 WHERE Item = 'SchemaVer'", true);
				break;

			case 60:
				logger::log("SQL", "Updating FROM Schema version 60 TO Schema version 61");
				generic_sql_query("ALTER TABLE Tracktable ADD COLUMN usedInPlaylist TINYINT(1) DEFAULT 0", true);
				generic_sql_query("UPDATE Statstable SET Value = 61 WHERE Item = 'SchemaVer'", true);
				break;

			case 61:
				logger::log("SQL", "Updating FROM Schema version 61 TO Schema version 62");
				generic_sql_query("ALTER TABLE Albumtable ADD COLUMN randomSort INT DEFAULT 0", true);
				generic_sql_query("UPDATE Statstable SET Value = 62 WHERE Item = 'SchemaVer'", true);
				break;

			case 62:
				logger::log("SQL", "Updating FROM Schema version 62 TO Schema version 63");
				upgrade_saved_crazies();
				generic_sql_query("UPDATE Statstable SET Value = 63 WHERE Item = 'SchemaVer'", true);
				break;

			case 63:
				logger::log("SQL", "Updating FROM Schema version 63 TO Schema version 64");
				generic_sql_query("ALTER TABLE Tracktable ADD COLUMN Genreindex INT UNSIGNED DEFAULT 0", true);
				// generic_sql_query("INSERT INTO Genretable (Genre) VALUES ('None')", true);
				generic_sql_query("UPDATE Statstable SET Value = 64 WHERE Item = 'SchemaVer'", true);
				break;

			case 64:
				logger::log("SQL", "Updating FROM Schema version 64 TO Schema version 65");
				generic_sql_query("ALTER TABLE Tracktable ADD COLUMN TYear YEAR", true);
				// generic_sql_query("INSERT INTO Genretable (Genre) VALUES ('None')", true);
				generic_sql_query("UPDATE Statstable SET Value = 65 WHERE Item = 'SchemaVer'", true);
				break;

			case 65:
				logger::log("SQL", "Updating FROM Schema version 65 TO Schema version 66");
				update_track_dates();
				generic_sql_query("UPDATE Statstable SET Value = 66 WHERE Item = 'SchemaVer'", true);
				break;

			case 66:
				logger::log("SQL", "Updating FROM Schema version 66 TO Schema version 67");
				generic_sql_query("ALTER TABLE Podcasttable ADD COLUMN WriteTags TINYINT(1) DEFAULT 0", true);
				generic_sql_query("UPDATE Statstable SET Value = 67 WHERE Item = 'SchemaVer'", true);
				break;

			case 67:
				logger::log("SQL", "Updating FROM Schema version 67 TO Schema version 68");
				prefs::upgrade_host_defs(68);
				generic_sql_query("UPDATE Statstable SET Value = 68 WHERE Item = 'SchemaVer'", true);
				break;

			case 68:
				logger::log("SQL", "Updating FROM Schema version 68 TO Schema version 69");
				prefs::upgrade_host_defs(69);
				generic_sql_query("UPDATE Statstable SET Value = 69 WHERE Item = 'SchemaVer'", true);
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

function mb4_bodge() {
	return '';
}

function delete_orphaned_artists() {
	generic_sql_query("CREATE TEMPORARY TABLE Croft AS SELECT Artistindex FROM Tracktable UNION SELECT AlbumArtistindex FROM Albumtable");
	generic_sql_query("DELETE FROM Artisttable WHERE Artistindex NOT IN (SELECT Artistindex FROM Croft)");
}

function hide_played_tracks() {
	generic_sql_query("UPDATE Tracktable SET Hidden = 1, isSearchResult = 0 WHERE TTindex IN (SELECT TTindex FROM Tracktable JOIN Playcounttable USING (TTindex) WHERE isSearchResult = 2)", true);
}

function sql_recent_tracks() {
	return "SELECT Uri FROM Tracktable JOIN Albumtable USING (Albumindex) WHERE DATETIME('now', '-2 MONTH') <= DATETIME(DateAdded)";
}

function init_random_albums() {
	generic_sql_query('UPDATE Albumtable SET randomSort = RANDOM()', true);
}

function sql_recently_played() {
	return "SELECT t.Uri, t.Title, a.Artistname, al.Albumname, al.Image, al.ImgKey, CAST(strftime('%s', p.LastPlayed) AS INT) AS unixtime FROM Tracktable AS t JOIN Playcounttable AS p USING (TTindex) JOIN Albumtable AS al USING (albumindex) JOIN Artisttable AS a ON (a.Artistindex = al.AlbumArtistindex) WHERE DATETIME('now', '-14 DAYS') <= DATETIME(p.LastPlayed) AND p.LastPlayed IS NOT NULL ORDER BY p.LastPlayed DESC";
}

function recently_played_playlist() {
	$qstring = "SELECT Uri FROM Playcounttable JOIN Tracktable USING (TTindex) WHERE DATETIME('now', '-14 DAYS') <= DATETIME(LastPlayed) AND LastPlayed IS NOT NULL";
	return $qstring;
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

function tracks_played_since($option, $value) {
	$value = round($value);
	switch ($option) {
		case RADIO_RULE_OPTIONS_INTEGER_LESSTHAN:
			return "(LastPlayed IS NOT NULL AND DATE('now', '-".$value." DAYS') < DATE(LastPlayed))";
			break;

		case RADIO_RULE_OPTIONS_INTEGER_EQUALS:
			return "(LastPlayed IS NOT NULL AND DATE('now', '-".$value." DAYS') = DATE(LastPlayed))";
			break;

		case RADIO_RULE_OPTIONS_INTEGER_GREATERTHAN:
			return "(LastPlayed IS NULL OR DATE('now', '-".$value." DAYS') > DATE(LastPlayed))";
			break;

	}

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
			return "AND DATETIME('now', '-1 DAYS') <= DateAdded";
			break;

		case ADDED_THIS_WEEK:
			return "AND DATETIME('now', '-7 DAYS') <= DateAdded";
			break;

		case ADDED_THIS_MONTH:
			return "AND DATETIME('now', '-1 MONTHS') <= DateAdded";
			break;

		case ADDED_THIS_YEAR:
			return "AND DATETIME('now', '-1 YEAR') <= DateAdded";
			break;

		default:
			logger::error("SQL", "ERROR! Unknown Collection Range ".$range);
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
									OR ? LIKE '%' || PodcastTracktable.Localfilename",
									$url,
									$url);
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

function create_playcount_triggers() {
	generic_sql_query("CREATE TRIGGER syncupdatetrigger AFTER UPDATE ON Playcounttable
						FOR EACH ROW
						WHEN NEW.Playcount > OLD.Playcount
						BEGIN
							UPDATE Playcounttable SET SyncCount = OLD.SyncCount + 1 WHERE TTindex = New.TTindex;
						END;", true);

	generic_sql_query("CREATE TRIGGER syncinserttrigger AFTER INSERT ON Playcounttable
						FOR EACH ROW
						BEGIN
							UPDATE Playcounttable SET SyncCount = 1 WHERE TTindex = NEW.TTindex;
						END;", true);

}

function create_update_triggers() {

	logger::log("SQLITE", "Creating Triggers for update operation");

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
						BEGIN
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = OLD.TTindex);
						END;", true);

	generic_sql_query("CREATE TRIGGER track_delete_trigger AFTER DELETE ON Tracktable
						FOR EACH ROW
						BEGIN
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = OLD.Albumindex;
						END;", true);

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
