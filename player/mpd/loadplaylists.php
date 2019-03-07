<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");
include ("collection/collection.php");
include ("player/mpd/connection.php");
include ("backends/sql/backend.php");
include ("utils/phpQuery.php");
require_once ("utils/imagefunctions.php");
$used_images = array();
$pl_error = false;
if (array_key_exists('playlist', $_REQUEST)) {
    $pl = $_REQUEST['playlist'];
    do_playlist_tracks($pl,'icon-music', $_REQUEST['target']);
} else if (array_key_exists('userplaylist', $_REQUEST)) {
    $pl = $_REQUEST['userplaylist'];
    do_user_playlist_tracks($pl,'icon-music', $_REQUEST['target']);
} else if (array_key_exists('addtoplaylistmenu', $_REQUEST)) {
    $playlists = do_mpd_command("listplaylists", true, true);
    if (is_array($playlists) && array_key_exists('playlist', $playlists)) {
        usort($playlists['playlist'], "plsort");
        foreach ($playlists['playlist'] as $pl) {
            if (allow_track_deletion($pl)) {
                print '<div class="containerbox backhi clickicon menuitem clickaddtoplaylist" name="'.rawurlencode($pl).'">';
                print '<i class="fixed collectionicon icon-doc-text"></i>';
                print '<div class="expand">'.htmlentities($pl).'</div>';
                print '</div>';
            }
        }
    }
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
    } else if (is_array($playlists) && array_key_exists('error', $playlists)) {
        // Prevent unwanted deletion of playlist images when there was an error getting the list
        $pl_error = true;
    }
    $existingfiles = glob('prefs/userplaylists/*');
    foreach($existingfiles as $file) {
        add_playlist(rawurlencode(file_get_contents($file)), htmlentities(basename($file)), 'icon-doc-text', 'clickloaduserplaylist', true, $c, true, null);
        $c++;
    }
    sort($used_images);
    $imgs = glob('prefs/plimages/*');
    sort($imgs);
    if (!$pl_error) {
        $unneeded = array_diff($imgs, $used_images);
        foreach ($unneeded as $img) {
            debuglog("Removing uneeded playlist image ".$img,"PLAYLISTS");
            if (is_dir($img)) {
                rrmdir($img);
            } else {
                @unlink($img);
            }
        }
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
    playlistPlayHeader($pl, $pl);
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

function do_user_playlist_tracks($pl, $icon, $target) {
    debuglog("Downloading remote playlist ".$pl,"USERPLAYLISTS");
    // Use the MPD version of the playlist parser, since that parses all tracks,
    // which is what we want.
    require_once ("player/mpd/streamplaylisthandler.php");
    require_once ("utils/getInternetPlaylist.php");

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

    $tracks = load_internet_playlist($pl, '', '', true);
    directoryControlHeader($target, $pl_name);
    playlistPlayHeader($pl, $pl_name);
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
            $albumimage = new albumImage(array('artist' => "PLAYLIST", 'album' => $name));
            $image = $albumimage->get_image_if_exists();
            $i = dirname($albumimage->basepath);
            if (!in_array($i, $used_images)) {
                $used_images[] = $i;
            }
            $html = albumHeader(array(
                'id' => 'pholder_'.md5($name),
                'Image' => $image,
                'Searched' => 1,
                'AlbumUri' => null,
                'Year' => null,
                'Artistname' => '',
                'Albumname' => $name,
                'why' => 'whynot',
                'ImgKey' => $albumimage->get_image_key(),
                'userplaylist' => $class,
                'plpath' => $link,
                'class' => preg_replace('/clickload/', '', $class),
                'expand' => true
            ));
            $out = addPlaylistControls($html, $delete, $is_user, $name);
            print $out->html();
            break;

        case "clicktrack":
            print '<input type="hidden" value="'.$pl.'" />';
            print '<input type="hidden" value="'.$count.'" />';
            print '<div class="containerbox menuitem draggable playable clickable '.$class.' playlisttrack" name="'.$link.'">';
            print '<i class="'.$icon.' fixed collectionicon"></i>';
            print '<div class="expand">'.$name.'</div>';
            if ($delete) {
                print '<i class="icon-cancel-circled fixed playlisticonr clickable clickicon clickdeleteplaylisttrack" name="'.$count.'"></i>';
                print '<input type="hidden" value="'.$pl.'" />';
            }
            print '</div>';
            break;

        case "clickcue":
            print '<div class="containerbox meunitem draggable playable clickable '.$class.'" name="'.$link.'">';
            print '<i class="'.$icon.' fixed collectionicon"></i>';
            print '<div class="expand">'.$name.'</div>';
            print '</div>';
            break;

        default:
            debuglog("ERROR! Not permitted type passed to add_playlist", "MPD_PLAYLISTS",2);
            break;


    }
}

function do_playlist_header() {
    print '<div class="configtitle textcentre brick_wide"><b>'.get_int_text('button_loadplaylist').'</b></div>';
    print '<div class="containerbox dropdown-container fullwidth brick_wide">';
    print '<div class="fixed padright padleft"><span class="alignmid">External URL</span></div>';

    // print '<div class="expand dropdown-holder">
    //     <input class="enter" id="godfreybiggins" type="text" onkeyup="onKeyUp(event)" /></div>';
    print '<div class="expand dropdown-holder">
        <input class="enter clearbox" id="godfreybiggins" type="text" /></div>';

    print '<button class="fixed alignmid" '.
        'onclick="player.controller.loadPlaylistURL($(\'#godfreybiggins\').val())">Play</button>';
    print '</div>';
}

?>
