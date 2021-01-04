<?php
class musicCollection extends collection_base {

	private $albums = [];
	protected $find_track;

	public function __construct($opts = []) {
		$this->options = array_merge($this->options, $opts);
		if ($this->options['dbterms']['tag'] == null && $this->options['dbterms']['rating'] == null) {
			// Makes the if statement faster
			logger::log('MUSICCOLLECTION', 'No tags or ratings in use');
			$this->options['dbterms'] = true;
		}
		parent::__construct();
	}

	public function newTrack(&$filedata) {
		if ($this->options['dbterms'] === true || $this->check_url_against_database($filedata['file'], $this->options['dbterms']['tag'], $this->options['dbterms']['rating'])) {
			if ($this->options['doing_search']) {
				// If we're doing a search, we check to see if that track is in the database
				// because the user might have set the AlbumArtist to something different
				$filedata = array_replace($filedata, $this->get_extra_track_info($filedata));
			}

			$track = new track($filedata);
			if ($this->options['trackbytrack'] && $track->tags['albumartist'] !== null && $track->tags['Disc'] !== null) {
				$this->do_track_by_track( $track );
			} else {
				$albumkey = strtolower($track->tags['folder'].$track->tags['Album'].$track->tags['albumartist']);
				if (array_key_exists($albumkey, $this->albums)) {
					if ($this->options['allow_duplicates'] || $this->albums[$albumkey]->checkForDuplicate($track)) {
						$this->albums[$albumkey]->newTrack($track);
					}
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
		logger::mark('COLLECTION', 'Starting tracks_to_database');
		foreach ($this->albums as &$album) {
			$album->sortTracks();
			$album->check_database();
		}
		$this->albums = array();
		$performance['sorting'] = microtime(true) - $timer;
	}

	public function filter_duplicate_tracks() {
		$this->options['allow_duplicates'] = false;
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

	public function collectionUpdateRunning() {
		$cur = $this->simple_query('Value', 'Statstable', 'Item', 'Updating', null);
		switch ($cur) {
			case null:
				logger::warn('COLLECTION', 'Got null response to update lock check');
			case '0':
				$this->generic_sql_query("UPDATE Statstable SET Value = 1 WHERE Item = 'Updating'", true);
				return false;

			case '1':
				logger::error('COLLECTION', 'Multiple collection updates attempted');
				return true;
		}
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
		$this->remove_cruft();
		logger::log('COLLECTION', 'Updating collection version to', ROMPR_COLLECTION_VERSION);
		$this->update_stat('ListVersion',ROMPR_COLLECTION_VERSION);
		$this->update_track_stats();
		$dur = format_time(time() - $now);
		logger::info('BACKEND', "Cruft Removal Took ".$dur);
		logger::info('BACKEND', "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~");
	}

	public function clearUpdateLock() {
		logger::debug('COLLECTION', 'Clearing update lock');
		$this->generic_sql_query("UPDATE Statstable SET Value = 0 WHERE Item = 'Updating'", true);
	}

	public function prepare_findtracks() {
		if ($this->options['doing_search']) {
			$this->prepare_findtrack_for_search();
		} else {
			$this->prepare_findtrack_for_update();
		}
	}

	public function remove_findtracks() {
		$this->find_track = null;
	}

	public function do_track_by_track(&$trackobject) {

		// Tracks must have disc and albumartist tags to be handled by this method.
		// Loads of static variables to speed things up - we don't have to look things up every time.

		static $current_albumartist = null;
		static $current_album = null;
		static $current_domain = null;
		static $current_albumlink = null;
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
			$current_domain != $trackobject->tags['domain'] ||
			($trackobject->tags['X-AlbumUri'] != null && $trackobject->tags['X-AlbumUri'] != $current_albumlink)) {
			if ($albumobj !== true)
				$albumobj->check_database();

			$albumobj = new album($trackobject);
			$current_albumartist = $trackobject->tags['albumartist'];
			$current_album = $trackobject->tags['Album'];
			$current_domain = $trackobject->tags['domain'];
			$current_albumlink = $albumobj->tracks[0]->tags['X-AlbumUri'];
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

}
?>