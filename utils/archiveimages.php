<?php

// Run this file from this folder
// php ./archiveimages.php

// Sadly I can't run this through the rompr GUI as the webserver does not have permission to write
// to your Music Folders.

// Make sure the correct path to your Music folders is set in Rompr's preferences menu.

// This will copy all the albumart rompr has downloaded for your Local Music (only) into the
// same folders as the music itself. It can then be automatically restored by rompr if you ever
// reinstall or move to another machine.

// WARNING! This will REMOVE images in the albums folders if those images are called 'cover.jpg' (or png or whatever)
// of if they've been archived there by an earlier version of this script. This script will copy the images
// as 'cover.jpg' (or png or whatever)
// Add --embed to the command line to embed the artwork into your files
// You must have the following installed to embed images:
// eyed3 (for MP3 files)
// metaflac (for FLAC files) (often the package name is just flac)
// atomicparsley (for M4A/MP4) files
// Note that atomicparsley only supports jpg and png files

chdir('..');
include ("includes/vars.php");
include ("includes/functions.php");
prefs::$database = new image_archiver();
$r = prefs::$database->get_all_images();

$embed = (count($argv) > 1 && $argv[1] == '--embed');

if (file_exists('donelist.txt')) {
	$donelist = file('donelist.txt');
} else {
	$donelist = [];
}

foreach ($r as $obj) {
	if ($obj->Domain == 'local') {
		print "\n\n\n";
		$nom = "Album : ".$obj->Artistname." ".$obj->Albumname." ".$obj->useTrackIms."\n";
		print $nom;
		if (in_array($nom, $donelist)) {
			print " .. already done this one\n";
			continue;
		}

		$retval = dirname($obj->Uri);
		$retval = preg_replace('#^local:track:#', '', $retval);
		$retval = preg_replace('#^file://#', '', $retval);
		$retval = preg_replace('#^beetslocal:\d+:'.prefs::get_pref('music_directory_albumart').'/#', '', $retval);

		if (is_dir('prefs/MusicFolders') && $retval != '.') {
			$albumpath = imageFunctions::munge_filepath($retval);
			print " Directory $albumpath\n";
			print " ".basename($obj->Image)."\n";
			if (is_dir($albumpath) && basename($obj->Image)) {
				$img = 'albumart/asdownloaded/'.basename($obj->Image);
				$ext = pathinfo($img, PATHINFO_EXTENSION);
				$out = $albumpath.'/cover.'.$ext;
				$filename = pathinfo($img, PATHINFO_FILENAME);
				$current = glob($albumpath.'/*.{jpg,png,bmp,gif,jpeg,webp,JPEG,JPG,BMP,GIF,PNG,WEBP}', GLOB_BRACE);
				foreach ($current as $f) {
					if (strlen(basename($f)) == 36) {
						print '  Removing '.$f."\n";
						unlink($f);
					} else if (pathinfo($f, PATHINFO_FILENAME) == 'cover') {
						print '  Removing '.$f."\n";
						unlink($f);
					}
				}
				print "Archiving Image ".$img.' to '.$out."\n";
				copy($img, $out);
				if ($embed && $obj->useTrackIms == 1) {
					print "Album is configured to use Track Images, not embedding artwork\n";
				}
				if ($embed && $obj->useTrackIms == 0) {
					$mp3s = glob($albumpath.'/*.{mp3,m4a,mp4,flac}', GLOB_BRACE);
					foreach ($mp3s as $mp3) {
						$mext = pathinfo($mp3, PATHINFO_EXTENSION);
						$output = [];
						$exitcode = 0;
						switch ($mext) {
							case 'mp3':
								print "Adding image to MP3 file $mp3\n";
								print " Image is $out\n";
								print " MP3   is $mp3\n";
								exec("eyeD3 --remove-all-images \"$mp3\"");
								exec("eyeD3 --add-image \"$out:FRONT_COVER\" \"$mp3\"", $output, $exitcode);
								break;

							case "m4a":
							case "mp4":
								if ($ext == 'jpg' || $ext == 'png') {
									print "Adding image to MP4 file $mp3\n";
									print " Image is $out\n";
									print " MP4   is $mp3\n";
									exec("AtomicParsley \"$mp3\" --overWrite --artwork REMOVE_ALL");
									exec("AtomicParsley \"$mp3\" --overWrite --artwork \"$out\"", $output, $exitcode);
								} else {
									print "Can't add $out to MP4 file $mp3\n";
								}
								break;

							case "flac":
								print "Adding image to FLAC file\n";
								print " Image is $out\n";
								print " FLAC  is $mp3\n";
								exec("metaflac --remove --block-type=PICTURE,PADDING --dont-use-padding \"$mp3\"");
								exec("metaflac --remove-tag=COVERART  --dont-use-padding \"$mp3\"");
								exec("metaflac --import-picture-from=\"3||||$out\"  \"$mp3\"", $output, $exitcode);
								break;

						}
						if ($exitcode != 0) {
							print "Exti Code Was $exitcode\n";
							foreach ($output as $line) {
								print "$line\n";
							}
							exit(0);
						}
					}
				}
			}
		}
		file_put_contents('donelist.txt', $nom, FILE_APPEND);
	}
}

if (file_exists('donelist.txt')) {
	unlink('donelist.txt');
}
?>
