<?php

include( "backends/sql/connect.php");
connect_to_database();
$find_track = null;
$update_track = null;
$transaction_open = false;
$numdone = 0;
$doing_search = false;
$nodata = array (
	'isSearchResult' => 4,
	'Rating' => 0,
	'Tags' => array()
);

// In the following, we're using a mixture of prepared statements and raw queries.
// Raw queries are easier to handle in many cases, but prepared statements take a lot of fuss away
// when dealing with strings, as it automatically escapes everything.

// So what are Hidden tracks?
// These are used to count plays from online sources when those tracks are not in the collection.
// Doing this does increase the size of the database. Quite a lot. But without it the stats for charts
// and fave artists etc don't make a lot of sense in a world where a proportion of your listening
// is in response to searches of Spotify or youtube etc.

// Wishlist items have Uri as NULL. Each wishlist track is in a distinct album - this makes stuff
// easier for the wishlist viewer

// Assumptions are made in the code that Wishlist items will not be hidden tracks and that hidden
// tracks have no metadata apart from a Playcount. Always be aware of this.

// For tracks, LastModified controls whether a collection update will update any of its data.
// Tracks added by hand (by tagging or rating, via userRatings.php) must have LastModified as NULL
// - this is how we prevent the collection update from removing them.

// Search:
// The database is now used to handle the search results as well.
// Tracktable.isSearchResult is set to:
//		1 on any existing track that comes up in the search
//		2 for any track that comes up the search and has to be added - i.e it's not part of the main collection.
//		3 for any hidden track that comes up in search so it can be re-hidden later.
//		The reason for doing search through the database like this is that
//		a) It means we can dump the old xml backend
//		b) The search results will obey the same sort options as the collection
//		c) We can include rating info in the search results just like in the collection.
//		Note that there is arithmetical logic to the values used here, they're not arbitrary flags

// Collection:
//  justAdded is automatically set to 1 for any track that has just been added
//  when updating the collection we set them all to 0 and then set to 1 on any existing track we find,
//  then we can easily remove old tracks.

function create_new_track(&$data) {

	// create_new_track
	//		Creates a new track, along with artists and album if necessary
	//		Returns: TTindex
	
	global $mysqlc;

	if ($data['albumai'] == null) {
		// Does the albumartist exist?
		$data['albumai'] = check_artist($data['albumartist']);
	}

	// Does the track artist exist?
	if ($data['trackai'] == null) {
		if ($data['artist'] != $data['albumartist']) {
			$data['trackai'] = check_artist($data['artist']);
		} else {
			$data['trackai'] = $data['albumai'];
		}
	}

	if ($data['albumai'] == null || $data['trackai'] == null) {
		debuglog("Trying to create new track but failed to get an artist index","MYSQL",2);
		return null;
	}

	if ($data['albumindex'] == null) {
		// Does the album exist?
		if ($data['album'] == null) {
			$data['album'] = 'rompr_wishlist_'.microtime('true');
		}
		$data['albumindex'] = check_album($data);
		if ($data['albumindex'] == null) {
			debuglog("Trying to create new track but failed to get an album index","MYSQL",2);
			return null;
		}
	}

	$retval = null;
	if ($stmt = sql_prepare_query(
		"INSERT INTO
			Tracktable
			(Title, Albumindex, Trackno, Duration, Artistindex, Disc, Uri, LastModified, Hidden, isSearchResult)
			VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
		$data['title'], $data['albumindex'], $data['trackno'], $data['duration'], $data['trackai'],
		$data['disc'], $data['uri'], $data['lastmodified'], $data['hidden'], $data['searchflag']))
	{
		$retval = $mysqlc->lastInsertId();
	}
	$stmt = null;
	return $retval;
}

function check_artist($artist) {

	// check_artist:
	//		Checks for the existence of an artist by name in the Artisttable and creates it if necessary
	//		Returns: Artistindex

	$index = null;
	if ($stmt = sql_prepare_query(
		"SELECT Artistindex FROM Artisttable WHERE LOWER(Artistname) = LOWER(?)", $artist)) {
		$obj = $stmt->fetch(PDO::FETCH_OBJ);
		$index = $obj ? $obj->Artistindex : null;
	    if ($index) {
	    } else {
			$index = create_new_artist($artist);
	    }
	}
	$stmt = null;
	return $index;
}

function create_new_artist($artist) {

	// create_new_artist
	//		Creates a new artist
	//		Returns: Artistindex

	global $mysqlc;
	$retval = null;
	if ($stmt = sql_prepare_query("INSERT INTO Artisttable (Artistname) VALUES (?)", $artist)) {
		$retval = $mysqlc->lastInsertId();
		debuglog("Created artist ".$artist." with Artistindex ".$retval,"MYSQL",7);
	}
	$stmt = null;
	return $retval;
}

function best_value($a, $b) {

	// best_value
	//		Used by check_album to determine the best value to use when updating album details
	//		Returns: value

	if ($b == null || $b == "") {
		return $a;
	} else {
		return $b;
	}
}

function check_album(&$data) {

	// check_album:
	//		Checks for the existence of an album and creates it if necessary
	//		Returns: Albumindex

	global $prefs;
	$index = null;
	$year = null;
	$img = null;
	if ($stmt = sql_prepare_query(
		"SELECT
			Albumindex,
			Year,
			Image,
			AlbumUri
		FROM
			Albumtable
		WHERE
			LOWER(Albumname) = LOWER(?)
			AND AlbumArtistindex = ?
			AND Domain = ?", $data['album'], $data['albumai'], $data['domain'])) {
		$obj = $stmt->fetch(PDO::FETCH_OBJ);
		$index = $obj ? $obj->Albumindex : 0;
		if ($index) {
			$year = best_value($obj->Year, $data['date']);
			$img  = best_value($obj->Image, $data['image']);
			$uri  = best_value($obj->AlbumUri, $data['albumuri']);
			if ($img != $obj->Image) {
				$retval = archive_image($img, $data['imagekey']);
				$img = $retval['image'];
			}
			if ($year != $obj->Year || $img != $obj->Image || $uri != $obj->AlbumUri) {

				if ($prefs['debug_enabled'] > 6) {
					debuglog("Updating Details For Album ".$data['album']." (index ".$index.")" ,"MYSQL",7);
					debuglog("  Old Date  : ".$obj->Year,"MYSQL",7);
					debuglog("  New Date  : ".$year,"MYSQL",7);
					debuglog("  Old Image : ".$obj->Image,"MYSQL",7);
					debuglog("  New Image : ".$img,"MYSQL",7);
					debuglog("  Old Uri  : ".$obj->AlbumUri,"MYSQL",7);
					debuglog("  New Uri  : ".$uri,"MYSQL",7);
				}
				
				if ($up = sql_prepare_query("UPDATE Albumtable SET Year=?, Image=?, AlbumUri=?, justUpdated=1 WHERE Albumindex=?",$year, $img, $uri, $index)) {
					debuglog("   ...Success","MYSQL",9);
					$up = null;
				} else {
					debuglog("   Album ".$data['album']." update FAILED","MYSQL",3);
					return false;
				}
			}
		} else {
			$index = create_new_album($data);
		}
	}
	$stmt = null;
	return $index;
}

function create_new_album($data) {

	// create_new_album
	//		Creates an album
	//		Returns: Albumindex

	global $mysqlc;
	$retval = null;
	$im = array('searched' => 0, 'image' => null);
	if ($data['imagekey'] !== null) {
		$im = archive_image($data['image'], $data['imagekey']);
	}
	if ($stmt = sql_prepare_query(
		"INSERT INTO
			Albumtable
			(Albumname, AlbumArtistindex, AlbumUri, Year, Searched, ImgKey, mbid, Domain, Image)
		VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?)",
		$data['album'], $data['albumai'], $data['albumuri'], $data['date'], $im['searched'], $data['imagekey'], $data['ambid'], $data['domain'], $im['image'])) {
		$retval = $mysqlc->lastInsertId();
		debuglog("Created Album ".$data['album']." with Albumindex ".$retval,"MYSQL",7);
	}
	$stmt = null;
	return $retval;
}

function remove_ttid($ttid) {

	// Remove a track from the database.
	// Doesn't do any cleaning up - call remove_cruft afterwards to remove orphaned artists and albums

	// Deleting tracks will delete their associated playcounts. While it might seem like a good idea
	// to hide them instead, in fact this results in a situation where we have tracks in our database
	// that no longer exist in physical form - eg if local tracks are removed. This is really bad if we then
	// later play those tracks from an online source and rate them. romprmetadata::find_item will return the hidden local track,
	// which will get rated and appear back in the collection. So now we have an unplayable track in our collection.
	// There's no real way round it, (without creating some godwaful lookup table of backends it's safe to do this with)
	// so we just delete the track and lose the playcount information.

	// If it's a search result, it must be a manually added track (we can't delete collection tracks)
	// and we might still need it in the search, so set it to a 2 instead of deleting it.

	$retval = false;
	debuglog("Removing track ".$ttid,"MYSQL",5);
	if ($result = generic_sql_query("DELETE FROM Tracktable WHERE isSearchResult != 1 AND TTindex = '".$ttid."'")) {
		if ($result = generic_sql_query("UPDATE Tracktable SET isSearchResult = 2 WHERE isSearchResult = 1 AND TTindex = '".$ttid."'")) {
			$retval = true;
		}
	}
	$result = null;
	return $retval;
}

function list_tags() {

	// list_tags
	//		Return a sorted lst of tag names. Used by the UI for creating the tag menu

	$tags = array();
	if ($result = generic_sql_query("SELECT Name FROM Tagtable ORDER BY LOWER(Name)")) {
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			$tags[] = $obj->Name;
		}
	}
	$result = null;
	return $tags;
}

function list_all_rating_data($sortby) {

	global $prefs;

	// list_all_rating_data
	//		Used by Rating Manager to get all the info it needs

	$qstring = "SELECT
			IFNULL(r.Rating, 0) AS Rating,
			IFNULL(".SQL_TAG_CONCAT.", 'No Tags') AS Tags,
			tr.TTindex,
			tr.TrackNo,
			tr.Title,
			tr.Duration,
			tr.Uri,
			a.Artistname,
			aa.Artistname AS AlbumArtist,
			al.Albumname,
			al.Image, ";
			if (count($prefs['nosortprefixes']) > 0) {
				$qstring .= "CASE ";
				foreach($prefs['nosortprefixes'] AS $p) {
					$phpisshitsometimes = strlen($p)+2;
					$qstring .= "WHEN LOWER(aa.Artistname) LIKE '".strtolower($p).
						" %' THEN UPPER(SUBSTR(aa.Artistname,".$phpisshitsometimes.",1)) ";
				}
				$qstring .= "ELSE UPPER(SUBSTR(aa.Artistname,1,1)) END AS SortLetter";
			} else {
				$qstring .= "UPPER(SUBSTR(aa.Artistname,1,1)) AS SortLetter";
			}

	$qstring .= " FROM
			Tracktable AS tr
			LEFT JOIN Ratingtable AS r ON tr.TTindex = r.TTindex
			LEFT JOIN TagListtable AS tl ON tr.TTindex = tl.TTindex
			LEFT JOIN Tagtable AS t USING (Tagindex)
			JOIN Albumtable AS al USING (Albumindex)
			JOIN Artisttable AS a ON (tr.Artistindex = a.Artistindex)
			JOIN Artisttable AS aa ON (al.AlbumArtistindex = aa.Artistindex)
		WHERE (r.Rating IS NOT NULL OR t.Name IS NOT NULL) AND tr.Uri IS NOT NULL
		GROUP BY tr.TTindex
		ORDER BY ";

	if (count($prefs['nosortprefixes']) > 0) {
		$qstring .= "(CASE ";
		foreach($prefs['nosortprefixes'] AS $p) {
			$phpisshitsometimes = strlen($p)+2;
			$qstring .= "WHEN LOWER(AlbumArtist) LIKE '".strtolower($p).
				" %' THEN LOWER(SUBSTR(AlbumArtist,".$phpisshitsometimes.")) ";
		}
		$qstring .= "ELSE LOWER(AlbumArtist) END)";
	} else {
		$qstring .= "LOWER(AlbumArtist)";
	}
	$qstring .= ", al.Albumname, tr.TrackNo";

	$ratings = array();

	if ($result = generic_sql_query($qstring)) {
		while ($a = $result->fetch(PDO::FETCH_ASSOC)) {
			if ($sortby == 'Tag') {
				$indices = explode(', ',$a['Tags']);
			} else {
				$indices = array($a[$sortby]);
			}
			foreach ($indices as $i) {
				$ratings[$i][] = $a;
			}
		}
	}

	if ($sortby != 'AlbumArtist') {
		ksort($ratings, SORT_STRING);
	}
	$result = null;
	return $ratings;
}

function clear_wishlist() {
	if ($result = generic_sql_query("DELETE FROM Tracktable WHERE Uri IS NULL")) {
		return true;
	} else {
		return false;
	}
}

function num_collection_tracks($albumindex) {
	// Returns the number of tracks this album contains that were added by a collection update
	// (i.e. not added manually). We do this because editing year or album artist for those albums
	// won't hold across a collection update, so we just forbid it.
	$retval = 0;
	if ($result = generic_sql_query("SELECT COUNT(TTindex) AS cnt FROM Tracktable WHERE Albumindex = ".$albumindex." AND LastModified IS NOT NULL AND Hidden = 0 AND Uri IS NOT NULL AND isSearchResult < 2")) {
		$obj = $result->fetch(PDO::FETCH_OBJ);
		$retval = $obj->cnt;
	}
	$result = null;
	return $retval;
}

function get_all_data($ttid) {

	// Misleadingly named function which should be used to get ratings and tags
	// (and whatever else we might add) based on a TTindex
	global $nodata;
	$data = $nodata;
	if ($result = generic_sql_query("SELECT
			IFNULL(r.Rating, 0) AS Rating,
			IFNULL(p.Playcount, 0) AS Playcount,
			".sql_to_unixtime('p.LastPlayed')." AS LastTime,
			".sql_to_unixtime('tr.DateAdded')." AS DateAdded,
			IFNULL(".SQL_TAG_CONCAT.", '') AS Tags,
			tr.isSearchResult,
			tr.Hidden
		FROM
			Tracktable AS tr
			LEFT JOIN Ratingtable AS r ON tr.TTindex = r.TTindex
			LEFT JOIN Playcounttable AS p ON tr.TTindex = p.TTindex
			LEFT JOIN TagListtable AS tl ON tr.TTindex = tl.TTindex
			LEFT JOIN Tagtable AS t USING (Tagindex)
		WHERE tr.TTindex = ".$ttid."
		GROUP BY tr.TTindex
		ORDER BY t.Name"
	)) {
		$database = $result->fetchAll(PDO::FETCH_ASSOC);
		$data = array_shift($database);
		$data['Tags'] = explode(', ', $data['Tags']);
		if ($data['LastTime'] != null && $data['LastTime'] != 0 && $data['LastTime'] != '0') {
			$data['Last'] = $data['LastTime'];
		}
	}
	$result = null;
	return $data;
}

// Looking up this way is hugely faster than looking up by Uri
function get_extra_track_info(&$filedata) {
	$data = null;
	if ($result = sql_prepare_query(
		"SELECT Uri, TTindex, Disc, Artistname AS AlbumArtist, Albumtable.Image AS ImageForPlaylist, ImgKey AS ImgKey, mbid AS MUSICBRAINZ_ALBUMID
			FROM
				Tracktable
				JOIN Albumtable USING (Albumindex)
				JOIN Artisttable ON Albumtable.AlbumArtistindex = Artisttable.Artistindex
				WHERE Title = ?
				AND TrackNo = ?
				AND Albumname = ?",
			$filedata['Title'], $filedata['Track'], $filedata['Album']
		)) {

		while ($tinfo = $result->fetch(PDO::FETCH_ASSOC)) {
			if ($tinfo['Uri'] == $filedata['file']) {
				$data = $tinfo;
				break;
			}
		}
	}
	$result = null;
	if (is_array($data)) {
		return $data;
	} else {
		return array();
	}
}

// While this is nice, the complexity of the query makes it very slow - the above query completes
// in about 1/3 of the time. It's s shame, since this pre-populates all our usermeta for the playlist
// but the delay is unacceptable with large playlists. Even the above version is pushing it
// function get_extra_track_info(&$filedata) {
// 	$data = null;
// 	if ($result = sql_prepare_query("SELECT
// 			IFNULL(r.Rating, 0) AS Rating,
// 			IFNULL(p.Playcount, 0) AS Playcount,
// 			".sql_to_unixtime('p.LastPlayed')." AS LastTime,
// 			".sql_to_unixtime('tr.DateAdded')." AS DateAdded,
// 			IFNULL(".SQL_TAG_CONCAT.", '') AS Tags,
// 			tr.Uri,
// 			tr.TTindex AS TTindex,
// 			tr.Disc AS Disc,
// 			tr.isSearchResult AS isSearchResult,
// 			tr.Hidden AS Hidden,
// 			ta.Artistname AS AlbumArtist,
// 			al.Image AS ImageForPlaylist,
// 			al.ImgKey AS ImgKey
// 		FROM
// 			(Tracktable AS tr, Artisttable AS ta, Albumtable AS al)
// 			LEFT JOIN Ratingtable AS r ON tr.TTindex = r.TTindex
// 			LEFT JOIN Playcounttable AS p ON tr.TTindex = p.TTindex
// 			LEFT JOIN TagListtable AS tl ON tr.TTindex = tl.TTindex
// 			LEFT JOIN Tagtable AS t USING (Tagindex)
// 				WHERE tr.Title = ?
// 				AND al.Albumname = ?
// 				AND tr.Trackno = ?
// 				AND al.AlbumArtistindex = ta.Artistindex
// 				AND al.Albumindex = tr.Albumindex
// 		GROUP BY tr.TTindex
// 		ORDER BY t.Name",
// 		$filedata['Title'], $filedata['Album'], $filedata['Track']
// 		)) {

// 		while ($tinfo = $result->fetch(PDO::FETCH_ASSOC)) {
// 			if ($tinfo['Uri'] == $filedata['file']) {
// 				$data = $tinfo;
// 				break;
// 			}
// 		}
// 	}
// 	if (is_array($data)) {
// 		$data['Tags'] = explode(', ', $data['Tags']);
// 		if ($data['LastTime'] != null && $data['LastTime'] != 0 && $data['LastTime'] != '0') {
// 			$data['Last'] = $data['LastTime'];
// 		}
// 		return $data;
// 	} else {
// 		return array();
// 	}
// }

function get_imagesearch_info($key) {

	// Used by getalbumcover.php to get album and artist names etc based on an Image Key
	$retval = array(false, null, null, null, null, null, false);
	if ($result = generic_sql_query(
		"SELECT DISTINCT
			Artisttable.Artistname,
			Albumname,
			mbid,
			Albumindex,
			AlbumUri,
			isSearchResult
		FROM
			Albumtable
			JOIN Artisttable ON AlbumArtistindex = Artisttable.Artistindex
			JOIN Tracktable USING (Albumindex)
			WHERE ImgKey = '".$key."' AND isSearchResult < 2 AND Hidden = 0"
	)) {
		// This can come back with multiple results if we have the same album on multiple backends
		// So we make sure we combine the data to get the best possible set
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			if ($retval[1] == null) {
				$retval[1] = $obj->Artistname;
			}
			if ($retval[2] == null) {
				$retval[2] = $obj->Albumname;
			}
			if ($retval[3] == null || $retval[3] == "") {
				$retval[3] = $obj->mbid;
			}
			if ($retval[4] == null) {
				$retval[4] = get_album_directory($obj->Albumindex, $obj->AlbumUri);
			}
			if ($retval[5] == null || $retval[5] == "") {
				$retval[5] = $obj->AlbumUri;
			}
			$retval[0] = 1;
			$retval[6] = true;
			debuglog("Found album ".$retval[2]." in database","GETALBUMCOVER",6);
		}
	}

	if ($result = generic_sql_query(
		"SELECT DISTINCT
			Artisttable.Artistname,
			Albumname,
			mbid,
			Albumindex,
			AlbumUri,
			isSearchResult
		FROM
			Albumtable
			JOIN Artisttable ON AlbumArtistindex = Artisttable.Artistindex
			JOIN Tracktable USING (Albumindex)
			WHERE ImgKey = '".$key."' AND isSearchResult > 1"
	)) {
		// This can come back with multiple results if we have the same album on multiple backends
		// So we make sure we combine the data to get the best possible set
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			if ($retval[1] == null) {
				$retval[1] = $obj->Artistname;
			}
			if ($retval[2] == null) {
				$retval[2] = $obj->Albumname;
			}
			if ($retval[3] == null || $retval[3] == "") {
				$retval[3] = $obj->mbid;
			}
			if ($retval[4] == null) {
				$retval[4] = get_album_directory($obj->Albumindex, $obj->AlbumUri);
			}
			if ($retval[5] == null || $retval[5] == "") {
				$retval[5] = $obj->AlbumUri;
			}
			if ($retval[0] === false) {
				$retval[0] = 1;
			}
			$retval[6] = true;
			debuglog("Found album ".$retval[2]." in search results or hidden tracks","GETALBUMCOVER",6);
		}
	}
	$result = null;
	return $retval;
}

function get_albumlink($albumindex) {
	return simple_query('AlbumUri', 'Albumtable', 'Albumindex', $albumindex, "");
}

function get_album_directory($albumindex, $uri) {
	global $prefs;
	$retval = null;
	// Get album directory by using the Uri of one of its tracks, making sure we choose only local tracks
	if (getDomain($uri) == 'local') {
		if ($result = generic_sql_query(
			"SELECT Uri FROM Tracktable WHERE Albumindex = ".$albumindex." LIMIT 1")) {
			while ($obj2 = $result->fetch(PDO::FETCH_OBJ)) {
				$retval = dirname($obj2->Uri);
				$retval = preg_replace('#^local:track:#', '', $retval);
				$retval = preg_replace('#^file://#', '', $retval);
				$retval = preg_replace('#^beetslocal:\d+:'.$prefs['music_directory_albumart'].'/#', '', $retval);
				debuglog("Got album directory using track Uri - ".$retval,"SQL",9);
			}
		}
	}
	$result = null;
	return $retval;
}

function update_image_db($key, $notfound, $imagefile) {
	$val = ($notfound == 0) ? $imagefile : "";
	if ($stmt = sql_prepare_query("UPDATE Albumtable SET Image=?, Searched = 1 WHERE ImgKey=?", $val, $key)) {
		debuglog("    Database Image URL Updated","MYSQL",8);
	} else {
		debuglog("    Failed To Update Database Image URL","MYSQL",2);
	}
	$stmt = null;
}

function track_is_hidden($ttid) {
	$h = simple_query('Hidden', 'Tracktable', 'TTindex', $ttid, 0);
	return ($h != 0) ? true : false;
}

function track_is_searchresult($ttid) {
	// This is for detecting tracks that were added as part of a search, or un-hidden as part of a search
	$h = simple_query('isSearchResult', 'Tracktable', 'TTindex', $ttid, 0);
	return ($h > 1) ? true : false;
}

function track_is_wishlist($ttid) {
	$u = simple_query('Uri', 'Tracktable', 'TTindex', $ttid, '');
	if ($u === null) {
		debuglog("Track ".$ttid." is wishlist. Discarding","USERRATING");
		generic_sql_query("DELETE FROM Playcounttable WHERE TTindex=".$ttid);
		generic_sql_query("DELETE FROM Tracktable WHERE TTindex=".$ttid);
		return true;
	}
	return false;
}

function check_for_wishlist_track(&$data) {
	if ($result = sql_prepare_query("SELECT TTindex FROM Tracktable JOIN Artisttable USING (Artistindex) WHERE Artistname=? AND Title=? AND Uri IS NULL",$data['artist'],$data['title'])) {
		while ($obj = $result->fetch(PDO::FETCH_ASSOC)) {
			debuglog("Wishlist Track ".$result['TTindex']." matches the one we're adding","USERRATING");
			$meta = get_all_data($result['TTindex']);
			$data['attributes'] = array();
			$data['attributes'][] = array('attribute' => 'Rating', 'value' => $meta['Rating']);
			$data['attributes'][] = array('attribute' => 'Tags', 'value' => $meta['Tags']);
			generic_sql_query("DELETE FROM Tracktable WHERE TTindex=".$result['TTindex']);
		}
	}
	$result = null;
}

function albumartist_sort_query($flag) {
	global $prefs;
	// This query gives us album artists only. It also makes sure we only get artists for whom we
	// have actual tracks (no album artists who appear only on the wishlist or who have only hidden tracks)
	$sflag = ($flag == 'b') ? "AND t.isSearchResult > 0" : "AND t.isSearchResult < 2";
	$qstring =
		"SELECT
			a.Artistname,
			a.Artistindex
		FROM
			Artisttable AS a
			JOIN Albumtable AS al ON a.Artistindex = al.AlbumArtistindex
			JOIN Tracktable AS t ON al.Albumindex = t.Albumindex
		WHERE
			t.Uri IS NOT NULL
			AND t.Hidden = 0 ".$sflag."
		GROUP BY a.Artistindex
		ORDER BY ";
	foreach ($prefs['artistsatstart'] as $a) {
		$qstring .= "CASE WHEN LOWER(Artistname) = LOWER('".$a."') THEN 1 ELSE 2 END, ";
	}
	if (count($prefs['nosortprefixes']) > 0) {
		$qstring .= "(CASE ";
		foreach($prefs['nosortprefixes'] AS $p) {
			$phpisshitsometimes = strlen($p)+2;
			$qstring .= "WHEN LOWER(Artistname) LIKE '".strtolower($p).
				" %' THEN LOWER(SUBSTR(Artistname,".$phpisshitsometimes.")) ";
		}
		$qstring .= "ELSE LOWER(Artistname) END)";
	} else {
		$qstring .= "LOWER(Artistname)";
	}
	return $qstring;
	return $qstring;
}

function do_artists_from_database($why, $what, $who) {
	global $divtype;
	$singleheader = array();
	debuglog("Generating artist ".$why.$what.$who." from database","DUMPALBUMS",7);
	$singleheader['type'] = 'insertAfter';
	$singleheader['where'] = 'fothergill';
	$count = 0;
	if ($result = generic_sql_query(albumartist_sort_query($why))) {
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			if ($who == "root") {
				print artistHeader($why.$what.$obj->Artistindex, $obj->Artistname);
				$count++;
			} else {
				if ($obj->Artistindex != $who) {
					$singleheader['type'] = 'insertAfter';
					$singleheader['where'] = $why.$what.$obj->Artistindex;
				} else {
					$singleheader['html'] = artistHeader($why.$what.$obj->Artistindex, $obj->Artistname);
					$singleheader['id'] = $who;
					return $singleheader;
				}
			}
			$divtype = ($divtype == "album1") ? "album2" : "album1";
		}
	} else {
		print '<h3>'.get_int_text("label_general_error").'</h3>';
	}
	$result = null;
	return $count;
}

function get_list_of_artists() {
	$vals = array();
	if ($result = generic_sql_query(albumartist_sort_query("a"))) {
		while ($v = $result->fetch(PDO::FETCH_ASSOC)) {
			$vals[] = $v;
		}
	}
	$result = null;
	return $vals;
}

function album_sort_query($why, $what, $who) {
	global $prefs;
	$sflag = ($why == "b") ? "AND Tracktable.isSearchResult > 0" : "AND Tracktable.isSearchResult < 2";

	$qstring = "SELECT Albumtable.*, Artisttable.Artistname FROM Albumtable JOIN Artisttable ON
			(Albumtable.AlbumArtistindex = Artisttable.Artistindex) WHERE ";

	if ($who != "root") {
		$qstring .= "AlbumArtistindex = '".$who."' AND ";
	}
	$qstring .= "Albumindex IN (SELECT Albumindex FROM Tracktable WHERE
			Tracktable.Albumindex = Albumtable.Albumindex AND ";

	$qstring .= "Tracktable.Uri IS NOT NULL AND Tracktable.Hidden = 0 ".$sflag.")";
	$qstring .= " ORDER BY ";
	if ($prefs['sortcollectionby'] == "albumbyartist" && $who == "root") {
		foreach ($prefs['artistsatstart'] as $a) {
			$qstring .= "CASE WHEN LOWER(Artisttable.Artistname) = LOWER('".$a."') THEN 1 ELSE 2 END, ";
		}
		if (count($prefs['nosortprefixes']) > 0) {
			$qstring .= "(CASE ";
			foreach($prefs['nosortprefixes'] AS $p) {
				$phpisshitsometimes = strlen($p)+2;
				$qstring .= "WHEN LOWER(Artisttable.Artistname) LIKE '".strtolower($p).
					" %' THEN LOWER(SUBSTR(Artisttable.Artistname,".$phpisshitsometimes.")) ";
			}
			$qstring .= "ELSE LOWER(Artisttable.Artistname) END),";
		} else {
			$qstring .= "LOWER(Artisttable.Artistname),";
		}
	}
	$qstring .= " CASE WHEN Albumname LIKE '".get_int_text('label_allartist')."%' THEN 1 ELSE 2 END,";
	if ($prefs['sortbydate']) {
		if ($prefs['notvabydate']) {
			$qstring .= " CASE WHEN Artisttable.Artistname = 'Various Artists' THEN LOWER(Albumname) ELSE Year END,";
		} else {
			$qstring .= ' Year,';
		}
	}
	$qstring .= ' LOWER(Albumname)';
	return $qstring;
}

function do_artist_banner($why, $what, $who) {
	debuglog("Creating Banner ".$why." ".$what." ".$who,"SQL");
	$singleheader['type'] = 'insertAfter';
	$singleheader['where'] = 'fothergill';
	$qstring = album_sort_query($why, $what, 'root');
	if ($result = generic_sql_query($qstring)) {
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			if ($obj->AlbumArtistindex != $who) {
				$singleheader['where'] = 'aalbum'.$obj->Albumindex;
				$singleheader['type'] = 'insertAfter';
			} else {
				$singleheader['html'] = artistBanner($obj->Artistname, $obj->AlbumArtistindex, $why);
				$singleheader['id'] = $obj->AlbumArtistindex;
				return $singleheader;
			}
		}
	}
	$result = null;
}

function do_albums_from_database($why, $what, $who, $fragment = false, $use_artistindex = false, $force_artistname = false) {
	global $prefs;
	$singleheader = array();
	if ($prefs['sortcollectionby'] == "artist") {
		$singleheader['type'] = 'insertAtStart';
		$singleheader['where'] = $why.'artist'.$who;
	} else {
		$singleheader['type'] = 'insertAfter';
		$singleheader['where'] = 'fothergill';
	}
	debuglog("Generating albums for ".$why.$what.$who." from database","DUMPALBUMS",7);

	$qstring = album_sort_query($why, $what, $who);
	debuglog("Query String Is ".$qstring,"COLLECTION",9);

	$count = 0;
	if ($result = generic_sql_query($qstring)) {
		$currart = "";
		$currban = "";
		while ($obj = $result->fetch(PDO::FETCH_ASSOC)) {
			$artistbanner = ($prefs['sortcollectionby'] == 'albumbyartist' && $prefs['showartistbanners']) ? $obj['Artistname'] : null;
			$obj['Artistname'] = ($force_artistname || $prefs['sortcollectionby'] == "album") ? $obj['Artistname'] : null;
			$obj['why'] = $why;
			$obj['id'] = $why.$what.$obj['Albumindex'];
			if ($fragment === false) {
				if ($artistbanner !== null && $artistbanner !== $currban) {
					print artistBanner($artistbanner, $obj['AlbumArtistindex'], $why);
				}
				print albumHeader($obj);
			} else {
				if ($obj['Albumindex'] != $fragment) {
					$singleheader['where'] = 'aalbum'.$obj['Albumindex'];
					$singleheader['type'] = 'insertAfter';
				} else {
					$singleheader['html'] = albumHeader($obj);
					$singleheader['id'] = $fragment;
					return $singleheader;
				}
			}
			$currart = $obj['Artistname'];
			$currban = $artistbanner;
			$count++;
		}
		debuglog("... Found ".$count." albums", "COLLECTION");
		if ($count == 0 && !($why == 'a' && $who == 'root')) {
			noAlbumsHeader();
		}
	} else {
		print '<h3>'.get_int_text("label_general_error").'</h3>';
	}
	$result = null;
	return $count;
}

function artistBanner($a, $i, $why) {
	return '<div class="configtitle artistbanner" id="'.$why.'artist'.$i.'"><b>'.$a.'</b></div>';
}

function remove_album_from_database($albumid) {
	generic_sql_query("DELETE FROM Tracktable WHERE Albumindex = ".$albumid);
	generic_sql_query("DELETE FROM Albumtable WHERE Albumindex = ".$albumid);
}

function get_list_of_albums($aid) {
	global $prefs;
	$vals = array();

	$qstring = "SELECT * FROM Albumtable WHERE AlbumArtistindex = '".$aid."' AND ";
	$qstring .= "Albumindex IN (SELECT Albumindex FROM Tracktable WHERE Tracktable.Albumindex =
		Albumtable.Albumindex AND Tracktable.Uri IS NOT NULL AND Tracktable.Hidden = 0 AND
		Tracktable.isSearchResult < 2)";
	$qstring .= ' ORDER BY LOWER(Albumname)';
	if ($result = generic_sql_query($qstring)) {
		while ($v = $result->fetch(PDO::FETCH_ASSOC)) {
			$vals[] = $v;
		}
	}
	$result = null;
	return $vals;
}

function get_album_tracks_from_database($index, $cmd, $flag) {
	$retarr = array();
	$qstring = null;
	$cmd = ($cmd === null) ? 'add' : $cmd;
	switch ($flag) {
		// Really need to make these flags simpler, but it does work.
		case "b":
			// b - tracks from search results
			$action = "SELECT";
			$sflag = "AND isSearchResult > 0";
			$rflag = "";
			break;

		case "r":
			// r - only tracks with ratings
			$action = "SELECT";
			$sflag = "AND isSearchResult < 2";
			$rflag = " JOIN Ratingtable USING (TTindex)";
			break;

		case "t":
			// t - only tracks with tags
			$action = "SELECT DISTINCT";
			$sflag = "AND isSearchResult < 2";
			$rflag = " JOIN TagListtable USING (TTindex)";
			break;

		case "y":
			// y = only tracks with tags and ratings
			$action = "SELECT DISTINCT";
			$sflag = "AND isSearchResult < 2";
			$rflag = " JOIN Ratingtable USING (TTindex)";
			$rflag .= " JOIN TagListtable USING (TTindex)";
			break;

		case "u":
			// u - only tracks with tags or ratings
			$qstring = "SELECT Uri, Disc, Trackno FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Albumindex = '".$index."' AND Uri IS NOT NULL AND Hidden = 0 AND isSearchResult <2 UNION SELECT Uri, Disc, TrackNo FROM Tracktable JOIN TagListtable USING (TTindex) WHERE Albumindex = '".$index."' AND Uri IS NOT NULL AND Hidden = 0 AND isSearchResult <2 ORDER BY Disc, TrackNo;";
			break;

		default:
			// anything else - tracks from collection
			$action = "SELECT";
			$sflag = "AND isSearchResult < 2";
			$rflag = "";
			break;
	}
	debuglog("Getting Album Tracks for Albumindex ".$index,"MYSQL",7);
	if ($qstring === null) {
		$qstring = $action." Uri FROM Tracktable".$rflag." WHERE Albumindex = '".$index."' AND Uri IS NOT NULL AND Hidden = 0 ".$sflag." ORDER BY Disc, TrackNo";
	}
	if ($result = generic_sql_query($qstring)) {
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			$retarr[] = $cmd.' "'.$obj->Uri.'"';
		}
	}
	$result = null;
	return $retarr;
}

function get_artist_tracks_from_database($index, $cmd, $flag) {
	global $prefs;
	$retarr = array();
	debuglog("Getting Tracks for AlbumArtist ".$index,"MYSQL",7);
	$qstring = "SELECT Albumindex, Artistname FROM Albumtable JOIN Artisttable ON
		(Albumtable.AlbumArtistindex = Artisttable.Artistindex) WHERE AlbumArtistindex = ".$index." ORDER BY";
	if ($prefs['sortbydate']) {
		if ($prefs['notvabydate']) {
			$qstring .= " CASE WHEN Artistname = 'Various Artists' THEN LOWER(Albumname) ELSE Year END,";
		} else {
			$qstring .= ' Year,';
		}
	}
	$qstring .= ' LOWER(Albumname)';
	if ($result = generic_sql_query($qstring)) {
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			$retarr = array_merge($retarr, get_album_tracks_from_database($obj->Albumindex, $cmd, $flag));
		}
	}
	$result = null;
	return $retarr;
}

function do_tracks_from_database($why, $what, $whom, $fragment = false) {
	// This function can accept multiple album ids ($whom can be an array)
	// in which case it will combine them all into one 'virtual album' - see browse_album()
	$who = getArray($whom);
	$wdb = implode($who, ', ');
    debuglog("Generating tracks for album(s) ".$wdb." from database","DUMPALBUMS",7);
	if ($fragment) {
		ob_start();
	}
	$t = ($why == "b") ? "AND isSearchResult > 0" : "AND isSearchResult < 2";
	if ($result = generic_sql_query(
		// This looks like a wierd way of doing it but the obvious way doesn't work with mysql
		// due to table alises being used.
		"SELECT
			".SQL_TAG_CONCAT." AS tags,
			r.Rating AS rating,
			tr.TTindex AS ttid,
			tr.Title AS title,
			tr.TrackNo AS trackno,
			tr.Duration AS time,
			tr.LastModified AS lm,
			tr.Disc AS disc,
			tr.Uri AS uri,
			ta.Artistname AS artist,
			tr.Artistindex AS trackartistindex,
			al.AlbumArtistindex AS albumartistindex
		FROM
			(Tracktable AS tr, Artisttable AS ta, Albumtable AS al)
			LEFT JOIN TagListtable AS tl ON tr.TTindex = tl.TTindex
			LEFT JOIN Tagtable AS t USING (Tagindex)
			LEFT JOIN Ratingtable AS r ON tr.TTindex = r.TTindex
			WHERE (".implode(' OR ', array_map('do_fiddle', $who)).")
				AND uri IS NOT NULL
				AND tr.Hidden = 0
				".$t."
				AND tr.Artistindex = ta.Artistindex
				AND al.Albumindex = tr.Albumindex
		GROUP BY tr.TTindex
		ORDER BY CASE WHEN title LIKE 'Album: %' THEN 1 ELSE 2 END, disc, trackno"
	)) {
		$trackarr = $result->fetchAll(PDO::FETCH_ASSOC);
		$numtracks = count($trackarr);
		$numdiscs = get_highest_disc($trackarr);
		$currdisc = -1;
		foreach ($trackarr as $arr) {
			if ($numdiscs > 1 && $arr['disc'] != $currdisc && $arr['disc'] > 0) {
                $currdisc = $arr['disc'];
                print '<div class="clickable clickdisc draggable discnumber indent">'.ucfirst(strtolower(get_int_text("musicbrainz_disc"))).' '.$currdisc.'</div>';
			}
			if ($currdisc > 0) {
                $arr['discclass'] = ' disc'.$currdisc;
			} else {
                $arr['discclass'] = '';
			}
			$arr['numtracks'] = $numtracks;
			$tracktype = albumTrack($arr);
		}
		if ($tracktype == 1) {
			debuglog("Album ".$wdb." has no tracks, just an artist link","SQL",6);
			print '<input type="hidden" class="expandartist"/>';
		} else if ($tracktype == 2) {
			debuglog("Album ".$wdb." has no tracks, just an album link","SQL",6);
			print '<input type="hidden" class="expandalbum"/>';
		}
	} else {
        print '<h3>'.get_int_text("label_general_error").'</h3>';
	}
	$result = null;
	if ($fragment) {
		$s = ob_get_contents();
		ob_end_clean();
		return $s;
	}
}

function get_highest_disc($tracks) {
	$n = 1;
	foreach ($tracks as $t) {
		if ($t['disc'] > $n) {
			$n = $t['disc'];
		}
	}
	return $n;
}

function do_fiddle($a) {
	return 'tr.Albumindex = '.$a;
}

function get_artist_charts() {
	$artists = array();
	$query = "SELECT SUM(Playcount) AS playtot, Artistindex, Artistname FROM
		 Playcounttable JOIN Tracktable USING (TTindex) JOIN Artisttable USING (Artistindex)";
	$query .= " GROUP BY Artistindex ORDER BY playtot DESC LIMIT 40";
	if ($result = generic_sql_query($query)) {
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			$artists[] = array( 'label_artist' => $obj->Artistname, 'soundcloud_plays' => $obj->playtot);
		}
	}
	$result = null;
	return $artists;
}

function get_album_charts() {
	$albums = array();
	$query = "SELECT SUM(Playcount) AS playtot, Albumname, Artistname, AlbumUri, Albumindex
		 FROM Playcounttable JOIN Tracktable USING (TTindex) JOIN Albumtable USING (Albumindex)
		 JOIN Artisttable ON Albumtable.AlbumArtistindex = Artisttable.Artistindex";
	$query .= " GROUP BY Albumindex ORDER BY playtot DESC LIMIT 40";
	if ($result = generic_sql_query($query)) {
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			$albums[] = array( 'label_artist' => $obj->Artistname,
				'label_album' => $obj->Albumname,
				'soundcloud_plays' => $obj->playtot, 'uri' => $obj->AlbumUri);
		}
	}
	$result = null;
	return $albums;
}

function get_track_charts($limit = 40) {
	$tracks = array();
	$query = "SELECT Title, Playcount, Artistname, Uri FROM Tracktable JOIN Playcounttable USING (TTIndex)
		JOIN Artisttable USING (Artistindex)";
	$query .= " ORDER BY Playcount DESC LIMIT ".$limit;
	if ($result = generic_sql_query($query)) {
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			$tracks[] = array( 'label_artist' => $obj->Artistname,
				'label_track' => $obj->Title,
				'soundcloud_plays' => $obj->Playcount, 'uri' => $obj->Uri);
		}
	}
	$result = null;
	return $tracks;
}

function find_justadded_artists() {
	$retval = array();
	if ($result = generic_sql_query("SELECT DISTINCT AlbumArtistindex FROM Albumtable JOIN Tracktable USING (Albumindex) WHERE justAdded = 1")) {
		$retval = $result->fetchAll(PDO::FETCH_COLUMN, 'AlbumArtistindex');
	}
	$result = null;
	return $retval;
}

function find_justadded_albums() {
	$retval = array();
	if ($result = generic_sql_query("SELECT DISTINCT Albumindex FROM Tracktable WHERE justAdded = 1")) {
		$retval =  $result->fetchAll(PDO::FETCH_COLUMN, 'Albumindex');
	}
	$result = null;
	return $retval;
}

function get_user_radio_streams() {
	$retval = array();
	if ($result = generic_sql_query("SELECT * FROM RadioStationtable WHERE IsFave = 1 ORDER BY Number, StationName")) {
		$retval = $result->fetchAll(PDO::FETCH_ASSOC);
	}
	$result = null;
	return $retval;
}

function remove_user_radio_stream($x) {
    generic_sql_query("UPDATE RadioStationtable SET IsFave = 0, Number = 65535 WHERE Stationindex = ".$x);
}

function save_radio_order($order) {
    foreach ($order as $i => $o) {
        generic_sql_query("UPDATE RadioStationtable SET Number = ".$i." WHERE Stationindex = ".$o);
    }
}

function check_radio_station($playlisturl, $stationname, $image) {
	global $mysqlc;
	$index = null;
	if ($stmt = sql_prepare_query("SELECT Stationindex FROM RadioStationtable WHERE PlaylistUrl = ?", $playlisturl)) {
		$obj = $stmt->fetch(PDO::FETCH_OBJ);
		$index = $obj ? $obj->Stationindex : null;
		if ($index) {
			debuglog("Found radio station with index ".$index,"RADIO");
		} else {
			debuglog("Adding New Radio Station : ","RADIO");
			debuglog("  Name  : ".$stationname,"RADIO");
			debuglog("  Image : ".$image,"RADIO");
			debuglog("  URL   : ".$playlisturl,"RADIO");
			if ($stmt = sql_prepare_query("INSERT INTO RadioStationtable (IsFave, StationName, PlaylistUrl, Image) VALUES (?, ?, ?, ?)",
				0, trim($stationname), trim($playlisturl), trim($image))) {
				$index = $mysqlc->lastInsertId();
				debuglog("Created new radio station with index ".$index,"RADIO");
			}
		}
	}
	$stmt = null;
	return $index;
}

function check_radio_tracks($stationid, $tracks) {
	generic_sql_query("DELETE FROM RadioTracktable WHERE Stationindex = ".$stationid);
	foreach ($tracks as $track) {
		debuglog("  Adding New Track ".$track['TrackUri'],"RADIO");
		sql_prepare_query("INSERT INTO RadioTracktable (Stationindex, TrackUri, PrettyStream) VALUES (?, ?, ?)",
			$stationid, trim($track['TrackUri']), trim($track['PrettyStream']));
	}
}

function add_fave_station($info) {
	if (array_key_exists('streamid', $info) && $info['streamid']) {
		debuglog("Updating StationIndex ".$info['streamid']." to be fave","RADIO");
		generic_sql_query("UPDATE RadioStationtable SET IsFave = 1 WHERE Stationindex = ".$info['streamid']);
		return true;
	}
	$stationindex = check_radio_station($info['location'],$info['album'],$info['image']);
	check_radio_tracks($stationindex, array('TrackUri' => $info['location'], 'PrettyStream' => $info['stream']));
	generic_sql_query("UPDATE RadioStationtable SET IsFave = 1 WHERE Stationindex = ".$stationindex);
}

function update_radio_station_name($info) {
	debuglog("Updating Stationindex ".$info['streamid'].' with new name '.$info['name']);
	sql_prepare_query("UPDATE RadioStationtable SET StationName = ? WHERE Stationindex = ?",$info['name'],$info['streamid']);
}

function update_stream_image($stream, $image) {
	$streamid = stream_index_from_key($stream);
	sql_prepare_query("UPDATE RadioStationtable SET Image = ? WHERE Stationindex = ?",$image,$streamid);
}

//
// Database Global Stats and Version Control
//

function update_track_stats() {
	debuglog("Updating Track Stats","MYSQL",7);
	if ($result = generic_sql_query(
		"SELECT COUNT(*) AS NumArtists FROM (SELECT DISTINCT AlbumArtistIndex FROM Albumtable
		INNER JOIN Tracktable USING (Albumindex) WHERE Albumname IS NOT NULL AND Uri IS NOT NULL
		AND Hidden = 0 AND isSearchResult < 2) AS t")) {
		$ac = 0;
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			$ac = $obj->NumArtists;
		}
		update_stat('ArtistCount',$ac);
	}

	if ($result = generic_sql_query(
		"SELECT COUNT(*) AS NumAlbums FROM (SELECT DISTINCT Albumindex FROM Albumtable
		INNER JOIN Tracktable USING (Albumindex) WHERE Albumname IS NOT NULL AND Uri IS NOT NULL
		AND Hidden = 0 AND isSearchResult < 2) AS t")) {
		$ac = 0;
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			$ac = $obj->NumAlbums;
		}
		update_stat('AlbumCount',$ac);
	}

	if ($result = generic_sql_query(
		"SELECT COUNT(*) AS NumTracks FROM Tracktable WHERE Uri IS NOT NULL AND Hidden=0 AND isSearchResult < 2")) {
		$ac = 0;
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			$ac = $obj->NumTracks;
		}
		update_stat('TrackCount',$ac);
	}

	if ($result = generic_sql_query(
		"SELECT SUM(Duration) AS TotalTime FROM Tracktable WHERE Uri IS NOT NULL AND Hidden=0 AND isSearchResult < 2")) {
		$ac = 0;
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			$ac = $obj->TotalTime;
		}
		update_stat('TotalTime',$ac);
	}
	debuglog("Track Stats Updated","MYSQL",9);
}

function update_stat($item, $value) {
	generic_sql_query("UPDATE Statstable SET Value='".$value."' WHERE Item='".$item."'");
}

function get_stat($item) {
	return simple_query('Value', 'Statstable', 'Item', $item, 0);
}

function dumpAlbums($which) {

    global $divtype, $prefs;

    $a = preg_match('/(a|b)(.*?)(\d+|root)/', $which, $matches);
	if (!$a) {
		print '<h3>'.get_int_text("label_general_error").'</h3>';
		debuglog('Artist dump failed - regexp failed to match '.$which,"DUMPALBUMS",3);
		return false;
	}
    $why = $matches[1];
    $what = $matches[2];
    $who = $matches[3];
    $count = null;

    switch ($who) {
    	case 'root':
	    	if ($why == 'a') {
	    		collectionStats();
	    	} else {
	    		searchStats();
	    	}
        	$divtype = "album1";
        	switch ($what) {
        		case 'artist':
        			$count = do_artists_from_database($why, $what, $who);
        			break;

        		case 'album':
        		case 'albumbyartist':
        			$count = do_albums_from_database($why, 'album', $who, false, false, false);
        			break;

        	}
	        if ($count == 0) {
	        	if ($why == 'a') {
	        		emptyCollectionDisplay();
	        	} else {
		        	emptySearchDisplay();
	        	}
	        }
	        break;

	    default:
	    	switch ($what) {
	    		case 'artist':
		    		do_albums_from_database($why, 'album', $who, false, false, false);
		    		break;
		    	case 'album':
		    		do_tracks_from_database($why, $what, $who, false);
		    		break;
	    	}
    }

}

function collectionStats() {
    print '<div id="fothergill">';
    print alistheader(get_stat('ArtistCount'), get_stat('AlbumCount'), get_stat('TrackCount'), format_time(get_stat('TotalTime')));
    print '</div>';
}

function searchStats() {
    $numartists = 0;
    $numalbums = 0;
    $numtracks = 0;
    $numtime = 0;
	if ($result = generic_sql_query(
		"SELECT COUNT(*) AS NumArtists FROM (SELECT DISTINCT AlbumArtistIndex FROM Albumtable
		INNER JOIN Tracktable USING (Albumindex) WHERE Albumname IS NOT NULL AND Uri IS NOT
		NULL AND Hidden = 0 AND isSearchResult > 0) AS t")) {
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			$numartists = $obj->NumArtists;
		}
	}

	if ($result = generic_sql_query(
		"SELECT COUNT(*) AS NumAlbums FROM (SELECT DISTINCT Albumindex FROM Albumtable
		INNER JOIN Tracktable USING (Albumindex) WHERE Albumname IS NOT NULL AND Uri IS NOT
		NULL AND Hidden = 0 AND isSearchResult > 0) AS t")) {
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			$numalbums = $obj->NumAlbums;
		}
	}

	if ($result = generic_sql_query(
		"SELECT COUNT(*) AS NumTracks FROM Tracktable WHERE Uri IS NOT NULL
		AND Hidden=0 AND isSearchResult > 0")) {
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			$numtracks = $obj->NumTracks;
		}
	}

	if ($result = generic_sql_query(
		"SELECT SUM(Duration) AS TotalTime FROM Tracktable WHERE Uri IS NOT NULL AND
		Hidden=0 AND isSearchResult > 0")) {
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			$numtime = $obj->TotalTime;
		}
	}
	$result = null;
    print alistheader($numartists, $numalbums, $numtracks, format_time($numtime));

}

function getItemsToAdd($which, $cmd = null) {
    $a = preg_match('/(a|b|r|t|y|u)(.*?)(\d+|root)/', $which, $matches);
    if (!$a) {
        debuglog('Regexp failed to match '.$which,"GETITEMSTOADD",3);
        return array();
    }
    $why = $matches[1];
    $what = $matches[2];
    $who = $matches[3];
    switch ($what) {
    	case "artist":
    		return get_artist_tracks_from_database($who, $cmd, $why);
    		break;

    	case "album":
    		return get_album_tracks_from_database($who, $cmd, $why);
    		break;

    	default:
	        debuglog('Unknown type '.$which,"GETITEMSTOADD",3);
	        return array();
	        break;
    }
}

function playAlbumFromTrack($uri) {
	$albumindex = null;
	$trackno = null;
	$disc = null;
	$issearchresult = 0;
	$retval = array('add "'.$uri.'"');
	if ($stmt = sql_prepare_query("SELECT Albumindex, TrackNo, Disc, isSearchResult FROM Tracktable WHERE Uri = ?", $uri)) {
		while ($obj = $stmt->fetch(PDO::FETCH_OBJ)) {
			$albumindex = $obj->Albumindex;
			$trackno = $obj->TrackNo;
			$disc = $obj->Disc;
			$issearchresult = $obj->isSearchResult;
		}
		if ($issearchresult == 0) {
			$newsr = 2;
		} else {
			$newsr = 3;
		}
		if ($albumindex !== null) {
			$retval = array();
			if ($stmt = sql_prepare_query("SELECT Uri FROM Tracktable WHERE Albumindex = ? AND ((Disc = ? AND TrackNo >= ?) OR (Disc > ?)) AND Hidden = 0 AND isSearchResult < ? ORDER BY Disc, TrackNo", $albumindex, $disc, $trackno, $disc, $newsr)) {
				while ($obj = $stmt->fetch(PDO::FETCH_OBJ)) {
					$retval[] = 'add "'.$obj->Uri.'"';
				}
			}
		}
	}
	$stmt = null;
	return $retval;
}

function check_url_against_database($url, $itags, $rating) {
	global $mysqlc;
	if ($mysqlc === null) {
		connect_to_database();
	}

	$qstring = "SELECT t.TTindex FROM Tracktable AS t ";
	$tags = array();
	if ($itags !== null) {
		$qstring .= "JOIN (SELECT DISTINCT TTindex FROM TagListtable JOIN Tagtable AS tag USING (Tagindex) WHERE";
		$tagterms = array();
		foreach ($itags as $tag) {
			$tags[] = trim($tag);
			$tagterms[] = " tag.Name LIKE ?";
		}
		$qstring .= implode(" OR",$tagterms);
		$qstring .=") AS j ON j.TTindex = t.TTindex ";
	}
	if ($rating !== null) {
		$qstring .= "JOIN (SELECT * FROM Ratingtable WHERE Rating >= ".$rating.") AS rat ON rat.TTindex = t.TTindex ";
	}
	$tags[] = $url;
	$qstring .= "WHERE t.Uri = ?";
	if ($stmt = sql_prepare_query_later($qstring)) {
		if ($stmt->execute($tags)) {
			// rowCount() doesn't work for SELECT with SQLite
			while($obj = $stmt->fetch(PDO::FETCH_OBJ)) {
				return true;
			}
		} else {
			show_sql_error();
		}
	} else {
		show_sql_error();
	}
	$stmt = null;
	return false;
}

function cleanSearchTables() {
	// Clean up the database tables before performing a new search or updating the collection

	debuglog("Cleaning Search Results","MYSQL",6);
	// Any track that was previously hidden needs to be re-hidden
	generic_sql_query("UPDATE Tracktable SET Hidden = 1, isSearchResult = 0 WHERE isSearchResult = 3");

	// Any track that was previously a '2' (added to database as search result) but now
	// has a playcount needs to become a zero and be hidden.
	hide_played_tracks();

	// remove any remaining '2's
	generic_sql_query("DELETE FROM Tracktable WHERE isSearchResult = 2");

	// Set '1's back to '0's
	generic_sql_query("UPDATE Tracktable SET isSearchResult = 0 WHERE isSearchResult = 1");

	// This may leave some orphaned albums and artists
	remove_cruft();

	//
	// remove_cruft creates some temporary tables and we need to remove them because
	// remove cruft will be called again later on if we're doing a collection update.
	// Sadly, DROP TABLE runs into locking problems, at least with SQLite, so instead
	// we close the DB connection and start again.
	// So this function must be called BEFORE prepareCollectionUpdate, as that creates
	// temporary tables of its own.
	//

	close_database();
	sleep(1);
	connect_to_database();

}

//
// Stuff to do with creating the database from a music collection (collection.php)
//

function prepareCollectionUpdate() {
	create_foundtracks();
	prepare_findtracks();
	open_transaction();
}

function prepare_findtracks() {
	global $find_track, $update_track;
	if ($find_track = sql_prepare_query_later(
		"SELECT TTindex, Disc, LastModified, Hidden, isSearchResult, Uri FROM Tracktable WHERE Title=? AND ((Albumindex=? AND TrackNo=? AND Disc=?) OR (Artistindex=? AND Uri IS NULL))")) {
	} else {
		show_sql_error();
        exit(1);
	}

	if ($update_track = sql_prepare_query_later(
		"UPDATE Tracktable SET Trackno=?, Duration=?, Disc=?, LastModified=?, Uri=?, Albumindex=?, isSearchResult=?, Hidden=0, justAdded=1 WHERE TTindex=?")) {
	} else {
		show_sql_error();
        exit(1);
	}
}

function remove_findtracks() {
	global $find_track, $update_track;
	$find_track = null;
	$update_track = null;
}

function tidy_database() {
    // Find tracks that have been removed
    debuglog("Starting Cruft Removal","TIMINGS",4);
    $now = time();
    debuglog("Finding tracks that have been deleted","MYSQL",7);
    generic_sql_query("DELETE FROM Tracktable WHERE LastModified IS NOT NULL AND Hidden = 0 AND justAdded = 0");
    remove_cruft();
	update_stat('ListVersion',ROMPR_COLLECTION_VERSION);
	update_track_stats();
	$dur = format_time(time() - $now);
	debuglog("Cruft Removal Took ".$dur,"TIMINGS",4);
    debuglog("~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~","TIMINGS",4);
	close_transaction();
}

function create_foundtracks() {
	// The order of these is VERY IMPORTANT!
	generic_sql_query("UPDATE Tracktable SET justAdded = 0");
	generic_sql_query("UPDATE Albumtable SET justUpdated = 0");
}

function remove_cruft() {
    debuglog("Removing orphaned albums","MYSQL",6);
    // NOTE - the Albumindex IS NOT NULL is essential - if any albumindex is NULL the entire () expression returns NULL
    generic_sql_query("DELETE FROM Albumtable WHERE Albumindex NOT IN (SELECT DISTINCT Albumindex FROM Tracktable WHERE Albumindex IS NOT NULL)");

    debuglog("Removing orphaned artists","MYSQL",6);
    delete_orphaned_artists();

    debuglog("Tidying Metadata","MYSQL",6);
    generic_sql_query("DELETE FROM Ratingtable WHERE Rating = '0'");
	generic_sql_query("DELETE FROM Ratingtable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable WHERE Hidden = 0)");
	generic_sql_query("DELETE FROM TagListtable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable WHERE Hidden = 0)");
	generic_sql_query("DELETE FROM Tagtable WHERE Tagindex NOT IN (SELECT Tagindex FROM TagListtable)");
	generic_sql_query("DELETE FROM Playcounttable WHERE Playcount = '0'");
	generic_sql_query("DELETE FROM Playcounttable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable)");
}

function do_track_by_track($trackobject) {

	// Tracks must have disc and albumartist tags to be handled by this method.
	// Loads of static variables to speed things up - we don't have to look things up every time.

	static $current_albumartist = null;
	static $current_album = null;
	static $current_domain = null;
	static $current_albumlink= null;
	static $albumobj = null;

	static $albumindex = null;
	static $albumartistindex = null;

	$artistname = $trackobject->get_sort_artist();

	if ($current_albumartist != $artistname) {
		$albumartistindex = check_artist($artistname);
	}
    if ($albumartistindex == null) {
    	debuglog("ERROR! Checked artist ".$artistname." and index is still null!","MYSQL_TBT",1);
        return false;
    }

    if ($current_albumartist != $artistname || $current_album != $trackobject->tags['Album'] ||
    		$current_domain != $trackobject->tags['domain'] ||
    		($trackobject->tags['X-AlbumUri'] != null && $trackobject->tags['X-AlbumUri'] != $current_albumlink)) {

    	$albumobj = new album($trackobject);
    	$params = array(
            'album' => $albumobj->name,
            'albumai' => $albumartistindex,
            'albumuri' => $albumobj->uri,
            'image' => $albumobj->getImage('small'),
            'date' => $albumobj->getDate(),
            'searched' => "0",
            'imagekey' => $albumobj->getKey(),
            'ambid' => $albumobj->musicbrainz_albumid,
            'domain' => $albumobj->domain);
        $albumindex = check_album($params, false);

        if ($albumindex == null) {
        	debuglog("ERROR! Album index for ".$albumobj->name." is still null!","MYSQL_TBT",1);
    		return false;
        }
    } else {
    	$albumobj->newTrack($trackobject, true);
    }

    $current_albumartist = $artistname;
    $current_album = $albumobj->name;
    $current_domain = $albumobj->domain;
    $current_albumlink = $albumobj->uri;

	foreach ($albumobj->tracks as $trackobj) {
		// The album we've just created must only have one track, but this makes sure we use the track object
		// that is part of the album. This MAY be important due to various assumptions
		check_and_update_track($trackobj, $albumindex, $albumartistindex, $artistname);
	}

}

function check_and_update_track($trackobj, $albumindex, $artistindex, $artistname) {
	global $find_track, $update_track, $numdone, $prefs, $doing_search;
	static $current_trackartist = null;
	static $trackartistindex = null;
    $ttid = null;
    $lastmodified = null;
    $hidden = 0;
    $disc = 0;
    $uri = null;
    $issearchresult = 0;

    // Why are we not checking by URI? That should be unique, right?
    // Well, er. no. They're not.
    // Especially Spotify returns the same URI multiple times if it's in mutliple playlists
    // We CANNOT HANDLE that. Nor do we want to.

    // The other advantage of this is that we can put an INDEX on Albumindex, TrackNo, and Title,
    // which we can't do with Uri cos it's too long - this speeds the whole process up by a factor
    // of about 32 (9 minutes when checking by URI vs 15 seconds this way, on my collection)
    // Also, URIs might change if the user moves his music collection.

	if ($prefs['collection_type'] == "sqlite") {
		// Lord knows why, but we have to re-prepare these every single bloody time!
		prepare_findtracks();
	}

    if ($find_track->execute(array($trackobj->tags['Title'], $albumindex, $trackobj->tags['Track'],$trackobj->tags['Disc'],$artistindex))) {
    	while ($obj = $find_track->fetch(PDO::FETCH_OBJ)) {
    		$ttid = $obj->TTindex;
    		$lastmodified = $obj->LastModified;
    		$hidden = $obj->Hidden;
    		$disc = $obj->Disc;
    		$issearchresult = $obj->isSearchResult;
    		$uri = $obj->Uri;
    		break;
    	}
    } else {
    	show_sql_error();
    	return false;
    }

    // NOTE: It is imperative that the search results have been tidied up -
    // i.e. there are no 1s or 2s in the database before we do a collection update

    // When doing a search, we MUST NOT change lastmodified of any track, because this will cause
    // user-added tracks to get a lastmodified date, and lastmodified == NULL
    // is how we detect user-added tracks and prevent them being deleted on collection updates

    if ($ttid) {
    	if ((!$doing_search && $trackobj->tags['Last-Modified'] != $lastmodified) ||
    		($doing_search && $issearchresult == 0) ||
    		($trackobj->tags['Disc'] != $disc && $trackobj->tags['Disc'] !== '') ||
    		$hidden != 0 ||
    		$trackobj->tags['file'] != $uri) {

    		//
    		// Lots of debug output
    		//

    		if ($prefs['debug_enabled'] > 6) {
		    	debuglog("  Updating track with ttid $ttid because :","MYSQL",7);
		    	if (!$doing_search && $lastmodified === null) debuglog("    LastModified is not set in the database","MYSQL",7);
		    	if (!$doing_search && $trackobj->tags['Last-Modified'] === null) debuglog("    TrackObj LastModified is NULL too!","MYSQL",7);
		    	if (!$doing_search && $lastmodified != $trackobj->tags['Last-Modified']) debuglog("    LastModified has changed: We have ".$lastmodified." but track has ".$trackobj->tags['Last-Modified'],"MYSQL",7);
		    	if ($disc != $trackobj->tags['Disc']) debuglog("    Disc Number has changed: We have ".$disc." but track has ".$trackobj->tags['Disc'],"MYSQL",7);
		    	if ($hidden != 0) debuglog("    It is hidden","MYSQL",7);
		    	if ($trackobj->tags['file'] != $uri) {
		    		debuglog("    Uri has changed from : ".$uri,"MYSQL",7);
		    		debuglog("                      to : ".$trackobj->tags['file'],7);
		    	}
		    }

		    //
		    // End of debug output
		    //

	    	$newsearchresult = 0;
	    	if ($doing_search) {
	    		// Sometimes spotify search returns the same track with multiple URIs. This means we update the track
	    		// when we get the second one and isSearchResult gets set to zero unless we do this.
	    		$newsearchresult = $issearchresult;
	    	}
	    	$newlastmodified = $trackobj->tags['Last-Modified'];
	    	if ($issearchresult == 0 && $doing_search) {
	    		$newsearchresult = ($hidden != 0) ? 3 : 1;
	    		debuglog("    It needs to be marked as a search result : Value ".$newsearchresult,"MYSQL",7);
	    		$newlastmodified = $lastmodified;
	    	}

			if ($update_track->execute(array($trackobj->tags['Track'], $trackobj->tags['Time'], $trackobj->tags['Disc'],
					$newlastmodified, $trackobj->tags['file'], $albumindex,	$newsearchresult, $ttid))) {
				$numdone++;
			} else {
				show_sql_error();
			}
		} else {
			generic_sql_query("UPDATE Tracktable SET justAdded = 1 WHERE TTindex = ".$ttid);
		}
    } else {
    	$a = $trackobj->get_artist_string();
    	if ($a != $current_trackartist || $trackartistindex == null) {
	        if ($artistname != $a && $a != null) {
	            $trackartistindex = check_artist($a);
	        } else {
	            $trackartistindex = $artistindex;
	        }
	    }
        if ($trackartistindex == null) {
        	debuglog("ERROR! Trackartistindex is still null!","MYSQL_TBT",1);
            return false;
        }

        $current_trackartist = $a;
        $sflag = ($doing_search) ? 2 : 0;
        $params = array(
            'title' => $trackobj->tags['Title'],
            'artist' => null,
            'trackno' => $trackobj->tags['Track'],
            'duration' => $trackobj->tags['Time'],
            'albumartist' => null,
            'albumuri' => null,
            'image' => null,
            'album' => null,
            'date' => null,
            'uri' => $trackobj->tags['file'],
            'trackai' => $trackartistindex,
            'albumai' => $artistindex,
            'albumindex' => $albumindex,
            'searched' => null,
            'imagekey' => null,
            'lastmodified' => $trackobj->tags['Last-Modified'],
            'disc' => $trackobj->tags['Disc'],
            'ambid' => null,
            'domain' => null,
            'hidden' => 0,
            'searchflag' => $sflag
        );
        $ttid = create_new_track($params);
        $numdone++;
	}
	check_transaction();
    if ($ttid == null) {
    	debuglog("ERROR! No ttid for track ".$trackobj->tags['file'],"MYSQL",1);
    	debuglog("Parameters were : ","MYSQL",3);
    	debuglog("  Title            : ".$trackobj->tags['Title'],"MYSQL",3);
    	debuglog("  Track            : ".$trackobj->tags['Track'],"MYSQL",3);
    	debuglog("  Time             : ".$trackobj->tags['Time'],"MYSQL",3);
    	debuglog("  file             : ".$trackobj->tags['file'],"MYSQL",3);
    	debuglog("  trackartistindex : ".$trackartistindex,"MYSQL",3);
    	debuglog("  artistindex      : ".$artistindex,"MYSQL",3);
    	debuglog("  albumindex       : ".$albumindex,"MYSQL",3);
    	debuglog("  Last-Modified    : ".$trackobj->tags['Last-Modified'],"MYSQL",3);
    	debuglog("  Disc             : ".$trackobj->tags['Disc'],"MYSQL",3);
    	debuglog("  sflag            : ".$sflag,"MYSQL",3);
    }
}

?>