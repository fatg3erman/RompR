<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
require_once ("utils/imagefunctions.php");
include ("international.php");
include ("backends/sql/backend.php");

$qstring = "SELECT
	IFNULL(r.Rating, 0) AS rating,
	".SQL_TAG_CONCAT." AS tags,
	IFNULL(p.Playcount, 0) AS playcount,
	tr.TTindex,
	Title,
	Albumname,
	ta.Artistname,
	aa.Artistname AS AlbumArtist,
	Image
	FROM
	Tracktable AS tr
	LEFT JOIN Ratingtable AS r ON tr.TTindex = r.TTindex
	LEFT JOIN TagListtable AS tl ON tr.TTindex = tl.TTindex
	LEFT JOIN Tagtable AS t USING (Tagindex)
	LEFT JOIN Playcounttable AS p ON tr.TTindex = p.TTindex
	JOIN Artisttable AS ta USING (Artistindex)
	JOIN Albumtable USING (Albumindex)
	JOIN Artisttable AS aa ON (Albumtable.AlbumArtistindex = aa.Artistindex)
	WHERE LinkChecked = 1 OR LinkChecked = 3
	GROUP BY tr.TTindex
	ORDER BY aa.Artistname, Albumname, TrackNo";

$result = generic_sql_query($qstring);
if (count($result) == 0) {
	print '<h3>'.get_int_text('label_no_unplayable').'</h3>';
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
		if (in_array($track['AlbumArtist'], $prefs['artistsatstart'])) {
			print '<div class="fixed playlistrow2 trackartist">'.$track['Artistname'].'</div>';
		} else {
			print '<div class="fixed playlistrow2 trackartist">'.$track['AlbumArtist'].'</div>';
		}
		print '<div class="fixed playlistrow2 trackartist">'.$track['Albumname'].'</div>';
		if ($track['rating'] > 0) {
			print '<div class="fixed playlistrow2 trackrating"><i class="icon-'.$track['rating'].'-stars rating-icon-small nopointer"></i></div>';
		}
		if ($track['tags']) {
			print '<div class="fixed playlistrow2 tracktags"><i class="icon-tags collectionicon"></i>'.$track['tags'].'</div>';
		}
		print '</div>';

		print '<i class="icon-search smallicon infoclick clicksearchtrack plugclickable fixed tooltip spinable" title="'.get_int_text('label_searchtrack').'"></i>';
		print '<input type="hidden" value="'.$track['Title'].'" />';
		if (in_array($track['AlbumArtist'], $prefs['artistsatstart'])) {
			print '<input type="hidden" value="'.$track['Artistname'].'" />';
		} else {
			print '<input type="hidden" value="'.$track['AlbumArtist'].'" />';
		}
		print '<i class="icon-cancel-circled smallicon fixed clickicon clickremdb infoclick plugclickable tooltip" title="'.get_int_text('label_removefromcol').'"></i>';
		print '<input type="hidden" value="'.$track['TTindex'].'" />';

		print '</div>';
		print '</div>';
	}
}

?>