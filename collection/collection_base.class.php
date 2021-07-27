<?php

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
// Tracks added by hand (by tagging or rating, via api/metadata/) must have LastModified as NULL
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

class collection_base extends database {

	protected $options = [
		'doing_search' => false,
		'trackbytrack' => true,
		'dbterms' => ['tag' => null, 'rating' => null]
	];

	private $find_album = true;
	private $find_album2;

	public function get_option($option) {
		return $this->options[$option];
	}

	// Looking up this way is hugely faster than looking up by Uri
	protected function get_extra_track_info(&$filedata) {
		// It's important to check the domain, since we're returning Albumindexes
		// and that can make eg spotify search results be put under local albums
		// We could also just not return the album index, but doing it this way speeds it up
		// and I don't *think* it'll cause a problem.
		$data = array();;

		$result = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, null,
			'SELECT
				Uri,
				TTindex,
				Disc,
				Artistname AS AlbumArtist,
				Albumtable.Image AS "X-AlbumImage",
				mbid AS MUSICBRAINZ_ALBUMID,
				Searched,
				IFNULL(Playcount, 0) AS Playcount,
				isAudiobook,
				Albumindex AS album_index,
				AlbumArtistindex AS albumartist_index,
				useTrackIms AS usetrackimages,
				Tracktable.Artistindex AS trackartist_index
			FROM
				Tracktable
				JOIN Albumtable USING (Albumindex)
				JOIN Artisttable ON Albumtable.AlbumArtistindex = Artisttable.Artistindex
				LEFT JOIN Playcounttable USING (TTindex)
			WHERE
			Hidden = 0
			AND Title = ?
			AND TrackNo = ?
			AND Albumname = ?
			AND Domain = ?',
			$filedata['Title'], $filedata['Track'], $filedata['Album'], $filedata['domain']
		);
		foreach ($result as $tinfo) {
			if ($tinfo['Uri'] == $filedata['file']) {
				if ($tinfo['isAudiobook'] > 0) {
					$tinfo['type'] = 'audiobook';
				}
				$tinfo['isAudiobook'] = null;
				$data = array_filter($tinfo, function($v) {
					if ($v === null || $v == '') {
						return false;
					}
					return true;
				});
				break;
			}
		}

		// Spotify. Fucking Spotify will CHANGE THE TITLE OF A TRACK. So I've added an index to the Uri
		// field, which I really didn't want to ever have to do. But we must follow down whatever shit-filled
		// drainpipe Spotify lead us into.
		if (count($data) == 0) {
			$result = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, null,
				'SELECT
					Title,
					Uri,
					TTindex,
					Disc,
					Artistname AS AlbumArtist,
					Albumtable.Image AS "X-AlbumImage",
					mbid AS MUSICBRAINZ_ALBUMID,
					Searched,
					IFNULL(Playcount, 0) AS Playcount,
					isAudiobook,
					useTrackIms AS usetrackimages,
					Albumindex AS album_index,
					AlbumArtistindex AS albumartist_index,
					Tracktable.Artistindex AS trackartist_index
				FROM
					Tracktable
					JOIN Albumtable USING (Albumindex)
					JOIN Artisttable ON Albumtable.AlbumArtistindex = Artisttable.Artistindex
					LEFT JOIN Playcounttable USING (TTindex)
				WHERE
				Hidden = 0
				AND Uri = ?',
				$filedata['file']
			);
			foreach ($result as $tinfo) {
				if ($tinfo['isAudiobook'] > 0) {
					$tinfo['type'] = 'audiobook';
				}
				$tinfo['isAudiobook'] = null;
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
			$result = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, null,
				'SELECT
					Albumtable.Image AS "X-AlbumImage",
					mbid AS MUSICBRAINZ_ALBUMID,
					Searched,
					useTrackIms AS usetrackimages,
					Albumindex AS album_index,
					AlbumArtistindex AS albumartist_index
				FROM
					Albumtable
					JOIN Artisttable ON Albumtable.AlbumArtistindex = Artisttable.Artistindex
					WHERE Albumname = ?
					AND Artistname = ?
					AND Domain = ?',
				$filedata['Album'], concatenate_artist_names($filedata['AlbumArtist']), $filedata['domain']
			);
			foreach ($result as $tinfo) {
				$data = array_filter($tinfo, function($v) {
					if ($v === null || $v == '') {
						return false;
					}
					return true;
				});
				break;
			}
		}

		if ($filedata['domain'] == 'youtube' && array_key_exists('AlbumArtist', $data)) {
			// Workaround a mopidy-youtube bug where sometimes it reports incorrect Artist info
			// if the item being added to the queue is not the result of a search. In this case we will
			// (almost) always have AlbumArtist info, so use that and it'll then stay consistent with the collection
			$data['Artist'] = $data['AlbumArtist'];
		}

		foreach (MPD_ARRAY_PARAMS as $p) {
			if (array_key_exists($p, $data) && $data[$p] !== null) {
				$data[$p] = getArray($data[$p]);
			}
		}

		return $data;
	}

	public function resetallsyncdata() {
		$this->generic_sql_query('UPDATE Playcounttable SET SyncCount = 0 WHERE TTindex > 0', true);
	}

	public function create_foundtracks() {
		// The order of these is VERY IMPORTANT!
		// Also the WHERE (thing) = 1 is important otherwise, at least with MySQL, it sets EVERY ROW to 0
		// whether or not it's already 0. That takes a very long time
		$this->generic_sql_query("UPDATE Tracktable SET justAdded = 0 WHERE justAdded = 1", true);
		$this->generic_sql_query("UPDATE Albumtable SET justUpdated = 0 WHERE justUpdated = 1", true);
	}

	public function check_artist($artist) {

		// check_artist:
		//		Checks for the existence of an artist by name in the Artisttable and creates it if necessary
		//		Returns: Artistindex

		$index = $this->sql_prepare_query(false, null, 'Artistindex', null, "SELECT Artistindex FROM Artisttable WHERE LOWER(Artistname) = LOWER(?)", $artist);
		if ($index === null) {
			$index = $this->create_new_artist($artist);
		}
		return $index;
	}

	protected function create_new_artist($artist) {

		// create_new_artist
		//		Creates a new artist
		//		Returns: Artistindex

		$retval = null;
		if ($this->sql_prepare_query(true, null, null, null, "INSERT INTO Artisttable (Artistname) VALUES (?)", $artist)) {
			$retval = $this->mysqlc->lastInsertId();
			logger::trace('BACKEND', "Created artist",$artist,"with Artistindex",$retval);
		}
		return $retval;
	}

	protected function best_value($a, $b, &$changed) {

		// best_value
		//		Used by check_album to determine the best value to use when updating album details
		//		$a is the current value, $b is the new value
		//		Returns: value

		if (!$b) {
			return $a;
		} else {
			if ($a != $b) {
				$changed = true;
				return $b;
			} else {
				return $a;
			}
		}
	}

	private function prepare_findalbum() {
		$this->find_album = $this->sql_prepare_query_later(
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
				AND Domain = ?"
		);

		$this->find_album2 = $this->sql_prepare_query_later(
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
				AND AlbumArtistindex = ?"
		);

	}

	public function check_album(&$data) {

		// check_album:
		//		Checks for the existence of an album and creates it if necessary
		//		Returns: Albumindex

		$index = null;
		$year = null;
		$img = null;
		$mbid = null;
		$obj = null;

		if ($this->find_album === true)
			$this->prepare_findalbum();

		$this->find_album->execute([$data['Album'], $data['albumartist_index'], $data['domain']]);
		$result = $this->find_album->fetchAll(PDO::FETCH_OBJ);
		$obj = array_shift($result);

		if (prefs::$prefs['preferlocalfiles'] && $this->options['trackbytrack'] && !$this->options['doing_search'] && $data['domain'] == 'local' && !$obj) {
			// Does the album exist on a different, non-local, domain? The checks above ensure we only do this
			// during a collection update
			$this->find_album2->execute([$data['Album'], $data['albumartist_index']]);
			$result = $this->find_album2->fetchAll(PDO::FETCH_OBJ);
			$obj = array_shift($result);
			if ($obj) {
				logger::mark('BACKEND', "Album ".$data['Album']." was found on domain ".$obj->Domain.". Changing to local");
				$index = $obj->Albumindex;
				if ($this->sql_prepare_query(true, null, null, null, "UPDATE Albumtable SET AlbumUri = NULL, Domain = ?, justUpdated = ? WHERE Albumindex = ?", 'local', 1, $index)) {
					$obj->AlbumUri = null;
					logger::debug('BACKEND', "   ...Success");
				} else {
					logger::warn('BACKEND', "   Album ".$data['Album']." update FAILED");
					return false;
				}
			}
		}

		if ($obj) {
			$changed = false;
			$index = $obj->Albumindex;
			$year = $this->best_value($obj->Year, $data['year'], $changed);
			$img = $this->best_value($obj->Image, $data['X-AlbumImage'], $changed);
			$uri = $this->best_value($obj->AlbumUri, $data['X-AlbumUri'], $changed);
			$mbid = $this->best_value($obj->mbid, $data['MUSICBRAINZ_ALBUMID'], $changed);
			if ($changed) {

				logger::mark('BACKEND', "Updating Details For Album ".$data['Album']." (index ".$index.")" );
				if (prefs::$prefs['debug_enabled'] > 6) {
					logger::trace('BACKEND', "  Date  :",$obj->Year,'->',$year);
					logger::trace('BACKEND', "  Image :",$obj->Image,'->',$img);
					logger::trace('BACKEND', "  Uri  :",$obj->AlbumUri,'->',$uri);
					logger::trace('BACKEND', "  MBID  :",$obj->mbid,'->',$mbid);
				}

				if ($this->sql_prepare_query(true, null, null, null,
					"UPDATE Albumtable SET
						Year = ?,
						Image = ?,
						AlbumUri = ?,
						mbid = ?,
						justUpdated = 1
					WHERE
						Albumindex = ?",
					$year, $img, $uri, $mbid, $index)) {
					logger::debug('BACKEND', "   ...Success");
				} else {
					logger::warn('BACKEND', "   Album ".$data['album']." update FAILED");
					return false;
				}
			}
		} else {
			$index = $this->create_new_album($data);
		}
		return $index;
	}

	protected function create_new_album($data) {

		// create_new_album
		//		Creates an album
		//		Returns: Albumindex

		$retval = null;
		if ($this->sql_prepare_query(true, null, null, null,
			"INSERT INTO
				Albumtable
				(Albumname, AlbumArtistindex, AlbumUri, Year, Searched, ImgKey, mbid, Domain, Image)
			VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?)",
			$data['Album'],
			$data['albumartist_index'],
			$data['X-AlbumUri'],
			$data['year'],
			$data['X-AlbumImage'] ? 1 : 0,
			$data['ImgKey'],
			$data['MUSICBRAINZ_ALBUMID'],
			$data['domain'],
			$data['X-AlbumImage']))
		{
			$retval = $this->mysqlc->lastInsertId();
			logger::trace('BACKEND', "Created Album ".$data['Album']." with Albumindex ".$retval);
		}
		return $retval;
	}

	protected function check_radio_source($data) {

		$index = $this->simple_query('Sourceindex', 'WishlistSourcetable', 'SourceUri', $data['streamuri'], null);
		if ($index === null) {
			logger::log('BACKEND', "Creating Wishlist Source",$data['streamname']);
			if ($this->sql_prepare_query(true, null, null, null,
			"INSERT INTO WishlistSourcetable (SourceName, SourceImage, SourceUri) VALUES (?, ?, ?)",
			$data['streamname'], $data['streamimage'], $data['streamuri']))
			{
				$index = $this->mysqlc->lastInsertId();
			}
		}
		return $index;
	}

	protected function remove_cruft() {
		logger::log('BACKEND', "Removing orphaned albums");
		$t = microtime(true);
		$this->generic_sql_query("DELETE FROM Albumtable WHERE Albumindex NOT IN (SELECT DISTINCT Albumindex FROM Tracktable)", true);
		$at = microtime(true) - $t;
		logger::info('BACKEND', " -- Removing orphaned albums took ".$at." seconds");

		logger::log('BACKEND', "Removing orphaned artists");
		$t = microtime(true);
		$this->delete_orphaned_artists();
		$at = microtime(true) - $t;
		logger::info('BACKEND', " -- Removing orphaned artists took ".$at." seconds");

		logger::log('BACKEND', "Tidying Metadata");
		$t = microtime(true);
		$this->generic_sql_query("DELETE FROM Ratingtable WHERE Rating = '0'", true);
		$this->generic_sql_query("DELETE FROM Ratingtable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable WHERE Hidden = 0)", true);
		$this->generic_sql_query("DELETE FROM TagListtable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable WHERE Hidden = 0)", true);
		$this->generic_sql_query("DELETE FROM Genretable WHERE Genreindex NOT IN (SELECT DISTINCT Genreindex FROM Tracktable)", true);
		// Temporary table needed otherwise we get a conflict with one of our triggers
		$this->generic_sql_query("CREATE TEMPORARY TABLE used_tags AS SELECT DISTINCT Tagindex FROM TagListtable", true);
		$this->generic_sql_query("DELETE FROM Tagtable WHERE Tagindex NOT IN (SELECT Tagindex FROM used_tags)", true);
		$this->generic_sql_query("DROP TABLE used_tags", true);
		$this->generic_sql_query("DELETE FROM Playcounttable WHERE Playcount = 0", true);
		$this->generic_sql_query("DELETE FROM Playcounttable WHERE TTindex NOT IN (SELECT TTindex FROM Tracktable)", true);

 		$at = microtime(true) - $t;
		logger::info('BACKEND', " -- Tidying metadata took ".$at." seconds");
	}

	protected function update_track_stats() {
		logger::mark('BACKEND', "Updating Track Stats");
		$t = microtime(true);
		$this->update_stat('ArtistCount',$this->get_artist_count(ADDED_ALL_TIME, 0));
		$this->update_stat('AlbumCount',$this->get_album_count(ADDED_ALL_TIME, 0));
		$this->update_stat('TrackCount',$this->get_track_count(ADDED_ALL_TIME, 0));
		$this->update_stat('TotalTime',$this->get_duration_count(ADDED_ALL_TIME, 0));
		$this->update_stat('BookArtists',$this->get_artist_count(ADDED_ALL_TIME, 1));
		$this->update_stat('BookAlbums',$this->get_album_count(ADDED_ALL_TIME, 1));
		$this->update_stat('BookTracks',$this->get_track_count(ADDED_ALL_TIME, 1));
		$this->update_stat('BookTime',$this->get_duration_count(ADDED_ALL_TIME, 1));
		$at = microtime(true) - $t;
		logger::info('BACKEND', "Updating Track Stats took ".$at." seconds");
	}

	protected function update_stat($item, $value) {
		$this->generic_sql_query("UPDATE Statstable SET Value='".$value."' WHERE Item='".$item."'", true);
	}

	protected function get_artist_count($range, $iab) {
		$qstring = "SELECT COUNT(*) AS NumArtists FROM (SELECT AlbumArtistindex FROM Albumtable
			INNER JOIN Tracktable USING (Albumindex) WHERE Uri IS NOT NULL
			AND Hidden = 0 AND isSearchResult < 2 AND isAudiobook";
		$qstring .= ($iab == 0) ? ' = ' : ' > ';
		$qstring .= '0 '.$this->track_date_check($range, 'a')." GROUP BY AlbumArtistindex) AS t";
		return $this->generic_sql_query($qstring, false, null, 'NumArtists', 0);
	}

	protected function get_album_count($range, $iab) {
		$qstring = "SELECT COUNT(*) AS NumAlbums FROM (SELECT Albumindex FROM Tracktable WHERE Uri IS NOT NULL
			AND Hidden = 0 AND isSearchResult < 2 AND isAudiobook";
		$qstring .= ($iab == 0) ? ' = ' : ' > ';
		$qstring .= '0 '.$this->track_date_check($range, 'a')." GROUP BY Albumindex) AS t";
		return $this->generic_sql_query($qstring, false, null, 'NumAlbums', 0);
	}

	protected function get_track_count($range, $iab) {
		$qstring = "SELECT COUNT(*) AS NumTracks FROM Tracktable WHERE Uri IS NOT NULL AND Hidden=0 AND isAudiobook";
		$qstring .= ($iab == 0) ? ' = ' : ' > ';
		$qstring .= '0 '.$this->track_date_check($range, 'a')." AND isSearchResult < 2";
		return $this->generic_sql_query($qstring, false, null, 'NumTracks', 0);
	}

	protected function get_duration_count($range, $iab) {
		$qstring = "SELECT SUM(Duration) AS TotalTime FROM Tracktable WHERE Uri IS NOT NULL AND Hidden=0 AND isAudiobook";
		$qstring .= ($iab == 0) ? ' = ' : ' > ';
		$qstring .= '0 '.$this->track_date_check($range, 'a')." AND isSearchResult < 2";
		$ac = $this->generic_sql_query($qstring, false, null, 'TotalTime', 0);
		if ($ac == '') {
			$ac = 0;
		}
		return $ac;
	}

	public function clear_wishlist() {
		return $this->generic_sql_query("DELETE FROM Tracktable WHERE Uri IS NULL", true);
	}

	protected function get_stat($item) {
		return $this->simple_query('Value', 'Statstable', 'Item', $item, 0);
	}

	public function collectionStats() {
		$html = '<div id="fothergill" class="brick brick_wide">';
		if (prefs::$prefs['collectionrange'] == ADDED_ALL_TIME) {
			$html .= $this->alistheader($this->get_stat('ArtistCount'),
								$this->get_stat('AlbumCount'),
								$this->get_stat('TrackCount'),
								format_time($this->get_stat('TotalTime'))
							);
		} else {
			$html .= $this->alistheader($this->get_artist_count(prefs::$prefs['collectionrange'], 0),
								$this->get_album_count(prefs::$prefs['collectionrange'], 0),
								$this->get_track_count(prefs::$prefs['collectionrange'], 0),
								format_time($this->get_duration_count(prefs::$prefs['collectionrange'], 0)));
		}
		$html .= '</div>';
		return $html;
	}

	public function audiobookStats() {
		$html = '<div id="mingus" class="brick brick_wide">';

		if (prefs::$prefs['collectionrange'] == ADDED_ALL_TIME) {
			$html .= $this->alistheader($this->get_stat('BookArtists'),
								$this->get_stat('BookAlbums'),
								$this->get_stat('BookTracks'),
								format_time($this->get_stat('BookTime'))
							);
		} else {
			$html .= $this->alistheader($this->get_artist_count(prefs::$prefs['collectionrange'], 1),
								$this->get_album_count(prefs::$prefs['collectionrange'], 1),
								$this->get_track_count(prefs::$prefs['collectionrange'], 1),
								format_time($this->get_duration_count(prefs::$prefs['collectionrange'], 1)));
		}
		$html .= "</div>";
		return $html;
	}

	public function searchStats() {
		$numartists = $this->generic_sql_query(
			"SELECT COUNT(*) AS NumArtists FROM (SELECT DISTINCT AlbumArtistIndex FROM Albumtable
			INNER JOIN Tracktable USING (Albumindex) WHERE Albumname IS NOT NULL AND Uri IS NOT
			NULL AND Hidden = 0 AND isSearchResult > 0) AS t", false, null, 'NumArtists', 0);

		$numalbums = $this->generic_sql_query(
			"SELECT COUNT(*) AS NumAlbums FROM (SELECT DISTINCT Albumindex FROM Albumtable
			INNER JOIN Tracktable USING (Albumindex) WHERE Albumname IS NOT NULL AND Uri IS NOT
			NULL AND Hidden = 0 AND isSearchResult > 0) AS t", false, null, 'NumAlbums', 0);

		$numtracks = $this->generic_sql_query(
			"SELECT COUNT(*) AS NumTracks FROM Tracktable WHERE Uri IS NOT NULL
			AND Hidden=0 AND isSearchResult > 0", false, null, 'NumTracks', 0);

		$numtime = $this->generic_sql_query(
			"SELECT SUM(Duration) AS TotalTime FROM Tracktable WHERE Uri IS NOT NULL AND
			Hidden=0 AND isSearchResult > 0", false, null, 'TotalTime', 0);

		$html =  '<div id="searchstats" class="brick brick_wide">';
		$html .= $this->alistheader($numartists, $numalbums, $numtracks, format_time($numtime));
		$html .= '</div>';
		return $html;

	}

	protected function alistheader($nart, $nalb, $ntra, $tim) {
		return '<div style="margin-bottom:4px">'.
		'<table width="100%" class="playlistitem">'.
		'<tr><td align="left">'.$nart.' '.language::gettext("label_artists").
		'</td><td align="right">'.$nalb.' '.language::gettext("label_albums").'</td></tr>'.
		'<tr><td align="left">'.$ntra.' '.language::gettext("label_tracks").
		'</td><td align="right">'.$tim.'</td></tr>'.
		'</table>'.
		'</div>';
	}

	public function get_album_details($albumindex) {
		return $this->generic_sql_query(
			"SELECT Albumname, Artistname, Image, AlbumUri, useTrackIms
			FROM Albumtable
			JOIN Artisttable ON Albumtable.AlbumArtistindex = Artisttable.Artistindex
			WHERE Albumindex = ".$albumindex );
	}

	public function dumpAlbums($which) {
		$sorter = choose_sorter_by_key($which);
		$lister = new $sorter($which);
		$lister->output_html();
	}

	public function check_url_against_database($url, $itags, $rating) {
		$qstring = "SELECT COUNT(TTindex) AS num FROM Tracktable ";
		$selectors = [];
		$tags = [];
		if ($rating !== null) {
			$qstring .= "JOIN Ratingtable USING (TTindex) ";
			$tags[] = (int) $rating;
			$selectors[] = 'Rating >= ?';
		}
		if ($itags !== null) {
			$qstring .= "JOIN TagListtable USING (TTindex) JOIN Tagtable USING (Tagindex) ";
			$tagterms = array();
			foreach ($itags as $tag) {
				$tags[] = trim($tag);
				$tagterms[] = "Tagtable.Name = ?";
			}
			$selectors[].= '('.implode(" OR ",$tagterms).')';
		}
		$tags[] = $url;
		$selectors[] = 'Uri = ?';
		$qstring .= ' WHERE '.implode(' AND ', $selectors);
		if ($this->sql_prepare_query(false, null, 'num', 0, $qstring, $tags)) {
			return true;
		}
		return false;
	}

	protected function check_genre($genre) {
		$index = $this->simple_query('Genreindex', 'Genretable', 'Genre', $genre, null);
		if ($index === null) {
			$this->sql_prepare_query(true, null, null, null, 'INSERT INTO Genretable (Genre) VALUES(?)', $genre);
			$index = $this->mysqlc->lastInsertId();
			logger::log('BACKEND', 'Created Genre '.$genre);
		}
		return $index;
	}

	public function check_stream_in_collection($url) {
		return $this->sql_prepare_query(false, PDO::FETCH_OBJ, null, null,
			"SELECT
				Tracktable.Title AS title,
				ta.Artistname AS artist,
				Tracktable.Duration AS duration,
				Albumname AS album,
				aa.Artistname AS albumartist,
				Image AS image,
				ImgKey AS imgkey
			FROM Tracktable
				JOIN Artisttable AS ta USING (Artistindex)
				JOIN Albumtable USING (Albumindex)
				JOIN Artisttable AS aa ON (Albumtable.AlbumArtistindex = aa.Artistindex)
			WHERE
				Hidden = 0 AND
				Uri = ?",
			$url
		);
	}

	public function find_radio_track_from_url($url) {
		return $this->sql_prepare_query(false, PDO::FETCH_OBJ, null, null,
									"SELECT
										Stationindex, PlaylistUrl, StationName, Image, PrettyStream
										FROM
										RadioStationtable JOIN RadioTracktable USING (Stationindex)
										WHERE TrackUri = ?",$url);
	}

	public function update_radio_station_name($info) {
		if ($info['streamid']) {
			logger::mark('BACKEND', "Updating Stationindex",$info['streamid'],"with new name",$info['name']);
			$this->sql_prepare_query(true, null, null, null, "UPDATE RadioStationtable SET StationName = ? WHERE Stationindex = ?",$info['name'],$info['streamid']);
		} else {
			$stationid = $this->check_radio_station($info['uri'], $info['name'], '');
			$this->check_radio_tracks($stationid, array(array('TrackUri' => $info['uri'], 'PrettyStream' => '')));
		}
	}

	public function check_radio_station($playlisturl, $stationname, $image) {
		$index = null;
		$index = $this->sql_prepare_query(false, null, 'Stationindex', false, "SELECT Stationindex FROM RadioStationtable WHERE PlaylistUrl = ?", $playlisturl);
		if ($index === false) {
			logger::mark('BACKEND', "Adding New Radio Station");
			logger::log('BACKEND', "  Name  :",$stationname);
			logger::log('BACKEND', "  Image :",$image);
			logger::log('BACKEND', "  URL   :",$playlisturl);
			if ($this->sql_prepare_query(true, null, null, null, "INSERT INTO RadioStationtable (IsFave, StationName, PlaylistUrl, Image) VALUES (?, ?, ?, ?)",
									0, trim($stationname), trim($playlisturl), trim($image))) {
				$index = $this->mysqlc->lastInsertId();
				logger::log('BACKEND', "Created new radio station with index ".$index);
			}
		} else {
			$this->sql_prepare_query(true, null, null, null, "UPDATE RadioStationtable SET StationName = ?, Image = ? WHERE Stationindex = ?",
				trim($stationname), trim($image), $index);
			logger::mark('BACKEND', "Found radio station",$stationname,"with index",$index);
		}
		return $index;
	}

	public function check_radio_tracks($stationid, $tracks) {
		$this->generic_sql_query("DELETE FROM RadioTracktable WHERE Stationindex = ".$stationid, true);
		foreach ($tracks as $track) {
			$index = $this->sql_prepare_query(false, null, 'Stationindex', false, "SELECT Stationindex FROM RadioTracktable WHERE TrackUri = ?", trim($track['TrackUri']));
			if ($index !== false) {
				logger::log('BACKEND', "  Track already exists for stationindex",$index);
				$stationid = $index;
			} else {
				logger::log('BACKEND', "  Adding New Track",$track['TrackUri'],"to station",$stationid);
				$this->sql_prepare_query(true, null, null, null, "INSERT INTO RadioTracktable (Stationindex, TrackUri, PrettyStream) VALUES (?, ?, ?)",
									$stationid, trim($track['TrackUri']), trim($track['PrettyStream']));
			}
		}
		return $stationid;
	}

	public function get_user_radio_streams() {
		return $this->generic_sql_query("SELECT * FROM RadioStationtable WHERE IsFave = 1 ORDER BY Number, StationName");
	}

	public function remove_user_radio_stream($x) {
		$this->generic_sql_query("UPDATE RadioStationtable SET IsFave = 0, Number = 65535 WHERE Stationindex = ".$x, true);
	}

	public function save_radio_order($order) {
		foreach ($order as $i => $o) {
			logger::trace('RADIO ORDER', 'Station',$o,'index',$i);
			$this->generic_sql_query("UPDATE RadioStationtable SET Number = ".$i." WHERE Stationindex = ".$o, true);
		}
	}

	public function add_fave_station($info) {
		if (array_key_exists('streamid', $info) && $info['streamid']) {
			logger::mark('BACKEND', "Updating StationIndex",$info['streamid'],"to be fave");
			$this->generic_sql_query("UPDATE RadioStationtable SET IsFave = 1 WHERE Stationindex = ".$info['streamid'], true);
			return true;
		}
		$stationindex = $this->check_radio_station($info['location'],$info['album'],$info['image']);
		$stationindex = $this->check_radio_tracks($stationindex, array(array('TrackUri' => $info['location'], 'PrettyStream' => $info['stream'])));
		$this->generic_sql_query("UPDATE RadioStationtable SET IsFave = 1 WHERE Stationindex = ".$stationindex, true);
	}

	public function num_collection_tracks($albumindex) {
		// Returns the number of tracks this album contains that were added by a collection update
		// (i.e. not added manually). We do this because editing year or album artist for those albums
		// won't hold across a collection update, so we just forbid it.
		return $this->generic_sql_query("SELECT COUNT(TTindex) AS cnt FROM Tracktable WHERE Albumindex = ".$albumindex." AND LastModified IS NOT NULL AND Hidden = 0 AND Uri IS NOT NULL AND isSearchResult < 2", false, null, 'cnt', 0);

	}

	public function album_is_audiobook($albumindex) {
		// Returns the maxiumum value of isAudiobook for a given album
		return $this->generic_sql_query("SELECT MAX(isAudiobook) AS cnt FROM Tracktable WHERE Albumindex = ".$albumindex." AND Hidden = 0 AND Uri IS NOT NULL AND isSearchResult < 2", false, null, 'cnt', 0);
	}

	public function get_imagesearch_info($key) {

		// Used by utils/getalbumcover.php to get album and artist names etc based on an Image Key

		$retval = array('artist' => null, 'album' => null, 'mbid' => null, 'albumpath' => null, 'albumuri' => null, 'trackuri' => null, 'dbimage' => null);
		$queries = array(
			"SELECT DISTINCT
				Artisttable.Artistname,
				Albumname,
				mbid,
				Albumindex,
				AlbumUri,
				isSearchResult,
				Uri,
				Image
			FROM
				Albumtable
				JOIN Artisttable ON AlbumArtistindex = Artisttable.Artistindex
				JOIN Tracktable USING (Albumindex)
				WHERE ImgKey = ? AND isSearchResult < 2 AND Hidden = 0",

			"SELECT DISTINCT
				Artisttable.Artistname,
				Albumname,
				mbid,
				Albumindex,
				AlbumUri,
				isSearchResult,
				Uri,
				Image
			FROM
				Albumtable
				JOIN Artisttable ON AlbumArtistindex = Artisttable.Artistindex
				JOIN Tracktable USING (Albumindex)
				WHERE ImgKey = ? AND isSearchResult > 1"
		);

		foreach ($queries as $query) {
			$result = $this->sql_prepare_query(false, PDO::FETCH_OBJ, null, null, $query, $key);

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
					$retval['albumpath'] = $this->get_album_directory($obj->Albumindex, $obj->AlbumUri);
				}
				if ($retval['albumuri'] == null || $retval['albumuri'] == "") {
					$retval['albumuri'] = $obj->AlbumUri;
				}
				if ($retval['trackuri'] == null) {
					$retval['trackuri'] = $obj->Uri;
				}
				if ($retval['dbimage'] == null) {
					$retval['dbimage'] = $obj->Image;
				}
				logger::log('BACKEND', "Found album",$retval['album'],",in database");
			}
		}
		return $retval;
	}

	public function get_album_directory($albumindex, $uri) {
		$retval = null;
		// Get album directory by using the Uri of one of its tracks, making sure we choose only local tracks
		if (getDomain($uri) == 'local') {
			$result = $this->generic_sql_query("SELECT Uri FROM Tracktable WHERE Albumindex = ".$albumindex);
			while (count($result) > 0 && $retval === null) {
				$obj2 = array_shift($result);
				if ($obj2['Uri']) {
					$retval = dirname($obj2['Uri']);
					$retval = preg_replace('#^local:track:#', '', $retval);
					$retval = preg_replace('#^file://#', '', $retval);
					$retval = preg_replace('#^beetslocal:\d+:'.prefs::$prefs['music_directory_albumart'].'/#', '', $retval);
					logger::log('BACKEND', "Got album directory using track Uri :",$retval);
				}
			}
		}
		return $retval;
	}

	public function update_image_db($key, $found, $imagefile) {
		$val = ($found) ? $imagefile : null;
		if ($this->sql_prepare_query(true, null, null, null, "UPDATE Albumtable SET Image = ?, Searched = 1 WHERE ImgKey = ?", $val, $key)) {
			logger::log('BACKEND', "    Database Image URL Updated");
		} else {
			logger::warn('BACKEND', "    Failed To Update Database Image URL",$val,$key);
		}
	}

	// function find_justadded_artists() {
	// 	return sql_get_column("SELECT DISTINCT AlbumArtistindex FROM Albumtable JOIN Tracktable USING (Albumindex) WHERE justAdded = 1", 0);
	// }

	// function find_stream_name_from_index($index) {
	// 	return simple_query('StationName', 'RadioStationtable', 'StationIndex', $index, '');
	// }

	public function update_stream_image($stream, $image) {
		$this->sql_prepare_query(true, null, null, null, "UPDATE RadioStationtable SET Image = ? WHERE StationName = ?",$image,$stream);
	}

	public function get_all_images() {
		return $this->generic_sql_query('SELECT Image, ImgKey FROM Albumtable', false, PDO::FETCH_OBJ);
	}

	public function cleanSearchTables() {
		// Clean up the database tables before performing a new search or updating the collection

		logger::mark('BACKEND', "Cleaning Search Results");
		// Any track that was previously hidden needs to be re-hidden
		$this->generic_sql_query("UPDATE Tracktable SET Hidden = 1, isSearchResult = 0 WHERE isSearchResult = 3", true);

		// Any track that was previously a '2' (added to database as search result) but now
		// has a playcount needs to become a zero and be hidden.
		$this->hide_played_tracks();

		// remove any remaining '2's
		$this->generic_sql_query("DELETE FROM Tracktable WHERE isSearchResult = 2", true);

		// Set '1's back to '0's
		$this->generic_sql_query("UPDATE Tracktable SET isSearchResult = 0 WHERE isSearchResult = 1", true);

		// This may leave some orphaned albums and artists
		$this->remove_cruft();

	}

	public function pull_wishlist($sortby) {
		$qstring = "SELECT
			IFNULL(r.Rating, 0) AS rating,
			".database::SQL_TAG_CONCAT." AS tags,
			tr.TTindex AS ttid,
			tr.Title AS title,
			tr.Duration AS time,
			tr.Albumindex AS albumindex,
			a.Artistname AS albumartist,
			a.Artistindex AS artistindex,
			tr.DateAdded AS DateAdded,
			ws.SourceName AS SourceName,
			ws.SourceImage AS SourceImage,
			ws.SourceUri AS SourceUri
			FROM
			Tracktable AS tr
			LEFT JOIN Ratingtable AS r ON tr.TTindex = r.TTindex
			LEFT JOIN TagListtable AS tl ON tr.TTindex = tl.TTindex
			LEFT JOIN Tagtable AS t USING (Tagindex)
			LEFT JOIN WishlistSourcetable AS ws USING (Sourceindex)
			JOIN Artisttable AS a ON (tr.Artistindex = a.Artistindex)
			WHERE
			tr.Uri IS NULL AND tr.Hidden = 0
			GROUP BY ttid
			ORDER BY ";

		switch ($sortby) {
			case 'artist':
				foreach (prefs::$prefs['artistsatstart'] as $a) {
					$qstring .= "CASE WHEN LOWER(albumartist) = LOWER('".$a."') THEN 1 ELSE 2 END, ";
				}
				if (count(prefs::$prefs['nosortprefixes']) > 0) {
					$qstring .= "(CASE ";
					foreach(prefs::$prefs['nosortprefixes'] AS $p) {
						$phpisshitsometimes = strlen($p)+2;
						$qstring .= "WHEN LOWER(albumartist) LIKE '".strtolower($p)." %' THEN LOWER(SUBSTR(albumartist,".
							$phpisshitsometimes.")) ";
					}
					$qstring .= "ELSE LOWER(albumartist) END)";
				} else {
					$qstring .= "LOWER(albumartist)";
				}
				$qstring .= ", DateAdded, SourceName";
				break;

			case 'date':
				$qstring .= "DateAdded, SourceName";
				break;

			case 'station':
				$qstring .= 'SourceName, DateAdded';
				break;

			default:
				$qstring .= "rating, DateAdded";
				break;

		}
		return $this->generic_sql_query($qstring);
	}

}

?>