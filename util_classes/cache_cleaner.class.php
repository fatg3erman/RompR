<?php
class cache_cleaner extends database {

	public function check_clean_time() {
		$last_clean = $this->simple_query('Value', 'Statstable', 'Item', 'LastCache', time() + 90000);
		if ($last_clean + 86400 <= time()) {
			logger::log('CACHE CLEANER', 'Time To Clean The Cache');
			$this->sql_prepare_query(true, null, null, null,
				"UPDATE Statstable SET Value = ? WHERE Item = 'LastCache'",
				time()
			);
			return true;
		}
		return false;
	}

	public function clean_cache() {
		logger::mark("CACHE CLEANER", "-----------------------------------------------------------------------");
		logger::mark("CACHE CLEANER", "Checking Cache");

		if ($this->collectionUpdateRunning()) {
			logger::warn('CACHE CLEANER', 'Cache was not cleaned this time because a Collection Update was running');
			return;
		}

		// DO NOT REDUCE the values for musicbrainz
		// - we have to follow their API rules and as we don't check
		// expiry headers at all we need to keep everything for a month
		// otherwise they will ban us. Don't spoil it for everyone.
		$now = time();
		// One Month
		$this->clean_cache_dir('prefs/jsoncache/musicbrainz/', 2592000);
		// One Month
		$this->clean_cache_dir('prefs/jsoncache/allmusic/', 2592000);
		// One Month
		$this->clean_cache_dir('prefs/jsoncache/discogs/', 2592000);
		// One Month
		$this->clean_cache_dir('prefs/jsoncache/wikipedia/', 2592000);
		// One Month
		$this->clean_cache_dir('prefs/jsoncache/lastfm/', 2592000);
		// One Month
		$this->clean_cache_dir('prefs/jsoncache/soundcloud/', 2592000);
		// One Month
		$this->clean_cache_dir('prefs/jsoncache/spotify/', 2592000);
		// One Week
		$this->clean_cache_dir('prefs/jsoncache/commradio/', 604800);
		// One Week
		$this->clean_cache_dir('prefs/jsoncache/somafm/', 604800);
		// One Week
		$this->clean_cache_dir('prefs/jsoncache/icecast/', 604800);
		// Six Months - after all, lyrics are small and don't change
		$this->clean_cache_dir('prefs/jsoncache/lyrics/', 15552000);
		// One week (or it can get REALLY big)
		$this->clean_cache_dir('prefs/imagecache/', 604800);
		// Clean the albumart temporary upload directory
		$this->clean_cache_dir('albumart/', 1);
		// Clean the temp directory
		$this->clean_cache_dir('prefs/temp/', 1);
		logger::info("CACHE CLEANER", "== Cache Was Cleaned In ".format_time(time() - $now),'seconds');

		$now = time();
		logger::info("CACHE CLEANER", "Checking database for hidden album art");
		$this->remove_hidden_images();
		logger::info("CACHE CLEANER", "== Check For Hidden Album Art took ".format_time(time() - $now));

		prefs::load();
		if (prefs::get_pref('cleanalbumimages')) {
			$now = time();
			// TODO: This is too slow
			logger::info("CACHE CLEANER", "Checking albumart folder for unneeded images");
			$files = glob('albumart/small/*.*');
			foreach ($files as $image) {
				// Remove images for hidden tracks and search results. The missing check below will reset the db entries for those albums
				// Keep everything for 24 hours regardless, we might be using it in a playlist or something
				if (filemtime($image) < time()-86400) {
					if ($this->check_albums_using_image($image) < 1) {
						logger::log("CACHE CLEANER", "  Removing Unused Album image ",$image);
						$albumimage = new baseAlbumImage(array('baseimage' => $image));
						array_map('unlink', $albumimage->get_images());
					}
				}
			}
			logger::info("CACHE CLEANER", "== Check For Unneeded Images took ".format_time(time() - $now));

			logger::info("CACHE CLEANER", "Checking for orphaned radio station images");
			$now = time();
			$files = glob('prefs/userstreams/*');
			foreach ($files as $image) {
				if ($this->check_stations_using_image($image) < 1) {
					logger::log("CACHE CLEANER", "  Removing orphaned radio station image",$image);
					rrmdir($image);
				}
			}
			logger::info("CACHE CLEANER", "== Check For Orphaned Radio Station Images took ".format_time(time() - $now));

			logger::info("CACHE CLEANER", "Checking for orphaned podcast data");
			$now = time();
			$files = glob('prefs/podcasts/*');
			$pods = $this->get_all_podcast_indices();
			foreach ($files as $file) {
				if (!in_array(basename($file), $pods)) {
					logger::log("CACHE CLEANER", "  Removing orphaned podcast directory",$file);
					rrmdir($file);
				}
			}
			logger::info("CACHE CLEANER", "== Check For Orphaned Podcast Data took ".format_time(time() - $now));
		}

		logger::info("CACHE CLEANER", "Checking database for missing album art");
		$now = time();
		$this->check_for_missing_albumart();
		logger::info("CACHE CLEANER", "== Check For Missing Album Art took ".format_time(time() - $now));

		logger::info("CACHE CLEANER", "Checking for orphaned Wishlist Sources");
		$now = time();
		$this->tidy_wishlist();
		logger::info("CACHE CLEANER", "== Check For Orphaned Wishlist Sources took ".format_time(time() - $now));

		logger::info("CACHE CLEANER", "Checking for orphaned youtube downloads");
		$now = time();
		$yts = glob('prefs/youtubedl/*');
		foreach ($yts as $dir) {
			$flacs = glob($dir.'/*.flac');
			foreach ($flacs as $flac) {
				$numfiles = $this->check_youtube_uri_exists($flac);
				if ($numfiles > 0)
					continue 2;

			}
			logger::log('CACHE CLEANER', $flac,'does not have an associated track');
			rrmdir($dir);
		}
		logger::info("CACHE CLEANER", "== Check For Orphaned youtube downloads took ".format_time(time() - $now));

		logger::info("CACHE CLEANER", "Tidying Ratings and Playcounts");
		$now = time();
		$this->tidy_ratings_and_playcounts();
		logger::info("CACHE CLEANER", "== Tidying Ratings and Playcounts took ".format_time(time() - $now));


		// Compact the database
		logger::mark("CACHE CLEANER", "Optimising Database");
		$now = time();
		$this->optimise_database();
		logger::info("CACHE CLEANER", "== Database Optimisation took ".format_time(time() - $now));

		$this->clearUpdateLock();

		logger::mark("CACHE CLEANER", "Database Tidying Is Complete");
		logger::mark("CACHE CLEANER", "-----------------------------------------------------------------------");

	}

	private function remove_hidden_images() {
		// Note the final line checking that image isn't in use by another album
		// it's an edge case where we have the album local but we also somehow have a spotify or whatever
		// version with hidden tracks
		$this->open_transaction();
		$result = $this->generic_sql_query("SELECT DISTINCT Albumindex, Albumname, Image, Domain FROM
			Tracktable JOIN Albumtable USING (Albumindex) JOIN Playcounttable USING (TTindex)
			WHERE Hidden = 1 AND LinkChecked < 4
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

	private function check_albums_using_image($image) {
		return $this->sql_prepare_query(false, null, 'acount', 0,
			"SELECT COUNT(Albumindex) AS acount FROM Albumtable
			JOIN Tracktable USING (Albumindex)
			WHERE
			Image = ?
			AND ((Hidden = 0
			AND isSearchResult < 2)
			OR LinkChecked = 4)
			AND URI IS NOT NULL",
		$image);
	}

	private function check_stations_using_image($image) {
		return $this->generic_sql_query(
			"SELECT COUNT(Stationindex) AS acount FROM RadioStationtable WHERE Image LIKE '".$image."%'",
			false, null, 'acount', 0);
	}

	private function get_all_podcast_indices() {
		return $this->sql_get_column("SELECT PODindex FROM Podcasttable", 0);
	}

	private function check_for_missing_albumart() {
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

	private function tidy_wishlist() {
		$this->generic_sql_query("DELETE FROM WishlistSourcetable WHERE Sourceindex NOT IN (SELECT DISTINCT Sourceindex FROM Tracktable WHERE Sourceindex IS NOT NULL)");
	}

	private function check_youtube_uri_exists($uri) {
		$bacon = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, array(),
			"SELECT TTindex FROM Tracktable WHERE Uri LIKE CONCAT('%', ?) AND Hidden = ?",
			$uri,
			0
		);
		return count($bacon);
	}

	private function tidy_ratings_and_playcounts() {
		// This just keeps the tables small. Sometimes we add a Rating of 0 to force metadatabase
		// to create a new track
		$this->generic_sql_query("DELETE FROM Ratingtable WHERE Rating = 0", true);
		$this->generic_sql_query("DELETE FROM Playcounttable WHERE Playcount = 0", true);
	}

	private function clean_cache_dir($dir, $time) {
		logger::log("CACHE CLEANER", "Cache Cleaner is running on ".$dir);
		$cache = glob($dir."*");
		$now = time();
		foreach($cache as $file) {
			if (!is_dir($file)) {
				if($now - filemtime($file) > $time) {
					logger::trace("CACHE CLEANER", "Removing file ".$file);
					@unlink ($file);
				}
			}
		}
	}

}
?>