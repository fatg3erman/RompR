<?php
chdir('../..');
set_time_limit(360);
include ("includes/vars.php");
include ("includes/functions.php");
include ("utils/imagefunctions.php");
include ("international.php");
debuglog("--------------------------START---------------------","USERRATING",4);
include ("backends/sql/backend.php");
include ("backends/sql/metadatafunctions.php");
include("player/mpd/connection.php");

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

open_transaction();
create_foundtracks();

$params = json_decode(file_get_contents('php://input'), true);

foreach($params as $p) {

	sanitise_data($p);
	debuglog("  Action is \n".multi_implode($p,"\n"),"USERRATING",8);

	switch ($p['action']) {

		case 'dummy':
			$returninfo = $dummydata;
			break;

		case 'getplaylist':
			preparePlaylist();
		case 'repopulate':
			$returninfo = doPlaylist($p['playlist'], $p['numtracks']);
			break;

		case 'taglist':
			$returninfo = list_all_tag_data();
			break;

		case 'ratlist':
			$returninfo = list_all_rating_data($p['sortby']);
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
	prepare_returninfo();
	remove_cruft();
	update_track_stats();
	doCollectionHeader();
}
print json_encode($returninfo);
close_transaction();
close_mpd();

debuglog("---------------------------END----------------------","USERRATING",4);

function sanitise_data(&$data) {

	foreach (array( 'action',
					'title',
					'artist',
					'trackno',
					'duration',
					'albumuri',
					'image',
					'album',
					'uri',
					'trackai',
					'albumai',
					'albumindex',
					'searched',
					'lastmodified',
					'ambid',
					'attributes',
					'which',
					'wltrack',
					'reqid') as $key) {
		if (!array_key_exists($key, $data)) {
			$data[$key] = null;
		}
	}
	foreach (array( 'trackno', 'duration') as $key) {
		if ($data[$key] == null) {
			$data[$key] = 0;
		}
	}
	$data['albumartist'] = array_key_exists('albumartist', $data) ? $data['albumartist'] : $data['artist'];
	$data['date'] = (array_key_exists('date', $data) && $data['date'] != 0) ? getYear($data['date']) : null;
	$data['urionly'] = array_key_exists('urionly', $data) ? true : false;
	$data['disc'] = array_key_exists('disc', $data) ? $data['disc'] : 1;
	$data['domain'] = array_key_exists('domain', $data) ? $data['domain'] : ($data['uri'] === null ? "local" : getDomain($data['uri']));
	$data['imagekey'] = array_key_exists('imagekey', $data) ? $data['imagekey'] : make_image_key($data['albumartist'],$data['album']);
	$data['hidden'] = 0;
	$data['searchflag'] = 0;
	if (substr($data['image'],0,4) == "http") {
		$data['image'] = "getRemoteImage.php?url=".$data['image'];
	}

}

function prepare_returninfo() {
	debuglog("Preparing Return Info","USERRATINGS",6);
	global $returninfo, $prefs;
	if ($result = generic_sql_query('SELECT DISTINCT AlbumArtistindex FROM Albumtable WHERE justUpdated = 1')) {
		while ($mod = $result->fetch(PDO::FETCH_ASSOC)) {
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
	}
	if ($result = generic_sql_query('SELECT Albumindex, AlbumArtistindex FROM Albumtable WHERE justUpdated = 1')) {
		while ($mod = $result->fetch(PDO::FETCH_ASSOC)) {
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
	}
	if ($result = generic_sql_query('SELECT Albumindex, AlbumArtistindex, Uri, TTindex FROM Tracktable JOIN Albumtable USING (Albumindex) WHERE justAdded = 1 AND Hidden = 0')) {
		while ($mod = $result->fetch(PDO::FETCH_ASSOC)) {
			debuglog("  New Track in album ".$mod['Albumindex'].' has TTindex '.$mod['TTindex'],"USERRATING");
			$returninfo['addedtracks'][] = array('artistindex' => $mod['AlbumArtistindex'], 'albumindex' => $mod['Albumindex'], 'trackuri' => rawurlencode($mod['Uri']));
		}
	}
	$result = null;
}

function artist_albumcount($artistindex) {
	$retval = 0;
	if ($result = generic_sql_query(
		"SELECT
			COUNT(Albumindex) AS num
		FROM
			Albumtable LEFT JOIN Tracktable USING (Albumindex)
		WHERE
			AlbumArtistindex = ".$artistindex.
			" AND Hidden = 0
			AND isSearchResult < 2
			AND Uri IS NOT NULL")) {
		$obj = $result->fetch(PDO::FETCH_OBJ);
		$retval = $obj->num;
	} else {
		debuglog("ERROR counting albums for Artist ".$artistindex,"USERRATINGS",2);
	}
	$result = null;
	return $retval;
}

function album_trackcount($albumindex) {
	$retval = 0;
	if ($result = generic_sql_query(
		"SELECT
			COUNT(TTindex) AS num
		FROM
			Tracktable
		WHERE
			Albumindex = ".$albumindex.
			" AND Hidden = 0
			AND isSearchResult < 2
			AND Uri IS NOT NULL")) {
		$obj = $result->fetch(PDO::FETCH_OBJ);
		$retval = $obj->num;
	} else {
		debuglog("ERROR counting tracks for album ".$albumindex,"USERRATINGS",2);
	}
	$result = null;
	return $retval;
}

function forcedUriOnly($u,$d) {

	// Some mopidy backends - YouTube and SoundCloud - can return the same artist/album/track info
	// for multiple different tracks.
	// This gives us a problem because romprmetadata::find_item will think they're the same.
	// So for those backends we always force urionly to be true
	debuglog("Checking domain : ".$d,"USERRATINGS",9);

	if ($u || $d == "youtube" || $d == "soundcloud") {
		return true;
	} else {
		return false;
	}

}

function preparePlaylist() {
	generic_sql_query("DROP TABLE IF EXISTS pltable");
	generic_sql_query("CREATE TABLE pltable(TTindex INT UNSIGNED NOT NULL UNIQUE)");
}

function doPlaylist($playlist, $limit) {
	debuglog("Loading Playlist ".$playlist,"RATINGS");
	$sqlstring = "";
	$tags = null;
	$random = true;
	switch($playlist) {
		case "1stars":
			$sqlstring = "SELECT TTindex FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Uri
				IS NOT NULL AND Hidden=0 AND isSearchResult < 2 AND Rating > 0";
			break;
		case "2stars":
			$sqlstring = "SELECT TTindex FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Uri
				IS NOT NULL AND Hidden=0 AND isSearchResult < 2 AND Rating > 1";
			break;
		case "3stars":
			$sqlstring = "SELECT TTindex FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Uri
				IS NOT NULL AND Hidden=0 AND isSearchResult < 2 AND Rating > 2";
			break;
		case "4stars":
			$sqlstring = "SELECT TTindex FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Uri
				IS NOT NULL AND Hidden=0 AND isSearchResult < 2 AND Rating > 3";
			break;
		case "5stars":
			$sqlstring = "SELECT TTindex FROM Tracktable JOIN Ratingtable USING (TTindex) WHERE Uri
				IS NOT NULL AND Hidden=0 AND isSearchResult < 2 AND Rating > 4";
			break;
		case "mostplayed":
			// Used to be tracks with above average playcount, now also includes any rated tracks.
			// Still called mostplayed :)
			$avgplays = getAveragePlays();
			$sqlstring = "SELECT TTindex FROM Tracktable JOIN Playcounttable USING (TTindex)
				LEFT JOIN Ratingtable USING (TTindex) WHERE Uri IS NOT NULL AND Hidden = 0 AND
				isSearchResult < 2 AND (Playcount > ".$avgplays." OR Rating IS NOT NULL)";
			break;
		case "allrandom":
			$sqlstring = "SELECT TTindex FROM Tracktable WHERE Uri IS NOT NULL AND Hidden=0 AND
				isSearchResult < 2";
			break;
		case "neverplayed":
			// LEFT JOIN (used here and above) means that the right-hand side of the JOIN will be
			// NULL if TTindex doesn't exist on that side. Very handy.
			$sqlstring = "SELECT Tracktable.TTindex FROM Tracktable LEFT JOIN Playcounttable ON
				Tracktable.TTindex = Playcounttable.TTindex WHERE Playcounttable.TTindex IS NULL";
			break;
		case "recentlyplayed":
			$sqlstring = recently_played_playlist();
			break;
		default:
			if (preg_match('/tag\+(.*)/', $playlist, $matches)) {
				$taglist = explode(',', $matches[1]);
				$sqlstring = "SELECT DISTINCT TTindex FROM Tracktable JOIN TagListtable USING
					(TTindex) JOIN Tagtable USING (Tagindex) WHERE (";
				$tags = array();
				foreach ($taglist as $i => $tag) {
					debuglog("Getting tag playlist for ".$tag,"PLAYLISTS",6);
					$tags[] = trim($tag);
					if ($i > 0) {
						$sqlstring .= " OR ";
					}
					$sqlstring .=  "Tagtable.Name = ?";
				}
				$sqlstring .= ") AND Tracktable.Uri IS NOT NULL AND Tracktable.Hidden = 0 AND
					Tracktable.isSearchResult < 2 ";
			} else {
				debuglog("Unrecognised playlist ".$playlist,"PLAYLISTS", 4);
			}
			break;
	}
	$uris = getAllURIs($sqlstring, $limit, $tags, $random);
	$json = array();
	foreach ($uris as $u) {
		$json[] = array( 'type' => 'uri', 'name' => $u);
	}
	return $json;
}

function doCollectionHeader() {
	global $returninfo;
	$returninfo['stats'] = alistheader(get_stat('ArtistCount'), get_stat('AlbumCount'),
			get_stat('TrackCount'), format_time(get_stat('TotalTime')));
}

function check_backup_dir() {
	$dirname = date('Y-m-d-H-i');
	if (is_dir('prefs/databackups/'.$dirname)) {
		exec('rm -fR prefs/databackups/'.$dirname);
	}
	exec('mkdir prefs/databackups/'.$dirname);
	return 'prefs/databackups/'.$dirname;
}

function backup_unrecoverable_data() {

	// This makes a backup of all manually added tracks and all
	// rating, tag, and playcount data. This can be used to restore it
	// or transfer it to another machine

	$dirname = check_backup_dir();

	debuglog("Backing up manually added tracks","BACKEND",5);
	$tracks = get_manually_added_tracks();
	if ($tracks === false) {
		debuglog(" ..well, that didn't work","BACKEND",1);
	} else {
		file_put_contents($dirname.'/tracks.json',json_encode($tracks));
	}
	debuglog("Backing up ratings","BACKEND",5);
	$tracks = get_ratings();
	if ($tracks === false) {
		debuglog(" ..well, that didn't work","BACKEND",1);
	} else {
		file_put_contents($dirname.'/ratings.json',json_encode($tracks));
	}
	debuglog("Backing up Playcounts","BACKEND",5);
	$tracks = get_playcounts();
	if ($tracks === false) {
		debuglog(" ..well, that didn't work","BACKEND",1);
	} else {
		file_put_contents($dirname.'/playcounts.json',json_encode($tracks));
	}
	debuglog("Backing up Tags","BACKEND",5);
	$tracks = get_tags();
	if ($tracks === false) {
		debuglog(" ..well, that didn't work","BACKEND",1);
	} else {
		file_put_contents($dirname.'/tags.json',json_encode($tracks));
	}
}

function analyse_backups() {
	$data = array();
	$bs = glob('prefs/databackups/*');
	rsort($bs);
	foreach ($bs as $backup) {
		$tracks = count(json_decode(file_get_contents($backup.'/tracks.json')));
		$ratings = count(json_decode(file_get_contents($backup.'/ratings.json')));
		$playcounts = count(json_decode(file_get_contents($backup.'/playcounts.json')));
		$tags = count(json_decode(file_get_contents($backup.'/tags.json')));
		$data[] = array(
			'dir' => basename($backup),
			'name' => strftime('%c', DateTime::createFromFormat('Y-m-d-H-i', basename($backup))->getTimestamp()),
			'stats' => array(
				'Manually Added Tracks' => $tracks,
				'Playcounts' => $playcounts,
				'Tracks With Ratings' => $ratings,
				'Tracks With Tags' => $tags,
			)
		);
	}
	return $data;
}

function removeBackup($which) {
	system('rm -fR prefs/databackups/'.$which);
}

function restoreBackup($backup) {
	global $prefs;
	if (file_exists('prefs/databackups/'.$backup.'/tracks.json')) {
		debuglog("Restoring Manually Added Tracks",4,"BACKUPS");
		$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/tracks.json'), true);
		foreach ($tracks as $trackdata) {
			sanitise_data($trackdata);
			romprmetadata::add($trackdata);
		}
	}
	if (file_exists('prefs/databackups/'.$backup.'/ratings.json')) {
		debuglog("Restoring Ratings",4,"BACKUPS");
		$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/ratings.json'), true);
		foreach ($tracks as $trackdata) {
			sanitise_data($trackdata);
			$trackdata['attributes'] = array(array('attribute' => 'Rating', 'value' => $trackdata['rating']));
			romprmetadata::set($trackdata);
		}
	}
	if (file_exists('prefs/databackups/'.$backup.'/tags.json')) {
		debuglog("Restoring Tags",4,"BACKUPS");
		$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/tags.json'), true);
		foreach ($tracks as $trackdata) {
			sanitise_data($trackdata);
			$trackdata['attributes'] = array(array('attribute' => 'Tags', 'value' => explode(',',$trackdata['tag'])));
			romprmetadata::set($trackdata);
		}
	}
	if (file_exists('prefs/databackups/'.$backup.'/playcounts.json')) {
		debuglog("Restoring Playcounts",4,"BACKUPS");
		$tracks = json_decode(file_get_contents('prefs/databackups/'.$backup.'/playcounts.json'), true);
		foreach ($tracks as $trackdata) {
			sanitise_data($trackdata);
			$trackdata['attributes'] = array(array('attribute' => 'Playcount', 'value' => $trackdata['playcount']));
			if (!array_key_exists('lastplayed', $trackdata)) {
				// Sanitise backups made before lastplayed was added
				$trackdata['lastplayed'] = null;
			}
			romprmetadata::inc($trackdata);
		}
	}
	// Now... we may have restored data on tracks that were previously local and now aren't there any more.
	// If they're local tracks that have been removed, then we don't want them or care about their data
	if ($prefs['player_backend'] == "mpd") {
		generic_sql_query("DELETE FROM Tracktable WHERE Uri IS NOT NULL AND LastModified IS NULL AND Hidden = 0");
	} else {
		generic_sql_query("DELETE FROM Tracktable WHERE Uri LIKE 'local:%' AND LastModified IS NULL AND Hidden = 0");
	}
	remove_cruft();
}

function get_manually_added_tracks() {

	// get_manually_added_tracks
	//		Creates data for backup

	if ($result = generic_sql_query(
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
		WHERE Tracktable.LastModified IS NULL AND Tracktable.Hidden = 0 AND Tracktable.isSearchResult < 2 AND uri IS NOT NULL"
		)) {
		return $result->fetchAll(PDO::FETCH_ASSOC);
	} else {
		return false;
	}
}

function get_ratings() {

	// get_ratings
	//		Creates data for backup

	if ($result = generic_sql_query(
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
		ORDER BY rating, albumartist, album, trackno"
		)) {
		return $result->fetchAll(PDO::FETCH_ASSOC);
	} else {
		return false;
	}
}

function get_playcounts() {

	// get_playcounts
	//		Creates data for backup

	if ($result = generic_sql_query(
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
		ORDER BY playcount, albumartist, album, trackno"
		)) {
		return $result->fetchAll(PDO::FETCH_ASSOC);
	} else {
		return false;
	}
}

function get_tags() {

	// get_tags
	//		Creates data for backup

	if ($result = generic_sql_query(
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
		GROUP BY tr.TTindex"
	)) {
		return $result->fetchAll(PDO::FETCH_ASSOC);
	} else {
		return false;
	}
}

function getAllURIs($sqlstring, $limit, $tags, $random = true) {

	// Get all track URIs using a supplied SQL string. For playlist generators
	$uris = array();
	$tries = 0;
	do {
		if ($tries == 1) {
			debuglog("No URIs found. Resetting history table","SMART PLAYLIST",5);
			preparePlaylist();
		}
		generic_sql_query("CREATE TEMPORARY TABLE IF NOT EXISTS pltemptable(TTindex INT UNSIGNED NOT NULL UNIQUE)");
		theBabyDumper($sqlstring, $limit, $tags, $random);
		if ($result = generic_sql_query(
			"SELECT Uri FROM Tracktable WHERE TTindex IN (SELECT TTindex FROM pltemptable)")) {
			while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
				$uris[] = $obj->Uri;
				debuglog("URI : ".$obj->Uri,"SMART PLAYLIST");
			}
		}
		$tries++;
	} while (count($uris) == 0 && $tries < 2);
	$result = null;
	generic_sql_query("INSERT INTO pltable (TTindex) SELECT TTindex FROM pltemptable");
	return $uris;
}

function theBabyDumper($sqlstring, $limit, $tags, $random) {
	debuglog("Selector is ".$sqlstring,"SMART PLAYLIST",6);
	$rndstr = $random ? " ORDER BY ".SQL_RANDOM_SORT : " ORDER BY Albumindex, TrackNo";
	if ($tags) {
		$stmt = sql_prepare_query_later(
			"INSERT INTO pltemptable(TTindex) ".$sqlstring.
			" AND NOT Tracktable.TTindex IN (SELECT TTindex FROM pltable)".$rndstr." LIMIT ".$limit);
		if ($stmt !== false) {
			$stmt->execute($tags);
		}
		$stmt = null;
	} else {
		generic_sql_query(
			"INSERT INTO pltemptable(TTindex) ".$sqlstring.
			" AND NOT Tracktable.TTindex IN (SELECT TTindex FROM pltable)".$rndstr." LIMIT ".$limit);
	}
}

function get_recommendation_seeds($days, $limit, $top) {

	// 1. Get a list of tracks played in the last $days days, sorted by their OVERALL popularity
	$resultset = array();
	if ($result = generic_sql_query(
		"SELECT SUM(Playcount) AS playtotal, Artistname, Title, Uri
		FROM Playcounttable JOIN Tracktable USING (TTindex)
		JOIN Artisttable USING (Artistindex)
		WHERE ".sql_two_weeks_include($days).
		" AND Uri IS NOT NULL GROUP BY Artistindex ORDER BY playtotal DESC LIMIT ".$limit)) {
		$resultset = $result->fetchAll(PDO::FETCH_ASSOC);
	}

	// 2. Get a list of recently played tracks, ignoring popularity
	if ($result = generic_sql_query(
		"SELECT 0 AS playtotal, Artistname, Title, Uri
		FROM Playcounttable JOIN Tracktable USING (TTindex)
		JOIN Artisttable USING (Artistindex)
		WHERE ".sql_two_weeks_include(intval($days/2)).
		" AND Uri IS NOT NULL GROUP BY Artistindex ORDER BY ".SQL_RANDOM_SORT." LIMIT ".intval($limit/2))) {
		$resultset = array_merge($resultset, $result->fetchAll(PDO::FETCH_ASSOC));
	}

	// 3. Get the top tracks overall
	$tracks = get_track_charts(intval($limit/2));
	foreach ($tracks as $track) {
		if ($track['uri']) {
			$resultset[] = array('playtotal' => $track['soundcloud_plays'],
									'Artistname' => $track['label_artist'],
									'Uri' => $track['uri']);
		}
	}

	// 4. Randomise that list and return the first $top.
	$artists = array();
	$results = array();
	foreach ($resultset as $result) {
		if (!in_array($result['Artistname'], $artists)) {
			$artists[] = $result['Artistname'];
			$results[] = $result;
		}
	}

	shuffle($results);
	$resultset = array_slice($results,0,$top);
	return $resultset;
}

function getAveragePlays() {
	$avgplays = simple_query('avg(Playcount)', 'Playcounttable', null, null, 0);
	return round($avgplays, 0, PHP_ROUND_HALF_DOWN);
}

function get_fave_artists() {
	// Can we have a tuning slider to increase the 'Playcount > x' value?
	generic_sql_query(
		"CREATE TEMPORARY TABLE aplaytable AS SELECT SUM(Playcount) AS playtotal, Artistindex FROM
		(SELECT Playcount, Artistindex FROM Playcounttable JOIN Tracktable USING (TTindex) WHERE
		Playcount > 10) AS derived GROUP BY Artistindex");

	$artists = array();
	if ($result = generic_sql_query(
		"SELECT playtot, Artistname FROM (SELECT SUM(Playcount) AS playtot, Artistindex FROM
		(SELECT Playcount, Artistindex FROM Playcounttable JOIN Tracktable USING (TTindex)) AS
		derived GROUP BY Artistindex) AS alias JOIN Artisttable USING (Artistindex) WHERE
		playtot > (SELECT AVG(playtotal) FROM aplaytable) ORDER BY ".SQL_RANDOM_SORT)) {
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			debuglog("Artist : ".$obj->Artistname,"FAVEARTISTS");
			$artists[] = array( 'name' => $obj->Artistname, 'plays' => $obj->playtot);
		}
	}
	$result = null;
	return $artists;
}


?>