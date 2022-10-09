<?php

// Automatic Collection Updates can be performed using cURL:
// curl -b "currenthost=Default;player_backend=mpd" http://localhost/rompr/api/collection/?rebuild > /dev/null
// where currenthost is the name of one of the Players defined in the Configuration menu
// and player_backend MUST be mpd or mopidy, depending on what your player is.
// You can also use eg -b "debug_enabled=8;currenthost=MPD;player_backend=mpd"
// to get more debug info in the webserver error log.

chdir('../..');

require_once ("includes/vars.php");
require_once ("includes/functions.php");
$error = 0;

logger::trace("TIMINGS", "======================================================================");
$initmem = memory_get_usage();
logger::trace("COLLECTION", "Memory Used is ".$initmem);
$now2 = time();

switch (true) {

	case array_key_exists('item', $_REQUEST):
		// Populate a dropdown in the collection or search results
		logit('item');
		prefs::$database = new musicCollection();
		prefs::$database->dumpAlbums($_REQUEST['item']);
		prefs::$database->close_database();
		break;

	case array_key_exists('mpdsearch', $_REQUEST):
		// Handle an mpd-style search request
		logit('mpdsearch');
		list($cmd, $dbterms) = check_dbterms($_REQUEST['command']);
		if ($_REQUEST['resultstype'] == "tree") {
			mpd_file_search($cmd, checkDomains($_REQUEST), $dbterms);
		} else {
			mpd_search($cmd, checkDomains($_REQUEST), $dbterms);
		}
		break;

	case array_key_exists('terms', $_REQUEST):
		// SQL database search request
		logit('terms');
		if ($_REQUEST['resultstype'] == "tree") {
			database_tree_search();
		} else {
			database_search();
		}
		break;

	case array_key_exists("rawterms", $_REQUEST):
		logit('rawterms');
		// Handle an mpd-style search request requiring tl_track format results
		// Note that raw_search uses the collection models but not the database
		// hence $trackbytrack must be false
		logger::log("MPD SEARCH", "Doing RAW search");
		raw_search();
		break;

	case array_key_exists('rebuild', $_REQUEST):
		logit('rebuild');
		// This is a request to rebuild the music collection
		update_collection();
		break;

	default:
		logger::warn("ALBUMS", "Couldn't figure out what to do!");
		break;

}

logger::trace("TIMINGS", "== Collection Update And Send took ".format_time(time() - $now2));
$peakmem = memory_get_peak_usage();
$ourmem = $peakmem - $initmem;
logger::trace("TIMINGS", "Peak Memory Used Was ".number_format($peakmem)." bytes  - meaning we used ".number_format($ourmem)." bytes.");
logger::trace("TIMINGS", "======================================================================");

function logit($key) {
	logger::log("COLLECTION", "Request is",$key,"=",$_REQUEST[$key]);
}

function checkDomains($d) {
	if (array_key_exists('domains', $d)) {
		return $d['domains'];
	}
	logger::log("SEARCH", "No search domains in use");
	return false;
}

function check_dbterms($cmd) {
	$dbterms = ['tag' => null, 'rating' => null];
	foreach ($_REQUEST['mpdsearch'] as $key => $term) {
		switch ($key) {
			case 'tag':
			case 'rating':
				$dbterms[$key] = $term;
				break;

			default:
				foreach ($term as $t) {
					$cmd .= " ".$key.' "'.format_for_mpd(html_entity_decode(trim($t))).'"';
				}
				break;

		}
	}
	logger::log('MPDSEARCH', 'Search Command is',$cmd);
	return [$cmd, $dbterms];
}

function mpd_file_search($cmd, $domains, $dbterms) {
	prefs::$database = new collection_base();
	$player = new fileCollector($dbterms);
	$player->doFileSearch($cmd, $domains);
}

function mpd_search($cmd, $domains, $dbterms) {
	// If we're searching for tags or ratings it would seem sensible to only search the database
	// HOWEVER - we could be searching for genre or performer or composer - which will not match in the database
	// For those cases ONLY, controller.js will call into this instead of database_search, and we set $dbterms
	// to make the collection check everything it finds against the database
	$options = [
		'doing_search' => true,
		'trackbytrack' => true
	];
	if (count($dbterms) > 0) {
		$options['dbterms'] = $dbterms;
	}
	prefs::$database = new musicCollection($options);
	prefs::$database->cleanSearchTables();
	prefs::$database->do_update_with_command($cmd, array(), $domains);
	prefs::$database->dumpAlbums($_REQUEST['dump']);
	prefs::$database->dumpArtistSearchResults($_REQUEST['dump']);
}

function database_search() {
	prefs::$database = new db_collection();
	// prefs::$database->open_transaction();
	prefs::$database->cleanSearchTables();
	logger::log('SEARCH', 'Using Database For Search');
	prefs::$database->doDbCollection($_REQUEST['terms'], checkDomains($_REQUEST), false);
	// prefs::$database->close_transaction();
	prefs::$database->dumpAlbums($_REQUEST['dump']);
}

function database_tree_search() {
	$tree = new mpdlistthing(null);
	prefs::$database = new db_collection();
	prefs::$database->doDbCollection($_REQUEST['terms'], checkDomains($_REQUEST), $tree);
	printFileSearch($tree);
}

function raw_search() {

	// RAW search is used by favefinder, the wishlist, and various smart radios
	// It uses the collection datamodels but does not use the database, because that
	// might overwrite search results that the user is currently viewing.

	$domains = checkDomains($_REQUEST);
	prefs::$database = new musicCollection([
		'doing_search' => true,
		'trackbytrack' => false
	]);
	prefs::$database->do_raw_search($domains, $_REQUEST['checkdb'], $_REQUEST['rawterms'], $_REQUEST['command']);
	prefs::$database->tracks_as_array();
}

function update_collection() {
	prefs::$database = new musicCollection();
	// Check that an update is not currently in progress
	// and create the update lock if not

	if (file_exists('prefs/monitor')) {
		unlink('prefs/monitor');
	}
	// Send some dummy data back to the browser, then close the connection
	// so that the browser doesn't time out and retry

    logger::log('COLLECTION', 'Checking Nothing Else Is Running...');
	if (prefs::$database->collectionUpdateRunning(true)) {
		header('HTTP/1.1 500 Internal Server Error');
		print language::gettext('error_nocol');
		exit(0);
	}

	close_browser_connection();

	global $performance;
	$timer = microtime(true);

	// prefs::$database->read_collection_lastmodified();

	// The triggers aren't needed during this operation and slow things down
	prefs::$database->drop_triggers();

	// Browser is now happy. Now we can do our work in peace.
    logger::log('COLLECTION', 'Now were on our own');
	$t = microtime(true);
    prefs::$database->cleanSearchTables();
    $performance['cleansearch'] = microtime(true) - $t;
    logger::log('COLLECTION', 'Preparing update...');
	$t = microtime(true);
    prefs::$database->prepareCollectionUpdate();
    $performance['prepareupdate'] = microtime(true) - $t;

    // DO NOT open the transaction before this point, doing it
    // any earlier makes MySQL very unhappy because reasons
	prefs::$database->open_transaction();

    $player = new player();
    logger::log('COLLECTION', 'Doing Update');
	$t = microtime(true);
    foreach ($player->musicCollectionUpdate() as $filedata) {
    	prefs::$database->newTrack($filedata);
    }
    $performance['trackbytrack_scan'] = microtime(true) - $t;
	$t = microtime(true);
	prefs::$database->tracks_to_database();
    $performance['tracks_to_database_outer'] = microtime(true) - $t;

	prefs::$database->close_transaction();

    logger::log('COLLECTION', 'Tidying...');
	$t = microtime(true);
    prefs::$database->tidy_database();
    $performance['tidydatabase'] = microtime(true) - $t;
    logger::log('COLLECTION', 'Finishing...');
	// Add a marker to the monitor file to say we've finished
	$player->collectionUpdateDone();

	prefs::$database->create_triggers();

	// Clear the update lock
	prefs::$database->clearUpdateLock();

	$performance['total'] = microtime(true) - $timer;

	// prefs::$database->save_collection_lastmodified();

	print_performance_measurements();

}

?>
