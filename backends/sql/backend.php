<?php

include ("backends/sql/connect.php");
require_once ("skins/".$skin."/ui_elements.php");
connect_to_database();
$collection_type = get_collection_type();
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
// Tracktable.isSearchResult is set to:
//		1 on any existing track that comes up in the search
//		2 for any track that comes up the search and has to be added - i.e it's not part of the main collection.
//		3 for any hidden track that comes up in search so it can be re-hidden later.
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

	$data['sourceindex'] = null;
	if ($data['uri'] === null && array_key_exists('streamuri', $data) && $data['streamuri'] !== null) {
		$data['sourceindex'] = check_radio_source($data);
	}

	if (sql_prepare_query(true, null, null, null,
		"INSERT INTO
			Tracktable
			(Title, Albumindex, Trackno, Duration, Artistindex, Disc, Uri, LastModified, Hidden, isSearchResult, Sourceindex)
			VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
		$data['title'], $data['albumindex'], $data['trackno'], $data['duration'], $data['trackai'],
		$data['disc'], $data['uri'], $data['lastmodified'], $data['hidden'], $data['searchflag'], $data['sourceindex']))
	{
		return $mysqlc->lastInsertId();
	}
	return null;
}

function check_radio_source($data) {
	global $mysqlc;
	$index = simple_query('Sourceindex', 'WishlistSourcetable', 'SourceUri', $data['streamuri'], null);
	if ($index === null) {
		debuglog("Creating Wishlist Source ".$data['streamname'],"SQL");
		if (sql_prepare_query(true, null, null, null,
		"INSERT INTO WishlistSourcetable (SourceName, SourceImage, SourceUri) VALUES (?, ?, ?)",
		$data['streamname'], $data['streamimage'], $data['streamuri']))
		{
			$index = $mysqlc->lastInsertId();
		}
	}
	return $index;
}

function check_artist($artist) {

	// check_artist:
	//		Checks for the existence of an artist by name in the Artisttable and creates it if necessary
	//		Returns: Artistindex

	$index = sql_prepare_query(false, null, 'Artistindex', null, "SELECT Artistindex FROM Artisttable WHERE LOWER(Artistname) = LOWER(?)", $artist);
    if ($index === null) {
		$index = create_new_artist($artist);
	}
	return $index;
}

function create_new_artist($artist) {

	// create_new_artist
	//		Creates a new artist
	//		Returns: Artistindex

	global $mysqlc;
	$retval = null;
	if (sql_prepare_query(true, null, null, null, "INSERT INTO Artisttable (Artistname) VALUES (?)", $artist)) {
		$retval = $mysqlc->lastInsertId();
		debuglog("Created artist ".$artist." with Artistindex ".$retval,"MYSQL",7);
	}
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

	global $prefs, $trackbytrack, $doing_search;
	$index = null;
	$year = null;
	$img = null;
	$mbid = null;
	$result = sql_prepare_query(false, PDO::FETCH_OBJ, null, null,
		"SELECT
			Albumindex,
			Year,
			Image,
			AlbumUri,
			mbid
		FROM
			Albumtable
		WHERE
			LOWER(Albumname) = LOWER(?)
			AND AlbumArtistindex = ?
			AND Domain = ?", $data['album'], $data['albumai'], $data['domain']);
	$obj = array_shift($result);

	if ($prefs['preferlocalfiles'] && $trackbytrack && !$doing_search && $data['domain'] == 'local' && !$obj) {
		// Does the album exist on a different, non-local, domain? The checks above ensure we only do this
		// during a collection update
		$result = sql_prepare_query(false, PDO::FETCH_OBJ, null, null,
			"SELECT
				Albumindex,
				Year,
				Image,
				AlbumUri,
				mbid,
				Domain
			FROM
				Albumtable
			WHERE
				LOWER(Albumname) = LOWER(?)
				AND AlbumArtistindex = ?", $data['album'], $data['albumai']);
		$obj = array_shift($result);
		if ($obj) {
			debuglog("Album ".$data['album']." was found on domain ".$obj->Domain.". Changing to local","MYSQL", 7);
			$index = $obj->Albumindex;
			if (sql_prepare_query(true, null, null, null, "UPDATE Albumtable SET AlbumUri=NULL, Domain=?, justUpdated=? WHERE Albumindex=?", 'local', 1, $index)) {
				$obj->AlbumUri = null;
				debuglog("   ...Success","MYSQL",9);
			} else {
				debuglog("   Album ".$data['album']." update FAILED","MYSQL",3);
				return false;
			}
		}
	}

	if ($obj) {
		$index = $obj->Albumindex;
		$year = best_value($obj->Year, $data['date']);
		$img  = best_value($obj->Image, $data['image']);
		$uri  = best_value($obj->AlbumUri, $data['albumuri']);
		$mbid  = best_value($obj->mbid, $data['ambid']);
		if ($year != $obj->Year || $img != $obj->Image || $uri != $obj->AlbumUri || $mbid != $obj->mbid) {

			if ($prefs['debug_enabled'] > 6) {
				debuglog("Updating Details For Album ".$data['album']." (index ".$index.")" ,"MYSQL",7);
				debuglog("  Old Date  : ".$obj->Year,"MYSQL",7);
				debuglog("  New Date  : ".$year,"MYSQL",7);
				debuglog("  Old Image : ".$obj->Image,"MYSQL",7);
				debuglog("  New Image : ".$img,"MYSQL",7);
				debuglog("  Old Uri  : ".$obj->AlbumUri,"MYSQL",7);
				debuglog("  New Uri  : ".$uri,"MYSQL",7);
				debuglog("  Old MBID  : ".$obj->mbid,"MYSQL",7);
				debuglog("  New MBID  : ".$mbid,"MYSQL",7);
			}

			if (sql_prepare_query(true, null, null, null, "UPDATE Albumtable SET Year=?, Image=?, AlbumUri=?, mbid=?, justUpdated=1 WHERE Albumindex=?",$year, $img, $uri, $mbid, $index)) {
				debuglog("   ...Success","MYSQL",9);
			} else {
				debuglog("   Album ".$data['album']." update FAILED","MYSQL",3);
				return false;
			}
		}
	} else {
		$index = create_new_album($data);
	}
	return $index;
}

function create_new_album($data) {

	// create_new_album
	//		Creates an album
	//		Returns: Albumindex

	global $mysqlc;
	$retval = null;
	$im = array(
		'searched' => $data['image'] ? 1: 0,
		'image' => $data['image']
	);
	if (sql_prepare_query(true, null, null, null,
		"INSERT INTO
			Albumtable
			(Albumname, AlbumArtistindex, AlbumUri, Year, Searched, ImgKey, mbid, Domain, Image)
		VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?)",
		$data['album'], $data['albumai'], $data['albumuri'], $data['date'], $im['searched'], $data['imagekey'], $data['ambid'], $data['domain'], $im['image'])) {
		$retval = $mysqlc->lastInsertId();
		debuglog("Created Album ".$data['album']." with Albumindex ".$retval,"MYSQL",7);
	}
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

	debuglog("Removing track ".$ttid,"MYSQL",5);
	$t = time();
	$result = false;
	if (generic_sql_query("DELETE FROM Tracktable WHERE isSearchResult != 1 AND TTindex = '".$ttid."'",true)) {
		if (generic_sql_query("UPDATE Tracktable SET isSearchResult = 2 WHERE isSearchResult = 1 AND TTindex = '".$ttid."'", true)) {
			$result = true;;
		}
	}
	$to = time() - $t;
	debuglog("Removing track took ".$to." seconds","MYSQL",8);
	return $result;
}

function list_tags() {

	// list_tags
	//		Return a sorted lst of tag names. Used by the UI for creating the tag menu

	$tags = array();
	$result = generic_sql_query("SELECT Name FROM Tagtable ORDER BY LOWER(Name)");
	foreach ($result as $r) {
		$tags[] = $r['Name'];
	}
	return $tags;
}

function get_rating_headers($sortby) {

	$ratings = array();

	switch ($sortby) {

		case 'Rating':
			$ratings = generic_sql_query("SELECT Rating AS Name, COUNT(TTindex) AS NumTracks FROM Ratingtable GROUP BY Rating ORDER BY Rating");
			break;

		case 'Tag':
			$ratings = generic_sql_query('SELECT Name, COUNT(TTindex) AS NumTracks FROM Tagtable JOIN TagListtable USING (Tagindex) GROUP BY Name ORDER BY Name');
			break;

		case 'AlbumArtist':
			// It's actually Track Artist, but sod changing it now.
			$qstring = "SELECT DISTINCT Artistname AS Name, COUNT(DISTINCT TTindex) AS NumTracks
						FROM
						Artisttable AS a JOIN Tracktable AS tt USING (Artistindex)
						LEFT JOIN Ratingtable USING (TTindex)
						LEFT JOIN `TagListtable` USING (TTindex)
						LEFT JOIN Tagtable AS t USING (Tagindex)
						WHERE Hidden = 0 AND isSearchResult < 2 AND (Rating IS NOT NULL OR t.Name IS NOT NULL)
						GROUP BY Artistname
						ORDER BY ";
			$qstring .= sort_artists_by_name();
			$ratings = generic_sql_query($qstring);
			break;

		case 'Tags':
			$ratings = generic_sql_query("SELECT DISTINCT ".SQL_TAG_CONCAT." AS Name, 0 AS NumTracks FROM
											(SELECT Tagindex, TTindex FROM TagListtable ORDER BY Tagindex) AS tagorder
											JOIN Tagtable AS t USING (Tagindex)
											GROUP BY TTindex
											ORDER By Name");
			break;

		default:
			$ratings = array('INTERNAL ERROR!');
			break;

	}

	return $ratings;
}

function sortletter_mangler() {
	global $prefs;
	$qstring = '';
	if (count($prefs['nosortprefixes']) > 0) {
		$qstring .= "CASE ";
		foreach($prefs['nosortprefixes'] AS $p) {
			$phpisshitsometimes = strlen($p)+2;
			$qstring .= "WHEN LOWER(a.Artistname) LIKE '".strtolower($p).
				" %' THEN UPPER(SUBSTR(a.Artistname,".$phpisshitsometimes.",1)) ";
		}
		$qstring .= "ELSE UPPER(SUBSTR(a.Artistname,1,1)) END AS SortLetter";
	} else {
		$qstring .= "UPPER(SUBSTR(a.Artistname,1,1)) AS SortLetter";
	}
	return $qstring;
}

function get_rating_info($sortby, $value) {

	global $prefs;

	// Tuned SQL queries for each type, for speed, otherwise it's unuseable

	switch ($sortby) {
		case 'Rating':
			$qstring = "SELECT
		 		r.Rating AS Rating,
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
			$qstring .= sortletter_mangler();

			$qstring .= " FROM
					Ratingtable AS r
					JOIN Tracktable AS tr ON tr.TTindex = r.TTindex
					LEFT JOIN TagListtable AS tl ON tr.TTindex = tl.TTindex
					LEFT JOIN Tagtable AS t USING (Tagindex)
					JOIN Albumtable AS al USING (Albumindex)
					JOIN Artisttable AS a ON (tr.Artistindex = a.Artistindex)
					JOIN Artisttable AS aa ON (al.AlbumArtistindex = aa.Artistindex)
				WHERE r.Rating = ".$value." AND tr.isSearchResult < 2 AND tr.Uri IS NOT NULL";
				break;

			case 'Tag':
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
				$qstring .= sortletter_mangler();

				$qstring .= " FROM
					Tracktable AS tr
					LEFT JOIN Ratingtable AS r ON tr.TTindex = r.TTindex
					LEFT JOIN TagListtable AS tl ON tr.TTindex = tl.TTindex
					LEFT JOIN Tagtable AS t USING (Tagindex)
					JOIN Albumtable AS al USING (Albumindex)
					JOIN Artisttable AS a ON (tr.Artistindex = a.Artistindex)
					JOIN Artisttable AS aa ON (al.AlbumArtistindex = aa.Artistindex)
				WHERE tr.isSearchResult < 2  AND tr.Uri IS NOT NULL AND tr.TTindex IN (SELECT TTindex FROM TagListtable JOIN Tagtable USING (Tagindex) WHERE Name = '".$value."')";
				break;

			case 'AlbumArtist':
				// It's actually Track Artist, but sod changing it now.
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
			 		al.Image ";

			 	$qstring .= " FROM
			 		Tracktable AS tr
			 		LEFT JOIN Ratingtable AS r ON tr.TTindex = r.TTindex
			 		LEFT JOIN TagListtable AS tl ON tr.TTindex = tl.TTindex
			 		LEFT JOIN Tagtable AS t USING (Tagindex)
			 		JOIN Albumtable AS al USING (Albumindex)
			 		JOIN Artisttable AS a ON (tr.Artistindex = a.Artistindex)
			 		JOIN Artisttable AS aa ON (al.AlbumArtistindex = aa.Artistindex)
			 	WHERE (r.Rating IS NOT NULL OR t.Name IS NOT NULL)  AND tr.Uri IS NOT NULL AND tr.isSearchResult < 2 AND a.Artistname = '".$value."'";
				break;

			case 'Tags':
				$qstring = "SELECT
					IFNULL(r.Rating, 0) AS Rating,
					".SQL_TAG_CONCAT." AS Tags,
					tr.TTindex,
					tr.TrackNo,
					tr.Title,
					tr.Duration,
					tr.Uri,
					a.Artistname,
					aa.Artistname AS AlbumArtist,
					al.Albumname,
					COUNT(tr.TTindex) AS count,
					al.Image, ";
				$qstring .= sortletter_mangler();

				$qstring .= " FROM
					TagListtable AS tl
					JOIN Tracktable AS tr USING (TTindex)
					JOIN Tagtable AS t USING (Tagindex)
					LEFT JOIN Ratingtable AS r ON tr.TTindex = r.TTindex
					JOIN Albumtable AS al USING (Albumindex)
					JOIN Artisttable AS a ON (tr.Artistindex = a.Artistindex)
					JOIN Artisttable AS aa ON (al.AlbumArtistindex = aa.Artistindex)
					WHERE ";
					$tags = explode(', ',$value);
					foreach ($tags as $i => $t) {
						$tags[$i] = "tr.TTindex IN (SELECT TTindex FROM TagListtable JOIN Tagtable USING (Tagindex) WHERE Name='".$t."')";
					}
					$qstring .= implode(' AND ', $tags);
					$qstring .= " AND tr.isSearchResult < 2 AND tr.Uri IS NOT NULL";
					break;
	}

	$qstring .= " GROUP BY tr.TTindex ORDER BY ";
	$qstring .= sort_artists_by_name();
	$qstring .= ", al.Albumname, tr.TrackNo";

	$t =  microtime(true);
	$ratings =  generic_sql_query($qstring);
	$took = microtime(true) - $t;
	debuglog(" :: Getting rating data took ".$took." seconds","SQL");
	// Our query for Tags (Tag List) will get eg if we're looking for 'tag1' it'll get tracks with 'tag1' and 'tag2'
	// So we check how many tags there are in each result and only use the ones that match the number of tags we're looking for
	if ($sortby == 'Tags') {
		$temp = array();
		$c = count($tags);
		foreach ($ratings as $rat) {
			if ($rat['count'] == $c) {
				$temp[] = $rat;
			}
		}
		$ratings = $temp;
	}

	return $ratings;

}

function clear_wishlist() {
	return generic_sql_query("DELETE FROM Tracktable WHERE Uri IS NULL", true);
}

function num_collection_tracks($albumindex) {
	// Returns the number of tracks this album contains that were added by a collection update
	// (i.e. not added manually). We do this because editing year or album artist for those albums
	// won't hold across a collection update, so we just forbid it.
	return generic_sql_query("SELECT COUNT(TTindex) AS cnt FROM Tracktable WHERE Albumindex = ".$albumindex." AND LastModified IS NOT NULL AND Hidden = 0 AND Uri IS NOT NULL AND isSearchResult < 2", false, null, 'cnt', 0);

}

function get_all_data($ttid) {

	// Misleadingly named function which should be used to get ratings and tags
	// (and whatever else we might add) based on a TTindex
	global $nodata;
	$data = $nodata;
	$result = generic_sql_query("SELECT
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
	);
	if (count($result) > 0) {
		$data = array_shift($result);
		$data['Tags'] = explode(', ', $data['Tags']);
		if ($data['LastTime'] != null && $data['LastTime'] != 0 && $data['LastTime'] != '0') {
			$data['Last'] = $data['LastTime'];
		}
	}
	return $data;
}

// Looking up this way is hugely faster than looking up by Uri
function get_extra_track_info(&$filedata) {
	$data = array();;
	$result = sql_prepare_query(false, PDO::FETCH_ASSOC, null, null,
		'SELECT Uri, TTindex, Disc, Artistname AS AlbumArtist, Albumtable.Image AS "X-AlbumImage", ImgKey AS ImgKey, mbid AS MUSICBRAINZ_ALBUMID, Searched
			FROM
				Tracktable
				JOIN Albumtable USING (Albumindex)
				JOIN Artisttable ON Albumtable.AlbumArtistindex = Artisttable.Artistindex
				WHERE Title = ?
				AND TrackNo = ?
				AND Albumname = ?',
			$filedata['Title'], $filedata['Track'], $filedata['Album']
	);
	foreach ($result as $tinfo) {
		if ($tinfo['Uri'] == $filedata['file']) {
			debuglog("Found Track In Collection","EXTRAINFO");
			$data = array_filter($tinfo, function($v) {
				if ($v === null || $v == '') {
					return false;
				}
				return true;
			});
			break;
		}
	}
	if (count($data) == 0) {
		$result = sql_prepare_query(false, PDO::FETCH_ASSOC, null, null,
			'SELECT Albumtable.Image AS "X-AlbumImage", ImgKey AS ImgKey, mbid AS MUSICBRAINZ_ALBUMID, Searched
				FROM
					Albumtable
					JOIN Artisttable ON Albumtable.AlbumArtistindex = Artisttable.Artistindex
					WHERE Albumname = ?
					AND Artistname = ?',
				$filedata['Album'], concatenate_artist_names($filedata['AlbumArtist'])
		);
		foreach ($result as $tinfo) {
			debuglog("Found Album In Collection","EXTRAINFO");
			$data = array_filter($tinfo, function($v) {
				if ($v === null || $v == '') {
					return false;
				}
				return true;
			});
			break;
		}
	}
	return $data;
}

function get_imagesearch_info($key) {

	// Used by getalbumcover.php to get album and artist names etc based on an Image Key

	$retval = array('artist' => null, 'album' => null, 'mbid' => null, 'albumpath' => null, 'albumuri' => null);
	$result = generic_sql_query(
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
			WHERE ImgKey = '".$key."' AND isSearchResult < 2 AND Hidden = 0", false, PDO::FETCH_OBJ
	);
	// This can come back with multiple results if we have the same album on multiple backends
	// So we make sure we combine the data to get the best possible set
	foreach ($result as $obj) {
		if ($retval['artist'] == null) {
			$retval['artist'] = $obj->Artistname;
		}
		if ($retval['album'] == null) {
			$retval['album'] = $obj->Albumname;
		}
		if ($retval['mbid'] == null || $retval['mbid'] == "") {
			$retval['mbid'] = $obj->mbid;
		}
		if ($retval['albumpath'] == null) {
			$retval['albumpath'] = get_album_directory($obj->Albumindex, $obj->AlbumUri);
		}
		if ($retval['albumuri'] == null || $retval['albumuri'] == "") {
			$retval['albumuri'] = $obj->AlbumUri;
		}
		debuglog("Found album ".$retval['album']." in collection","GETALBUMCOVER",6);
	}

	$result = generic_sql_query(
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
			WHERE ImgKey = '".$key."' AND isSearchResult > 1", false, PDO::FETCH_OBJ
	);
	// This can come back with multiple results if we have the same album on multiple backends
	// So we make sure we combine the data to get the best possible set
	foreach ($result as $obj) {
		if ($retval['artist'] == null) {
			$retval['artist'] = $obj->Artistname;
		}
		if ($retval['album'] == null) {
			$retval['album'] = $obj->Albumname;
		}
		if ($retval['mbid'] == null || $retval['mbid'] == "") {
			$retval['mbid'] = $obj->mbid;
		}
		if ($retval['albumpath'] == null) {
			$retval['albumpath'] = get_album_directory($obj->Albumindex, $obj->AlbumUri);
		}
		if ($retval['albumuri'] == null || $retval['albumuri'] == "") {
			$retval['albumuri'] = $obj->AlbumUri;
		}
		debuglog("Found album ".$retval['album']." in search results or hidden tracks","GETALBUMCOVER",6);
	}
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
		$result = generic_sql_query("SELECT Uri FROM Tracktable WHERE Albumindex = ".$albumindex." LIMIT 1");
		foreach ($result as $obj2) {
			$retval = dirname($obj2['Uri']);
			$retval = preg_replace('#^local:track:#', '', $retval);
			$retval = preg_replace('#^file://#', '', $retval);
			$retval = preg_replace('#^beetslocal:\d+:'.$prefs['music_directory_albumart'].'/#', '', $retval);
			debuglog("Got album directory using track Uri - ".$retval,"SQL",9);
		}
	}
	return $retval;
}

function update_image_db($key, $found, $imagefile) {
	$val = ($found) ? $imagefile : null;
	if (sql_prepare_query(true, null, null, null, "UPDATE Albumtable SET Image = ?, Searched = 1 WHERE ImgKey = ?", $val, $key)) {
		debuglog("    Database Image URL Updated","MYSQL",8);
	} else {
		debuglog("    Failed To Update Database Image URL","MYSQL",2);
	}
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
		generic_sql_query("DELETE FROM Playcounttable WHERE TTindex=".$ttid, true);
		generic_sql_query("DELETE FROM Tracktable WHERE TTindex=".$ttid, true);
		return true;
	}
	return false;
}

function check_for_wishlist_track(&$data) {
	$result = sql_prepare_query(false, PDO::FETCH_ASSOC, null, null, "SELECT TTindex FROM Tracktable JOIN Artisttable USING (Artistindex)
									WHERE Artistname=? AND Title=? AND Uri IS NULL",$data['artist'],$data['title']);
	foreach ($result as $obj) {
		debuglog("Wishlist Track ".$obj['TTindex']." matches the one we're adding","USERRATING",7);
		$meta = get_all_data($obj['TTindex']);
		$data['attributes'] = array();
		$data['attributes'][] = array('attribute' => 'Rating', 'value' => $meta['Rating']);
		$data['attributes'][] = array('attribute' => 'Tags', 'value' => $meta['Tags']);
		generic_sql_query("DELETE FROM Tracktable WHERE TTindex=".$obj['TTindex'], true);
	}
}

function sort_artists_by_name() {
	global $prefs;
	$qstring = '';
	foreach ($prefs['artistsatstart'] as $a) {
		$qstring .= "CASE WHEN LOWER(a.Artistname) = LOWER('".$a."') THEN 1 ELSE 2 END, ";
	}
	if (count($prefs['nosortprefixes']) > 0) {
		$qstring .= "(CASE ";
		foreach($prefs['nosortprefixes'] AS $p) {
			$phpisshitsometimes = strlen($p)+2;
			$qstring .= "WHEN LOWER(a.Artistname) LIKE '".strtolower($p).
				" %' THEN LOWER(SUBSTR(a.Artistname,".$phpisshitsometimes.")) ";
		}
		$qstring .= "ELSE LOWER(a.Artistname) END)";
	} else {
		$qstring .= "LOWER(a.Artistname)";
	}
	return $qstring;
}

function albumartist_sort_query($flag) {
	// This query gives us album artists only. It also makes sure we only get artists for whom we
	// have actual tracks (no album artists who appear only on the wishlist or who have only hidden tracks)
	// Using GROUP BY is faster than using SELECT DISTINCT
	// USING IN is faster than the double JOIN
	global $prefs;
	$sflag = ($flag == 'b') ? "AND isSearchResult > 0" : "AND isSearchResult < 2";

	$qstring = "SELECT Artistname, Artistindex
					FROM Artisttable AS a
					WHERE
					Artistindex IN
						(SELECT AlbumArtistindex FROM Albumtable JOIN Tracktable USING (Albumindex)
							WHERE Uri IS NOT NULL
							AND Hidden = 0
							".track_date_check($prefs['collectionrange'], $flag)."
							".$sflag."
							GROUP BY AlbumArtistindex)
				ORDER BY ";
	$qstring .= sort_artists_by_name();
	return $qstring;
}

function do_artists_from_database($why, $what, $who) {
	global $divtype;
	$singleheader = array();
	debuglog("Generating artist ".$why.$what.$who." from database","DUMPALBUMS",7);
	$singleheader['type'] = 'insertAfter';
	$singleheader['where'] = 'fothergill';
	$count = 0;
	$t = microtime(true);
	$result = generic_sql_query(albumartist_sort_query($why), false, PDO::FETCH_ASSOC);
	$at = microtime(true) - $t;
	debuglog(" -- Album Artist SQL query took ".$at." seconds","SQL",8);
	$t = microtime(true);
	foreach($result as $obj) {
		if ($who == "root") {
			print artistHeader($why.$what.$obj['Artistindex'], $obj['Artistname']);
			$count++;
		} else {
			if ($obj['Artistindex'] != $who) {
				$singleheader['type'] = 'insertAfter';
				$singleheader['where'] = $why.$what.$obj['Artistindex'];
			} else {
				$singleheader['html'] = artistHeader($why.$what.$obj['Artistindex'], $obj['Artistname']);
				$singleheader['id'] = $who;
				$at = microtime(true) - $t;
				debuglog(" -- Generating Artist Header took ".$at." seconds","SQL",8);
				return $singleheader;
			}
		}
		$divtype = ($divtype == "album1") ? "album2" : "album1";
	}
	$at = microtime(true) - $t;
	debuglog(" -- Generating Artist List took ".$at." seconds","SQL",8);
	return $count;
}

function get_list_of_artists() {
	return generic_sql_query(albumartist_sort_query("a"));
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

	$qstring .= "Tracktable.Uri IS NOT NULL AND Tracktable.Hidden = 0 ".track_date_check($prefs['collectionrange'], $why)." ".$sflag.")";
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
	$result = generic_sql_query($qstring, false, PDO::FETCH_OBJ);
	foreach ($result as $obj) {
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

function do_albums_from_database($why, $what, $who, $fragment = false, $use_artistindex = false, $force_artistname = false, $do_controlheader = true) {
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
	$currart = "";
	$currban = "";
	$result = generic_sql_query($qstring);
	if ($do_controlheader && count($result) > 0) {
		print albumControlHeader($fragment, $why, $what, $who, $result[0]['Artistname']);
	}
	foreach ($result as $obj) {
		$artistbanner = ($prefs['sortcollectionby'] == 'albumbyartist' && $prefs['showartistbanners']) ? $obj['Artistname'] : null;
		$obj['Artistname'] = ($force_artistname || $prefs['sortcollectionby'] == "album") ? $obj['Artistname'] : null;
		$obj['why'] = $why;
		$obj['id'] = $why.$what.$obj['Albumindex'];
		$obj['class'] = 'album';
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
	return $count;
}

function artistBanner($a, $i, $why) {
	return '<div class="configtitle artistbanner brick brick_wide" id="'.$why.'artist'.$i.'"><b>'.$a.'</b></div>';
}

function remove_album_from_database($albumid) {
	generic_sql_query("DELETE FROM Tracktable WHERE Albumindex = ".$albumid, true);
	generic_sql_query("DELETE FROM Albumtable WHERE Albumindex = ".$albumid, true);
}

function get_list_of_albums($aid) {
	$qstring = "SELECT * FROM Albumtable WHERE AlbumArtistindex = '".$aid."' AND ";
	$qstring .= "Albumindex IN (SELECT Albumindex FROM Tracktable WHERE Tracktable.Albumindex =
		Albumtable.Albumindex AND Tracktable.Uri IS NOT NULL AND Tracktable.Hidden = 0 AND
		Tracktable.isSearchResult < 2)";
	$qstring .= ' ORDER BY LOWER(Albumname)';
	return generic_sql_query($qstring);
}

function get_album_tracks_from_database($index, $cmd, $flag) {
	global $prefs;
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
			$qstring = "SELECT Uri, Disc, Trackno FROM
							Tracktable JOIN Ratingtable USING (TTindex)
							WHERE Albumindex = '".$index.
							"' AND Uri IS NOT NULL
							AND Hidden = 0
							AND isSearchResult <2 "
							.track_date_check($prefs['collectionrange'], $flag).
						"UNION SELECT Uri, Disc, TrackNo FROM
							Tracktable JOIN TagListtable USING (TTindex)
							WHERE Albumindex = '".$index.
							"' AND Uri IS NOT NULL
							AND Hidden = 0
							AND isSearchResult <2 "
							.track_date_check($prefs['collectionrange'], $flag).
						"ORDER BY Disc, TrackNo;";
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
		$qstring = $action." Uri FROM Tracktable".$rflag." WHERE Albumindex = '".$index."' AND Uri IS NOT NULL AND Hidden = 0 ".track_date_check($prefs['collectionrange'], $flag)." ".$sflag." ORDER BY Disc, TrackNo";
	}
	$result = generic_sql_query($qstring);
	foreach($result as $a) {
		$retarr[] = $cmd.' "'.$a['Uri'].'"';
	}
	return $retarr;
}

function get_artist_tracks_from_database($index, $cmd, $flag) {
	global $prefs;
	$retarr = array();
	debuglog("Getting Tracks for AlbumArtist ".$index,"MYSQL",7);
	$qstring = "SELECT Albumindex FROM Albumtable JOIN Artisttable ON
		(Albumtable.AlbumArtistindex = Artisttable.Artistindex) WHERE AlbumArtistindex = ".$index." ORDER BY";
	if ($prefs['sortbydate']) {
		if ($prefs['notvabydate']) {
			$qstring .= " CASE WHEN Artistname = 'Various Artists' THEN LOWER(Albumname) ELSE Year END,";
		} else {
			$qstring .= ' Year,';
		}
	}
	$qstring .= ' LOWER(Albumname)';
	$result = generic_sql_query($qstring);
	foreach ($result as $a) {
		$retarr = array_merge($retarr, get_album_tracks_from_database($a['Albumindex'], $cmd, $flag));
	}
	return $retarr;
}

function do_tracks_from_database($why, $what, $whom, $fragment = false) {
	// This function can accept multiple album ids ($whom can be an array)
	// in which case it will combine them all into one 'virtual album' - see browse_album()
	global $prefs;
	$who = getArray($whom);
	$wdb = implode($who, ', ');
    debuglog("Generating tracks for album(s) ".$wdb." from database","DUMPALBUMS",7);
	if ($fragment) {
		ob_start();
	}
	$t = ($why == "b") ? "AND isSearchResult > 0" : "AND isSearchResult < 2";
	$trackarr = generic_sql_query(
		// This looks like a wierd way of doing it but the obvious way doesn't work with mysql
		// due to table aliases being used.
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
				".track_date_check($prefs['collectionrange'], $why)."
				".$t."
				AND tr.Artistindex = ta.Artistindex
				AND al.Albumindex = tr.Albumindex
		GROUP BY tr.TTindex
		ORDER BY CASE WHEN title LIKE 'Album: %' THEN 1 ELSE 2 END, disc, trackno"
	);
	$numtracks = count($trackarr);
	$numdiscs = get_highest_disc($trackarr);
	$currdisc = -1;
	trackControlHeader($why, $what, $who[0], get_album_details($who[0]));
	foreach ($trackarr as $arr) {
		if ($numdiscs > 1 && $arr['disc'] != $currdisc && $arr['disc'] > 0) {
            $currdisc = $arr['disc'];
            print '<div class="clickable clickdisc playable draggable discnumber indent">'.ucfirst(strtolower(get_int_text("musicbrainz_disc"))).' '.$currdisc.'</div>';
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

function get_album_details($albumindex) {
	return generic_sql_query(
		"SELECT Albumname, Artistname, Image, AlbumUri
		FROM Albumtable
		JOIN Artisttable ON Albumtable.AlbumArtistindex = Artisttable.Artistindex
		WHERE Albumindex = ".$albumindex );
}

function get_artist_charts() {
	$artists = array();
	$query = "SELECT SUM(Playcount) AS playtot, Artistindex, Artistname FROM
		 Playcounttable JOIN Tracktable USING (TTindex) JOIN Artisttable USING (Artistindex)";
	$query .= " GROUP BY Artistindex ORDER BY playtot DESC LIMIT 40";
	$result = generic_sql_query($query, false, PDO::FETCH_OBJ);
	foreach ($result as $obj) {
		$artists[] = array( 'label_artist' => $obj->Artistname, 'soundcloud_plays' => $obj->playtot);
	}
	return $artists;
}

function get_album_charts() {
	$albums = array();
	$query = "SELECT SUM(Playcount) AS playtot, Albumname, Artistname, AlbumUri, Albumindex
		 FROM Playcounttable JOIN Tracktable USING (TTindex) JOIN Albumtable USING (Albumindex)
		 JOIN Artisttable ON Albumtable.AlbumArtistindex = Artisttable.Artistindex";
	$query .= " GROUP BY Albumindex ORDER BY playtot DESC LIMIT 40";
	$result = generic_sql_query($query, false, PDO::FETCH_OBJ);
	foreach ($result as $obj) {
		$albums[] = array( 'label_artist' => $obj->Artistname,
			'label_album' => $obj->Albumname,
			'soundcloud_plays' => $obj->playtot, 'uri' => $obj->AlbumUri);
	}
	return $albums;
}

function get_track_charts($limit = 40) {
	$tracks = array();
	$query = "SELECT Title, Playcount, Artistname, Uri FROM Tracktable JOIN Playcounttable USING (TTIndex)
		JOIN Artisttable USING (Artistindex)";
	$query .= " ORDER BY Playcount DESC LIMIT ".$limit;
	$result = generic_sql_query($query, false, PDO::FETCH_OBJ);
	foreach ($result as $obj) {
		$tracks[] = array( 'label_artist' => $obj->Artistname,
			'label_track' => $obj->Title,
			'soundcloud_plays' => $obj->Playcount, 'uri' => $obj->Uri);
	}
	return $tracks;
}

function find_justadded_artists() {
	return sql_get_column("SELECT DISTINCT AlbumArtistindex FROM Albumtable JOIN Tracktable USING (Albumindex) WHERE justAdded = 1", 'AlbumArtistindex');
}

function find_justadded_albums() {
	return sql_get_column("SELECT DISTINCT Albumindex FROM Tracktable WHERE justAdded = 1", 'Albumindex');
}

function get_user_radio_streams() {
	return generic_sql_query("SELECT * FROM RadioStationtable WHERE IsFave = 1 ORDER BY Number, StationName");
}

function remove_user_radio_stream($x) {
    generic_sql_query("UPDATE RadioStationtable SET IsFave = 0, Number = 65535 WHERE Stationindex = ".$x, true);
}

function save_radio_order($order) {
    foreach ($order as $i => $o) {
        generic_sql_query("UPDATE RadioStationtable SET Number = ".$i." WHERE Stationindex = ".$o, true);
    }
}

function check_radio_station($playlisturl, $stationname, $image) {
	global $mysqlc;
	$index = null;
	$index = sql_prepare_query(false, null, 'Stationindex', false, "SELECT Stationindex FROM RadioStationtable WHERE PlaylistUrl = ?", $playlisturl);
	if ($index === false) {
		debuglog("Adding New Radio Station : ","RADIO");
		debuglog("  Name  : ".$stationname,"RADIO");
		debuglog("  Image : ".$image,"RADIO");
		debuglog("  URL   : ".$playlisturl,"RADIO");
		if (sql_prepare_query(true, null, null, null, "INSERT INTO RadioStationtable (IsFave, StationName, PlaylistUrl, Image) VALUES (?, ?, ?, ?)",
								0, trim($stationname), trim($playlisturl), trim($image))) {
			$index = $mysqlc->lastInsertId();
			debuglog("Created new radio station with index ".$index,"RADIO");
		}
	} else {
		debuglog("Found radio station with index ".$index,"RADIO");
	}
	return $index;
}

function check_radio_tracks($stationid, $tracks) {
	generic_sql_query("DELETE FROM RadioTracktable WHERE Stationindex = ".$stationid, true);
	foreach ($tracks as $track) {
		debuglog("  Adding New Track ".$track['TrackUri'],"RADIO");
		sql_prepare_query(true, null, null, null, "INSERT INTO RadioTracktable (Stationindex, TrackUri, PrettyStream) VALUES (?, ?, ?)",
							$stationid, trim($track['TrackUri']), trim($track['PrettyStream']));
	}
}

function add_fave_station($info) {
	if (array_key_exists('streamid', $info) && $info['streamid']) {
		debuglog("Updating StationIndex ".$info['streamid']." to be fave","RADIO");
		generic_sql_query("UPDATE RadioStationtable SET IsFave = 1 WHERE Stationindex = ".$info['streamid'], true);
		return true;
	}
	$stationindex = check_radio_station($info['location'],$info['album'],$info['image']);
	check_radio_tracks($stationindex, array(array('TrackUri' => $info['location'], 'PrettyStream' => $info['stream'])));
	generic_sql_query("UPDATE RadioStationtable SET IsFave = 1 WHERE Stationindex = ".$stationindex, true);
}

function update_radio_station_name($info) {
	if ($info['streamid']) {
		debuglog("Updating Stationindex ".$info['streamid'].' with new name '.$info['name']);
		sql_prepare_query(true, null, null, null, "UPDATE RadioStationtable SET StationName = ? WHERE Stationindex = ?",$info['name'],$info['streamid']);
	} else {
		$stationid = check_radio_station($info['uri'], $info['name'], '');
		check_radio_tracks($stationid, array(array('TrackUri' => $info['uri'], 'PrettyStream' => '')));
	}
}

function find_stream_name_from_index($index) {
	return simple_query('StationName', 'RadioStationtable', 'StationIndex', $index, '');
}

function update_stream_image($stream, $image) {
	sql_prepare_query(true, null, null, null, "UPDATE RadioStationtable SET Image = ? WHERE StationName = ?",$image,$stream);
}

function update_podcast_image($podid, $image) {
	debuglog("Setting Image to ".$image." for podid ".$podid,"PODCASTS");
	sql_prepare_query(true, null, null, null, 'UPDATE Podcasttable SET Image = ? WHERE PODindex = ?',$image, $podid);
}

function find_radio_track_from_url($url) {
	return sql_prepare_query(false, PDO::FETCH_OBJ, null, null,
                                "SELECT
                                    Stationindex, PlaylistUrl, StationName, Image, PrettyStream
                                    FROM
                                    RadioStationtable JOIN RadioTracktable USING (Stationindex)
                                    WHERE TrackUri = ?",$url);
}

function find_podcast_track_from_url($url) {
	return sql_prepare_query(false, PDO::FETCH_OBJ, null, null,
								"SELECT
									PodcastTracktable.Title AS title,
									PodcastTracktable.Artist AS artist,
									PodcastTracktable.Duration AS duration,
									PodcastTracktable.Description AS comment,
									Podcasttable.Title AS album,
									Podcasttable.Artist AS albumartist,
									Podcasttable.Image AS image
									FROM PodcastTracktable JOIN Podcasttable USING (PODindex)
									WHERE PodcastTracktable.Link=?
									OR PodcastTracktable.Localfilename=?",
									$url,
									preg_replace('#http://.*?/#', '/', $url));
}

//
// Database Global Stats and Version Control
//

function update_track_stats() {
	debuglog("Updating Track Stats","MYSQL",7);
	$t = microtime(true);
	$ac = get_artist_count(ADDED_ALL_TIME);
	update_stat('ArtistCount',$ac);

	$ac = get_album_count(ADDED_ALL_TIME);
	update_stat('AlbumCount',$ac);

	$ac = get_track_count(ADDED_ALL_TIME);
	update_stat('TrackCount',$ac);

	$ac = get_duration_count(ADDED_ALL_TIME);
	update_stat('TotalTime',$ac);
	$at = microtime(true) - $t;
	debuglog("Updating Track Stats took ".$at." seconds","BACKEND",8);
}

function update_stat($item, $value) {
	generic_sql_query("UPDATE Statstable SET Value='".$value."' WHERE Item='".$item."'", true);
}

function get_stat($item) {
	return simple_query('Value', 'Statstable', 'Item', $item, 0);
}

function get_artist_count($range) {
	$ac = generic_sql_query(
		"SELECT COUNT(*) AS NumArtists FROM (SELECT AlbumArtistindex FROM Albumtable
		INNER JOIN Tracktable USING (Albumindex) WHERE Uri IS NOT NULL
		AND Hidden = 0 AND isSearchResult < 2 ".track_date_check($range, 'a')." GROUP BY AlbumArtistindex) AS t", false, null, 'NumArtists', 0);
	return $ac;
}

function get_album_count($range) {
	$ac = generic_sql_query(
		"SELECT COUNT(*) AS NumAlbums FROM (SELECT Albumindex FROM Tracktable WHERE Uri IS NOT NULL
		AND Hidden = 0 AND isSearchResult < 2 ".track_date_check($range, 'a')." GROUP BY Albumindex) AS t", false, null, 'NumAlbums', 0);
	return $ac;
}

function get_track_count($range) {
	$ac = generic_sql_query("SELECT COUNT(*) AS NumTracks FROM Tracktable WHERE Uri IS NOT NULL AND Hidden=0 ".track_date_check($range, 'a')." AND isSearchResult < 2", false, null, 'NumTracks', 0);
	return $ac;
}

function get_duration_count($range) {
	$ac = generic_sql_query("SELECT SUM(Duration) AS TotalTime FROM Tracktable WHERE Uri IS NOT NULL AND Hidden=0 ".track_date_check($range, 'a')." AND isSearchResult < 2", false, null, 'TotalTime', 0);
	if ($ac == '') {
		$ac = 0;
	}
	return $ac;
}

function set_collection_type() {
	if ($prefs['player_backend'] == 'mpd') {
		generic_sql_query('UPDATE Statstable SET Value = '.COLLECTION_TYPE_MPD.' WHERE Item = CollType');
	} else {
		generic_sql_query('UPDATE Statstable SET Value = '.COLLECTION_TYPE_MOPIDY.' WHERE Item = CollType');
	}
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
			print '<div class="sizer"></div>';
	    	if ($why == 'a') {
	    		print collectionStats();
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
	global $prefs;
    $html = '<div id="fothergill" class="brick brick_wide">';
	if ($prefs['collectionrange'] == ADDED_ALL_TIME) {
    	$html .= alistheader(get_stat('ArtistCount'),
							get_stat('AlbumCount'),
							get_stat('TrackCount'),
							format_time(get_stat('TotalTime'))
						);
	} else {
		$html .= alistheader(get_artist_count($prefs['collectionrange']),
							get_album_count($prefs['collectionrange']),
							get_track_count($prefs['collectionrange']),
							format_time(get_duration_count($prefs['collectionrange'])));
	}
    $html .= '</div>';
	return $html;
}

function searchStats() {
	$numartists = generic_sql_query(
		"SELECT COUNT(*) AS NumArtists FROM (SELECT DISTINCT AlbumArtistIndex FROM Albumtable
		INNER JOIN Tracktable USING (Albumindex) WHERE Albumname IS NOT NULL AND Uri IS NOT
		NULL AND Hidden = 0 AND isSearchResult > 0) AS t", false, null, 'NumArtists', 0);

	$numalbums = generic_sql_query(
		"SELECT COUNT(*) AS NumAlbums FROM (SELECT DISTINCT Albumindex FROM Albumtable
		INNER JOIN Tracktable USING (Albumindex) WHERE Albumname IS NOT NULL AND Uri IS NOT
		NULL AND Hidden = 0 AND isSearchResult > 0) AS t", false, null, 'NumAlbums', 0);

	$numtracks = generic_sql_query(
		"SELECT COUNT(*) AS NumTracks FROM Tracktable WHERE Uri IS NOT NULL
		AND Hidden=0 AND isSearchResult > 0", false, null, 'NumTracks', 0);

	$numtime = generic_sql_query(
		"SELECT SUM(Duration) AS TotalTime FROM Tracktable WHERE Uri IS NOT NULL AND
		Hidden=0 AND isSearchResult > 0", false, null, 'TotalTime', 0);

	print '<div class="brick brick_wide">';
    print alistheader($numartists, $numalbums, $numtracks, format_time($numtime));
	print '</div>';

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
	$retval = array('add "'.$uri.'"');
	$result = sql_prepare_query(false, PDO::FETCH_OBJ, null, null, "SELECT Albumindex, TrackNo, Disc, isSearchResult FROM Tracktable WHERE Uri = ?", $uri);
	$album = array_shift($result);
	if ($album) {
		$newsr = ($album->isSearchResult == 0) ? 2 : 3;
		$retval = array();
		$result = sql_prepare_query(false, PDO::FETCH_OBJ, null, null, "SELECT Uri FROM Tracktable WHERE Albumindex = ? AND ((Disc = ? AND TrackNo >= ?) OR (Disc > ?)) AND Hidden = 0 AND isSearchResult < ? ORDER BY Disc, TrackNo",
										$album->Albumindex, $album->Disc, $album->TrackNo, $album->Disc, $newsr);
		foreach ($result as $obj) {
			$retval[] = 'add "'.$obj->Uri.'"';
		}

	}
	return $retval;
}

function check_url_against_database($url, $itags, $rating) {
	global $mysqlc;
	if ($mysqlc === null) connect_to_database();
	$qstring = "SELECT COUNT(t.TTindex) AS num FROM Tracktable AS t ";
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
	$count = sql_prepare_query(false, null, 'num', 0, $qstring, $tags);
	if ($count > 0) {
		return true;
	}
	return false;
}

function cleanSearchTables() {
	// Clean up the database tables before performing a new search or updating the collection

	debuglog("Cleaning Search Results","MYSQL",6);
	// Any track that was previously hidden needs to be re-hidden
	generic_sql_query("UPDATE Tracktable SET Hidden = 1, isSearchResult = 0 WHERE isSearchResult = 3", true);

	// Any track that was previously a '2' (added to database as search result) but now
	// has a playcount needs to become a zero and be hidden.
	hide_played_tracks();

	// remove any remaining '2's
	generic_sql_query("DELETE FROM Tracktable WHERE isSearchResult = 2", true);

	// Set '1's back to '0's
	generic_sql_query("UPDATE Tracktable SET isSearchResult = 0 WHERE isSearchResult = 1", true);

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
    generic_sql_query("DELETE FROM Tracktable WHERE LastModified IS NOT NULL AND Hidden = 0 AND justAdded = 0", true);
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
	// Also the WHERE (thing) = 1 is important otherwise, at least with MySQL, it sets EVERY ROW to 0
	// whether or not it's already 0. That takes a very long time
	generic_sql_query("UPDATE Tracktable SET justAdded = 0 WHERE justAdded = 1", true);
	generic_sql_query("UPDATE Albumtable SET justUpdated = 0 WHERE justUpdated = 1", true);
}

function remove_cruft() {
    debuglog("Removing orphaned albums","MYSQL",6);
	$t = microtime(true);
    generic_sql_query("DELETE FROM Albumtable WHERE Albumindex NOT IN (SELECT DISTINCT Albumindex FROM Tracktable)", true);
	$at = microtime(true) - $t;
	debuglog(" -- Removing orphaned albums took ".$at." seconds","BACKEND",6);

    debuglog("Removing orphaned artists","MYSQL",6);
	$t = microtime(true);
    delete_orphaned_artists();
	$at = microtime(true) - $t;
	debuglog(" -- Removing orphaned artists took ".$at." seconds","BACKEND",6);

    debuglog("Tidying Metadata","MYSQL",6);
	$t = microtime(true);
    generic_sql_query("DELETE FROM Ratingtable WHERE Rating = '0'", true);
	generic_sql_query("DELETE FROM Ratingtable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable WHERE Hidden = 0)", true);
	generic_sql_query("DELETE FROM TagListtable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable WHERE Hidden = 0)", true);
	// Temporary table needed  because we can't use an IN clause as it conflicts with a trigger
	generic_sql_query("CREATE TEMPORARY TABLE used_tags AS SELECT DISTINCT Tagindex FROM TagListtable", true);
	generic_sql_query("DELETE FROM Tagtable WHERE Tagindex NOT IN (SELECT Tagindex FROM used_tags)", true);
	generic_sql_query("DELETE FROM Playcounttable WHERE Playcount = '0'", true);
	generic_sql_query("DELETE FROM Playcounttable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable)", true);
	$at = microtime(true) - $t;
	debuglog(" -- Tidying metadata took ".$at." seconds","BACKEND",6);
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

	// Note the use of === to detect LastModified, because == doesn't tell the difference between 0 and null
	//  - so if we have a manually added track and then add a collection track over it from a backend that doesn't
	//  give us LastModified (eg Spotify-Web), we don't update lastModified and the track remains manually added.

    if ($ttid) {
    	if ((!$doing_search && $trackobj->tags['Last-Modified'] !== $lastmodified) ||
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
		    	if (!$doing_search && $lastmodified !== $trackobj->tags['Last-Modified']) debuglog("    LastModified has changed: We have ".$lastmodified." but track has ".$trackobj->tags['Last-Modified'],"MYSQL",7);
		    	if ($disc != $trackobj->tags['Disc']) debuglog("    Disc Number has changed: We have ".$disc." but track has ".$trackobj->tags['Disc'],"MYSQL",7);
		    	if ($hidden != 0) debuglog("    It is hidden","MYSQL",7);
		    	if ($trackobj->tags['file'] != $uri) {
		    		debuglog("    Uri has changed from : ".$uri,"MYSQL",7);
		    		debuglog("                      to : ".$trackobj->tags['file'],"MYSQL",7);
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
			generic_sql_query("UPDATE Tracktable SET justAdded = 1 WHERE TTindex = ".$ttid, true);
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
