<?php
require_once ("includes/vars.php");
require_once ("includes/functions.php");
require_once ("international.php");
require_once ("player/".$prefs['player_backend']."/player.php");
require_once ("backends/sql/backend.php");
require_once ("utils/imagefunctions.php");

// For speed and memory usage we do two VERY IMPORTANT things
// 1. JSON encode each track one-by-one, print out the result, and then throw that data away
// 2. Don't use the Collection data models.
// This saves potentiall hundreds of MB of RAM - json_encode is astonishingly inefficient,
// and the Collection data models use up a lot of memory for things we just don't need here

// This is intended to be a fast pipe to convert MPD data into RompR data.

header('Content-Type: application/json; charset=utf-8');
$doneone = false;
class playlistCollection {

    private $foundartists;

    public function doNewPlaylistFile(&$filedata) {

        global $prefs;

        // Translate from MPD_FILE_MODEL to ROMPR_FILE_MODEL
        $info = array_replace(ROMPR_FILE_MODEL, $filedata);
        if ($info['Title'] === null) {
            $info['Title'] = '';
        }
        $albumartist = format_sortartist($filedata);
        // Bloody spotify often returns album artist = A & B but track artists 'B' and 'A'.
        // This screws up the playcount stats. They're also not consistent with
        // capitalisation of articles in Album Artists
        $tartist = format_artist($filedata['Artist'],'');
        $tartist_reversed = is_array($filedata['Artist']) ? format_artist(array_reverse($filedata['Artist']),'') : '';

        if (strtolower($tartist_reversed) == strtolower($albumartist)) {
            $info['Artist'] = array_reverse($info['Artist']);
            $tartist = $tartist_reversed;
            $albumartist = $tartist_reversed;
        }

        $albumimage = new baseAlbumImage(array(
            'baseimage' => $filedata['X-AlbumImage'],
            'artist' => artist_for_image($filedata['type'], $albumartist),
            'album' => $filedata['Album']
        ));
        $albumimage->check_image($filedata['domain'], $filedata['type'], true);

        $info['Id'] = (int) $filedata['Id'];
        $info['year'] = getYear($$filedata['Date']);
        $info['ImgKey'] = $albumimage->get_image_key();
        $info['images'] = $albumimage->get_images();
        $info['trackartist'] = $tartist;
        $info['albumartist'] = $albumartist;
        $info['metadata']['track']['name'] = trim($filedata['Title']);
        $info['metadata']['track']['musicbrainz_id'] = trim($filedata['MUSICBRAINZ_TRACKID']);
        $info['metadata']['album']['name'] = trim($filedata['Album']);
        $info['metadata']['album']['artist'] = trim($albumartist);
        $info['metadata']['album']['musicbrainz_id'] = trim($filedata['MUSICBRAINZ_ALBUMID']);
        $info['metadata']['album']['uri'] = $filedata['X-AlbumUri'];
        if ($info['X-AlbumUri'] && getDomain($filedata['X-AlbumUri']) == 'spotify') {
            $info['metadata']['album']['spotify'] = array(
                'id' => substr($filedata['X-AlbumUri'], 14)
            );
        }

        $this->foundartists = array();

        // All kinds of places we get artist names from:
        // Composer, Performer, Track Artist, Album Artist
        // Note that we filter duplicates
        // This creates the metadata array used by the info panel and nowplaying -
        // Metadata such as scrobbles and ratings will still use the Album Artist

        if ($prefs['displaycomposer']) {
            // The user has chosen to display Composer/Perfomer information
            // Here check:
            // a) There is composer/performer information AND
            // bi) Specific Genre Selected, Track Has Genre, Genre Matches Specific Genre OR
            // bii) No Specific Genre Selected, Track Has Genre
            if (($info['Composer'] !== null || $info['Performer'] !== null) &&
                (($prefs['composergenre'] && $info['Genre'] &&
                    checkComposerGenre($info['Genre'], $prefs['composergenrename'])) ||
                (!$prefs['composergenre'] && $info['Genre'])))
            {
                // Track Genre matches selected 'Sort By Composer' Genre
                // Display Compoer - Performer - AlbumArtist
                $this->do_composers($info);
                $this->do_performers($info);
                // The album artist probably won't be required in this case, but use it just in case
                $this->do_albumartist($info);
                // Don't do track artist as with things tagged like this this is usually rubbish
            } else {
                // Track Genre Does Not Match Selected 'Sort By Composer' Genre
                // Or there is no composer/performer info
                // Do Track Artist - Album Artist - Composer - Performer
                $this->do_track_artists($info);
                $this->do_albumartist($info);
                $this->do_performers($info);
                $this->do_composers($info);
            }
            if ($info['Composer'] !== null || $info['Performer'] !== null) {
                $info['metadata']['iscomposer'] = 'true';
            }
        } else {
            // The user does not want Composer/Performer information
            $this->do_track_artists($info, $albumartist);
            $this->do_albumartist($info, $albumartist);
        }

        if (count($info['metadata']['artists']) == 0) {
            array_push($info['metadata']['artists'], array( "name" => "", "musicbrainz_id" => ""));
        }

        return $info;
    }

    private function do_composers(&$info) {
        if ($info['Composer'] == null) {
            return;
        }
        foreach ($info['Composer'] as $comp) {
            if ($this->artist_not_found_yet($comp)) {
                array_push($info['metadata']['artists'], array( "name" => trim($comp), "musicbrainz_id" => "", "type" => "composer", "ignore" => "false"));
            }
        }
    }

    private function do_performers(&$info) {
        if ($info['Performer'] == null) {
            return;
        }
        foreach ($info['Performer'] as $comp) {
            $toremove = null;
            foreach($info['metadata']['artists'] as $i => $artist) {
                if ($artist['type'] == "albumartist" || $artist['type'] == "artist") {
                    if (strtolower($artist['name'] ==  strtolower($comp))) {
                        $toremove = $i;
                        break;
                    }
                }
            }
            if ($toremove !== null) {
                array_splice($info['metadata']['artists'], $toremove, 1);
            }

            if ($toremove !== null || $this->artist_not_found_yet($comp)) {
                array_push($info['metadata']['artists'], array( "name" => trim($comp), "musicbrainz_id" => "", "type" => "performer", "ignore" => "false"));
            }
        }
    }

    private function do_albumartist(&$info) {
        $aartist = null;
        if (!($info['type'] == "stream" && $info['albumartist'] == "Radio") &&
            strtolower($info['albumartist']) != "various artists" &&
            strtolower($info['albumartist']) != "various")
        {
            $aartist = $info['albumartist'];
        }
        if ($aartist !== null && $this->artist_not_found_yet($aartist)) {
            array_push($info['metadata']['artists'], array( "name" => trim($aartist), "musicbrainz_id" => trim($info['MUSICBRAINZ_ALBUMARTISTID']), "type" => "albumartist", "ignore" => "false"));
        }
    }

    private function do_track_artists(&$info) {
        if ($info['Artist'] == null) {
            return;
        }
        $artists = getArray($info['Artist']);
        $mbids = $info['MUSICBRAINZ_ARTISTID'];
        if (count($mbids) > count($artists)) {
            // More MBIDs that Artists. This might be one of those daft things where MBIDs are semicolon-separated
            // but artists are comma-separated.
            // You can even get artists = ['artist1, artist2', 'artist3']. Sigh. Hence the first implode.
            $astring = implode(', ',$artists);
            $newartists = explode(',', $astring);
            if (count($newartists) == count($mbids)) {
                debuglog("GETPLAYLIST","Trying splitting comma-separated artist string",8);
                // In case AlbumArtist has that format too
                $this->artist_not_found_yet($astring);
                $artists = $newartists;
            }
        }
        while (count($mbids) < count($artists)) {
            $mbids[] = "";
        }
        $a = array();
        foreach ($artists as $i => $comp) {
            if ($comp != "") {
                if ($this->artist_not_found_yet($comp)) {
                    array_push($info['metadata']['artists'], array( "name" => trim($comp), "musicbrainz_id" => trim($mbids[$i]), "type" => "artist", "ignore" => "false"));
                    $a[] = $comp;
                }
            }
        }
        // This is to try and prevent repeated names - eg artists = [Pete, Dud] and albumartist = Pete & Dud or Dud & Pete
        $this->artist_not_found_yet(concatenate_artist_names($a));
        $this->artist_not_found_yet(concatenate_artist_names(array_reverse($a)));
    }

    private function artist_not_found_yet($a) {
        $s = strtolower($a);
        if (in_array($s, $this->foundartists)) {
            return false;
        } else {
            $this->foundartists[] = $s;
            return true;
        }
    }
}

$player = new $PLAYER_TYPE();
$collection = new playlistCollection();
print '[';
foreach ($player->get_playlist($collection) as $info) {
    if ($doneone) {
        print ', ';
    } else {
        $doneone = true;
    }
    print json_encode($info);
};
print ']';
ob_flush();
debuglog("Playlist Output Is Done","GETPLAYLIST");

?>
