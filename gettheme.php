<?php
require_once ('includes/vars.php');
logger::log('THEME','Theme       :',$_REQUEST['theme']);
logger::log('THEME','Font        :',$_REQUEST['fontfamily']);
logger::log('THEME','Font Size   :',$_REQUEST['fontsize']);
logger::log('THEME','Cover Size  :',$_REQUEST['coversize']);
logger::log('THEME','Icons       :',$_REQUEST['icontheme']);
header('Content-Type: text/css');
// Font MUST be first, otherwise the @import on the Google fonts doesn't work
readfile('fonts/'.$_REQUEST['fontfamily']);
readfile('themes/'.$_REQUEST['theme']);
readfile('sizes/'.$_REQUEST['fontsize']);
readfile('coversizes/'.$_REQUEST['coversize']);
readfile('iconsets/'.$_REQUEST['icontheme'].'/theme.css');
readfile('iconsets/'.$_REQUEST['icontheme'].'/adjustments.css');
?>