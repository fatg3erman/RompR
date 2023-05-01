<?php

class playlistCollection extends musicCollection {

	private $foundartists;

	public function doNewPlaylistFile(&$filedata) {
		if (!is_array($filedata))
			return array_replace(MPD_FILE_MODEL, ROMPR_FILE_MODEL);

		// Translate from MPD_FILE_MODEL to ROMPR_FILE_MODEL
		$filedata = array_replace(MPD_FILE_MODEL, ROMPR_FILE_MODEL, $filedata, $this->get_extra_track_info($filedata));

		$track = new track($filedata);
		if ($track->tags['albumartist'] === null)
			$track->tags['albumartist'] = $track->get_sort_artist();

		$track->tags['Id'] = (int) $track->tags['Id'];

		if ($track->tags['Title'] === null)
			$track->tags['Title'] = '';

		// Bloody spotify often returns album artist = 'A & B' but track artists 'B' and 'A'.
		// This screws up the playcount stats. They're also not consistent with
		// capitalisation of articles in Album Artists
		if (is_array($filedata['Artist']) && count($filedata['Artist']) > 1) {
			$trackartist_backwards = format_artist(array_reverse($filedata['Artist']),'');
			if (strtolower($trackartist_backwards) == strtolower($track->tags['albumartist'])) {
				$track->tags['Artist'] = array_reverse($track->tags['Artist']);
				$track->tags['trackartist'] = $trackartist_backwards;
			}
		}

		$albumimage = new baseAlbumImage(array(
			'baseimage' => $track->tags['X-AlbumImage'],
			'artist' => imageFunctions::artist_for_image($track->tags['type'], $track->tags['albumartist']),
			'album' => $track->tags['Album'],
			'key' => $track->tags['ImgKey']
		));
		$albumimage->check_image($track->tags['domain'], $track->tags['type'], true);

		$track->tags['ImgKey'] = $albumimage->get_image_key();
		$track->tags['images'] = $albumimage->get_images();
		$track->tags['metadata']['track']['name'] = trim($track->tags['Title']);
		$track->tags['metadata']['track']['musicbrainz_id'] = trim($track->tags['MUSICBRAINZ_TRACKID']);
		$track->tags['metadata']['track']['artist'] = trim($track->tags['trackartist']);
		$track->tags['metadata']['album']['name'] = trim($track->tags['Album']);
		$track->tags['metadata']['album']['artist'] = trim($track->tags['albumartist']);
		$track->tags['metadata']['album']['musicbrainz_id'] = trim($track->tags['MUSICBRAINZ_ALBUMID']);
		$track->tags['metadata']['album']['uri'] = $track->tags['X-AlbumUri'];
		if ($track->tags['X-AlbumUri'] && getDomain($track->tags['X-AlbumUri']) == 'spotify') {
			$info['metadata']['album']['spotify'] = array(
				'id' => substr($track->tags['X-AlbumUri'], 14)
			);
		}

		$this->foundartists = array();

		// All kinds of places we get artist names from:
		// Composer, Performer, Track Artist, Album Artist
		// Note that we filter duplicates
		// This creates the metadata array used by the info panel and nowplaying -
		// Metadata such as scrobbles and ratings will still use the Album Artist

		if (prefs::get_pref('displaycomposer')) {
			// The user has chosen to display Composer/Perfomer information
			// Here check:
			// a) There is composer/performer information AND
			// bi) Specific Genre Selected, Track Has Genre, Genre Matches Specific Genre OR
			// bii) No Specific Genre Selected, Track Has Genre
			if (($track->tags['Composer'] !== null || $track->tags['Performer'] !== null) &&
				((prefs::get_pref('composergenre') && $track->tags['Genre'] &&
					checkComposerGenre($track->tags['Genre'], prefs::get_pref('composergenrename'))) ||
				(!prefs::get_pref('composergenre') && $track->tags['Genre'])))
			{
				// Track Genre matches selected 'Sort By Composer' Genre
				// Display Compoer - Performer - AlbumArtist
				$this->do_composers($track->tags);
				$this->do_performers($track->tags);
				// The album artist probably won't be required in this case, but use it just in case
				$this->do_albumartist($track->tags);
				// Don't do track artist as with things tagged like this this is usually rubbish
			} else {
				// Track Genre Does Not Match Selected 'Sort By Composer' Genre
				// Or there is no composer/performer info
				// Do Track Artist - Album Artist - Composer - Performer
				$this->do_track_artists($track->tags);
				$this->do_albumartist($track->tags);
				$this->do_performers($track->tags);
				$this->do_composers($track->tags);
			}
			if ($track->tags['Composer'] !== null || $track->tags['Performer'] !== null) {
				$track->tags['metadata']['iscomposer'] = 'true';
			}
		} else {
			// The user does not want Composer/Performer information
			$this->do_track_artists($track->tags, $track->tags['albumartist']);
			$this->do_albumartist($track->tags, $track->tags['albumartist']);
		}

		if (count($track->tags['metadata']['artists']) == 0) {
			array_push($track->tags['metadata']['artists'], array( "name" => "", "musicbrainz_id" => ""));
		}

		$track->tags['metadata']['track']['backendid'] = $track->tags['Id'];
		$track->tags['metadata']['album']['backendid'] = $track->tags['Id'];

		return $track->tags;
	}

	private function do_composers(&$info) {
		if ($info['Composer'] == null) {
			return;
		}
		foreach ($info['Composer'] as $comp) {
			if ($this->artist_not_found_yet($comp)) {
				array_push($info['metadata']['artists'], array( "name" => trim($comp), "musicbrainz_id" => "", "type" => "composer", "ignore" => "false", 'backendid' => $info['Id']));
			}
		}
	}

	private function do_performers(&$info) {
		if ($info['Performer'] == null) {
			return;
		}
		foreach ($info['Performer'] as $comp) {
			$toremove = null;
			foreach($info['metadata']['artists'] as $i => $artist) {
				if ($artist['type'] == "albumartist" || $artist['type'] == "artist") {
					if (strtolower($artist['name'] ==  strtolower($comp))) {
						$toremove = $i;
						break;
					}
				}
			}
			if ($toremove !== null) {
				array_splice($info['metadata']['artists'], $toremove, 1);
			}

			if ($toremove !== null || $this->artist_not_found_yet($comp)) {
				array_push($info['metadata']['artists'], array( "name" => trim($comp), "musicbrainz_id" => "", "type" => "performer", "ignore" => "false", 'backendid' => $info['Id']));
			}
		}
	}

	private function do_albumartist(&$info) {
		$aartist = null;
		if (!($info['type'] == "stream" && $info['albumartist'] == "Radio") &&
			strtolower($info['albumartist']) != "various artists" &&
			strtolower($info['albumartist']) != "various")
		{
			$aartist = $info['albumartist'];
		}
		if ($aartist !== null && $this->artist_not_found_yet($aartist)) {
			array_push($info['metadata']['artists'], array( "name" => trim($aartist), "musicbrainz_id" => trim($info['MUSICBRAINZ_ALBUMARTISTID']), "type" => "albumartist", "ignore" => "false", 'backendid' => $info['Id']));
		}
	}

	private function do_track_artists(&$info) {
		if ($info['Artist'] == null) {
			return;
		}
		$artists = getArray($info['Artist']);
		$mbids = $info['MUSICBRAINZ_ARTISTID'];
		if (count($mbids) > count($artists)) {
			// More MBIDs that Artists. This might be one of those daft things where MBIDs are semicolon-separated
			// but artists are comma-separated.
			// You can even get artists = ['artist1, artist2', 'artist3']. Sigh. Hence the first implode.
			$astring = implode(', ',$artists);
			$newartists = explode(',', $astring);
			if (count($newartists) == count($mbids)) {
				logger::debug("Trying splitting comma-separated artist string", "GETPLAYLIST");
				// In case AlbumArtist has that format too
				$this->artist_not_found_yet($astring);
				$artists = $newartists;
			}
		}
		while (count($mbids) < count($artists)) {
			$mbids[] = "";
		}
		$a = array();
		foreach ($artists as $i => $comp) {
			if ($comp != "") {
				if ($this->artist_not_found_yet($comp)) {
					array_push($info['metadata']['artists'], array( "name" => trim($comp), "musicbrainz_id" => trim($mbids[$i]), "type" => "artist", "ignore" => "false", 'backendid' => $info['Id']));
					$a[] = $comp;
				}
			}
		}
		// This is to try and prevent repeated names - eg artists = [Pete, Dud] and albumartist = Pete & Dud or Dud & Pete
		$this->artist_not_found_yet(concatenate_artist_names($a));
		$this->artist_not_found_yet(concatenate_artist_names(array_reverse($a)));
	}

	private function artist_not_found_yet($a) {
		$s = strtolower($a);
		if (in_array($s, $this->foundartists)) {
			return false;
		} else {
			$this->foundartists[] = $s;
			return true;
		}
	}
}

?>