<?php
// Clean the backend cache. We do this with an AJAX request because
// a) It doesn't slow down the loading of the page, and
// b) If we do it at page load time Chrome's page preload feature can result in two of them running simultaneously,
//    which produces 'cannot stat' errors.

chdir('..');
include("includes/vars.php");
include("includes/functions.php");
require_once("utils/imagefunctions.php");
include("backends/sql/backend.php");

logger::mark("CACHE CLEANER", "-----------------------------------------------------------------------");
logger::mark("CACHE CLEANER", "Checking Cache");

// DO NOT REDUCE the values for musicbrainz
// - we have to follow their API rules and as we don't check
// expiry headers at all we need to keep everything for a month
// otherwise they will ban us. Don't spoil it for everyone.

// One Month
clean_cache_dir('prefs/jsoncache/musicbrainz/', 2592000);
// One Month
clean_cache_dir('prefs/jsoncache/allmusic/', 2592000);
// One Week
clean_cache_dir('prefs/jsoncache/discogs/', 604800);
// One Month
clean_cache_dir('prefs/jsoncache/wikipedia/', 2592000);
// One Month
clean_cache_dir('prefs/jsoncache/lastfm/', 2592000);
// One Month
clean_cache_dir('prefs/jsoncache/soundcloud/', 2592000);
// One Month
clean_cache_dir('prefs/jsoncache/spotify/', 2592000);
// One Month
clean_cache_dir('prefs/jsoncache/google/', 2592000);
// One Week
clean_cache_dir('prefs/jsoncache/commradio/', 604800);
// One Week
clean_cache_dir('prefs/jsoncache/somafm/', 604800);
// One Week
clean_cache_dir('prefs/jsoncache/icecast/', 604800);
// Six Months - after all, lyrics are small and don't change
clean_cache_dir('prefs/jsoncache/lyrics/', 15552000);
// One week (or it can get REALLY big)
clean_cache_dir('prefs/imagecache/', 604800);
// Clean the albumart temporary upload directory
clean_cache_dir('albumart/', 1);
// Clean the temp directory
clean_cache_dir('prefs/temp/', 1);
logger::mark("CACHE CLEANER", "Cache has been cleaned");

if ($mysqlc) {

	$now = time();
	logger::mark("CACHE CLEANER", "Tidying Database");
	logger::info("CACHE CLEANER", "Checking database for hidden album art");
	// Note the final line checking that image isn't in use by another album
	// it's an edge case where we have the album local but we also somehow have a spotify or whatever
	// version with hidden tracks
	$result = generic_sql_query("SELECT DISTINCT Albumindex, Albumname, Image, Domain FROM
		Tracktable JOIN Albumtable USING (Albumindex) JOIN Playcounttable USING (TTindex)
		WHERE Hidden = 1
		AND ".sql_two_weeks()."
		AND
			Albumindex NOT IN (SELECT Albumindex FROM Albumtable JOIN Tracktable USING (Albumindex) WHERE Hidden = 0)
		AND
			Image NOT IN (SELECT Image FROM Albumtable JOIN Tracktable USING (Albumindex) WHERE Hidden = 0)", false, PDO::FETCH_OBJ);
	foreach ($result as $obj) {
		if (preg_match('#^albumart/small/#', $obj->Image)) {
			logger::log("CACHE CLEANER", "Removing image for hidden album",$obj->Albumname,$obj->Image);
			generic_sql_query("UPDATE Albumtable SET Image = NULL, Searched = 0 WHERE Albumindex = ".$obj->Albumindex, true);
		}
	}
	logger::info("CACHE CLEANER", "== Check For Hidden Album Art took ".format_time(time() - $now));


	if ($prefs['cleanalbumimages']) {
		$now = time();
		// TODO: This is too slow
		logger::info("CACHE CLEANER", "Checking albumart folder for unneeded images");
		$files = glob('albumart/small/*.*');
		foreach ($files as $image) {
			// Remove images for hidden tracks and search results. The missing check below will reset the db entries for those albums
			// Keep everything for 24 hours regardless, we might be using it in a playlist or something
			if (filemtime($image) < time()-86400) {
				$count = sql_prepare_query(false, null, 'acount', 0, "SELECT COUNT(Albumindex) AS acount FROM Albumtable JOIN Tracktable USING (Albumindex) WHERE Image = ? AND Hidden = 0 AND isSearchResult < 2 AND URI IS NOT NULL", $image);
				if ($count < 1) {
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
			$count = generic_sql_query("SELECT COUNT(Stationindex) AS acount FROM RadioStationtable WHERE Image LIKE '".$image."%'", false, null, 'acount', 0);
			if ($count < 1) {
				logger::log("CACHE CLEANER", "  Removing orphaned radio station image",$image);
				rrmdir($image);
			}
		}
		logger::trace("CACHE CLEANER", "== Check For Orphaned Radio Station Images took ".format_time(time() - $now));

		logger::mark("CACHE CLEANER", "Checking for orphaned podcast data");
		$now = time();
		$files = glob('prefs/podcasts/*');
		$pods = sql_get_column("SELECT PODindex FROM Podcasttable", 0);
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
	$result = generic_sql_query("SELECT Albumindex, Albumname, Image, Domain FROM Albumtable WHERE Image NOT LIKE 'getRemoteImage%'", false, PDO::FETCH_OBJ);
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
			sql_prepare_query(true, null, null, null, "UPDATE Albumtable SET Searched = ?, Image = ? WHERE Albumindex = ?", $searched, $image, $obj->Albumindex);
		}
	}
	logger::info("CACHE CLEANER", "== Check For Missing Album Art took ".format_time(time() - $now));

	logger::info("CACHE CLEANER", "Checking for orphaned Wishlist Sources");
	$now = time();
	generic_sql_query("DELETE FROM WishlistSourcetable WHERE Sourceindex NOT IN (SELECT DISTINCT Sourceindex FROM Tracktable WHERE Sourceindex IS NOT NULL)");
	logger::info("CACHE CLEANER", "== Check For Orphaned Wishlist Sources took ".format_time(time() - $now));

	// Compact the database
	if ($prefs['collection_type'] == 'sqlite') {
		logger::mark("CACHE CLEANER", "Vacuuming Database");
		$now = time();
		generic_sql_query("VACUUM", true);
		generic_sql_query("PRAGMA optimize", true);
		logger::info("CACHE CLEANER", "== Database Optimisation took ".format_time(time() - $now));
	}

	logger::mark("CACHE CLEANER", "Database Tidying Is Complete");

}

logger::mark("CACHE CLEANER", "-----------------------------------------------------------------------");

function clean_cache_dir($dir, $time) {
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

?>

<html></html>
