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
// Font MUST be first, otherwise the @import on the Google fonts doesn't work
readfile('fonts/'.$fontfamily);
readfile('themes/'.$theme);
readfile('sizes/'.$fontsize);
readfile('coversizes/'.$coversize);
readfile('iconsets/'.$icontheme.'/theme.css');
?>