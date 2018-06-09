<?php
// Clean the backend cache. We do this with an AJAX request because
// a) It doesn't slow down the loading of the page, and
// b) If we do it at page load time Chrome's page preload feature can result in two of them running simultaneously,
//    which produces 'cannot stat' errors.

chdir('..');
include("includes/vars.php");
include("includes/functions.php");
include("utils/imagefunctions.php");
include("backends/sql/backend.php");

debuglog("Checking Cache","CACHE CLEANER");

// DO NOT REDUCE the values for musicbrainz or discogs
// - we have to follow their API rules and as we don't check
// expiry headers at all we need to keep everything for a month
// otherwise they will ban us. Don't spoil it for everyone.

// One Month
clean_cache_dir('prefs/jsoncache/musicbrainz/', 2592000);
// One Month
clean_cache_dir('prefs/jsoncache/allmusic/', 2592000);
// One Month
clean_cache_dir('prefs/jsoncache/discogs/', 2592000);
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
// Six Months - after all, lyrics are small and don't change
clean_cache_dir('prefs/jsoncache/lyrics/', 15552000);
// Two weeks (or it can get REALLY big)
clean_cache_dir('prefs/imagecache/', 1296000);
// Clean the albumart temporary upload directory
clean_cache_dir('albumart/', 1);
// Clean the temp directory
clean_cache_dir('prefs/temp/', 1);
debuglog("Cache has been cleaned","CACHE CLEANER");

if ($mysqlc) {

    debuglog("Checking database for hidden album art","CACHE CLEANER");
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
            debuglog("Removing image for hidden album ".$obj->Albumname." ".$obj->Image,"CACHE CLEANER");
            generic_sql_query("UPDATE Albumtable SET Image = NULL, Searched = 0 WHERE Albumindex = ".$obj->Albumindex, true);
        }
    }

    if ($prefs['cleanalbumimages']) {
        debuglog("Checking albumart folder for unneeded images","CACHE CLEANER");
        $files = glob('albumart/small/*.jpg');
        foreach ($files as $image) {
            // Remove images for hidden tracks and search results. The missing check below will reset the db entries for those albums
            // Keep everything for 24 hours regardless, we might be using it in a playlist or something
            if (filemtime($image) < time()-86400) {
                $count = sql_prepare_query(false, null, 'acount', 0, "SELECT COUNT(Albumindex) AS acount FROM Albumtable WHERE Image = ? AND Albumindex IN (SELECT DISTINCT Albumindex FROM Tracktable WHERE Hidden = 0 AND isSearchResult < 2 AND URI IS NOT NULL)", $image);
                if ($count < 1) {
                    debuglog("  Removing Unused Album image ".$image,"CACHE CLEANER");
                    exec('rm albumart/small/'.basename($image));
                    if (file_exists('albumart/medium/'.basename($image))) {
                        exec('rm albumart/medium/'.basename($image));
                    }
                    exec('rm albumart/asdownloaded/'.basename($image));
                }
            }
        }

        debuglog("Checking for orphaned radio station images","CACHE CLEANER");
        $files = glob('prefs/userstreams/*.*');
        foreach ($files as $image) {
            $count = sql_prepare_query(false, null, 'acount', 0, "SELECT COUNT(Stationindex) AS acount FROM RadioStationtable WHERE Image = ?",$image);
            if ($count < 1) {
                debuglog("  Removing orphaned radio station image ".$image,"CACHE CLEANER");
                exec('rm '.$image);
            }
        }

        debuglog("Checking for orphaned podcast data","CACHE CLEANER");
        $files = glob('prefs/podcasts/*');
        $pods = sql_get_column("SELECT PODindex FROM Podcasttable", 'PODindex');
        foreach ($files as $file) {
            if (!in_array(basename($file), $pods)) {
                debuglog("  Removing orphaned podcast directory ".$file,"CACHE CLEANER");
                exec('rm -fR '.$file);
            }
        }
        $files = glob('prefs/podcasts/*');
        foreach ($files as $pod) {
            $i = simple_query('Image', 'Podcasttable', 'PODindex', basename($pod), '');
            $files = glob($pod.'/{*.jpg,*.jpeg,*.JPEG,*.JPG,*.gif,*.GIF,*.png,*.PNG}', GLOB_BRACE);
            foreach ($files as $file) {
                if ($file != $i) {
                    debuglog("  Removing orphaned podcast image ".$file,"CACHE CLEANER");
                    exec('rm "'.$file.'"');
                }
            }
        }
    }

    debuglog("Checking database for missing album art","CACHE CLEANER");
    $result = generic_sql_query("SELECT Albumindex, Albumname, Image, Domain, ImgKey FROM Albumtable", false, PDO::FETCH_OBJ);
    foreach ($result as $obj) {
        if ($obj->Image != '' && !file_exists($obj->Image)) {
            if (preg_match('#^getRemoteImage\.php\?url=(.*)#', $obj->Image)) {
                // Don't do this, it archives all the soundcloud images for search results
                // and we don't want that.
                // debuglog($obj->Albumname." has remote image ".$obj->Image,"CACHE CLEANER");
                // $retval = archive_image($obj->Image, $obj->ImgKey);
                // $image = $retval['image'];
                // $searched = 1;
            } else {
                debuglog($obj->Albumname." has missing image ".$obj->Image,"CACHE CLEANER");
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
    }

    // Compact the database
    if ($prefs['collection_type'] == 'sqlite') {
        debuglog("Vacuuming Database","CACHE CLEANER");
        generic_sql_query("VACUUM", true);
        generic_sql_query("PRAGMA optimize", true);
    }

}

function clean_cache_dir($dir, $time) {

    debuglog("Cache Cleaner is running on ".$dir,"CACHE CLEANER");
    $cache = glob($dir."*");
    $now = time();
    foreach($cache as $file) {
        if (!is_dir($file)) {
            if($now - filemtime($file) > $time) {
                debuglog("Removing file ".$file,"CACHE CLEANER",4);
                @unlink ($file);
            }
        }
    }
}

?>

<html></html>
