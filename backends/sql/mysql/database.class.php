<?php

class database extends data_base {

	const SQL_RANDOM_SORT = 'RAND()';
	const SQL_TAG_CONCAT = "GROUP_CONCAT(t.Name SEPARATOR ', ') ";
	const SQL_URI_CONCAT = "GROUP_CONCAT(Uri SEPARATOR ',') ";
	const STUPID_CONCAT_THING = "SELECT PODindex, PODTrackindex FROM PodcastTracktable WHERE Link = ? OR ? LIKE CONCAT('%', Localfilename)";

	public function __construct() {
		try {
			if (is_numeric(prefs::$prefs['mysql_port'])) {
				logger::core("MYSQL", "Connecting using hostname and port");
				$dsn = "mysql:host=".prefs::$prefs['mysql_host'].";port=".prefs::$prefs['mysql_port'].";dbname=".prefs::$prefs['mysql_database'].";charset=utf8mb4";
			} else {
				logger::core("MYSQL", "Connecting using unix socket");
				$dsn = "mysql:unix_socket=".prefs::$prefs['mysql_port'].";dbname=".prefs::$prefs['mysql_database'].";charset=utf8mb4";
			}
			$this->mysqlc = new PDO($dsn, prefs::$prefs['mysql_user'], prefs::$prefs['mysql_password']);
			logger::core("MYSQL", "Connected to MySQL");
			$this->generic_sql_query('SET SESSION sql_mode="STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION"', true);
		} catch (Exception $e) {
			logger::warn("MYSQL", "Database connect failure - ".$e);
			sql_init_fail($e->getMessage());
		}
	}

	protected function hide_played_tracks() {
		$this->generic_sql_query("CREATE TEMPORARY TABLE Fluff(TTindex INT UNSIGNED NOT NULL UNIQUE, PRIMARY KEY(TTindex)) ENGINE=MEMORY AS SELECT TTindex FROM Tracktable JOIN Playcounttable USING (TTindex) WHERE isSearchResult = 2", true);
		$this->generic_sql_query("UPDATE Tracktable SET Hidden = 1, isSearchResult = 0 WHERE TTindex IN (SELECT TTindex FROM Fluff)", true);
	}

	protected function sql_recent_tracks() {
		return "SELECT Uri FROM Tracktable JOIN Albumtable USING (Albumindex) WHERE (DATE_SUB(CURDATE(),INTERVAL 60 DAY) <= DateAdded)";
	}

	public function sql_recently_played() {
		return $this->generic_sql_query(
			"SELECT t.Uri, t.Title, a.Artistname, al.Albumname, al.Image, al.ImgKey, UNIX_TIMESTAMP(p.LastPlayed) AS unixtime
				FROM Tracktable AS t
				JOIN Playcounttable AS p USING (TTindex)
				JOIN Albumtable AS al USING (Albumindex)
				JOIN Artisttable AS a ON (a.Artistindex = al.AlbumArtistindex)
				WHERE DATE_SUB(CURDATE(),INTERVAL 14 DAY) <= p.LastPlayed
				AND p.LastPlayed IS NOT NULL
				ORDER BY p.LastPlayed DESC",
			false,
			PDO::FETCH_OBJ
		);
	}

	protected function init_random_albums() {
		$this->generic_sql_query('UPDATE Albumtable SET randomSort = RAND() * 10000000', true);
	}

	protected function recently_played_playlist() {
		return "SELECT Uri FROM Playcounttable JOIN Tracktable USING (TTindex) WHERE DATE_SUB(CURDATE(),INTERVAL 14 DAY) <= LastPlayed AND LastPlayed IS NOT NULL";
	}

	protected function sql_two_weeks() {
		return "DATE_SUB(CURDATE(),INTERVAL 14 DAY) > LastPlayed";
	}

	protected function sql_two_weeks_include($days) {
		return "DATE_SUB(CURDATE(),INTERVAL ".$days." DAY) <= LastPlayed  AND LastPlayed IS NOT NULL";
	}

	protected function sql_to_unixtime($s) {
		return "UNIX_TIMESTAMP(".$s.")";
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
										OR ? LIKE CONCAT('%', PodcastTracktable.Localfilename)",
										$url,
										$url);
	}

	public function optimise_database() {

	}

	public function delete_orphaned_artists() {
		// MariaDB doesn't like using a UNION in the select when we create the table. MySQL is fine with it but we have to
		// cope with the retarded backwards fork.
		$this->generic_sql_query("CREATE TEMPORARY TABLE Cruft(Artistindex INT UNSIGNED) ENGINE=MEMORY AS (SELECT DISTINCT Artistindex FROM Tracktable)");
		$this->generic_sql_query('INSERT INTO Cruft (SELECT DISTINCT AlbumArtistindex AS Artistindex FROM Albumtable)');
		$this->generic_sql_query("DELETE FROM Artisttable WHERE Artistindex NOT IN (SELECT Artistindex FROM Cruft)");
		$this->generic_sql_query("DROP TABLE Cruft");
	}

	protected function prepare_findtrack_for_update() {
		if (prefs::$prefs['old_style_sql']) {
			$this->prepare_update_findtrack_old();
		} else {
			$this->prepare_update_findtrack_new();
		}
	}

	private function prepare_update_findtrack_old() {
		if ($this->find_track = $this->sql_prepare_query_later(
			"INSERT INTO Tracktable
				(Title, Albumindex, TrackNo, Duration, Artistindex, Disc, Uri, LastModified, isAudiobook, Genreindex, TYear)
			VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
			ON DUPLICATE KEY UPDATE
				Duration = VALUES(Duration),
				Uri = VALUES(Uri),
				LastModified = VALUES(LastModified),
				Hidden = 0,
				justAdded = 1,
				isAudiobook = CASE WHEN isAudiobook = 2 THEN 2 ELSE VALUES(isAudiobook) END,
				Genreindex = VALUES(Genreindex),
				TYear = VALUES(TYear)"
		)) {
			logger::log('MYSQL', 'Prepared old-style update query successfully');
		} else {
			$this->show_sql_error();
			exit(1);
		}
	}

	private function prepare_update_findtrack_new() {
		if ($this->find_track = $this->sql_prepare_query_later(
			"INSERT INTO Tracktable
				(Title, Albumindex, TrackNo, Duration, Artistindex, Disc, Uri, LastModified, isAudiobook, Genreindex, TYear)
			VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) AS new
			ON DUPLICATE KEY UPDATE
				Duration = new.Duration,
				Uri = new.Uri,
				LastModified = new.LastModified,
				Hidden = 0,
				justAdded = 1,
				isAudiobook = CASE WHEN Tracktable.isAudiobook = 2 THEN 2 ELSE new.isAudiobook END,
				Genreindex = new.Genreindex,
				TYear = new.TYear"
		)) {
			logger::log('MYSQL', 'Prepared new-style update query successfully');
		} else {
			$this->show_sql_error();
			exit(1);
		}
	}

	protected function prepare_findtrack_for_search() {
		if (prefs::$prefs['old_style_sql']) {
			$this->prepare_search_findtrack_old();
		} else {
			$this->prepare_search_findtrack_new();
		}
	}

	//
	// In the Search queries note the care taken around isSearchResult. Once it has been set to not 0, it must not be
	// altered if the track comes up again in the same search (as happens with Spotify). This is because the first time
	// might unhide a hidden track and so the second time sets isSearchResult to 1 because Hidden is now 0.
	// So we have a double CASE WHEN on that to make sure we check isSearchResult before Hidden
	//

	private function prepare_search_findtrack_old() {
		if ($this->find_track = $this->sql_prepare_query_later(
			"INSERT INTO Tracktable
				(Title, Albumindex, TrackNo, Duration, Artistindex, Disc, Uri, LastModified, isSearchResult, isAudiobook, Genreindex, TYear)
			VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, 2, ?, ?, ?)
			ON DUPLICATE KEY UPDATE
				Duration = VALUES(Duration),
				Uri = VALUES(Uri),
				isSearchResult = CASE WHEN isSearchResult > 0 THEN isSearchResult ELSE CASE WHEN Hidden = 0 THEN 1 ELSE 3 END END,
				Hidden = 0,
				justAdded = 1,
				isAudiobook = CASE WHEN isAudiobook = 2 THEN 2 ELSE VALUES(isAudiobook) END,
				Genreindex = VALUES(Genreindex),
				TYear = VALUES(TYear)"
		)) {
			logger::log('MYSQL', 'Prepared old-style update query successfully');
		} else {
			$this->show_sql_error();
			exit(1);
		}
	}

	private function prepare_search_findtrack_new() {
		if ($this->find_track = $this->sql_prepare_query_later(
			"INSERT INTO Tracktable
				(Title, Albumindex, TrackNo, Duration, Artistindex, Disc, Uri, LastModified, isSearchResult, isAudiobook, Genreindex, TYear)
			VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, 2, ?, ?, ?) AS new
			ON DUPLICATE KEY UPDATE
				Duration = new.Duration,
				Uri = new.Uri,
				isSearchResult = CASE WHEN Tracktable.isSearchResult > 0 THEN Tracktable.isSearchResult ELSE CASE WHEN Tracktable.Hidden = 0 THEN 1 ELSE 3 END END,
				Hidden = 0,
				justAdded = 1,
				isAudiobook = CASE WHEN Tracktable.isAudiobook = 2 THEN 2 ELSE new.isAudiobook END,
				Genreindex = new.Genreindex,
				TYear = new.TYear"
		)) {
			logger::log('MYSQL', 'Prepared old-style update query successfully');
		} else {
			$this->show_sql_error();
			exit(1);
		}
	}

	protected function tracks_played_since($option, $value) {
		$value = round($value);
		switch ($option) {
			case RADIO_RULE_OPTIONS_INTEGER_LESSTHAN:
				return "(LastPlayed IS NOT NULL AND TIMESTAMPDIFF(DAY, LastPlayed, CURRENT_TIMESTAMP) < ".$value.")";
				break;

			case RADIO_RULE_OPTIONS_INTEGER_EQUALS:
				return "(LastPlayed IS NOT NULL AND TIMESTAMPDIFF(DAY, LastPlayed, CURRENT_TIMESTAMP) = ".$value.")";
				break;

			case RADIO_RULE_OPTIONS_INTEGER_GREATERTHAN:
				return "(LastPlayed IS NULL OR TIMESTAMPDIFF(DAY, LastPlayed, CURRENT_TIMESTAMP) > ".$value.")";
				break;

	}

}


}

?>