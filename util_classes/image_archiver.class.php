<?php
class image_archiver extends database {
	public function get_all_images() {
		return $this->generic_sql_query(
			'SELECT
				Uri,
				Image,
				Albumname,
				Domain
			FROM Tracktable
			JOIN Albumtable USING (Albumindex)
			WHERE Domain = "local" AND Uri IS NOT NULL
			GROUP BY Albumindex', false, PDO::FETCH_OBJ);

	}
}
?>