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

// WARNING - DON'T use this unless your music is organised so each album is in its own directory

chdir('..');
include ("includes/vars.php");
include ("includes/functions.php");
prefs::$database = new image_archiver();
$r = prefs::$database->get_all_images();

const TEMP_DIR="/tmp/rompr";

$red = "\033[91m";
$green = "\033[92m";
$yellow = "\033[92m";
$cyan = "\033[92m";
$grey = "\033[37m";
$off = "\033[0m";

if (!is_dir(TEMP_DIR)) {
	mkdir(TEMP_DIR, 0755);
}
$embed = (count($argv) > 1 && $argv[1] == '--embed');

if (file_exists('donelist.txt')) {
	$donelist = file('donelist.txt');
} else {
	$donelist = [];
}

foreach ($r as $obj) {
	if ($obj->Domain == 'local') {
		print "\n\n\n";
		$nom = "$green Album : ".$obj->Artistname." ".$obj->Albumname." ".$obj->useTrackIms."$off\n";
		print $nom;
		if (in_array($nom, $donelist)) {
			print "$red .. already done this one$off\n";
			continue;
		}

		$retval = dirname($obj->Uri);
		$retval = preg_replace('#^local:track:#', '', $retval);
		$retval = preg_replace('#^file://#', '', $retval);
		$retval = preg_replace('#^beetslocal:\d+:'.prefs::get_pref('music_directory_albumart').'/#', '', $retval);

		if (is_dir('prefs/MusicFolders') && $retval != '.') {
			$albumpath = imageFunctions::munge_filepath($retval);
			print "$grey  Directory $albumpath $off\n";
			print "$grey  Image ".basename($obj->Image)."$off\n";
			if (is_dir($albumpath) && basename($obj->Image)) {
				$img = 'albumart/asdownloaded/'.basename($obj->Image);
				$ext = pathinfo($img, PATHINFO_EXTENSION);
				$out = $albumpath.'/cover.'.$ext;
				$filename = pathinfo($img, PATHINFO_FILENAME);
				$current = glob($albumpath.'/*.{jpg,png,bmp,gif,jpeg,webp,JPEG,JPG,BMP,GIF,PNG,WEBP}', GLOB_BRACE);
				foreach ($current as $f) {
					if (strlen(basename($f)) == 36) {
						print "$cyan    Removing $f $off\n";
						unlink($f);
					} else if (pathinfo($f, PATHINFO_FILENAME) == 'cover') {
						print "$cyan    Removing $f $off\n";
						unlink($f);
					}
				}
				print "$yellow   Archiving Image $img to $out $off\n";
				copy($img, $out);
				if ($embed && $obj->useTrackIms == 1) {
					print "$red  Album is configured to use Track Images, not embedding artwork$off\n";
				}
				if ($embed && $obj->useTrackIms == 0) {
					$mp3s = glob($albumpath.'/*.{mp3,m4a,mp4,flac}', GLOB_BRACE);
					foreach ($mp3s as $mp3) {
						$mext = pathinfo($mp3, PATHINFO_EXTENSION);
						$output = [];
						$exitcode = 0;

						$escmp3 = escapeshellarg($mp3);

						switch ($mext) {
							case 'mp3':
								print "$yellow  Adding image to MP3 file $mp3 $off\n";
								if ($ext != 'jpg') {
									$newfile = convert_file($out);
								} else {
									$newfile = $out;
								}
								# eyeD3 can complain about pathnames with unusual characters in it
								$copyto = TEMP_DIR.'/'.basename($newfile);
								if (copy($newfile, $copyto)) {
									print "$grey    Source Image is $newfile $off\n";
									print "$grey     (Copied to $copyto) $off\n";
									print "$grey    MP3          is $mp3 $off\n";
									exec("eyeD3 --remove-all-images $escmp3");
									exec("eyeD3 --add-image ".escapeshellarg("$copyto:FRONT_COVER")." $escmp3", $output, $exitcode);
								} else {
									print "$red File Copy Failed!$off\n";
									print "$red Copying from $newfile $off\n";
									print "$red To $copyto $off\n";
									exit(0);
								}
								if ($ext != 'jpg') {
									unlink($newfile);
								}
								break;

							case "m4a":
							case "mp4":
								print "$yellow  Adding image to MP4 file $mp3 $off\n";
								if ($ext != 'jpg' && $ext != 'png') {
									$newfile = convert_file($out);
								} else {
									$newfile = $out;
								}
								print "$grey    Image is $newfile $off\n";
								print "$grey    MP4   is $mp3 $off\n";
								exec("AtomicParsley $escmp3 --overWrite --artwork REMOVE_ALL");
								exec("AtomicParsley $escmp3 --overWrite --artwork ".escapeshellarg($newfile), $output, $exitcode);
								if ($ext != 'jpg' && $ext != 'png') {
									unlink($newfile);
								}
								break;

							case "flac":
								print "$yellow  Adding image to FLAC file $mp3 $off\n";
								if ($ext != 'jpg' && $ext != 'png') {
									$newfile = convert_file($out);
								} else {
									$newfile = $out;
								}
								print "$grey    Image is $newfile $off\n";
								print "$grey    MP4   is $mp3 $off\n";
								exec("metaflac --remove --block-type=PICTURE,PADDING --dont-use-padding $escmp3");
								exec("metaflac --remove-tag=COVERART  --dont-use-padding $escmp3");
								exec("metaflac --import-picture-from=".escapeshellarg("3||||$newfile")." $escmp3", $output, $exitcode);
								break;

						}
						if ($exitcode != 0) {
							print "$red Exti Code Was $exitcode $off\n";
							foreach ($output as $line) {
								print "$red $line $off\n";
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

function convert_file($out) {
	$newfile = pathinfo($out, PATHINFO_FILENAME).".jpg";
	print "$cyan    Converting file to ".$newfile."$off\n";
	exec("convert -quality 95 ".escapeshellarg($out)." ".escapeshellarg($newfile));
	return $newfile;
}
?>
