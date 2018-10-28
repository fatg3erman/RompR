<?php
chdir('../..');

include ("includes/vars.php");
include ("includes/functions.php");
include ("collection/collection.php");
include ("player/mpd/connection.php");
include ("backends/sql/backend.php");

switch ($_REQUEST['action']) {

	case "getchunk":
		get_chunk_of_data($_REQUEST['offset'], $_REQUEST['limit']);
		break;

	case "gettotal":
		get_total_tracks();
		break;

}

function get_chunk_of_data($offset, $limit) {

	$arse = generic_sql_query("SELECT
		TTindex,
		ta.Artistname as Trackartist,
		aa.Artistname as Albumartist,
		Albumname,
		Title,
		Disc,
		TrackNo,
		IFNULL(Playcount, 0) AS Playcount
		FROM
		Tracktable JOIN Albumtable USING (Albumindex)
		JOIN Artisttable AS aa ON (Albumtable.AlbumArtistindex = aa.Artistindex)
		JOIN Artisttable AS ta ON (Tracktable.Artistindex = ta.Artistindex)
		LEFT JOIN Playcounttable USING (TTindex)
		WHERE isSearchResult !=2
		ORDER BY aa.Artistname, Albumname, Disc ASC, TrackNo ASC LIMIT ".$offset.", ".$limit);

	debuglog("Got ".count($arse)." rows", "LFMIMPORTER");

	header('Content-Type: application/json; charset=utf-8');
	print json_encode($arse);

}

function get_total_tracks() {

	$arse = generic_sql_query("SELECT
		COUNT(TTindex) AS total
		FROM
		Tracktable JOIN Albumtable USING (Albumindex)
		JOIN Artisttable AS aa ON (Albumtable.AlbumArtistindex = aa.Artistindex)
		JOIN Artisttable AS ta ON (Tracktable.Artistindex = ta.Artistindex)
		LEFT JOIN Playcounttable USING (TTindex)
		WHERE isSearchResult !=2");

	debuglog("Got ".count($arse)." rows", "LFMIMPORTER");
	header('Content-Type: application/json; charset=utf-8');
	print json_encode(array('total' => $arse[0]['total']));

}


?>