<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");
include ("getid3/getid3.php");

$fname = $_POST['file'];
$fname = preg_replace('/local:track:/','',$fname);
$fname = preg_replace('#file://#','',$fname);
$fname = 'prefs/MusicFolders/'.$fname;
$artist = $_POST['artist'];
$song = $_POST['song'];

$getID3 = new getID3;
$output = null;
debuglog("Looking for lyrics in ".$fname,"LYRICS");
debuglog("  Artist is ".$artist,"LYRICS");
debuglog("  Song is ".$artist,"LYRICS");

if (file_exists($fname)) {
	debuglog("File Exists ".$fname,"LYRICS");
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
	$uri = "http://lyrics.wikia.com/api.php?func=getSong&artist=".urlencode($artist)."&song=".urlencode($song)."&fmt=xml";
	debuglog("Trying ".$uri,"LYRICS");
	$d = new url_downloader(array(
		'url' => $uri,
		'cache' => 'lyrics',
		'return_data' => true
	));
	if ($d->get_data_to_file()) {
		$l = simplexml_load_string($d->get_data());
		if ($l->url) {
			debuglog("  Now Getting ".html_entity_decode($l->url),"LYRICS");
			$d2 = new url_downloader(array(
				'url' => html_entity_decode($l->url),
				'cache' => 'lyrics',
				'return_data' => true
			));
			if ($d2->get_data_to_file()) {
				if (preg_match('/\<div class=\'lyricbox\'\>\<script\>.*?\<\/script\>(.*?)\<\!--/', $d2->get_data(), $matches)) {
					$output = html_entity_decode($matches[1]);
				} else if (preg_match('/\<div class=\'lyricbox\'\>(.*?)\<div class=\'lyricsbreak\'\>/', $d2->get_data(), $matches)) {
					$output = html_entity_decode($matches[1]);
				} else {
					debuglog("    Could Not Find Lyrics","LYRICS");
				}
			}
		} else {
			debuglog("  Nope, nothing there","LYRICS");
		}
	}
} else {
	debuglog("  Got lyrics from file","LYRICS");
}

if ($output == null) {
	$output = '<h3 align=center>'.get_int_text("lyrics_nonefound").'</h3><p>'.get_int_text("lyrics_info").'</p>';
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
