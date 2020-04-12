<?php

// Quick and dirty Cantata ratings importer to shut people up.

chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
require_once ("international.php");
require_once ("backends/sql/backend.php");
require_once ("backends/sql/metadatafunctions.php");
require_once ("player/".$prefs['player_backend']."/player.php");

$player = new $PLAYER_TYPE();
if (file_exists('prefs/canmonitor')) {
	unlink('prefs/canmonitor');
}

$commands = $player->get_commands();
if (!in_array('sticker', $commands['command'])) {
	logger::log('CANIMPORTER', 'Player does not support Sticker Command');
	$output = array('done' => 1, 'total' => 1, 'message' => 'Stickers not supported by your player');
	file_put_contents('prefs/canmonitor', json_encode($output));
	header('HTTP/1.1 204 No Content');
	exit(0);
}

$output = array('done' => 0, 'total' => 1, 'message' => 'Preparing....');
file_put_contents('prefs/canmonitor', json_encode($output));

$dirs = array();
$total = 0;
foreach ($player->parse_list_output('sticker find song "" rating', $dirs, false) as $f) {
	$total++;
}

logger::log('CANIMPORTER', 'There are',$total,'rated tracks');
if ($total == 0) {
	$output = array('done' => 1, 'total' => 1, 'message' => 'No Rated Tracks Found');
	file_put_contents('prefs/canmonitor', json_encode($output));
	header('HTTP/1.1 204 No Content');
	exit(0);
}

$count = 0;
open_transaction();
foreach ($player->parse_list_output('sticker find song "" rating', $dirs, false) as $filedata) {
	$uri = $filedata['file'];
	$cockend = $filedata['sticker'];
	$bellend = explode('=', $cockend);
	$rating = ceil($bellend[1]/2);
	logger::log('CANIMPORTER', 'Uri :',$uri,', Rating',$rating);

	$ttindex = simple_query('TTindex', 'Tracktable', 'Uri', $uri, null);
	if ($ttindex) {
		logger::log('CANIMPORTER','  TTindex is',$ttindex);
		sql_prepare_query(true, null, null, null,
			"REPLACE INTO Ratingtable (TTindex, Rating) VALUES (?, ?)",
			$ttindex,
			$rating
		);
		$numdone++;
		check_transaction();
	} else {
		logger::log('CANIMPORTER', '  Could not find TTindex');
	}

	$count++;
	$output = array('done' => $count, 'total' => $total, 'message' => 'Done '.$count.' of '.$total);
	file_put_contents('prefs/canmonitor', json_encode($output));
	print 'Some data...';
}
close_transaction();


?>