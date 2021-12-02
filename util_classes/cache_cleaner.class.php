<?php
class cache_cleaner extends database {
	public function remove_hidden_images() {
		// Note the final line checking that image isn't in use by another album
		// it's an edge case where we have the album local but we also somehow have a spotify or whatever
		// version with hidden tracks
		$this->open_transaction();
		$result = $this->generic_sql_query("SELECT DISTINCT Albumindex, Albumname, Image, Domain FROM
			Tracktable JOIN Albumtable USING (Albumindex) JOIN Playcounttable USING (TTindex)
			WHERE Hidden = 1
			AND ".$this->sql_two_weeks()."
			AND
				Albumindex NOT IN (SELECT Albumindex FROM Albumtable JOIN Tracktable USING (Albumindex) WHERE Hidden = 0)
			AND
				Image NOT IN (SELECT Image FROM Albumtable JOIN Tracktable USING (Albumindex) WHERE Hidden = 0)", false, PDO::FETCH_OBJ);

		foreach ($result as $obj) {
			if (preg_match('#^albumart/small/#', $obj->Image)) {
				logger::log("CACHE CLEANER", "Removing image for hidden album",$obj->Albumname,$obj->Image);
				$this->generic_sql_query("UPDATE Albumtable SET Image = NULL, Searched = 0 WHERE Albumindex = ".$obj->Albumindex, true);
				$this->check_transaction();
			}
		}
		$this->close_transaction();
	}

	public function check_albums_using_image($image) {
		return $this->sql_prepare_query(false, null, 'acount', 0,
			"SELECT COUNT(Albumindex) AS acount FROM Albumtable
			JOIN Tracktable USING (Albumindex)
			WHERE Image = ?
			AND Hidden = 0
			AND isSearchResult < 2
			AND URI IS NOT NULL",
		$image);
	}

	public function check_stations_using_image($image) {
		return $this->generic_sql_query(
			"SELECT COUNT(Stationindex) AS acount FROM RadioStationtable WHERE Image LIKE '".$image."%'",
			false, null, 'acount', 0);
	}

	public function get_all_podcast_indices() {
		return $this->sql_get_column("SELECT PODindex FROM Podcasttable", 0);
	}

	public function check_for_missing_albumart() {
		$this->open_transaction();
		$result = $this->generic_sql_query("SELECT Albumindex, Albumname, Image, Domain FROM Albumtable WHERE Image NOT LIKE 'getRemoteImage%'", false, PDO::FETCH_OBJ);
		foreach ($result as $obj) {
			if ($obj->Image != '' && !file_exists($obj->Image)) {
				logger::log("CACHE CLEANER", $obj->Albumname,"has missing image",$obj->Image);
				if (file_exists("newimages/".$obj->Domain."-logo.svg")) {
					$image = "newimages/".$obj->Domain."-logo.svg";
					$searched = 1;
				} else {
					$image = '';
					$searched = 0;
				}
				$this->sql_prepare_query(true, null, null, null, "UPDATE Albumtable SET Searched = ?, Image = ? WHERE Albumindex = ?", $searched, $image, $obj->Albumindex);
				$this->check_transaction();
			}
		}
		$this->close_transaction();
	}

	public function tidy_wishlist() {
		$this->generic_sql_query("DELETE FROM WishlistSourcetable WHERE Sourceindex NOT IN (SELECT DISTINCT Sourceindex FROM Tracktable WHERE Sourceindex IS NOT NULL)");
	}

	public function check_ttindex_exists($ttindex) {
		$bacon = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, array(),
			'SELECT * FROM Tracktable WHERE TTindex = ? AND Hidden = ?',
			$ttindex,
			0
		);
		return count($bacon);
	}

}
?>