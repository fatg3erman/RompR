<?php
chdir('..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");
include ("backends/sql/backend.php");
include ("backends/sql/metadatafunctions.php");

logger::log("FAVEALBUMS", "Populating Favourite Album Radio");

preparePlaylist();
preparePlTrackTable();

generic_sql_query("CREATE TEMPORARY TABLE alplaytable AS
	SELECT SUM(Playcount) AS playtotal,
	Albumindex
	FROM (SELECT Playcount, Albumindex FROM Playcounttable JOIN Tracktable USING (TTindex)
	WHERE Playcount > 3) AS derived GROUP BY Albumindex ORDER BY ".SQL_RANDOM_SORT, true);
// This rather cumbersome code gives us albums in a random order but tracks in order.
// All attempts to do this with a single SQL query hit a brick wall.
$albums = array();
$avgplays = generic_sql_query("SELECT AVG(playtotal) AS plavg FROM alplaytable", false, null, 'plavg', 0);

$qstring = "SELECT TTindex, TrackNo, Albumindex FROM Tracktable JOIN alplaytable
	USING (Albumindex) WHERE playtotal > ".$avgplays." AND Uri IS NOT NULL AND Hidden = 0 AND isAudiobook = 0";

if ($prefs['collection_player'] == 'mopidy' && $prefs['player_backend'] == 'mpd') {
	$qstring .= ' AND Uri LIKE "local:%"';
}

$result = generic_sql_query($qstring, false, PDO::FETCH_OBJ);
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
$count = 0;
foreach($albums as $a) {
	ksort($a);
	foreach ($a as $t) {
		generic_sql_query("INSERT INTO pltracktable (TTindex) VALUES (".$t.")");
		$count++;
	}
}

print json_encode(array('total' => $count));

?>