<?php
class cache_cleaner extends database {

	public function check_clean_time() {
		$last_clean = $this->simple_query('Value', 'Statstable', 'Item', 'LastCache', time() + 90000);
		if ($last_clean + 86400 <= time()) {
			logger::mark('CACHE CLEANER', 'Time To Clean The Cache');
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
		$this->clean_cache_dir('prefs/jsoncache/allmusic/', 2592000);
		// One Month
		$this->clean_cache_dir('prefs/jsoncache/discogs/', 2592000);
		// One Month
		$this->clean_cache_dir('prefs/jsoncache/musicbrainz/', 2592000);
		// One Month
		$this->clean_cache_dir('prefs/jsoncache/wikipedia/', 2592000);
		// One Month
		$this->clean_cache_dir('prefs/jsoncache/lastfm/', 2592000);
		// One Month
		$this->clean_cache_dir('prefs/jsoncache/spotify/', 2592000);
		// Six Months - after all, lyrics are small and don't change
		$this->clean_cache_dir('prefs/jsoncache/lyrics/', 15552000);
		// One Month
		$this->clean_cache_dir('prefs/jsoncache/soundcloud/', 2592000);
		// One Week
		$this->clean_cache_dir('prefs/jsoncache/commradio/', 604800);
		// One Week
		$this->clean_cache_dir('prefs/jsoncache/somafm/', 604800);
		// One Month
		$this->clean_cache_dir('prefs/jsoncache/bing/', 2592000);
		// One Month
		$this->clean_cache_dir('prefs/jsoncache/wikidata/', 2592000);
		// One week (or it can get REALLY big)
		$this->clean_cache_dir('prefs/imagecache/', 604800);
		// Clean the albumart temporary upload directory
		$this->clean_cache_dir('albumart/', 1);
		// Clean the temp directory
		$this->clean_cache_dir('prefs/temp/', 1);
		logger::info("CACHE CLEANER", "== Cache Was Cleaned In ".format_time(time() - $now),'seconds');

		$now = time();

		prefs::load();
		if (prefs::get_pref('cleanalbumimages')) {
			$now = time();
			// TODO: This is too slow
			logger::info("CACHE CLEANER", "Checking albumart folder for unneeded images");
			$used_images = $this->get_used_album_images();
			$files = glob('albumart/small/*.*');
			$unused = array_diff($files, $used_images);
			foreach ($unused as $image) {
				logger::log("CACHE CLEANER", "  Removing Unused Album image ",$image);
				$albumimage = new baseAlbumImage(array('baseimage' => $image));
				array_map('unlink', $albumimage->get_images());
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
		$this->check_youtube_dir('prefs/youtubedl');
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

	private function check_youtube_dir($path) {
		$numfiles = 0;
		foreach (new DirectoryIterator($path) as $f) {
			if ($f->isFile()) {
				$numfiles++;
				$fpath = $f->getPathname();
				if ($this->check_youtube_uri_exists(substr($fpath, strpos($fpath, 'youtubedl/'))) == 0) {
					logger::log('CACHE CLEANER', $fpath,'does not have an associated track');
					unlink($fpath);
				}
			} else if (!$f->isDot() && $f->isDir()) {
				$numfiles++;
				$this->check_youtube_dir($f->getRealPath());
			}
		}
		if ($numfiles == 0) {
			logger::log('CACHE CLEANER', $path,'is empty');
			rmdir($path);
		}
	}

	private function get_used_album_images() {
		$images = $this->sql_get_column(
			"SELECT Image FROM Albumtable JOIN Tracktable USING (Albumindex)
			WHERE Hidden = 0 AND Image LIKE 'albumart/small/%'", 0
		);
		$ll = $this->generic_sql_query("SELECT * FROM AlbumsToListenTotable");
		foreach ($ll as $album) {
			$ad = json_decode($album['JsonData'], true);
			if (array_key_exists('albumimage', $ad)) {
				if (strpos($ad['albumimage']['small'], 'albumart/') === 0)
					$images[] = $ad['albumimage']['small'];
			}
		}
		return array_unique($images);
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
		$result = $this->generic_sql_query("SELECT Albumindex, Albumname, Image, Domain FROM Albumtable WHERE Image LIKE 'albumart/%'", false, PDO::FETCH_OBJ);
		foreach ($result as $obj) {
			if (!file_exists($obj->Image)) {
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
					@unlink($file);
				}
			}
		}
	}

}
?>