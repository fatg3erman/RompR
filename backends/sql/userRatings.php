<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
require_once ("utils/imagefunctions.php");
include ("international.php");
debuglog("--------------------------START---------------------","USERRATING",4);
include ("backends/sql/backend.php");
include ("backends/sql/metadatafunctions.php");
$start = time();
include("player/mpd/connection.php");
$took = time() - $start;
debuglog("Connected to player in ".$took." seconds", "USERRATING",8);

$error = 0;
$count = 1;
$divtype = "album1";
$returninfo = array();
$download_file = "";
$dummydata = array('dummy' => 'baby');

if ($mysqlc == null) {
	debuglog("Can't Do ratings stuff as no SQL connection!","RATINGS",1);
	header('HTTP/1.0 403 Forbidden');
	exit(0);
}

$start = time();
open_transaction();
create_foundtracks();
$took = time() - $start;
debuglog("Creating FoundTracks took ".$took." seconds", "USERRATING",8);

$params = json_decode(file_get_contents('php://input'), true);

// If you add new actions remember to update actions_requring_cleanup in metahandlers.js

foreach($params as $p) {

	romprmetadata::sanitise_data($p);

	debuglog("Doing action ".strtoupper($p['action']), "USERRATING", 7);
	foreach ($p as $i => $v) {
		if ($i != 'action' && $v) {
			if (is_array($v)) {
				debuglog(' Array - '.multi_implode($v,', '), ' '.$i,8);
			} else {
				debuglog(' '.$v, ' '.$i,8);
			}
		}
	}
	// debuglog("  Action is \n".multi_implode($p,", "),"USERRATING",9);

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
			romprmetadata::{$p['action']}($p);
			break;

		default:
			debuglog("Unknown Request ".$p['action'],"USERRATINGS",2);
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
close_mpd();

debuglog("---------------------------END----------------------","USERRATING",4);

function prepare_returninfo() {
	debuglog("Preparing Return Info","USERRATINGS",6);
	global $returninfo, $prefs;
	$t = microtime(true);
	$result = generic_sql_query('SELECT DISTINCT AlbumArtistindex FROM Albumtable WHERE justUpdated = 1');
	foreach ($result as $mod) {
		if (artist_albumcount($mod['AlbumArtistindex']) == 0) {
			$returninfo['deletedartists'][] = $mod['AlbumArtistindex'];
			debuglog("  Artist ".$mod['AlbumArtistindex']." has no visible albums","USERRATINGS",6);
		} else {
			debuglog("  Artist ".$mod['AlbumArtistindex']." has modified albums","USERRATINGS",6);
			switch ($prefs['sortcollectionby']) {
				case 'album':
					break;

				case 'artist':
					debuglog("    Creating Artist Header","USERRATINGS",7);
					$returninfo['modifiedartists'][] = do_artists_from_database('a', $prefs['sortcollectionby'], $mod['AlbumArtistindex']);
					break;

				case 'albumbyartist':
					debuglog("    Creating Artist Banner","USERRATINGS",7);
					if ($prefs['showartistbanners']) {
						$returninfo['modifiedartists'][] = do_artist_banner('a','album',$mod['AlbumArtistindex']);
					}
					break;
			}
		}
	}

	$at = microtime(true) - $t;
	debuglog(" -- Finding removed artists took ".$at." seconds","BACKEND",8);

	$t = microtime(true);
	$result = generic_sql_query('SELECT Albumindex, AlbumArtistindex FROM Albumtable WHERE justUpdated = 1');
	foreach ($result as $mod) {
		if (album_trackcount($mod['Albumindex']) == 0) {
			debuglog("  Album ".$mod['Albumindex']." has no visible tracks","USERRATINGS",6);
			$returninfo['deletedalbums'][] = $mod['Albumindex'];
		} else {
			debuglog("  Album ".$mod['Albumindex']." was modified","USERRATINGS",6);
			switch ($prefs['sortcollectionby']) {
				case 'album':
				case 'albumbyartist':
					$r = do_albums_from_database('a', 'album', 'root', $mod['Albumindex'], false, false);
					break;

				case 'artist':
					$r = do_albums_from_database('a', 'album', $mod['AlbumArtistindex'], $mod['Albumindex'], false, false);
					break;
			}
			$r['tracklist'] = do_tracks_from_database('a', 'album', $mod['Albumindex'], true);
			$returninfo['modifiedalbums'][] = $r;
		}
	}
	$at = microtime(true) - $t;
	debuglog(" -- Finding removed albums took ".$at." seconds","BACKEND",8);

	$t = microtime(true);
	$result = generic_sql_query('SELECT Albumindex, AlbumArtistindex, Uri, TTindex FROM Tracktable JOIN Albumtable USING (Albumindex) WHERE justAdded = 1 AND Hidden = 0');
	foreach ($result as $mod) {
		debuglog("  New Track in album ".$mod['Albumindex'].' has TTindex '.$mod['TTindex'],"USERRATING");
		$returninfo['addedtracks'][] = array('artistindex' => $mod['AlbumArtistindex'], 'albumindex' => $mod['Albumindex'], 'trackuri' => rawurlencode($mod['Uri']));
	}
	$at = microtime(true) - $t;
	debuglog(" -- Finding added tracks took ".$at." seconds","BACKEND",8);
}

function artist_albumcount($artistindex) {
	return generic_sql_query(
		"SELECT
			COUNT(Albumindex) AS num
		FROM
			Albumtable LEFT JOIN Tracktable USING (Albumindex)
		WHERE
			AlbumArtistindex = ".$artistindex.
			" AND Hidden = 0
			AND isSearchResult < 2
			AND Uri IS NOT NULL", false, null, 'num', 0);
}

function album_trackcount($albumindex) {
	return generic_sql_query(
		"SELECT
			COUNT(TTindex) AS num
		FROM
			Tracktable
		WHERE
			Albumindex = ".$albumindex.
			" AND Hidden = 0
			AND isSearchResult < 2
			AND Uri IS NOT NULL", false, null, 'num', 0);
}

function doCollectionHeader() {
	global $returninfo;
	$returninfo['stats'] = collectionStats();
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

	debuglog("Backing up manually added tracks","BACKEND",5);
	$tracks = get_manually_added_tracks();
	file_put_contents($dirname.'/tracks.json',json_encode($tracks));

	debuglog("Backing up ratings","BACKEND",5);
	$tracks = get_ratings();
	file_put_contents($dirname.'/ratings.json',json_encode($tracks));

	debuglog("Backing up Playcounts","BACKEND",5);
	$tracks = get_playcounts();
	file_put_contents($dirname.'/playcounts.json',json_encode($tracks));

	debuglog("Backing up Tags","BACKEND",5);
	$tracks = get_tags();
	file_put_contents($dirname.'/tags.json',json_encode($tracks));

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
		$data[] = array(
			'dir' => basename($backup),
			'name' => strftime('%c', DateTime::createFromFormat('Y-m-d-H-i', basename($backup))->getTimestamp()),
			'stats' => array(
				'Manually Added Tracks' => file_exists($backup.'/tracks.json') ? 'OK' : 'Missing!',
				'Playcounts' => file_exists($backup.'/playcounts.json') ? 'OK' : 'Missing!',
				'Tracks With Ratings' => file_exists($backup.'/ratings.json') ? 'OK' : 'Missing!',
				'Tracks With Tags' => file_exists($backup.'/tags.json') ? 'OK' : 'Missing!',
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
		debuglog("Restoring Manually Added Tracks",4,"BACKUPS");
		$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/tracks.json'), true);
		foreach ($tracks as $i => $trackdata) {
			romprmetadata::sanitise_data($trackdata);
			romprmetadata::add($trackdata, false);
			$progress = round(($i/count($tracks))*100);
			fwrite($monitor, "\n<b>Restoring Manually Added Tracks : </b>".$progress."%");
		}
	}
	if (file_exists('prefs/databackups/'.$backup.'/ratings.json')) {
		debuglog("Restoring Ratings",4,"BACKUPS");
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
		debuglog("Restoring Tags",4,"BACKUPS");
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
		debuglog("Restoring Playcounts",4,"BACKUPS");
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
			Albumtable.Albumname AS album,
			Albumtable.AlbumUri AS albumuri,
			Albumtable.Year AS date,
			ta.Artistname AS artist,
			aat.Artistname AS albumartist
		FROM
			Tracktable
			JOIN Artisttable AS ta USING (Artistindex)
			JOIN Albumtable ON Tracktable.Albumindex = Albumtable.Albumindex
			JOIN Artisttable AS aat ON Albumtable.AlbumArtistindex = aat.Artistindex
		WHERE Tracktable.LastModified IS NULL AND Tracktable.Hidden = 0 AND Tracktable.isSearchResult < 2 AND uri IS NOT NULL");
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
			al.Albumname AS album,
			al.AlbumUri AS albumuri,
			al.Year AS date,
			ta.Artistname AS artist,
			aat.Artistname AS albumartist
		FROM
			Ratingtable AS r
			JOIN Tracktable AS tr USING (TTindex)
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
			al.Albumname AS album,
			al.AlbumUri AS albumuri,
			al.Year AS date,
			ta.Artistname AS artist,
			aat.Artistname AS albumartist
		FROM
			Playcounttable AS p
			JOIN Tracktable AS tr USING (TTindex)
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
			al.Albumname AS album,
			al.AlbumUri AS albumuri,
			al.Year AS date,
			ta.Artistname AS artist,
			aat.Artistname AS albumartist
		FROM
			Tagtable AS t
			JOIN TagListtable AS tl USING (Tagindex)
			JOIN Tracktable AS tr ON tl.TTindex = tr.TTindex
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
	$result = generic_sql_query(
		"SELECT 0 AS playtotal, Artistname, Title, Uri
		FROM Playcounttable JOIN Tracktable USING (TTindex)
		JOIN Artisttable USING (Artistindex)
		WHERE ".sql_two_weeks_include(intval($days/2)).
		" AND Uri IS NOT NULL GROUP BY Artistindex ORDER BY ".SQL_RANDOM_SORT." LIMIT ".intval($limit/2));
	$resultset = array_merge($resultset, $result);

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
		debuglog("Artist : ".$obj->Artistname,"FAVEARTISTS");
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
			debuglog("Trying to add duplicate album to Listen Later","LISTENLATER");
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
	debuglog("Updating Link Check For TTindex ".$ttindex." ".$uri ,"METADATA", 7);
	sql_prepare_query(true, null, null, null,
		"UPDATE Tracktable SET LinkChecked = ?, Uri = ? WHERE TTindex = ?", $status, $uri, $ttindex);
}

function resetLinkCheck() {
	generic_sql_query("UPDATE Tracktable SET LinkChecked = 0 WHERE LinkChecked = 2");
	generic_sql_query("UPDATE Tracktable SET LinkChecked = 1 WHERE LinkChecked = 3");
}

?>
