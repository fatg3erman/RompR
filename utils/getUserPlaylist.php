<?php
chdir('..');
include ("includes/vars.php");
include ("includes/functions.php");
require_once ('utils/imagefunctions.php');

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
	$old_name = rawurldecode($_REQUEST['rename']);
	$new_name = rawurldecode($_REQUEST['newname']);
	$oldimage = new albumImage(array('artist' => 'PLAYLIST', 'album' => $old_name));
	$oldimage->change_name($new_name);
	system('mv "prefs/userplaylists/'.$old_name.'" "prefs/userplaylists/'.$new_name.'"');
}

?>

<html></html>
