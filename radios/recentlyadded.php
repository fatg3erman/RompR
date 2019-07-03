<?php
chdir('..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");
include ("backends/sql/backend.php");
include ("backends/sql/metadatafunctions.php");

$mode = $_REQUEST['mode'];
logger::log("RECENTLY ADDED", "Populating Recently Added Sorted By ".$mode);

preparePlaylist();
preparePlTrackTable();

$qstring = "";
$count = 0;
if ($mode == "recentlyadded_random") {
	$result = generic_sql_query(sql_recent_tracks());
	foreach ($result as $t) {
		generic_sql_query("INSERT INTO pltracktable (TTindex) VALUES (".$t['TTindex'].")");
		$count++;
	}
} else {
	// This rather cumbersome code gives us albums in a random order but tracks in order.
	// All attempts to do this with a single SQL query hit a brick wall.
	$albums = array();
	$result = generic_sql_query(sql_recent_albums(), false, PDO::FETCH_OBJ);
	foreach ($result as $obj) {
		if (!array_key_exists($obj->Albumindex, $albums)) {
			$albums[$obj->Albumindex] = array($obj->TrackNo => $obj->TTindex);
		} else {
			if (array_key_exists($obj->TrackNo, $albums[$obj->Albumindex])) {
				array_push($albums[$obj->Albumindex], $obj->TTindex);
			} else {
				$albums[$obj->Albumindex][$obj->TrackNo] = $obj->TTindex;
			}
		}
	}
	shuffle($albums);
	foreach($albums as $a) {
		ksort($a);
		foreach ($a as $t) {
			generic_sql_query("INSERT INTO pltracktable (TTindex) VALUES (".$t.")");
			$count++;
		}
	}
}

print json_encode(array('total' => $count));

?>