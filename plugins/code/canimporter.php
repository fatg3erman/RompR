<?php

// Quick and dirty Cantata ratings importer to shut people up.

chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");

$player = new player();
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

prefs::$database = new cantata_importer();
prefs::$database->cantata_import();

function import_cantata_track() {
	global $player;
	foreach ($player->parse_list_output('sticker find song "" rating', $dirs, false) as $filedata) {
		print 'Some data...';
		yield $filedata;
	}
}

?>