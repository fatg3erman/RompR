<?php

class db_collection extends collection_base {

	public function __construct() {
		logger::log('DB_COLLECTION', 'Starting massive badger process');
		parent::__construct();
	}

	public function doDbCollection($terms, $domains, $tree) {

		// This can actually be used to search the database for title, album, artist, anything, rating, and tag
		// But it isn't because we let Mopidy/MPD search for anything they support because otherwise we
		// have to duplicate their entire database, which is daft.
		// This function was written before I realised that... :)
		// It's still used for searches where we're only looking for tags and/or ratings in conjunction with
		// any of the above terms, because mopidy often returns incomplete search results.

		$parameters = array();
		$qstring = "SELECT t.*, al.*, a1.*, a2.Artistname AS AlbumArtistName, Genre ";
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
				$terms['rating'][0].") AS rat ON rat.TTindex = t.TTindex ";
		}
		$qstring .= "LEFT JOIN Genretable USING (Genreindex) ";
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
		if (array_key_exists('trackartist', $terms)) {
			// Bit of a hack but faveFinder uses different search terms. Why?
			$searchmap = [
				'trackartist' => 'a1.Artistname',
				'Title' => 't.Title',
				'Album' => 'al.Albumname'
			];
		} else {
			$searchmap = [
				'genre' => 'Genre',
				'artist' => 'a1.Artistname',
				'album' =>  'al.Albumname',
				'title' => 't.Title',
				'file' => 't.Uri',
				'albumartist' => 'a2.Artistname',
				// These 3 come from faveFinder
			];
		}
		foreach ($searchmap as $t => $d) {
			if (array_key_exists($t, $terms) && $terms[$t]) {
				$qstring .= 'AND (';
				$qstring .= $this->format_for_search($terms[$t],$d, $parameters);
				// $qstring .= ' OR '.$this->format_for_search2($terms[$t],$d, $parameters);
				$qstring .= ') ';
			}
		}

		if (array_key_exists('any', $terms)) {
			$qstring .= ' AND (';
			$bunga = array();
			foreach ($terms['any'] as $tim) {
				$t = explode(' ',$tim);
				foreach ($t as $tom) {
					foreach ($searchmap as $d) {
						$bunga[] = $this->format_for_search(array($tom), $d, $parameters);
						// $bunga[] = $this->format_for_search2(array($tom), $d, $parameters);
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

		if (is_array($domains) && count($domains) > 0) {
			$qstring .= "AND (";
			$domainterms = array();
			foreach ($domains as $dom) {
				$parameters[] = trim($dom)."%";
				$domainterms[] = "t.Uri LIKE ?";
			}
			$qstring .= implode(" OR ",$domainterms);
			$qstring .= ")";
		}

		logger::mark("DB SEARCH", "String", $qstring);
		logger::mark("DB SEARCH", "Parameters", $parameters);

		$result = $this->sql_prepare_query(false, PDO::FETCH_OBJ, null, null, $qstring, $parameters);
		$fcount = count($result);
		$retval = [];
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
				'year' => $obj->Year,
				'Last-Modified' => $obj->LastModified,
				'Genre' => $obj->Genre,
				'trackartist' => $obj->Artistname
			);
			$filedata = array_replace(MPD_FILE_MODEL, $filedata);
			logger::trace("DB SEARCH", "Found :",$obj->Title,$obj->Uri,$obj->TTindex);
			if ($tree === false) {
				$this->generic_sql_query("UPDATE Tracktable SET isSearchResult = 1 WHERE TTindex = ".$obj->TTindex, true);
				$this->check_transaction();
			} else if ($tree === true) {
				// Slightly hacky, tree == true means "don't do a tree but also don't use the database"
				$retval[] = $filedata;
			} else {
				$tree->newItem($filedata);
			}
		}
		if ($tree === true) {
			return $retval;
		} else {
			return $fcount;
		}

	}

	private function format_for_search($terms, $s, &$parameters) {
		$a = array();
		$terms = getArray($terms);
		foreach ($terms as $i => $term) {
			$a[] = "".$s." LIKE ?";
			$parameters[] = '% '.trim($term). '%';
			$a[] = "".$s." LIKE ?";
			$parameters[] = '%'.trim($term). ' %';
		}
		$ret = implode(' OR ',$a);
		return $ret;
	}

	private function format_for_search2($terms, $s, &$parameters) {
		$a = array();
		$terms = getArray($terms);
		foreach ($terms as $i => $term) {
			$a[] = "LOWER(".$s.') = ?';
			$parameters[] = strtolower(trim($term));
		}
		$ret = implode(' OR ',$a);
		return $ret;
	}

}

?>
