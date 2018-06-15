<?php

function preprocess_stream(&$filedata) {

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
            $filedata['ImgKey']) = check_radio_and_podcasts($filedata);

    if (strrpos($filedata['file'], '#') !== false) {
        # Fave radio stations added by Cantata/MPDroid
        $filedata['Album'] = substr($filedata['file'], strrpos($filedata['file'], '#')+1, strlen($filedata['file']));
    }

    if (strpos($filedata['file'], 'bassdrive.com') !== false) {
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

function preprocess_soundcloud(&$filedata) {
    $filedata['folder'] = concatenate_artist_names($filedata['Artist']);
    $filedata['AlbumArtist'] = $filedata['Artist'];
    $filedata['X-AlbumUri'] = $filedata['file'];
    $filedata['Album'] = $filedata['Title'];
    $filedata['X-AlbumImage'] = 'getRemoteImage.php?url='.$filedata['X-AlbumImage'];
}

function check_radio_and_podcasts($filedata) {

    $url = $filedata['file'];

    // Do podcasts first. Podcasts played fro TuneIn get added as radio stations, and then if we play that track again
    // via podcasts we want to make sure we pick up the details.

    $result = find_podcast_track_from_url($url);
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

    $result = find_radio_track_from_url($url);
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
        ($filedata['X-AlbumImage'] == null) ? 'newimages/broadcast.svg' : $filedata['X-AlbumImage'],
        getDummyStation(unwanted_array($url)),
        null,
        $filedata['AlbumArtist'],
        null,
        array_key_exists('Comment', $filedata) ? $filedata['Comment'] : '',
        null
    );

}

?>
