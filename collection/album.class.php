<?php
class album {

	public $tracks;

	public function __construct(&$track) {
		$this->tracks = [$track];
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
		if ($this->tracks[0]->tags['year'] === null && $track->tags['year'] !== null) {
			$this->tracks[0]->set_year($track->tags['year']);
		}
		if ($this->tracks[0]->tags['Date'] === null) {
			$this->tracks[0]->tags['Date'] = $track->tags['Date'];
		}
		if ($this->tracks[0]->tags['MUSICBRAINZ_ALBUMID'] === '') {
			$this->tracks[0]->tags['MUSICBRAINZ_ALBUMID'] = $track->tags['MUSICBRAINZ_ALBUMID'];
		}
		if ($this->tracks[0]->tags['X-AlbumUri'] === null) {
			if (strpos($track->tags['file'], ':album:') !== false || strpos($track->tags['file'], ':playlist:') !== false) {
				$this->tracks[0]->tags['X-AlbumUri'] = $track->tags['file'];
			} else {
				$this->tracks[0]->tags['X-AlbumUri'] = $track->tags['X-AlbumUri'];
			}
		}
	}

	// Munge our album data into somthing approaching a spotify album object so
	// we can pass it to spotifyAlbumThing.
	public function dump_json($player) {
		$image = $this->getImage('asdownloaded');
		// Is this too hacky? It is if we start returning LOTS of matches, it's going to
		// take a very long time. But otherwise we don't get any image at all for anything
		// that isn't from search results
		if ($player && !$image && prefs::get_pref('player_backend') == 'mopidy')
			$image = $player->find_album_image($this->tracks[0]->tags['X-AlbumUri']);

		$data = [
			'name' => $this->tracks[0]->tags['Album'],
			'artists' => [['name' => $this->tracks[0]->tags['albumartist']]],
			'uri' => $this->tracks[0]->tags['X-AlbumUri'],
			'id' => md5($this->tracks[0]->tags['X-AlbumUri']),
			'images' => [[
				'url' => $image,
				'width' => 0
			]],
			'domain' => $this->tracks[0]->tags['domain'],
			'tracks' => ['items' => []]
		];
		foreach ($this->tracks as $track) {
			$data['tracks']['items'][] = [
				'uri' => $track->tags['file'],
				'name' => $track->tags['Title'],
				'track_number' => $track->tags['Track'],
				'duration_ms' => $track->tags['Time']*1000,
				'artists' => [['name' => $track->tags['trackartist']]]
			];
		}
		return json_encode($data);
	}

	public function get_dummy_id() {
		return md5($this->tracks[0]->tags['X-AlbumUri']);
	}

	public function check_database() {
		// The indexes might have been looked up when we did get_extra_track_info, so no need
		// to look them up again. Saves a lot of time on search.
		if ($this->tracks[0]->tags['albumartist_index'] === null) {
			$this->tracks[0]->tags['albumartist_index'] = prefs::$database->check_artist($this->tracks[0]->tags['albumartist']);
		}
		if ($this->tracks[0]->tags['album_index'] === null) {
			$this->tracks[0]->tags['X-AlbumImage'] = $this->getImage('small');
			$this->tracks[0]->tags['album_index'] = prefs::$database->check_album($this->tracks[0]->tags);
		}
		foreach ($this->tracks as &$trackobj) {
			$trackobj->tags['album_index'] = $this->tracks[0]->tags['album_index'];
			$trackobj->tags['albumartist_index'] = $this->tracks[0]->tags['albumartist_index'];
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

	public function get_album_name() {
		return $this->tracks[0]->tags['Album'];
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

		// Sort the tracks into discs. This is important because some backends (eg Spotify)
		// don't return disc numbers and multi-disc albums don't sort properly (and could possibly
		// result in duplicate tracks)

		// Do NOT attempt to assign track numbers to tracks that don't have them (Track == 0)
		// because that means what's in the db doesn't match what comes from the player,
		// which means get_extra_track_info doesn't match the track.

		// The most likely case for Track == 0 is from a backend that doesn't support track numbers
		// because they're meaningless - eg YouTube, Soundcloud
		// But another possibility is just badly tagged local tracks. What we really don't want to do
		// in that case is end up with an album with 14 discs each with one track, becvause it looks shit.
		// so Track zeroes have some special handling.

		// The following will fail if we have an album where all the tracks has no track number, no disc
		// number, and the same title. We'll regard them all as the same track. But that's correct.

		$tracks = array();
		foreach ($this->tracks as &$track) {
			if ($track->tags['Track'] == 0 && $track->tags['Disc'] === null)
				$track->tags['Disc'] = 1;

			if (array_key_exists($track->tags['Track'], $tracks)) {
				$tracks[$track->tags['Track']]++;
			} else {
				$tracks[$track->tags['Track']] = 1;
			}
		}
		// Assign disc numbers in reverse order so that tracks that are later in the list
		// get higher disc numbers. This makes most sense.
		$this->tracks = array_reverse($this->tracks);
		foreach ($this->tracks as &$track) {
			if ($track->tags['Disc'] === null) {
				$track->tags['Disc'] = $tracks[$track->tags['Track']];
				$tracks[$track->tags['Track']]--;
			}
		}
		// Reverse the array again so they get added to the db in the same order we read them in
		// so that if all else fails they might come out in the right order
		$this->tracks = array_reverse($this->tracks);

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

	public function check_ytmusic_lookup() {
		logger::debug('ALBUM', 'Uri is',$this->tracks[0]->tags['X-AlbumUri']);
		if (strpos($this->tracks[0]->tags['X-AlbumUri'], 'ytmusic:') !== false) {
			logger::log('ALBUM', 'Forcing lookup of album', $this->tracks[0]->tags['X-AlbumUri']);
			return $this->tracks[0]->tags['X-AlbumUri'];
		}
		return false;
	}

}
?>