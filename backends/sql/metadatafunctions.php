<?php

class romprmetadata {

	public static function sanitise_data(&$data) {

		foreach (array( 'action',
						'title',
						'artist',
						'trackno',
						'duration',
						'albumuri',
						'image',
						'album',
						'uri',
						'trackai',
						'albumai',
						'albumindex',
						'searched',
						'lastmodified',
						'streamname',
						'streamimage',
						'streamuri',
						'type',
						'ambid',
						'isaudiobook',
						'attributes',
						'imagekey',
						'which',
						'wltrack',
						'reqid') as $key) {
			if (!array_key_exists($key, $data)) {
				$data[$key] = null;
			}
		}
		foreach (array( 'trackno', 'duration', 'isaudiobook') as $key) {
			if ($data[$key] == null) {
				$data[$key] = 0;
			}
		}
		$data['albumartist'] = array_key_exists('albumartist', $data) ? $data['albumartist'] : $data['artist'];
		$data['date'] = (array_key_exists('date', $data) && $data['date'] != 0) ? getYear($data['date']) : null;
		$data['urionly'] = array_key_exists('urionly', $data) ? true : false;
		$data['disc'] = array_key_exists('disc', $data) ? $data['disc'] : 1;
		$data['domain'] = array_key_exists('domain', $data) ? $data['domain'] : ($data['uri'] === null ? "local" : getDomain($data['uri']));
		$data['hidden'] = 0;
		$data['searchflag'] = 0;
		if (substr($data['image'],0,4) == "http") {
			$data['image'] = "getRemoteImage.php?url=".$data['image'];
		}
		if ($data['imagekey'] === null) {
			$albumimage = new baseAlbumImage(array(
				'artist' => artist_for_image($data['type'], $data['albumartist']),
				'album' => $data['album']
			));
			$data['imagekey'] = $albumimage->get_image_key();
		}
	}

	public static function set($data, $keep_wishlist = false) {
		global $returninfo;
		if ($data['artist'] === null ||
			$data['title'] === null ||
			$data['attributes'] == null) {
			logger::error("USERRATING", "Something is not set", $data);
			header('HTTP/1.1 400 Bad Request');
			print json_encode(array('error' => 'Artist or Title or Attributes not set'));
			exit(0);
		}

		switch ($data['artist']) {
			case 'geturisfordir':
				$ttids = romprmetadata::geturisfordir($data);
				break;

			case  'geturis':
				$ttids = romprmetadata::geturis($data);
				break;

			default:
				$ttids = romprmetadata::find_item($data, forcedUriOnly($data['urionly'], getDomain($data['uri'])));
				break;
		}

		$newttids = array();
		foreach ($ttids as $ttid) {
			if ($keep_wishlist || !track_is_wishlist($ttid)) {
				$newttids[] = $ttid;
			}
		}
		$ttids = $newttids;

		if (count($ttids) == 0) {
			$ttids[0] = create_new_track($data);
			logger::log("USERRATINGS", "Created New Track with TTindex ".$ttids[0]);
		}

		if (count($ttids) > 0) {
			if (romprmetadata::doTheSetting($ttids, $data['attributes'], $data['uri'])) {
			} else {
				header('HTTP/1.1 417 Expectation Failed');
				$returninfo['error'] = 'Setting attributes failed';
			}
		} else {
			logger::fail("USERRATING", "TTID Not Found");
			header('HTTP/1.1 417 Expectation Failed');
			$returninfo['error'] = 'TTindex not found';
		}
	}

	public static function add($data, $urionly = true) {
		// This is used for adding specific tracks so we need urionly to be true
		// We don't simply call into this using 'set' with urionly set to true
		// because that might result in the rating being changed

		// The only time we call inot this with $urionly set to false is when we're restoring a metadata
		// backup. In that case we might be copying data from one setup to another and we might have
		// the track already in local, so we don't want to add duplicates. Neither way is perfect but
		// this makes most sense I think.

		global $returninfo;
		$ttids = romprmetadata::find_item($data, $urionly);

		// As we check by URI we can only have one result.
		$ttid = null;
		if (count($ttids) > 0) {
			$ttid = $ttids[0];
			if (track_is_hidden($ttid) || track_is_searchresult($ttid)) {
				logger::mark("USERRATINGS", "Track ".$ttid." being added is a search result or a hidden track");
				// Setting attributes (Rating: 0) will unhide/un-searchify it. Ratings of 0 are got rid of
				// by remove_cruft at the end, because they're meaningless
				if ($data['attributes'] == null) {
					$data['attributes'] = array(array('attribute' => 'Rating', 'value'=> 0));
				}
			} else {
				logger::warn("USERRATINGS", "Track being added already exists");
			}
		}

		check_for_wishlist_track($data);

		if ($ttid == null) {
			logger::log("USERRATINGS", "Creating Track being added");
			$ttid = create_new_track($data);
		}

		romprmetadata::doTheSetting(array($ttid), $data['attributes'], $data['uri']);
	}

	public static function inc($data) {
		global $returninfo;
		// NOTE : 'inc' does not do what you might expect.
		// This is not an 'increment' function, it still does a SET but it will create a hidden track
		// if the track can't be found, compare to SET which creates a new unhidden track.
		if ($data['artist'] === null ||
			$data['title'] === null ||
			$data['attributes'] == null) {
			logger::error("USERRATING", "Something is not set",$data);
			header('HTTP/1.1 400 Bad Request');
			print json_encode(array('error' => 'Artist or Title or Attributes not set'));
			exit(0);
		}
		$ttids = romprmetadata::find_item($data, forcedUriOnly(false,getDomain($data['uri'])));
		if (count($ttids) == 0) {
			logger::log("USERRATING", "Doing an INCREMENT action - Found NOTHING so creating hidden track");
			$data['hidden'] = 1;
			$ttids[0] = create_new_track($data);
		}

		romprmetadata::checkLastPlayed($data);

		if (count($ttids) > 0) {
			foreach ($ttids as $ttid) {
				logger::trace("USERRATING", "Doing an INCREMENT action - Found TTID ",$ttid);
				foreach ($data['attributes'] as $pair) {
					logger::log("USERRATING", "(Increment) Setting",$pair["attribute"],"to",$pair["value"],"on",$ttid);
					romprmetadata::increment_value($ttid, $pair["attribute"], $pair["value"], $data['lastplayed']);
				}
				$returninfo['metadata'] = get_all_data($ttid);
			}
		}
		return $ttids;
	}

	private static function checkLastPlayed(&$data) {
		if (array_key_exists('lastplayed', $data)) {
			if (is_numeric($data['lastplayed'])) {
				// Convert timestamp from LastFM into MySQL TIMESTAMP format
				$data['lastplayed'] = date('Y-m-d H:i:s', $data['lastplayed']);
			}
		} else {
			$data['lastplayed'] = date('Y-m-d H:i:s');
		}
	}

	public static function syncinc($data) {
		global $returninfo;
		if ($data['artist'] === null ||
			$data['title'] === null ||
			$data['attributes'] == null) {
			logger::error("SYNCINC", "Something is not set", $data);
			header('HTTP/1.1 400 Bad Request');
			print json_encode(array('error' => 'Artist or Title or Attributes not set'));
			exit(0);
		}

		$ttids = romprmetadata::find_item($data, forcedUriOnly(false,getDomain($data['uri'])));
		if (count($ttids) == 0) {
			$ttids = romprmetadata::inc($data);
			romprmetadata::resetSyncCounts($ttids);
			return true;
		}

		romprmetadata::checkLastPlayed($data);
		logger::log("SYNCINC", "LastPlayed is ".$data['lastplayed']);
		foreach ($ttids as $ttid) {
			logger::log("SYNCINC", "Doing a SYNC action on TTID ".$ttid);
			$rowcount = generic_sql_query("UPDATE Playcounttable SET SyncCount = SyncCount - 1, LastPlayed = '".$data['lastplayed']."' WHERE TTindex = ".$ttid." AND SyncCount > 0",
				false, null, null, null, true);
			if ($rowcount > 0) {
				logger::log("SYNCINC", "  Decremented sync counter for this track");
			} else {
				$rowcount = generic_sql_query("UPDATE Playcounttable SET Playcount = Playcount + 1, LastPlayed = '".$data['lastplayed']."' WHERE TTindex = ".$ttid,
					false, null, null, null, true);
				if ($rowcount > 0) {
					logger::log("SYNCINC", "  Incremented Playcount for this track");
					// At this point, SyncCount must have been zero but the update will have incremented it again,
					// because of the trigger. resetSyncCounts takes care of this;
				} else {
					logger::log("SYNCINC", "  Track not found in Playcounttable");
					$metadata = get_all_data($ttid);
					romprmetadata::increment_value($ttid, 'Playcount', $metadata['Playcount'] + 1, $data['lastplayed']);
					// At this point, SyncCount must have been zero but the update will have incremented it again,
					// because of the trigger. resetSyncCounts takes care of this;
				}
				romprmetadata::resetSyncCounts(array($ttid));
			}
		}

		// Let's just see if it's a podcast track and mark it as listened.
		// This won't always work, as scrobbles are often not what's in the RSS feed, but we can but do our best
		sql_prepare_query(true, null, null, null, 
			"UPDATE PodcastTrackTable SET Listened = ? WHERE Title = ? AND Artist = ?",
			1,
			$data['title'],
			$data['artist']
		);

	}

	public static function resetSyncCounts($ttids) {
		foreach ($ttids as $ttid) {
			generic_sql_query("UPDATE Playcounttable SET SyncCount = 0 WHERE TTindex = ".$ttid, true);
		}
	}

	public static function resetallsyncdata() {
		generic_sql_query('UPDATE Playcounttable SET SyncCount = 0 WHERE TTindex > 0', true);
	}

	public static function remove($data) {
		global $returninfo;
		if ($data['artist'] === null || $data['title'] === null) {
			header('HTTP/1.1 400 Bad Request');
			print json_encode(array('error' => 'Artist or Title not set'));
			exit(0);
		}
		$ttids = romprmetadata::find_item($data, forcedUriOnly($data['urionly'], getDomain($data['uri'])));
		if (count($ttids) > 0) {
			foreach ($ttids as $ttid) {
				$result = true;
				foreach ($data['attributes'] as $pair) {
					logger::trace("USERRATING", "Removing",$pair);
					$r = romprmetadata::remove_tag($ttid, $pair["value"]);
					if ($r == false) {
						logger::fail("USERRATING", "FAILED Removing",$pair);
						$result = false;
					}
				}
				if ($result) {
					$returninfo['metadata'] = get_all_data($ttid);
				} else {
					header('HTTP/1.1 417 Expectation Failed');
					$returninfo['error'] = 'Removing attributes failed';
				}
			}
		} else {
			logger::fail("USERRATING", "TTID Not Found");
			header('HTTP/1.1 417 Expectation Failed');
			$returninfo['error'] = 'TTindex not found';
		}
	}

	public static function get($data) {
		global $returninfo, $nodata;
		if ($data['artist'] === null || $data['title'] === null) {
			header('HTTP/1.1 400 Bad Request');
			print json_encode(array('error' => 'Artist or Title not set'));
			exit(0);
		}
		$ttids = romprmetadata::find_item($data, forcedUriOnly(false, getDomain($data['uri'])));
		if (count($ttids) > 0) {
			$ttid = array_shift($ttids);
			$returninfo = get_all_data($ttid);
		} else {
			$returninfo = $nodata;
		}
	}

	public static function setalbummbid($data) {
		global $returninfo, $nodata;
		$ttids = romprmetadata::find_item($data, forcedUriOnly(false, getDomain($data['uri'])));
		if (count($ttids) > 0) {
			foreach ($ttids as $ttid) {
				logger::log("BACKEND", "Updating album MBID ".$data['attributes']." from TTindex ".$ttid);
				$albumindex = simple_query('Albumindex', 'Tracktable', 'TTindex', $ttid, null);
				logger::trace("BACKEND", "   .. album index is ".$albumindex);
				sql_prepare_query(true, null, null, null, "UPDATE Albumtable SET mbid = ? WHERE Albumindex = ? AND mbid IS NULL",$data['attributes'],$albumindex);
			}
		}
		$returninfo = $nodata;
	}

	public static function cleanup($data) {
		logger::log("SQL", "Doing Database Cleanup And Stats Update");
		remove_cruft();
		update_track_stats();
		doCollectionHeader();
	}

	public static function amendalbum($data) {
		if ($data['albumindex'] !== null && romprmetadata::amend_album($data['albumindex'], $data['albumartist'], $data['date'])) {
		} else {
			header('HTTP/1.1 400 Bad Request');
			$returninfo['error'] = 'That just did not work';
		}
	}

	public static function deletetag($data) {
		if (romprmetadata::remove_tag_from_db($data['value'])) {
		} else {
			header('HTTP/1.1 400 Bad Request');
			$returninfo['error'] = 'Well, that went well';
		}
	}

	public static function delete($data) {
		$ttids = romprmetadata::find_item($data, true);
		if (count($ttids) == 0) {
			header('HTTP/1.1 400 Bad Request');
			$returninfo['error'] = 'TTindex not found';
		} else {
			romprmetadata::delete_track(array_shift($ttids));
		}
	}

	public static function deletewl($data) {
		romprmetadata::delete_track($data['wltrack']);
	}

	public static function deleteid($data) {
		romprmetadata::delete_track($data['ttid']);
	}

	public static function getcharts($data) {
		global $returninfo;
		$returninfo['Artists'] = get_artist_charts();
		$returninfo['Albums'] = get_album_charts();
		$returninfo['Tracks'] = get_track_charts();
	}

	public static function clearwishlist() {
		logger::log("MONKEYS", "Removing Wishlist Tracks");
		if (clear_wishlist()) {
			logger::log("MONKEYS", " ... Success!");
		} else {
			logger::warn("MONKEYS", "Failed removing wishlist tracks");
		}
	}

	// Private Functions

	static function geturisfordir($data) {
		global $PLAYER_TYPE;
		$player = new $PLAYER_TYPE();
		$uris = $player->get_uris_for_directory($data['uri']);
		$ttids = array();
		foreach ($uris as $uri) {
			$t = sql_prepare_query(false, PDO::FETCH_COLUMN, 'TTindex', null, "SELECT TTindex FROM Tracktable WHERE Uri = ?", $uri);
			$ttids = array_merge($ttids, $t);
		}
		return $ttids;
	}

	static function geturis($data) {
		$uris = getItemsToAdd($data['uri'], "");
		$ttids = array();
		foreach ($uris as $uri) {
			$uri = trim(substr($uri, strpos($uri, ' ')+1, strlen($uri)), '"');
			$r = sql_prepare_query(false, PDO::FETCH_COLUMN, 'TTindex', null, "SELECT TTindex FROM Tracktable WHERE Uri = ?", $uri);
			$ttids = array_merge($ttids, $t);
		}
		return $ttids;
	}

	static function print_debug_ttids($ttids, $s) {
		$time = time() - $s;
		if (count($ttids) > 0) {
			logger::log("TIMINGS", "    Found TTindex(es)",$ttids,"in",$time,"seconds");
		}
	}

	static function find_item($data,$urionly) {

		// romprmetadata::find_item
		//		Looks for a track in the database based on uri, title, artist, album, and albumartist or
		//		combinations of those
		//		Returns: Array of TTindex

		// romprmetadata::find_item is used by userRatings to find tracks on which to update or display metadata.
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
		// Looking everything up by name/album/artist (i.e. ignoring the URI in romprmetadata::find_item)
		// doesn't fix this because the collection display still doesn't show the rating as that's
		// looked up by TTindex

		$start_time = time();
		logger::shout("FIND ITEM", "Looking for item ".$data['title']);
		$ttids = array();
		if ($urionly && $data['uri']) {
			logger::mark("FIND ITEM", "  Trying by URI ".$data['uri']);
			$t = sql_prepare_query(false, PDO::FETCH_COLUMN, 'TTindex', null, "SELECT TTindex FROM Tracktable WHERE Uri = ?", $data['uri']);
			$ttids = array_merge($ttids, $t);
		}

		if ($data['artist'] == null || $data['title'] == null || ($urionly && $data['uri'])) {
			romprmetadata::print_debug_ttids($ttids, $start_time);
			return $ttids;
		}

		if (count($ttids) == 0) {
			if ($data['album']) {
				if ($data['albumartist'] !== null && $data['trackno'] != 0) {
					logger::mark("FIND ITEM", "  Trying by albumartist",$data['albumartist'],"album",$data['album'],"title",$data['title'],"track number",$data['trackno']);
					$t = sql_prepare_query(false, PDO::FETCH_COLUMN, 'TTindex', null,
						"SELECT
							TTindex
						FROM
							Tracktable JOIN Albumtable USING (Albumindex)
							JOIN Artisttable ON Albumtable.AlbumArtistindex = Artisttable.Artistindex
						WHERE
							LOWER(Title) = LOWER(?)
							AND LOWER(Artistname) = LOWER(?)
							AND LOWER(Albumname) = LOWER(?)
							AND TrackNo = ?",
						$data['title'], $data['albumartist'], $data['album'], $data['trackno']);
					$ttids = array_merge($ttids, $t);
				}

				if (count($ttids) == 0 && $data['albumartist'] !== null) {
					logger::mark("FIND ITEM", "  Trying by albumartist",$data['albumartist'],"album",$data['album'],"and title",$data['title']);
					$t = sql_prepare_query(false, PDO::FETCH_COLUMN, 'TTindex', null,
						"SELECT
							TTindex
						FROM
							Tracktable JOIN Albumtable USING (Albumindex)
							JOIN Artisttable ON Albumtable.AlbumArtistindex = Artisttable.Artistindex
						WHERE
							LOWER(Title) = LOWER(?)
							AND LOWER(Artistname) = LOWER(?)
							AND LOWER(Albumname) = LOWER(?)",
						$data['title'], $data['albumartist'], $data['album']);
					$ttids = array_merge($ttids, $t);
				}

				if (count($ttids) == 0 && ($data['albumartist'] == null || $data['albumartist'] == $data['artist'])) {
					logger::mark("FIND ITEM", "  Trying by artist",$data['artist'],",album",$data['album'],"and title",$data['title']);
					$t = sql_prepare_query(false, PDO::FETCH_COLUMN, 'TTindex', null,
						"SELECT
							TTindex
						FROM
							Tracktable JOIN Artisttable USING (Artistindex)
							JOIN Albumtable USING (Albumindex)
						WHERE
							LOWER(Title) = LOWER(?)
							AND LOWER(Artistname) = LOWER(?)
						    AND LOWER(Albumname) = LOWER(?)", $data['title'], $data['artist'], $data['album']);
					$ttids = array_merge($ttids, $t);
				}

				// Finally look for Uri NULL which will be a wishlist item added via a radio station
				if (count($ttids) == 0) {
					logger::mark("FIND ITEM", "  Trying by (wishlist) artist",$data['artist'],"and title",$data['title']);
					$t = sql_prepare_query(false, PDO::FETCH_COLUMN, 'TTindex', null,
						"SELECT
							TTindex
						FROM
							Tracktable JOIN Artisttable USING (Artistindex)
						WHERE
							LOWER(Title) = LOWER(?)
							AND LOWER(Artistname) = LOWER(?)
							AND Uri IS NULL",
						$data['title'], $data['artist']);
					$ttids = array_merge($ttids, $t);
				}
			} else {
				// No album supplied - ie this is from a radio stream. First look for a match where
				// there is something in the album field
				logger::mark("FIND ITEM", "  Trying by artist",$data['artist'],"Uri NOT NULL and title",$data['title']);
				$t = sql_prepare_query(false, PDO::FETCH_COLUMN, 'TTindex', null,
					"SELECT
						TTindex
					FROM
						Tracktable JOIN Artisttable USING (Artistindex)
					 WHERE
					 	LOWER(Title) = LOWER(?)
					 	AND LOWER(Artistname) = LOWER(?)
					 	AND Uri IS NOT NULL", $data['title'], $data['artist']);
				$ttids = array_merge($ttids, $t);

				if (count($ttids) == 0) {
					logger::mark("FIND ITEM", "  Trying by (wishlist) artist",$data['artist'],"and title",$data['title']);
					$t = sql_prepare_query(false, PDO::FETCH_COLUMN, 'TTindex', null,
						"SELECT
							TTindex
						FROM
							Tracktable JOIN Artisttable USING (Artistindex)
						WHERE
							LOWER(Title) = LOWER(?)
							AND LOWER(Artistname) = LOWER(?)
							AND Uri IS NULL", $data['title'], $data['artist']);
					$ttids = array_merge($ttids, $t);
				}
			}
		}
		romprmetadata::print_debug_ttids($ttids, $start_time);
		return $ttids;
	}

	static function increment_value($ttid, $attribute, $value, $lp) {

		// Increment_value doesn't 'increment' as such - it's used for setting values on tracks without
		// unhiding them. It's used for Playcount, which was originally an 'increment' type function but
		// that changed because multiple rompr instances cause multiple increments

		logger::mark("INCREMENT", "Setting",$attribute,"to",$value,"for TTID",$ttid);
		if (sql_prepare_query(true, null, null, null, "REPLACE INTO ".$attribute."table (TTindex, ".$attribute.", LastPlayed) VALUES (?, ?, ?)", $ttid, $value, $lp)) {
			logger::trace("INCREMENT", " .. success");
		} else {
			logger::fail("INCREMENT", "FAILED Setting",$attribute,"to",$value,"for TTID",$ttid);
			return false;
		}
		return true;

	}

	static function set_attribute($ttid, $attribute, $value) {

		// set_attribute
		//		Sets an attribute (Rating, Tag etc) on a TTindex.
		logger::mark("ATTRIBUTE", "Setting",$attribute,"to",$value,"on",$ttid);
		if (sql_prepare_query(true, null, null, null, "REPLACE INTO ".$attribute."table (TTindex, ".$attribute.") VALUES (?, ?)", $ttid, $value)) {
			logger::trace("ATTRIBUTE", "  .. success");
		} else {
			logger::fail("ATTRIBUTE", "FAILED Setting",$attribute,"to",$value,"on",$ttid);
			return false;
		}
		return true;
	}

	static function doTheSetting($ttids, $attributes, $uri) {
		global $returninfo;
		$result = true;
		logger::trace("USERRATING", "Checking For attributes");
		if ($attributes !== null) {
			logger::trace("USERRATING", "Setting attributes");
			foreach($ttids as $ttid) {
				logger::debug("USERRATING", "TTid ".$ttid);
				foreach ($attributes as $pair) {
					logger::log("USERRATING", "Setting",$pair["attribute"],"to",$pair['value'],"on TTindex",$ttid);
					switch ($pair['attribute']) {
						case 'Tags':
							$result = romprmetadata::addTags($ttid, $pair['value']);
							break;

						default:
							$result = romprmetadata::set_attribute($ttid, $pair["attribute"], $pair["value"]);
							break;
					}
					if (!$result) { break; }
				}
				if ($uri) {
					$returninfo['metadata'] = get_all_data($ttid);
				}
			}
		}
		return $result;
	}

	static function addTags($ttid, $tags) {

		// addTags
		//		Add a list of tags to a TTindex

		foreach ($tags as $tag) {
			$t = trim($tag);
			if ($t == '') continue;
			logger::mark("ADD TAGS", "Adding Tag",$t,"to TTindex",$ttid);
			$tagindex = sql_prepare_query(false, null, 'Tagindex', null, "SELECT Tagindex FROM Tagtable WHERE Name=?", $t);
			if ($tagindex == null) $tagindex = romprmetadata::create_new_tag($t);
			if ($tagindex == null) {
				logger::fail("ADD TAGS", "    Could not create tag",$t);
				return false;
			}

			if ($result = generic_sql_query("INSERT INTO TagListtable (TTindex, Tagindex) VALUES ('".$ttid."', '".$tagindex."')", true)) {
				logger::trace("ADD TAGS", "Success");
			} else {
				// Doesn't matter, we have a UNIQUE constraint on both columns to prevent us adding the same tag twice
				logger::debug("ADD TAGS", "  .. Failed but that's OK if it's because of a duplicate entry or UNQIUE constraint");
			}
		}
		return true;
	}

	static function create_new_tag($tag) {

		// create_new_tags
		//		Creates a new entry in Tagtable
		//		Returns: Tagindex

		global $mysqlc;
		logger::mark("CREATE TAG", "Creating new tag",$tag);
		$tagindex = null;
		if (sql_prepare_query(true, null, null, null, "INSERT INTO Tagtable (Name) VALUES (?)", $tag)) {
			$tagindex = $mysqlc->lastInsertId();
		}
		return $tagindex;
	}

	static function remove_tag($ttid, $tag) {

		// remove_tags
		//		Removes a tag relation from a TTindex

		logger::mark("REMOVE TAG", "Removing Tag",$tag,"from TTindex",$ttid);
		$retval = false;
		if ($tagindex = simple_query('Tagindex', 'Tagtable', 'Name', $tag, false)) {
			$retval = generic_sql_query("DELETE FROM TagListtable WHERE TTindex = '".$ttid."' AND Tagindex = '".$tagindex."'", true);
		} else {
			logger::fail("REMOVE TAG", "  ..  Could not find tag",$tag);
		}
		return $retval;
	}

	static function remove_tag_from_db($tag) {
		logger::mark("REMOVE TAG", "Removing Tag",$tag,",from database");
		return sql_prepare_query(true, null, null, null, "DELETE FROM Tagtable WHERE Name=?", $tag);
	}

	static function delete_track($ttid) {
		if (remove_ttid($ttid)) {
		} else {
			header('HTTP/1.1 400 Bad Request');
		}
	}

	static function amend_album($albumindex, $newartist, $date) {
		logger::mark("AMEND ALBUM", "Updating Album index",$albumindex,"with new artist",$newartist,"and new date",$date);
		$artistindex = ($newartist == null) ? null : check_artist($newartist);
		$result = sql_prepare_query(false, PDO::FETCH_OBJ, null, null, "SELECT * FROM Albumtable WHERE Albumindex = ?", $albumindex);
		$obj = array_shift($result);
		if ($obj) {
			$params = array(
				'album' => $obj->Albumname,
				'albumai' => ($artistindex == null) ? $obj->AlbumArtistindex : $artistindex,
				'albumuri' => $obj->AlbumUri,
				'image' => $obj->Image,
				'date' => ($date == null) ? $obj->Year : $date,
				'searched' => $obj->Searched,
				'imagekey' => $obj->ImgKey,
				'ambid' => $obj->mbid,
				'domain' => $obj->Domain);
			$newalbumindex = check_album($params);
			if ($albumindex != $newalbumindex) {
				logger::log("AMEND ALBUM", "Moving all tracks from album",$albumindex,"to album",$newalbumindex);
				if (sql_prepare_query(true, null, null, null, "UPDATE Tracktable SET Albumindex = ? WHERE Albumindex = ?", $newalbumindex, $albumindex)) {
					logger::trace("AMEND ALBUM", "...Success");
				} else {
					logger::fail("AMEND ALBUM", "Track move Failed!");
					return false;
				}
			}
		} else {
			logger::error("AMEND ALBUM", "Failed to find album to update!");
			return false;
		}
		return true;
	}

}

function forcedUriOnly($u,$d) {

	// Some mopidy backends - YouTube and SoundCloud - can return the same artist/album/track info
	// for multiple different tracks.
	// This gives us a problem because romprmetadata::find_item will think they're the same.
	// So for those backends we always force urionly to be true
	logger::debug("USERRATINGS", "Checking domain : ".$d);

	if ($u || $d == "youtube" || $d == "soundcloud") {
		return true;
	} else {
		return false;
	}

}

function preparePlaylist() {
	generic_sql_query("DROP TABLE IF EXISTS pltable", true);
	generic_sql_query("CREATE TABLE pltable(TTindex INT UNSIGNED NOT NULL UNIQUE)", true);
}

function preparePlTrackTable() {
	generic_sql_query("DROP TABLE IF EXISTS pltracktable", true);
	generic_sql_query("CREATE TABLE pltracktable(TTindex INT UNSIGNED NOT NULL UNIQUE)", true);
}

function doPlaylist($playlist, $limit) {
	global $prefs;
	logger::blurt("SMARTRADIO", "Loading Playlist",$playlist,'limit',$limit);
	$sqlstring = "";
	$tags = null;
	$random = true;
	switch($playlist) {
		case "1stars":
			$sqlstring = "SELECT TTindex FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Uri
				IS NOT NULL AND Hidden=0 AND isSearchResult < 2 AND Rating > 0";
			break;
		case "2stars":
			$sqlstring = "SELECT TTindex FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Uri
				IS NOT NULL AND Hidden=0 AND isSearchResult < 2 AND Rating > 1";
			break;
		case "3stars":
			$sqlstring = "SELECT TTindex FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Uri
				IS NOT NULL AND Hidden=0 AND isSearchResult < 2 AND Rating > 2";
			break;
		case "4stars":
			$sqlstring = "SELECT TTindex FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Uri
				IS NOT NULL AND Hidden=0 AND isSearchResult < 2 AND Rating > 3";
			break;
		case "5stars":
			$sqlstring = "SELECT TTindex FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Uri
				IS NOT NULL AND Hidden=0 AND isSearchResult < 2 AND Rating > 4";
			break;
		case "favealbums":
		case "recentlyadded_byalbum":
			// This is a rather odd SQL query but it needs a WHERE clause and a JOIN with Tracktable
			// in order to work with the generic track dumping functions
			$sqlstring = "SELECT TTindex FROM pltracktable JOIN Tracktable USING (TTindex) WHERE TTindex > 0";
			$random = false;
			break;

		case "recentlyadded_random":
			$sqlstring = "SELECT TTindex FROM pltracktable JOIN Tracktable USING (TTindex) WHERE TTindex > 0";
			break;

		case "mostplayed":
			// Used to be tracks with above average playcount, now also includes any rated tracks.
			// Still called mostplayed :)
			$avgplays = getAveragePlays();
			$sqlstring = "SELECT TTindex FROM Tracktable JOIN Playcounttable USING (TTindex)
				LEFT JOIN Ratingtable USING (TTindex) WHERE Uri IS NOT NULL AND Hidden = 0 AND
				isSearchResult < 2 AND (Playcount > ".$avgplays." OR Rating IS NOT NULL)";
			break;

		case "allrandom":
			$sqlstring = "SELECT TTindex FROM Tracktable WHERE Uri IS NOT NULL AND Hidden=0 AND
				isSearchResult < 2";
			break;

		case "neverplayed":
			// LEFT JOIN (used here and above) means that the right-hand side of the JOIN will be
			// NULL if TTindex doesn't exist on that side. Very handy.
			$sqlstring = "SELECT Tracktable.TTindex FROM Tracktable LEFT JOIN Playcounttable ON
				Tracktable.TTindex = Playcounttable.TTindex WHERE Playcounttable.TTindex IS NULL";
			break;

		case "recentlyplayed":
			$sqlstring = recently_played_playlist();
			break;

		default:
			if (preg_match('/tag\+(.*)/', $playlist, $matches)) {
				$taglist = explode(',', $matches[1]);
				$sqlstring = 'SELECT DISTINCT TTindex FROM Tracktable JOIN TagListtable USING (TTindex) JOIN Tagtable USING (Tagindex) WHERE ';
				// Concatenate this bracket here otherwise Atom's syntax colouring goes haywire
				$sqlstring .= '(';
				$tags = array();
				foreach ($taglist as $i => $tag) {
					logger::mark("SMART RADIO", "Getting tag playlist for",$tag);
					$tags[] = trim($tag);
					if ($i > 0) {
						$sqlstring .= " OR ";
					}
					$sqlstring .=  "Tagtable.Name = ?";
				}
				$sqlstring .= ") AND Tracktable.Uri IS NOT NULL AND Tracktable.Hidden = 0 AND
					Tracktable.isSearchResult < 2 ";
			} else {
				logger::fail("SMART RADIO", "Unrecognised playlist",$playlist);
			}
			break;
	}
	$sqlstring .= ' AND (LinkChecked = 0 OR LinkChecked = 2) AND isAudiobook = 0';
	if ($prefs['collection_player'] == 'mopidy' && $prefs['player_backend'] == 'mpd') {
		$sqlstring .= ' AND Uri LIKE "local:%"';
	}
	$uris = getAllURIs($sqlstring, $limit, $tags, $random);
	$json = array();
	foreach ($uris as $u) {
		$json[] = array( 'type' => 'uri', 'name' => $u);
	}
	return $json;
}

function getAllURIs($sqlstring, $limit, $tags, $random = true) {

	// Get all track URIs using a supplied SQL string. For playlist generators
	$uris = array();
	$tries = 0;
	do {
		if ($tries == 1) {
			logger::log("SMART PLAYLIST", "No URIs found. Resetting history table");
			preparePlaylist();
		}
		generic_sql_query("CREATE TEMPORARY TABLE IF NOT EXISTS pltemptable(TTindex INT UNSIGNED NOT NULL UNIQUE)", true);
		theBabyDumper($sqlstring, $limit, $tags, $random);
		$uris = sql_get_column("SELECT Uri FROM Tracktable WHERE TTindex IN (SELECT TTindex FROM pltemptable)", 0);
		$tries++;
	} while (count($uris) == 0 && $tries < 2);
	generic_sql_query("INSERT INTO pltable (TTindex) SELECT TTindex FROM pltemptable", true);
	return $uris;
}

function theBabyDumper($sqlstring, $limit, $tags, $random) {
	logger::trace("SMART PLAYLIST", "Selector is ".$sqlstring);
	$rndstr = $random ? " ORDER BY ".SQL_RANDOM_SORT : " ORDER BY Albumindex, TrackNo";
	if ($tags) {
		sql_prepare_query(true, null, null, null,
			"INSERT INTO pltemptable(TTindex) ".$sqlstring.
			" AND NOT Tracktable.TTindex IN (SELECT TTindex FROM pltable)".$rndstr." LIMIT ".$limit, $tags);
	} else {
		generic_sql_query(
			"INSERT INTO pltemptable(TTindex) ".$sqlstring.
			" AND NOT Tracktable.TTindex IN (SELECT TTindex FROM pltable)".$rndstr." LIMIT ".$limit, true);
	}
}

function getAveragePlays() {
	$avgplays = simple_query('avg(Playcount)', 'Playcounttable', null, null, 0);
	return round($avgplays, 0, PHP_ROUND_HALF_DOWN);
}

?>
