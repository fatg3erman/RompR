<?php
define('ROMPR_IS_LOADING', true);
require_once ("includes/vars.php");
require_once ("includes/functions.php");
prefs::$database = new collection_base();
set_version_string();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>RompЯ Album Art</title>
<link rel="shortcut icon" sizes="196x196" href="newimages/favicon-196.png" />
<link rel="shortcut icon" sizes="128x128" href="newimages/favicon-128.png" />
<link rel="shortcut icon" sizes="64x64" href="newimages/favicon-64.png" />
<link rel="shortcut icon" sizes="48x48" href="newimages/favicon-48.png" />
<link rel="shortcut icon" sizes="32x32" href="newimages/favicon-32.png" />
<link rel="shortcut icon" sizes="16x16" href="newimages/favicon-16.png" />
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />
<?php
print '<script type="application/json" name="font_sizes">'."\n".json_encode(FONT_SIZES)."\n</script>\n";
print '<script type="application/json" name="cover_sizes">'."\n".json_encode(COVER_SIZES)."\n</script>\n";
print '<script type="application/json" name="browser_prefs">'."\n".json_encode(array_keys(prefs::BROWSER_PREFS))."\n</script>\n";
print '<link rel="stylesheet" type="text/css" href="get_css.php?version='.$version_string.'&skin='.prefs::skin().'" />'."\n";
print '<link rel="stylesheet" type="text/css" href="css/albumartmanager/albumart.css?version=?'.$version_string.'" />'."\n";
?>
<link rel="stylesheet" id="theme" type="text/css" />
<link type="text/css" href="css/scrollbars/jquery.mCustomScrollbar.css" rel="stylesheet" />
<?php
$scripts = array(
	"jquery/jquery-3.6.0.min.js",
	"jquery/jquery-migrate-3.3.2.min.js",
	"jshash-2.2/md5-min.js",
	"ui/functions.js",
	"ui/prefs.js",
	"ui/language.php",
	"jquery/jquery-ui.min.js",
	"jquery/jquery.mCustomScrollbar.concat.min-3.1.5.js",
	"includes/globals.js",
	"ui/uifunctions.js",
	"ui/metahandlers.js",
	"ui/widgets.js",
	"ui/debug.js",
	"ui/coverscraper.js",
	"browser/helpers/bing.js",
	"ui/albumart.js"
);
foreach ($scripts as $i) {
	logger::mark("INIT", "Loading ".$i);
	print '<script type="text/javascript" src="'.$i.'?version='.$version_string.'"></script>'."\n";
}
javascript_globals::print_globals();
?>
</head>
<body class="desktop">
<div class="albumcovers">
<div class="infosection">
<table width="100%">
<?php
print '<tr>
		<td colspan="4"><h2>'.language::gettext("albumart_title").'</h2></td>
		<td class="outer" align="right" colspan="1"><button id="finklestein">'.language::gettext("albumart_onlyempty").'</button></td>
	</tr>';
print '<tr>
		<td class="outer" id="totaltext"></td>
		<td colspan="3"><div class="invisible" id="progress"></div></td>
		<td class="outer" align="right"><button id="harold">'.language::gettext("albumart_getmissing").'</button></td>
	</tr>';
print '<tr>
		<td colspan="4"</td>
		<td class="outer" align="right"><button id="doobag">'.language::gettext("albumart_findsmall").'</button></td>
	</tr>';
print '<tr>
		<td class="outer" id="infotext"></td>
		<td colspan="3" align="center"><div class="inner" id="status">'.language::gettext('label_loading').'</div></td>
		<td class="outer styledinputs" align="right"><input type="checkbox" class="topcheck" id="dinkytoys"><label id="dinkylabel" for="dinkytoys" onclick="toggleLocal()" disabled>Ignore Local Images</label></td>
	</tr>';

print '<tr>
		<td colspan="4"></td>
		<td class="outer styledinputs" align="right"><input type="checkbox" class="topcheck" id="poobag"><label id="poobaglabel" for="poobag" onclick="toggleScrolling()" disabled>Follow Progress</label></td>
	</tr>';
?>
</table>
</div>
</div>
<div id="wobblebottom">

<div id="artistcoverslist" class="tleft noborder">
	<div class="noselection fullwidth">
<?php
	print '<div class="containerbox menuitem clickable clickselectartist selected" id="allartists"><div class="expand" class="artistrow">'.language::gettext("albumart_allartists").'</div></div>';
	print '<div class="containerbox menuitem clickable clickselectartist" id="savedplaylists"><div class="expand" class="artistrow">Saved Playlists</div></div>';
	print '<div class="containerbox menuitem clickable clickselectartist" id="radio"><div class="expand" class="artistrow">'.language::gettext("label_yourradio").'</div></div>';
	do_artists_db_style();
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
	$lister = new sortby_artist('cartistroot');
	foreach ($lister->root_sort_query() as $artist) {
		print '<div class="containerbox menuitem clickable clickselectartist';
		print '" id="artistname'.$artist['Artistindex'].'">';
		print '<div class="expand" class="artistrow">'.$artist['Artistname'].'</div>';
		print '</div>';
	}
}

function do_covers_db_style() {
	global $count;
	global $albums_without_cover;
	$lister = new sortby_artist('cartistroot');
	foreach ($lister->root_sort_query() as $artist) {
		print '<div class="albumart_artist_holder" name="artistname'.$artist['Artistindex'].'">';
			print '<div class="albumsection">';
				print '<div class="tleft"><h2>'.$artist['Artistname'].'</h2></div><div class="tright rightpad"><button class="invisible" onclick="getNewAlbumArt(\'#album'.$count.'\')">'.language::gettext("albumart_getthese").'</button></div>';
			print "</div>\n";
				print '<div id="album'.$count.'" class="containerbox fullwidth bigholder wrap">';
				$albumlister = new sortby_artist('cartist'.$artist['Artistindex']);
				foreach ($albumlister->album_sort_query(false) as $album) {
					print '<div class="fixed albumimg closet">';
						print '<div class="covercontainer">';
							print '<input name="albumpath" type="hidden" value="'.prefs::$database->get_album_directory($album['Albumindex'], $album['AlbumUri']).'" />';
							print '<input name="searchterm" type="hidden" value="'.rawurlencode($artist['Artistname']." ".munge_album_name($album['Albumname'])).'" />';
							$album['Searched'] = 1;
							$img = new baseAlbumImage(array('baseimage' => $album['Image']));
							print $img->html_for_image($album, "clickable clickicon clickalbumcover droppable", 'medium', true, true);
							if ($img->album_has_no_image()) {
								$albums_without_cover++;
							}
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

	$playlists = prefs::$database->get_user_radio_streams();
	if (count($playlists) > 0) {
		print '<div class="albumart_artist_holder" name="radio">';
			print '<div class="albumsection">';
				print '<div class="tleft"><h2>Radio Stations</h2></div><div class="tright rightpad"><button class="invisible" onclick="getNewAlbumArt(\'#album'.$count.'\')">'.language::gettext("albumart_getthese").'</button></div>';
				print "</div>\n";
				print '<div id="album'.$count.'" class="containerbox fullwidth bigholder wrap">';
				foreach ($playlists as $file) {
					print '<div class="fixed albumimg closet">';
					print '<div class="covercontainer">';
					print '<input name="searchterm" type="hidden" value="'.rawurlencode($file['StationName']).'" />';
					print '<input name="artist" type="hidden" value="STREAM" />';
					print '<input name="album" type="hidden" value="'.rawurlencode($file['StationName']).'" />';
					$img = new baseAlbumImage(array('artist' => 'STREAM', 'album' => $file['StationName'], 'baseimage' => $file['Image']));
					print $img->html_for_image(array('Searched' => 1, 'ImgKey' => $img->get_image_key()), 'clickable clickicon clickalbumcover droppable', 'medium', true, true);
					if ($img->album_has_no_image()) {
						$albums_without_cover++;
					}
					print '<div>'.htmlentities($file['StationName']).'</div>';
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
	$player = new player();

	$playlists = $player->get_stored_playlists(false);
	if (!is_array($playlists)) {
		$playlists = array();
	}
	$plfiles = glob('prefs/userplaylists/*');
	foreach ($plfiles as $f) {
		$playlists[] = basename($f);
	}
	print '<div class="albumart_artist_holder" name="savedplaylists">';
		print '<div class="albumsection">';
			print '<div class="tleft"><h2>Saved Playlists</h2></div>';
		print "</div>\n";
			print '<div id="album'.$count.'" class="containerbox fullwidth bigholder wrap">';
			sort($playlists, SORT_STRING);
			foreach ($playlists as $pl) {
				logger::log("PLAYLISTART", "Playlist",$pl);
				print '<div class="fixed albumimg closet">';
					print '<div class="covercontainer">';
						$plsearch = preg_replace('/ \(by .*?\)$/', '', $pl);
						print '<input name = "searchterm" type="hidden" value="'.rawurlencode($plsearch).'" />';
						print '<input name="artist" type="hidden" value="PLAYLIST" />';
						print '<input name="album" type="hidden" value="'.rawurlencode($pl).'" />';
						$img = new baseAlbumImage(array('artist' => 'PLAYLIST', 'album' => $pl));
						$a = $img->get_image_if_exists('medium');
						print $img->html_for_image(array('Searched' => 1, 'ImgKey' => $img->get_image_key()), 'clickable clickicon clickalbumcover droppable playlistimage', 'medium', true, true);
						if ($img->album_has_no_image()) {
							$albums_without_cover++;
						}
						print '<div>'.htmlentities($pl).'</div>';
					print '</div>';
				print '</div>';
				$count++;
			}
		print "</div>\n";
	print "</div>\n";

}

?>
