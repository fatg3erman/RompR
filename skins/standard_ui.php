<?php

require_once('utils/imagefunctions.php');

function albumTrack($data) {
    global $prefs;
    if (substr($data['title'],0,6) == "Album:") return 2;
    if (substr($data['title'],0,7) == "Artist:") return 1;

    $d = getDomain($data['uri']);

    if ($prefs['player_backend'] == "mpd" && $d == "soundcloud") {
        $class = 'clickcue';
    } else {
        $class = 'clicktrack';
    }
    $class .= $data['discclass'];

    // Outer container
    if ($data['playable'] == 1 or $data['playable'] == 3) {
        // Note - needs clicktrack and name in case it is a removeable track
        print '<div class="unplayable clicktrack ninesix indent containerbox padright" name="'.rawurlencode($data['uri']).'">';
    } else if ($data['uri'] == null) {
        print '<div class="playable '.$class.' ninesix draggable indent containerbox padright" name="'.$data['ttid'].'">';
    } else {
        print '<div class="playable '.$class.' ninesix draggable indent containerbox padright" name="'.rawurlencode($data['uri']).'">';
    }

    // Track Number
    if ($data['trackno'] && $data['trackno'] != "" && $data['trackno'] > 0) {
        print '<div class="tracknumber fixed"';
        if ($data['numtracks'] > 99 || $data['trackno'] > 99) {
            print ' style="width:3em"';
        }
        print '>'.$data['trackno'].'</div>';
    }

    print domainIcon($d, 'collectionicon');

    // Track Title, Artist, and Rating
    if ((string) $data['title'] == "") $data['title'] = urldecode($data['uri']);
    print '<div class="expand containerbox vertical">';
    print '<div class="fixed tracktitle">'.$data['title'].'</div>';
    if ($data['artist'] && $data['trackartistindex'] != $data['albumartistindex']) {
        print '<div class="fixed playlistrow2 trackartist">'.$data['artist'].'</div>';
    }
    if ($data['rating']) {
        print '<div class="fixed playlistrow2 trackrating">';
        print '<i class="icon-'.trim($data['rating']).'-stars rating-icon-small"></i>';
        print '</div>';
    }
    if ($data['tags']) {
        print '<div class="fixed playlistrow2 tracktags">';
        print '<i class="icon-tags collectionicon"></i>'.$data['tags'];
        print '</div>';
    }
    print '</div>';

    // Track Duration
    print '<div class="fixed playlistrow2 tracktime">';
    if ($data['time'] > 0) {
        print format_time($data['time']);
    }
    print '</div>';

    // Delete Button
    if ($data['lm'] === null) {
        print '<i class="icon-cancel-circled playlisticonr fixed clickable clickicon clickremdb"></i>';
    }

    print '</div>';
    if ($data['progress'] > 0) {
        print '<input type="hidden" class="resumepos" value="'.$data['progress'].'" />';
        print '<input type="hidden" class="length" value="'.$data['time'].'" />';
    }

    return 0;
}

function artistHeader($id, $name) {
    global $divtype;
    $h = '<div class="clickalbum playable draggable containerbox menuitem '.$divtype.'" name="'.$id.'">';
    $h .= '<i class="icon-toggle-closed menu mh openmenu fixed artist" name="'.$id.'"></i>';
    $h .= '<div class="expand">'.$name.'</div>';
    $h .= '</div>';
    return $h;
}

function noAlbumsHeader() {
    print '<div class="playlistrow2" style="padding-left:64px">'.
        get_int_text("label_noalbums").'</div>';
}

function albumHeader($obj) {
    global $prefs;
    $h = '';
    if ($obj['why'] === null) {
        $h .= '<div class="containerbox menuitem">';
    } else if ($obj['AlbumUri']) {
        $albumuri = rawurlencode($obj['AlbumUri']);
        if (preg_match('/spotify%3Aartist%3A/', $albumuri)) {
            $h .= '<div class="clickartist playable draggable containerbox menuitem" name="'.preg_replace('/'.get_int_text('label_allartist').'/', '', $obj['Albumname']).'">';
        } else if (strtolower(pathinfo($albumuri, PATHINFO_EXTENSION)) == "cue") {
            logger::log("FUNCTIONS", "Cue Sheet found for album ".$obj['Albumname']);
            $h .= '<div class="clickcue playable draggable containerbox menuitem" name="'.$albumuri.'">';
        } else {
            $h .= '<div class="clicktrack playable draggable containerbox menuitem" name="'.$albumuri.'">';
        }
    } else if (array_key_exists('streamuri', $obj)) {
        $h .= '<div class="clickstream playable draggable containerbox menuitem" name="'.$obj['streamuri'].'" streamname="'.$obj['streamname'].'" streamimg="'.$obj['streamimg'].'">';
    } else if (array_key_exists('userplaylist', $obj)) {
        $h .= '<div class="playable '.$obj['userplaylist'].' draggable containerbox menuitem" name="'.$obj['plpath'].'">';
    } else {
        $h .= '<div class="clickalbum playable draggable containerbox menuitem" name="'.$obj['id'].'">';
    }
    if (array_key_exists('plpath', $obj)) {
        $h .= '<input type="hidden" name="dirpath" value="'.$obj['plpath'].'" />';
    }
    if ($obj['id'] != 'nodrop') {
        $h .= '<i class="icon-toggle-closed menu openmenu mh fixed '.$obj['class'].'" name="'.$obj['id'].'"></i>';
    }

    $h .= '<div class="smallcover fixed">';
    $albumimage = new baseAlbumImage(array('baseimage' => $obj['Image']));
    $h .= $albumimage->html_for_image($obj, 'smallcover fixed', 'small');
    $h .= '</div>';

    $h .= domainHtml($obj['AlbumUri']);

    $h .= artistNameHtml($obj);

    $h .= '</div>';
    if ($obj['why'] == "a") {
        $id = preg_replace('/^.album/','',$obj['id']);
        $classes = array();;
        if (num_collection_tracks($id) == 0) {
            $classes[] = 'clickamendalbum';
            if (!album_is_audiobook($id)) {
                $classes[] = 'clicksetasaudiobook';
            }
        }
        if ($obj['AlbumUri']) {
            $classes[] = 'clickalbumoptions';
        }
        $classes[] = 'clickratedtracks';
        if (count($classes) > 0) {
            $h .= '<div class="icon-menu playlisticonr fixed clickable clickicon clickalbummenu '.implode(' ',$classes).'" name="'.$id.'"></div>';
        }
    }
    $h .= '</div>';
    return $h;
}

function albumControlHeader($fragment, $why, $what, $who, $artist) {
    return '';
}

function trackControlHeader($why, $what, $who, $dets) {
}

function printDirectoryItem($fullpath, $displayname, $prefix, $dircount, $printcontainer = false) {
    $c = ($printcontainer) ? "searchdir" : "directory";
    print '<div class="'.$c.' clickalbum playable draggable containerbox menuitem" name="'.$prefix.$dircount.'">';
    print '<input type="hidden" name="dirpath" value="'.rawurlencode($fullpath).'" />';
    print '<i class="icon-toggle-closed menu openmenu mh fixed '.$c.'" name="'.$prefix.$dircount.'"></i>';
    print '<i class="icon-folder-open-empty fixed collectionicon"></i>';
    print '<div class="expand">'.htmlentities(urldecode($displayname)).'</div>';
    print '</div>';
    if ($printcontainer) {
        print '<div class="dropmenu" id="'.$prefix.$dircount.'">';
    }
}

function directoryControlHeader($prefix) {

}

function printRadioDirectory($att) {
    $name = md5($att['URL']);
    print '<div class="directory containerbox menuitem">';
    print '<input type="hidden" value="'.rawurlencode($att['URL']).'" />';
    print '<input type="hidden" value="'.rawurlencode($att['text']).'" />';
    print '<i class="browse menu clickable mh tunein fixed icon-toggle-closed" name="tunein_'.$name.'"></i>';
    print '<i class="icon-folder-open-empty fixed collectionicon"></i>';
    print '<div class="expand">'.$att['text'].'</div>';
    print '</div>';
    print '<div id="tunein_'.$name.'" class="dropmenu"></div>';
}

function playlistPlayHeader($name, $text) {

}

function addPodcastCounts($html, $extra) {
    $out = phpQuery::newDocument($html);
    $out->find('.menuitem')->append($extra);
    return $out;
}

function addUserRadioButtons($html, $index, $uri, $name, $image) {
    $out = phpQuery::newDocument($html);
    $extra = '<div class="fixed clickable clickradioremove clickicon yourradio" name="'.$index.'"><i class="icon-cancel-circled playlisticonr"></i></div>';
    $out->find('.menuitem')->append($extra);
    return $out;
}

function addPlaylistControls($html, $delete, $is_user, $name) {
    global $prefs;
    $out = phpQuery::newDocument($html);
    if ($delete && ($is_user || $prefs['player_backend'] == "mpd")) {
        $add = ($is_user) ? "user" : "";
        $h = '<i class="icon-floppy fixed smallicon clickable clickicon clickrename'.$add.'playlist"></i>';
        $h .= '<input type="hidden" value="'.$name.'" />';
        $h .= '<i class="icon-cancel-circled fixed smallicon clickable clickicon clickdelete'.$add.'playlist"></i>';
        $h .= '<input type="hidden" value="'.$name.'" />';
        $out->find('.menuitem')->append($h);
    }
    return $out;
}

?>
