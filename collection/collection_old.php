<?php

include ("player/mpd/streamhandler.php");

$numtracks = 0;
$numalbums = 0;
$numartists = 0;
$totaltime = 0;

$playlist = array();
$putinplaylistarray = false;

$count = 1;
$divtype = "album1";
$collection = null;

$dbterms = array( 'tags' => null, 'rating' => null );

$trackbytrack = false;
$raw_search = false;

function format_artist($artist, $empty = null) {
    $a = concatenate_artist_names($artist);
    if ($a != '.' && $a != "") {
        return $a;
    } else {
        return $empty;
    }
}

function format_sortartist($tags, $return_albumartist = false) {
    global $prefs;
    $sortartist = null;
    if ($prefs['sortbycomposer'] && $tags['Composer'] !== null) {
        if ($prefs['composergenre'] && $tags['Genre'] &&
            checkComposerGenre($tags['Genre'], $prefs['composergenrename'])) {
                $sortartist = $tags['Composer'];
        } else if (!$prefs['composergenre']) {
            $sortartist = $tags['Composer'];
        }
    }
    if ($sortartist == null) {
        if ($return_albumartist || $tags['AlbumArtist'] != null) {
            $sortartist = $tags['AlbumArtist'];
        } else if ($tags['Artist'] != null) {
            $sortartist = $tags['Artist'];
        } else if ($tags['station'] != null) {
            $sortartist = $tags['station'];
        }
    }
    $sortartist = concatenate_artist_names($sortartist);
    //Some discogs tags have 'Various' instead of 'Various Artists'
    if ($sortartist == "Various") {
        $sortartist = "Various Artists";
    }
    return $sortartist;
}

function cacheOrDefaultImage($image, $artname, $size, $domain) {

    if (file_exists("prefs/imagecache/".$artname."_".$size.".jpg")) {
        // This will happen if we're playing a track from Spotify that
        // isn't in the collection. coverscraper will have saved the image
        // to the image cache and the playlist needs to know about it.
        $image = "prefs/imagecache/".$artname."_".$size.".jpg";
    }

    switch ($domain) {
        case "youtube":
        case "soundcloud":
            // Youtube and SoundCloud return album images, but it makes more
            // sense to use them as track images. This is handled in the track
            // object BUT $this->image will also be set to the same image because
            // mopidy returns it as an album image, so this is a kind of hacky thing.
            $image = "newimages/".$domain."-logo.svg";
            break;

        case "internetarchive":
        case "bassdrive":
        case "oe1":
            // These are here because they're not classed as streams -
            // domain will only be bassdrive or oe1 if this is an archive
            // track, and those make more sense to display as albums.
            if ($image == "") $image = "newimages/".$domain."-logo.svg";
            break;

        case "podcast":
        case "podcast+http":
        case "podcast http":
            // Some podcasts return album images, which will be $this->image
            // But not all of them do so we need a fallback.
            if ($image == "") $image = "newimages/podcast-logo.svg";
            break;
    }

    if (file_exists("albumart/".$size."/".$artname.".jpg")) {
        // If there's a local image this overrides everything else because it might
        // be something the user has downloaded
        $image = "albumart/".$size."/".$artname.".jpg";
    }

    return $image;
}

function getImageForTrack($tags) {
    switch ($tags['domain']) {
        case "soundcloud":
        case "youtube":
        case "yt":
            if ($tags['TrackImage']) {
                return $tags['TrackImage'];
            } else if ($tags['X-AlbumImage']) {
                return $tags['X-AlbumImage'];
            } else {
                return "";
            }
            break;

        default:
            return null;
    }
}

class album {
    public function __construct($name, $artist, $domain, $folder = null) {
        global $numalbums;
        $this->artist = $artist;
        $this->name = trim($name);
        $this->tracks = array();
        $this->folder = $folder;
        $this->iscompilation = false;
        $this->musicbrainz_albumid = '';
        $this->datestamp = null;
        $this->uri = null;
        $this->domain = $domain;
        $this->image = null;
        $this->artistobject = null;
        $this->numOfDiscs = -1;
        $this->numOfTrackOnes = 0;
        $this->key = null;
        $numalbums++;
    }

    public function newTrack(&$object, $clear = false) {
        if ($clear) {
            $this->tracks = array($object);
        } else {
            $this->tracks[] = $object;
        }
        if ($this->folder == null) {
            $this->folder = $object->tags['folder'];
        }
        // These parameters are NOT set at create time because we could have
        // some files where only one of them has these tags, so we check it
        // for every incoming track.
        if ($this->image == null) {
            $this->image = $object->tags['X-AlbumImage'];
        }
        if ($this->datestamp == null) {
            $this->datestamp = $object->tags['Date'];
        }
        if ($this->musicbrainz_albumid == '') {
            $this->musicbrainz_albumid = $object->tags['MUSICBRAINZ_ALBUMID'];
        }
        if ($object->tags['Disc'] !== null && $this->numOfDiscs < $object->tags['Disc']) {
            $this->numOfDiscs = $object->tags['Disc'];
        }
        if ($object->tags['Track'] == 1) {
            $this->numOfTrackOnes++;
        }
        if ($this->uri == null && $object->tags['X-AlbumUri'] != null) {
            $this->uri = $object->tags['X-AlbumUri'];
        }
        $object->setAlbumObject($this);
    }

    public function isCompilation() {
        return $this->iscompilation;
    }

    public function getKey() {
        if ($this->key == null) {
            $this->key = make_image_key($this->artist, $this->name);
        }
        return $this->key;
    }

    public function getImage($size) {

        // Return image for an album
        $image = "";
        $artname = $this->getKey();
        if ($this->image && $this->image !== "") {
            $image = $this->image;
        }

        $image =  cacheOrDefaultImage($image, $artname, $size, $this->domain);
        return $image;
    }

    public function setAsCompilation() {
        debuglog("Setting album ".$this->name." as a compilation","COLLECTION",6);
        $this->artist = "Various Artists";
        $this->iscompilation = true;
    }

    public function setArtistObject(&$ob) {
        if ($this->artist != "Various Artists") {
            if ($this->artistobject !== null) {
                $this->artistobject->pleaseReleaseMe($this);
            }
            $this->artistobject = $ob;
        }
    }

    public function trackCount() {
        return count($this->tracks);
    }

    public function sortTracks($always = false) {
        // Mopidy-Spotify doesn't send disc numbers. If we're using the sql backend
        // we don't really need to pre-sort tracks because we can do it on the fly.
        // However, when there are no disc numbers multi-disc albums don't sort properly.
        // Hence we do a little check that we have have the same number of 'Track 1's
        // as discs and only do the sort if they're not the same. This'll also
        // sort out badly tagged local files. It's essential that disc numbers are set
        // because the database will not find the tracks otherwise.
        if ($always == false && $this->numOfDiscs > 0 &&
            ($this->numOfTrackOnes <= 1 || $this->numOfTrackOnes == $this->numOfDiscs)) {
               return $this->numOfDiscs;
        }

        $discs = array();
        $number = 1;
        foreach ($this->tracks as $ob) {
            if ($ob->tags['Track'] !== '') {
                $track_no = intval($ob->tags['Track']);
            } else {
                $track_no = $number;
            }
            # Just in case we have a multiple disc album with no disc number tags
            $discno = intval($ob->tags['Disc']);
            if ($discno == '' || $discno == null) {
                $discno = 1;
            }
            if (!array_key_exists($discno, $discs)) {
            	$discs[$discno] = array();
            }
            while(array_key_exists($track_no, $discs[$discno])) {
                $discno++;
	            if (!array_key_exists($discno, $discs)) {
    	        	$discs[$discno] = array();
                }
            }
            $discs[$discno][$track_no] = $ob;
            $ob->updateDiscNo($discno);
            $number++;
        }
        $numdiscs = count($discs);

        $this->tracks = array();
        ksort($discs, SORT_NUMERIC);
        foreach ($discs as $disc) {
            ksort($disc, SORT_NUMERIC);
            $this->tracks = array_merge($this->tracks, $disc);
        }
        $this->numOfDiscs = $numdiscs;

        return $numdiscs;
    }

    public function getDate() {
        return getYear($this->datestamp);
    }

    public function getAllTracks($cmd) {
        $tracks = array();
        foreach ($this->tracks as $track) {
            if (preg_match('/:track:/', $track->tags['file'])) {
                $tracks[] = $cmd.' "'.format_for_mpd($track->tags['file']).'"';
            }
        }
        return $tracks;
    }
}

class artist {

    public function __construct($name) {
        $this->name = $name;
        $this->lcname = strtolower($name);
        $this->albums = array();
        $this->uri = null;
    }

    public function newAlbum(&$object) {
        // Pass an album object to this function
        $key = preg_replace("/\W|_/", '',
            strtolower($object->name).$object->domain.$object->folder);
        $this->albums[$key] = $object;
        $object->setArtistObject($this);
    }

    public function checkAlbum($album, $directory, $domain) {
        $key = preg_replace("/\W|_/", '', strtolower($album).$domain.$directory);
        if (array_key_exists($key, $this->albums)) {
            debuglog("Checkalbum found album ".$album." by ".$this->name,"COLLECTION",7);
            return $this->albums[$key];
        }
        return false;
    }

    public function pleaseReleaseMe(&$object) {
        $ak = null;
        foreach ($this->albums as $key => $album) {
            if ($album === $object) {
                $ak = $key;
                break;
            }
        }
        if ($ak) {
            debuglog("Removing album ".$object->name." from artist ".$this->name, "COLLECTION");
            unset($this->albums[$ak]);
        } else {
            debuglog(
                "AWOOGA! Removing album ".$object->name." from artist ".$this->name.
                " FAILED!", "COLLECTION");
        }
    }

    public function trackCount() {
        $count = 0;
        foreach($this->albums as $album) {
            $count += $album->trackCount();
        }
        return $count;
    }

    public function getAllTracks($cmd) {
        $tracks = array();
        foreach ($this->albums as $album) {
            $album->sortTracks(true);
            $tracks = array_merge($album->getAllTracks($cmd), $tracks);
        }
        return $tracks;
    }

}

class track {
    public function __construct(&$filedata) {

        $this->albumobject = null;
        $this->tags = $filedata;
    }

    public function getImage() {
        return getImageForTrack($this->tags);
    }

    public function setAlbumObject(&$object) {
        $this->albumobject = $object;
    }

    public function updateDiscNo($disc) {
        $this->tags['Disc'] = $disc;
    }

    public function get_artist_string() {
        return format_artist($this->tags['Artist']);
    }

    public function get_sort_artist() {
        return format_sortartist($this->tags);
    }

    public function get_checked_url() {
        global $prefs;
        $matches = array();
        if ($prefs['player_backend'] == 'mpd' &&
            preg_match("/api\.soundcloud\.com\/tracks\/(\d+)\//", $this->tags['file'], $matches)) {
            return array('clickcue', "soundcloud://track/".$matches[1]);
        } else {
            return array('clicktrack', $this->tags['file']);
        }
    }

    public function get_artist_track_title() {
        if ($this->albumobject->name == "Unknown Internet Stream") {
            return $this->tags['file'];
        } else {
            if ($this->tags['type'] == "stream") {
                return $this->albumobject->name;
            } else {
                return htmlentities($this->tags['Title']).'<br/><span class="playlistrow2">'.htmlentities($this->get_artist_string()).'</span>';
            }
        }
    }
}

class musicCollection {

    public function __construct() {
        $this->artists = array();
    }

    private function findAlbumByName($album) {
        $results = array();
        foreach ($this->artists as $art) {
            foreach ($art->albums as $object) {
                if(strtolower($object->name) == $album) {
                    $results[] = $object;
                }
            }
        }
        return $results;
    }

    private function checkAlbum($album, $artist, $directory, $domain) {
        $a = false;
        if ($artist && $directory) {
            $albm = trim($album);
            $a = $this->artists[$artist]->checkAlbum($album, $directory, $domain);
        }
        return $a;
    }

    private function findAlbum($album, $artist, $directory, $domain) {
        if ($artist != null) {
            $albm = trim($album);
            debuglog("Looking for album ".$album." by ".$artist,"COLLECTION", 5);
            foreach ($this->artists[$artist]->albums as $object) {
                if ($albm == $object->name && $domain == $object->domain) {
                    return $object;
                }
            }
        }
        if ($directory != null && $directory != ".") {
            debuglog("Looking for album ".$album." in ".$directory,"COLLECTION", 5);
            $albs = $this->findAlbumByName(trim(strtolower($album)));
            foreach ($albs as $object) {
                debuglog("Found one in ".$object->folder,"COLLECTION", 5);
                if ($directory == $object->folder && $object->domain == $domain) {
                    return $object;
                }
            }
        }
        return false;
    }

    public function newTrack(&$track) {

        global $playlist, $prefs, $trackbytrack, $putinplaylistarray;

        static $current_artist = null;
        static $current_album = null;
        static $abm = false;

        $sortartist = $track->get_sort_artist();
        $artistkey = strtolower($sortartist);

        // If artist doesn't exist, create it -
        // indexed by all lower case name for convenient sorting and grouping
        if (!array_key_exists($artistkey, $this->artists)) {
            $this->artists[$artistkey] = new artist($sortartist);
        }

        // NOTE: Spotify can have mutliple albums with the same name.
        // if ($domain == "spotify" || $album != $current_album || $sortartist != $current_artist || $domain != $current_domain) {
        if (($current_album && $current_artist) &&
            ($track->tags['Album'] != $current_album->name || $artistkey != $current_artist->lcname ||
            $track->tags['domain'] != $current_album->domain)) {
            $abm = false;
        }

        // Albums are not indexed by name, since we may have 2 or more albums with the same name
        // by multiple artists

        // Same album name, same directory, album artist NOT set
        // Therefore MUST be sortartist != current_artist and therefore this is a compilation
        // (or domain *could* be different but we mitigate that by setting folder carefully)
        // This check goes first, because it's the most likely.
        if ($abm === false && $track->tags['AlbumArtist'] == null &&
            $current_album && $track->tags['Album'] == $current_album->name &&
            $track->tags['folder'] == $current_album->folder) {

            $abm = $current_album;
            if (!$abm->isCompilation()) {
                debuglog("Album ".$track->tags['Album']." has no AlbumArtist tags but is a compilation","COLLECTION",6);
                debuglog("sortartst is ".$sortartist. " and current_artist is ".$current_artist->name,"COLLECTION",6);
                // Create various artists group if it isn't there
                if (!array_key_exists("various artists", $this->artists)) {
                    $this->artists["various artists"] = new artist("Various Artists");
                }
                // Add the album to the various artists group
                $this->artists["various artists"]->newAlbum($abm);
                $abm->setAsCompilation();
            }
            $current_artist = $this->artists[$artistkey];
            $current_album = $abm;
        }

        if ($abm === false) {
            // Still not found. Do we have an album by this sort artist with this name
            // in this directory on this domain?
            $abm = $this->checkAlbum($track->tags['Album'], $artistkey, $track->tags['folder'], $track->tags['domain']);
            if ($abm) {
                $current_artist = $this->artists[$artistkey];
                $current_album = $abm;
            }
        }

        // The following 2 checks are the slowest by a fair way but we're unlikely ever to
        // actually get here I think, provided most people's collections are vageuly organised
        // into directories the above two checks should find the album.
        // That only leaves these to run once for every new album with null albumartist.... hmmmm...

        if ($track->tags['AlbumArtist'] == null && $abm === false) {
            // Do we have an album by this sortartist with this name, in any directory
            $abm = $this->findAlbum($track->tags['Album'], $artistkey, null, $track->tags['domain']);
            if ($abm) {
                debuglog("Found album ".$track->tags['Album']." using artist with no directory","COLLECTION",6);
                $current_artist = $this->artists[$artistkey];
                $current_album = $abm;
            }
        }

        if ($track->tags['AlbumArtist'] == null && $abm === false) {
            // Do we have an album by this name, in the same directory
            $abm = $this->findAlbum($track->tags['Album'], null, $track->tags['folder'], $track->tags['domain']);
            if ($abm !== false) {
                debuglog("Found album ".$track->tags['Album']." using directory","COLLECTION",6);
                // Yes we do, and as all the previous checks failed it MUST be a compilation
                // EXCEPT for the case where the album axists because it's one of those annoying Spotify search results
                // that has no artist.
                if ($abm->artistobject->name == "Spotify") {
                    debuglog("Album is a Spotify artist-less search result. Using ".$artistkey,"COLLECTION",6);
                    $this->artists[$artistkey]->newAlbum($abm);
                } else if (!($abm->isCompilation())) {
                    // Create various artists group if it isn't there
                    if (!array_key_exists("various artists", $this->artists)) {
                        $this->artists["various artists"] = new artist("Various Artists");
                    }
                    // Add the album to the various artists group
                    $this->artists["various artists"]->newAlbum($abm);
                    $abm->setAsCompilation();
                }
                $current_artist = $this->artists[$artistkey];
                $current_album = $abm;
            }
        }

        if ($abm === false) {
            // We didn't find the album, so create it
            $abm = new album($track->tags['Album'], $sortartist, $track->tags['domain'], $track->tags['folder']);
            $this->artists[$artistkey]->newAlbum($abm);
            $current_artist = $this->artists[$artistkey];
            $current_album = $abm;
        }

        $abm->newTrack($track);

        if ($track->tags['Pos'] !== null) {
            $playlist[$track->tags['Pos']] = $track;
        } else if ($putinplaylistarray) {
            $playlist[] = $track;
        }
    }

    // NOTE :   If it's a track from a compilation not tagged with 'various artists',
    //          it's now been added to various artists
    //              and the album has the iscompilation flag set
    //              and the artist name for the album set to Various Artists
    //          Tracks which have 'Various Artists' as the artist/album artist name in the ID3 tag
    //          will be in the various artists group too,
    //              but 'iscompilation' will not be set unless as least one of the tracks on the
    //              album has something else as the artist name.
    //              This shouldn't matter.
    //          In fact, following some recent changes, I don't think we even need the
    //          isCompilation flag.

    public function getSortedArtistList() {
        $temp = array_keys($this->artists);
        sort($temp, SORT_STRING);
        $key = array_search("various artists", $temp);
        if ($key !== false) {
            unset($temp[$key]);
            array_unshift($temp, "various artists");
        }
        return $temp;
    }

    public function artistName($artist) {
        return $this->artists[$artist]->name;
    }

    public function artistNumTracks($artist) {
        return $this->artists[$artist]->trackCount();
    }

    public function getAlbumList($artist, $ignore_compilations) {
        return $this->artists[$artist]->albums;
    }

    public function getAllTracks($cmd) {
        $tracks = array();
        $artists = $this->getSortedArtistList();
        foreach ($artists as $artist) {
            $tracks = array_merge($this->artists[$artist]->getAllTracks($cmd), $tracks);
        }
        return $tracks;
    }

    public function tracks_as_array() {
        $results = array();
        $artistlist = $this->getSortedArtistList();
        foreach($artistlist as $artistkey) {
            $albumartist = $this->artistName($artistkey);
            $albumlist = $this->getAlbumList($artistkey, false, false);
            if (count($albumlist) > 0) {
                foreach($albumlist as $album) {
                    $album->sortTracks();
                    foreach($album->tracks as $trackobj) {
                        $a = $trackobj->get_artist_string();
                        $trackartist = ($a != null) ? $a : $albumartist;
                        $track = array(
                            "uri" => $trackobj->tags['file'],
                            "album" => $album->name,
                            "title" => $trackobj->tags['Title'],
                            "artist" => $trackartist,
                            "albumartist" => $albumartist,
                            "trackno" => $trackobj->tags['Track'],
                            "disc" => $trackobj->tags['Disc'],
                            "albumuri" => $album->uri,
                            "image" => $album->getImage('asdownloaded'),
                            "trackimage" => $trackobj->getImage(),
                            "duration" => $trackobj->tags['Time'],
                            "date" => $album->datestamp
                        );
                        // A lot of code that depends on this was written to handle
                        // mopidy model search results. The above is not mopidy model,
                        // but the following code is to friggicate it into something that
                        // makes faveFinder still work because it's late and this is easier than
                        // fucking about with everything that depends on this.
                        $d = getDomain($track['uri']);
                        $fuckyou = false;
                        foreach ($results as $c => $piss) {
                            if ($piss["uri"] == $d.':bodgehack') {
                                $fuckyou = $c;
                                break;
                            }
                        }
                        if ($fuckyou === false) {
                            $results[] = array(
                                "tracks" => array(),
                                "uri" => $d.':bodgehack'
                            );
                            $fuckyou = count($results) - 1;
                        }
                        $results[$fuckyou]['tracks'][] = $track;
                    }
                }
            }
        }
        return $results;
    }

}

function munge_youtube_track_into_artist($t) {
    // Risky, but mopidy-youtube doesn't return artists (for obvious reasons)
    if (preg_match('/^(.*?)\s*[-|\|+]\s*/', $t, $matches)) {
        if ($matches[1] !== "") {
            return array($matches[1]);
        } else {
            return array("Youtube");
        }
    } else {
        return array("Youtube");
    }
}

function munge_youtube_track_into_album($t) {
    // Even riskier, but mopidy-youtube doesn't return albums except 'Youtube' (for obvious reasons)
    if (preg_match('/^.*?\s*[-|\|+]\s*(.*?)\s+[-|\|+]\s+/', $t, $matches)) {
        if ($matches[1] !== "") {
            return $matches[1];
        }
    }

    if (preg_match('/^.*?\s*[-|\|+]\s*(.*?)\s+[\(|\[]*full album[\)|\]]*/i', $t, $matches)) {
        if ($matches[1] !== "") {
            return $matches[1];
        }
    }

    return "Youtube";

}

function munge_youtube_track_into_title($t) {
    // Risky as fuck!
    if (preg_match('/^.*?\s*[-|\|+]\s*.*?\s+[-|\|+]\s+(.*?)$/', $t, $matches)) {
        return $matches[1];
    } else {
        return $t;
    }
}

function album_from_path($p) {
    $a = rawurldecode(basename(dirname($p)));
    if ($a == ".") {
        $a = '';
    }
    return $a;
}

function artist_from_path($p, $f) {
    $a = rawurldecode(basename(dirname(dirname($p))));
    if ($a == "." || $a == "" || $a == " & ") {
        $a = ucfirst(getDomain(urldecode($f)));
    }
    return $a;
}

function is_albumlink($uri) {
    return preg_match('/^.+?:album:|^.+?:artist:/',$uri);
}

function process_file($filedata) {

    global $numtracks, $totaltime, $prefs, $dbterms, $collection, $putinplaylistarray,
            $trackbytrack, $doing_search, $raw_search;

    global $db_time, $coll_time, $rtime;

    // Pre-process the file data

    $mytime = microtime(true);

    if ($dbterms['tags'] !== null || $dbterms['rating'] !== null) {
        // If this is a search and we have tags or ratings to search for, check them here.
        if (check_url_against_database($filedata['file'], $dbterms['tags'],
            $dbterms['rating']) == false) {
            return false;
        }
    }

   if ($prefs['ignore_unplayable'] && strpos($filedata['Title'], "[unplayable]") === 0) {
        debuglog("Ignoring unplayable track ".$filedata['file'],"COLLECTION",9);
        return false;
    }
    if (strpos($filedata['Title'], "[loading]") === 0) {
        debuglog("Ignoring unloaded track ".$filedata['file'],"COLLECTION",9);
        return false;
    }
    // Spotify has taken to returning album links with no artist data... sigh.
    if ($filedata['Artist'] == null && is_albumlink($filedata['file'])) {
        debuglog("Ignoring useless album/artist link without artist name","COLLECTION",7);
        return false;
    }
    
    $filedata['domain'] = getDomain($filedata['file']);
    $unmopfile  = preg_replace('/^.+?:(track|album|artist):/', '', $filedata['file']);

    check_is_stream($filedata);

    if ($filedata['type'] != 'stream') {
        if ($filedata['Title'] == null) $filedata['Title'] = rawurldecode(basename($filedata['file']));
        if ($filedata['Album'] == null) $filedata['Album'] = album_from_path($unmopfile);
        if ($filedata['Artist'] == null) $filedata['Artist'] = array(artist_from_path($unmopfile, $filedata['file']));
    }
    if ($filedata['Track'] == null) {
        $filedata['Track'] = format_tracknum(basename(rawurldecode($filedata['file'])));
    } else {
        $filedata['Track'] = format_tracknum(ltrim($filedata['Track'], '0'));
    }

    // External Album URI (mopidy only)
    // OR cue sheet link (mpd only). We're only doing CUE sheets, not M3U
    if ($filedata['X-AlbumUri'] === null &&
            strtolower(pathinfo($filedata['playlist'], PATHINFO_EXTENSION)) == "cue") {
        $filedata['X-AlbumUri'] = $filedata['playlist'];
        debuglog("Found CUE sheet for album ".$filedata['Album'],"COLLECTION");
    }
    // Disc Number
    if ($filedata['Disc'] != null) {
        $filedata['Disc'] = format_tracknum(ltrim($filedata['Disc'], '0'));
    }

    if (strpos($filedata['file'], ':artist:') !== false) {
        debuglog("Found artist link","COLLECtION",5);
        $filedata['X-AlbumUri'] = $filedata['file'];
        $filedata['Album'] = get_int_text("label_allartist").concatenate_artist_names($filedata['Artist']);
        $filedata['Disc'] = 0;
        $filedata['Track'] = 0;
    } else if (strpos($filedata['file'], ':album:') !== false) {
        $filedata['X-AlbumUri'] = $filedata['file'];
        $filedata['Disc'] = 0;
        $filedata['Track'] = 0;
    }

    // Sometimes the file domain can be http but the album domain is correct
    // this is true eg for bassdrive
    if ($filedata['X-AlbumUri'] !== null && getDomain($filedata['X-AlbumUri']) != getDomain($filedata['file'])) {
        $filedata['domain'] = getDomain($filedata['X-AlbumUri']);
    }

    if (strpos($filedata['file'], 'archives.bassdrivearchive.com') !== false) {
        // Slightly annoyingly, bassdrive archive tracks come back with http uris.
        $filedata['domain'] = "bassdrive";
    }

    switch($filedata['domain']) {
        // Some domain-specific fixups for mpd's soundcloud playlist plugin and various unhelpful
        // mopidy backends. There's no consistency of behaviour in the Mopidy backends
        // so to provide some kind of consistency of display we have to do a lot of work.
        case "soundcloud":
            if ($prefs['player_backend'] == "mpd") {
                if ($filedata['Name'] != null) {
                    $filedata['Title'] = $filedata['Name'];
                    $filedata['Album'] = "SoundCloud";
                    $arse = explode(' - ',$filedata['Name']);
                    $filedata['Artist'] = array($arse[0]);
                } else {
                    $filedata['Artist'] = array("Unknown Artist");
                    $filedata['Title'] = "Unknown Track";
                    $filedata['Album'] = "SoundCloud";
                }
            } else {
                $filedata['folder'] = concatenate_artist_names($filedata['Artist']);
                $filedata['AlbumArtist'] = $filedata['Artist'];
            }
            break;

        case "youtube":
            $filedata['folder'] = $filedata['file'];
            $filedata['Artist'] = munge_youtube_track_into_artist($filedata['Title']);
            $filedata['Album'] = munge_youtube_track_into_album($filedata['Title']);
            $filedata['Title'] = munge_youtube_track_into_title($filedata['Title']);
            $filedata['AlbumArtist'] = $filedata['Artist'];
            break;

        case "spotify":
            $filedata['folder'] = $filedata['X-AlbumUri'];
            break;

        case "internetarchive":
            $filedata['X-AlbumUri'] = $filedata['file'];
            $filedata['folder'] = $filedata['file'];
            $filedata['AlbumArtist'] = "Internet Archive";
            break;

        case "podcast http":
        case "podcast https":
        case "podcast ftp":
        case "podcast file":
            $filedata['folder'] = dirname($file);
            $matches = array();
            $a = preg_match('/podcast\+http:\/\/(.*?)\//', $filedata['file'], $matches);
            if ($a == 1) {
                $filedata['AlbumArtist'] = array($matches[1]);
                $filedata['Album'] = $filedata['Title'];
                $filedata['Album'] = preg_replace('/^Album\:\s*/','',$filedata['Album']);
                $albumuri = $file;
            } else {
                $filedata['AlbumArtist'] = array("Podcasts");
            }
            if (is_array($filedata['Artist']) && ($filedata['Artist'][0] == "http" || $filedata['Artist'][0] == "https" ||
                $filedata['Artist'][0] == "ftp" || $filedata['Artist'][0] == "file" ||
                substr($filedata['Artist'][0],0,7) == "podcast")) {
                $filedata['Artist'] = $filedata['AlbumArtist'];
            }
            break;

        default:
            $filedata['folder'] = dirname($unmopfile);
            break;
    }

    $rtime += microtime(true) - $mytime;

    if ($trackbytrack && $filedata['AlbumArtist'] && $filedata['Disc'] !== null) {
        $tstart = microtime(true);
        do_track_by_track( new track($filedata) );
        $db_time += microtime(true) - $tstart;
    } else if ($filedata['Pos'] !== null) {
        // Playlist track. Swerve the collectioniser and use the database
        $tstart = microtime(true);
        $extrainfo = get_extra_track_info($filedata['file']);
        foreach ($extrainfo as $i => $v) {
            if ($v !== null && $v != "") {
                $filedata[$i] = $v;
            }
        }
        $db_time += microtime(true) - $tstart;
        $cstart = microtime(true);
        doNewPlaylistFile($filedata);
        $coll_time += microtime(true) - $cstart;
    } else if ($doing_search) {
        // Is this dodgy? Seems to me we get a lot of search tracks bobbling by that
        // will already be in the database and may have other things going on.
        // I don't think it'll slow us down too much i we check by Uri in this case
        $foundit = false;
        $tstart = microtime(true);
        if ($r = sql_prepare_query(
            "SELECT
                TTindex,
                isSearchResult,
                Hidden,
                Artistname
            FROM
                Tracktable JOIN Albumtable USING (Albumindex)
                JOIN Artisttable on (Albumtable.AlbumArtistindex = Artisttable.Artistindex)
            WHERE Uri = ?", $filedata['file'])) {
            while ($obj = $r->fetch(PDO::FETCH_OBJ)) {
                if ($raw_search) {
                    debuglog(" Search result has AlbumArtist = ".$filedata['AlbumArtist'].", DB has ".$obj->Artistname,"COLLECTION");
                    $filedata['AlbumArtist'] = $obj->Artistname;
                } else {
                    debuglog("Search - Found TTindex ".$obj->TTindex." already in database","COLLECTION");
                    $foundit = true;
                    if ($obj->isSearchResult == 0) {
                        debuglog("  .. marking it as a search result","COLLECTION");
                        $newsearchresult = ($obj->Hidden != 0) ? 3 : 1;
                        generic_sql_query("UPDATE Tracktable SET isSearchResult = ".$newsearchresult." WHERE TTindex = ".$obj->TTindex);
                    }
                }
            }
        }
        $db_time += microtime(true) - $tstart;
        if (!$foundit) {
            $cstart = microtime(true);
            if ($filedata['Disc'] === null) $filedata['Disc'] = 1;
            $collection->newTrack( new track($filedata) );
            $coll_time += microtime(true) - $cstart;
        }
    } else {
        $cstart = microtime(true);
        if ($filedata['Disc'] === null) $filedata['Disc'] = 1;
        $collection->newTrack( new track($filedata) );
        $coll_time += microtime(true) - $cstart;
    }

    $numtracks++;
    $totaltime += $filedata['Time'];
}

?>
