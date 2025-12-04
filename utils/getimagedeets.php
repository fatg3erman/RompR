<?php
chdir('..');
include ("includes/vars.php");
include ("includes/functions.php");
prefs::$database = new collection_base();
$who = $_REQUEST['who'];

$retval = [
	'phrase' => 'Unknown Album',
	'path' => '.'
];

$deets = prefs::$database->get_album_details($who);
$path = prefs::$database->get_album_directory($who, $deets['AlbumUri']);

$retval['phrase'] = $deets['Artistname'].' '.$deets['Albumname'];
if ($path) {
	$retval['path'] = $path;
}

header('Content-Type: application/json; charset=utf-8');
print json_encode($retval);

?>
