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

require_once ('includes/vars.php');
header('Content-Type: text/css');
$fontfamily = array_key_exists('fontfamily', $_REQUEST) ? $_REQUEST['fontfamily'] : 'Nunito.css';
$theme = array_key_exists('theme', $_REQUEST) ? $_REQUEST['theme'] : 'Numismatist.css';
$icontheme = array_key_exists('icontheme', $_REQUEST) ? $_REQUEST['icontheme'] : 'New-Dark-Circled';
// For older-style requests before we could have spaces
$fontfamily = preg_replace('/_/', ' ', $fontfamily);
logger::log('THEME','Theme       :',$theme);
logger::log('THEME','Font        :',$fontfamily);
logger::log('THEME','Icons       :',$icontheme);

//We need to put any @imports first, so we must read the font
readfile('fonts/'.$fontfamily);
print "\n";

// Theme files import one or the other of the theme_base files.
// We can either use @import and put them at the root of the site
// or we can read them now. Either way we have to scan the file and
// pull the import out, because it has to first. This way feels safer.
// The File to import is included in a comment in the theme file, ie
/* theme_base/image_theme.css */
// We parse comments, check to see if they're files, and read them if they are
$files = [
	'themes/'.$theme
];
$files_to_read = [];
foreach ($files as $file) {
	$lines = file($file);
	foreach ($lines as $line) {
		if (preg_match('/\/\* (.+) \*\//', $line, $matches)) {
			$files_to_read[] = trim($matches[1]);
		} else {
			print $line;
		}
	}
}
print "\n";

foreach ($files_to_read as $file) {
	if (file_exists($file)) {
		readfile($file);
		print "\n";
	}
}

readfile('iconsets/'.$icontheme.'/theme.css');
print "\n";

?>