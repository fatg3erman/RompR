<?php
$streamdomains = array(
    "http", "https", "mms", "mmsh", "mmst", "mmsu", "gopher", "rtp", "rtsp", "rtmp", "rtmpt",
    "rtmps", "dirble", "tunein", "radio-de", "audioaddict", "oe1");

function check_is_stream(&$filedata) {
    global $streamdomains;
    if (in_array($filedata['domain'], $streamdomains) &&
        !preg_match('#/item/\d+/file$#', $filedata['file']) &&
        strpos($filedata['file'], 'vk.me') === false &&
        strpos($filedata['file'], 'oe1:archive') === false &&
        strpos($filedata['file'], 'http://leftasrain.com') === false)
    {

        $filedata['Track'] = null;

        list (  $filedata['Title'],
                $filedata['Time'],
                $filedata['Artist'],
                $filedata['Album'],
                $filedata['folder'],
                $filedata['type'],
                $filedata['X-AlbumImage'],
                $filedata['station'],
                $filedata['stream'],
                $filedata['AlbumArtist'],
                $filedata['StreamIndex'],
                $filedata['Comment'],
                $filedata['ImgKey']) = getStuffFromXSPF($filedata);

        if (strrpos($filedata['file'], '#') !== false) {
            # Fave radio stations added by Cantata/MPDroid
            $filedata['Album'] = substr($filedata['file'], strrpos($filedata['file'], '#')+1, strlen($filedata['file']));
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

        if (strpos($filedata['file'], 'bassdrive.com') !== false) {
            // Slightly annoyingly, bassdrive archive tracks come back with http uris.
            $filedata['domain'] = "bassdrive";
            $filedata['Album'] = 'Bassdrive';
        }

        // Mopidy's podcast backend
        if ($filedata['Genre'] == "Podcast") {
            $filedata['type'] = "podcast";
        }

        if (preg_match('/^http:/', $filedata['X-AlbumImage'])) {
            $filedata['X-AlbumImage'] = "getRemoteImage.php?url=".$filedata['X-AlbumImage'];
        }
    }
}


function getStuffFromXSPF($filedata) {

    $url = $filedata['file'];

    $result = sql_prepare_query(false, PDO::FETCH_OBJ, null, null,
                                "SELECT
                                    Stationindex, PlaylistUrl, StationName, Image, PrettyStream
                                    FROM
                                    RadioStationtable JOIN RadioTracktable USING (Stationindex)
                                    WHERE TrackUri = ?",$url);
    foreach ($result as $obj) {
        debuglog("Found Radio Station ".$obj->StationName,"STREAMHANDLER");
        // Munge munge munge to make it looks pretty
        if ($obj->StationName != '') {
            debuglog("  Setting Album name from database ".$obj->StationName,"STREAMHANDLER");
            $album = $obj->StationName;
        } else if ($filedata['Name'] && $filedata['Name'] != 'no name' && strpos($filedata['Name'], ' ') !== false) {
            debuglog("  Setting Album from Name ".$filedata['Name'],"STREAMHANDLER");
            $album = $filedata['Name'];
        } else if ($filedata['Name'] == null && $filedata['Title'] != null && $filedata['Title'] != 'no name' &&
            $filedata['Artist'] == null && $filedata['Album'] == null && strpos($filedata['Title'], ' ') !== false) {
            debuglog("  Setting Album from Title ".$filedata['Title'],"STREAMHANDLER");
            $album = $filedata['Title'];
            $filedata['Title'] = null;
        } else {
            debuglog("  No information to set Album field","STREAMHANDLER");
            $album = ROMPR_UNKNOWN_STREAM;
        }
        return array (
            $filedata['Title'] === null ? '' : $filedata['Title'],
            0,
            $filedata['Artist'],
            $album,
            $obj->PlaylistUrl,
            "stream",
            ($obj->Image == '') ? $filedata['X-AlbumImage'] : $obj->Image,
            getDummyStation($url),
            $obj->PrettyStream,
            $filedata['AlbumArtist'],
            $obj->Stationindex,
            array_key_exists('Comment', $filedata) ? $filedata['Comment'] : '',
            get_stream_imgkey($obj->Stationindex)
        );
    }

    $result = sql_prepare_query(false, PDO::FETCH_OBJ, null, null,
                                "SELECT
                                    PodcastTracktable.Title AS title,
                                    PodcastTracktable.Artist AS artist,
                                    PodcastTracktable.Duration AS duration,
                                    PodcastTracktable.Description AS comment,
                                    Podcasttable.Title AS album,
                                    Podcasttable.Artist AS albumartist,
                                    Podcasttable.Image AS image
                                    FROM PodcastTracktable JOIN Podcasttable USING (PODindex)
                                    WHERE PodcastTracktable.Link=?",$url);
    foreach ($result as $obj) {
        debuglog("Found PODCAST ".$obj->title,"STREAMHANDLER");
        return array(
            ($obj->title == '') ? $filedata['Title'] : $obj->title,
            // Mopidy's estimate of the duration is frequently more accurate than that supplied in the RSS
            (array_key_exists('Time', $filedata) && $filedata['Time'] > 0) ? $filedata['Time'] : $obj->duration,
            ($obj->artist == '') ? $filedata['Artist'] : array($obj->artist),
            ($obj->album == '') ? $filedata['Album'] : $obj->album,
            md5($obj->album),
            'podcast',
            $obj->image,
            null,
            '',
            ($obj->albumartist == '') ? $filedata['AlbumArtist'] : array($obj->albumartist),
            null,
            format_text($obj->comment),
            null
        );
    }

    if (preg_match('#prefs/podcasts/(\d+)/(\d+)/(.*)$#', $url, $matches)) {
        $result = sql_prepare_query(false, PDO::FETCH_OBJ, null, null,
                                    "SELECT
                                        PodcastTracktable.Title AS title,
                                        PodcastTracktable.Artist AS artist,
                                        PodcastTracktable.Duration AS duration,
                                        PodcastTracktable.Description AS comment,
                                        Podcasttable.Title AS album,
                                        Podcasttable.Artist AS albumartist,
                                        Podcasttable.Image AS image
                                        FROM PodcastTracktable JOIN Podcasttable USING (PODindex)
                                        WHERE PodcastTracktable.Localfilename=? AND PodcastTracktable.PODindex=? AND PodcastTracktable.PODTrackindex=?",$matches[3],$matches[1],$matches[2]);
        foreach ($result as $obj) {
            debuglog("Found PODCAST ".$obj->title,"STREAMHANDLER");
            return array(
                ($obj->title == '') ? $filedata['Title'] : $obj->title,
                (array_key_exists('Time', $filedata) && $filedata['Time'] > 0) ? $filedata['Time'] : $obj->duration,
                ($obj->artist == '') ? $filedata['Artist'] : array($obj->artist),
                ($obj->album == '') ? $filedata['Album'] : $obj->album,
                md5($obj->album),
                'podcast',
                $obj->image,
                null,
                '',
                ($obj->albumartist == '') ? $filedata['AlbumArtist'] : array($obj->albumartist),
                null,
                format_text($obj->comment),
                null
            );
        }
    }

    debuglog("Stream Track ".$filedata['file']." from ".$filedata['domain']." was not found in stored library","STREAMHANDLER",5);

    if ($filedata['Album']) {
        $album = $filedata['Album'];
    } else if ($filedata['Name']) {
        debuglog("  Setting Album from Name ".$filedata['Name'],"STREAMHANDLER");
        $album = $filedata['Name'];
    } else if ($filedata['Name'] == null && $filedata['Title'] != null && $filedata['Artist'] == null && $filedata['Album'] == null) {
        debuglog("  Setting Album from Title ".$filedata['Title'],"STREAMHANDLER");
        $album = $filedata['Title'];
        $filedata['Title'] = null;
    } else {
        debuglog("  No information to set Album field","STREAMHANDLER");
        $album = ROMPR_UNKNOWN_STREAM;
    }
    return array(
        $filedata['Title'],
        0,
        $filedata['Artist'],
        $album,
        getStreamFolder(unwanted_array($url)),
        "stream",
        $filedata['X-AlbumImage'],
        getDummyStation(unwanted_array($url)),
        null,
        $filedata['AlbumArtist'],
        null,
        array_key_exists('Comment', $filedata) ? $filedata['Comment'] : '',
        null
    );

}

function getStreamFolder($url) {
    $f = dirname($url);
    if ($f == "." || $f == "") $f = $url;
    return $f;
}

function getDummyStation($url) {
    $f = getDomain($url);
    switch ($f) {
        case "http":
        case "https":
        case "mms":
        case "mmsh":
        case "mmst":
        case "mmsu":
        case "gopher":
        case "rtp":
        case "rtsp":
        case "rtmp":
        case "rtmpt":
        case "rtmps":
            return "Radio";
            break;

        default:
            return ucfirst($f);
            break;
    }
}


?>
