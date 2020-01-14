<?php

function choose_sorter_by_key($which) {
	global $prefs;
	$a = preg_match('/(a|b|c|r|t|y|u|z)(.*?)(\d+|root)_*(\d+)*/', $which, $matches);
	switch ($matches[1]) {
		case 'b':
			return 'sortby_'.$prefs['sortresultsby'];
			break;

		default:
			return 'sortby_'.$prefs['sortcollectionby'];
			break;
	}
}

class sortby_base {

	protected $why;
	protected $what;
	protected $who;
	protected $when = null;

	// $which is a key passed in from the UI, and created by this class
	// It is made up of several parts
	// $why$what$who
	// $why is a single lowercase character:
	// 	a - tracks from Music Collection
	//	b - tracks from search results
	//	z - audiobooks
	// When this is used to get tracks to add to the queue, some extras are accepted
	//	r - only tracks with ratings
	//	t - only tracks with tags
	//	y - only tracks with tags and ratings
	//	u - only tracks with tags or ratings
	// $what is a lowercase string.
	//	to get tracklistings for an album $what *must*be 'album'
	//	any other string means 'the root item' (eg albumartists, tags)
	// $who is:
	//	root - to get the complete list of root items
	//	[index] an index into the database
	//
	//	eg aartist1234 - sets this up to get albums from the collecion for Albumartist 1234
	//		abollocks1234 would also work
	//	   aalbum2345 gets tracks for Albumindex 2345, in the context of this sorter
	//  $who can contain two indices eg 2345_1234
	//	eg aalbum2345_1234 would get tracks for album 2345 in the context of root item 1234
	//	This latter form is useful when dealing with sort modes where the same album may appear
	//	under multiple root headings - eg when sorting by tag
	//	In this case, '1234' is referred to as $when


	public function __construct($which) {
		global $prefs, $divtype;
		$divtype = 'album1';
		$a = preg_match('/(a|b|c|r|t|y|u|z)(.*?)(\d+|root)_*(\d+)*/', $which, $matches);
		if (!$a) {
			logger::warn("SORTBY", "Sort Init failed - regexp failed to match",$which);
			return false;
		}
		$this->why = $matches[1];
		$this->what = $matches[2];
		$this->who = $matches[3];
		if (array_key_exists(4, $matches)) {
			$this->when = $matches[4];
		}
		logger::debug('SORTER', 'Initialised',$this->why,$this->what,$this->who);
		if ($this->when) {
			logger::debug('SORTER', '  Root item key is',$this->when);
		}
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
		switch($this->why) {
			case 'a':
				$where = 'fothergill';
				break;
			case 'b':
				$where = 'searchstats';
				break;
			case 'z':
				$where = 'mingus';
				break;
		}
		return array(
			'type' => 'insertAfter',
			'where' => $where
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
					case 'album':
						$this->output_track_list();
						break;
					default:
						$this->output_album_list();
						break;
				}
		}
	}

	protected function emptyCollectionDisplay() {
		global $prefs;
		switch ($this->why) {
			case 'a':
				print '<div id="emptycollection" class="textcentre fullwidth">
				<p>Your Music Collection Is Empty</p>
				<p>You can add files to it by tagging and rating them, or you can build a collection of all your music</p>';
				print '</div>';
				break;

			case 'b':
				print '<div class="textcentre fullwidth">';
				if ($prefs['sortresultsby'] == 'tag' || $prefs['sortresultsby'] == 'rating') {
					print '<p>You are sorting results by '.ucfirst(get_int_text(COLLECTION_SORT_MODES[$prefs['sortresultsby']])).' which may mean some results are not displayed</p>';
				}
				print '</div>';
				break;

			case 'z':
				print '<div class="textcentre fullwidth">
				<p>There are no Spoken Word tracks in your Collection that can be displayed when sorting by '.ucfirst(get_int_text(COLLECTION_SORT_MODES[$prefs['sortresultsby']])).'</p>
				</div>';
				break;
		}
	}

	public function track_sort_query() {
		// This is the generic query for sortby_artist, sortby_album, and sortby_albumbyartist
		global $prefs;
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
				WHERE
					tr.Albumindex = ".$this->who."
					AND uri IS NOT NULL
					AND tr.Hidden = 0
					".track_date_check($prefs['collectionrange'], $this->why)."
					".$this->filter_track_on_why()."
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
			yield $this->why.'album'.$album['Albumindex'];
		}
	}

	public function output_track_list($fragment = false) {
		logger::log('SORTBY', 'Doing Track List For Album',$this->who);
		$trackarr = $this->track_sort_query();
		if ($fragment) {
			ob_start();
		}
		$numtracks = count($trackarr);
		$numdiscs = get_highest_disc($trackarr);
		$currdisc = -1;
		trackControlHeader($this->why, $this->what, $this->who, $this->when, get_album_details($this->who));
		$total_time = 0;
		$tracktype = null;
		foreach ($trackarr as $arr) {
			logger::debug('SORTBY', 'Track', $arr['title']);
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
				logger::mark("GET TRACKS", "Album",$this->who," - adding album link to get all tracks");
				print '<input type="hidden" class="expandalbum"/>';
			}
		}
		// if ($tracktype == 1) {
		// 	logger::mark("GET TRACKS", "Album",$this->who,"has no tracks, just an artist link");
		// 	print '<input type="hidden" class="expandartist"/>';
		// }
		if ($total_time > 0) {
			print '<input type="hidden" class="albumtime" value="'.format_time($total_time).'" />';
		}
		if ($fragment) {
			$s = ob_get_contents();
			ob_end_clean();
			return $s;
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
			AND Uri IS NOT NULL ".
			$this->filter_track_on_why();
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
			AND Uri IS NOT NULL ".
			$this->filter_track_on_why();
		return generic_sql_query($qstring, false, null, 'num', 0);
	}

}

?>