<?php

require_once ('getid3/getid3.php');

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
						'genre',
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
		// One of these following two is redundant, but code needs tidying VASTLY before I can unpick that
		// i.e. why aren't we using ROMPR_FILE_MODEL for this?
		$data['date'] = (array_key_exists('date', $data) && $data['date'] != 0) ? getYear($data['date']) : null;
		$data['year'] = (array_key_exists('date', $data) && $data['date'] != 0) ? getYear($data['date']) : null;
		$data['urionly'] = array_key_exists('urionly', $data) ? true : false;
		$data['disc'] = array_key_exists('disc', $data) ? $data['disc'] : 1;
		$data['domain'] = array_key_exists('domain', $data) ? $data['domain'] : ($data['uri'] === null ? "local" : getDomain($data['uri']));
		$data['hidden'] = 0;
		if ($data['genre'] === null) {
			logger::warn('SANITISER', 'Track has NULL genre');
			$data['genre'] = 'None';
		}
		$data['genreindex'] = check_genre($data['genre']);
		$data['searchflag'] = 0;
		if (substr($data['image'],0,4) == "http") {
			$data['image'] = "getRemoteImage.php?url=".rawurlencode($data['image']);
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
			logger::error("SET", "Something is not set", $data);
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
			logger::log("SET", "Created New Track with TTindex ".$ttids[0]);
		}

		if (count($ttids) > 0) {
			if (romprmetadata::doTheSetting($ttids, $data['attributes'], $data['uri'])) {
			} else {
				header('HTTP/1.1 417 Expectation Failed');
				$returninfo['error'] = 'Setting attributes failed';
			}
		} else {
			logger::warn("SET", "TTID Not Found");
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
				logger::mark("ADD", "Track ".$ttid." being added is a search result or a hidden track");
				// Setting attributes (Rating: 0) will unhide/un-searchify it. Ratings of 0 are got rid of
				// by remove_cruft at the end, because they're meaningless
				if ($data['attributes'] == null) {
					$data['attributes'] = array(array('attribute' => 'Rating', 'value'=> 0));
				}
			} else {
				logger::warn("ADD", "Track being added already exists");
			}
		}

		check_for_wishlist_track($data);

		if ($ttid == null) {
			logger::log("ADD", "Creating Track being added");
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
			logger::error("INC", "Something is not set",$data);
			header('HTTP/1.1 400 Bad Request');
			print json_encode(array('error' => 'Artist or Title or Attributes not set'));
			exit(0);
		}
		$ttids = romprmetadata::find_item($data, forcedUriOnly(false,getDomain($data['uri'])));
		if (count($ttids) == 0) {
			logger::trace("INC", "Doing an INCREMENT action - Found NOTHING so creating hidden track");
			$data['hidden'] = 1;
			$ttids[0] = create_new_track($data);
		}

		romprmetadata::checkLastPlayed($data);

		if (count($ttids) > 0) {
			foreach ($ttids as $ttid) {
				logger::trace("INC", "Doing an INCREMENT action - Found TTID ",$ttid);
				foreach ($data['attributes'] as $pair) {
					logger::log("INC", "(Increment) Setting",$pair["attribute"],"to",$pair["value"],"on",$ttid);
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

	public static function youtubedl($data) {
		// logger::log('YOUTUBEDL', print_r($data, true));
		$ytdl_path = find_executable('youtube-dl');
		if ($ytdl_path === false) {
			logger::error('YOUTUBEDL', 'youtube-dl binary could not be found');
			header("HTTP/1.1 404 Not Found");
			print json_encode(array('error' => 'Could not find youtube-dl'));
			exit(0);
		}
		logger::log('YOUTUBEDL', 'youtube-dl is at',$ytdl_path);
		$avconv_path = find_executable('avconv');
		if ($avconv_path === false) {
			$avconv_path = find_executable('ffmpeg');
			if ($avconv_path === false) {
				logger::error('YOUTUBEDL', 'Could not find avconv or ffmpeg');
				header("HTTP/1.1 404 Not Found");
				print json_encode(array('error' => 'Could not find avconv or ffmpeg'));
				exit(0);
			}
		}
		$a = preg_match('/:video\/.*\.(.+)$/', $data['uri'], $matches);
		if ($a) {

			$uri_to_get = 'https://youtu.be/'.$matches[1];
			logger::log('YOUTUBEDL', 'Downloading',$uri_to_get);

			$info = sql_prepare_query(false, PDO::FETCH_ASSOC, null, array(),
				"SELECT Title, Artistname FROM Tracktable JOIN Artisttable USING (Artistindex) WHERE Uri = ?",
				$data['uri']
			);
			if (is_array($info) && count($info) > 0) {
				logger::log('YOUTUBEDL', '  Title is',$info[0]['Title']);
				logger::log('YOUTUBEDL', '  Artist is',$info[0]['Artistname']);
			} else {
				loger::log('YOUTUBEDL', '  Could not find title and artist from collection');
			}

			chdir('prefs/youtubedl');
			$ttindex = simple_query('TTindex', 'Tracktable', 'Uri', $data['uri'], null);
			if ($ttindex === null) {
				logger::error('YOUTUBEDL', 'Could not locate that URI in the database!');
				header("HTTP/1.1 404 Not Found");
				print json_encode(array('error' => 'Could not locate that track in the database!'));
				exit(0);
			}
			file_put_contents('dlprogress', $ttindex."\n");
			if (!is_dir($ttindex)) {
				mkdir($ttindex);
			}
			chdir($ttindex);
			file_put_contents('original.uri', $uri_to_get);
			exec($ytdl_path.'youtube-dl --ffmpeg-location '.$avconv_path.' --extract-audio --write-thumbnail --restrict-filenames --newline --audio-format flac --audio-quality 0 '.$uri_to_get.' >> ../dlprogress 2>&1', $output, $retval);
			if ($retval != 0) {
				logger::error('YOUTUBEDL', 'youtube-dl returned error code', $retval);
				header("HTTP/1.1 404 Not Found");
				print json_encode(array('error' => 'youtube-dl returned error code '.$retval));
				exit(0);
			}
			$files = glob('*.flac');
			if (count($files) == 0) {
				logger::error('YOUTUBEDL', 'Could not find downloaded flac file in prefs/youtubedl/'.$ttindex);
				header("HTTP/1.1 404 Not Found");
				print json_encode(array('error' => 'Could not locate downloaded flac file!'));
				exit(0);
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
				$tagwriter->remove_other_tags = false;
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

			$new_uri = dirname(dirname(get_base_url())).'/prefs/youtubedl/'.$ttindex.'/'.$files[0];
			logger::log('YOUTUBEDL', 'New URI is', $new_uri);
			sql_prepare_query(true, null, null, null,
				"UPDATE Tracktable SET Uri = ? WHERE Uri = ?",
				$new_uri,
				$data['uri']
			);
			// Ready for the next one if there is one
			chdir('../../..');
		} else {
			logger::error('YOUTUBEDL', 'Could not match URI',$data['uri']);
			header("HTTP/1.1 404 Not Found");
			print json_encode(array('error' => 'Could not match URI '.$data['uri']));
			exit(0);
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
		foreach ($ttids as $ttid) {
			logger::log("SYNCINC", "Doing a SYNC action on TTID ".$ttid,'LastPlayed is',$data['lastplayed']);
			$rowcount = generic_sql_query("UPDATE Playcounttable SET SyncCount = SyncCount - 1, LastPlayed = '".$data['lastplayed']."' WHERE TTindex = ".$ttid." AND SyncCount > 0",
				false, null, null, null, true);
			if ($rowcount > 0) {
				logger::trace("SYNCINC", "  Decremented sync counter for this track");
			} else {
				$rowcount = generic_sql_query("UPDATE Playcounttable SET Playcount = Playcount + 1, LastPlayed = '".$data['lastplayed']."' WHERE TTindex = ".$ttid,
					false, null, null, null, true);
				if ($rowcount > 0) {
					logger::trace("SYNCINC", "  Incremented Playcount for this track");
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
			"UPDATE PodcastTracktable SET Listened = ?, New = ? WHERE Title = ? AND Artist = ?",
			1,
			0,
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
					logger::log("REMOVE", "Removing",$pair);
					$r = romprmetadata::remove_tag($ttid, $pair["value"]);
					if ($r == false) {
						logger::warn("REMOVE", "FAILED Removing",$pair);
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
			logger::warn("USERRATING", "TTID Not Found");
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
				logger::trace("BACKEND", "Updating album MBID ".$data['attributes']." from TTindex ".$ttid);
				$albumindex = simple_query('Albumindex', 'Tracktable', 'TTindex', $ttid, null);
				logger::debug("BACKEND", "   .. album index is ".$albumindex);
				sql_prepare_query(true, null, null, null, "UPDATE Albumtable SET mbid = ? WHERE Albumindex = ? AND mbid IS NULL",$data['attributes'],$albumindex);
			}
		}
		$returninfo = $nodata;
	}

	public static function updateAudiobookState($data) {
		$ttids = romprmetadata::find_item($data, forcedUriOnly(false, getDomain($data['uri'])));
		if (count($ttids) > 0) {
			foreach ($ttids as $ttid) {
				logger::log('SQL', 'Setting Audiobooks state for TTIndex',$ttid,'to',$data['isaudiobook']);
				sql_prepare_query(true, null, null, null, 'UPDATE Tracktable SET isAudiobook = ? WHERE TTindex = ?', $data['isaudiobook'], $ttid);
			}
		}
	}

	public static function cleanup($data) {
		logger::info("CLEANUP", "Doing Database Cleanup And Stats Update");
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

	public static function deletealbum($data) {
		if ($data['albumindex'] !== null && romprmetadata::delete_album($data['albumindex'])) {
		} else {
			header('HTTP/1.1 400 Bad Request');
			$returninfo['error'] = 'That just did not work';
		}
	}

	public static function setasaudiobook($data) {
		if ($data['albumindex'] !== null && romprmetadata::set_as_audiobook($data['albumindex'], $data['value'])) {
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
			logger::debug("MONKEYS", " ... Success!");
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
			logger::info("TIMINGS", "    Found TTindex(es)",$ttids,"in",$time,"seconds");
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
		logger::mark("FIND ITEM", "Looking for item ".$data['title']);
		$ttids = array();
		if ($urionly && $data['uri']) {
			logger::log("FIND ITEM", "  Trying by URI ".$data['uri']);
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
					logger::log("FIND ITEM", "  Trying by albumartist",$data['albumartist'],"album",$data['album'],"title",$data['title'],"track number",$data['trackno']);
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
					logger::log("FIND ITEM", "  Trying by albumartist",$data['albumartist'],"album",$data['album'],"and title",$data['title']);
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
					logger::log("FIND ITEM", "  Trying by artist",$data['artist'],",album",$data['album'],"and title",$data['title']);
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
					logger::log("FIND ITEM", "  Trying by (wishlist) artist",$data['artist'],"and title",$data['title']);
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
				logger::log("FIND ITEM", "  Trying by artist",$data['artist'],"Uri NOT NULL and title",$data['title']);
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
					logger::log("FIND ITEM", "  Trying by (wishlist) artist",$data['artist'],"and title",$data['title']);
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

		logger::log("INCREMENT", "Setting",$attribute,"to",$value,"for TTID",$ttid);
		if (sql_prepare_query(true, null, null, null, "REPLACE INTO ".$attribute."table (TTindex, ".$attribute.", LastPlayed) VALUES (?, ?, ?)", $ttid, $value, $lp)) {
			logger::debug("INCREMENT", " .. success");
		} else {
			logger::warn("INCREMENT", "FAILED Setting",$attribute,"to",$value,"for TTID",$ttid);
			return false;
		}
		return true;

	}

	static function set_attribute($ttid, $attribute, $value) {

		// set_attribute
		//		Sets an attribute (Rating, Tag etc) on a TTindex.
		logger::log("ATTRIBUTE", "Setting",$attribute,"to",$value,"on",$ttid);
		if (sql_prepare_query(true, null, null, null, "REPLACE INTO ".$attribute."table (TTindex, ".$attribute.") VALUES (?, ?)", $ttid, $value)) {
			logger::debug("ATTRIBUTE", "  .. success");
		} else {
			logger::warn("ATTRIBUTE", "FAILED Setting",$attribute,"to",$value,"on",$ttid);
			return false;
		}
		return true;
	}

	static function doTheSetting($ttids, $attributes, $uri) {
		global $returninfo;
		$result = true;
		logger::debug("USERRATING", "Checking For attributes");
		if ($attributes !== null) {
			logger::debug("USERRATING", "Setting attributes");
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
				romprmetadata::check_audiobook_status($ttid);
				if ($uri) {
					$returninfo['metadata'] = get_all_data($ttid);
				}
			}
		}
		return $result;
	}

	static function check_audiobook_status($ttid) {
		global $prefs;
		$albumindex = generic_sql_query("SELECT Albumindex FROM Tracktable WHERE TTindex = ".$ttid, false, null, 'Albumindex', null);
		if ($albumindex !== null) {
			$sorter = choose_sorter_by_key('zalbum'.$albumindex);
			$lister = new $sorter('zalbum'.$albumindex);
			if ($lister->album_trackcount($albumindex) > 0) {
				logger::log('USERRATING', 'Album '.$albumindex.' is an audiobook, updating track audiobook state');
				generic_sql_query("UPDATE Tracktable SET isAudiobook = 2 WHERE TTindex = ".$ttid);
			}
		}
	}

	static function addTags($ttid, $tags) {

		// addTags
		//		Add a list of tags to a TTindex

		foreach ($tags as $tag) {
			$t = trim($tag);
			if ($t == '') continue;
			logger::log("ADD TAGS", "Adding Tag",$t,"to TTindex",$ttid);
			$tagindex = sql_prepare_query(false, null, 'Tagindex', null, "SELECT Tagindex FROM Tagtable WHERE Name=?", $t);
			if ($tagindex == null) $tagindex = romprmetadata::create_new_tag($t);
			if ($tagindex == null) {
				logger::warn("ADD TAGS", "    Could not create tag",$t);
				return false;
			}

			if ($result = generic_sql_query("INSERT INTO TagListtable (TTindex, Tagindex) VALUES ('".$ttid."', '".$tagindex."')", true)) {
				logger::debug("ADD TAGS", "Success");
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

		logger::log("REMOVE TAG", "Removing Tag",$tag,"from TTindex",$ttid);
		$retval = false;
		if ($tagindex = simple_query('Tagindex', 'Tagtable', 'Name', $tag, false)) {
			$retval = generic_sql_query("DELETE FROM TagListtable WHERE TTindex = '".$ttid."' AND Tagindex = '".$tagindex."'", true);
		} else {
			logger::warn("REMOVE TAG", "  ..  Could not find tag",$tag);
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

	static function delete_album($albumindex) {
		$result = generic_sql_query('DELETE FROM Tracktable WHERE Albumindex = '.$albumindex);
		return true;
	}

	static function set_as_audiobook($albumindex, $value) {
		$result = sql_prepare_query(true, null, null, null, 'UPDATE Tracktable SET isAudiobook = ?, justAdded = 1 WHERE Albumindex = ?', $value, $albumindex);
		return $result;
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
	generic_sql_query('UPDATE Tracktable SET usedInPlaylist = 0 WHERE usedInPlaylist = 1');
	init_random_albums();
}

function doPlaylist($playlist, $limit) {
	global $prefs;
	logger::mark("SMARTRADIO", "Loading Playlist",$playlist,'limit',$limit);
	$sqlstring = "";
	$tags = null;
	$random = true;
	switch($playlist) {
		case "1stars":
			$sqlstring = "SELECT Uri FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Rating > 0";
			break;
		case "2stars":
			$sqlstring = "SELECT Uri FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Rating > 1";
			break;
		case "3stars":
			$sqlstring = "SELECT Uri FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Rating > 2";
			break;
		case "4stars":
			$sqlstring = "SELECT Uri FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Rating > 3";
			break;
		case "5stars":
			$sqlstring = "SELECT Uri FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Rating > 4";
			break;

		case "recentlyadded_byalbum":
			$random = false;
		case "recentlyadded_random":
			$sqlstring = sql_recent_tracks();
			break;

		case "favealbums":
			$avgplays = getAveragePlays();
			$sqlstring = "SELECT Uri FROM Tracktable JOIN Albumtable USING (Albumindex)
				WHERE Albumindex IN
				(SELECT Albumindex FROM Tracktable JOIN Playcounttable USING (TTindex)
				LEFT JOIN Ratingtable USING (TTindex) WHERE Uri IS NOT NULL
				AND (Playcount > ".$avgplays." OR Rating IS NOT NULL))";
			$random = false;
			break;

		case "mostplayed":
			// Used to be tracks with above average playcount, now also includes any rated tracks.
			// Still called mostplayed :)
			$avgplays = getAveragePlays();
			$sqlstring = "SELECT Uri FROM Tracktable JOIN Playcounttable USING (TTindex)
				LEFT JOIN Ratingtable USING (TTindex) WHERE Uri IS NOT NULL
				AND (Playcount > ".$avgplays." OR Rating IS NOT NULL)";
			break;

		case "allrandom":
			$sqlstring = "SELECT Uri FROM Tracktable WHERE Uri IS NOT NULL AND Hidden=0 AND
				isSearchResult < 2";
			break;

		case "neverplayed":
			$sqlstring = "SELECT Tracktable.Uri FROM Tracktable LEFT JOIN Playcounttable ON
				Tracktable.TTindex = Playcounttable.TTindex WHERE Playcounttable.TTindex IS NULL";
			break;

		case "recentlyplayed":
			$sqlstring = recently_played_playlist();
			break;

		default:
			if (preg_match('/^(\w+)\+(.+)$/', $playlist, $matches)) {
				$fn = 'smart_radio_'.$matches[1];
				list($sqlstring, $tags) = $fn($matches[2]);
			} else {
				logger::warn("SMART RADIO", "Unrecognised playlist",$playlist);
			}
			break;
	}
	$sqlstring .= ' AND (LinkChecked = 0 OR LinkChecked = 2) AND isAudiobook = 0 AND usedInPlaylist = 0 AND isSearchResult < 2 AND Hidden = 0 AND Uri IS NOT NULL';
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

function smart_radio_tag($param) {
	$taglist = explode(',', $param);
	$sqlstring = 'SELECT DISTINCT Uri FROM Tracktable JOIN TagListtable USING (TTindex) JOIN Tagtable USING (Tagindex) WHERE ';
	// Concatenate this bracket here otherwise Atom's syntax colouring goes haywire
	$sqlstring .= '(';
	$tags = array();
	foreach ($taglist as $i => $tag) {
		logger::trace("SMART RADIO", "Getting tag playlist for",$tag);
		$tags[] = strtolower(trim($tag));
		if ($i > 0) {
			$sqlstring .= " OR ";
		}
		$sqlstring .=  "LOWER(Tagtable.Name) = ?";
	}
	$sqlstring .= ")";
	return array($sqlstring, $tags);
}

function smart_radio_genre($param) {
	$genrelist = explode(',', $param);
	$sqlstring = 'SELECT DISTINCT Uri FROM Tracktable JOIN Genretable USING (Genreindex) WHERE ';
	// Concatenate this bracket here otherwise Atom's syntax colouring goes haywire
	$sqlstring .= '(';
	$tags = array();
	foreach ($genrelist as $i => $genre) {
		logger::trace("SMART RADIO", "Getting genre playlist for",$genre);
		$tags[] = strtolower(trim($genre));
		if ($i > 0) {
			$sqlstring .= " OR ";
		}
		$sqlstring .=  "LOWER(Genre) = ?";
	}
	$sqlstring .= ")";
	return array($sqlstring, $tags);
}

function smart_radio_artist($param) {
	$artistlist = explode(',', $param);
	$sqlstring = 'SELECT DISTINCT Uri FROM Tracktable JOIN Artisttable USING (Artistindex) WHERE ';
	// Concatenate this bracket here otherwise Atom's syntax colouring goes haywire
	$sqlstring .= '(';
	$tags = array();
	foreach ($artistlist as $i => $artist) {
		logger::trace("SMART RADIO", "Getting artist playlist for",$artist);
		$tags[] = strtolower(trim($artist));
		if ($i > 0) {
			$sqlstring .= " OR ";
		}
		$sqlstring .=  "LOWER(Artistname) = ?";
	}
	$sqlstring .= ")";
	return array($sqlstring, $tags);
}

function smart_radio_custom($param) {
	$station = json_decode(file_get_contents('prefs/customradio/'.format_for_disc($param).'.json'), true);
	$tags = array();
	$sqlstring = "SELECT DISTINCT Uri FROM
		Tracktable
		JOIN Artisttable AS ta USING (Artistindex)
		JOIN Albumtable USING (Albumindex)
		JOIN Artisttable AS aa ON (Albumtable.AlbumArtistindex = aa.Artistindex)
		LEFT JOIN Genretable USING (Genreindex)
		LEFT JOIN Ratingtable USING (TTindex)
		LEFT JOIN Playcounttable USING (TTindex)
		LEFT JOIN TagListtable USING (TTindex)
		LEFT JOIN Tagtable USING (Tagindex)
		WHERE (";
	foreach ($station['rules'] as $i => $rule) {
		if ($i > 0) {
			$sqlstring .= $station['combine_option'];
		}
		$values = explode(',', $rule['value']);
		$sqlstring .= '(';
		foreach ($values as $j => $value) {
			logger::log('CUSTOMRADIO',$rule['db_key'],$value);
			if ($j > 0) {
				switch ($rule['option']) {
					case RADIO_RULE_OPTIONS_STRING_IS:
					case RADIO_RULE_OPTIONS_STRING_CONTAINS:
						$sqlstring .= ' OR ';
						break;

					case RADIO_RULE_OPTIONS_STRING_IS_NOT:
					case RADIO_RULE_OPTIONS_STRING_NOT_CONTAINS:
						$sqlstring .= ' AND ';
						break;

					default:
						logger::error('CUSTOMRADIO', 'Multiple values in integer option!');
						break;
				}
			}

			if (preg_match('/db_function_(.+)$/', $rule['db_key'], $matches)) {
				$function = $matches[1];
				$sqlstring .= $function($rule['option'], trim($value));
			} else {
				switch ($rule['option']) {
					case RADIO_RULE_OPTIONS_STRING_IS:
						$tags[] = strtolower(trim($value));
						$sqlstring .= 'LOWER('.$rule['db_key'].') = ?';
						break;

					case RADIO_RULE_OPTIONS_STRING_IS_NOT:
						$tags[] = strtolower(trim($value));
						$sqlstring .= 'LOWER('.$rule['db_key'].') IS NOT ?';
						break;
						break;

					case RADIO_RULE_OPTIONS_STRING_CONTAINS:
						$tags[] = "%".strtolower(trim($value))."%";
						$sqlstring .= 'LOWER('.$rule['db_key'].") LIKE ?";
						break;

					case RADIO_RULE_OPTIONS_STRING_NOT_CONTAINS:
						$tags[] = "%".strtolower(trim($value))."%";
						$sqlstring .= 'LOWER('.$rule['db_key'].") NOT LIKE ?";
						break;

					case RADIO_RULE_OPTIONS_INTEGER_LESSTHAN:
						$tags[] = trim($value);
						$sqlstring .= $rule['db_key'].' < ?';
						break;

					case RADIO_RULE_OPTIONS_INTEGER_EQUALS:
						$tags[] = trim($value);
						$sqlstring .= $rule['db_key'].' = ?';
						break;

					case RADIO_RULE_OPTIONS_INTEGER_GREATERTHAN:
						$tags[] = trim($value);
						$sqlstring .= $rule['db_key'].' > ?';
						break;

					case RADIO_RULE_OPTIONS_STRING_EXISTS:
						$sqlstring .= $rule['db_key'].' IS NOT NULL';
						break;

					default:
						logger::error('CUSTOMRADIO', 'Unknown Option Value',$rule['option']);
						break;

				}
			}

		}
		$sqlstring.= ')';

	}
	$sqlstring .= ")";

	return array($sqlstring, $tags);
}

function getAllURIs($sqlstring, $limit, $tags, $random = true) {

	// Get all track URIs using a supplied SQL string. For playlist generators
	$uris = array();
	$tries = 0;
	$rndstr = $random ? " ORDER BY ".SQL_RANDOM_SORT : " ORDER BY randomSort, Albumindex, Disc, TrackNo";
	$sqlstring .= ' '.$rndstr.' LIMIT '.$limit;
	logger::log('GETALLURIS', $sqlstring);
	if ($tags) {
		foreach ($tags as $t) {
			logger::log('GETALLURILS', '  Param :',$t);
		}
	}
	do {
		if ($tries == 1) {
			logger::info("SMART PLAYLIST", "No URIs found. Resetting history table");
			preparePlaylist();
		}
		if ($tags) {
			$uris = sql_prepare_query(false, PDO::FETCH_COLUMN, 'Uri', null, $sqlstring, $tags);
		} else {
			$uris = sql_get_column($sqlstring, 'Uri');
		}
		$tries++;
	} while (count($uris) == 0 && $tries < 2);
	foreach ($uris as $uri) {
		sql_prepare_query(true, null, null, null, 'UPDATE Tracktable SET usedInPlaylist = 1 WHERE Uri = ?', $uri);
	}
	if (count($uris) == 0) {
		$uris = array('NOTRACKS!');
	}
	return $uris;
}

function getAveragePlays() {
	$avgplays = simple_query('avg(Playcount)', 'Playcounttable', null, null, 0);
	return round($avgplays, 0, PHP_ROUND_HALF_DOWN);
}

?>
