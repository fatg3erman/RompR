<?php

class database extends data_base {

	const SQL_RANDOM_SORT = 'RANDOM()';
	const SQL_TAG_CONCAT = "GROUP_CONCAT(t.Name,', ') ";
	const SQL_URI_CONCAT = "GROUP_CONCAT(Uri,',') ";
	const STUPID_CONCAT_THING = "SELECT PODindex, PODTrackindex FROM PodcastTracktable WHERE Link = ? OR ? LIKE '%' || Localfilename";

	public function __construct() {
		try {
			$dsn = "sqlite:prefs/collection.sq3";
			logger::core('SQLITE','Opening collection',$dsn);
			$this->mysqlc = new PDO($dsn);
			logger::core("SQLITE", "Connected to SQLite");
			// This increases performance
			$this->generic_sql_query('PRAGMA journal_mode=DELETE', true);
			$this->generic_sql_query('PRAGMA cache_size=-4000', true);
			$this->generic_sql_query('PRAGMA synchronous=OFF', true);
			$this->generic_sql_query('PRAGMA threads=4', true);
		} catch (Exception $e) {
			logger::error("SQLITE", "Couldn't Connect To SQLite - ".$e);
			sql_init_fail($e->getMessage());
		}

	}

	protected function hide_played_tracks() {
		// If isSearchResult is 2, then it had to be added, therefore if itis subsequently rated it needs to become a manually added track,
		// so we need to set LastModified to NULL now.
		$this->generic_sql_query("UPDATE Tracktable SET Hidden = 1, isSearchResult = 0, LastModified = NULL WHERE TTindex IN (SELECT TTindex FROM Tracktable JOIN Playcounttable USING (TTindex) WHERE isSearchResult = 2)", true);
	}

	protected function sql_recent_tracks() {
		return "SELECT Uri FROM Tracktable JOIN Albumtable USING (Albumindex) WHERE DATETIME('now', '-2 MONTH') <= DATETIME(DateAdded)";
	}

	protected function init_random_albums() {
		$this->generic_sql_query('UPDATE Albumtable SET randomSort = RANDOM()', true);
	}

	public function sql_recently_played() {
		$tracks = $this->generic_sql_query(
			"SELECT
				t.Uri,
				t.Title,
				a.Artistname,
				al.Albumname,
				al.Image,
				al.ImgKey,
				strftime('%H:%M', p.LastPlayed) AS playtime,
				date(p.LastPlayed) AS playdate
			FROM Tracktable AS t
			JOIN Playcounttable AS p USING (TTindex)
			JOIN Albumtable AS al USING (Albumindex)
			JOIN Artisttable AS a ON (a.Artistindex = al.AlbumArtistindex)
			WHERE DATETIME('now', '-14 DAYS') <= DATETIME(p.LastPlayed)
			AND p.LastPlayed IS NOT NULL
			ORDER BY p.LastPlayed DESC",
			false
		);
		foreach ($tracks as &$track) {
			$track['playdate'] = date('l, jS F Y', strtotime($track['playdate']));
		}
		return $tracks;
	}

	protected function recently_played_playlist() {
		return "SELECT Uri FROM Playcounttable JOIN Tracktable USING (TTindex) WHERE DATETIME('now', '-14 DAYS') <= DATETIME(LastPlayed) AND LastPlayed IS NOT NULL";
	}

	public function sql_two_weeks() {
		return "DATETIME('now', '-14 DAYS') > DATETIME(LastPlayed)";
	}

	protected function sql_two_weeks_include($days) {
		return "DATETIME('now', '-".$days." DAYS') <= DATETIME(LastPlayed) AND LastPlayed IS NOT NULL";
	}

	protected function sql_to_unixtime($s) {
		return "CAST(strftime('%s', ".$s.") AS INT)";
	}

	public function track_date_check($range, $flag) {
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

	public function find_podcast_track_from_url($url) {
		return $this->sql_prepare_query(false, PDO::FETCH_OBJ, null, null,
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

	public function optimise_database() {
		$this->generic_sql_query("VACUUM", true);
		$this->generic_sql_query("PRAGMA optimize", true);
	}

	public function delete_orphaned_artists() {
		$this->generic_sql_query("DELETE FROM Artisttable WHERE Artistindex NOT IN (SELECT DISTINCT Artistindex FROM Tracktable UNION SELECT DISTINCT AlbumArtistindex AS Artistindex FROM Albumtable)");
	}

	protected function prepare_findtrack_for_update() {
		if ($this->find_track = $this->sql_prepare_query_later(
			"INSERT INTO Tracktable
				(Title, Albumindex, TrackNo, Duration, Artistindex, Disc, Uri, LastModified, isAudiobook, Genreindex, TYear)
			VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
			ON CONFLICT(Albumindex, Artistindex, TrackNo, Disc, Title) DO UPDATE SET
				Duration = excluded.Duration,
				Uri = excluded.Uri,
				LastModified = excluded.LastModified,
				Hidden = 0,
				justAdded = 1,
				isAudiobook = CASE WHEN isAudiobook = 2 THEN 2 ELSE excluded.isAudiobook END,
				Genreindex = excluded.Genreindex,
				TYear = excluded.TYear"
		)) {
			logger::log('SQLITE', 'Prepared new-style update query successfully');
		} else {
			$this->show_sql_error();
			exit(1);
		}
	}

	//
	// In the Search queries note the care taken around isSearchResult. Once it has been set to not 0, it must not be
	// altered if the track comes up again in the same search (as happens with Spotify). This is because the first time
	// might unhide a hidden track and so the second time sets isSearchResult to 1 because Hidden is now 0.
	// So we have a double CASE WHEN on that to make sure we check isSearchResult before Hidden
	//

	protected function prepare_findtrack_for_search() {
		if ($this->find_track = $this->sql_prepare_query_later(
			"INSERT INTO Tracktable
				(Title, Albumindex, TrackNo, Duration, Artistindex, Disc, Uri, LastModified, isSearchResult, isAudiobook, Genreindex, TYear)
			VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, 2, ?, ?, ?)
			ON CONFLICT(Albumindex, Artistindex, TrackNo, Disc, Title) DO UPDATE SET
				Duration = excluded.Duration,
				Uri = excluded.Uri,
				isSearchResult = CASE WHEN isSearchResult > 0 THEN isSearchResult ELSE CASE WHEN Hidden = 0 THEN 1 ELSE 3 END END,
				Hidden = 0,
				justAdded = 1,
				isAudiobook = CASE WHEN isAudiobook = 2 THEN 2 ELSE excluded.isAudiobook END,
				Genreindex = excluded.Genreindex,
				TYear = excluded.TYear"
		)) {
			logger::log('SQLITE', 'Prepared old-style update query successfully');
		} else {
			$this->show_sql_error();
			exit(1);
		}
	}

	protected function tracks_played_since($option, $value) {
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

			case RADIO_RULE_OPTIONS_INTEGER_ISNOT:
				return "(LastPlayed IS NULL OR DATE('now', '-".$value." DAYS') <> DATE(LastPlayed))";
				break;

		}

	}

	protected function check_youtube_uri_exists($uri) {
		logger::trace('CLEANER', 'Checking for', $uri);
		$bacon = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, array(),
			"SELECT TTindex FROM Tracktable WHERE Uri LIKE '%' || ? AND Hidden = ?",
			$uri,
			0
		);
		return count($bacon);
	}

	public function drop_triggers() {
		$triggers = $this->generic_sql_query("SELECT * FROM sqlite_master WHERE type = 'trigger'");
		foreach ($triggers as $trigger) {
			logger::debug('MYSQL', 'Dropping Trigger', $trigger['name']);
			$this->generic_sql_query('DROP TRIGGER '.$trigger['name']);
		}
	}

	public function create_triggers() {
		$this->create_update_triggers();
		$this->create_conditional_triggers();
		$this->create_playcount_triggers();
		$this->create_progress_triggers();
	}

	// Note there are multiple create_xxxx_triggers functions because we need
	// sometimes to be able to add them in batches when we're updating from
	// an older schema version.

	protected function create_conditional_triggers() {
		$this->generic_sql_query("CREATE TRIGGER IF NOT EXISTS track_insert_trigger AFTER INSERT ON Tracktable
							FOR EACH ROW
							WHEN NEW.Hidden=0
							BEGIN
							UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = NEW.Albumindex;
							END;", true);

		$this->generic_sql_query("CREATE TRIGGER IF NOT EXISTS track_update_trigger AFTER UPDATE ON Tracktable
							FOR EACH ROW
							WHEN (NEW.Hidden<>OLD.Hidden OR NEW.isAudiobook<>OLD.isAudiobook)
							BEGIN
							UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = NEW.Albumindex;
							UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = OLD.Albumindex;
							END;", true);
	}

	protected function create_update_triggers() {
		$this->generic_sql_query("CREATE TRIGGER IF NOT EXISTS rating_update_trigger AFTER UPDATE ON Ratingtable
							FOR EACH ROW
							BEGIN
							UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = NEW.TTindex);
							UPDATE Tracktable SET Hidden = 0, justAdded = 1 WHERE Hidden = 1 AND TTindex = NEW.TTindex;
							UPDATE Tracktable SET isSearchResult = 1, LastModified = NULL, justAdded = 1 WHERE isSearchResult > 1 AND TTindex = NEW.TTindex;
							END;", true);

		$this->generic_sql_query("CREATE TRIGGER IF NOT EXISTS rating_insert_trigger AFTER INSERT ON Ratingtable
							FOR EACH ROW
							BEGIN
							UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = NEW.TTindex);
							UPDATE Tracktable SET Hidden = 0, justAdded = 1 WHERE Hidden = 1 AND TTindex = NEW.TTindex;
							UPDATE Tracktable SET isSearchResult = 1, LastModified = NULL, justAdded = 1 WHERE isSearchResult > 1 AND TTindex = NEW.TTindex;
							END;", true);

		$this->generic_sql_query("CREATE TRIGGER IF NOT EXISTS tag_delete_trigger AFTER DELETE ON Tagtable
							FOR EACH ROW
							BEGIN
							DELETE FROM TagListtable WHERE Tagindex = OLD.Tagindex;
							END;", true);

		$this->generic_sql_query("CREATE TRIGGER IF NOT EXISTS tag_insert_trigger AFTER INSERT ON TagListtable
							FOR EACH ROW
							BEGIN
							UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = NEW.TTindex);
							UPDATE Tracktable SET Hidden = 0, justAdded = 1 WHERE Hidden = 1 AND TTindex = NEW.TTindex;
							UPDATE Tracktable SET isSearchResult = 1, LastModified = NULL, justAdded = 1 WHERE isSearchResult > 1 AND TTindex = NEW.TTindex;
							END;", true);

		$this->generic_sql_query("CREATE TRIGGER IF NOT EXISTS tag_remove_trigger AFTER DELETE ON TagListtable
							FOR EACH ROW
							BEGIN
							UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = OLD.TTindex);
							END;", true);

		$this->generic_sql_query("CREATE TRIGGER IF NOT EXISTS track_delete_trigger AFTER DELETE ON Tracktable
							FOR EACH ROW
							BEGIN
							UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = OLD.Albumindex;
							END;", true);

	}

	protected function create_playcount_triggers() {
		$this->generic_sql_query("CREATE TRIGGER IF NOT EXISTS syncupdatetrigger AFTER UPDATE ON Playcounttable
							FOR EACH ROW
							WHEN NEW.Playcount > OLD.Playcount
							BEGIN
								UPDATE Playcounttable SET SyncCount = OLD.SyncCount + 1 WHERE TTindex = New.TTindex;
							END;", true);

		$this->generic_sql_query("CREATE TRIGGER IF NOT EXISTS syncinserttrigger AFTER INSERT ON Playcounttable
							FOR EACH ROW
							BEGIN
								UPDATE Playcounttable SET SyncCount = 1 WHERE TTindex = NEW.TTindex;
							END;", true);

	}

	protected function create_progress_triggers() {
		$this->generic_sql_query("CREATE TRIGGER IF NOT EXISTS progress_update_trigger AFTER UPDATE ON Bookmarktable
							FOR EACH ROW
							BEGIN
							UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = NEW.TTindex);
							END;", true);

		$this->generic_sql_query("CREATE TRIGGER IF NOT EXISTS progress_insert_trigger AFTER INSERT ON Bookmarktable
							FOR EACH ROW
							BEGIN
							UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = NEW.TTindex);
							END;", true);

	}

	public function create_toptracks_table() {
		$name = lastfm_radio::get_seed_table_name();
		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS ".$name."(".
			"topindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
			"Type INTEGER NOT NULL, ".
			"Artist VARCHAR(100) COLLATE NOCASE, ".
			"Title VARCHAR(255) COLLATE NOCASE)", true))
		{
			logger::log("SQLITE",$name,"OK");
			$this->generic_sql_query("DELETE FROM ".$name);
			$this->generic_sql_query("CREATE UNIQUE INDEX IF NOT EXISTS nodupes_".$name." ON ".$name." (Artist, Title)");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking ".$name." : ".$err);
		}
	}

	public function add_toptrack($type, $artist, $title) {
		$name = lastfm_radio::get_seed_table_name();
		$this->sql_prepare_query(true, null, null, null,
			"INSERT OR IGNORE INTO ".$name." (Type, Artist, Title) VALUES (?, ? ,?)",
			$type,
			$artist,
			$title
		);
	}

	public function create_radio_uri_table() {
		$name = lastfm_radio::get_uri_table_name();
		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS ".$name."(".
			"uriindex INTEGER PRIMARY KEY NOT NULL UNIQUE, ".
			"Uri TEXT)", true))
		{
			logger::log("SQLITE",$name,"OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking ".$name." : ".$err);
		}
		$this->generic_sql_query("DELETE FROM ".$name);
	}
}
?>