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
    if ($data['uri'] == null) {
        print '<div class="clickable '.$class.' ninesix draggable indent containerbox padright calign" name="'.$data['ttid'].'">';
    } else {
        print '<div class="clickable '.$class.' ninesix draggable indent containerbox padright calign" name="'.rawurlencode($data['uri']).'">';
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

    return 0;
}

function artistHeader($id, $name) {
    global $divtype;
    $h = '<div class="menu containerbox menuitem artist '.$divtype.'" name="'.$id.'">';
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
    if ($obj['id'] == 'nodrop') {
        // Hacky at the moment, we only use nodrop for streams but here there is no checking
        // because I'm lazy.
        $h .= '<div class="clickable clickstream clickicon containerbox menuitem '.$obj['class'].'" name="'.$obj['streamuri'].'" streamname="'.$obj['streamname'].'" streamimg="'.$obj['streamimg'].'">';
    } else {
        if (array_key_exists('plpath', $obj)) {
            $h .= '<input type="hidden" name="dirpath" value="'.$obj['plpath'].'" />';
        }
        $h .= '<div class="menu containerbox menuitem '.$obj['class'].'" name="'.$obj['id'].'">';
    }

    $i = $obj['Image'];
    $h .= '<div class="smallcover fixed">';
    $extra = (array_key_exists('userplaylist', $obj)) ? ' plimage' : '';
    if (!$obj['Image'] && $obj['Searched'] != 1) {
        $h .= '<img class="smallcover fixed notexist'.$extra.'" name="'.$obj['ImgKey'].'" />'."\n";
    } else  if (!$obj['Image'] && $obj['Searched'] == 1) {
        $h .= '<img class="smallcover fixed notfound'.$extra.'" name="'.$obj['ImgKey'].'" />'."\n";
    } else {
        $h .= '<img class="smallcover fixed'.$extra.'" name="'.$obj['ImgKey'].'" src="'.$i.'" />'."\n";
    }
    $h .= '</div>';

    if ($obj['AlbumUri']) {
        $d = getDomain($obj['AlbumUri']);
        $d = preg_replace('/\+.*/','', $d);
        $h .= domainIcon($d, 'collectionicon');
        if (strtolower(pathinfo($obj['AlbumUri'], PATHINFO_EXTENSION)) == "cue") {
            $h .= '<i class="icon-doc-text playlisticon fixed"></i>';
        }
    }

    if ($prefs['sortcollectionby'] == 'albumbyartist' && $obj['Artistname']) {
        $h .= '<div class="expand">'.$obj['Albumname'];
        $h .= '<br><span class="notbold">'.$obj['Artistname'].'</span>';
        if ($obj['Year'] && $prefs['sortbydate']) {
            $h .= ' <span class="notbold">('.$obj['Year'].')</span>';
        }
        $h .= '</div>';
    } else {
        $h .= '<div class="expand">'.$obj['Albumname'];
        if ($obj['Year'] && $prefs['sortbydate']) {
            $h .= ' <span class="notbold">('.$obj['Year'].')</span>';
        }
        if ($obj['Artistname']) {
            $h .= '<br><span class="notbold">'.$obj['Artistname'].'</span>';
        }
        $h .= '</div>';
    }
    $h .= '</div>';
    return $h;
}

function albumControlHeader($fragment, $why, $what, $who, $artist) {
    if ($fragment || $who == 'root') {
        return '';
    }
    $html = '<div class="menu backmenu" name="'.$why.'artist'.$who.'">';
    $html .='</div>';
    $html .= '<div class="configtitle textcentre"><b>'.$artist.'</b></div>';
    $html .= '<div class="textcentre clickable clickalbum ninesix noselect" name="'.$why.'artist'.$who.'">'.get_int_text('label_play_all').'</div>';
    return $html;
}

function trackControlHeader($why, $what, $who, $dets) {
    $html = '<div class="menu backmenu" name="'.$why.$what.$who.'"></div>';
    foreach ($dets as $det) {
        $albumimage = new baseAlbumImage(array('baseimage' => $det['Image']));
        $images = $albumimage->get_images();
        $html .= '<div class="album-menu-header"><img class="album_menu_image" src="'.$images['asdownloaded'].'" /></div>';
        if ($why != '') {
            $html .= '<div class="textcentre ninesix playlistrow2">'.get_int_text('label_play_options').'</div>';
            $html .= '<div class="containerbox wrap album-play-controls">';
            if ($det['AlbumUri']) {
                $albumuri = rawurlencode($det['AlbumUri']);
                if (strtolower(pathinfo($albumuri, PATHINFO_EXTENSION)) == "cue") {
                    $html .= '<div class="icon-no-response-playbutton smallicon expand clickable clickcue fakedouble noselect" name="'.$albumuri.'"></div>';
                } else {
                    $html .= '<div class="icon-no-response-playbutton smallicon expand clickable clicktrack fakedouble noselect" name="'.$albumuri.'"></div>';
                    $html .= '<div class="icon-music smallicon expand clickable clickalbum noselect" name="'.$why.'album'.$who.'"></div>';
}
            } else {
                $html .= '<div class="icon-no-response-playbutton smallicon expand clickable clickalbum fakedouble noselect" name="'.$why.'album'.$who.'"></div>';
            }
            $html .= '<div class="icon-single-star smallicon expand clickable clickicon clickable clickalbum fakedouble noselect" name="ralbum'.$who.'"></div>';
            $html .= '<div class="icon-tags smallicon expand clickable clickicon clickable clickalbum fakedouble noselect" name="talbum'.$who.'"></div>';
            $html .= '<div class="icon-ratandtag smallicon expand clickable clickicon clickable clickalbum fakedouble noselect" name="yalbum'.$who.'"></div>';
            $html .= '<div class="icon-ratortag smallicon expand clickable clickicon clickable clickalbum fakedouble noselect" name="ualbum'.$who.'"></div>';
            $html .= '</div>';
            $html .= '<div class="textcentre ninesix playlistrow2">'.ucfirst(get_int_text('label_tracks')).'</div>';
        }
    }
    print $html;
}

function printDirectoryItem($fullpath, $displayname, $prefix, $dircount, $printcontainer = false) {
    $c = ($printcontainer) ? "searchdir" : "directory";
    print '<input type="hidden" name="dirpath" value="'.rawurlencode($fullpath).'" />';
    print '<div class="'.$c.' menu containerbox menuitem" name="'.$prefix.$dircount.'">';
    print '<i class="icon-folder-open-empty fixed collectionitem"></i>';
    print '<div class="expand">'.htmlentities(urldecode($displayname)).'</div>';
    print '</div>';
    if ($printcontainer) {
        print '<div class="dropmenu" id="'.$prefix.$dircount.'"><div class="menu backmenu" name="'.$prefix.$dircount.'"></div>';
    }
}

function directoryControlHeader($prefix, $name = null) {
    print '<div class="menu backmenu" name="'.trim($prefix, '_').'"></div>';
    if ($name !== null) {
        print '<div class="textcentre"><b>'.$name.'</b></div>';
    }
}

function printRadioDirectory($att) {
    $name = md5($att['URL']);
    print '<input type="hidden" value="'.rawurlencode($att['URL']).'" />';
    print '<input type="hidden" value="'.rawurlencode($att['text']).'" />';
    print '<div class="browse menu directory containerbox menuitem" name="tunein_'.$name.'">';
    print '<i class="icon-folder-open-empty fixed collectionitem"></i>';
    print '<div class="expand">'.$att['text'].'</div>';
    print '</div>';
    print '<div id="tunein_'.$name.'" class="dropmenu"></div>';
}

function playlistPlayHeader($name) {
    print '<div class="textcentre clickable clickloadplaylist ninesix" name="'.$name.'">'.get_int_text('label_play_all');
    print '<input type="hidden" name="dirpath" value="'.$name.'" />';
    print '</div>';
}

function addPodcastCounts($html, $extra) {
    $out = phpQuery::newDocument($html);
    $out->find('.menuitem')->append($extra);
    return $out;
}

function addUserRadioButtons($html, $index, $uri, $name, $image) {
    $out = phpQuery::newDocument($html);
    $extra = '<div class="fixed clickable clickradioremove clickicon" name="'.$index.'"><i class="icon-cancel-circled playlisticonr"></i></div>';
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
