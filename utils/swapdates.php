<?php

# Scan your music folders recursively and exchange the date tag for the original release date tag.
# Preserve the date tag in release date tag.
# Works with MP3 and FLAC files only.

chdir('..');

const START_DIR = '/mnt/Music/Music';

$dirs = [START_DIR];

const BLACK="0;30";
const RED="0;31";
const GREEN="1;32";
const BROWN="0;33";
const YELLOW="1;33";
const BLUE="1;34";
const MAGENTA="0;35";
const CYAN="0;36";
const GREY="0;37";
const WHITE="1;37";
const HIGHLIGHT="1;33;45";

while (count($dirs) > 0) {
	$dir = array_shift($dirs);
	print("\n");
	format_out(BLUE, $dir);
	$files = glob($dir.'/*');
	foreach ($files as $file) {
		if (is_dir($file)) {
			$dirs[] = $file;
		} else {
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			if ($ext == 'mp3') {
			// if ($ext == 'mp3' || $ext == 'flac') {
				format_out(HIGHLIGHT, str_repeat('-', strlen($file)+2));
				format_out(HIGHLIGHT, " $file");
				format_out(HIGHLIGHT, str_repeat('-', strlen($file)+2));
				switch ($ext) {
					case 'mp3':
						list($date, $releasedate) = parse_mp3_tags($file);
						break;

					case 'flac':
						list($date, $releasedate) = parse_flac_tags($file);
						break;
				}

				format_out(GREEN, "  Original Date = $date");
				format_out(GREEN, "  Release Date  = $releasedate");

				if ($date == null || $date == $releasedate || compare_years($date, $releasedate)) {
					format_out(RED, "  Nothing to change here");
				} else {
					switch ($ext) {
						case 'mp3':
							write_mp3_tags($file, $date, $releasedate);
							break;

						case 'flac':
							write_flac_tags($file, $date, $releasedate);
							break;
					}
				}
			}
		}
	}
}

function write_flac_tags($file, $date, $releasedate) {
	$fn = escapeshellarg($file);
	exec("metaflac --remove-tag=DATE --dont-use-padding $fn", $output, $exitcode);
	if ($exitcode == 0) {
		format_out(CYAN, "  Setting DATE to $date");
		exec("metaflac --set-tag=\"DATE=$date\" --dont-use-padding $fn", $output, $exitcode);
	}
	if ($releasedate && $exitcode == 0) {
		exec("metaflac --remove-tag=RELEASEDATE --dont-use-padding $fn", $output, $exitcode);
	}
	if ($releasedate && $exitcode == 0) {
		format_out(CYAN, "  Setting RELEASEDATE to $releasedate");
		exec("metaflac --set-tag=\"RELEASEDATE=$releasedate\" --dont-use-padding $fn", $output, $exitcode);
	}
	if ($exitcode != 0) {
		show_error($output);
	}
}

function write_mp3_tags($file, $date, $releasedate) {
	$fn = escapeshellarg($file);
	format_out(CYAN, "  Setting DATE to $date");
	exec("eyeD3 --recording-date=$date $fn", $output, $exitcode);
	if ($releasedate && $exitcode == 0) {
		format_out(CYAN, "  Setting RELEASEDATE to $releasedate");
		exec("eyeD3 --release-date=$releasedate $fn", $output, $exitcode);
	}
	if ($exitcode != 0) {
		show_error($output);
	}
}

function show_error($output) {
	foreach ($output as $line) {
		format_out(RED, $line);
	}
	exit(0);
}

function compare_years($d1, $d2) {
	$da = explode('-', $d1);
	$db = explode('-', $d2);
	if ($da[0] == $db[0]) {
		format_out(GREY, "    Years are identical - ".$da[0]);
		return true;
	}
	return false;
}

function parse_mp3_tags($file) {
	$date = null;
	$releasedate = null;
	$originalyear = null;
	$fn = escapeshellarg($file);
	exec("eyeD3 $fn", $output);
	$oy = false;
	foreach ($output as $line) {
		if ($oy) {
			$oy = false;
			$originalyear = trim($line);
			format_out(GREY, "    Current ORIGINALYEAR tag = $originalyear");
		} else if (preg_match('/original release date: (\d+)/', $line, $matches)) {
			$date = $matches[1];
			format_out(GREY, "    Current ORIGINALDATE tag = $date");
		} else if (preg_match('/recording date: (\d+)/', $line, $matches)) {
			$releasedate = $matches[1];
			format_out(GREY, "    Current DATE tag = $releasedate");
		} else if (strpos($line, 'Description: originalyear')) {
			$oy = true;
		}
	}
	if ($date === null) {
		$date = $originalyear;
	}
	return [$date, $releasedate];
}

function parse_flac_tags($file) {
	$date = null;
	$releasedate = null;
	$fn = escapeshellarg($file);
	$output = [];
	exec("metaflac --show-tag=DATE $fn", $output);
	$s = explode('=', $output[0]);
	if (count($s) == 2 && $s[0] == 'DATE') {
		$releasedate = $s[1];
		format_out(GREY, "    Current DATE tag = $releasedate");
	}
	$output = [];
	exec("metaflac --show-tag=ORIGINALDATE $fn", $output);
	$s = explode('=', $output[0]);
	if (count($s) == 2 && $s[0] == 'ORIGINALDATE') {
		$date = $s[1];
		format_out(GREY, "    Current ORIGINALDATE tag = $date");
	}
	if ($date == null) {
		$output = [];
		exec("metaflac --show-tag=ORIGINALYEAR $fn", $output);
		$s = explode('=', $output[0]);
		if (count($s) == 2 && $s[0] == 'ORIGINALYEAR') {
			$date = $s[1];
			format_out(GREY, "    Current ORIGINALYEAR tag = $date");
		}
	}
	return [$date, $releasedate];
}

function format_out($col, $text) {
	print "\e[".$col."m".$text."\e[0m\n";
}

?>