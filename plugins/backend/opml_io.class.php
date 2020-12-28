<?php
class opml_io extends database {
	public function get_podcasts() {
		return $this->generic_sql_query("SELECT FeedURL, Title FROM Podcasttable WHERE Subscribed = 1", false, PDO::FETCH_OBJ);
	}

	public function podcast_is_subscribed($feedURL) {
		$r = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, null,
			"SELECT Title FROM Podcasttable WHERE Subscribed = 1 AND FeedURL = ?", $feedURL);
		if (count($r) > 0) {
			logger::log("OPML Imoprter", "    Already Subscribed To Podcast ".$feedURL);
			return true;
		}
		return false;
	}

}
?>