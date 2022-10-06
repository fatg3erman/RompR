<?php
class musicCollection extends collection_base {

	private $albums = [];
	protected $find_track;

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
		if ($this->options['dbterms'] === true || $this->check_url_against_database($filedata['file'], $this->options['dbterms']['tag'], $this->options['dbterms']['rating'])) {
			if ($this->options['doing_search']) {
				// If we're doing a search, we check to see if that track is in the database
				// because the user might have set the AlbumArtist to something different
				$filedata = array_replace($filedata, $this->get_extra_track_info($filedata));
			}

			$track = new track($filedata);
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
		print '[';
		foreach($this->albums as $album) {
			$image = $album->getImage('asdownloaded');
			logger::log("COLLECTION", "Doing Album",$album->tracks[0]->tags['Album']);
			$album->sortTracks();
			foreach($album->tracks as $trackobj) {
				if ($c) {
					$c = false;
				} else {
					print ', ';
				}
				$trackobj->tags['X-AlbumImage'] = $image;
				logger::trace("COLLECTION", "Title - ".$trackobj->tags['Title']);
				print json_encode($trackobj->tags);
			}
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
		$this->generic_sql_query("DELETE FROM Tracktable WHERE Albumindex = ".$who, true);
		$this->generic_sql_query("DELETE FROM Albumtable WHERE Albumindex = ".$who, true);
		$this->generic_sql_query('UPDATE Albumtable SET Albumindex = '.$who.' WHERE Albumindex = '.$new);
		$this->generic_sql_query('UPDATE Tracktable SET Albumindex = '.$who.' WHERE Albumindex = '.$new);
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
		logger::trace("COLLECTION", "Browsing For Podcast ".substr($uri, 9));
		$podatabase = new poDatabase();
		$podid = $podatabase->getNewPodcast(substr($uri, 8), 0, false);
		logger::log("ALBUMS", "Ouputting Podcast ID ".$podid);
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

		logger::log('COLLECTION', 'Browsing for album',$uri);
		$this->do_update_with_command('find file "'.$uri.'"', array(), false);
		// Just occasionally, the spotify album originally returned by search has an incorrect AlbumArtist
		// When we browse the album the new tracks therefore get added to a new album.
		// In this case we remove the old album and set the Albumindex of the new one to the Albumindex of the old one
		// (otherwise the GUI doesn't work)
		$just_added = $this->find_justadded_albums();
		if (is_array($just_added) && count($just_added) > 0 && $just_added[0] != $index) {
			logger::log('BROWSEALBUM', 'New album',$just_added[0],'was created. Setting it to',$index);
			if ($album_details['Image'] != null) {
				$this->set_image_for_album($just_added[0], $album_details['Image']);
			}
			$this->replace_album_in_database($index, $just_added[0]);
		}
		return false;
	}

	public function check_artist_browse($index) {
		$uri = $this->get_browse_uri($index);
		if ($uri == 'dummy') return true;
		if ($uri) {
			$this->options['doing_search'] = true;
			$this->options['trackbytrack'] = true;
			logger::log('COLLECTION', 'Browsing for artist',$uri);
			$this->do_update_with_command('find file "'.$uri.'"', array(), false);
			$this->unbrowse_artist($index);
			return true;
		} else {
			return false;
		}
	}

	public function do_update_with_command($cmd, $dirs, $domains) {
		logger::log('COLLECTION', 'Doing update with',$cmd);
		$this->open_transaction();
		$this->prepareCollectionUpdate();
		$player = new player();
		$player->initialise_search();
		foreach ($player->parse_list_output($cmd, $dirs, $domains) as $filedata) {
			$this->newTrack($filedata);
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
		logger::trace('BACKEND', "Checking Wishlist");
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
				logger::log('COLLECTION', "We have found wishlist track",$wishtrack['Title'],'by',$wishtrack['albumartist'],'as TTindex',$newtrack['TTindex']);
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

		logger::trace('BACKEND', "Finding tracks that have been deleted");
		$this->generic_sql_query("DELETE FROM Tracktable WHERE LastModified IS NOT NULL AND Hidden = 0 AND justAdded = 0", true);

		logger::trace('BACKEND', "Making Sure Local Tracks Are Not Unplayable");
		$this->generic_sql_query("UPDATE Tracktable SET LinkChecked = 0 WHERE LinkChecked > 0 AND Uri LIKE 'local:%'", true);

		$this->remove_cruft();
		logger::log('COLLECTION', 'Updating collection version to', ROMPR_COLLECTION_VERSION);
		$this->update_stat('ListVersion',ROMPR_COLLECTION_VERSION);
		$this->update_track_stats();
		$dur = format_time(time() - $now);
		logger::info('BACKEND', "Cruft Removal Took ".$dur);
		logger::info('BACKEND', "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~");
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

	public function do_raw_search($domains, $checkdb, $rawterms, $command) {
		$found = false;
		logger::trace("RAW SEARCH", "domains are ".print_r($domains, true));
		logger::trace("RAW SEARCH", "terms are   ".print_r($rawterms, true));
		if ($checkdb !== 'false') {
			logger::trace("RAW SEARCH", " ... checking database first ");
			$collection = new db_collection();
			$t = $collection->doDbCollection($rawterms, $domains, true);
			foreach ($t as $filedata) {
				$this->newTrack($filedata);
				$found = true;
			}
		}
		if (!$found) {
			foreach ($rawterms as $key => $term) {
				$command .= " ".$key.' "'.format_for_mpd(html_entity_decode($term[0])).'"';
			}
			logger::trace("RAW SEARCH", "Search command : ".$command);
			$player = new player();
			$dirs = array();
			foreach ($player->parse_list_output($command, $dirs, $domains) as $filedata) {
				$this->newTrack($filedata);
			}
		}
	}

	public function fave_finder($params) {
		logger::log('FAVEFINDER', 'Looking for',$params['Artist'],$params['Title']);
		$rp = prefs::get_radio_params();
		$st = [];
		if ($params['Artist'])
			$st[] = $params['Artist'];
		if ($params['Title'])
			$st[] = $params['Title'];
		$this->do_raw_search($rp['radiodomains'], 'false', ['any' => [implode(' ', $st)]], 'search');
		$matches = [];
		foreach ($this->albums as $album) {
			$album->sortTracks();
			foreach($album->tracks as $trackobj) {
				if ($this->is_artist_or_album($trackobj->tags['file']))
					continue;

				if ($trackobj->tags['Title'] == null && $trackobj->tags['trackartist'] == null)
					continue;

				if ($this->compare_tracks_with_artist($params, $trackobj->tags)) {
					logger::log('FAVEFINDER', 'Found',$trackobj->tags['trackartist'],$trackobj->tags['Title']);
					// Prioritise tracks with Album information. ytmusic often doesn't return this.
					if ($trackobj->tags['Album']) {
						array_unshift($matches, $trackobj->tags);
					} else {
						array_push($matches, $trackobj->tags);
					}
				}
			}
		}
		$this->albums = [];
		return $matches;
	}

}
?>