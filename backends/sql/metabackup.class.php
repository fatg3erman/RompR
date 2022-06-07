<?php

class metabackup extends metaDatabase {

	// To add something new to back up, add it to this array with a unique name and filename,
	// then add two private methods. i.e.
	// 'Your Name' => '/yourname.json'
	// get_YourName($file)
	//  - this method should return a stats string, eg '10 Tracks'
	// restore_YourName($file, &$monitor, $translate)
	//  - this method should fwrite lines into $monitor as it goes

	const BACKUP_MAP = [
		'Manually Added Tracks' => '/tracks.json',
		'Ratings' => '/ratings.json',
		'Tags' => '/tags.json',
		'Playcounts' => '/playcounts.json',
		'Spoken Word' => '/audiobooks.json',
		'Bookmarks' => '/bookmarks.json',
		'Podcasts' => '/podcasts.json',
		'Radio' => '/radio_stations.json',
		'Images' => '/bg_images.json',
		'Wishlist Sources' => '/wl_sources.json',
		'Albums To Listen To' => '/albums_to_listen_to.json',
		'Alarms' => '/alarms.json'
	];

	private function check_backup_dir() {
		$dirname = date('Y-m-d-H-i');
		if (is_dir('prefs/databackups/'.$dirname)) {
			rrmdir('prefs/databackups/'.$dirname);
		}
		mkdir('prefs/databackups/'.$dirname, 0755);
		return 'prefs/databackups/'.$dirname;
	}

	public function backup_unrecoverable_data() {

		$stats = [];
		$dirname = $this->check_backup_dir();

		foreach (self::BACKUP_MAP as $title => $file) {
			logger::log("BACKEND", "Backing up",$title);
			$fn = 'get_'.str_replace(' ', '', $title);
			$stats[$title] = $this->$fn($dirname.$file);
		}

		file_put_contents($dirname.'/newversion', ROMPR_SCHEMA_VERSION);
		file_put_contents($dirname.'/stats.json', json_encode($stats));

	}

	public function analyse_backups() {
		$data = array();
		$bs = glob('prefs/databackups/*');
		rsort($bs);
		foreach ($bs as $backup) {
			$data[] = [
				'dir' => basename($backup),
				'name' => strftime('%c', DateTime::createFromFormat('Y-m-d-H-i', basename($backup))->getTimestamp()),
				'stats' => $this->check_backup($backup)
			];
		}
		return $data;
	}

	private function check_backup($backup) {
		$stats = [];
		if (file_exists($backup.'/stats.json'))
			$stats = json_decode(file_get_contents($backup.'/stats.json'), true);

		$data = [];
		foreach (self::BACKUP_MAP as $title => $file) {
			if (array_key_exists($title, $stats)) {
				$data[$title] = $stats[$title];
			} else {
				$data[$title] = file_exists($backup.$file) ? 'OK' : 'Missing!';
			}
		}
		return $data;
	}

	public function removeBackup($which) {
		rrmdir('prefs/databackups/'.$which);
	}

	public function restoreBackup($backup) {
		if (file_exists('prefs/backupmonitor')) {
			unlink('prefs/backupmonitor');
		}
		$this->open_transaction();
		$monitor = fopen('prefs/backupmonitor', 'w');
		$translate = !file_exists('prefs/databackups/'.$backup.'/newversion');

		foreach (self::BACKUP_MAP as $title => $file) {
			$filename = 'prefs/databackups/'.$backup.$file;
			if (file_exists($filename)) {
				logger::mark("BACKUPS", "Restoring",$title);
				$fn = 'restore_'.str_replace(' ', '', $title);
				$this->$fn($filename, $monitor, $translate);
			}
		}

		fwrite($monitor, "\n<b>Cleaning Up...</b>\n");
		// Now... we may have restored data on tracks that were previously local and now aren't there any more.
		// If they're local tracks that have been removed, then we don't want them or care about their data
		if (prefs::get_pref('player_backend') == "mpd") {
			$this->generic_sql_query("DELETE FROM Tracktable WHERE Uri IS NOT NULL AND LastModified IS NULL AND Hidden = 0", true);
		} else {
			$this->generic_sql_query("DELETE FROM Tracktable WHERE Uri LIKE 'local:%' AND LastModified IS NULL AND Hidden = 0", true);
		}

		$this->generic_sql_query("UPDATE Albumtable SET domain = 'spotify' WHERE AlbumUri LIKE 'spotify:%'");
		$this->check_transaction();
		$this->resetallsyncdata();
		$this->remove_cruft();
		$this->update_track_stats();
		$this->close_transaction();
		fwrite($monitor, "\n \n");
		fclose($monitor);
	}

	private function generic_restore(&$trackdata, $table) {
		$fart = array_keys($trackdata);
		$qstring = "INSERT INTO ".$table." (".
			implode(', ', $fart).
			") VALUES (".
			implode(', ', array_fill(0, count($fart), '?')).
			")";
		$this->sql_prepare_query(true, null, null, null,
			$qstring,
			array_values($trackdata)
		);
		$this->check_transaction();
	}

	private function translate_data($trackdata) {
		// Update older backups
		$retval = [
			'Title' => $trackdata['title'],
			'Track' => $trackdata['trackno'],
			'Time' => $trackdata['duration'],
			'Disc' => $trackdata['disc'],
			'file' => $trackdata['uri'],
			'Album' => $trackdata['album'],
			'X-AlbumUri' => $trackdata['albumuri'],
			'Date' => $trackdata['date'],
			'trackartist' => $trackdata['artist'],
			'albumartist' => $trackdata['albumartist']
		];
		foreach (['isaudiobook', 'playcount', 'rating', 'tag', 'bookmark', 'bookmarkname'] as $thing)
		if (array_key_exists($thing, $trackdata)) {
			$retval[$thing] = $trackdata[$thing];
		}
		return $retval;
	}

	//
	// Backup Functions
	//

	private function get_ManuallyAddedTracks($file) {

		// get_manually_added_tracks
		//		Creates data for backup

		$tracks = $this->generic_sql_query(
			"SELECT
				Tracktable.Title AS Title,
				Tracktable.TrackNo AS Track,
				Tracktable.Duration AS Time,
				Tracktable.Disc AS Disc,
				Tracktable.Uri AS file,
				IFNULL(Genretable.Genre, 'None') AS Genre,
				Albumtable.Albumname AS Album,
				Albumtable.AlbumUri AS `X-AlbumUri`,
				Albumtable.Year AS year,
				ta.Artistname AS trackartist,
				aat.Artistname AS albumartist
			FROM
				Tracktable
				LEFT JOIN Genretable USING (Genreindex)
				JOIN Artisttable AS ta USING (Artistindex)
				JOIN Albumtable ON Tracktable.Albumindex = Albumtable.Albumindex
				JOIN Artisttable AS aat ON Albumtable.AlbumArtistindex = aat.Artistindex
			WHERE Tracktable.LastModified IS NULL AND
				((Tracktable.Hidden = 0 AND Tracktable.isSearchResult < 2) OR (Tracktable.Hidden = 1 AND Tracktable.LinkChecked = 4))
				AND Uri IS NOT NULL");

		file_put_contents($file, json_encode($tracks));

		return count($tracks).' Tracks';
	}

	private function get_Ratings($file) {

		// get_ratings
		//		Creates data for backup

		global $stats;

		$tracks = $this->generic_sql_query(
			"SELECT
				r.Rating AS rating,
				tr.Title AS Title,
				tr.TrackNo AS Track,
				tr.Duration AS Time,
				tr.Disc AS Disc,
				tr.Uri AS file,
				IFNULL(ge.Genre, 'None') AS Genre,
				al.Albumname AS Album,
				al.AlbumUri AS `X-AlbumUri`,
				al.Year AS year,
				ta.Artistname AS trackartist,
				aat.Artistname AS albumartist
			FROM
				Ratingtable AS r
				JOIN Tracktable AS tr USING (TTindex)
				LEFT JOIN Genretable AS ge USING (Genreindex)
				JOIN Artisttable AS ta USING (Artistindex)
				JOIN Albumtable AS al ON tr.Albumindex = al.Albumindex
				JOIN Artisttable AS aat ON al.AlbumArtistindex = aat.Artistindex
			WHERE rating > 0 AND tr.Hidden = 0 AND tr.isSearchResult < 2
			ORDER BY rating, albumartist, album, trackno");

		file_put_contents($file, json_encode($tracks));

		return count($tracks).' Tracks';
	}

	private function get_Tags($file) {

		// get_tags
		//		Creates data for backup

		$tracks = $this->generic_sql_query(
			"SELECT
				".database::SQL_TAG_CONCAT."AS tag,
				tr.Title AS Title,
				tr.TrackNo AS Track,
				tr.Duration AS Time,
				tr.Disc AS Disc,
				tr.Uri AS file,
				IFNULL(ge.Genre, 'None') AS Genre,
				al.Albumname AS Album,
				al.AlbumUri AS `X-AlbumUri`,
				al.Year AS year,
				ta.Artistname AS trackartist,
				aat.Artistname AS albumartist
			FROM
				Tagtable AS t
				JOIN TagListtable AS tl USING (Tagindex)
				JOIN Tracktable AS tr ON tl.TTindex = tr.TTindex
				LEFT JOIN Genretable AS ge USING (Genreindex)
				JOIN Artisttable AS ta USING (Artistindex)
				JOIN Albumtable AS al ON tr.Albumindex = al.Albumindex
				JOIN Artisttable AS aat ON al.AlbumArtistindex = aat.Artistindex
			WHERE tr.Hidden = 0 AND tr.isSearchResult < 2
			GROUP BY tr.TTindex");

		file_put_contents($file, json_encode($tracks));

		return count($tracks).' Tracks';
	}

	private function get_Playcounts($file) {

		// get_playcounts
		//		Creates data for backup

		$tracks = $this->generic_sql_query(
			"SELECT
				p.Playcount AS playcount,
				p.LastPlayed AS lastplayed,
				tr.Title AS Title,
				tr.TrackNo AS Track,
				tr.Duration AS Time,
				tr.Disc AS Disc,
				tr.Uri AS file,
				IFNULL(ge.Genre, 'None') AS Genre,
				al.Albumname AS Album,
				al.AlbumUri AS `X-AlbumUri`,
				al.Year AS year,
				ta.Artistname AS trackartist,
				aat.Artistname AS albumartist
			FROM
				Playcounttable AS p
				JOIN Tracktable AS tr USING (TTindex)
				LEFT JOIN Genretable AS ge USING (Genreindex)
				JOIN Artisttable AS ta USING (Artistindex)
				JOIN Albumtable AS al ON tr.Albumindex = al.Albumindex
				JOIN Artisttable AS aat ON al.AlbumArtistindex = aat.Artistindex
			WHERE playcount > 0
			ORDER BY playcount, albumartist, album, trackno");

		file_put_contents($file, json_encode($tracks));

		return count($tracks).' Tracks';
	}

	private function get_SpokenWord($file) {

		// get_audiobooks
		//		Creates data for backup

		$tracks = $this->generic_sql_query(
			"SELECT
				Tracktable.Title AS Title,
				Tracktable.TrackNo AS Track,
				Tracktable.Duration AS Time,
				Tracktable.Disc AS Disc,
				Tracktable.Uri AS file,
				IFNULL(Genretable.Genre, 'None') AS Genre,
				Albumtable.Albumname AS Album,
				Albumtable.AlbumUri AS `X-AlbumUri`,
				Albumtable.Year AS year,
				ta.Artistname AS trackartist,
				aat.Artistname AS albumartist,
				Tracktable.isAudiobook AS isaudiobook
			FROM
				Tracktable
				LEFT JOIN Genretable USING (Genreindex)
				JOIN Artisttable AS ta USING (Artistindex)
				JOIN Albumtable ON Tracktable.Albumindex = Albumtable.Albumindex
				JOIN Artisttable AS aat ON Albumtable.AlbumArtistindex = aat.Artistindex
			WHERE ((Tracktable.Hidden = 0 AND Tracktable.isSearchResult < 2) OR (Tracktable.Hidden = 1 AND Tracktable.LinkChecked = 4))
			AND Uri IS NOT NULL AND Tracktable.isAudiobook > 0");

		file_put_contents($file, json_encode($tracks));

		return count($tracks).' Tracks';
	}

	private function get_Podcasts($file) {
		$tracks = $this->generic_sql_query("SELECT * FROM Podcasttable WHERE Subscribed = 1");
		file_put_contents($file, json_encode($tracks));

		$retval = count($tracks).' Podcasts / ';

		$file = dirname($file).'/podcast_tracks.json';
		$tracks = $this->generic_sql_query("SELECT * FROM PodcastTracktable WHERE PODindex IN (SELECT PODindex FROM Podcasttable WHERE Subscribed = 1)");
		file_put_contents($file, json_encode($tracks));
		$retval .= count($tracks).' Episodes';

		$file = dirname($file).'/podcast_bookmarks.json';
		$tracks = $this->generic_sql_query("SELECT * FROM PodBookmarktable");
		file_put_contents($file, json_encode($tracks));

		return $retval;

	}

	private function get_Bookmarks($file) {

		// get_bookmarks
		//		Creates data for backup

		$tracks = $this->generic_sql_query(
			"SELECT
				b.Bookmark AS bookmark,
				b.name AS bookmarkname,
				tr.Title AS Title,
				tr.TrackNo AS Track,
				tr.Duration AS Time,
				tr.Disc AS Disc,
				tr.Uri AS file,
				IFNULL(ge.Genre, 'None') AS Genre,
				al.Albumname AS Album,
				al.AlbumUri AS `X-AlbumUri`,
				al.Year AS year,
				ta.Artistname AS trackartist,
				aat.Artistname AS albumartist
			FROM
				Bookmarktable AS b
				JOIN Tracktable AS tr USING (TTindex)
				LEFT JOIN Genretable AS ge USING (Genreindex)
				JOIN Artisttable AS ta USING (Artistindex)
				JOIN Albumtable AS al ON tr.Albumindex = al.Albumindex
				JOIN Artisttable AS aat ON al.AlbumArtistindex = aat.Artistindex
			WHERE Bookmark > 0 AND tr.Hidden = 0 AND tr.isSearchResult < 2
			ORDER BY albumartist, album, trackno");

		file_put_contents($file, json_encode($tracks));

		return count($tracks).' Bookmarks';
	}

	private function get_Radio($file) {
		$tracks = $this->generic_sql_query("SELECT * FROM RadioStationtable WHERE IsFave = 1");
		file_put_contents($file, json_encode($tracks));
		$retval = count($tracks).' Stations';

		$file = dirname($file).'/radio_tracks.json';
		$tracks = $this->generic_sql_query("SELECT * FROM RadioTracktable WHERE Stationindex IN
			(SELECT Stationindex FROM RadioStationtable WHERE IsFave = 1)");
		file_put_contents($file, json_encode($tracks));
		return $retval;
	}

	private function get_Images($file) {
		$tracks = $this->generic_sql_query("SELECT * FROM BackgroundImageTable");
		file_put_contents($file, json_encode($tracks));
		return count($tracks).' Images';
	}

	private function get_WishlistSources($file) {
		$tracks = $this->generic_sql_query("SELECT * FROM WishlistSourcetable");
		file_put_contents($file, json_encode($tracks));
		return count($tracks).' Stations';
	}

	private function get_AlbumsToListenTo($file) {
		$tracks = $this->generic_sql_query("SELECT * FROM AlbumsToListenTotable");
		file_put_contents($file, json_encode($tracks));
		return count($tracks).' Albums';
	}

	private function get_Alarms($file) {
		$tracks = $this->generic_sql_query("SELECT * FROM Alarms");
		file_put_contents($file, json_encode($tracks));
		return count($tracks).' Alarms';
	}

	//
	// Restore Functions
	//

	private function restore_ManuallyAddedTracks($file, &$monitor, $translate) {
		$tracks = json_decode(file_get_contents($file), true);
		foreach ($tracks as $i => $trackdata) {
			if ($translate)
				$trackdata = $this->translate_data($trackdata);
			$this->sanitise_data($trackdata);
			$trackdata['urionly'] = true;
			$this->set($trackdata);
			$this->check_transaction();
			$progress = round(($i/count($tracks))*100);
			fwrite($monitor, "\n<b>Restoring Manually Added Tracks : </b>".$progress."%\n");
		}
	}

	private function restore_Ratings($file, &$monitor, $translate) {
		$tracks = json_decode(file_get_contents($file), true);
		foreach ($tracks as $i => $trackdata) {
			if ($translate)
				$trackdata = $this->translate_data($trackdata);
			$this->sanitise_data($trackdata);
			$trackdata['attributes'] = array(array('attribute' => 'Rating', 'value' => $trackdata['rating']));
			$this->set($trackdata);
			$this->check_transaction();
			$progress = round(($i/count($tracks))*100);
			fwrite($monitor, "\n<b>Restoring Ratings : </b>".$progress."%\n");
		}
	}

	private function restore_Tags($file, &$monitor, $translate) {
		$tracks = json_decode(file_get_contents($file), true);
		foreach ($tracks as $i => $trackdata) {
			if ($translate)
				$trackdata = $this->translate_data($trackdata);
			$this->sanitise_data($trackdata);
			$trackdata['attributes'] = array(array('attribute' => 'Tags', 'value' => explode(',',$trackdata['tag'])));
			$this->set($trackdata);
			$this->check_transaction();
			$progress = round(($i/count($tracks))*100);
			fwrite($monitor, "\n<b>Restoring Tags : </b>".$progress."%\n");
		}
	}

	private function restore_Playcounts($file, &$monitor, $translate) {
		$tracks = json_decode(file_get_contents($file), true);
		foreach ($tracks as $i => $trackdata) {
			if ($translate)
				$trackdata = $this->translate_data($trackdata);
			$this->sanitise_data($trackdata);
			$trackdata['attributes'] = array(array('attribute' => 'Playcount', 'value' => $trackdata['playcount']));
			$this->inc($trackdata);
			$this->check_transaction();
			$progress = round(($i/count($tracks))*100);
			fwrite($monitor, "\n<b>Restoring Playcounts : </b>".$progress."%\n");
		}
	}

	private function restore_SpokenWord($file, &$monitor, $translate) {
		$tracks = json_decode(file_get_contents($file), true);
		foreach ($tracks as $i => $trackdata) {
			if ($translate)
				$trackdata = $this->translate_data($trackdata);
			$this->sanitise_data($trackdata);
			$this->updateAudiobookState($trackdata);
			$this->check_transaction();
			$progress = round(($i/count($tracks))*100);
			fwrite($monitor, "\n<b>Restoring Spoken Word Tracks : </b>".$progress."%\n");
		}
	}

	private function restore_Bookmarks($file, &$monitor, $translate) {
		$this->generic_sql_query('DELETE FROM Bookmarktable WHERE Bookmark >= 0');
		$tracks = json_decode(file_get_contents($file), true);
		foreach ($tracks as $i => $trackdata) {
			if ($translate)
				$trackdata = $this->translate_data($trackdata);
			$this->sanitise_data($trackdata);
			$trackdata['attributes'] = array(array('attribute' => 'Bookmark', 'value' => [$trackdata['bookmark'], $trackdata['bookmarkname']]));
			$this->set($trackdata);
			$this->check_transaction();
			$progress = round(($i/count($tracks))*100);
			fwrite($monitor, "\n<b>Restoring Bookmarks : </b>".$progress."%\n");
		}
	}

	private function restore_Podcasts($file, &$monitor, $translate) {
		$this->generic_sql_query("DELETE FROM PodcastTracktable WHERE PODTrackindex IS NOT NULL");
		$this->generic_sql_query("DELETE FROM Podcasttable WHERE PODindex IS NOT NULL");
		$tracks = json_decode(file_get_contents($file), true);
		foreach ($tracks as $i => $trackdata) {
			if (array_key_exists('LastUpdate', $trackdata))
				unset($trackdata['LastUpdate']);
			$this->generic_restore($trackdata, 'Podcasttable');
			$progress = round(($i/count($tracks))*100);
			fwrite($monitor, "\n<b>Restoring Podcasts : </b>".$progress."%\n");
		}

		$file = dirname($file).'/podcast_tracks.json';
		$tracks = json_decode(file_get_contents($file), true);
		foreach ($tracks as $i => $trackdata) {
			if ($trackdata['Downloaded'] == 1 && !file_exists('.'.$trackdata['Localfilename'])) {
				logger::log('BACKUPS', 'Podcast track',$trackdata['Title'],'has been removed');
				$trackdata['Downloaded'] = 0;
				$trackdata['Localfilename'] = null;
			}

			if (array_key_exists('Progress', $trackdata))
				unset($trackdata['Progress']);

			if (!array_key_exists('Image', $trackdata))
				$trackdata['Image'] = null;

			$this->generic_restore($trackdata, 'PodcastTracktable');
			$progress = round(($i/count($tracks))*100);
			fwrite($monitor, "\n<b>Restoring Podcast Tracks : </b>".$progress."%\n");
		}

		$file = dirname($file).'/podcast_bookmarks.json';
		if (file_exists($file)) {
			$tracks = json_decode(file_get_contents($file), true);
			foreach ($tracks as $i => $trackdata) {
				$this->generic_restore($trackdata, 'PodBookmarktable');
				$progress = round(($i/count($tracks))*100);
				fwrite($monitor, "\n<b>Restoring Podcast Bookmarks : </b>".$progress."%\n");
			}
		}
	}

	private function restore_Radio($file, &$monitor, $translate) {
		$this->generic_sql_query("DELETE FROM RadioStationtable WHERE Stationindex IS NOT NULL");
		$this->generic_sql_query("DELETE FROM RadioTracktable WHERE Trackindex IS NOT NULL");
		$tracks = json_decode(file_get_contents($file), true);
		foreach ($tracks as $i => $trackdata) {
			$this->generic_restore($trackdata, 'RadioStationtable');
			$progress = round(($i/count($tracks))*100);
			fwrite($monitor, "\n<b>Restoring Radio Stations : </b>".$progress."%\n");
		}

		$file = dirname($file).'/radio_tracks.json';
		$tracks = json_decode(file_get_contents($file), true);
		foreach ($tracks as $i => $trackdata) {
			$this->generic_restore($trackdata, 'RadioTracktable');
			$progress = round(($i/count($tracks))*100);
			fwrite($monitor, "\n<b>Restoring Radio Tracks : </b>".$progress."%\n");
		}
	}

	private function restore_Images($file, &$monitor, $translate) {
		$this->generic_sql_query("DELETE FROM BackgroundImageTable WHERE BgImageIndex IS NOT NULL");
		$tracks = json_decode(file_get_contents($file), true);
		foreach ($tracks as $i => $trackdata) {
			if (file_exists($trackdata['Filename'])) {
				$this->generic_restore($trackdata, 'BackgroundImageTable');
			} else {
				logger::log('BACKUPS', 'Background Image',$trackdata['Filename'],'has been removed');
			}
			$progress = round(($i/count($tracks))*100);
			fwrite($monitor, "\n<b>Restoring Background Images : </b>".$progress."%\n");
		}
	}

	private function restore_WishlistSources($file, &$monitor, $translate) {
		$this->generic_sql_query("DELETE FROM WishlistSourcetable WHERE Sourceindex IS NOT NULL");
		$tracks = json_decode(file_get_contents($file), true);
		foreach ($tracks as $i => $trackdata) {
			$this->generic_restore($trackdata, 'WishlistSourcetable');
			$progress = round(($i/count($tracks))*100);
			fwrite($monitor, "\n<b>Restoring Wishlist Sources : </b>".$progress."%\n");
		}
	}

	private function restore_AlbumsToListenTo($file, &$monitor, $translate) {
		$this->generic_sql_query("DELETE FROM AlbumsToListenTotable WHERE Listenindex IS NOT NULL");
		$tracks = json_decode(file_get_contents($file), true);
		foreach ($tracks as $i => $trackdata) {
			$this->generic_restore($trackdata, 'AlbumsToListenTotable');
			$progress = round(($i/count($tracks))*100);
			fwrite($monitor, "\n<b>Restoring Albums To Listen To : </b>".$progress."%\n");
		}
	}

	private function restore_Alarms($file, &$monitor, $translate) {
		$this->generic_sql_query("DELETE FROM Alarms WHERE Alarmindex IS NOT NULL");
		$tracks = json_decode(file_get_contents($file), true);
		foreach ($tracks as $i => $trackdata) {
			$this->generic_restore($trackdata, 'Alarms');
			$progress = round(($i/count($tracks))*100);
			fwrite($monitor, "\n<b>Restoring Alarms : </b>".$progress."%\n");
		}
	}

}

?>