<?php
class album {

	public $tracks;
	private $numOfDiscs;
	private $numOfTrackOnes;

	public function __construct(&$track) {
		$this->tracks = [$track];
		$this->numOfTrackOnes = $track->tags['Track'] == 1 ? 1 : 0;
		$this->numOfDiscs = $track->tags['Disc'];
	}

	public function newTrack(&$track) {
		$this->tracks[] = $track;
		// We use the first track to collate all the data from the tracks as they come in
		if ($this->tracks[0]->tags['albumartist'] === null) {
			$this->tracks[0]->tags['albumartist'] = $track->tags['albumartist'];
		}
		if ($this->tracks[0]->tags['X-AlbumImage'] === null) {
			$this->tracks[0]->tags['X-AlbumImage'] = $track->tags['X-AlbumImage'];
		}
		if ($this->tracks[0]->tags['year'] === null) {
			$this->tracks[0]->tags['year'] = $track->tags['year'];
		}
		if ($this->tracks[0]->tags['Date'] === null) {
			$this->tracks[0]->tags['Date'] = $track->tags['Date'];
		}
		if ($this->tracks[0]->tags['MUSICBRAINZ_ALBUMID'] === '') {
			$this->tracks[0]->tags['MUSICBRAINZ_ALBUMID'] = $track->tags['MUSICBRAINZ_ALBUMID'];
		}
		if ($this->tracks[0]->tags['X-AlbumUri'] === null) {
			$this->tracks[0]->tags['X-AlbumUri'] = $track->tags['X-AlbumUri'];
		}
		if ($track->tags['Disc'] !== null && $this->numOfDiscs < $track->tags['Disc']) {
			$this->numOfDiscs = $track->tags['Disc'];
		}
		if ($track->tags['Track'] == 1) {
			$this->numOfTrackOnes++;
		}
	}

	public function check_database() {
		// The indexes might have been looked up when we did get_extra_track_info, so no need
		// to look them up again. Saves a lot of time on search.
		if ($this->tracks[0]->tags['albumartist_index'] === null) {
			$this->tracks[0]->tags['albumartist_index'] = prefs::$database->check_artist($this->tracks[0]->tags['albumartist']);
		}
		if ($this->tracks[0]->tags['album_index'] === null) {
			$this->tracks[0]->tags['X-AlbumImage'] = $this->getImage('small');
			$albumindex = prefs::$database->check_album($this->tracks[0]->tags);
		} else {
			$albumindex = $this->tracks[0]->tags['album_index'];
		}
		foreach ($this->tracks as &$trackobj) {
			$trackobj->tags['album_index'] = $albumindex;
			prefs::$database->check_and_update_track($trackobj);
		}
	}

	public function getImage($size) {
		$albumimage = new baseAlbumImage(array(
			'baseimage' => ($this->tracks[0]->tags['X-AlbumImage']) ? $this->tracks[0]->tags['X-AlbumImage'] : '',
			'artist' => imageFunctions::artist_for_image($this->tracks[0]->tags['type'], $this->tracks[0]->tags['albumartist']),
			'album' => $this->tracks[0]->tags['Album']
		));
		$albumimage->check_image($this->tracks[0]->tags['domain'], $this->tracks[0]->tags['type']);
		$images = $albumimage->get_images();
		$this->tracks[0]->tags['ImgKey'] = $albumimage->get_image_key();
		return $images[$size];
	}

	public function trackCount() {
		return count($this->tracks);
	}

	public function getAllTracks($cmd) {
		$tracks = array();
		foreach ($this->tracks as $track) {
			if (preg_match('/:track:/', $track->tags['file'])) {
				$tracks[] = $cmd.' "'.format_for_mpd($track->tags['file']).'"';
			}
		}
		return $tracks;
	}

	public function sortTracks() {

		// Some Mopidy backends don't send disc numbers.When there are no disc numbers
		// multi-disc albums don't sort properly.
		// Hence we do a little check that we have have the same number of 'Track 1's
		// as discs and only do the sort if they're not the same. This'll also
		// sort out badly tagged local files. It's essential that disc numbers are set
		// because the database will not find the tracks otherwise.

		// Also here, because ths gets called always, we try to set our albumartist
		// to something sensible. So far it has been set to Composer tags if required by the
		// user, or to the AlbumArtist setting, which will be null if no AlbumArtist tag is present -
		// as is the case with many mopidy backends

		if ($this->tracks[0]->tags['albumartist'] === null) {
			logger::mark("COLLECTION", "Finding AlbumArtist for album ".$this->tracks[0]->tags['Album']);
			if (count($this->tracks) < ROMPR_MIN_TRACKS_TO_DETERMINE_COMPILATION) {
				logger::log("COLLECTION", "  Album ".$this->tracks[0]->tags['Album']." has too few tracks to determine album artist");
				$this->decideOnArtist($this->tracks[0]->get_sort_artist());
			} else {
				$artists = array();
				foreach ($this->tracks as $track) {
					$a = $track->get_sort_artist();
					if (!array_key_exists($a, $artists)) {
						$artists[$a] = 0;
					}
					$artists[$a]++;
				}
				$q = array_flip($artists);
				rsort($q);
				$candidate_artist = $q[0];
				$fraction = $artists[$candidate_artist]/count($this->tracks);
				logger::log("COLLECTION", "  Artist ".$candidate_artist." has ".$artists[$candidate_artist]." tracks out of ".count($this->tracks));
				if ($fraction > ROMPR_MIN_NOT_COMPILATION_THRESHOLD) {
					logger::log("COLLECTION", "    ... which is good enough. Album ".$this->tracks[0]->tags['Album']." is by ".$candidate_artist);
					$this->decideOnArtist($candidate_artist);
				} else {
					logger::log("COLLECTION", "   ... which is not enough");
					$this->decideOnArtist("Various Artists");
				}
			}
		}

		// if ($this->numOfDiscs > 0 && ($this->numOfTrackOnes <= 1 || $this->numOfTrackOnes == $this->numOfDiscs)) {
		// 	return $this->numOfDiscs;
		// }

		$tracks = array();
		$this->numOfDiscs = 0;
		foreach ($this->tracks as &$track) {
			if (!array_key_exists($track->tags['Track'], $tracks)) {
				$tracks[$track->tags['Track']] = 1;
			} else {
				$tracks[$track->tags['Track']]++;
			}
		}
		// Assign disc numbers in reverse order so that tracks that are later in the list
		// get higher disc numbers. This makes most sense.
		$this->tracks = array_reverse($this->tracks);
		foreach ($this->tracks as &$track) {
			if (!is_numeric($track->tags['Disc'])) {
				$track->tags['Disc'] = $tracks[$track->tags['Track']];
				$tracks[$track->tags['Track']]--;
			}
			$this->numOfDiscs = max($this->numOfDiscs, $track->tags['Disc']);
		}
		// Reverse the array again so they get added to the db in the same order we read them in
		// so that if all else fails they might come out in the right order
		$this->tracks = array_reverse($this->tracks);

		return $this->numOfDiscs;
	}

	public function checkForDuplicate($t) {
		foreach ($this->tracks as $track) {
			if ($t->tags['file'] == $track->tags['file']) {
				logger::trace("COLLECTION", "Filtering Duplicate Track ".$t->tags['file']);
				return false;
			}
		}
		return true;
	}

	private function decideOnArtist($candidate) {
		if ($this->tracks[0]->tags['albumartist'] === null) {
			logger::log("COLLECTION", "  ... Setting artist to ".$candidate);
			foreach ($this->tracks as $track) {
				$track->tags['AlbumArtist'] = [$candidate];
				$track->tags['albumartist'] = $candidate;
			}
		}
	}

}
?>