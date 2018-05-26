<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");
include ("collection/collection.php");
include ("player/mpd/connection.php");
include ("backends/sql/backend.php");
include ("utils/phpQuery.php");
$used_images = array();

if (array_key_exists('playlist', $_REQUEST)) {
    $pl = $_REQUEST['playlist'];
    do_playlist_tracks($pl,'icon-music', $_REQUEST['target']);
} else {
    do_playlist_header();
    $playlists = do_mpd_command("listplaylists", true, true);
    $c = 0;
    if (is_array($playlists) && array_key_exists('playlist', $playlists)) {
        usort($playlists['playlist'], "plsort");
        foreach ($playlists['playlist'] as $pl) {
            debuglog("Adding Playlist : ".$pl,"MPD PLAYLISTS",8);
            add_playlist(rawurlencode($pl), htmlentities($pl), 'icon-doc-text', 'clickloadplaylist', true, $c, false, null);
            $c++;
        }
    }
    $existingfiles = glob('prefs/userplaylists/*');
    foreach($existingfiles as $file) {
        add_playlist(rawurlencode(file_get_contents($file)), htmlentities(basename($file)), 'icon-doc-text', 'clickloaduserplaylist', true, $c, true, null);
        $c++;
    }
    sort($used_images);
    $imgs = glob('prefs/plimages/*.jpg');
    sort($imgs);
    $unneeded = array_diff($imgs, $used_images);
    foreach ($unneeded as $img) {
        debuglog("Removing uneeded playlist image ".$img,"PLAYLISTS");
        system('rm '.$img);
    }
}

function plsort($a, $b) {
    if ($a == "Discover Weekly (by spotifydiscover)") {
        return -1;
    }
    if ($b == "Discover Weekly (by spotifydiscover)") {
        return 1;
    }
    return (strtolower($a) < strtolower($b)) ? -1 : 1;
}

function do_playlist_tracks($pl, $icon, $target) {
    global $putinplaylistarray, $playlist;
    directoryControlHeader($target, $pl);
    playlistPlayHeader($pl);
    if ($pl == '[Radio Streams]') {
        $streams = do_mpd_command('listplaylistinfo "'.$pl.'"', true);
        if (is_array($streams) && array_key_exists('file', $streams)) {
            if (!is_array($streams['file'])) {
                $temp = $streams['file'];
                $streams = array();
                $streams['file'][0] = $temp;
            }
            $c = 0;
            foreach ($streams['file'] as $st) {
                add_playlist(rawurlencode($st), htmlentities(substr($st, strrpos($st, '#')+1, strlen($st))), 'icon-radio-tower' ,'clicktrack', true, $c, false, $pl);
                $c++;
            }
        }
    } else {
        $putinplaylistarray = true;
        doCollection('listplaylistinfo "'.$pl.'"');
        $c = 0;
        foreach($playlist as $track) {
            list($class, $link) = $track->get_checked_url();
            $d = getDomain($link);
            $icon = '';
            switch ($d) {
                case "soundcloud":
                case "youtube":
                case "spotify":
                case "gmusic":
                    $icon = "icon-".$d."-circled";
                    break;

                default;
                    $icon = "icon-music";
                    break;

            }
            add_playlist(rawurlencode($link), $track->get_artist_track_title(), $icon, $class, allow_track_deletion($pl), $c, false, $pl);
            $c++;
        }
    }
}

function allow_track_deletion($pl) {
    global $prefs;
    if ($prefs['player_backend'] == 'mpd') {
        return true;
    }
    if (preg_match('/ \(by/i', $pl)) {
        // Don't permit deletion of tracks from other peple's playlists (Spotify)
        return false;
    }
    return true;
}

function add_playlist($link, $name, $icon, $class, $delete, $count, $is_user, $pl) {
    global $prefs, $used_images;
    switch ($class) {
        case 'clickloadplaylist':
        case 'clickloaduserplaylist':
            $imgkey = md5($name);
            $image = null;
            if (file_exists('prefs/plimages/'.$imgkey.'.jpg')) {
                $used_images[] = "prefs/plimages/".$imgkey.".jpg";
                $image = "prefs/plimages/".$imgkey.".jpg";
            }
            $html = albumHeader(array(
                'id' => 'pholder_'.$name,
                'Image' => $image,
                'Searched' => 1,
                'AlbumUri' => null,
                'Year' => null,
                'Artistname' => '',
                'Albumname' => $name,
                'why' => 'whynot',
                'ImgKey' => $imgkey,
                'userplaylist' => $class,
                'plpath' => $link
            ));
            $out = phpQuery::newDocument($html);
            if ($delete && ($is_user || $prefs['player_backend'] == "mpd")) {
                $add = ($is_user) ? "user" : "";
                $h = '<i class="icon-floppy fixed smallicon clickable clickicon clickrename'.$add.'playlist"></i>';
                $h .= '<input type="hidden" value="'.$name.'" />';
                $h .= '<i class="icon-cancel-circled fixed smallicon clickable clickicon clickdelete'.$add.'playlist"></i>';
                $h .= '<input type="hidden" value="'.$name.'" />';
                $out->find('.menuitem')->append($h);
            }
            print $out->html();
            break;

        case "clicktrack":
            print '<div class="containerbox menuitem draggable clickable '.$class.'" name="'.$link.'">';
            print '<i class="'.$icon.' fixed smallicon"></i>';
            print '<div class="expand">'.$name.'</div>';
            if ($delete) {
                print '<i class="icon-cancel-circled fixed playlisticonr clickable clickicon clickdeleteplaylisttrack" name="'.$count.'"></i>';
                print '<input type="hidden" value="'.$pl.'" />';
            }
            print '</div>';
            break;

        case "clickcue":
            print '<div class="containerbox meunitem draggable clickable '.$class.'" name="'.$link.'">';
            print '<i class="'.$icon.' fixed smallicon"></i>';
            print '<div class="expand">'.$name.'</div>';
            print '</div>';
            break;

        default:
            debuglog("ERROR! Not permitted type passed to add_playlist", "MPD_PLAYLISTS",2);
            break;


    }
}

function do_playlist_header() {
    print '<div class="configtitle textcentre"><b>'.get_int_text('button_loadplaylist').'</b></div>';
    print '<div class="containerbox dropdown-container fullwidth">';
    print '<div class="fixed padright padleft"><span class="alignmid">External URL</span></div>';
    print '<div class="expand dropdown-holder">
        <input class="enter" id="godfreybiggins" type="text" onkeyup="onKeyUp(event)" /></div>';
    print '<button class="fixed alignmid" '.
        'onclick="player.controller.loadPlaylistURL($(\'#godfreybiggins\').val())">Play</button>';
    print '</div>';
}

?>
