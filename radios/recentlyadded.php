<?php
chdir('..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");
include ("backends/sql/backend.php");

$mode = $_REQUEST['mode'];
debuglog("Populating Recently Added Sorted By ".$mode, "RECENTLY ADDED");

$uris = array();
$qstring = "";
if ($mode == "random") {
	if ($result = generic_sql_query(sql_recent_tracks())) {
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			array_push($uris, $obj->Uri);
		}
	} else {
		debuglog("Nope, that didn't work","RECENTLY ADDED");
	}
} else {
	// This rather cumbersome code gives us albums in a random order but tracks in order.
	// All attempts to do this with a single SQL query hit a brick wall.
	$albums = array();
	if ($result = generic_sql_query(sql_recent_albums())) {
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			if (!array_key_exists($obj->Albumindex, $albums)) {
				$albums[$obj->Albumindex] = array($obj->TrackNo => $obj->Uri);
			} else {
				if (array_key_exists($obj->TrackNo, $albums[$obj->Albumindex])) {
					array_push($albums[$obj->Albumindex], $obj->Uri);
				} else {
					$albums[$obj->Albumindex][$obj->TrackNo] = $obj->Uri;
				}
			}
		}
		shuffle($albums);
		foreach($albums as $a) {
			ksort($a);
			foreach ($a as $t) {
				array_push($uris, $t);
			}
		}
	}
}

print json_encode($uris);

?>