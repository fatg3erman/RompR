<?php
class musicCollection extends collection_base {

	protected $albums = [];
	protected $find_track;
	protected $force_keys = [];

	public function __construct($opts = []) {
		$this->options = array_merge($this->options, $opts);
		if ($this->options['dbterms']['tag'] == null && $this->options['dbterms']['rating'] == null) {
			// Makes the if statement faster
			logger::core('MUSICCOLLECTION', 'No tags or ratings in use');
			$this->options['dbterms'] = true;
		}
		parent::__construct();
	}

	public function newTrack(&$filedata) {
		static $current_folder = null;
		if ($this->options['dbterms'] === true
			|| $this->check_url_against_database($filedata['file'], $this->options['dbterms']['tag'], $this->options['dbterms']['rating'])) {
			if ($this->options['doing_search']) {
				// If we're doing a search, we check to see if that track is in the database
				// because the user might have set the AlbumArtist to something different
				$filedata = array_replace($filedata, $this->get_extra_track_info($filedata));
			}

			$track = new track($filedata);
			if ($this->options['searchterms'] !== false && !$this->check_track_against_terms($track)) {
				return;
			}

			// Anything we want to force to a specific value - see do_update_with_command()
			foreach ($this->force_keys as $k => $v) {
				$filedata[$k] = $v;
			}

			if ($this->options['trackbytrack']) {
				if ($track->tags['albumartist'] !== null && $track->tags['Disc'] !== null) {
					//
					// With trackbytrack true, and the track having album artist and disc tags, we stick it
					// directly into the database
					//
					$this->do_track_by_track( $track );
				} else {
					//
					// With trackbytrack true, but the track lacking essential tags, we do folder by folder
					// - this means we don't use masses of RAM but we can cope with a messy folder full of
					// multiple badly tagged albums
					//
					if ($track->tags['folder'] != $current_folder) {
						$this->sort_badly_tagged_albums();
					}
					$albumkey = strtolower($track->tags['folder'].$track->tags['Album'].$track->tags['albumartist']);
					$current_folder = $track->tags['folder'];
					if (array_key_exists($albumkey, $this->albums)) {
						$this->albums[$albumkey]->newTrack($track);
					} else {
						$this->albums[$albumkey] = new album($track);
					}
				}
			} else {
				//
				// Otherwise we store it all up to the end and sort it all then.
				//
				$albumkey = strtolower($track->tags['folder'].$track->tags['Album'].$track->tags['albumartist']);
				if (array_key_exists($albumkey, $this->albums)) {
					$this->albums[$albumkey]->newTrack($track);
				} else {
					$this->albums[$albumkey] = new album($track);
				}
			}
		}
	}

	public function getAllTracks($cmd) {
		$tracks = array();
		foreach($this->albums as $album) {
			$tracks = array_merge($album->getAllTracks($cmd), $tracks);
		}
		return $tracks;
	}

	public function tracks_to_database() {
		// Fluch the previous albumobj from track_by_track
		global $performance;
		$timer = microtime(true);
		$nope = true;
		$this->do_track_by_track($nope);
		$this->sort_badly_tagged_albums();
		$performance['sorting'] = microtime(true) - $timer;
	}

	private function sort_badly_tagged_albums() {
		foreach ($this->albums as &$album) {
			$album->sortTracks();
			$album->check_database();
		}
		$this->albums = array();
	}

	public function tracks_as_array() {
		$c = true;
		$player = new player();
		print '[';
		foreach($this->albums as $album) {
			if ($c) {
				$c = false;
			} else {
				print ', ';
			}
			$album->sortTracks();
			print $album->dump_json($player);
		}
		print ']';
	}

	public function prepareCollectionUpdate() {
		$this->create_foundtracks();
		$this->prepare_findtracks();
	}

	public function find_justadded_albums() {
		return $this->sql_get_column("SELECT DISTINCT Albumindex FROM Tracktable WHERE justAdded = 1", 0);
	}

	public function set_image_for_album($albumindex, $image) {
		logger::log('MYSQL', 'Setting image for album',$albumindex,'to',$image);
		$this->sql_prepare_query(true, null, null, null, "UPDATE Albumtable SET Image = ?, Searched = 1 WHERE Albumindex = ?", $image, $albumindex);
	}

	public function replace_album_in_database($who, $new) {

		// This gets pretty messy. The easy case, with well-behaved backends, is we just delete
		// the old album and set the new one as its replacement.
		// BUT
		// If the album is the result of a search for 'Artist Name' but the Album Artist is Various Artists (or any other, different artist probably)
		// AND if only one track came back from the search, the original album will have Album Artist = 'Artist Name'. Sometimes. (youtube)
		// When we then look that up (at least with Youtube) it comes back with Artist = 'Various Artists' and NO Album Artist
		// EXCEPT for the track that exists which has Artist = 'Artist Name'. So that one ends up being added to the original album
		// which we immediately delete.
		// So we have to check what tracks exist on the original album then create or update tracks in the new album.
		// Note also that with Youtube the original track almost certainly doesn't have a Track Number, which means the lookup
		// has added a second version of it to the original album, which does have a track number. This doesn't matter though -
		// our first pass through the $tracks_now loop creates the original track with no track number but the second pass updates it.

		$tracks_now = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, [],
			"SELECT * FROM Tracktable JOIN Artisttable USING (Artistindex) WHERE Albumindex = ? AND Title NOT LIKE 'Album:%'",
			$who
		);
		$this->generic_sql_query("DELETE FROM Tracktable WHERE Albumindex = ".$who, true);
		$this->generic_sql_query("DELETE FROM Albumtable WHERE Albumindex = ".$who, true);
		$this->generic_sql_query('UPDATE Albumtable SET Albumindex = '.$who.' WHERE Albumindex = '.$new);
		$this->generic_sql_query('UPDATE Tracktable SET Albumindex = '.$who.' WHERE Albumindex = '.$new);
		foreach ($tracks_now as $track) {
			$ttids = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, [],
				"SELECT TTindex FROM Tracktable WHERE Albumindex = ? AND Title = ?",
				$who,
				$track['Title']
			);
			logger::log('BACKEND', 'Checking',$track['Title'],$track['Artistname'],$track['TTindex']);
			if (count($ttids) > 1) {
				logger::log("BACKEND", 'Ambiguous track',$track["Title"],'could not have trackartist reset');
			} else if (count($ttids) == 0) {
				logger::log("BACKEND", 'Could not find track',$track["Title"],'in newly created album. Ceating it.');
				$this->sql_prepare_query(true, null, null, null,
					"INSERT INTO Tracktable (Title, Albumindex, TrackNo, Duration, Artistindex, Disc, Uri, LastModified, Hidden, DateAdded, isSearchResult, isAudiobook, Genreindex, TYear)
					  VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
					$track['Title'],
					$who,
					$track['TrackNo'],
					$track['Duration'],
					$track['Artistindex'],
					$track['Disc'],
					$track['Uri'],
					$track['LastModified'],
					$track['Hidden'],
					$track['DateAdded'],
					$track['isSearchResult'],
					$track['isAudiobook'],
					$track['Genreindex'],
					$track['TYear']
				);
			} else {
				logger::log("BACKEND", 'Setting trackartist to',$track['Artistname'],'on TTindex',$ttids[0]['TTindex']);
				$this->sql_prepare_query(true, null, null, null,
					"UPDATE Tracktable SET Artistindex = ?, TrackNo = ? WHERE TTindex = ?",
					$track['Artistindex'],
					$track['TrackNo'],
					$ttids[0]['TTindex']
				);
			}
		}
	}

	public function add_browse_artist($artist) {
		logger::log('COLLECTION', 'Adding',$artist['Name'],$artist['Uri'],'to browse list');
		$index = $this->check_artist($artist['Name']);
		$this->sql_prepare_query(true, null, null, null,
			"INSERT INTO Artistbrowse (Artistindex, Uri) VALUES (?, ?)",
			$index, $artist['Uri']
		);
	}

	private function get_browse_uri($index) {
		return $this->simple_query('Uri', 'Artistbrowse', 'Artistindex', $index, null);
	}

	private function unbrowse_artist($index) {
		// Don't delete it, just set it to something that returns no results, otherwise skins
		// that delete their holders (like phone) will not use sortby_artist when they come back
		// into this panel.
		$this->sql_prepare_query(true, null, null, null,
			"UPDATE Artistbrowse SET Uri = 'dummy' WHERE Artistindex = ?",
			$index
		);
	}

	private function browse_podcast_search_result($uri) {
		logger::info("COLLECTION", "Browsing For Podcast ".substr($uri, 9));
		$podatabase = new poDatabase();
		$podid = $podatabase->getNewPodcast(substr($uri, 8), 0, false);
		logger::trace("ALBUMS", "Ouputting Podcast ID ",$podid);
		$podatabase->outputPodcast($podid, false);
	}

	public function check_album_browse($index) {
		$this->options['doing_search'] = true;
		$this->options['trackbytrack'] = true;

		$album_details = $this->get_album_details($index);
		$uri = $album_details['AlbumUri'];

		if (substr($uri, 0, 8) == 'podcast+') {
			$this->browse_podcast_search_result($uri);
			return true;
		}

		logger::info('COLLECTION', 'Browsing for album',$uri);
		$this->create_foundtracks();
		// We want the tracks we're browsing to appear under the same album. Sometimes Mopidy-YTMusic hasn't returned an album name
		// or some tracks have an album name and some haven't. It gets very confusing in the UI and for the backend
		// so we force the values of AlbumArtist and Album to be the same as the one we're browsing
		// whatever comes back from Mopidy. This isn't ideal but ytmusicapi seems very inconsistent
		// in what information it returns so we need to tidy it up.
		$this->do_update_with_command('find file "'.$uri.'"', array(), false, ['AlbumArtist' => $album_details['Artistname'], 'Album' => $album_details['Albumname']], []);
		$just_added = $this->find_justadded_albums();
		if (is_array($just_added) && count($just_added) > 0) {
			logger::log('BROWSEALBUM', 'We got a just modded response');
			$modded_album = array_pop($just_added);
			if ($modded_album == $index) {
				logger::log('BROWSEALBUM', 'We Modified existing album',$index);
				$this->rescan_zero_tracks($index);
				return $index;
			} else {
				// Just occasionally, the spotify album originally returned by search has an incorrect AlbumArtist
				// When we browse the album the new tracks therefore get added to a new album.
				// In this case we remove the old album and set the Albumindex of the new one to the Albumindex of the old one
				// (otherwise the GUI doesn't work). This shouldn't now be necessary as we force it above
				// but I eft this here just in case.
				logger::log('BROWSEALBUM', 'New album',$modded_album,'was created. Setting it to',$index);
				if ($album_details['Image'] != null) {
					$this->set_image_for_album($modded_album, $album_details['Image']);
				}
				$this->replace_album_in_database($index, $modded_album);
				$this->rescan_zero_tracks($index);
			}
		}

		return $index;
	}

	private function rescan_zero_tracks($index) {
		// This is a HACK. When browsing yt:playlist URIs we often end up getting details of an album that already exists
		// because it was returned as an album by search. Those tracks might not have track numbers and so they
		// get duplicated. But also sometimes for reasons beyond the realms of comprehension, sometimes this
		// browse DOESN't return all the tracks. I think we get two playlists that combine to one album because it's
		// a multi-disc set and Youtube can't just have one playlist for that can it, oh no, that would helpful.
		$tracks = $this->sql_prepare_query(false, PDO::FETCH_COLUMN, 0, [],
			"SELECT Title FROM Tracktable WHERE Albumindex = ? AND TrackNo > 0 AND isSearchResult > 0",
			$index
		);
		foreach ($tracks as $title) {
			$dumbass = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, 'TTindex', null,
				"SELECT TTindex FROM Tracktable WHERE Albumindex = ? AND Title = ? AND TrackNo = 0 AND isSearchResult = 2",
				$index,
				$title
			);
			if ($dumbass) {
				logger::log('BROWSEALBUM', 'Deleting TTindex',$dumbass,'because it now has a better copy with a track number');
				$this->sql_prepare_query(true, null, null, null,
					"DELETE FROM Tracktable WHERE TTindex = ?",
					$dumbass
				);
			}
		}
		$count = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, 'num', 0,
			"SELECT COUNT(TTindex) AS num FROM Tracktable WHERE Albumindex = ? AND Title NOT LIKE 'Album:%'",
			$index
		);
		if ($count > 0) {
			logger::log('BROWSEALBUM', 'Deleting Album link track because there are others');
			$this->sql_prepare_query(true, null, null, null,
				"DELETE FROM Tracktable WHERE Albumindex = ? AND Title LIKE 'Album:%'",
				$index
			);
		}

	}

	public function check_artist_browse($index) {
		$uri = $this->get_browse_uri($index);
		if ($uri == 'dummy') return true;
		if ($uri) {
			$this->options['doing_search'] = true;
			$this->options['trackbytrack'] = true;
			logger::info('COLLECTION', 'Browsing for artist',$uri);
			$this->do_update_with_command('find file "'.$uri.'"', array(), false, [], []);
			$this->unbrowse_artist($index);
			return true;
		} else {
			return false;
		}
	}

	public function do_update_with_command($cmd, $dirs, $domains, $force_keys, $mpdsearch) {
		logger::info('COLLECTION', 'Doing update with',$cmd);
		// In cases where we're browsing search results from eg youtube, the initial
		// Album Artist can sometimes be wrong, but we want it to be the same otherwise
		// the UI doesn't work, so force it.
		foreach ($force_keys as $k => $v) {
			logger::log('COLLECTION', 'Forcing',$k,'to',$v);
		}
		$this->force_keys = $force_keys;
		$this->open_transaction();
		$this->prepareCollectionUpdate();
		$player = new player();
		$player->initialise_search();
		if ($player->has_specific_search_function($mpdsearch, $domains)) {
			foreach ($player->search_function($mpdsearch, $domains) as $filedata) {
				$this->newTrack($filedata);
			}
		} else {
			foreach ($player->parse_list_output($cmd, $dirs, $domains) as $filedata) {
				$this->newTrack($filedata);
			}
		}
		$this->tracks_to_database();
		foreach ($player->to_browse as $artist) {
			$this->add_browse_artist($artist);
		}
		$this->close_transaction();
	}

	public function tidy_database() {
		// Find tracks that have been removed
		logger::mark('BACKEND', "Starting Cruft Removal");
		$now = time();
		logger::log('BACKEND', "Checking Wishlist");
		$wishlist = $this->pull_wishlist('date');
		foreach ($wishlist as $wishtrack) {
			$newtrack = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, array(),
				"SELECT TTindex FROM Tracktable WHERE
					Hidden = 0 AND Title = ? AND Artistindex = ? AND justAdded = 1 "
					.$this->track_date_check(ADDED_TODAY, false),
				$wishtrack['title'],
				$wishtrack['artistindex']
			);
			foreach ($newtrack AS $track) {
				logger::trace('COLLECTION', "We have found wishlist track",$wishtrack['Title'],'by',$wishtrack['trackartist'],'as TTindex',$newtrack['TTindex']);
				$this->sql_prepare_query(true, null, null, null,
					'UPDATE Ratingtable SET TTindex = ? WHERE TTindex = ?',
					$newtrack['TTindex'],
					$wishtrack['ttid']
				);
				$this->sql_prepare_query(true, null, null, null,
					'UPDATE TagListtable SET TTindex = ? WHERE TTindex = ?',
					$newtrack['TTindex'],
					$wishtrack['ttid']
				);
				$this->sql_prepare_query(true, null, null, null,
					'DELETE FROM Tracktable WHERE TTindex = ?',
					$wishtrack['ttid']
				);
			}
		}

		logger::log('BACKEND', "Finding tracks that have been deleted");
		$this->generic_sql_query("DELETE FROM Tracktable WHERE LastModified IS NOT NULL AND Hidden = 0 AND justAdded = 0", true);

		logger::log('BACKEND', "Making Sure Local Tracks Are Not Unplayable");
		$this->generic_sql_query("UPDATE Tracktable SET LinkChecked = 0 WHERE LinkChecked > 0 AND Uri LIKE 'local:%'", true);

		$this->remove_cruft();
		logger::log('COLLECTION', 'Updating collection version to', ROMPR_COLLECTION_VERSION);
		$this->update_stat('ListVersion',ROMPR_COLLECTION_VERSION);
		$this->update_track_stats();
		$dur = format_time(time() - $now);
		logger::trace('BACKEND', "Cruft Removal Took ".$dur);
	}

	public function prepare_findtracks() {
		if ($this->options['doing_search']) {
			$this->prepare_findtrack_for_search();
		} else {
			$this->prepare_findtrack_for_update();
		}
	}

	public function do_track_by_track(&$trackobject) {

		// Tracks must have disc and albumartist tags to be handled by this method.
		// Loads of static variables to speed things up - we don't have to look things up every time.

		static $current_albumartist = null;
		static $current_album = null;
		static $current_domain = null;
		static $albumobj = true;

		// === true is ever so slightly faster than === null
		if ($trackobject === true) {
			if ($albumobj !== true) {
				$albumobj->check_database();
				$albumobj = true;
			}
			return true;
		}

		if ($current_albumartist != $trackobject->tags['albumartist'] ||
			$current_album != $trackobject->tags['Album'] ||
			$current_domain != $trackobject->tags['domain']) {
			if ($albumobj !== true) {
				$albumobj->check_database();
			}

			$albumobj = new album($trackobject);
			$current_albumartist = $trackobject->tags['albumartist'];
			$current_album = $trackobject->tags['Album'];
			$current_domain = $trackobject->tags['domain'];
		} else {
			$albumobj->newTrack($trackobject);
		}
	}

	public function check_and_update_track(&$trackobj) {

		// Why are we not checking by URI? That should be unique, right?
		// Well, er. no. They're not.
		// Especially Spotify returns the same URI multiple times if it's in mutliple playlists
		// We really don't want to handle multiple versions of exactly the same track.

		// The other advantage of this is that we can put an INDEX on Albumindex, TrackNo, and Title,
		// which we can't do with Uri cos it's too long - this speeds the whole process up by a factor
		// of about 32 (9 minutes when checking by URI vs 15 seconds this way, on my collection)
		// Also, URIs might change if the user moves his music collection.

		// NOTE: It is imperative that the search results have been tidied up -
		// i.e. there are no 1s or 2s in the database before we do a collection update

		// When doing a search, we MUST NOT change lastmodified of any track, because this will cause
		// user-added tracks to get a lastmodified date, and lastmodified == NULL
		// is how we detect user-added tracks and prevent them being deleted on collection updates

		// isaudiobook is 2 for anything manually moved to Spoken Word - we don't want these being reset
		static $current_trackartist = null;
		static $trackartistindex = null;
		static $current_genre = null;
		static $genreindex = null;

		if ($trackobj->tags['Genre'] != $current_genre || $genreindex === null) {
			$current_genre = $trackobj->tags['Genre'];
			$genreindex = $this->check_genre($trackobj->tags['Genre']);
		}

		if ($trackobj->tags['trackartist_index'] === null) {
			$a = $trackobj->tags['trackartist'];
			if ($a != $current_trackartist || $trackartistindex == null) {
				if ($trackobj->tags['albumartist'] != $a && $a != '') {
					$trackartistindex = $this->check_artist($a);
				} else {
					$trackartistindex = $trackobj->tags['albumartist_index'];
				}
			}
			$current_trackartist = $a;
		} else {
			$trackartistindex = $trackobj->tags['trackartist_index'];
			$current_trackartist = null;
		}

		// logger::log('PARP','Albumindex',$trackobj->tags['album_index'],'Title',$trackobj->tags['Title']);

		if ($this->find_track->execute([
			$trackobj->tags['Title'],
			$trackobj->tags['album_index'],
			$trackobj->tags['Track'],
			$trackobj->tags['Time'],
			$trackartistindex,
			$trackobj->tags['Disc'],
			$trackobj->tags['file'],
			$trackobj->tags['Last-Modified'],
			$trackobj->tags['type'] == 'audiobook' ? 1 : 0,
			$genreindex,
			$trackobj->tags['year']
		])) {

		} else {
			$this->show_sql_error('AAAGH!', $this->find_track);
			exit(1);
		}

		$this->check_transaction();
	}

	public function update_album_mbid($mbid, $imgkey) {
		$this->sql_prepare_query(true, null, null, null, "UPDATE Albumtable SET mbid = ? WHERE ImgKey = ? AND mbid IS NULL", $mbid, $imgkey);
	}

}
?>