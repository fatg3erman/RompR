<?php

// Quick & dirty hack to fix up the translations. Should be run before every release

// This is a list of keys I think are needed
// but that the regexps will not pick up
// becvause they're created progamatically
$in_use = array(
	'label_discogs',
	'label_musicbrainz',
	'label_wikipedia',
	'info_disctitle',
	'info_encoder',
	'label_notrackinfo',
	'label_noartistinfo',
	'label_noalbuminfo',
	'discogs_videos',
	'label_tags',
	'label_lastfm_mix_7day',
	'label_lastfm_mix_1month',
	'label_lastfm_mix_12month',
	'label_lastfm_mix_overall',
	'label_lastfm_dip_7day',
	'label_lastfm_dip_1month',
	'label_lastfm_dip_12month',
	'label_lastfm_dip_overall'
);

$langnames = array();
$languages = array();
$longest = 0;
include ('international/en.php');
$everything = array(".");
while (count($everything) > 0) {
	$dir = array_shift($everything);
	print 'Scanning '.$dir."/*\n";
	$contents = glob($dir.'/*');
	foreach ($contents as $thing) {
		if (is_dir($thing)) {
			if ($thing != './prefs' && $thing != './albumart') {
				array_push($everything, $thing);
			}
		} else {
			analyze_file($thing);
		}
	}
}

$in_use = array_unique($in_use);
foreach ($languages['en'] as $key => $v) {
	if (!in_array($key, $in_use)) {
		print 'Found unused language key '.$key."\n";
		check_unused_key($key);
	}
}

$unused = array();
$in_use = array_unique($in_use);
foreach ($languages['en'] as $key => $v) {
	if (!in_array($key, $in_use)) {
		print 'Unused key '.$key."\n";
		array_push($unused, $key);
		if (strlen($key)+2 > $longest) {
			$longest = strlen($key)+2;
		}
	}
}

$infile = 'international/en.php';
print '** Updating '.$infile." **\n";
$outfile = array();
$comments = array(
	"\t// ----------------------------------------------------".PHP_EOL,
	"\t// Proobaly Unused".PHP_EOL,
	"\t// ----------------------------------------------------".PHP_EOL
);
foreach (file($infile) as $line) {
	if (preg_match('/^\s+[\'|\"](.+?)[\'|\"]\s+=\>\s+[\'|\"](.+?)[\'|\"]\s*,*\s*$/', $line, $matches)) {
		if (in_array($matches[1], $unused)) {
			$comments[] = "\t//\"".$matches[1]."\" =>".calc_required_tabs('//'.$matches[1])."\"".$matches[2]."\",".PHP_EOL;
		} else {
			$outfile[] = "\t\"".$matches[1]."\" =>".calc_required_tabs($matches[1])."\"".$matches[2]."\",".PHP_EOL;
		}
	} else if (preg_match('/^\);\s*$/', $line, $matches)) {
		foreach ($comments as $c) {
			$outfile[] = $c;
		}
		$outfile[] = $line;
	} else {
		$outfile[] = $line;
	}
}
file_put_contents('international/en.php', implode('', $outfile));

$otherlangs = glob('international/*.php');
foreach ($otherlangs as $lang) {
	if ($lang != 'international/en.php' && !preg_match('/test\.php/', $lang)) {
		print '** Updating '.$lang." **\n";
		include ($lang);
		$langnom = pathinfo($lang, PATHINFO_FILENAME);
		$outfile = array();
		$comments = array(
			"\t// ----------------------------------------------------".PHP_EOL,
			"\t// Proobaly Unused".PHP_EOL,
			"\t// ----------------------------------------------------".PHP_EOL
		);
		foreach (file($lang) as $line) {
			if (preg_match('/^\s+[\'|\"](.+?)[\'|\"]\s+=\>\s+[\'|\"](.+?)[\'|\"]\s*,*\s*$/', $line, $matches)) {
				if (in_array($matches[1], $unused)) {
					$comments[] = "\t//\"".$matches[1]."\" =>".calc_required_tabs('//'.$matches[1])."\"".$matches[2]."\",".PHP_EOL;
				} else {
					$outfile[] = "\t\"".$matches[1]."\" =>".calc_required_tabs($matches[1])."\"".$matches[2]."\",".PHP_EOL;
				}
			} else if (preg_match('/^\);\s*$/', $line, $matches)) {
				foreach ($comments as $c) {
					$outfile[] = $c;
				}
				$outfile[] = "\n";
				$outfile[] = "\t// ----------------------------------------------------".PHP_EOL;
				$outfile[] = "\t// Missing Translations".PHP_EOL;
				$outfile[] = "\t// ----------------------------------------------------".PHP_EOL;
				foreach ($languages['en'] as $key => $value) {
					if (!array_key_exists($key, $languages[$langnom])) {
						if (is_array($value)) {
							$newvalue = "array(";
							foreach ($value as $arse) {
								$newvalue .= '"'.$arse.'", ';
							}
							$newvalue = trim($newvalue);
							$newvalue = trim($newvalue, ',');
							$value = $newvalue.')';
							$outfile[] = "\t//\"".$key."\" =>".calc_required_tabs('//'.$key)."".$value.",".PHP_EOL;
						} else {
							$outfile[] = "\t//\"".$key."\" =>".calc_required_tabs('//'.$key)."\"".$value."\",".PHP_EOL;
						}
					}
				}
				$outfile[] = $line;
			} else {
				$outfile[] = $line;
			}
		}
		file_put_contents('international/'.$langnom.'.php', implode('', $outfile));
	}
}

function calc_required_tabs($string) {
	global $longest;
	$lengthdiff = $longest - strlen($string);
	if ($lengthdiff <= 0) {
		$lengthdiff = 4;
	}
	$numtabs = 1+ceil($lengthdiff/4);
	return str_repeat("\t", $numtabs);
}

function analyze_file($thing) {
	global $in_use, $longest;
	if (pathinfo($thing, PATHINFO_EXTENSION) == 'php' || pathinfo($thing, PATHINFO_EXTENSION) == 'js') {
		print "  ".$thing."\n";
		foreach (file($thing) as $line) {
			if (preg_match_all('/language::gettext\([\'|\"](.+?)[\'|\"]/', $line, $matches)) {
				foreach ($matches[1] as $match) {
					print '    Found '.$match."\n";
					array_push($in_use, $match);
					if (strlen($match) > $longest) {
						$longest = strlen($match);
					}
				}
			} else if (preg_match_all('/language\.gettext\([\'|\"](.+?)[\'|\"]/', $line, $matches)) {
				foreach ($matches[1] as $match) {
					print '    Found '.$match."\n";
					array_push($in_use, $match);
					if (strlen($match) > $longest) {
						$longest = strlen($match);
					}
				}
			}
		}
	}
}

function check_unused_key($key) {
	$everything = array(".");
	while (count($everything) > 0) {
		$dir = array_shift($everything);
		$contents = glob($dir.'/*');
		foreach ($contents as $thing) {
			if (is_dir($thing)) {
				if ($thing != './prefs' && $thing != './albumart') {
					array_push($everything, $thing);
				}
			} else {
				analyze_file_more_deeply($thing, $key);
			}
		}
	}
}

function analyze_file_more_deeply($thing, $key) {
	global $in_use, $longest;
	if (basename(dirname($thing)) != 'international' &&
		(pathinfo($thing, PATHINFO_EXTENSION) == 'php' || pathinfo($thing, PATHINFO_EXTENSION) == 'js')) {
		foreach (file($thing) as $num => $line) {
			if (preg_match('/[\'|\"]'.$key.'[\'|\"]/', $line)) {
				print '  Key appears to be in use in '.$thing.' at line '.($num+1)."\n";
				array_push($in_use, $key);
				if (strlen($key) > $longest) {
					$longest = strlen($key);
				}
				return true;
			} else if (preg_match('/\s'.$key.':/', $line)) {
				print '  Key appears to be in use in '.$thing.' at line '.($num+1)."\n";
				array_push($in_use, $key);
				return true;
				if (strlen($key) > $longest) {
					$longest = strlen($key);
				}
			}
		}
	}
	return false;
}


?>