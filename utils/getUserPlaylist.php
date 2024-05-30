<?php
chdir('..');
include ("includes/vars.php");
include ("includes/functions.php");
$r = json_decode(file_get_contents('php://input'), true);

if (array_key_exists('url', $r)) {
	$url = $r['url'];
	logger::mark("USERPLAYLIST", "Adding User External Playlist ".$url);
	$existingfiles = glob('prefs/userplaylists/*');
	$number = 1;
	while(in_array('prefs/userplaylists/User_Playlist_'.$number, $existingfiles)) {
		$number++;
	}
	file_put_contents('prefs/userplaylists/User_Playlist_'.$number, $url);
} else if (array_key_exists('del', $r)) {
	unlink('prefs/userplaylists/'.$r['del']);
} else if (array_key_exists('rename', $r)) {
	$old_name = $r['rename'];
	$new_name = $r['newname'];
	$oldimage = new albumImage(array('artist' => 'PLAYLIST', 'album' => $old_name));
	$oldimage->change_name($new_name);
	rename('prefs/userplaylists/'.$old_name, 'prefs/userplaylists/'.$new_name);
}

http_response_code(204);

?>

