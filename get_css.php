<?php

/*
Different browsers interpret relative paths in css variables differently.
If your variable is eg
	--my-background: url(../path/to/image);
Chrome interprets that as relative to the file in which it is referenced.
Safari interprets it as relative to the file in which it is defined.
Consistency would be helpful but co-operation is a dream we long gave up on, apparently.
RompR might be installed in any number of ways, so using /full/path/to/image doesn't
work either, since that would ignore the /rompr/ part of the URL, and we can't assume
everybody has installed it like that.
So what we're doing here is jumping through some hoops so that all of our CSS is pulled
relative to the root of the website, by reading it all in using php.
*/

require_once ("includes/vars.php");
header('Content-Type: text/css');
$css = glob('css/*.css');
foreach ($css as $file) {
	logger::log('GET-CSS', $file);
	readfile($file);
	print "\n";
}
if (file_exists('skins/'.prefs::skin().'/skin.css')) {
	logger::log('GET-CSS', 'skins/'.prefs::skin().'/skin.css');
	readfile('skins/'.prefs::skin().'/skin.css');
	print "\n";
}
if (file_exists('skins/'.prefs::skin().'/controlbuttons.css')) {
	logger::log('GET-CSS', 'skins/'.prefs::skin().'/controlbuttons.css');
	readfile('skins/'.prefs::skin().'/controlbuttons.css');
	print "\n";
}

if (file_exists('skins/'.prefs::skin().'/skin.requires')) {
	logger::log('GET-CSS', 'Reading Skin Requirements File');
	$requires = file('skins/'.prefs::skin().'/skin.requires');
	foreach ($requires as $s) {
		$s = trim($s);
		if (substr($s,0,1) != '#') {
			$ext = strtolower(pathinfo($s, PATHINFO_EXTENSION));
			if ($ext == "css") {
				logger::log('GET-CSS', $s);
				readfile($s);
				print "\n";
			}
		}
	}
}
?>