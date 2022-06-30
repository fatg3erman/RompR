<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
prefs::$database = new unplayable_tracks();
$result = prefs::$database->get_unplayable_tracks();
if (count($result) == 0) {
	print '<h3>'.language::gettext('label_no_unplayable').'</h3>';
	exit(0);
} else {
	foreach ($result as $track) {
		print '<div id="utrack'.$track['TTindex'].'" class="containerbox vertical robotlove">';
		print '<div class="containerbox fixed">';
		print '<div class="smallcover fixed">';
		if ($track['Image']) {
			print '<img class="smallcover" src="'.$track['Image'].'" />';
		} else {
			print '<img class="smallcover notfound" />';
		}
		print '</div>';

		print '<div class="expand containerbox vertical">';
		print '<div class="fixed tracktitle"><b>'.$track['Title'].'</b></div>';
		if (in_array($track['AlbumArtist'], prefs::get_pref('artistsatstart'))) {
			print '<div class="fixed playlistrow2 trackartist">'.$track['Artistname'].'</div>';
		} else {
			print '<div class="fixed playlistrow2 trackartist">'.$track['AlbumArtist'].'</div>';
		}
		print '<div class="fixed playlistrow2 trackartist">'.$track['Albumname'].'</div>';
		if ($track['rating'] > 0) {
			print '<div class="fixed playlistrow2 trackrating"><i class="icon-'.$track['rating'].'-stars rating-icon-small nopointer"></i></div>';
		}
		if ($track['tags']) {
			print '<div class="fixed playlistrow2 tracktags"><i class="icon-tags inline-icon"></i>'.$track['tags'].'</div>';
		}
		print '</div>';

		print '<i class="icon-search smallicon infoclick clicksearchtrack plugclickable fixed tooltip spinable" title="'.language::gettext('label_searchtrack').'"></i>';
		print '<input type="hidden" value="'.$track['Title'].'" />';
		if (in_array($track['AlbumArtist'], prefs::get_pref('artistsatstart'))) {
			print '<input type="hidden" value="'.$track['Artistname'].'" />';
		} else {
			print '<input type="hidden" value="'.$track['AlbumArtist'].'" />';
		}
		print '<input type="hidden" value="'.$track['TTindex'].'" />';
		print '<i class="icon-cancel-circled smallicon fixed clickicon clickremdb infoclick plugclickable tooltip" title="'.language::gettext('label_removefromcol').'"></i>';
		print '<input type="hidden" value="'.$track['TTindex'].'" />';

		print '</div>';
		print '</div>';
	}
}

?>