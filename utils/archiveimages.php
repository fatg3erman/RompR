<?php

// Run this file from this folder
// php ./archiveimages.php

// Sadly I can't run this through the rompr GUI as the webserver does not have permission to write
// to your Music Folders.

// Make sure the correct path to your Music folders is set in Rompr's preferences menu.

// This will copy all the albumart rompr has downloaded for your Local Music (only) into the
// same folders as the music itself. It can then be automatically restored by rompr if you ever
// reinstall or move to another machine.

chdir('..');
include ("includes/vars.php");
$skin = 'desktop';
include ("includes/functions.php");
include ("backends/sql/backend.php");
$r = generic_sql_query(
	'SELECT
		Uri,
		Image,
		Albumname,
		Domain
	FROM Tracktable
	JOIN Albumtable USING (Albumindex)
	WHERE Domain = "local" AND Uri IS NOT NULL
	GROUP BY Albumindex', false, PDO::FETCH_OBJ);

foreach ($r as $obj) {
	if ($obj->Domain == 'local') {
		print "Album : ".$obj->Albumname."\n";
		$retval = dirname($obj->Uri);
		$retval = preg_replace('#^local:track:#', '', $retval);
		$retval = preg_replace('#^file://#', '', $retval);
		$retval = preg_replace('#^beetslocal:\d+:'.$prefs['music_directory_albumart'].'/#', '', $retval);

		if (is_dir('prefs/MusicFolders') && $retval != '.') {
			$albumpath = munge_filepath($retval);
			if (is_dir($albumpath)) {
				$img = 'albumart/asdownloaded/'.basename($obj->Image);
				$out = $albumpath.'/'.basename($obj->Image);
				print "Archiving Image ".$img.' to '.$out."\n";
				$filename = pathinfo($img, PATHINFO_FILENAME);
				$current = glob($albumpath.'/'.$filename.'.*');
				foreach ($current as $f) {
					print '  Removing '.$f."\n";
					unlink($f);
				}
				copy($img, $out);
			}
		}
	}
}

?>
