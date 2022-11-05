<?php
class music_loader extends musicCollection {

	public function getItemsToAdd($which, $cmd = null) {
		$a = preg_match('/^(a|b|r|t|y|u|z)(.*?)(\d+|root)/', $which, $matches);
		if (!$a) {
			logger::error('BACKEND', "Regexp failed to match",$which);
			return array();
		}
		$what = $matches[2];
		logger::log('BACKEND','Getting tracks for',$which,$cmd);
		switch ($what) {
			case "album":
				return $this->get_album_tracks_from_database($which, $cmd);
				break;

			default:
				return $this->get_artist_tracks_from_database($which, $cmd);
				break;

		}
	}

	public function playAlbumFromTrack($uri) {
		// Used when CD player mode is on.
		$result = $this->sql_prepare_query(false, PDO::FETCH_OBJ, null, null, "SELECT Albumindex, TrackNo, Disc, isSearchResult, isAudiobook FROM Tracktable WHERE Uri = ?", $uri);
		$album = array_shift($result);
		$retval = array();
		if ($album) {
			if ($album->isSearchResult) {
				$why = 'b';
			} else if ($album->isAudiobook) {
				$why = 'z';
			} else {
				$why = 'a';
			}
			$alltracks = $this->get_album_tracks_from_database($why.'album'.$album->Albumindex, 'add');
			$count = 0;
			while (strpos($alltracks[$count], $uri) === false) {
				$count++;
			}
			$retval = array_slice($alltracks, $count);
		} else {
			// If we didn't find the track in the database, that'll be because it's
			// come from eg a spotifyAlbumThing or something like that (the JS doesn't discriminate)
			// so in this case just add the track
			$retval = array('add "'.$uri.'"');
		}
		return $retval;
	}

	private function get_album_tracks_from_database($which, $cmd) {
		$retarr = array();
		$sorter = choose_sorter_by_key($which);
		$lister = new $sorter($which);
		$result = $lister->track_sort_query();
		$cmd = ($cmd === null) ? 'add' : $cmd;
		foreach($result as $a) {
			$retarr[] = $cmd.' "'.$a['uri'].'"';
		}
		return $retarr;
	}

	private function get_artist_tracks_from_database($which, $cmd) {
		$retarr = array();
		logger::log('BACKEND', "Getting Tracks for Root Item",prefs::get_pref('sortcollectionby'),$which);
		$sorter = choose_sorter_by_key($which);
		$lister = new $sorter($which);
		foreach ($lister->albums_for_artist() as $a) {
			$retarr = array_merge($retarr, $this->get_album_tracks_from_database($a, $cmd));
		}
		return $retarr;
	}

}
?>