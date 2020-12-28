<?php
class lfm_importer extends database {
	public function get_chunk_of_data($offset, $limit) {
		$arse = $this->generic_sql_query("SELECT
			TTindex,
			ta.Artistname as Trackartist,
			aa.Artistname as Albumartist,
			Albumname,
			Title,
			Disc,
			TrackNo,
			IFNULL(Playcount, 0) AS Playcount
			FROM
			Tracktable JOIN Albumtable USING (Albumindex)
			JOIN Artisttable AS aa ON (Albumtable.AlbumArtistindex = aa.Artistindex)
			JOIN Artisttable AS ta ON (Tracktable.Artistindex = ta.Artistindex)
			LEFT JOIN Playcounttable USING (TTindex)
			WHERE isSearchResult != 2 AND ".
			$this->sql_to_unixtime('DateAdded')." > ".prefs::$prefs['lfm_importer_last_import'].
			" ORDER BY aa.Artistname, Albumname, Disc ASC, TrackNo ASC LIMIT ".$offset.", ".$limit);
	}

	public function get_total_tracks() {
		$arse = $this->generic_sql_query("SELECT
			COUNT(TTindex) AS total
			FROM
			Tracktable JOIN Albumtable USING (Albumindex)
			JOIN Artisttable AS aa ON (Albumtable.AlbumArtistindex = aa.Artistindex)
			JOIN Artisttable AS ta ON (Tracktable.Artistindex = ta.Artistindex)
			LEFT JOIN Playcounttable USING (TTindex)
			WHERE isSearchResult != 2 AND ".
			$this->sql_to_unixtime('DateAdded')." > ".prefs::$prefs['lfm_importer_last_import']
		);
	}

}
?>