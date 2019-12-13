<?php

function do_fiddle($a) {
	return 'tr.Albumindex = '.$a;
}

class sortby_base {

	protected $why;
	protected $what;
	protected $who;

	public function __construct($which) {
		global $prefs, $divtype;
		$divtype = 'album1';
		$a = preg_match('/(a|b|r|t|y|u|z)(.*?)(\d+|root)/', $which, $matches);
		if (!$a) {
			logger::fail("SORTBY", "Sort Init failed - regexp failed to match",$which);
			return false;
		}
		$this->why = $matches[1];
		$this->what = $matches[2];
		$this->who = $matches[3];
		logger::log('SORTER', 'Initialised',$this->why,$this->what,$this->who);
	}

	public function set_who($who) {
		$this->who = $who;
	}

	public function filter_root_on_why($table = '') {
		switch ($this->why) {
			case 'a':
				$sflag = "AND ".$table."isSearchResult < 2 AND ".$table."isAudiobook = 0";
				break;

			case 'b':
				$sflag = "AND ".$table."isSearchResult > 0";
				break;

			case 'z':
				$sflag = "AND ".$table."isSearchResult < 2 AND ".$table."isAudiobook > 0";
				break;

			case 'c':
				// Special case for album art manager
				$sflag = "AND ".$table."isSearchResult < 2";
				break;

			default:
				$sflag = '';
				break;
		}
		return $sflag;
	}

	public function filter_album_on_why() {
		return $this->filter_root_on_why('Tracktable.');
	}

	public function filter_track_on_why() {
		switch ($this->why) {
			case 'a':
				// Tracks from Collection //
				return "AND isSearchResult < 2 AND isAudiobook = 0";
				break;

			case 'b':
				// Tracks from Search Results //
				return "AND isSearchResult > 0";
				break;

			case 'r':
				// Only Tracks with Ratings //
				return "AND isSearchResult < 2 AND (LinkChecked = 0 OR LinkChecked = 2) AND r.TTindex IS NOT NULL";
				break;

			case 't':
				// Only Tracks with Tags //
				return "AND isSearchResult < 2 AND (LinkChecked = 0 OR LinkChecked = 2) AND tl.TTindex IS NOT NULL";
				break;

			case 'y':
				// Only Tracks with Tags and Ratings //
				return "AND isSearchResult < 2 AND (LinkChecked = 0 OR LinkChecked = 2) AND tl.TTindex IS NOT NULL AND r.TTindex IS NOT NULL";
				break;

			case 'u':
				// Only Tracks with Tags or Ratings //
				return "AND isSearchResult < 2 AND (LinkChecked = 0 OR LinkChecked = 2) AND (tl.TTindex IS NOT NULL OR r.TTindex IS NOT NULL)";
				break;

			case 'z':
				// Audiobooks //
				return "AND isSearchResult < 2 AND isAudiobook > 0";
				break;

			default:
				logger::error('SORTBY', 'Unknown WHY :',$this->why);
				break;
		}
	}

	public function initial_root_insert() {
		return array(
			'type' => 'insertAfter',
			'where' => ($this->why == 'a') ? 'fothergill' : 'mingus'
		);
	}

	public function initial_album_insert() {
		return array (
			'type' => 'insertAtStart',
			'where' => $this->why.'artist'.$this->who,
			'why' => $this->why
		);
	}

	public function output_html() {
		switch ($this->who) {
			case 'root':
				print '<div class="sizer"></div>';
				switch ($this->why) {
					case 'a':
						print collectionStats();
						break;
					case 'b':
						print searchStats();
						break;
					case 'z':
						print audiobookStats();
						break;
				}
				$count = $this->output_root_list();
				if ($count == 0) {
					$this->emptyCollectionDisplay();
				}
				break;

			default:
				switch ($this->what) {
					case 'artist':
						$this->output_album_list();
						break;
					case 'album':
						$this->output_track_list();
						break;
				}
		}
	}

	protected function emptyCollectionDisplay() {
		switch ($this->why) {
			case 'a':
				print '<div id="emptycollection" class="textcentre fullwidth">
				<p>Your Music Collection Is Empty</p>
				<p>You can add files to it by tagging and rating them, or you can build a collection of all your music</p>';
				print '</div>';
				break;

			case 'b':
				print '<div class="textcentre fullwidth">
				<p>No Results</p>
				</div>';
				break;

			case 'z':
				print '<div class="textcentre fullwidth">
				<p>No Audiobooks</p>
				</div>';
				break;
		}
	}

	public function track_sort_query() {
		// This is the generic query for sortby_artist, sortby_album, and sortby_albumbyartist
		global $prefs;
		$t = $this->filter_track_on_why();
		// This looks like a wierd way of doing it but the obvious way doesn't work with mysql
		// due to table aliases being used.
		$qstring = "SELECT
				".SQL_TAG_CONCAT." AS tags,
				r.Rating AS rating,
				pr.Progress AS progress,
				tr.TTindex AS ttid,
				tr.Title AS title,
				tr.TrackNo AS trackno,
				tr.Duration AS time,
				tr.LastModified AS lm,
				tr.Disc AS disc,
				tr.Uri AS uri,
				tr.LinkChecked AS playable,
				ta.Artistname AS artist,
				tr.Artistindex AS trackartistindex,
				al.AlbumArtistindex AS albumartistindex
			FROM
				(Tracktable AS tr, Artisttable AS ta, Albumtable AS al)
				LEFT JOIN TagListtable AS tl ON tr.TTindex = tl.TTindex
				LEFT JOIN Tagtable AS t USING (Tagindex)
				LEFT JOIN Ratingtable AS r ON tr.TTindex = r.TTindex
				LEFT JOIN Progresstable AS pr ON tr.TTindex = pr.TTindex
				WHERE (".implode(' OR ', array_map('do_fiddle', $this->who)).")
					AND uri IS NOT NULL
					AND tr.Hidden = 0
					".track_date_check($prefs['collectionrange'], $this->why)."
					".$t."
					AND tr.Artistindex = ta.Artistindex
					AND al.Albumindex = tr.Albumindex
			GROUP BY tr.TTindex
			ORDER BY CASE WHEN title LIKE 'Album: %' THEN 1 ELSE 2 END, disc, trackno";
		return generic_sql_query($qstring);
	}

	public function albums_for_artist() {
		// Usd when adding all tracks for artist to eg the Play Queue
		// Does not filter on r,t,y, or u
		foreach ($this->album_sort_query(false) as $album) {
			yield $album['Albumindex'];
		}
	}

	public function output_track_list($fragment = false) {
		// This function can accept multiple album ids ($this->who can be an array set by set_who()
		// in which case it will combine them all into one 'virtual album' - see browse_album()
		$this->who = getArray($this->who);
		logger::log('SORTBY', 'Doing Track List For Album',implode(',', $this->who));
		$trackarr = $this->track_sort_query();
		if ($fragment) {
			ob_start();
		}
		$numtracks = count($trackarr);
		$numdiscs = get_highest_disc($trackarr);
		$currdisc = -1;
		trackControlHeader($this->why, $this->what, $this->who[0], get_album_details($this->who[0]));
		$total_time = 0;
		$tracktype = null;
		foreach ($trackarr as $arr) {
			logger::log('SORTBY', 'Track', $arr['title']);
			$total_time += $arr['time'];
			if ($numdiscs > 1 && $arr['disc'] != $currdisc && $arr['disc'] > 0) {
				$currdisc = $arr['disc'];
				print '<div class="clickable clickdisc playable draggable discnumber indent">'.ucfirst(strtolower(get_int_text("musicbrainz_disc"))).' '.$currdisc.'</div>';
			}
			if ($currdisc > 0) {
				$arr['discclass'] = ' disc'.$currdisc;
			} else {
				$arr['discclass'] = '';
			}
			$arr['numtracks'] = $numtracks;
			$tracktype = albumTrack($arr);
			if ($tracktype == 2 && $this->why == 'b') {
				// albumTrack will return 2 if this is an :album: link - we add an expandalbum
				// input so the UI will populate the whole album, since spotify oftne only returns
				// the odd track. Obviously only do this for search results
				logger::mark("GET TRACKS", "Album",$this->who[0]," - adding album link to get all tracks");
				print '<input type="hidden" class="expandalbum"/>';
			}
		}
		if ($tracktype == 1) {
			logger::mark("GET TRACKS", "Album",$this->who[0],"has no tracks, just an artist link");
			print '<input type="hidden" class="expandartist"/>';
		}
		if ($total_time > 0) {
			print '<input type="hidden" class="albumtime" value="'.format_time($total_time).'" />';
		}
		if ($fragment) {
			$s = ob_get_contents();
			ob_end_clean();
			return $s;
		}
	}

	protected function returninfo_root_key() {
		if ($this->why == 'a') {
			return 'artists';
		} else {
			return 'bookartists';
		}
	}

	protected function returninfo_album_key() {
		if ($this->why == 'a') {
			return 'albums';
		} else {
			return 'audiobooks';
		}
	}

	protected function artist_albumcount($artistindex) {
		$qstring =
		"SELECT
			COUNT(Albumindex) AS num
		FROM
			Albumtable LEFT JOIN Tracktable USING (Albumindex)
		WHERE
			AlbumArtistindex = ".$artistindex.
			" AND Hidden = 0
			AND isSearchResult < 2
			AND Uri IS NOT NULL
			AND isAudiobook ";
		$qstring .= ($this->why == 'a') ? '= 0' : '> 0';
		return generic_sql_query($qstring, false, null, 'num', 0);
	}

	public function album_trackcount($albumindex) {
		$qstring =
		"SELECT
			COUNT(TTindex) AS num
		FROM
			Tracktable
		WHERE
			Albumindex = ".$albumindex.
			" AND Hidden = 0
			AND isSearchResult < 2
			AND Uri IS NOT NULL
			AND isAudiobook ";
		$qstring .= ($this->why == 'a') ? '= 0' : '> 0';
		return generic_sql_query($qstring, false, null, 'num', 0);
	}

}

?>