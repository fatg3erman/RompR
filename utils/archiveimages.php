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
if (!is_dir(TEMP_DIR)) {
	mkdir(TEMP_DIR, 0755);
}

const BLACK="0;30";
const RED="0;31";
const GREEN="1;32";
const BROWN="0;33";
const YELLOW="1;33";
const BLUE="1;34";
const MAGENTA="1;35";
const CYAN="0;36";
const GREY="0;37";
const WHITE="1;37";
const HIGHLIGHT="1;33;45";

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
		log_out(HIGHLIGHT, $nom);
		if (in_array($nom, $donelist)) {
			log_out(RED, " .. already done this one");
			continue;
		}

		$retval = dirname($obj->Uri);
		$retval = preg_replace('#^local:track:#', '', $retval);
		$retval = preg_replace('#^file://#', '', $retval);
		$retval = preg_replace('#^beetslocal:\d+:'.prefs::get_pref('music_directory_albumart').'/#', '', $retval);

		if (is_dir('prefs/MusicFolders') && $retval != '.') {
			$albumpath = imageFunctions::munge_filepath($retval);
			log_out(GREY, "  Directory $albumpath");
			log_out(GREY, "  Image ".basename($obj->Image));
			if (is_dir($albumpath) && basename($obj->Image)) {
				$img = 'albumart/asdownloaded/'.basename($obj->Image);
				$ext = pathinfo($img, PATHINFO_EXTENSION);
				$out = $albumpath.'/cover.'.$ext;
				$filename = pathinfo($img, PATHINFO_FILENAME);
				$current = glob($albumpath.'/*.{jpg,png,bmp,gif,jpeg,webp,JPEG,JPG,BMP,GIF,PNG,WEBP}', GLOB_BRACE);
				foreach ($current as $f) {
					if (strlen(basename($f)) == 36) {
						log_out(CYAN, "    Removing $f");
						unlink($f);
					} else if (pathinfo($f, PATHINFO_FILENAME) == 'cover') {
						log_out(CYAN, "    Removing $f");
						unlink($f);
					}
				}
				log_out(YELLOW,  "  Archiving Image $img to $out");
				copy($img, $out);
				if ($embed && $obj->useTrackIms == 1) {
					log_out(RED, "  Album is configured to use Track Images, not embedding artwork");
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
								log_out(YELLOW, "  Adding image to MP3 file $mp3");
								if ($ext != 'jpg') {
									$newfile = convert_file($out);
								} else {
									# eyeD3 can complain about pathnames with unusual characters in it
									$newfile = TEMP_DIR.'/'.md5($out).'.'.pathinfo($out, PATHINFO_EXTENSION);
									if (copy($out, $newfile)) {
										log_out(MAGENTA, "    Copied image to $copyto");
									} else {
										log_out(RED, "File Copy Failed!");
										log_out(RED, " Copying from $newfile");
										log_out(RED, " To           $copyto");
										exit(0);
									}
								}
								log_out(GREY, "    Source Image is $newfile");
								log_out(GREY, "    MP3          is $mp3");
								exec("eyeD3 --remove-all-images $escmp3");
								exec("eyeD3 --add-image ".escapeshellarg("$newfile:FRONT_COVER")." $escmp3", $output, $exitcode);
								unlink($newfile);
								break;

							case "m4a":
							case "mp4":
								log_out(YELLOW, "  Adding image to MP4 file $mp3");
								if ($ext != 'jpg' && $ext != 'png') {
									$newfile = convert_file($out);
								} else {
									$newfile = $out;
								}
								log_out(GREY, "    Source Image is $newfile");
								log_out(GREY, "    MP4          is $mp3");
								exec("AtomicParsley $escmp3 --overWrite --artwork REMOVE_ALL");
								exec("AtomicParsley $escmp3 --overWrite --artwork ".escapeshellarg($newfile), $output, $exitcode);
								if ($ext != 'jpg' && $ext != 'png') {
									unlink($newfile);
								}
								break;

							case "flac":
								log_out(YELLOW, "  Adding image to FLAC file $mp3");
								if ($ext != 'jpg' && $ext != 'png') {
									$newfile = convert_file($out);
								} else {
									$newfile = $out;
								}
								log_out(GREY, "    Source Image is $newfile");
								log_out(GREY, "    FLAC         is $mp3");
								exec("metaflac --remove --block-type=PICTURE,PADDING --dont-use-padding $escmp3");
								exec("metaflac --remove-tag=COVERART  --dont-use-padding $escmp3");
								exec("metaflac --import-picture-from=".escapeshellarg("3||||$newfile")." $escmp3", $output, $exitcode);
								break;

						}
						if ($exitcode != 0) {
							log_out(RED, "Exti Code Was $exitcode");
							foreach ($output as $line) {
								log_out(RED, $line);
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

function log_out($col, $text) {
	print "\e[".$col."m".trim($text, "\n")."\e[0m\n";
}

function convert_file($out) {
	$newfile = TEMP_DIR . '/' . pathinfo($out, PATHINFO_FILENAME).".jpg";
	log_out(MAGENTA, "    Converting $out to ".$newfile);
	if (is_file($newfile)) {
		unlink($newfile);
	}
	exec("convert -quality 95 ".escapeshellarg($out)." ".escapeshellarg($newfile));
	return $newfile;
}
?>
