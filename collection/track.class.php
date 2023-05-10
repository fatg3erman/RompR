<?php
class track {

	public $tags;

	public function __construct(&$filedata) {
		$this->tags = $filedata;
		// On creation, we set the 'albumartist' tag according to the user's
		// Album Artist sort preference. Note that this will be null
		// if the AlbumArtist tag is not set (or if we are using Composer adn that is null too)
		// We do this simply for speed otherwise we end up loking it up twice for every track
		// in the case where this remains as null we add it to the pile and
		// sort it out later.
		$this->format_sortartist();
		// $this->tags['albumartist'] = format_sortartist($this->tags);
		$this->tags['trackartist'] = format_artist($this->tags['Artist'], '');
		if (is_numeric($this->tags['year']) && $this->tags['year'] > 2155)
			$this->tags['year'] = null;

	}

	private function format_sortartist() {
		$sortartist = null;
		if ($this->is_classical()) {
			// Still do is_classical() because that sets a flag we use in playlistCollection.
			// We also always need to set the musicbrainz ids to nothing because with the swapping we're doing
			// we just can't keep things in sync
			$this->tags['MUSICBRAINZ_ALBUMARTISTID'] = '';
			$this->tags['MUSICBRAINZ_ARTISTID'] = array('');

			if (!$this->tags['is_tracklist']) {
				// DON'T do this if we're getting the play queue, because in that case we've done a
				// get_extra_track_info which returns what this did when we created the collection,
				// so if we do this again we just swap it back over.
				switch (prefs::get_pref('classical_rule')) {
					case prefs::CLASSICAL_RULES_USE_ARTIST:
						if ($this->tags['Artist']) {
							$sortartist = $this->tags['Artist'];
							$this->swap_albumartist();
						}
						break;

					case prefs::CLASSICAL_RULES_USE_COMPOSER:
						if ($this->tags['Composer']) {
							$sortartist = $this->tags['Composer'];
							$this->swap_albumartist();
						}
						break;
				}
			}
		}

		if ($sortartist === null)
			$sortartist = $this->tags['AlbumArtist'];

		$this->tags['albumartist'] = concatenate_artist_names($sortartist);
	}

	private function swap_albumartist() {
		if (is_array($this->tags['AlbumArtist'])) {
			// If we have an AlbumArtist, junk the first entry because that's almost always the artist
			// but often in a different form.
			$trash = array_shift($this->tags['AlbumArtist']);
			if (count($this->tags['AlbumArtist']) > 0) {
				$this->tags['Artist'] = $this->tags['AlbumArtist'];
			}
		}
	}

	private function is_classical() {
		if (!prefs::get_pref('useclassicalrules'))
			return false;

		// Detect if it is classical
		if ($this->checkClassicalGenre()) {
			$this->tags['is_classical'] = true;
			return true;
		}
		$cd = prefs::get_pref('classicalfolder');
		if ($cd == '')
			return false;
		$f = rawurldecode($this->tags['folder']);
		if (strpos($f, $cd) === 0) {
			$this->tags['is_classical'] = true;
			return true;
		}
		return false;
	}

	private function checkClassicalGenre() {
		if (!$this->tags['Genre'])
			return false;
		$cg = prefs::get_pref('classicalgenres');
		if (!is_array($cg) || count($cg) == 0 || $cg[0] == '')
			return false;

		$gl = strtolower($this->tags['Genre']);
		foreach ($cg as $g) {
			if ($gl == strtolower($g)) {
				return true;
			}
		}
		return false;
	}

	// MySQL YEAR must be < 2155. I have one where the year is 2810. Obvs a typo
	// but it causes an Exception, and it fucks things up.
	public function set_year($year) {
		if (is_numeric($year) && $year < 2156)
			$this->tags['year'] = $year;
	}

	public function get_sort_artist() {
		// Used when albumartist is not set (bevause there's either no
		// composer or albumartist tags
		$sortartist = null;
		if ($this->tags['Artist'] !== null) {
			$sortartist = $this->tags['Artist'];
		} else if ($this->tags['station'] !== null) {
			$sortartist = $this->tags['station'];
		}
		return concatenate_artist_names($sortartist);
	}

}
?>