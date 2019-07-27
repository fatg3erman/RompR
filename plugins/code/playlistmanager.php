<?php
chdir('../..');

include ("includes/vars.php");
include ("includes/functions.php");
require_once ("player/".$prefs['player_backend']."/player.php");
include ("backends/sql/backend.php");
require_once ("utils/imagefunctions.php");

switch ($_REQUEST['action']) {

	case "getlist":
		print_playlists_as_json();
		break;

}

function print_playlists_as_json() {
	global $PLAYER_TYPE;
	$player = new $PLAYER_TYPE();
	$pls = array();
	foreach ($player->get_stored_playlists(true) as $name) {
    	$pls[rawurlencode($name)] = array();
		$albumimage = new baseAlbumImage(array('artist' => "PLAYLIST", 'album' => $name));
		$c = 0;
		$plimage = $albumimage->get_image_if_exists();
		foreach ($player->get_stored_playlist_tracks($name, 0) as list($flag, $link, $filedata)) {
			$usealbumimage = $albumimage;
            $albumartist = format_sortartist($filedata);
            $image = $plimage;
            $result = sql_prepare_query(false, PDO::FETCH_OBJ, null, null,
                    "SELECT Image FROM
                    Albumtable JOIN Artisttable ON
                    (Albumtable.AlbumArtistindex = Artisttable.Artistindex)
                    WHERE Albumname = ? AND Artistname = ?", $filedata['Album'], $albumartist);
            foreach ($result as $obj) {
                $image = $obj->Image;
				$usealbumimage = new baseAlbumImage(array('artist' => $albumartist, 'album' => $filedata['Album']));
				break;
            }
            if ($image == $plimage) {
                $result = sql_prepare_query(false, PDO::FETCH_OBJ, null, null,
                        "SELECT Image, Artistname FROM
                        Tracktable JOIN Albumtable USING (Albumindex)
						JOIN Artisttable ON (Albumtable.AlbumArtistindex = Artisttable.Artistindex)
                        WHERE Albumname = ? AND Title = ?", $tfiledata['Album'], $filedata['Title']);
                foreach ($result as $obj) {
                    $image = $obj->Image;
					$usealbumimage = new baseAlbumImage(array('artist' => $obj->Artistname, 'album' => $filedata['Album']));
					break;
                }
            }
			if ($image == $plimage && $filedata['type'] == 'stream') {
                $result = sql_prepare_query(false, PDO::FETCH_OBJ, null, null,
                        "SELECT Image FROM RadioStationtable
                        WHERE StationName = ?", $filedata['Album']);
                foreach ($result as $obj) {
                    $image = $obj->Image;
					$usealbumimage = new baseAlbumImage(array('artist' => 'STREAM', 'album' => $filedata['Album']));
					break;
                }
            }
	        $pls[rawurlencode($name)][] = array(
	        	'Uri' => rawurlencode($link),
	        	'Title' => $filedata['Title'],
	            "Album" => $filedata['Album'],
    	        "Artist" => format_artist($filedata['Artist']),
	        	'albumartist' => $albumartist,
	        	'duration' => $filedata['Time'],
	        	'Image' => $image,
	        	'key' => $usealbumimage->get_image_key(),
	        	'pos' => $c,
	        	'plimage' => $plimage,
                'Type' => $filedata['type']
	        );
	        $c++;
        }
    }

    print json_encode($pls);

}

?>
