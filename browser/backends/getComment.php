<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("getid3/getid3.php");
$s = json_decode(file_get_contents('php://input'), true);
$fname = rawurldecode($s['file']);
$fname = preg_replace('/local:track:/','',$fname);
$fname = preg_replace('#file://#','',$fname);
$fname = 'prefs/MusicFolders/'.$fname;

$getID3 = new getID3;
$output = null;
logger::mark("COMMENT", "Looking for comment in",$fname);

if (file_exists($fname)) {
	logger::log("COMMENT", "    File Exists");
	$tags = $getID3->analyze($fname);
	getid3_lib::CopyTagsToComments($tags);

	if (array_key_exists('comments', $tags) &&
		array_key_exists('comment', $tags['comments']) &&
		is_array($tags['comments']['comment']) &&
		array_key_exists(0, $tags['comments']['comment'])
	) {
		$output = $tags['comments']['comment'][0];
	}
}

if ($output == null) {
	$output = "NOCOMMENT";
}

print $output;
?>