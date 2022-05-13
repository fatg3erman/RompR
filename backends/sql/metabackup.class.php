<?php

class metabackup extends metaDatabase {

	private function check_backup_dir() {
		$dirname = date('Y-m-d-H-i');
		if (is_dir('prefs/databackups/'.$dirname)) {
			rrmdir('prefs/databackups/'.$dirname);
		}
		mkdir('prefs/databackups/'.$dirname, 0755);
		return 'prefs/databackups/'.$dirname;
	}

	public function backup_unrecoverable_data() {

		// This makes a backup of all manually added tracks and all
		// rating, tag, and playcount data. This can be used to restore it
		// or transfer it to another machine

		$dirname = $this->check_backup_dir();

		logger::log("BACKEND", "Backing up manually added tracks");
		$tracks = $this->get_manually_added_tracks();
		file_put_contents($dirname.'/tracks.json',json_encode($tracks));

		logger::log("BACKEND", "Backing up ratings");
		$tracks = $this->get_ratings();
		file_put_contents($dirname.'/ratings.json',json_encode($tracks));

		logger::log("BACKEND", "Backing up Playcounts");
		$tracks = $this->get_playcounts();
		file_put_contents($dirname.'/playcounts.json',json_encode($tracks));

		logger::log("BACKEND", "Backing up Tags");
		$tracks = $this->get_tags();
		file_put_contents($dirname.'/tags.json',json_encode($tracks));

		logger::log("BACKEND", "Backing up Audiobook Status");
		$tracks = $this->get_audiobooks();
		file_put_contents($dirname.'/audiobooks.json',json_encode($tracks));

		logger::log("BACKEND", "Backing up Podcasts");
		$tracks = $this->get_podcasts();
		file_put_contents($dirname.'/podcasts.json',json_encode($tracks));
		$tracks = $this->get_podcast_tracks();
		file_put_contents($dirname.'/podcast_tracks.json',json_encode($tracks));
		$tracks = $this->get_podcast_bookmarks();
		file_put_contents($dirname.'/podcast_bookmarks.json',json_encode($tracks));

		logger::log("BACKEND", "Backing up Radio Stations");
		$tracks = $this->get_radio_stations();
		file_put_contents($dirname.'/radio_stations.json',json_encode($tracks));
		$tracks = $this->get_radio_tracks();
		file_put_contents($dirname.'/radio_tracks.json',json_encode($tracks));

		logger::log("BACKEND", "Backing up Background Images");
		$tracks = $this->get_bg_images();
		file_put_contents($dirname.'/bg_images.json',json_encode($tracks));

		logger::log("BACKEND", "Backing up Wishlist Sources");
		$tracks = $this->get_wishlist_sources();
		file_put_contents($dirname.'/wl_sources.json',json_encode($tracks));

		logger::log("BACKEND", "Backing up Albums To Listen To");
		$tracks = $this->get_albumstolistento();
		file_put_contents($dirname.'/albums_to_listen_to.json',json_encode($tracks));

		logger::log("BACKEND", "Backing up Bookmarks");
		$tracks = $this->get_bookmarks();
		file_put_contents($dirname.'/bookmarks.json',json_encode($tracks));

		file_put_contents($dirname.'/newversion', ROMPR_SCHEMA_VERSION);

	}

	public function analyse_backups() {
		$data = array();
		$bs = glob('prefs/databackups/*');
		rsort($bs);
		foreach ($bs as $backup) {
			// This is nice data to have, but it takes a very long time on a moderate computer
			// FIXME: We should create these numbers when we create the backup and save them so we can read them in
			// $tracks = count(json_decode(file_get_contents($backup.'/tracks.json')));
			// $ratings = count(json_decode(file_get_contents($backup.'/ratings.json')));
			// $playcounts = count(json_decode(file_get_contents($backup.'/playcounts.json')));
			// $tags = count(json_decode(file_get_contents($backup.'/tags.json')));

			$data[] = array(
				'dir' => basename($backup),
				'name' => strftime('%c', DateTime::createFromFormat('Y-m-d-H-i', basename($backup))->getTimestamp()),
				'stats' => array(
					'Manually Added Tracks' => file_exists($backup.'/tracks.json') ? 'OK' : 'Missing!',
					'Playcounts' => file_exists($backup.'/playcounts.json') ? 'OK' : 'Missing!',
					'Tracks With Ratings' => file_exists($backup.'/ratings.json') ? 'OK' : 'Missing!',
					'Tracks With Tags' => file_exists($backup.'/tags.json') ? 'OK' : 'Missing!',
					'Bookmarks' => file_exists($backup.'/bookmarks.json') ? 'OK' : 'Missing!',
					'Spoken Word' => file_exists($backup.'/audiobooks.json') ? 'OK' : 'Missing!',
					'Podcasts' => file_exists($backup.'/podcasts.json') ? 'OK' : 'Missing!',
					'Background Images' => file_exists($backup.'/bg_images.json') ? 'OK' : 'Missing!',
					'Radio Stations' => file_exists($backup.'/radio_stations.json') ? 'OK' : 'Missing!',
					'Albums To Listen To' => file_exists($backup.'/albums_to_listen_to.json') ? 'OK' : 'Missing!'
				)
			);
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
		if (file_exists('prefs/databackups/'.$backup.'/tracks.json')) {
			logger::mark("BACKUPS", "Restoring Manually Added Tracks");
			$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/tracks.json'), true);
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
		if (file_exists('prefs/databackups/'.$backup.'/ratings.json')) {
			logger::mark("BACKUPS", "Restoring Ratings");
			$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/ratings.json'), true);
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
		if (file_exists('prefs/databackups/'.$backup.'/tags.json')) {
			logger::mark("BACKUPS", "Restoring Tags");
			$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/tags.json'), true);
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
		if (file_exists('prefs/databackups/'.$backup.'/playcounts.json')) {
			logger::mark("BACKUPS", "Restoring Playcounts");
			$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/playcounts.json'), true);
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

		if (file_exists('prefs/databackups/'.$backup.'/bookmarks.json')) {
			logger::mark("BACKUPS", "Restoring Bookmarks");
			$this->generic_sql_query('DELETE FROM Bookmarktable WHERE Bookmark >= 0');
			$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/bookmarks.json'), true);
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

		if (file_exists('prefs/databackups/'.$backup.'/audiobooks.json')) {
			logger::mark("BACKUPS", "Restoring Audiobooks");
			$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/audiobooks.json'), true);
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

		if (file_exists('prefs/databackups/'.$backup.'/podcasts.json')) {
			logger::mark("BACKUPS", "Restoring Podcasts");
			$this->generic_sql_query("DELETE FROM PodcastTracktable WHERE PODTrackindex IS NOT NULL");
			$this->generic_sql_query("DELETE FROM Podcasttable WHERE PODindex IS NOT NULL");
			$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/podcasts.json'), true);
			foreach ($tracks as $i => $trackdata) {
				$this->generic_restore($trackdata, 'Podcasttable');
				$progress = round(($i/count($tracks))*100);
				fwrite($monitor, "\n<b>Restoring Podcasts : </b>".$progress."%\n");
			}
			$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/podcast_tracks.json'), true);
			foreach ($tracks as $i => $trackdata) {
				if ($trackdata['Downloaded'] == 1 && !file_exists('.'.$trackdata['Localfilename'])) {
					logger::log('BACKUPS', 'Podcast track',$trackdata['Title'],'has been removed');
					$trackdata['Downloaded'] = 0;
					$trackdata['Localfilename'] = null;
				}

				if (array_key_exists('Progress', $trackdata))
					unset($trackdata['Progress']);

				$this->generic_restore($trackdata, 'PodcastTracktable');
				$progress = round(($i/count($tracks))*100);
				fwrite($monitor, "\n<b>Restoring Podcast Tracks : </b>".$progress."%\n");
			}
		}

		if (file_exists('prefs/databackups/'.$backup.'/podcast_bookmarks.json')) {
			$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/podcast_bookmarks.json'), true);
			foreach ($tracks as $i => $trackdata) {
				$this->generic_restore($trackdata, 'PodBookmarktable');
				$progress = round(($i/count($tracks))*100);
				fwrite($monitor, "\n<b>Restoring Podcast Bookmarks : </b>".$progress."%\n");
			}
		}

		if (file_exists('prefs/databackups/'.$backup.'/radio_stations.json')) {
			logger::mark("BACKUPS", "Restoring Radio Stations");
			$this->generic_sql_query("DELETE FROM RadioStationtable WHERE Stationindex IS NOT NULL");
			$this->generic_sql_query("DELETE FROM RadioTracktable WHERE Trackindex IS NOT NULL");
			$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/radio_stations.json'), true);
			foreach ($tracks as $i => $trackdata) {
				$this->generic_restore($trackdata, 'RadioStationtable');
				$progress = round(($i/count($tracks))*100);
				fwrite($monitor, "\n<b>Restoring Radio Stations : </b>".$progress."%\n");
			}
			$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/radio_tracks.json'), true);
			foreach ($tracks as $i => $trackdata) {
				$this->generic_restore($trackdata, 'RadioTracktable');
				$progress = round(($i/count($tracks))*100);
				fwrite($monitor, "\n<b>Restoring Radio Tracks : </b>".$progress."%\n");
			}
		}

		if (file_exists('prefs/databackups/'.$backup.'/bg_images.json')) {
			logger::mark("BACKUPS", "Restoring Background Images");
			$this->generic_sql_query("DELETE FROM BackgroundImageTable WHERE BgImageIndex IS NOT NULL");
			$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/bg_images.json'), true);
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

		if (file_exists('prefs/databackups/'.$backup.'/wl_sources.json')) {
			logger::mark("BACKUPS", "Restoring W<?phpishlist Sources");
			$this->generic_sql_query("DELETE FROM WishlistSourcetable WHERE Sourceindex IS NOT NULL");
			$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/wl_sources.json'), true);
			foreach ($tracks as $i => $trackdata) {
				$this->generic_restore($trackdata, 'WishlistSourcetable');
				$progress = round(($i/count($tracks))*100);
				fwrite($monitor, "\n<b>Restoring Wishlist Sources : </b>".$progress."%\n");
			}
		}

		if (file_exists('prefs/databackups/'.$backup.'/albums_to_listen_to.json')) {
			logger::mark("BACKUPS", "Restoring Albums To Listen To");
			$this->generic_sql_query("DELETE FROM AlbumsToListenTotable WHERE Listenindex IS NOT NULL");
			$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/albums_to_listen_to.json'), true);
			foreach ($tracks as $i => $trackdata) {
				$this->generic_restore($trackdata, 'AlbumsToListenTotable');
				$progress = round(($i/count($tracks))*100);
				fwrite($monitor, "\n<b>Restoring Albums To Listen To : </b>".$progress."%\n");
			}
		}

		fwrite($monitor, "\n<b>Cleaning Up...</b>\n");
		// Now... we may have restored data on tracks that were previously local and now aren't there any more.
		// If they're local tracks that have been removed, then we don't want them or care about their data
		if (prefs::$prefs['player_backend'] == "mpd") {
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

	private function get_manually_added_tracks() {

		// get_manually_added_tracks
		//		Creates data for backup

		return $this->generic_sql_query(
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
			WHERE Tracktable.LastModified IS NULL AND Tracktable.Hidden = 0 AND Tracktable.isSearchResult < 2 AND uri IS NOT NULL");
	}

	private function get_audiobooks() {

		// get_audiobooks
		//		Creates data for backup

		return $this->generic_sql_query(
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
			WHERE Tracktable.Hidden = 0 AND Tracktable.isSearchResult < 2 AND uri IS NOT NULL AND Tracktable.isAudiobook > 0");
	}

	private function get_ratings() {

		// get_ratings
		//		Creates data for backup

		return $this->generic_sql_query(
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
	}

	private function get_bookmarks() {

		// get_bookmarks
		//		Creates data for backup

		return $this->generic_sql_query(
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
	}

	private function get_playcounts() {

		// get_playcounts
		//		Creates data for backup

		return $this->generic_sql_query(
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
	}

	private function get_tags() {

		// get_tags
		//		Creates data for backup

		return $this->generic_sql_query(
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
	}

	private function get_podcasts() {
		return $this->generic_sql_query("SELECT * FROM Podcasttable");
	}

	private function get_podcast_tracks() {
		return $this->generic_sql_query("SELECT * FROM PodcastTracktable");
	}

	private function get_podcast_bookmarks() {
		return $this->generic_sql_query("SELECT * FROM PodBookmarktable");
	}

	private function get_radio_stations() {
		return $this->generic_sql_query("SELECT * FROM RadioStationtable");
	}

	private function get_radio_tracks() {
		return $this->generic_sql_query("SELECT * FROM RadioTracktable");
	}

	private function get_bg_images() {
		return $this->generic_sql_query("SELECT * FROM BackgroundImageTable");
	}

	private function get_wishlist_sources() {
		return $this->generic_sql_query("SELECT * FROM WishlistSourcetable");
	}

	private function get_albumstolistento() {
		return $this->generic_sql_query("SELECT * FROM AlbumsToListenTotable");
	}

}

?>