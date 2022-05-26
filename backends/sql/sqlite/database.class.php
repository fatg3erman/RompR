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
			logger::log('MYSQL', 'Prepared new-style update query successfully');
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

}

?>