<?php
chdir('..');
include ("includes/vars.php");
include ("includes/functions.php");

if (array_key_exists('url', $_REQUEST)) {
	$url = rawurldecode($_REQUEST['url']);
	debuglog("Adding User External Playlist ".$url,"USERPLAYLIST");
	$existingfiles = glob('prefs/userplaylists/*');
	$number = 1;
	while(in_array('prefs/userplaylists/User_Playlist_'.$number, $existingfiles)) {
		$number++;
	}
	file_put_contents('prefs/userplaylists/User_Playlist_'.$number, $url);
} else if (array_key_exists('del', $_REQUEST)) {
	system('rm "prefs/userplaylists/'.rawurldecode($_REQUEST['del']).'"');
} else if (array_key_exists('rename', $_REQUEST)) {
	system('mv "prefs/userplaylists/'.rawurldecode($_REQUEST['rename']).'" "prefs/userplaylists/'.rawurldecode($_REQUEST['newname'].'"'));
}

?>

<html></html>