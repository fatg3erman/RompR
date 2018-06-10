
<?php

// Automatic Collection Updates can be performed using cURL:
// curl -b "currenthost=Default;player_backend=mpd" http://localhost/rompr/albums.php?rebuild > /dev/null
// where currenthost is the name of one of the Players defined in the Configuration menu
// and player_backend MUST be mpd or mopidy, depending on what your player is.
// You can also use eg -b "debug_enabled=8;currenthost=MPD;player_backend=mpd"
// to get more debug info in the webserver error log.

include ("includes/vars.php");
include ("includes/functions.php");
include ("utils/imagefunctions.php");
include ("international.php");

set_time_limit(800);
$error = 0;
include("backends/sql/backend.php");

debuglog("======================================================================","TIMINGS",4);
debuglog("== Starting Backend Collection Malarkey","TIMINGS",4);
$initmem = memory_get_usage();
debuglog("Memory Used is ".$initmem,"COLLECTION",4);
$now2 = time();

switch (true) {

    case array_key_exists('item', $_REQUEST):
        logit('item');
        // Populate a dropdown in the collection or search results
        dumpAlbums($_REQUEST['item']);
        break;

    case array_key_exists('mpdsearch', $_REQUEST):
        logit('mpdsearch');
        // Handle an mpd-style search request
        include ("player/mpd/connection.php");
        include ("collection/collection.php");
        $trackbytrack = true;
        $doing_search = true;
        mpd_search();
        break;

    case array_key_exists('browsealbum', $_REQUEST):
        logit('browsealbum');
        // Populate a spotify album in mopidy's search results - as spotify doesn't return all tracks
        include ("player/mpd/connection.php");
        include ("collection/collection.php");
        $trackbytrack = true;
        $doing_search = true;
        browse_album();
        break;

    case array_key_exists("rawterms", $_REQUEST):
        logit('rawterms');
        // Handle an mpd-style search request requiring tl_track format results
        // Note that raw_search uses the collection models but not the database
        // hence $trackbytrack must be false
        debuglog("Doing RAW search","MPD SEARCH",7);
        include ("player/mpd/connection.php");
        include ("collection/collection.php");
        include ("collection/dbsearch.php");
        $doing_search = true;
        raw_search();
        close_mpd();
        break;

    case array_key_exists('terms', $_REQUEST):
        logit('terms');
        // SQL database search request
        include ("player/mpd/connection.php");
        include ("collection/collection.php");
        include ("collection/dbsearch.php");
        $doing_search = true;
        database_search();
        break;

    case array_key_exists('wishlist', $_REQUEST):
        logit('wishlist');
        //  For wishlist viewer
        getWishlist();
        break;

    case array_key_exists('rebuild', $_REQUEST):
        logit('rebuild');
        // This is a request to rebuild the music collection
        include ("player/mpd/connection.php");
        include ("collection/collection.php");
        $trackbytrack = true;
        update_collection();
        break;

    default:
        debuglog("Couldn't figure out what to do!","ALBUMS",1);
        break;

}

if (isset($parse_time)) {
    debuglog("== Time Spent Reading Socket Data                      : ".$parse_time,"TIMTINGS",4);
    debuglog("== Time Spent Parsing Socket Data                      : ".$rtime,"TIMTINGS",4);
    debuglog("== Time Spent Checking/Writing to Database             : ".$db_time,"TIMTINGS",4);
    debuglog("== Time Spent Putting Stuff into Collection Structures : ".$coll_time,"TIMTINGS",4);
    debuglog("== Time Spent Sorting Collection Structures            : ".$cp_time,"TIMTINGS",4);
}

debuglog("== Collection Update And Send took ".format_time(time() - $now2),"TIMINGS",4);
$peakmem = memory_get_peak_usage();
$ourmem = $peakmem - $initmem;
debuglog("Peak Memory Used Was ".number_format($peakmem)." bytes  - meaning we used ".number_format($ourmem)." bytes.","COLLECTION",4);
debuglog("======================================================================","TIMINGS",4);

function logit($key) {
    if (is_array($_REQUEST[$key])) {
        debuglog("Request is : ".$key.' : '.multi_implode($_REQUEST[$key], ", "), "COLLECTION",8);
    } else {
        debuglog("Request is ".$key."=".$_REQUEST[$key],"COLLECTION",8);
    }
}

function checkDomains($d) {
    if (array_key_exists('domains', $d)) {
        return $d['domains'];
    }
    debuglog("No search domains in use","SEARCH");
    return null;
}

function mpd_search() {
    global $collection, $dbterms, $skin;
    // If we're searching for tags or ratings it would seem sensible to only search the database
    // HOWEVER - we could be searching for genre of performer or composer or any - which will not match in the database
    // For those cases ONLY, controller.js will call into this instead of database_search, and we set $dbterms
    // to make the collection check everything it finds against the database
    $cmd = $_REQUEST['command'];
    $domains = checkDomains($_REQUEST);
    foreach ($_REQUEST['mpdsearch'] as $key => $term) {
        if ($key == "tag") {
            $dbterms['tags'] = $term;
        } else if ($key == "rating") {
            $dbterms['rating'] = $term;
        } else if ($key == "any") {
            foreach ($term as $t) {
                $terms = explode(' ',$t);
                foreach ($terms as $tom) {
                    $cmd .= " ".$key.' "'.format_for_mpd(html_entity_decode(trim($tom))).'"';
                }
            }
        } else {
            foreach ($term as $t) {
                $cmd .= " ".$key.' "'.format_for_mpd(html_entity_decode(trim($t))).'"';
            }
        }
    }
    debuglog("Search command : ".$cmd,"MPD SEARCH");
    if ($_REQUEST['resultstype'] == "tree") {
        include ("player/mpd/filetree.php");
        require_once ("skins/".$skin."/ui_elements.php");
        doFileSearch($cmd, $domains);
    } else {
        cleanSearchTables();
        prepareCollectionUpdate();
        doCollection($cmd, $domains);
        $collection->tracks_to_database();
        close_transaction();
        dumpAlbums($_REQUEST['dump']);
        remove_findtracks();
    }
    close_mpd();
}

function browse_album() {
    global $collection, $skin;
    $domains = array();
    $a = preg_match('/(a|b)(.*?)(\d+|root)/', $_REQUEST['browsealbum'], $matches);
    if (!$a) {
        print '<h3>'.get_int_text("label_general_error").'</h3>';
        debuglog('Browse Album Failed - regexp failed to match '.$_REQUEST['browsealbum'],"DUMPALBUMS",3);
        return false;
    }
    $why = $matches[1];
    $what = $matches[2];
    $who = $matches[3];
    $albumlink = get_albumlink($who);
    if (substr($albumlink, 0, 8) == 'podcast+') {
        require_once('includes/podcastfunctions.php');
        debuglog("Browsing For Podcast ".substr($albumlink, 9), "ALBUMS");
        $podid = getNewPodcast(substr($albumlink, 8), 0);
        debuglog("Ouputting Podcast ID ".$podid, "ALBUMS");
        outputPodcast($podid, false);
    } else {
        if (preg_match('/^.+?:artist:/', $albumlink)) {
            remove_album_from_database($who);
        }
        $cmd = 'find file "'.$albumlink.'"';
        debuglog("Doing Album Browse : ".$cmd,"MPD");
        prepareCollectionUpdate();
        doCollection($cmd, $domains);
        $collection->tracks_to_database(true);
        close_transaction();
        remove_findtracks();
        if (preg_match('/^.+?:album:/', $albumlink)) {
            // Just occasionally, the spotify album originally returned by search has an incorrect AlbumArtist
            // When we browse the album the new tracks therefore get added to a new album, while the original tracks
            // remain attached to the old one. This is where we use do_tracks_from_database with an array of albumids
            // which joins them together into a virtual album, with the track ordering correct
            print do_tracks_from_database($why, $what, find_justadded_albums(), true);
        } else {
            $artistarray = find_justadded_artists();
            $do_controlheader = true;
            foreach ($artistarray as $artistid) {
                do_albums_from_database($why, 'album', $artistid, false, false, true, $do_controlheader);
                $do_controlheader = false;
            }
        }
    }
    close_mpd();
}

function raw_search() {
    global $collection, $doing_search;
    $domains = checkDomains($_REQUEST);
    $found = 0;
    debuglog("checkdb is ".$_REQUEST['checkdb'],"MPD SEARCH",4);
    if ($_REQUEST['checkdb'] !== 'false') {
        $collection = new musicCollection();
        debuglog(" ... checking database first ", "MPD SEARCH", 7);
        $found = doDbCollection($_REQUEST['rawterms'], $domains, "RAW");
        if ($found > 0) {
            debuglog("  ... found ".$found." matches in database", "MPD SEARCH", 6);
        }
    }
    if ($found == 0) {
        $cmd = $_REQUEST['command'];
        foreach ($_REQUEST['rawterms'] as $key => $term) {
            if ($key == "track_name") {
                $cmd .= ' title "'.format_for_mpd(html_entity_decode($term[0])).'"';
            } else {
                $cmd .= " ".$key.' "'.format_for_mpd(html_entity_decode($term[0])).'"';
            }
        }
        debuglog("Search command : ".$cmd,"MPD SEARCH");
        $doing_search = true;
        doCollection($cmd, $domains);
        
        // For backends that don't support multiple parameters (Google Play)
        // This'll return nothing for Spotify, so it's OK. It might help SoundCloud too.
        
        $cmd = $_REQUEST['command'].' any ';
        $parms = array();
        if (array_key_exists('artist', $_REQUEST['rawterms'])) {
            $parms[] = format_for_mpd(html_entity_decode($_REQUEST['rawterms']['artist'][0]));
        }
        if (array_key_exists('track_name', $_REQUEST['rawterms'])) {
            $parms[] = format_for_mpd(html_entity_decode($_REQUEST['rawterms']['track_name'][0]));
        }
        if (count($parms) > 0) {
            $cmd .= '"'.implode(' ',$parms).'"';
            debuglog("Search command : ".$cmd,"MPD SEARCH");
            $doing_search = true;
            $collection->filter_duplicate_tracks();
            doCollection($cmd, $domains, false);
        }
        
    }
    print json_encode($collection->tracks_as_array());
}

function database_search() {
    global $tree;
    $domains = checkDomains($_REQUEST);
    if ($_REQUEST['resultstype'] == "tree") {
        $tree = new mpdlistthing(null);
    } else {
        cleanSearchTables();
        open_transaction();
     }
    $fcount = doDbCollection($_REQUEST['terms'], $domains, $_REQUEST['resultstype']);
    if ($_REQUEST['resultstype'] == "tree") {
        printFileSearch($tree, $fcount);
    } else {
        close_transaction();
        dumpAlbums($_REQUEST['dump']);
    }
    close_mpd();
}

function update_collection() {
    global $collection;
    cleanSearchTables();
    prepareCollectionUpdate();
    musicCollectionUpdate();
    $collection->tracks_to_database();
    tidy_database();
    remove_findtracks();
    $whattodump = (array_key_exists('dump', $_REQUEST)) ? $_REQUEST['dump'] : false;
    if ($whattodump === false) {
        print '<html></html>';
    } else {
        dumpAlbums($whattodump);
    }
    close_mpd();
}

?>
