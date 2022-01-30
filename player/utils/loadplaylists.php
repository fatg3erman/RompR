<?php
chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
prefs::$database = new collection_base();
$used_images = array();
$dbterms = array( 'tags' => null, 'rating' => null );

if (array_key_exists('playlist', $_REQUEST)) {
	$pl = $_REQUEST['playlist'];
	do_playlist_tracks($pl,'icon-music', $_REQUEST['target']);

} else if (array_key_exists('userplaylist', $_REQUEST)) {
	$pl = $_REQUEST['userplaylist'];
	do_user_playlist_tracks($pl,'icon-music', $_REQUEST['target']);

} else if (array_key_exists('addtoplaylistmenu', $_REQUEST)) {
	$player = new player();
	$playlists = array();
	foreach ($player->get_stored_playlists(true) as $pl) {
		$playlists[] = array('name' => rawurlencode($pl), 'html' => htmlentities($pl));
	}
	header('Content-Type: application/json; charset=utf-8');
	print json_encode($playlists);
} else {
	do_playlist_header();
	$c = 0;
	$player = new player();
	foreach ($player->get_stored_playlists(false) as $pl) {
		logger::log("MPD PLAYLISTS", "Adding Playlist : ".$pl);
		add_playlist(rawurlencode($pl), htmlentities($pl), 'icon-doc-text', 'clickloadplaylist', player::is_personal_playlist($pl), $c, false, null);
		$c++;
	}
	$existingfiles = glob('prefs/userplaylists/*');
	foreach($existingfiles as $file) {
		add_playlist(rawurlencode(file_get_contents($file)), htmlentities(basename($file)), 'icon-doc-text', 'clickloaduserplaylist', true, $c, true, null);
		$c++;
	}
	if (!$player->playlist_error &&
		(prefs::$prefs['player_backend'] == prefs::$prefs['collection_player']) &&
		(prefs::$prefs['multihosts'][prefs::$prefs['currenthost']]['mopidy_remote'] == false)
	) {
		sort($used_images);
		$imgs = glob('prefs/plimages/*');
		sort($imgs);
		$unneeded = array_diff($imgs, $used_images);
		foreach ($unneeded as $img) {
			logger::log("MPD PLAYLISTS", "Removing uneeded playlist image",$img);
			if (is_dir($img)) {
				rrmdir($img);
			} else {
				@unlink($img);
			}
		}
	} else {
		logger::warn("MPD PLAYLISTS", "Error when loading saved playlists");
	}
}

function do_playlist_tracks($pl, $icon, $target) {
	uibits::directoryControlHeader($target, $pl);
	uibits::playlistPlayHeader(rawurlencode($pl), $pl);
	$player = new player();
	$c = 0;
	if ($pl == '[Radio Streams]') {
		foreach ($player->get_stored_playlist_tracks('[Radio Streams]', 0) as list($class, $uri, $filedata)) {
			add_playlist(rawurlencode($uri), htmlentities(substr($uri, strrpos($uri, '#')+1, strlen($uri))), 'icon-radio-tower' ,'clicktrack', true, $c, false, $pl);
			$c++;
		}
	} else {
		foreach ($player->get_stored_playlist_tracks($pl, 0) as list($class, $uri, $filedata)) {
			switch ($filedata['domain']) {
				case "soundcloud":
				case "youtube":
				case "spotify":
				case "gmusic":
					$icon = "icon-".$filedata['domain']."-circled";
					break;

				default;
					$icon = "icon-music";
					break;
			}
			add_playlist(rawurlencode($uri), get_artist_track_title($filedata), $icon, $class, player::is_personal_playlist($pl), $c, false, $pl);
			$c++;
		}
	}
}

function get_artist_track_title($filedata) {
	if ($filedata['Album'] == ROMPR_UNKNOWN_STREAM) {
		return $filedata['file'];
	} else {
		if ($filedata['type'] == "stream") {
			return $filedata['Album'];
		} else {
			return htmlentities($filedata['Title']).'<br/><span class="playlistrow2">'.htmlentities(format_artist($filedata['Artist'])).'</span>';
		}
	}
}

function do_user_playlist_tracks($pl, $icon, $target) {
	logger::mark("USERPLAYLISTS", "Downloading remote playlist",$pl);
	// Use the MPD version of the playlist parser, since that parses all tracks,
	// which is what we want.
	require_once ("player/mpd/streamplaylisthandler.php");

	// Real bugger this one.
	// HACK ALERT
	// We've got a URL, but to get the playlist title and image we need the name of the playlist
	// Just gonna have to read all the user playlists until we find the right one.
	$snookcocker = glob('prefs/userplaylists/*');
	$pl_name = $pl;
	foreach ($snookcocker as $file) {
		$lines = file($file);
		foreach ($lines as $line) {
			if (trim($line) == $pl) {
				$pl_name = basename($file);
				break 2;
			}
		}
	}

	$tracks = internetPlaylist::load_internet_playlist($pl, '', '', true);
	uibits::directoryControlHeader($target, $pl_name);
	uibits::playlistPlayHeader(rawurlencode($pl), $pl_name);
	foreach ($tracks as $c => $track) {
		add_playlist(
			rawurlencode($track['TrackUri']),
			($track['PrettyStream'] == '') ? $track['TrackUri'] : $track['PrettyStream'],
			'icon-radio-tower',
			'clicktrack',
			false,
			$c,
			false,
			$pl
		);
	}
}

function add_playlist($link, $name, $icon, $class, $delete, $count, $is_user, $pl) {
	global $used_images;
	// Non-editable playlists get a 'draggable' on tracks  as they don't use sortabletracklist
	// Editable playlists get a 'canreorder' on the album header
	$extra_class = ($delete) ? '' : 'draggable ';
	switch ($class) {
		case 'clickloadplaylist':
		case 'clickloaduserplaylist':
			$albumimage = new albumImage(array('artist' => "PLAYLIST", 'album' => $name));
			$image = $albumimage->get_image_if_exists();
			$i = dirname($albumimage->basepath);
			if (!in_array($i, $used_images)) {
				$used_images[] = $i;
			}
			$extra_class = ($delete && !$is_user) ? ' canreorder' : '';

			$buttons = '';
			if ($delete) {
				$add = ($is_user) ? "user" : "";
				$buttons = '<div class="album-extra-controls">';
				$buttons .= '<i class="icon-floppy inline-icon clickable clickicon clickrename'.$add.'playlist"></i>';
				$buttons .= '<input type="hidden" value="'.rawurlencode($name).'" />';
				$buttons .= '<i class="icon-cancel-circled inline-icon clickable clickicon clickdelete'.$add.'playlist"></i>';
				$buttons .= '<input type="hidden" value="'.rawurlencode($name).'" />';
				$buttons .= '</div>';
			}

			print uibits::albumHeader(array(
				'id' => 'pholder_'.md5($name),
				'Image' => $image,
				'Albumname' => $name,
				'ImgKey' => $albumimage->get_image_key(),
				'userplaylist' => $class,
				'plpath' => $link,
				'class' => preg_replace('/clickload/', '', $class).$extra_class,
				'podcounts' => $buttons
			));
			break;

		case "clicktrack":
			print '<div class="containerbox menuitem '.$extra_class.'playable clickable '.$class.' playlisttrack" name="'.$link.'">';
			print '<input class="playlistname" type="hidden" value="'.rawurlencode($pl).'" />';
			print '<input class="playlistpos" type="hidden" value="'.$count.'" />';
			print '<i class="'.$icon.' fixed inline-icon"></i>';
			print '<div class="expand">'.$name.'</div>';
			if ($delete) {
				print '<i class="icon-cancel-circled fixed inline-icon clickable clickicon clickdeleteplaylisttrack" name="'.$count.'"></i>';
				print '<input type="hidden" value="'.rawurlencode($pl).'" />';
			}
			print '</div>';
			break;

		case "clickcue":
			print '<div class="containerbox meunitem '.$extra_class.'playable clickable '.$class.'" name="'.$link.'">';
			print '<i class="'.$icon.' fixed inline-icon"></i>';
			print '<div class="expand">'.$name.'</div>';
			print '</div>';
			break;

		default:
			logger::error("MPD PLAYLISTS", "ERROR! Not permitted type passed to add_playlist",$class);
			break;


	}
}

function do_playlist_header() {
	print '<div class="containerbox vertical-centre fullwidth">';

	print '<div class="expand">
		<input class="enter clearbox" id="godfreybiggins" type="text" placeholder="URL" /></div>';

	print '<button class="fixed iconbutton icon-no-response-playbutton" '.
		'onclick="player.controller.loadPlaylistURL($(\'#godfreybiggins\').val())"></button>';
	print '</div>';
}

?>
