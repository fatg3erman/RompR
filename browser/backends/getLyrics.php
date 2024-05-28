<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("getid3/getid3.php");

$r = json_decode(file_get_contents('php://input'), true);

$fname = rawurldecode($r['file']);
$fname = preg_replace('/local:track:/','',$fname);
$fname = preg_replace('#file://#','',$fname);
$fname = 'prefs/MusicFolders/'.$fname;
$artist = $r['artist'];
$song = $r['song'];

$getID3 = new getID3;
$output = null;
logger::mark("LYRICS", "Looking for lyrics in",$fname);
logger::log("LYRICS", "  Artist is",$artist);
logger::log("LYRICS", "  Song is",$artist);

if (file_exists($fname)) {
	logger::log("LYRICS", "    File Exists");
	$tags = $getID3->analyze($fname);
	getid3_lib::CopyTagsToComments($tags);

	if (array_key_exists('comments', $tags) &&
			array_key_exists('lyrics', $tags['comments'])) {
		$output = $tags['comments']['lyrics'][0];
	} else if (array_key_exists('comments', $tags) &&
				array_key_exists('unsynchronised_lyric', $tags['comments'])) {
		$output = $tags['comments']['unsynchronised_lyric'][0];
	} else if (array_key_exists('quicktime', $tags) &&
				array_key_exists('moov', $tags['quicktime']) &&
				array_key_exists('subatoms', $tags['quicktime']['moov'])) {
		read_apple_awfulness($tags['quicktime']['moov']['subatoms']);
	}
}

if ($output == null) {
	logger::info("LYRICS", "Could not get lyrics from file");
	$output = '<h3 align=center>'.language::gettext("lyrics_nonefound").'</h3><p>'.language::gettext("lyrics_info").'</p>';
}

print $output;

function read_apple_awfulness($a) {
	// Whoever came up with this was on something.
	// All we want to do is read some metadata...
	// why do you have to store it in such a horrible, horrible, way?
	global $output;
	foreach ($a as $atom) {
		if (array_key_exists('name', $atom)) {
			if (preg_match('/lyr$/', $atom['name'])) {
				$output = preg_replace( '/^.*?data/', '', $atom['data']);
				break;
			}
		}
		if (array_key_exists('subatoms', $atom)) {
			read_apple_awfulness($atom['subatoms']);
		}
	}
}

?>
