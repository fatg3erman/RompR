<?php
chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
require_once ("international.php");
require_once ("player/".$prefs['player_backend']."/player.php");
require_once ("backends/sql/backend.php");
require_once ("utils/phpQuery.php");
require_once ("utils/imagefunctions.php");
$used_images = array();

if (array_key_exists('playlist', $_REQUEST)) {
    $pl = $_REQUEST['playlist'];
    do_playlist_tracks($pl,'icon-music', $_REQUEST['target']);

} else if (array_key_exists('userplaylist', $_REQUEST)) {
    $pl = $_REQUEST['userplaylist'];
    do_user_playlist_tracks($pl,'icon-music', $_REQUEST['target']);

} else if (array_key_exists('addtoplaylistmenu', $_REQUEST)) {
    $player = new $PLAYER_TYPE();
    foreach ($player->get_stored_playlists(true) as $pl) {
        print '<div class="containerbox backhi clickicon menuitem clickaddtoplaylist" name="'.rawurlencode($pl).'">';
        print '<i class="fixed collectionicon icon-doc-text"></i>';
        print '<div class="expand">'.htmlentities($pl).'</div>';
        print '</div>';
    }

} else {
    do_playlist_header();
    $c = 0;
    $player = new $PLAYER_TYPE();
    foreach ($player->get_stored_playlists(false) as $pl) {
        debuglog("Adding Playlist : ".$pl,"MPD PLAYLISTS",8);
        add_playlist(rawurlencode($pl), htmlentities($pl), 'icon-doc-text', 'clickloadplaylist', true, $c, false, null);
        $c++;
    }
    $existingfiles = glob('prefs/userplaylists/*');
    foreach($existingfiles as $file) {
        add_playlist(rawurlencode(file_get_contents($file)), htmlentities(basename($file)), 'icon-doc-text', 'clickloaduserplaylist', true, $c, true, null);
        $c++;
    }
    if (!$player->playlist_error) {
        sort($used_images);
        $imgs = glob('prefs/plimages/*');
        sort($imgs);
        $unneeded = array_diff($imgs, $used_images);
        foreach ($unneeded as $img) {
            debuglog("Removing uneeded playlist image ".$img,"PLAYLISTS");
            if (is_dir($img)) {
                rrmdir($img);
            } else {
                @unlink($img);
            }
        }
    } else {
        debuglog('Error when loading saved playlists','LOADPLAYLISTS');
    }
}

function do_playlist_tracks($pl, $icon, $target) {
    global $PLAYER_TYPE;
    directoryControlHeader($target, $pl);
    playlistPlayHeader($pl, $pl);
    $player = new $PLAYER_TYPE();
    $c = 0;
    if ($pl == '[Radio Streams]') {
        foreach ($player->get_stored_playlist_tracks('[Radio Streams]', 0) as list($class, $uri, $filedata)) {
            add_playlist(rawurlencode($uri), htmlentities(substr($uri, strrpos($uri, '#')+1, strlen($uri))), 'icon-radio-tower' ,'clicktrack', true, $c, false, $pl);
            $c++;
        }
    } else {
        foreach ($player->get_stored_playlist_tracks($pl, 0) as list($class, $uri, $filedata)) {
            switch ($filedata['domain']) {
                case "soundcloud":
                case "youtube":
                case "spotify":
                case "gmusic":
                    $icon = "icon-".$filedata['domain']."-circled";
                    break;

                default;
                    $icon = "icon-music";
                    break;
            }
            add_playlist(rawurlencode($uri), get_artist_track_title($filedata), $icon, $class, is_personal_playlist($pl), $c, false, $pl);
            $c++;
        }
    }
}

function get_artist_track_title($filedata) {
    if ($filedata['Album'] == ROMPR_UNKNOWN_STREAM) {
        return $filedata['file'];
    } else {
        if ($filedata['type'] == "stream") {
            return $filedata['Album'];
        } else {
            return htmlentities($filedata['Title']).'<br/><span class="playlistrow2">'.htmlentities(format_artist($filedata['Artist'])).'</span>';
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

function add_playlist($link, $name, $icon, $class, $delete, $count, $is_user, $pl) {
    global $used_images;
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

    print '<div class="expand dropdown-holder">
        <input class="enter clearbox" id="godfreybiggins" type="text" /></div>';

    print '<button class="fixed alignmid" '.
        'onclick="player.controller.loadPlaylistURL($(\'#godfreybiggins\').val())">Play</button>';
    print '</div>';
}

?>
