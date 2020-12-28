<?php
// Clean the backend cache. We do this with an AJAX request because
// a) It doesn't slow down the loading of the page, and
// b) If we do it at page load time Chrome's page preload feature can result in two of them running simultaneously,
//    which produces 'cannot stat' errors.

chdir('..');
include("includes/vars.php");
include("includes/functions.php");
prefs::$database = new cache_cleaner();
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

$now = time();
logger::mark("CACHE CLEANER", "Tidying Database");
logger::info("CACHE CLEANER", "Checking database for hidden album art");
prefs::$database->remove_hidden_images();
logger::info("CACHE CLEANER", "== Check For Hidden Album Art took ".format_time(time() - $now));

if (prefs::$prefs['cleanalbumimages']) {
	$now = time();
	// TODO: This is too slow
	logger::info("CACHE CLEANER", "Checking albumart folder for unneeded images");
	$files = glob('albumart/small/*.*');
	foreach ($files as $image) {
		// Remove images for hidden tracks and search results. The missing check below will reset the db entries for those albums
		// Keep everything for 24 hours regardless, we might be using it in a playlist or something
		if (filemtime($image) < time()-86400) {
			if (prefs::$database->check_albums_using_image($image) < 1) {
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
		if (prefs::$database->check_stations_using_image($image) < 1) {
			logger::log("CACHE CLEANER", "  Removing orphaned radio station image",$image);
			rrmdir($image);
		}
	}
	logger::trace("CACHE CLEANER", "== Check For Orphaned Radio Station Images took ".format_time(time() - $now));

	logger::mark("CACHE CLEANER", "Checking for orphaned podcast data");
	$now = time();
	$files = glob('prefs/podcasts/*');
	$pods = prefs::$database->get_all_podcast_indices();
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
prefs::$database->check_for_missing_albumart();
logger::info("CACHE CLEANER", "== Check For Missing Album Art took ".format_time(time() - $now));

logger::info("CACHE CLEANER", "Checking for orphaned Wishlist Sources");
$now = time();
prefs::$database->tidy_wishlist();
logger::info("CACHE CLEANER", "== Check For Orphaned Wishlist Sources took ".format_time(time() - $now));


logger::info("CACHE CLEANER", "Checking for orphaned youtube downloads");
$now = time();
$yts = glob('prefs/youtubedl/*');
foreach ($yts as $dir) {
	$numfiles = prefs::$database->check_ttindex_exists(basename($dir));
	if ($numfiles == 0) {
		logger::log('CACHE CLEANER', $dir,'does not have an associated track');
		exec('rm -fR '.$dir, $output, $retval);
	} else {
		$files = glob($dir.'/*.*');
		if (count($files) == 0) {
			logger::log('CACHE CLEANER', $dir,'is empty');
			exec('rm -fR '.$dir, $output, $retval);
		}
	}
}
logger::info("CACHE CLEANER", "== Check For Orphaned youtube downloads took ".format_time(time() - $now));

// Compact the database
logger::mark("CACHE CLEANER", "Optimising Database");
$now = time();
prefs::$database->optimise_database();
logger::info("CACHE CLEANER", "== Database Optimisation took ".format_time(time() - $now));

logger::mark("CACHE CLEANER", "Database Tidying Is Complete");
logger::mark("CACHE CLEANER", "-----------------------------------------------------------------------");

header('HTTP/1.1 204 No Content');

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

