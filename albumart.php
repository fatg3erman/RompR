<?php
define('ROMPR_IS_LOADING', true);
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");
include ("backends/sql/backend.php");
include("player/mpd/connection.php");
set_time_limit(240);
$oldmopidy = false;
$small_plugin_icons = false;
$only_plugins_on_menu = false;
$skin = "desktop";
set_version_string();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>RompЯ Album Art</title>
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />
<?php
print '<script type="application/json" name="translations">'."\n".json_encode($translations)."\n</script>\n";
print '<script type="application/json" name="prefs">'."\n".json_encode($prefs)."\n</script>\n";
print '<link rel="stylesheet" type="text/css" href="css/layout-january.css?version=?'.ROMPR_VERSION.'" />'."\n";
print '<link rel="stylesheet" type="text/css" href="skins/desktop/skin.css?version=='.ROMPR_VERSION.'" />'."\n";
print '<link rel="stylesheet" type="text/css" href="css/albumart.css?version=?'.ROMPR_VERSION.'" />'."\n";
?>
<link rel="stylesheet" id="theme" type="text/css" />
<link rel="stylesheet" id="fontsize" type="text/css" />
<link rel="stylesheet" id="fontfamily" type="text/css" />
<link rel="stylesheet" id="icontheme-theme" type="text/css" />
<link rel="stylesheet" id="icontheme-adjustments" type="text/css" />
<link type="text/css" href="css/jquery.mCustomScrollbar.css" rel="stylesheet" />
<?php
$scripts = array(
    "jquery/jquery-2.1.4.min.js",
    "jquery/jquery-migrate-1.2.1.js",
    "ui/functions.js",
    "ui/prefs.js",
    "ui/language.js",
    "jquery/jquery-ui.min.js",
    "jquery/jquery.mCustomScrollbar.concat.min.js",
    "includes/globals.js",
    "ui/uifunctions.js",
    "ui/metahandlers.js",
    "ui/widgets.js",
    "ui/debug.js",
    "ui/coverscraper.js",
    "ui/albumart.js"
);
foreach ($scripts as $i) {
    debuglog("Loading ".$i,"INIT",8);
    print '<script type="text/javascript" src="'.$i.'?version='.ROMPR_VERSION.'"></script>'."\n";
}
include ("includes/globals.php");
?>
</head>
<body class="desktop">
<div id="pset" class="invisible"></div>
<div class="albumcovers">
<div class="infosection">
<table width="100%">
<?php
print '<tr><td colspan="2"><h2>'.get_int_text("albumart_title").'</h2></td><td class="outer" align="right"><button id="doobag">'.get_int_text("albumart_findsmall").'</button></td><td class="outer" align="right"><button id="harold">'.get_int_text("albumart_getmissing").'</button></td></tr>';
print '<tr><td class="outer" id="totaltext"></td><td colspan=""><div class="invisible" id="progress"></div></td><td class="outer styledinputs" align="right"><input type="checkbox" class="topcheck" id="dinkytoys"><label for="dinkytoys" onclick="toggleLocal()">Ignore Local Images</label></td>
<td class="outer styledinputs" align="right"><input type="checkbox" class="topcheck" id="poobag"><label for="poobag" onclick="toggleScrolling()">Follow Progress</label></td></tr>';
print '<tr><td class="outer" id="infotext"></td><td colspan="2" align="center"><div class="inner" id="status">Loading...</div></td><td class="outer" align="right"><button id="finklestein">'.get_int_text("albumart_onlyempty").'</button></td></tr>';
?>
</table>
</div>
</div>
<div id="wobblebottom">

<div id="artistcoverslist" class="tleft noborder">
    <div class="noselection fullwidth">
<?php
if ($mysqlc) {
    print '<div class="containerbox menuitem clickable clickselectartist selected" id="allartists"><div class="expand" class="artistrow">'.get_int_text("albumart_allartists").'</div></div>';
    print '<div class="containerbox menuitem clickable clickselectartist" id="savedplaylists"><div class="expand" class="artistrow">Saved Playlists</div></div>';
    print '<div class="containerbox menuitem clickable clickselectartist" id="radio"><div class="expand" class="artistrow">'.get_int_text("label_yourradio").'</div></div>';
    do_artists_db_style();
}
?>
    </div>
</div>
<div id="coverslist" class="tleft noborder">

<?php

// Do Local Albums
$count = 0;
$albums_without_cover = 0;
do_covers_db_style();
do_playlists();
do_radio_stations();

print '</div>';

print "</div>\n";
print "</div>\n";
print '<script language="JavaScript">'."\n";
print 'var numcovers = '.$count.";\n";
print 'var albums_without_cover = '.$albums_without_cover.";\n";
print "</script>\n";
print "</body>\n";
print "</html>\n";

function do_artists_db_style() {
    $alist = get_list_of_artists();
    foreach ($alist as $artist) {
        print '<div class="containerbox menuitem clickable clickselectartist';
        print '" id="artistname'.$artist['Artistindex'].'">';
        print '<div class="expand" class="artistrow">'.$artist['Artistname'].'</div>';
        print '</div>';
    }
}

function do_covers_db_style() {
    global $count;
    global $albums_without_cover;
    $alist = get_list_of_artists();
    foreach ($alist as $artist) {
        print '<div class="cheesegrater" name="artistname'.$artist['Artistindex'].'">';
            print '<div class="albumsection">';
                print '<div class="tleft"><h2>'.$artist['Artistname'].'</h2></div><div class="tright rightpad"><button onclick="getNewAlbumArt(\'#album'.$count.'\')">'.get_int_text("albumart_getthese").'</button></div>';
            print "</div>\n";
                print '<div id="album'.$count.'" class="containerbox fullwidth bigholder wrap">';
                $blist = get_list_of_albums($artist['Artistindex']);
                foreach ($blist as $album) {
                    print '<div class="fixed albumimg closet">';
                        print '<div class="covercontainer">';
                            $class = "clickable clickicon clickalbumcover droppable";
                            $src = "";
                            if ($album['Image'] && $album['Image'] !== "") {
                                $src = $album['Image'];
                            } else {
                                $class = $class . " notexist";
                                $albums_without_cover++;
                            }
                            print '<input type="hidden" value="'.get_album_directory($album['Albumindex'], $album['AlbumUri']).'" />';
                            print '<input type="hidden" value="'.rawurlencode($artist['Artistname']." ".munge_album_name($album['Albumname'])).'" />';
                            print '<img class="'.$class.'" name="'.$album['ImgKey'].'"';
                            if ($src != "") {
                                print 'src="'.$src.'" ';
                            }
                            print '/>';

                            print '<div>'.$album['Albumname'].'</div>';
                        print '</div>';
                    print '</div>';
                    $count++;
                }
            print "</div>\n";
        print "</div>\n";
    }
}

function do_radio_stations() {

    global $count;
    global $albums_without_cover;

    $playlists = get_user_radio_streams();
    if (count($playlists) > 0) {
        print '<div class="cheesegrater" name="radio">';
            print '<div class="albumsection">';
                print '<div class="tleft"><h2>Radio Stations</h2></div><div class="tright rightpad"><button onclick="getNewAlbumArt(\'#album'.$count.'\')">'.get_int_text("albumart_getthese").'</button></div>';
                print "</div>\n";
                print '<div id="album'.$count.'" class="containerbox fullwidth bigholder wrap">';
                foreach ($playlists as $file) {
                    print '<div class="fixed albumimg closet">';
                    print '<div class="covercontainer">';
                    $class = "";
                    $src = "";
                    if ($file['Image']) {
                        $src = $file['Image'];
                        if ($file['Image'] == 'newimages/broadcast.svg' || $file['Image'] == 'newimages/icecast.svg') {
                            $class = " notexist";
                        }
                    } else {
                        $class = " notexist";
                        $albums_without_cover++;
                    }
                    print '<input type="hidden" value="'.rawurlencode($file['StationName']).'" />';
                    print '<img class="clickable clickicon clickalbumcover droppable'.$class.'" romprstream="'.get_stream_imgkey($file['Stationindex']).'" name="'.get_stream_imgkey($file['Stationindex']).'" src="'.$src.'" />';
                    print '<div>'.$file['StationName'].'</div>';
                    print '</div>';
                    print '</div>';
                    $count++;
                }
            print "</div>\n";
        print "</div>\n";
    }
}

function do_playlists() {

    global $count;
    global $albums_without_cover;
    
    $playlists = do_mpd_command("listplaylists", true, true);
    if (!is_array($playlists)) {
        $playlists = array();
    }
    $plfiles = glob('prefs/userplaylists/*');
    foreach ($plfiles as $f) {
        $playlists['playlist'][] = basename($f);
    }
    if (array_key_exists('playlist', $playlists)) {
        print '<div class="cheesegrater" name="savedplaylists">';
            print '<div class="albumsection">';
                print '<div class="tleft"><h2>Saved Playlists</h2></div><div class="tright rightpad"><button onclick="getNewAlbumArt(\'#album'.$count.'\')">'.get_int_text("albumart_getthese").'</button></div>';
            print "</div>\n";
                print '<div id="album'.$count.'" class="containerbox fullwidth bigholder wrap">';
                sort($playlists['playlist'], SORT_STRING);
                foreach ($playlists['playlist'] as $pl) {
                    print '<div class="fixed albumimg closet">';
                        print '<div class="covercontainer">';
                            $class = "";
                            $artname = md5(htmlentities($pl));
                            $src = "";
                            if (file_exists('prefs/plimages/'.$artname.'.jpg')) {
                                $src = 'prefs/plimages/'.$artname.'.jpg';
                            } else {
                                $class = " plimage notfound";
                                $albums_without_cover++;
                            }

                            $plsearch = preg_replace('/ \(by .*?\)$/', '', $pl);
                            print '<input type="hidden" value="'.rawurlencode($plsearch).'" />';
                            print '<img class="clickable clickicon clickalbumcover droppable'.$class.'" romprstream="'.ROMPR_PLAYLIST_KEY.'" name="'.$artname.'" src="'.$src.'" />';
                            print '<div>'.htmlentities($pl).'</div>';
                        print '</div>';
                    print '</div>';
                    $count++;
                }
                print "</div>\n";
        print "</div>\n";
    }

}

?>
