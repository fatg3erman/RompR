<?php

$numtracks = 0;
$numalbums = 0;
$numartists = 0;
$totaltime = 0;
$count = 1;
$divtype = "album1";
$dbterms = array( 'tags' => null, 'rating' => null );
$trackbytrack = false;

define('ROMPR_MIN_TRACKS_TO_DETERMINE_COMPILATION', 3);
define('ROMPR_MIN_NOT_COMPILATION_THRESHOLD', 0.6);

class musicCollection {

	public function __construct() {
		$this->albums = array();
		$this->filter_duplicates = false;
	}

	public function newTrack(&$filedata) {

		global $trackbytrack, $doing_search;

		if ($doing_search) {
			// If we're doing a search, we check to see if that track is in the database
			// because the user might have set the AlbumArtist to something different
			$filedata = array_replace($filedata, get_extra_track_info($filedata));
		}

		$track = new track($filedata);
		if ($trackbytrack && $filedata['AlbumArtist'] && $filedata['Disc'] !== null) {
			do_track_by_track( $track );
		} else {
			$albumkey = md5($track->tags['folder'].strtolower($track->tags['Album']).strtolower($track->get_sort_artist(true)));
			if (array_key_exists($albumkey, $this->albums)) {
				if (!$this->filter_duplicates || !$this->albums[$albumkey]->checkForDuplicate($track)) {
					$this->albums[$albumkey]->newTrack($track);
				}
			} else {
				$this->albums[$albumkey] = new album($track);
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
		do_track_by_track(null);
		foreach ($this->albums as $album) {
			$album->sortTracks();
			$album->check_database();
		}
		$this->albums = array();
	}

	public function get_albumartist_by_folder($f) {
		foreach ($this->albums as $album) {
			if ($album->folder == $f) {
				logger::trace("COLLECTION", "   Found albumartist by folder",$album->artist);
				return $album->artist;
			}
		}
		return null;
	}

	public function filter_duplicate_tracks() {
		$this->filter_duplicates = true;
	}

	public function tracks_as_array() {
		$results = array();
		foreach($this->albums as $album) {
			logger::log("COLLECTION", "Doing Album",$album->name);
			$album->sortTracks();
			foreach($album->tracks as $trackobj) {
				$track = array(
					"uri" => $trackobj->tags['file'],
					"album" => $album->name,
					"title" => $trackobj->tags['Title'],
					"artist" => $trackobj->get_artist_string(),
					"albumartist" => $album->artist,
					"trackno" => $trackobj->tags['Track'],
					"disc" => $trackobj->tags['Disc'],
					"albumuri" => $album->uri,
					"image" => $album->getImage('asdownloaded'),
					"duration" => $trackobj->tags['Time'],
					"date" => $album->datestamp,
					"genre" => $trackobj->tags['Genre']
				);
				logger::trace("COLLECTION", "Title - ".$trackobj->tags['Title']);
				// A lot of code that depends on this was written to handle mopidy model search results.
				// The above is not mopidy model, so friggicate it into just such a thing
				$d = getDomain($track['uri']);
				if (!array_key_exists($d, $results)) {
					logger::log("COLLECTION", "Creating Results Set For ".$d);
					$results[$d] = array(
						"tracks" => array(),
						"uri" => $d.':bodgehack'
					);
				}
				array_push($results[$d]['tracks'], $track);
			}
		}
		return $results;
	}

}

class album {

	public function __construct(&$track) {
		global $numalbums;
		$numalbums++;
		$this->tracks = array($track);
		// Sets album artist to composer (if set and required) or albumartist but NOT trackartist
		// therefore may still be null at this point.
		$this->artist = $track->get_sort_artist(true);
		$this->name = trim($track->tags['Album']);
		$this->folder = $track->tags['folder'];
		$this->musicbrainz_albumid = $track->tags['MUSICBRAINZ_ALBUMID'];
		$this->datestamp = $track->tags['Date'];
		$this->uri = $track->tags['X-AlbumUri'];
		$this->numOfDiscs = $track->tags['Disc'];
		$this->image = $track->tags['X-AlbumImage'];
		$this->key = $track->tags['ImgKey'];
		$this->numOfDiscs = $track->tags['Disc'];
		$this->numOfTrackOnes = $track->tags['Track'] == 1 ? 1 : 0;
		$this->domain = $track->tags['domain'];
		$this->albumartistindex = null;
		$this->albumindex = null;
		$this->year = $track->tags['year'];
	}

	public function newTrack(&$track) {
		$this->tracks[] = $track;
		if ($this->artist == null) {
			$this->artist = $track->get_sort_artist(true);
		}
		if ($this->image == null) {
			$this->image = $track->tags['X-AlbumImage'];
		}
		if ($this->datestamp == null) {
			$this->datestamp = $track->tags['Date'];
		}
		if ($this->musicbrainz_albumid == '') {
			$this->musicbrainz_albumid = $track->tags['MUSICBRAINZ_ALBUMID'];
		}
		if ($track->tags['Disc'] !== null && $this->numOfDiscs < $track->tags['Disc']) {
			$this->numOfDiscs = $track->tags['Disc'];
		}
		if ($track->tags['Track'] == 1) {
			$this->numOfTrackOnes++;
		}
		if ($this->uri == null) {
			$this->uri = $track->tags['X-AlbumUri'];
		}
	}

	public function check_database() {
		if ($this->albumartistindex == null) {
			$this->albumartistindex = check_artist($this->artist);
		}
		if ($this->albumindex == null) {
			$album = array(
				'album' => $this->name,
				'albumai' => $this->albumartistindex,
				'albumuri' => $this->uri,
				'image' => $this->getImage('small'),
				'date' => $this->year,
				'searched' => "0",
				'imagekey' => $this->getKey(),
				'ambid' => $this->musicbrainz_albumid,
				'domain' => $this->domain
			);
			$this->albumindex = check_album($album);
		}
		if ($this->albumindex) {
			foreach ($this->tracks as $trackobj) {
				check_and_update_track($trackobj, $this->albumindex, $this->albumartistindex, $this->artist);
			}
		}
	}

	public function getKey() {
		return $this->key;
	}

	public function getImage($size) {
		$albumimage = new baseAlbumImage(array(
			'baseimage' => ($this->image) ? $this->image : '',
			'artist' => imageFunctions::artist_for_image($this->tracks[0]->tags['type'], $this->artist),
			'album' => $this->name
		));
		$albumimage->check_image($this->domain, $this->tracks[0]->tags['type']);
		$images = $albumimage->get_images();
		$this->key = $albumimage->get_image_key();
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

	public function sortTracks($always = false) {

		// NB. BLOODY WELL CALL THIS FUNCTION
		// Unless you're so sure you know how all this works and you really don't need it.
		// Collection updates might be one such area but if you're not sure CALL IT ANYWAY and see what happens.

		// Some Mopidy backends don't send disc numbers. If we're using the sql backend
		// we don't really need to pre-sort tracks because we can do it on the fly.
		// However, when there are no disc numbers multi-disc albums don't sort properly.
		// Hence we do a little check that we have have the same number of 'Track 1's
		// as discs and only do the sort if they're not the same. This'll also
		// sort out badly tagged local files. It's essential that disc numbers are set
		// because the database will not find the tracks otherwise.

		// Also here, because ths gets called always, we try to set our albumartist
		// to something sensible. So far it has been set to Composer tags if required by the
		// user, or to the AlbumArtist setting, which will be null if no AlbumArtist tag is present -
		// as is the case with many mopidy backends

		if ($this->artist == null) {
			logger::mark("COLLECTION", "Finding AlbumArtist for album ".$this->name);
			if (count($this->tracks) < ROMPR_MIN_TRACKS_TO_DETERMINE_COMPILATION) {
				logger::log("COLLECTION", "  Album ".$this->name." has too few tracks to determine album artist");
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
					logger::log("COLLECTION", "    ... which is good enough. Album ".$this->name." is by ".$candidate_artist);
					$this->artist = $candidate_artist;
				} else {
					logger::log("COLLECTION", "   ... which is not enough");
					$this->decideOnArtist("Various Artists");
				}
			}
		}

		foreach ($this->tracks as $track) {
			$track->tags['AlbumArtist'] = $this->artist;
		}

		if ($always == false && $this->numOfDiscs > 0 && ($this->numOfTrackOnes <= 1 || $this->numOfTrackOnes == $this->numOfDiscs)) {
			return $this->numOfDiscs;
		}

		$discs = array();
		$number = 1;
		foreach ($this->tracks as $ob) {
			if ($ob->tags['Track'] !== '') {
				$track_no = intval($ob->tags['Track']);
			} else {
				$track_no = $number;
			}
			# Just in case we have a multiple disc album with no disc number tags
			$discno = intval($ob->tags['Disc']);
			if ($discno == '' || $discno == null || $discno == 0) {
				$discno = 1;
			}
			if (!array_key_exists($discno, $discs)) {
				$discs[$discno] = array();
			}
			while(array_key_exists($track_no, $discs[$discno])) {
				$discno++;
				if (!array_key_exists($discno, $discs)) {
					$discs[$discno] = array();
				}
			}
			$discs[$discno][$track_no] = $ob;
			$ob->updateTrackInfo(array('Disc' => $discno));
			$number++;
		}
		$numdiscs = count($discs);

		$this->tracks = array();
		ksort($discs, SORT_NUMERIC);
		foreach ($discs as $disc) {
			ksort($disc, SORT_NUMERIC);
			$this->tracks = array_merge($this->tracks, $disc);
		}
		$this->numOfDiscs = $numdiscs;

		return $numdiscs;
	}

	public function checkForDuplicate($t) {
		foreach ($this->tracks as $track) {
			if ($t->tags['file'] == $track->tags['file']) {
				logger::trace("COLLECTION", "Filtering Duplicate Track ".$t->tags['file']);
				return true;
			}
		}
		return false;
	}

	private function decideOnArtist($candidate) {
		if ($this->artist == null) {
			logger::log("COLLECTION", "  ... Setting artist to ".$candidate);
			$this->artist = $candidate;
		}
	}

}

class track {

	public $tags;

	public function __construct(&$filedata) {
		$this->tags = $filedata;
	}

	public function updateTrackInfo($info) {
		$this->tags = array_replace($this->tags, $info);
	}

	public function get_artist_string() {
		return format_artist($this->tags['Artist']);
	}

	public function get_sort_artist($return_albumartist = false) {
		return format_sortartist($this->tags, $return_albumartist);
	}

}

?>
