<?php
chdir('../..');

include ("includes/vars.php");
include ("includes/functions.php");
include ("collection/collection.php");
include ("player/mpd/connection.php");
include ("backends/sql/backend.php");

switch ($_REQUEST['action']) {

	case "getlist":
		print_playlists_as_json();
		break;

}

function print_playlists_as_json() {
	global $putinplaylistarray, $playlist, $prefs;
    $playlists = do_mpd_command("listplaylists", true, true);
    $pls = array();
    $putinplaylistarray = true;
    if (array_key_exists('playlist', $playlists)) {
        foreach ($playlists['playlist'] as $name) {
        	$playlist = array();
        	$pls[rawurlencode($name)] = array();
            doCollection('listplaylistinfo "'.$name.'"');
            $c = 0;
            $plimage = "";
            $key = md5(htmlentities($name));
            if (file_exists('prefs/plimages/'.$key.".jpg")) {
            	$plimage = 'prefs/plimages/'.$key.".jpg";
            }
            foreach($playlist as $track) {
                list($flag, $link) = $track->get_checked_url();
                $albumartist = format_sortartist($track->tags);
                $image = $plimage;
                if ($result = sql_prepare_query("SELECT Image FROM
                        Albumtable JOIN Artisttable ON
                        (Albumtable.AlbumArtistindex = Artisttable.Artistindex)
                        WHERE Albumname = ? AND Artistname = ?", $track->tags['Album'], $albumartist)) {
                    while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
                        $image = $obj->Image;
                    }
                }
                if ($image == $plimage) {
                    if ($result = sql_prepare_query("SELECT Image FROM
                            Tracktable JOIN Albumtable USING
                            (Albumindex)
                            WHERE Albumname = ? AND Title = ?", $track->tags['Album'], $track->tags['Title'])) {
                        while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
                            $image = $obj->Image;
                        }
                    }
                }
    	        $pls[rawurlencode($name)][] = array(
    	        	'Uri' => $link,
    	        	'Title' => $track->tags['Title'],
    	            "Album" => $track->tags['Album'],
        	        "Artist" => $track->get_artist_string(),
    	        	'albumartist' => $albumartist,
    	        	'duration' => $track->tags['Time'],
    	        	'Image' => $image,
    	        	'key' => $key,
    	        	'pos' => $c,
    	        	'plimage' => $plimage,
                    'Type' => $track->tags['type']
    	        );
    	        $c++;
    	    }
        }
    }

    print json_encode($pls);

}

?>
