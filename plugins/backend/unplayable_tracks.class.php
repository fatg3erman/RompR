<?php
class unplayable_tracks extends database {
	public function get_unplayable_tracks() {
		$qstring = "SELECT
			IFNULL(r.Rating, 0) AS rating,
			".database::SQL_TAG_CONCAT." AS tags,
			IFNULL(p.Playcount, 0) AS playcount,
			tr.TTindex,
			Title,
			Albumname,
			ta.Artistname,
			aa.Artistname AS AlbumArtist,
			Image
			FROM
			Tracktable AS tr
			LEFT JOIN Ratingtable AS r ON tr.TTindex = r.TTindex
			LEFT JOIN TagListtable AS tl ON tr.TTindex = tl.TTindex
			LEFT JOIN Tagtable AS t USING (Tagindex)
			LEFT JOIN Playcounttable AS p ON tr.TTindex = p.TTindex
			JOIN Artisttable AS ta USING (Artistindex)
			JOIN Albumtable USING (Albumindex)
			JOIN Artisttable AS aa ON (Albumtable.AlbumArtistindex = aa.Artistindex)
			WHERE LinkChecked = 1 OR LinkChecked = 3
			GROUP BY tr.TTindex
			ORDER BY aa.Artistname, Albumname, TrackNo";

		return $this->generic_sql_query($qstring);

	}
}