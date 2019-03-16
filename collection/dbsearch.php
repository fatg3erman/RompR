<?php

require_once ('player/mpd/filetree.php');

function doDbCollection($terms, $domains, $resultstype, &$collection) {

	// This can actually be used to search the database for title, album, artist, anything, rating, and tag
	// But it isn't because we let Mopidy/MPD search for anything they support because otherwise we
	// have to duplicate their entire database, which is daft.
	// This function was written before I realised that... :)
	// It's still used for searches where we're only looking for tags and/or ratings in conjunction with
	// any of the above terms, because mopidy often returns incomplete search results.

	$parameters = array();
	$qstring = "SELECT t.*, al.*, a1.*, a2.Artistname AS AlbumArtistName ";
	if (array_key_exists('rating', $terms)) {
		$qstring .= ",rat.Rating ";
	}
	$qstring .= "FROM Tracktable AS t ";
	if (array_key_exists('tag', $terms)) {
		$qstring .= "JOIN (SELECT DISTINCT TTindex FROM TagListtable JOIN Tagtable AS tag USING (Tagindex) WHERE";
		$tagterms = array();
		foreach ($terms['tag'] as $tag) {
			$parameters[] = trim($tag);
			$tagterms[] = " tag.Name LIKE ?";
		}
		$qstring .= implode(" OR",$tagterms);
		$qstring .=") AS j ON j.TTindex = t.TTindex ";
	}
	if (array_key_exists('rating', $terms)) {
		$qstring .= "JOIN (SELECT * FROM Ratingtable WHERE Rating >= ".
			$terms['rating'].") AS rat ON rat.TTindex = t.TTindex ";
	}
	$qstring .= "JOIN Artisttable AS a1 ON a1.Artistindex = t.Artistindex ";
	$qstring .= "JOIN Albumtable AS al ON al.Albumindex = t.Albumindex ";
	$qstring .= "JOIN Artisttable AS a2 ON al.AlbumArtistindex = a2.Artistindex ";
	if (array_key_exists('wishlist', $terms)) {
		$qstring .= "WHERE t.Uri IS NULL";
	} else {
		$qstring .= "WHERE t.Uri IS NOT NULL ";
	}
	$qstring .= "AND t.Hidden = 0 AND t.isSearchResult < 2 ";

	// Map search parameters to database tables
	$searchmap = array(
		'artist' => 'a1.Artistname',
		'album' =>  'al.Albumname',
		'title' => 't.Title',
		'file' => 't.Uri',
		'albumartist' => 'a2.Artistname'
	);

	foreach ($searchmap as $t => $d) {
		if (array_key_exists($t, $terms)) {
			$qstring .= 'AND (';
			$qstring .= format_for_search($terms[$t],$d, $parameters);
			$qstring .= ' OR '.format_for_search2($terms[$t],$d, $parameters);
			$qstring .= ') ';
		}
	}

	if (array_key_exists('any', $terms)) {
		$qstring .= ' AND (';
		$bunga = array();
		foreach ($terms['any'] as $tim) {
			$t = explode(' ',$tim);
			foreach ($t as $tom) {
				foreach ($searchmap AS $d) {
					$bunga[] = format_for_search(array($tom), $d, $parameters);
					$bunga[] = format_for_search2(array($tom), $d, $parameters);
				}
			}
		}
		$qstring .= implode(' OR ', $bunga);
		$qstring .= ')';
	}

	if (array_key_exists('date', $terms)) {
		$qstring .= "AND ";
		$parameters[] = trim($terms['date'][0]);
		$qstring .= "al.Year = ? ";
	}

	if (is_array($domains)) {
		$qstring .= "AND (";
		$domainterms = array();
		foreach ($domains as $dom) {
			$parameters[] = trim($dom)."%";
			$domainterms[] = "t.Uri LIKE ?";
		}
		$qstring .= implode(" OR ",$domainterms);
		$qstring .= ")";
	}

	debuglog("SQL Search String is ".$qstring,"DB SEARCH",8);
	foreach ($parameters as $i => $param) {
		debuglog("  Parameter ".$i."  '".$param.'"',"DB SEARCH", 8);
	}

	$result = sql_prepare_query(false, PDO::FETCH_OBJ, null, null, $qstring, $parameters);
	$fcount = count($result);
	foreach ($result as $obj) {
		$filedata = array(
			'Artist' => array($obj->Artistname),
			'Album' => $obj->Albumname,
			'AlbumArtist' => array($obj->AlbumArtistName),
			'file' => $obj->Uri,
			'Title' => $obj->Title,
			'Track' => $obj->TrackNo,
			'X-AlbumImage' => $obj->Image,
			'Time' => $obj->Duration,
			'X-AlbumUri' => $obj->AlbumUri,
			'Date' => $obj->Year,
			'Last-Modified' => $obj->LastModified
		);
		$filedata = array_merge(MPD_FILE_MODEL, $filedata);
		debuglog("Found : ".$obj->Title." ".$obj->Uri,"DB SEARCH",8);
		if ($resultstype == "tree") {
            $collection->newItem($filedata);
		} else if ($resultstype == "RAW") {
			$collection->newTrack($filedata);
		} else {
			generic_sql_query("UPDATE Tracktable SET isSearchResult = 1 WHERE TTindex = ".$obj->TTindex, true);
		}
	}

	return $fcount;

}

function format_for_search($terms, $s, &$parameters) {
	// Make things a little more searchable
	$a = array();
	foreach ($terms as $i => $term) {
		$t = trim($term);
		$t = preg_replace('/[\(\)\/\[\]\&\*\+\'\"\,\/]/','%',$t);
		$a[] = $s.' LIKE ?';
		$parameters[] = '% '.$t. '%';
		$a[] = $s.' LIKE ?';
		$parameters[] = '%'.$t. ' %';
	}
	$ret = implode(' OR ',$a);
	return $ret;
}

function format_for_search2($terms, $s, &$parameters) {
	// Make things a little more searchable
	$a = array();
	foreach ($terms as $i => $term) {
		$t = trim($term);
		$t = preg_replace('/[\(\)\/\[\]\&\*\+\'\"\,\/]/','%',$t);
		$a[] = $s.' = ?';
		$parameters[] = $t;
	}
	$ret = implode(' OR ',$a);
	return $ret;
}

?>
