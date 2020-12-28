<?php
chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");

// For speed and memory usage we do two VERY IMPORTANT things
// 1. JSON encode each track one-by-one, print out the result, and then throw that data away
// 2. Don't use the Collection data models.
// This saves potentially hundreds of MB of RAM - json_encode is astonishingly inefficient,
// and the Collection data models use up a lot of memory for things we just don't need here

// This is intended to be a fast pipe to convert MPD data into RompR data.
$c = true;
header('Content-Type: application/json; charset=utf-8');
$dbterms = array( 'tags' => null, 'rating' => null );
$player = new player();
prefs::$database = new playlistCollection();
print '[';
foreach ($player->get_playlist() as $filedata) {
	$info = prefs::$database->doNewPlaylistFile($filedata);
	# Timing comparisons show that if ($c) is faster than if ($c == 0)
	if ($c) {
		$c = false;
	} else {
		print ', ';
	}
	print json_encode($info);
};
print ']';
ob_flush();
prefs::$database->close_database();
?>
