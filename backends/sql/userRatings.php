<?php
chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
require_once ("utils/imagefunctions.php");
require_once ("international.php");
logger::mark("USERRATING", "--------------------------START---------------------");
require_once ("backends/sql/backend.php");
require_once ("backends/sql/metadatafunctions.php");
require_once ("player/".$prefs['player_backend']."/player.php");

$error = 0;
$count = 1;
$divtype = "album1";
$returninfo = array();
$download_file = "";
$dummydata = array('dummy' => 'baby');

if ($mysqlc == null) {
	logger::error("RATINGS", "Can't Do ratings stuff as no SQL connection!");
	header('HTTP/1.0 403 Forbidden');
	exit(0);
}

$start = time();
open_transaction();
create_foundtracks();
$took = time() - $start;
logger::info("TIMINGS", "Creating FoundTracks took ".$took." seconds");

$params = json_decode(file_get_contents('php://input'), true);

// If you add new actions remember to update actions_requring_cleanup in metahandlers.js

foreach($params as $p) {

	romprmetadata::sanitise_data($p);

	logger::log("USERRATING", "  Doing action",strtoupper($p['action']));
	foreach ($p as $i => $v) {
		if ($i != 'action' && $v) {
			logger::trace("Parameter", "    ",$i,':',$v);
		}
	}

	switch ($p['action']) {

		case 'dummy':
			$returninfo = $dummydata;
			break;

		case 'getplaylist':
			preparePlaylist();
		case 'repopulate':
			$returninfo = doPlaylist($p['playlist'], $p['numtracks']);
			break;

		case 'ratlist':
			$returninfo = get_rating_headers($p['sortby']);
			break;

		case 'ratentries':
			$returninfo = get_rating_info($p['sortby'], $p['value']);
			break;

		case 'metabackup':
			backup_unrecoverable_data();
			$returninfo = $dummydata;
			break;

		case 'getbackupdata':
			$returninfo = analyse_backups();
			break;

		case 'backupremove':
			removeBackup($p['which']);
			$returninfo = $dummydata;
			break;

		case 'backuprestore':
			restoreBackup($p['which']);
			$returninfo = $dummydata;
			break;

		case 'gettags':
			$returninfo = list_tags();
			break;

		case 'getgenres':
			$returninfo = list_genres();
			break;

		case 'getartists':
			$returninfo = list_artists();
			break;

		case 'getalbumartists':
			$returninfo = list_albumartists();
			break;

		case 'getfaveartists':
			$returninfo = get_fave_artists();
			break;

		case 'getrecommendationseeds';
			$returninfo = get_recommendation_seeds($p['days'], $p['limit'], $p['top']);
			break;

		case 'addtolistenlater':
			addToListenLater($p['json']);
			$returninfo = $dummydata;
			break;

		case 'getlistenlater':
			$returninfo = getListenLater();
			break;

		case 'removelistenlater':
			removeListenLater($p['index']);
			$returninfo = $dummydata;
			break;

		case 'getlinktocheck':
			$returninfo = getLinkToCheck();
			break;

		case 'updatelinkcheck':
			updateCheckedLink($p['ttindex'], $p['uri'], $p['status']);
			$returninfo = $dummydata;
			break;

		case 'resetlinkcheck':
			resetLinkCheck();
			$returninfo = $dummydata;
			break;

		case 'get':
		case 'inc':
		case "add":
		case 'set':
		case 'remove':
		case 'cleanup':
		case 'amendalbum':
		case 'deletetag':
		case 'delete':
		case 'deletewl':
		case 'getcharts':
		case 'clearwishlist':
		case 'setalbummbid':
		case 'syncinc':
		case 'resetallsyncdata':
		case 'deleteid':
		case 'deletealbum':
		case 'setasaudiobook':
		case 'resetresume':
			romprmetadata::{$p['action']}($p);
			break;

		default:
			logger::warn("USERRATINGS", "Unknown Request",$p['action']);
			header('HTTP/1.1 400 Bad Request');
			break;


	}
	check_transaction();
}

if (count($returninfo) == 0 || array_key_exists('metadata', $returninfo)) {
	// We don't do a database cleanup here. It can take a long time and this
	// really slows the GUI down. Cleanups and stats updates are done out-of-band
	// by the frontend DB Queue manager by calling into here with action = cleanup
	prepare_returninfo();
}
print json_encode($returninfo);
close_transaction();

logger::mark("USERRATING", "---------------------------END----------------------");

function prepare_returninfo() {
	logger::log("USERRATINGS", "Preparing Return Info");
	$t = microtime(true);
	global $returninfo, $prefs;

	$sorter = choose_sorter_by_key('aartistroot');
	$lister = new $sorter('aartistroot');
	$lister->get_modified_root_items();
	$lister->get_modified_albums();

	$sorter = choose_sorter_by_key('zartistroot');
	$lister = new $sorter('zartistroot');
	$lister->get_modified_root_items();
	$lister->get_modified_albums();

	$sorter = choose_sorter_by_key('bartistroot');
	$lister = new $sorter('bartistroot');
	$lister->get_modified_root_items();
	$lister->get_modified_albums();

	$result = generic_sql_query('SELECT Albumindex, AlbumArtistindex, Uri, TTindex, isAudiobook FROM Tracktable JOIN Albumtable USING (Albumindex) WHERE justAdded = 1 AND Hidden = 0');
	foreach ($result as $mod) {
		logger::log("USERRATING", "  New Track in album ".$mod['Albumindex'].' has TTindex '.$mod['TTindex']);
		$returninfo['addedtracks'][] = array(	'artistindex' => $mod['AlbumArtistindex'],
												'albumindex' => $mod['Albumindex'],
												'trackuri' => rawurlencode($mod['Uri']),
												'isaudiobook' => $mod['isAudiobook']
											);
	}
	$at = microtime(true) - $t;
	logger::info("TIMINGS", " -- Finding modified items took ".$at." seconds");
}


function doCollectionHeader() {
	global $returninfo;
	$returninfo['stats'] = collectionStats();
	$returninfo['bookstats'] = audiobookStats();
}

function check_backup_dir() {
	$dirname = date('Y-m-d-H-i');
	if (is_dir('prefs/databackups/'.$dirname)) {
		rrmdir('prefs/databackups/'.$dirname);
	}
	mkdir('prefs/databackups/'.$dirname, 0755);
	return 'prefs/databackups/'.$dirname;
}

function backup_unrecoverable_data() {

	// This makes a backup of all manually added tracks and all
	// rating, tag, and playcount data. This can be used to restore it
	// or transfer it to another machine

	$dirname = check_backup_dir();

	logger::log("BACKEND", "Backing up manually added tracks");
	$tracks = get_manually_added_tracks();
	file_put_contents($dirname.'/tracks.json',json_encode($tracks));

	logger::log("BACKEND", "Backing up ratings");
	$tracks = get_ratings();
	file_put_contents($dirname.'/ratings.json',json_encode($tracks));

	logger::log("BACKEND", "Backing up Playcounts");
	$tracks = get_playcounts();
	file_put_contents($dirname.'/playcounts.json',json_encode($tracks));

	logger::log("BACKEND", "Backing up Tags");
	$tracks = get_tags();
	file_put_contents($dirname.'/tags.json',json_encode($tracks));

	logger::log("BACKEND", "Backing up Audiobook Status");
	$tracks = get_audiobooks();
	file_put_contents($dirname.'/audiobooks.json',json_encode($tracks));

}

function analyse_backups() {
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

		// FIXME: Save and restore Audiobook status.

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

function removeBackup($which) {
	rrmdir('prefs/databackups/'.$which);
}

function restoreBackup($backup) {
	global $prefs;
	if (file_exists('prefs/backupmonitor')) {
		unlink('prefs/backupmonitor');
	}
	$monitor = fopen('prefs/backupmonitor', 'w');
	if (file_exists('prefs/databackups/'.$backup.'/tracks.json')) {
		logger::mark("BACKUPS", "Restoring Manually Added Tracks");
		$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/tracks.json'), true);
		foreach ($tracks as $i => $trackdata) {
			romprmetadata::sanitise_data($trackdata);
			romprmetadata::add($trackdata, false);
			$progress = round(($i/count($tracks))*100);
			fwrite($monitor, "\n<b>Restoring Manually Added Tracks : </b>".$progress."%");
		}
	}
	if (file_exists('prefs/databackups/'.$backup.'/ratings.json')) {
		logger::mark("BACKUPS", "Restoring Ratings");
		$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/ratings.json'), true);
		foreach ($tracks as $i => $trackdata) {
			romprmetadata::sanitise_data($trackdata);
			$trackdata['attributes'] = array(array('attribute' => 'Rating', 'value' => $trackdata['rating']));
			romprmetadata::set($trackdata, true);
			$progress = round(($i/count($tracks))*100);
			fwrite($monitor, "\n<b>Restoring Ratings : </b>".$progress."%");
		}
	}
	if (file_exists('prefs/databackups/'.$backup.'/tags.json')) {
		logger::mark("BACKUPS", "Restoring Tags");
		$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/tags.json'), true);
		foreach ($tracks as $i => $trackdata) {
			romprmetadata::sanitise_data($trackdata);
			$trackdata['attributes'] = array(array('attribute' => 'Tags', 'value' => explode(',',$trackdata['tag'])));
			romprmetadata::set($trackdata, true);
			$progress = round(($i/count($tracks))*100);
			fwrite($monitor, "\n<b>Restoring Tags : </b>".$progress."%");
		}
	}
	if (file_exists('prefs/databackups/'.$backup.'/playcounts.json')) {
		logger::mark("BACKUPS", "Restoring Playcounts");
		$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/playcounts.json'), true);
		foreach ($tracks as $i => $trackdata) {
			romprmetadata::sanitise_data($trackdata);
			$trackdata['attributes'] = array(array('attribute' => 'Playcount', 'value' => $trackdata['playcount']));
			if (!array_key_exists('lastplayed', $trackdata)) {
				// Sanitise backups made before lastplayed was added
				$trackdata['lastplayed'] = null;
			}
			romprmetadata::inc($trackdata);
			$progress = round(($i/count($tracks))*100);
			fwrite($monitor, "\n<b>Restoring Playcounts : </b>".$progress."%");
		}
	}
	if (file_exists('prefs/databackups/'.$backup.'/audiobooks.json')) {
		logger::mark("BACKUPS", "Restoring Audiobooks");
		$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/audiobooks.json'), true);
		foreach ($tracks as $i => $trackdata) {
			romprmetadata::sanitise_data($trackdata);
			romprmetadata::updateAudiobookState($trackdata);
			$progress = round(($i/count($tracks))*100);
			fwrite($monitor, "\n<b>Restoring Spoken Word Tracks : </b>".$progress."%");
		}
	}
	fwrite($monitor, "\n<b>Cleaning Up...</b>");
	// Now... we may have restored data on tracks that were previously local and now aren't there any more.
	// If they're local tracks that have been removed, then we don't want them or care about their data
	if ($prefs['player_backend'] == "mpd") {
		generic_sql_query("DELETE FROM Tracktable WHERE Uri IS NOT NULL AND LastModified IS NULL AND Hidden = 0", true);
	} else {
		generic_sql_query("DELETE FROM Tracktable WHERE Uri LIKE 'local:%' AND LastModified IS NULL AND Hidden = 0", true);
	}
	romprmetadata::resetallsyncdata();
	remove_cruft();
	update_track_stats();
	fwrite($monitor, '\n ');
	fclose($monitor);
}

function get_manually_added_tracks() {

	// get_manually_added_tracks
	//		Creates data for backup

	return generic_sql_query(
		"SELECT
			Tracktable.Title AS title,
			Tracktable.TrackNo AS trackno,
			Tracktable.Duration AS duration,
			Tracktable.Disc AS disc,
			Tracktable.Uri AS uri,
			Genretable.Genre AS genre,
			Tracktable.Genreindex AS genreindex,
			Albumtable.Albumname AS album,
			Albumtable.AlbumUri AS albumuri,
			Albumtable.Year AS date,
			ta.Artistname AS artist,
			aat.Artistname AS albumartist
		FROM
			Tracktable
			JOIN Genretable USING (Genreindex)
			JOIN Artisttable AS ta USING (Artistindex)
			JOIN Albumtable ON Tracktable.Albumindex = Albumtable.Albumindex
			JOIN Artisttable AS aat ON Albumtable.AlbumArtistindex = aat.Artistindex
		WHERE Tracktable.LastModified IS NULL AND Tracktable.Hidden = 0 AND Tracktable.isSearchResult < 2 AND uri IS NOT NULL");
}

function get_audiobooks() {

	// get_audiobooks
	//		Creates data for backup

	return generic_sql_query(
		"SELECT
			Tracktable.Title AS title,
			Tracktable.TrackNo AS trackno,
			Tracktable.Duration AS duration,
			Tracktable.Disc AS disc,
			Tracktable.Uri AS uri,
			Genretable.Genre AS genre,
			Albumtable.Albumname AS album,
			Albumtable.AlbumUri AS albumuri,
			Albumtable.Year AS date,
			ta.Artistname AS artist,
			aat.Artistname AS albumartist,
			Tracktable.isAudiobook AS isaudiobook
		FROM
			Tracktable
			JOIN Genretable USING (Genreindex)
			JOIN Artisttable AS ta USING (Artistindex)
			JOIN Albumtable ON Tracktable.Albumindex = Albumtable.Albumindex
			JOIN Artisttable AS aat ON Albumtable.AlbumArtistindex = aat.Artistindex
		WHERE Tracktable.Hidden = 0 AND Tracktable.isSearchResult < 2 AND uri IS NOT NULL AND Tracktable.isAudiobook > 0");
}

function get_ratings() {

	// get_ratings
	//		Creates data for backup

	return generic_sql_query(
		"SELECT
			r.Rating AS rating,
			tr.Title AS title,
			tr.TrackNo AS trackno,
			tr.Duration AS duration,
			tr.Disc AS disc,
			tr.Uri AS uri,
			ge.Genre AS genre,
			al.Albumname AS album,
			al.AlbumUri AS albumuri,
			al.Year AS date,
			ta.Artistname AS artist,
			aat.Artistname AS albumartist
		FROM
			Ratingtable AS r
			JOIN Tracktable AS tr USING (TTindex)
			JOIN Genretable AS ge USING (Genreindex)
			JOIN Artisttable AS ta USING (Artistindex)
			JOIN Albumtable AS al ON tr.Albumindex = al.Albumindex
			JOIN Artisttable AS aat ON al.AlbumArtistindex = aat.Artistindex
		WHERE rating > 0 AND tr.Hidden = 0 AND tr.isSearchResult < 2
		ORDER BY rating, albumartist, album, trackno");
}

function get_playcounts() {

	// get_playcounts
	//		Creates data for backup

	return generic_sql_query(
		"SELECT
			p.Playcount AS playcount,
			p.LastPlayed AS lastplayed,
			tr.Title AS title,
			tr.TrackNo AS trackno,
			tr.Duration AS duration,
			tr.Disc AS disc,
			tr.Uri AS uri,
			ge.Genre AS genre,
			al.Albumname AS album,
			al.AlbumUri AS albumuri,
			al.Year AS date,
			ta.Artistname AS artist,
			aat.Artistname AS albumartist
		FROM
			Playcounttable AS p
			JOIN Tracktable AS tr USING (TTindex)
			JOIN Genretable AS ge USING (Genreindex)
			JOIN Artisttable AS ta USING (Artistindex)
			JOIN Albumtable AS al ON tr.Albumindex = al.Albumindex
			JOIN Artisttable AS aat ON al.AlbumArtistindex = aat.Artistindex
		WHERE playcount > 0
		ORDER BY playcount, albumartist, album, trackno");
}

function get_tags() {

	// get_tags
	//		Creates data for backup

	return generic_sql_query(
		"SELECT
			".SQL_TAG_CONCAT."AS tag,
			tr.Title AS title,
			tr.TrackNo AS trackno,
			tr.Duration AS duration,
			tr.Disc AS disc,
			tr.Uri AS uri,
			ge.Genre AS genre,
			al.Albumname AS album,
			al.AlbumUri AS albumuri,
			al.Year AS date,
			ta.Artistname AS artist,
			aat.Artistname AS albumartist
		FROM
			Tagtable AS t
			JOIN TagListtable AS tl USING (Tagindex)
			JOIN Tracktable AS tr ON tl.TTindex = tr.TTindex
			JOIN Genretable AS ge USING (Genreindex)
			JOIN Artisttable AS ta USING (Artistindex)
			JOIN Albumtable AS al ON tr.Albumindex = al.Albumindex
			JOIN Artisttable AS aat ON al.AlbumArtistindex = aat.Artistindex
		WHERE tr.Hidden = 0 AND tr.isSearchResult < 2
		GROUP BY tr.TTindex");
}

function get_recommendation_seeds($days, $limit, $top) {

	// 1. Get a list of tracks played in the last $days days, sorted by their OVERALL popularity
	$resultset = generic_sql_query(
		"SELECT SUM(Playcount) AS playtotal, Artistname, Title, Uri
		FROM Playcounttable JOIN Tracktable USING (TTindex)
		JOIN Artisttable USING (Artistindex)
		WHERE ".sql_two_weeks_include($days).
		" AND Uri IS NOT NULL GROUP BY Artistindex ORDER BY playtotal DESC LIMIT ".$limit);

	// 2. Get a list of recently played tracks, ignoring popularity
	// $result = generic_sql_query(
	// 	"SELECT 0 AS playtotal, Artistname, Title, Uri
	// 	FROM Playcounttable JOIN Tracktable USING (TTindex)
	// 	JOIN Artisttable USING (Artistindex)
	// 	WHERE ".sql_two_weeks_include(intval($days/2)).
	// 	" AND Uri IS NOT NULL GROUP BY Artistindex ORDER BY ".SQL_RANDOM_SORT." LIMIT ".intval($limit/2));
	// $resultset = array_merge($resultset, $result);

	// 3. Get the top tracks overall
	$tracks = get_track_charts(intval($limit/2));
	foreach ($tracks as $track) {
		if ($track['uri']) {
			$resultset[] = array('playtotal' => $track['soundcloud_plays'],
									'Artistname' => $track['label_artist'],
									'Title' => $track['label_track'],
									'Uri' => $track['uri']);
		}
	}

	// 4. Randomise that list and return the first $top.
	shuffle($resultset);
	return array_slice($resultset,0,$top);
}

function get_fave_artists() {
	// Can we have a tuning slider to increase the 'Playcount > x' value?
	generic_sql_query(
		"CREATE TEMPORARY TABLE aplaytable AS SELECT SUM(Playcount) AS playtotal, Artistindex FROM
		(SELECT Playcount, Artistindex FROM Playcounttable JOIN Tracktable USING (TTindex) WHERE
		Playcount > 10) AS derived GROUP BY Artistindex", true);

	$artists = array();
	$result = generic_sql_query(
		"SELECT playtot, Artistname FROM (SELECT SUM(Playcount) AS playtot, Artistindex FROM
		(SELECT Playcount, Artistindex FROM Playcounttable JOIN Tracktable USING (TTindex)) AS
		derived GROUP BY Artistindex) AS alias JOIN Artisttable USING (Artistindex) WHERE
		playtot > (SELECT AVG(playtotal) FROM aplaytable) ORDER BY ".SQL_RANDOM_SORT, false, PDO::FETCH_OBJ);
	foreach ($result as $obj) {
		logger::debug("FAVEARTISTS", "Artist :",$obj->Artistname);
		$artists[] = array( 'name' => $obj->Artistname, 'plays' => $obj->playtot);
	}
	return $artists;
}

function addToListenLater($album) {
	$newid = spotifyAlbumId($album);
	$result = generic_sql_query("SELECT * FROM AlbumsToListenTotable");
	foreach ($result as $r) {
		$d = json_decode($r['JsonData'], true);
		$thisid = spotifyAlbumId($d);
		if ($thisid == $newid) {
			logger::warn("LISTENLATER", "Trying to add duplicate album to Listen Later");
			return;
		}
	}
	$d = json_encode($album);
	sql_prepare_query(true, null, null, null, "INSERT INTO AlbumsToListenTotable (JsonData) VALUES (?)", $d);
}

function getListenLater() {
	$result = generic_sql_query("SELECT * FROM AlbumsToListenTotable");
	$retval =  array();
	foreach ($result as $r) {
		$d = json_decode($r['JsonData']);
		$d->rompr_index = $r['Listenindex'];
		$retval[] = $d;
	}
	return $retval;
}

function removeListenLater($id) {
	generic_sql_query("DELETE FROM AlbumsToListenTotable WHERE Listenindex = ".$id, true);
}

function spotifyAlbumId($album) {
	if (array_key_exists('album', $album)) {
		return $album['album']['id'];
	} else {
		return $album['id'];
	}
}

function getLinkToCheck() {
	// LinkChecked:
	// 0 = Not Checked, Assumed Playable or Playable at last check
	// 1 = Not Checked, Unplayable at last check
	// 2 = Checked, Playable
	// 3 = Checked, Unplayable
	return generic_sql_query("SELECT TTindex, Uri, LinkChecked FROM Tracktable WHERE Uri LIKE 'spotify:%' AND Hidden = 0 AND isSearchResult < 2 AND LinkChecked < 2 ORDER BY TTindex ASC LIMIT 25");
}

function updateCheckedLink($ttindex, $uri, $status) {
	logger::trace("METADATA", "Updating Link Check For TTindex",$ttindex,$uri);
	sql_prepare_query(true, null, null, null,
		"UPDATE Tracktable SET LinkChecked = ?, Uri = ? WHERE TTindex = ?", $status, $uri, $ttindex);
}

function resetLinkCheck() {
	generic_sql_query("UPDATE Tracktable SET LinkChecked = 0 WHERE LinkChecked = 2");
	generic_sql_query("UPDATE Tracktable SET LinkChecked = 1 WHERE LinkChecked = 3");
}

?>
