<?php

class database extends data_base {

	const SQL_RANDOM_SORT = 'RAND()';
	const SQL_TAG_CONCAT = "GROUP_CONCAT(t.Name SEPARATOR ', ') ";
	const SQL_URI_CONCAT = "GROUP_CONCAT(Uri SEPARATOR ',') ";
	const STUPID_CONCAT_THING = "SELECT PODindex, PODTrackindex FROM PodcastTracktable WHERE Link = ? OR ? LIKE CONCAT('%', Localfilename)";

	public function __construct() {
		try {
			if (is_numeric(prefs::get_pref('mysql_port'))) {
				logger::core("MYSQL", "Connecting using hostname and port");
				$dsn = "mysql:host=".prefs::get_pref('mysql_host').";port=".prefs::get_pref('mysql_port').";dbname=".prefs::get_pref('mysql_database').";charset=utf8mb4";
			} else {
				logger::core("MYSQL", "Connecting using unix socket");
				$dsn = "mysql:unix_socket=".prefs::get_pref('mysql_port').";dbname=".prefs::get_pref('mysql_database').";charset=utf8mb4";
			}
			$this->mysqlc = new PDO($dsn, prefs::get_pref('mysql_user'), prefs::get_pref('mysql_password'));
			logger::core("MYSQL", "Connected to MySQL");
			$this->generic_sql_query('SET SESSION sql_mode="STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION"', true);
		} catch (Exception $e) {
			logger::warn("MYSQL", "Database connect failure - ".$e);
			sql_init_fail($e->getMessage());
		}
	}

	protected function hide_played_tracks() {
		// If isSearchResult is 2, then it had to be added, therefore if itis subsequently rated it needs to become a manually added track,
		// so we need to set LastModified to NULL now.
		$this->generic_sql_query("CREATE TEMPORARY TABLE Fluff(TTindex INT UNSIGNED NOT NULL UNIQUE, PRIMARY KEY(TTindex)) ENGINE=MEMORY AS SELECT TTindex FROM Tracktable JOIN Playcounttable USING (TTindex) WHERE isSearchResult = 2", true);
		$this->generic_sql_query("UPDATE Tracktable SET Hidden = 1, isSearchResult = 0, LastModified = NULL WHERE TTindex IN (SELECT TTindex FROM Fluff)", true);
	}

	protected function sql_recent_tracks() {
		return "SELECT Uri FROM Tracktable JOIN Albumtable USING (Albumindex) WHERE (DATE_SUB(CURDATE(),INTERVAL 60 DAY) <= DateAdded)";
	}

	public function sql_recently_played() {
		return $this->generic_sql_query(
			"SELECT
				t.Uri,
				t.Title,
				a.Artistname,
				al.Albumname,
				al.Image,
				al.ImgKey,
				DATE_FORMAT(p.LastPlayed, '%H:%i') AS playtime,
				DATE_FORMAT(p.LastPlayed, '%W, %D %M %Y') AS playdate
			FROM Tracktable AS t
			JOIN Playcounttable AS p USING (TTindex)
			JOIN Albumtable AS al USING (Albumindex)
			JOIN Artisttable AS a ON (a.Artistindex = al.AlbumArtistindex)
			WHERE DATE_SUB(CURDATE(),INTERVAL 14 DAY) <= p.LastPlayed
			AND p.LastPlayed IS NOT NULL
			ORDER BY p.LastPlayed DESC",
			false
		);
	}

	public function get_album_uri($trackuri) {
		return $this->sql_prepare_query(false, PDO::FETCH_ASSOC, 'AlbumUri', null,
			"SELECT AlbumUri FROM Albumtable JOIN Tracktable USING (Albumindex) WHERE Uri = ?",
			$trackuri
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

	public function check_podcast_trackimage($uri) {
		$retval = null;
		$thing = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, array(),
			"SELECT Image FROM PodcastTracktable WHERE Link = ? OR ? LIKE CONCAT('%', Localfilename)",
			$uri,
			$uri
		);
		if (count($thing) > 0) {
			$retval = $thing[0]['Image'];
		}
		return $retval;
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
		if (prefs::get_pref('old_style_sql')) {
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
		if (prefs::get_pref('old_style_sql')) {
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

			case RADIO_RULE_OPTIONS_INTEGER_ISNOT:
				return "(LastPlayed IS NULL OR TIMESTAMPDIFF(DAY, LastPlayed, CURRENT_TIMESTAMP) <> ".$value.")";
				break;

		}
	}

	protected function check_youtube_uri_exists($uri) {
		logger::trace('CLEANER', 'Checking for', $uri);
		$bacon = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, array(),
			"SELECT TTindex FROM Tracktable WHERE Uri LIKE CONCAT('%', ?) AND Hidden = ?",
			$uri,
			0
		);
		return count($bacon);
	}

	public function drop_triggers() {
		$triggers = $this->generic_sql_query('SHOW TRIGGERS');
		foreach ($triggers as $trigger) {
			logger::debug('MYSQL', 'Dropping Trigger', $trigger['Trigger']);
			$this->generic_sql_query('DROP TRIGGER '.$trigger['Trigger']);
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

	protected function create_update_triggers() {
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

	protected function trigger_not_exists($table, $trig) {
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

	public function create_toptracks_table() {
		// UNIQUE INDEX doesn't take NULL into account, so we can't put NULL into those columns
		// trackartist looks odd but it's consistent with what the UI does when
		// it calls fave_finder
		$name = everywhere_radio::get_seed_table_name();
		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS ".$name."(".
			"topindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
			"Type INT NOT NULL, ".
			"trackartist VARCHAR(100) NOT NULL, ".
			"Title VARCHAR(255) NOT NULL, ".
			"UNIQUE INDEX(trackartist, Title), ".
			"PRIMARY KEY (topindex)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL",$name,"OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking ".$name." : ".$err);
		}
		$this->generic_sql_query("TRUNCATE TABLE ".$name);
	}

	public function add_toptrack($type, $artist, $title) {
		$name = everywhere_radio::get_seed_table_name();
		$this->sql_prepare_query(true, null, null, null,
			"INSERT IGNORE INTO ".$name." (Type, trackartist, Title) VALUES (?, ? ,?)",
			$type,
			$artist,
			$title
		);
	}

	public function create_radio_uri_table() {
		$name = everywhere_radio::get_uri_table_name();
		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS ".$name."(".
			"uriindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
			"used TINYINT DEFAULT 0, ".
			"trackartist VARCHAR(100) NOT NULL, ".
			"Title VARCHAR(255) NOT NULL, ".
			"Uri VARCHAR(2000), ".
			"UNIQUE INDEX(trackartist, Title), ".
			"PRIMARY KEY (uriindex)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL",$name,"OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking ".$name." : ".$err);
		}
		$this->generic_sql_query("TRUNCATE TABLE ".$name);
	}

	public function create_radio_ban_table() {
		$name = everywhere_radio::get_ban_table_name();
		if ($this->generic_sql_query("CREATE TABLE IF NOT EXISTS ".$name."(".
			"banindex INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, ".
			"trackartist VARCHAR(100) NOT NULL, ".
			"Title VARCHAR(255) NOT NULL, ".
			"UNIQUE INDEX(trackartist, Title), ".
			"PRIMARY KEY (banindex)) ENGINE=InnoDB", true))
		{
			logger::log("MYSQL",$name,"OK");
		} else {
			$err = $this->mysqlc->errorInfo()[2];
			return array(false, "Error While Checking ".$name." : ".$err);
		}
	}

	// artist and title are only used here to prevent duplicates so we use
	// strip_track_name because online sources are often very inconsistent:
	// Blood Sweat & Tears
	// Blood, Sweat & Tears
	// Blood, Sweat And Tears
	// all come back in response to a search for Blood Sweat & Tears
	public function add_smart_uri($uri, $artist, $title) {
		$name = everywhere_radio::get_uri_table_name();
		$this->sql_prepare_query(true, null, null, null,
			"INSERT IGNORE INTO ".$name." (trackartist, Title, Uri) VALUES (?, ? ,?)",
			strip_track_name($artist),
			strip_track_name($title),
			$uri
		);
	}

	public function add_ban_track($artist, $title) {
		$name = everywhere_radio::get_ban_table_name();
		$this->sql_prepare_query(true, null, null, null,
			"INSERT IGNORE INTO ".$name." (trackartist, Title) VALUES (?, ?)",
			$artist,
			$title
		);
	}

}
?>