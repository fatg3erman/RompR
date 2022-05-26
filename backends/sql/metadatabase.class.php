<?php

require_once ('getid3/getid3.php');

class metaDatabase extends collection_base {

	public $returninfo = [ 'dummy' => 'baby' ];

	public const NODATA = [
		'isSearchResult' => 4,
		'Rating' => 0,
		'Tags' => []
	];

	public function sanitise_data(&$data) {

		//
		// Make sure the data we're dealing with is ROMPR_FILE_MODEL and do some sanity
		// checks on it to make certain important stuff isn't missing
		//

		$data = array_replace(MPD_FILE_MODEL, ROMPR_FILE_MODEL, $data);
		if ($data['albumartist'] === null) {
			logger::warn('METADATA', 'WARNING : albumartist is not set!');
			$data['albumartist'] = $data['trackartist'];
		}
		if ($data['Disc'] === null) {
			logger::warn('METADATA', 'WARNING : Disc is not set!');
			$data['Disc'] = 1;
		}
		if ($data['Genre'] === null) {
			logger::warn('METADATA', 'WARNING : Genre is not set!');
			$data['Genre'] = 'None';
		}
		if ($data['X-AlbumImage'] && substr($data['X-AlbumImage'],0,4) == "http") {
			logger::warn('METADATA', 'WARNING : Uncached remote image!');
			$data['X-AlbumImage'] = "getRemoteImage.php?url=".rawurlencode($data['X-AlbumImage']);
		}
		if ($data['ImgKey'] === null) {
			$albumimage = new baseAlbumImage(array(
				'artist' => imageFunctions::artist_for_image($data['type'], $data['albumartist']),
				'album' => $data['Album']
			));
			$data['ImgKey'] = $albumimage->get_image_key();
		}
		if ($data['year'] && !preg_match('/^\d\d\d\d$/', $data['year'])) {
			// If this has come from something like an 'Add Spotify Album To Collection' the year tag won't
			// exist but the Date tag might.
			logger::log('METADATA', 'Year is not a 4 digit year, analyzing Date field instead');
			$data['year'] = getYear($data['Date']);
		}
		// Very Important. The default in MPD_FILE_MODEL is 0 because that works for collection building
		$data['Last-Modified'] = null;
	}

	public function set($data) {

		//
		// Set Metadata either on a new or an existing track
		//

		if ($data['trackartist'] === null || $data['Title'] === null ) {
			logger::error("SET", "Something is not set");
			header('HTTP/1.1 400 Bad Request');
			print json_encode(['error' => 'Artist or Title not set']);
			exit(0);
		}

		$ttids = $this->find_item($data, $this->forced_uri_only($data['urionly'], $data['domain']));

		$newttids = array();
		$dummytributes = false;
		foreach ($ttids as $ttid) {
			//
			// If we found a track, check to see if it's in the wishlist and remove it if it is because
			// no longer want it, but preserve its metadata.
			//
			if (($dummytributes = $this->track_is_wishlist($ttid)) === false)
				$newttids[] = $ttid;

			// Hackery-wackery. In the event his request has come in from unplayable tracks to replace
			// an unplayable track, make sure we set it as playable. Does no harm to do this on any track
			$this->sql_prepare_query(true, null, null, null,
				"UPDATE Tracktable SET LinkChecked = 0 WHERE TTindex = ?",
				$ttid
			);

		}
		$ttids = $newttids;
		if (count($ttids) == 0 && $data['urionly'] && $dummytributes === false) {
			//
			// In the case where urionly is set, we won't have matched on a wishlist track so check for one of
			// those here now.
			//
			$dummytributes = $this->check_for_wishlist_track($data);
		}

		//
		// If we don't have any attributes, set a dummy attribute because this will result in the track
		// being unhidden/un-searchified if it already exists.
		// But in the case where the above wishlist checks returned some metatdata, use that instead
		// to make sure we transfer metadata from the wishlist to our new track
		//
		if ($data['attributes'] == null) {
			$data['attributes'] = ($dummytributes === false) ? [['attribute' => 'Rating', 'value'=> 0]] : $dummytributes;
		}

		if (count($ttids) == 0) {
			$ttids[0] = $this->create_new_track($data);
			logger::log("SET", "Created New Track with TTindex ".$ttids[0]);
		}

		if (count($ttids) > 0 && $this->doTheSetting($ttids, $data['attributes'], $data['file'])) {
			logger::debug('SET', 'Set command success');
		} else {
			logger::warn("SET", "Set Command failed", print_r($ttids, true));
			header('HTTP/1.1 417 Expectation Failed');
			$this->returninfo['error'] = 'TTindex not found';
		}
	}

	public function inc($data) {

		//
		// NOTE : 'inc' does not do what you might expect.
		// This is not an 'increment' function, it still does a SET but it will create a hidden track
		// if the track can't be found, compare to SET which creates a new unhidden track.
		//

		if ($data['trackartist'] === null || $data['Title'] === null ||	$data['attributes'] == null) {
			logger::error("INC", "Something is not set",$data);
			header('HTTP/1.1 400 Bad Request');
			print json_encode(array('error' => 'Artist or Title or Attributes not set'));
			exit(0);
		}

		$ttids = $this->find_item($data, $this->forced_uri_only(false, $data['domain']));
		if (count($ttids) == 0) {
			logger::trace("INC", "Doing an INCREMENT action - Found NOTHING so creating hidden track");
			$data['hidden'] = 1;
			$ttids[0] = $this->create_new_track($data);
		}

		$this->checkLastPlayed($data);

		foreach ($ttids as $ttid) {
			logger::trace("INC", "Doing an INCREMENT action - Found TTID ",$ttid);
			foreach ($data['attributes'] as $pair) {
				logger::log("INC", "(Increment) Setting",$pair["attribute"],"to",$pair["value"],"on",$ttid);
				$this->increment_value($ttid, $pair["attribute"], $pair["value"], $data['lastplayed']);
				$this->up_next_hack_for_audiobooks($ttid);
			}
			$this->returninfo['metadata'] = $this->get_all_data($ttid);
		}
		return $ttids;
	}

	private function up_next_hack_for_audiobooks($ttid) {
		$this->sql_prepare_query(true, null, null, null,
			"UPDATE Bookmarktable SET Bookmark = 0 WHERE TTindex = ? AND Name = ?",
			$ttid,
			'Resume'
		);
		$this->sql_prepare_query(true, null, null, null,
			"UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex IN
			(SELECT Albumindex FROM Tracktable WHERE TTindex = ? AND isAudiobook = ?)",
			$ttid, 1
		);
	}

	private function checkLastPlayed(&$data) {
		//
		// Return a LastPlayed value suitable for inerting into the database
		// either from the data or using the current timestamp
		//
		if ($data['lastplayed'] !== null && is_numeric($data['lastplayed'])) {
			// Convert timestamp from LastFM into MySQL TIMESTAMP format
			$data['lastplayed'] = date('Y-m-d H:i:s', $data['lastplayed']);
		} else if ($data['lastplayed'] !== null && preg_match('/\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d/', $data['lastplayed'])) {
			// Already in datestamp format as it would be eg when restoring a backup
		} else {
			$data['lastplayed'] = date('Y-m-d H:i:s');
			logger::log('INC', 'Setting lastplayed to',$data['lastplayed']);
		}
	}

	public function syncinc($data) {

		//
		// This is for syncing Last.FM playcounts
		//

		$this->sanitise_data($data);

		$ttids = $this->find_item($data, false);
		if (count($ttids) == 0) {
			$ttids = $this->inc($data);
			$this->resetSyncCounts($ttids);
			return true;
		}

		$this->checkLastPlayed($data);
		foreach ($ttids as $ttid) {
			logger::log("SYNCINC", "Doing a SYNC action on TTID ".$ttid,'LastPlayed is',$data['lastplayed']);
			$rowcount = $this->generic_sql_query("UPDATE Playcounttable SET SyncCount = SyncCount - 1 WHERE TTindex = ".$ttid." AND SyncCount > 0",
				false, null, null, null, true);
			if ($rowcount > 0) {
				logger::trace("SYNCINC", "  Decremented sync counter for this track");
			} else {
				$clp = $this->simple_query('LastPlayed', 'Playcounttable', 'TTindex', $ttid, null);
				if ($clp === null) {
					logger::trace('SYNCINC', 'Track does not currently have a playcount');
					$metadata = $this->get_all_data($ttid);
					$this->increment_value($ttid, 'Playcount', 1, $data['lastplayed']);
				} else {
					logger::trace('SYNCINC', 'Incrementing Playcount for this track');
					$this->sql_prepare_query(true, null, null, null,
						"UPDATE Playcounttable SET Playcount = Playcount + 1 WHERE TTindex = ?",
						$ttid
					);
					if (strtotime($clp) < strtotime($data['lastplayed'])) {
						logger::trace('SYNCINC', 'Updating LastPlayed for this track');
						$this->sql_prepare_query(true, null, null, null,
							"UPDATE Playcounttable SET LastPlayed = ? WHERE TTindex = ?",
							$data['lastplayed'],
							$ttid
						);
					}
				}
				// At this point, SyncCount must have been zero but the update will have incremented it again,
				// because of the trigger. resetSyncCounts takes care of this;
				$this->resetSyncCounts(array($ttid));
			}
		}

		// Let's just see if it's a podcast track and mark it as listened.
		// This won't always work, as scrobbles are often not what's in the RSS feed, but we can but do our best

		$boobly = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, array(),
			"SELECT PODTrackindex FROM PodcastTracktable JOIN Podcasttable USING (PODindex)
			WHERE (Podcasttable.Artist LIKE ? OR PodcastTracktable.Artist LIKE ?)
			AND Podcasttable.Title LIKE ?
			AND PodcastTracktable.Title LIKE ?",
			$data['trackartist'],
			$data['trackartist'],
			$data['Album'],
			$data['Title']
		);
		$podtrack = (count($boobly) == 0) ? null : $boobly[0]['PODTrackindex'];
		if ($podtrack !== null) {
			logger::trace('SYNCINC', 'This track matches a Podcast episode');
			$this->sql_prepare_query(true, null, null, null,
				"UPDATE PodcastTracktable SET Listened = ?, New = ? WHERE PODTrackindex = ?",
				1,
				0,
				$podtrack
			);
		}
	}

	public function resetSyncCounts($ttids) {
		foreach ($ttids as $ttid) {
			$this->generic_sql_query("UPDATE Playcounttable SET SyncCount = 0 WHERE TTindex = ".$ttid, true);
		}
	}

	public function remove($data) {

		//
		// Remove a tag from a track
		//

		if ($data['trackartist'] === null || $data['Title'] === null) {
			header('HTTP/1.1 400 Bad Request');
			print json_encode(array('error' => 'Artist or Title not set'));
			exit(0);
		}
		$ttids = $this->find_item($data, $this->forced_uri_only($data['urionly'], $data['domain']));
		if (count($ttids) > 0) {
			foreach ($ttids as $ttid) {
				$result = true;
				foreach ($data['attributes'] as $pair) {
					logger::log("REMOVE", "Removing",$pair);
					$r = $this->remove_tag($ttid, $pair["value"]);
					if ($r == false) {
						logger::warn("REMOVE", "FAILED Removing",$pair);
						$result = false;
					}
				}
				if ($result) {
					$this->returninfo['metadata'] = $this->get_all_data($ttid);
				} else {
					header('HTTP/1.1 417 Expectation Failed');
					$this->returninfo['error'] = 'Removing attributes failed';
				}
			}
		} else {
			logger::warn("USERRATING", "TTID Not Found");
			header('HTTP/1.1 417 Expectation Failed');
			$this->returninfo['error'] = 'TTindex not found';
		}
	}

	public function get($data) {

		//
		// Get all matadata for a track
		//

		if ($data['trackartist'] === null || $data['Title'] === null) {
			header('HTTP/1.1 400 Bad Request');
			print json_encode(array('error' => 'Artist or Title not set'));
			exit(0);
		}
		$ttids = $this->find_item($data, $this->forced_uri_only(false, $data['domain']));
		if (count($ttids) > 0) {
			$ttid = array_shift($ttids);
			$this->returninfo = $this->get_all_data($ttid);
		} else {
			$this->returninfo = self::NODATA;
		}
	}

	public function setalbummbid($data) {
		$ttids = $this->find_item($data, $this->forced_uri_only(false, $data['domain']));
		if (count($ttids) > 0) {
			foreach ($ttids as $ttid) {
				logger::trace("BACKEND", "Updating album MBID ".$data['attributes']." from TTindex ".$ttid);
				$albumindex = $this->simple_query('Albumindex', 'Tracktable', 'TTindex', $ttid, null);
				logger::debug("BACKEND", "   .. album index is ".$albumindex);
				$this->sql_prepare_query(true, null, null, null, "UPDATE Albumtable SET mbid = ? WHERE Albumindex = ? AND mbid IS NULL",$data['attributes'],$albumindex);
			}
		}
	}

	public function updateAudiobookState($data) {
		$ttids = $this->find_item($data, $this->forced_uri_only(false, $data['domain']));
		if (count($ttids) > 0) {
			foreach ($ttids as $ttid) {
				logger::log('SQL', 'Setting Audiobooks state for TTIndex',$ttid,'to',$data['isaudiobook']);
				$this->sql_prepare_query(true, null, null, null, 'UPDATE Tracktable SET isAudiobook = ? WHERE TTindex = ?', $data['isaudiobook'], $ttid);
			}
		}
	}

	public function cleanup($data) {
		logger::info("CLEANUP", "Doing Database Cleanup And Stats Update");
		$this->remove_cruft();
		$this->generic_sql_query("DELETE FROM Bookmarktable WHERE Bookmark = 0");
		$this->update_track_stats();
		$this->doCollectionHeader();
	}

	public function amendalbum($data) {
		if ($data['album_index'] !== null && $this->amend_album($data['album_index'], $data['albumartist'], $data['year'])) {
		} else {
			header('HTTP/1.1 400 Bad Request');
			$this->returninfo['error'] = 'That just did not work';
		}
	}

	public function deletealbum($data) {
		if ($data['album_index'] !== null && $this->delete_album($data['album_index'])) {
		} else {
			header('HTTP/1.1 400 Bad Request');
			$this->returninfo['error'] = 'That just did not work';
		}
	}

	public function setasaudiobook($data) {
		if ($data['album_index'] !== null && $this->set_as_audiobook($data['album_index'], $data['value'])) {
		} else {
			header('HTTP/1.1 400 Bad Request');
			$this->returninfo['error'] = 'That just did not work';
		}
	}

	public function usetrackimages($data) {
		if ($data['album_index'] !== null && $this->use_trackimages($data['album_index'], $data['value'])) {
		} else {
			header('HTTP/1.1 400 Bad Request');
			$this->returninfo['error'] = 'That just did not work';
		}
	}

	public function delete($data) {
		$ttids = $this->find_item($data, true);
		if (count($ttids) == 0) {
			header('HTTP/1.1 400 Bad Request');
			$this->returninfo['error'] = 'TTindex not found';
		} else {
			$this->delete_track(array_shift($ttids));
		}
	}

	public function deletewl($data) {
		$this->delete_track($data['wltrack']);
	}

	public function deleteid($data) {
		$this->delete_track($data['ttid']);
	}

	public function clearwishlist() {
		logger::log("MONKEYS", "Removing Wishlist Tracks");
		if ($this->clear_wishlist()) {
			logger::debug("MONKEYS", " ... Success!");
		} else {
			logger::warn("MONKEYS", "Failed removing wishlist tracks");
		}
	}

	// Private Functions

	private function geturisfordir($data) {
		$player = new player();
		$uris = $player->get_uris_for_directory($data['file']);
		$ttids = array();
		foreach ($uris as $uri) {
			$t = $this->sql_prepare_query(false, PDO::FETCH_COLUMN, 0, null, "SELECT TTindex FROM Tracktable WHERE Uri = ?", $uri);
			$ttids = array_merge($ttids, $t);
		}
		return $ttids;
	}

	private function geturis($data) {
		$uris = $this->getItemsToAdd($data['file'], "");
		$ttids = array();
		foreach ($uris as $uri) {
			$uri = trim(substr($uri, strpos($uri, ' ')+1, strlen($uri)), '"');
			$r = $this->sql_prepare_query(false, PDO::FETCH_COLUMN, 0, null, "SELECT TTindex FROM Tracktable WHERE Uri = ?", $uri);
			$ttids = array_merge($ttids, $t);
		}
		return $ttids;
	}

	private function print_debug_ttids($ttids, $s) {
		$time = time() - $s;
		if (count($ttids) > 0) {
			logger::info("TIMINGS", "    Found TTindex(es)",$ttids,"in",$time,"seconds");
		}
	}

	private function find_item($data,$urionly) {

		// $this->find_item
		//		Looks for a track in the database based on uri, title, artist, album, and albumartist or
		//		combinations of those
		//		Returns: Array of TTindex

		// find_item is used to find tracks on which to update or display metadata.
		// It is NOT used when the collection is created

		// When Setting Metadata we do not use a URI because we might have mutliple versions of the
		// track in the database or someone might be rating a track from Spotify that they already have
		// in Local. So in this case we check using an increasingly wider check to find the track,
		// returning as soon as one of these produces matches.
		//		First by Title, TrackNo, AlbumArtist and Album
		//		Third by Track, Album Artist, and Album
		// 		Then by Track, Track Artist, and Album
		//		Then by Track, Artist, and Album NULL (meaning wishlist)
		// We return ALL tracks found, because you might have the same track on multiple backends,
		// and set metadata on them all.
		// This means that when getting metadata it doesn't matter which one we match on.
		// When we Get Metadata we do supply a URI BUT we don't use it if we have one, just because.
		// $urionly can be set to force looking up only by URI. This is used by when we need to import a
		// specific version of the track  - currently from either the Last.FM importer or when we add a
		// spotify album to the collection

		// If we don't supply an album to this function that's because we're listening to the radio.
		// In that case we look for a match where there is something in the album field and then for
		// where album is NULL

		// FIXME! There is one scenario where the above fails.
		// If you tag or rate a track, and then add it to the collection again from another backend
		// later on, the rating doesn't get picked up by the new copy.
		// Looking everything up by name/album/artist (i.e. ignoring the URI in $this->find_item)
		// doesn't fix this because the collection display still doesn't show the rating as that's
		// looked up by TTindex

		$start_time = time();
		logger::mark("FIND ITEM", "Looking for item ".$data['Title']);
		$ttids = array();

		if ($urionly && $data['file']) {
			logger::log("FIND ITEM", "  Trying by URI ".$data['file']);
			$t = $this->sql_prepare_query(false, PDO::FETCH_COLUMN, 0, null, "SELECT TTindex FROM Tracktable WHERE Uri = ?", $data['file']);
			$ttids = array_merge($ttids, $t);
		}

		if ($data['trackartist'] == null || $data['Title'] == null || ($urionly && $data['file'])) {
			$this->print_debug_ttids($ttids, $start_time);
			return $ttids;
		}

		if (count($ttids) == 0) {
			if ($data['Album']) {
				if ($data['albumartist'] !== null && $data['Track'] != 0) {
					logger::log("FIND ITEM", "  Trying by albumartist",$data['albumartist'],"album",$data['Album'],"title",$data['Title'],"track number",$data['Track']);
					$t = $this->sql_prepare_query(false, PDO::FETCH_COLUMN, 0, null,
						"SELECT
							TTindex
						FROM
							Tracktable JOIN Albumtable USING (Albumindex)
							JOIN Artisttable ON Albumtable.AlbumArtistindex = Artisttable.Artistindex
						WHERE
							Title = ?
							AND Artistname = ?
							AND Albumname = ?
							AND TrackNo = ?",
						$data['Title'], $data['albumartist'], $data['Album'], $data['Track']);
					$ttids = array_merge($ttids, $t);
				}

				if (count($ttids) == 0 && $data['albumartist'] !== null) {
					logger::log("FIND ITEM", "  Trying by albumartist",$data['albumartist'],"album",$data['Album'],"and title",$data['Title']);
					$t = $this->sql_prepare_query(false, PDO::FETCH_COLUMN, 0, null,
						"SELECT
							TTindex
						FROM
							Tracktable JOIN Albumtable USING (Albumindex)
							JOIN Artisttable ON Albumtable.AlbumArtistindex = Artisttable.Artistindex
						WHERE
							Title = ?
							AND Artistname = ?
							AND Albumname = ?",
						$data['Title'], $data['albumartist'], $data['Album']);
					$ttids = array_merge($ttids, $t);
				}

				if (count($ttids) == 0 && ($data['albumartist'] == null || $data['albumartist'] == $data['trackartist'])) {
					logger::log("FIND ITEM", "  Trying by artist",$data['trackartist'],",album",$data['Album'],"and title",$data['Title']);
					$t = $this->sql_prepare_query(false, PDO::FETCH_COLUMN, 0, null,
						"SELECT
							TTindex
						FROM
							Tracktable JOIN Artisttable USING (Artistindex)
							JOIN Albumtable USING (Albumindex)
						WHERE
							Title = ?
							AND Artistname = ?
							AND Albumname = ?", $data['Title'], $data['trackartist'], $data['Album']);
					$ttids = array_merge($ttids, $t);
				}

				// Finally look for Uri NULL which will be a wishlist item added via a radio station
				if (count($ttids) == 0) {
					logger::log("FIND ITEM", "  Trying by (wishlist) artist",$data['trackartist'],"and title",$data['Title']);
					$t = $this->sql_prepare_query(false, PDO::FETCH_COLUMN, 0, null,
						"SELECT
							TTindex
						FROM
							Tracktable JOIN Artisttable USING (Artistindex)
						WHERE
							Title = ?
							AND Artistname = ?
							AND Uri IS NULL",
						$data['Title'], $data['trackartist']);
					$ttids = array_merge($ttids, $t);
				}
			} else {
				// No album supplied - ie this is from a radio stream. First look for a match where
				// there is something in the album field
				logger::log("FIND ITEM", "  Trying by artist",$data['trackartist'],"Uri NOT NULL and title",$data['Title']);
				$t = $this->sql_prepare_query(false, PDO::FETCH_COLUMN, 0, null,
					"SELECT
						TTindex
					FROM
						Tracktable JOIN Artisttable USING (Artistindex)
					 WHERE
						Title = ?
						AND Artistname = ?
						AND Uri IS NOT NULL", $data['Title'], $data['trackartist']);
				$ttids = array_merge($ttids, $t);

				if (count($ttids) == 0) {
					logger::log("FIND ITEM", "  Trying by (wishlist) artist",$data['trackartist'],"and title",$data['Title']);
					$t = $this->sql_prepare_query(false, PDO::FETCH_COLUMN, 0, null,
						"SELECT
							TTindex
						FROM
							Tracktable JOIN Artisttable USING (Artistindex)
						WHERE
							Title = ?
							AND Artistname = ?
							AND Uri IS NULL", $data['Title'], $data['trackartist']);
					$ttids = array_merge($ttids, $t);
				}
			}
		}

		if (count($ttids) == 0 && !$urionly && $data['file']) {
			// Just in case. Sometimes Spotify changes titles on us.
			logger::log("FIND ITEM", "  Trying by URI ".$data['file']);
			$t = $this->sql_prepare_query(false, PDO::FETCH_COLUMN, 0, null, "SELECT TTindex FROM Tracktable WHERE Uri = ?", $data['file']);
			$ttids = array_merge($ttids, $t);
		}

		$this->print_debug_ttids($ttids, $start_time);
		return $ttids;
	}

	private function increment_value($ttid, $attribute, $value, $lp) {

		// Increment_value doesn't 'increment' as such - it's used for setting values on tracks without
		// unhiding them. It's used for Playcount, which was originally an 'increment' type function but
		// that changed because multiple rompr instances cause multiple increments

		$current = $this->simple_query($attribute, $attribute.'table', 'TTindex', $ttid, null);
		if ($current !== null && $current >= $value) {
			// Don't INC if it has already been INCed, because this changes the LastPlayed time. This happens if romonitor has already updated
			// it and then we return to a browser, say on a mobile device, and that updates it again. The nowplaying_hack function
			// still ensures that the album gets marked as modified so that the UI updates. The UI will always update playcount before
			// romonitor does if the UI is open.
			logger::log('INCREMENT', 'Not incrementing',$attribute,'for TTindex',$ttid,'because current value',$current,'is >= new value',$value);
			return true;
		}

		logger::log("INCREMENT", "Setting",$attribute,"to",$value,'and lastplayed to',$lp,"for TTID",$ttid);
		if ($this->sql_prepare_query(true, null, null, null, "REPLACE INTO ".$attribute."table (TTindex, ".$attribute.", LastPlayed) VALUES (?, ?, ?)", $ttid, $value, $lp)) {
			logger::debug("INCREMENT", " .. success");
			// if ($attribute == 'Playcount' && $this->simple_query('isAudiobook', 'Tracktable', 'TTindex', $ttid, 0) == 1) {
			// 	logger::log('INCREMENT', 'Resetting resume position for TTID',$ttid);
			// 	// Always do this even if there is no stored progress to reset - it triggers the Progress trigger which makes the UI update
			// 	// so that the Up Next marker moves
			// 	$this->sql_prepare_query(true, null, null, null, 'REPLACE INTO Bookmarktable (TTindex, Bookmark, Name) VALUES (? ,?, ?)', $ttid, 0, 'Resume');
			// }
		} else {
			logger::warn("INCREMENT", "FAILED Setting",$attribute,"to",$value,"for TTID",$ttid);
			return false;
		}
		return true;

	}

	private function set_attribute($ttid, $attribute, $value) {

		// set_attribute
		//		Sets an attribute (Rating, Bookmark etc) on a TTindex.
		//		For this to work, the table must have columns TTindex, $attribute and must be called [$attribute]table, eg $attribute = Rating on Ratingtable

		if (is_array($value)) {
			// If $value is an array, it's expected to be used in a table with columns TTindex, $attribute, Name (eg $attribute = Bookmark)
			// The array should contain entries [Value for $attribute, Value for Name]
			// It's a bit of a hack but it works so far
			logger::log("ATTRIBUTE", "Setting",$attribute,"to",$value[0],$value[1],"on",$ttid);
			array_unshift($value, $ttid);
			if ($this->sql_prepare_query(true, null, null, null, "REPLACE INTO ".$attribute."table (TTindex, ".$attribute.", Name) VALUES (?, ?, ?)", $value)) {
				logger::debug("ATTRIBUTE", "  .. success");
			} else {
				logger::warn("ATTRIBUTE", "FAILED Setting",$attribute,"to",print_r($value, true));
				return false;
			}
		} else {
			logger::log("ATTRIBUTE", "Setting",$attribute,"to",$value,"on",$ttid);
			if ($this->sql_prepare_query(true, null, null, null, "REPLACE INTO ".$attribute."table (TTindex, ".$attribute.") VALUES (?, ?)", $ttid, $value)) {
				logger::debug("ATTRIBUTE", "  .. success");
			} else {
				logger::warn("ATTRIBUTE", "FAILED Setting",$attribute,"to",$value,"on",$ttid);
				return false;
			}
		}
		return true;
	}

	private function doTheSetting($ttids, $attributes, $uri) {
		$result = true;
		if ($attributes !== null) {
			logger::debug("USERRATING", "Setting attributes");
			foreach($ttids as $ttid) {
				foreach ($attributes as $pair) {
					logger::log("USERRATING", "Setting",$pair["attribute"],"to",$pair['value'],"on TTindex",$ttid);
					switch ($pair['attribute']) {
						case 'Tags':
							$result = $this->addTags($ttid, $pair['value']);
							break;

						default:
							$result = $this->set_attribute($ttid, $pair["attribute"], $pair["value"]);
							break;
					}
					if (!$result) { break; }
				}
				$this->check_audiobook_status($ttid);
				if ($uri) {
					$this->returninfo['metadata'] = $this->get_all_data($ttid);
				}
			}
		}
		return $result;
	}

	private function check_audiobook_status($ttid) {
		$albumindex = $this->generic_sql_query("SELECT Albumindex FROM Tracktable WHERE TTindex = ".$ttid, false, null, 'Albumindex', null);
		if ($albumindex !== null) {
			$sorter = choose_sorter_by_key('zalbum'.$albumindex);
			$lister = new $sorter('zalbum'.$albumindex);
			if ($lister->album_trackcount($albumindex) > 0) {
				logger::log('USERRATING', 'Album '.$albumindex.' is an audiobook, updating track audiobook state');
				$this->generic_sql_query("UPDATE Tracktable SET isAudiobook = 2 WHERE TTindex = ".$ttid);
			}
		}
	}

	private function addTags($ttid, $tags) {

		// addTags
		//		Add a list of tags to a TTindex

		foreach ($tags as $tag) {
			$t = trim($tag);
			if ($t == '') continue;
			logger::log("ADD TAGS", "Adding Tag",$t,"to TTindex",$ttid);
			$tagindex = $this->sql_prepare_query(false, null, 'Tagindex', null, "SELECT Tagindex FROM Tagtable WHERE Name=?", $t);
			if ($tagindex == null) $tagindex = $this->create_new_tag($t);
			if ($tagindex == null) {
				logger::warn("ADD TAGS", "    Could not create tag",$t);
				return false;
			}

			if ($result = $this->sql_prepare_query(true, null, null, null,
					"INSERT INTO TagListtable (TTindex, Tagindex) VALUES (?, ?)",
						$ttid,
						$tagindex
					)
				) {
				logger::debug("ADD TAGS", "Success");
				if (in_array($t, prefs::$prefs['auto_audiobook'])) {
					logger::log('ADD TAGS', 'Setting TTindex',$ttid,'as audiobook due to tag',$t);
					$albumindex = $this->simple_query('Albumindex', 'Tracktable', 'TTindex', $ttid, null);
					$this->set_as_audiobook($albumindex, 2);
				}
			} else {
				// Doesn't matter, we have a UNIQUE constraint on both columns to prevent us adding the same tag twice
				logger::debug("ADD TAGS", "  .. Failed but that's OK if it's because of a duplicate entry or UNQIUE constraint");
			}
		}
		return true;
	}

	private function create_new_tag($tag) {

		// create_new_tags
		//		Creates a new entry in Tagtable
		//		Returns: Tagindex

		logger::mark("CREATE TAG", "Creating new tag",$tag);
		$tagindex = null;
		if ($this->sql_prepare_query(true, null, null, null, "INSERT INTO Tagtable (Name) VALUES (?)", $tag)) {
			$tagindex = $this->mysqlc->lastInsertId();
		}
		return $tagindex;
	}

	private function remove_tag($ttid, $tag) {

		// remove_tags
		//		Removes a tag relation from a TTindex

		logger::log("REMOVE TAG", "Removing Tag",$tag,"from TTindex",$ttid);
		$retval = false;
		if ($tagindex = $this->simple_query('Tagindex', 'Tagtable', 'Name', $tag, false)) {
			$retval = $this->generic_sql_query("DELETE FROM TagListtable WHERE TTindex = '".$ttid."' AND Tagindex = '".$tagindex."'", true);
		} else {
			logger::warn("REMOVE TAG", "  ..  Could not find tag",$tag);
		}
		return $retval;
	}

	private function delete_track($ttid) {
		if ($this->remove_ttid($ttid)) {
		} else {
			header('HTTP/1.1 400 Bad Request');
		}
	}

	private function amend_album($albumindex, $newartist, $date) {
		logger::mark("AMEND ALBUM", "Updating Album index",$albumindex,"with new artist",$newartist,"and new date",$date);
		$artistindex = ($newartist == null) ? null : $this->check_artist($newartist);
		$result = $this->sql_prepare_query(false, PDO::FETCH_OBJ, null, null, "SELECT * FROM Albumtable WHERE Albumindex = ?", $albumindex);
		$obj = array_shift($result);
		if ($obj) {
			$params = array(
				'Album' => $obj->Albumname,
				'albumartist_index' => ($artistindex == null) ? $obj->AlbumArtistindex : $artistindex,
				'X-AlbumUri' => $obj->AlbumUri,
				'X-AlbumImage' => $obj->Image,
				'year' => ($date == null) ? $obj->Year : getYear($date),
				'Searched' => $obj->Searched,
				'ImgKey' => $obj->ImgKey,
				'MUSICBRAINZ_ALBUMID' => $obj->mbid,
				'domain' => $obj->Domain);
			$newalbumindex = $this->check_album($params);
			if ($albumindex != $newalbumindex) {
				logger::log("AMEND ALBUM", "Moving all tracks from album",$albumindex,"to album",$newalbumindex);
				if ($this->sql_prepare_query(true, null, null, null, "UPDATE Tracktable SET Albumindex = ? WHERE Albumindex = ?", $newalbumindex, $albumindex)) {
					logger::debug("AMEND ALBUM", "...Success");
				} else {
					logger::warn("AMEND ALBUM", "Track move Failed!");
					return false;
				}
			}
		} else {
			logger::error("AMEND ALBUM", "Failed to find album to update!");
			return false;
		}
		return true;
	}

	private function delete_album($albumindex) {
		$result = $this->generic_sql_query('DELETE FROM Tracktable WHERE Albumindex = '.$albumindex);
		return true;
	}

	private function set_as_audiobook($albumindex, $value) {
		$result = $this->sql_prepare_query(true, null, null, null, 'UPDATE Tracktable SET isAudiobook = ?, justAdded = 1 WHERE Albumindex = ?', $value, $albumindex);
		return $result;
	}

	private function use_trackimages($albumindex, $value) {
		$result = $this->sql_prepare_query(true, null, null, null, 'UPDATE Albumtable SET useTrackIms = ?, justUpdated = 1 WHERE Albumindex = ?', $value, $albumindex);
		return $result;
	}

	private function forced_uri_only($u,$d) {
		// Some mopidy backends - YouTube and SoundCloud - can return the same artist/album/track info
		// for multiple different tracks.
		// This gives us a problem because $this->find_item will think they're the same.
		// So for those backends we always force urionly to be true
		logger::core("USERRATINGS", "Checking domain : ".$d);
		if ($u || $d == "youtube" || $d == "soundcloud") {
			return true;
		} else {
			return false;
		}
	}

	private function doCollectionHeader() {
		$this->returninfo['stats'] = $this->collectionStats();
		$this->returninfo['bookstats'] = $this->audiobookStats();
	}

	private function track_is_wishlist($ttid) {
		// Returns boolean false if the TTindex is not in the wishlist or an array of attributes otherwise
		$retval = false;
		$u = $this->simple_query('Uri', 'Tracktable', 'TTindex', $ttid, null);
		if ($u == null) {
			logger::mark('BACKEND', "Track",$ttid,"is wishlist. Discarding");
			$meta = $this->get_all_data($ttid);
			$retval = [
				['attribute' => 'Rating', 'value' => $meta['Rating']],
				['attribute' => 'Tags', 'value' => $meta['Tags']]
			];
			$this->generic_sql_query("DELETE FROM Playcounttable WHERE TTindex=".$ttid, true);
			$this->generic_sql_query("DELETE FROM Tracktable WHERE TTindex=".$ttid, true);
		}
		return $retval;
	}

	// private function track_is_hidden($ttid) {
	// 	$h = $this->simple_query('Hidden', 'Tracktable', 'TTindex', $ttid, 0);
	// 	return ($h != 0) ? true : false;
	// }

	// private function track_is_searchresult($ttid) {
	// 	// This is for detecting tracks that were added as part of a search, or un-hidden as part of a search
	// 	$h = $this->simple_query('isSearchResult', 'Tracktable', 'TTindex', $ttid, 0);
	// 	return ($h > 1) ? true : false;
	// }

	// private function track_is_unplayable($ttid) {
	// 	$r = $this->simple_query('LinkChecked', 'Tracktable', 'TTindex', $ttid, 0);
	// 	return ($r == 1 || $r == 3);
	// }

	private function check_for_wishlist_track($data) {
		// Searches for a wishlist track based on Title and Artistname
		// Returns false if nothing found or an array of attributes otherwise
		$retval = false;
		$result = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, null,
			"SELECT TTindex FROM Tracktable JOIN Artisttable USING (Artistindex)
			WHERE Artistname = ? AND Title = ? AND Uri IS NULL",
		$data['trackartist'],$data['Title']);
		foreach ($result as $obj) {
			logger::mark('BACKEND', "Wishlist Track",$obj['TTindex'],"matches the one we're adding");
			$meta = $this->get_all_data($obj['TTindex']);
			$retval = [
				['attribute' => 'Rating', 'value' => $meta['Rating']],
				['attribute' => 'Tags', 'value' => $meta['Tags']]
			];
			$this->generic_sql_query("DELETE FROM Playcounttable WHERE TTindex=".$obj['TTindex'], true);
			$this->generic_sql_query("DELETE FROM Tracktable WHERE TTindex=".$obj['TTindex'], true);
		}
		return $retval;
	}

	private function get_all_data($ttid) {

		// Misleadingly named function which should be used to get ratings and tags
		// (and whatever else we might add) based on a TTindex
		$data = self::NODATA;
		$result = $this->generic_sql_query("SELECT
				IFNULL(r.Rating, 0) AS Rating,
				IFNULL(p.Playcount, 0) AS Playcount,
				".$this->sql_to_unixtime('p.LastPlayed')." AS LastTime,
				".$this->sql_to_unixtime('tr.DateAdded')." AS DateAdded,
				IFNULL(".database::SQL_TAG_CONCAT.", '') AS Tags,
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
			$data['Tags'] = ($data['Tags'] == '') ? array() : explode(', ', $data['Tags']);
			if ($data['LastTime'] != null && $data['LastTime'] != 0 && $data['LastTime'] != '0') {
				$data['Last'] = $data['LastTime'];
			}
		}
		return $data;
	}

	private function remove_ttid($ttid) {

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
		// Also in this case, set isAudiobook to 0 because if it's a search result AND it's been moved to Spoken Word
		// then deleted, if someone tried to then re-add it it doesn't appear in the display because all manually-added tracks go to
		// the Collection not Spoken Word, but this doesn't work oh god it's horrible just leave it.

		logger::log('BACKEND', "Removing track ".$ttid);
		$result = false;
		if ($this->generic_sql_query("DELETE FROM Tracktable WHERE isSearchResult != 1 AND TTindex = '".$ttid."'",true)) {
			if ($this->generic_sql_query("UPDATE Tracktable SET isSearchResult = 2, isAudiobook = 0 WHERE isSearchResult = 1 AND TTindex = '".$ttid."'", true)) {
				$result = true;
			}
		}
		return $result;
	}

	public function prepare_returninfo() {
		logger::log("USERRATINGS", "Preparing Return Info");
		$t = microtime(true);

		$sorter = choose_sorter_by_key('aartistroot');
		$lister = new $sorter('aartistroot');
		$lister->get_modified_root_items();
		$lister->get_modified_albums();

		$sorter = choose_sorter_by_key('zartistroot');
		$lister = new $sorter('zartistroot');
		$lister->get_modified_root_items();
		$lister->get_modified_albums();

		$sorter = choose_sorter_by_key('bartistroot');
		if ($sorter) {
			$lister = new $sorter('bartistroot');
			$lister->get_modified_root_items();
			$lister->get_modified_albums();
		}

		$result = $this->generic_sql_query(
			'SELECT Albumindex, AlbumArtistindex, Uri, TTindex, isAudiobook
			FROM Tracktable JOIN Albumtable USING (Albumindex)
			WHERE justAdded = 1 AND Hidden = 0'
		);
		foreach ($result as $mod) {
			logger::log("USERRATING", "  New Track in album ".$mod['Albumindex'].' has TTindex '.$mod['TTindex']);
			$this->returninfo['addedtracks'][] = array(	'artistindex' => $mod['AlbumArtistindex'],
													'albumindex' => $mod['Albumindex'],
													'trackuri' => rawurlencode($mod['Uri']),
													'isaudiobook' => $mod['isAudiobook']
												);
		}
		$at = microtime(true) - $t;
		logger::info("TIMINGS", " -- Finding modified items took ".$at." seconds");
	}

	private function create_new_track(&$data) {

		// create_new_track
		//		Creates a new track, along with artists and album if necessary
		//		Returns: TTindex

		// This is used by the metadata functions for adding new tracks. It is NOT used
		// when doing a search or updating the collection, for reasons explained below.

		// This copes with backends like youtube and soundcloud where 2 actual different tracks
		// might fall foul of our UNiQUE KEY constraint because those backends don't really have
		// the concept of an album or a track number.

		// If it gets to the stage where that's a problem, we'll just drop support for those backends.
		// Fuck knows it'll make my life easier. I quite like having youtube support, but only because
		// I've implemented the download audio option.

		if ($data['albumartist_index'] == null) {
			// Does the albumartist exist?
			$data['albumartist_index'] = $this->check_artist($data['albumartist']);
		}

		// Does the track artist exist?
		if ($data['trackartist_index'] == null) {
			if ($data['trackartist'] != $data['albumartist']) {
				$data['trackartist_index'] = $this->check_artist($data['trackartist']);
			} else {
				$data['trackartist_index'] = $data['albumartist_index'];
			}
		}

		if ($data['albumartist_index'] === null || $data['trackartist_index'] === null) {
			logger::warn('BACKEND', "Trying to create new track but failed to get an artist index");
			return null;
		}

		if ($data['album_index'] == null) {
			// Does the album exist?
			if ($data['Album'] == null) {
				$data['Album'] = 'rompr_wishlist_'.microtime('true');
			}
			$data['album_index'] = $this->check_album($data);
			if ($data['album_index'] === null) {
				logger::warn('BACKEND', "Trying to create new track but failed to get an album index");
				return null;
			}
		}

		$data['sourceindex'] = null;
		if ($data['file'] === null && $data['streamuri'] !== null) {
			$data['sourceindex'] = $this->check_radio_source($data);
		}

		// Check the track doesn't already exist. This can happen if we're doing an ADD operation and only the URI is different
		// (fucking Spotify). We're not using the ON DUPLICATE KEY UPDATE here because, when that does an UPDATE instead of an INSERT,
		// lastUpdateId() does not return the TTindex of the updated track but rather the current AUTO_INCREMENT value of the table
		// which is about as useful as giving me a forwarding address that only contains the correct continent.

		// We also have to cope with youtube and soundcloud, where the same combination of unique keys can actually refer to
		// different tracks. In those circumstances we will have looked up using uri only. As urionly did NOT find a track this
		// means the track we're trying to add must be different. In this case we increment the disc number until we have a unique track.

		while (($bollocks = $this->check_track_exists($data)) !== false) {
			if ($this->forced_uri_only(false, $data['domain'])) {
				$data['Disc']++;
			} else {
				$track = $bollocks[0];
				$cock = false;
				logger::mark('BACKEND', 'Track being added already exists', $data['file'], $track['Uri']);
				$this->sql_prepare_query(true, null, null, null,
					"UPDATE Tracktable SET Uri = ?, Duration = ?, Hidden = ?, Sourceindex = ?, isAudiobook = ?, Genreindex = ?, TYear = ?, LinkChecked = ?, justAdded = ? WHERE TTindex = ?",
					$data['file'],
					$this->best_value($track['Duration'], $data['Time'], $cock),
					$data['hidden'],
					$data['sourceindex'],
					$data['isaudiobook'],
					$this->check_genre($data['Genre']),
					$this->best_value($track['TYear'], $data['year'], $cock),
					0,
					1,
					$track['TTindex']
				);
				return $track['TTindex'];
			}
		}

		if ($this->sql_prepare_query(true, null, null, null,
			"INSERT INTO
				Tracktable
				(Title, Albumindex, Trackno, Duration, Artistindex, Disc, Uri, LastModified, Hidden, Sourceindex, isAudiobook, Genreindex, TYear)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
				$data['Title'],
				$data['album_index'],
				$data['Track'],
				$data['Time'],
				$data['trackartist_index'],
				$data['Disc'],
				$data['file'],
				$data['Last-Modified'],
				$data['hidden'],
				$data['sourceindex'],
				$data['isaudiobook'],
				$this->check_genre($data['Genre']),
				$data['year']
			))
		{
			return $this->mysqlc->lastInsertId();
		} else {
			logger::error('BACKEND', 'FAILED to create new track!');
		}
		return null;
	}

	private function check_track_exists($data) {
		$bollocks = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, array(),
			"SELECT * FROM Tracktable WHERE Albumindex = ? AND Artistindex = ? AND TrackNo = ? AND Disc = ? AND Title = ?",
			$data['album_index'], $data['trackartist_index'], $data['Track'], $data['Disc'], $data['Title']
		);
		return (count($bollocks) > 0) ? $bollocks : false;
	}

	private function youtubedl_error($message, $progress_file) {
		logger::error('YOUTUBEDL', $message);
		header("HTTP/1.1 404 Not Found");
		print $message;

		if ($progress_file && file_exists($progress_file))
			unlink($progress_file);

		exit(0);
	}

	public function youtubedl($data) {
		$ytdl_path = find_executable('youtube-dl');
		if ($ytdl_path === false)
			$this->youtubedl_error('youtube-dl binary could not be found', null);

		logger::log('YOUTUBEDL', 'youtube-dl is at',$ytdl_path);
		$avconv_path = find_executable('avconv');
		if ($avconv_path === false) {
			$avconv_path = find_executable('ffmpeg');
			if ($avconv_path === false)
				$this->youtubedl_error('Could not find avconv or ffmpeg', null);

		}

		$a = preg_match('/:video\/.*\.(.+)$/', $data['file'], $matches);
		if (!$a)
			$a = preg_match('/:video:(.+)$/', $data['file'], $matches);

		if (!$a)
			$this->youtubedl_error('Could not match URI '.$data['file'], null);

		$uri_to_get = 'https://youtu.be/'.$matches[1];
		logger::log('YOUTUBEDL', 'Downloading',$uri_to_get);

		$info = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, array(),
			"SELECT Title, Artistname FROM Tracktable JOIN Artisttable USING (Artistindex) WHERE Uri = ?",
			$data['file']
		);
		if (is_array($info) && count($info) > 0) {
			logger::log('YOUTUBEDL', '  Title is',$info[0]['Title']);
			logger::log('YOUTUBEDL', '  Artist is',$info[0]['Artistname']);
		} else {
			logger::log('YOUTUBEDL', '  Could not find title and artist from collection');
		}

		$ttindex = $this->simple_query('TTindex', 'Tracktable', 'Uri', $data['file'], null);
		if ($ttindex === null)
			$this->youtubedl_error('Could not locate that track in the database!', null);

		$progress_file = 'prefs/youtubedl/dlprogress_'.md5($data['file']);
		if (!file_put_contents($progress_file, '    Downloading '.$uri_to_get."\n")) {
			$this->youtubedl_error('Could not open progress file. Possible permissions error', null);
		}

		if (is_dir('prefs/youtubedl/'.$ttindex)) {
			$this->youtubedl_error('Target Directory prefs/youtubedl/'.$ttindex,'already exists', $progress_file);
		} else {
			logger::log('YOUTUBEDL', 'Making Directory prefs/youtubedl/'.$ttindex);
			mkdir('prefs/youtubedl/'.$ttindex);
		}
		// At this point, terminate the request so the download can run in the background.
		// If we don't do this the browser will retry after 3 minutes and there's nothing we
		// can do about that.
		close_browser_connection();
		logger::log('YOUTUBEDL', 'OK now we start the fun');
		file_put_contents('prefs/youtubedl/'.$ttindex.'/original.uri', $uri_to_get);
		exec($ytdl_path.'youtube-dl -o "prefs/youtubedl/'.$ttindex.'/%(title)s-%(id)s.%(ext)s" --ffmpeg-location '.$avconv_path.' --extract-audio --write-thumbnail --restrict-filenames --newline --audio-format flac --audio-quality 0 '.$uri_to_get.' >> '.$progress_file.' 2>&1', $output, $retval);
		if ($retval != 0 && $retval != 1) {
			$this->youtubedl_error('youtube-dl returned error code '.$retval, $progress_file);
		}
		$files = glob('prefs/youtubedl/'.$ttindex.'/*.flac');
		if (count($files) == 0) {
			$this->youtubedl_error('Could not find downloaded flac file in prefs/youtubedl/'.$ttindex, $progress_file);
		} else {
			logger::log('YOUTUBEDL', print_r($files, true));
		}

		if (is_array($info) && count($info) > 0) {
			logger::log('YOUTUBEDL', 'Writing ID3 tags to',$files[0]);

			$getID3 = new getID3;
			$getID3->setOption(array('encoding'=>'UTF-8'));

			getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'write.php', __FILE__, true);

			$tagwriter = new getid3_writetags;
			$tagwriter->filename       = $files[0];
			$tagwriter->tagformats     = array('metaflac');
			$tagwriter->overwrite_tags = true;
			$tagwriter->tag_encoding   = 'UTF-8';
			$tagwriter->remove_other_tags = true;
			$tags = array(
				'artist' => array(html_entity_decode($info[0]['Artistname'])),
				'albumartist' => array(html_entity_decode($info[0]['Artistname'])),
				'album' => array(html_entity_decode($info[0]['Title'])),
				'title' => array(html_entity_decode($info[0]['Title']))
			);
			$tagwriter->tag_data = $tags;
			if ($tagwriter->WriteTags()) {
				logger::log('YOUTTUBEDL', 'Successfully wrote tags');
				if (!empty($tagwriter->warnings)) {
					logger::log('YOUTUBEDL', 'There were some warnings'.implode(' ', $tagwriter->warnings));
				}
			} else {
				logger::error('YOUTUBEDL', 'Failed to write tags!', implode(' ', $tagwriter->errors));
			}
		}

		$new_uri = dirname(dirname(get_base_url())).'/'.$files[0];
		logger::log('YOUTUBEDL', 'New URI is', $new_uri);
		$this->sql_prepare_query(true, null, null, null,
			"UPDATE Tracktable SET Uri = ? WHERE Uri = ?",
			$new_uri,
			$data['file']
		);
		$albumindex = $this->simple_query('Albumindex', 'Tracktable', 'Uri', $new_uri, null);
		$this->sql_prepare_query(true, null, null, null,
			"UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = ?",
			$albumindex
		);
		return $progress_file;
	}

}

?>
