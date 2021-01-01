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
					'Spoken Word' => file_exists($backup.'/audiobooks.json') ? 'OK' : 'Missing!',
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
				fwrite($monitor, "\n<b>Restoring Playcounts : </b>".$progress."%");
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
		fwrite($monitor, "\n<b>Cleaning Up...</b>\n");
		// Now... we may have restored data on tracks that were previously local and now aren't there any more.
		// If they're local tracks that have been removed, then we don't want them or care about their data
		if (prefs::$prefs['player_backend'] == "mpd") {
			$this->generic_sql_query("DELETE FROM Tracktable WHERE Uri IS NOT NULL AND LastModified IS NULL AND Hidden = 0", true);
		} else {
			$this->generic_sql_query("DELETE FROM Tracktable WHERE Uri LIKE 'local:%' AND LastModified IS NULL AND Hidden = 0", true);
		}
		$this->check_transaction();
		$this->resetallsyncdata();
		$this->remove_cruft();
		$this->update_track_stats();
		$this->close_transaction();
		fwrite($monitor, "\n \n");
		fclose($monitor);
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
		foreach (['isaudiobook', 'playcount', 'rating', 'tag'] as $thing)
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

}

?>