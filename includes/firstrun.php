<?php
$root_level_dirs = array(
	'prefs/imagecache',
	'prefs/podcasts',
	'prefs/jsoncache',
	'prefs/jsoncache/allmusic',
	'prefs/jsoncache/discogs',
	'prefs/jsoncache/musicbrainz',
	'prefs/jsoncache/wikipedia',
	'prefs/jsoncache/lastfm',
	'prefs/jsoncache/spotify',
	'prefs/jsoncache/lyrics',
	'prefs/jsoncache/google',
	'prefs/jsoncache/soundcloud',
	'prefs/userplaylists',
	'prefs/plimages',
	'prefs/userbackgrounds',
	'prefs/crazyplaylists',
	'prefs/databackups',
	'prefs/userstreams',
	'prefs/temp',
	'albumart/asdownloaded',
	'albumart/small',
	'albumart/medium'
);
foreach ($root_level_dirs as $dir) {
	if (!is_dir($dir)) {
		debuglog("Making Directory ".$dir,"INIT");
		mkdir($dir, 0744, true);
	}
}
$all = glob('prefs/*');
foreach ($all as $dir) {
	if (is_dir($dir) && !in_array($dir, $root_level_dirs) && basename($dir) != 'MusicFolders') {
		debuglog("Removing Directory ".$dir,"INIT");
		system('rm -fR "'.$dir.'"');
	}
}
if (file_exists('prefs/monitor.xml')) {
	unlink('prefs/monitor.xml');
}
if (file_exists('prefs/monitor')) {
	unlink('prefs/monitor');
}
?>
