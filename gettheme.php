<?php
require_once ('includes/vars.php');
header('Content-Type: text/css');
$fontfamily = array_key_exists('fontfamily', $_REQUEST) ? $_REQUEST['fontfamily'] : 'Nunito.css';
$theme = array_key_exists('theme', $_REQUEST) ? $_REQUEST['theme'] : 'Numismatist.css';
$fontsize = array_key_exists('fontsize', $_REQUEST) ? $_REQUEST['fontsize'] : '02-Normal.css';
$coversize = array_key_exists('coversize', $_REQUEST) ? $_REQUEST['coversize'] : '40-Large.css';
$icontheme = array_key_exists('icontheme', $_REQUEST) ? $_REQUEST['icontheme'] : 'New-Dark-Circled';
// For older-style requests before we could have spaces
$fontfamily = preg_replace('/_/', ' ', $fontfamily);
logger::log('THEME','Theme       :',$theme);
logger::log('THEME','Font        :',$fontfamily);
logger::log('THEME','Font Size   :',$fontsize);
logger::log('THEME','Cover Size  :',$coversize);
logger::log('THEME','Icons       :',$icontheme);

//We need to put any @imports first
$files = [
	'fonts/'.$fontfamily,
	'themes/'.$theme
];

$lines = [];
foreach ($files as $file) {
	$lines[$file] = file($file);
	print implode('', array_filter($lines[$file], 'check_for_import'));
}
// This will put the @imports in again, but that doesn't matter
// because @imports must come before everything else in the file
foreach ($lines as $file) {
	print implode('', $file);
}

// These files don't have @imports in them at the time of writing
readfile('sizes/'.$fontsize);
readfile('coversizes/'.$coversize);
readfile('iconsets/'.$icontheme.'/theme.css');

function check_for_import($v) {
	return (strpos($v, "@import") !== false);
}

?>