<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
require_once ("utils/imagefunctions.php");
include ("international.php");
include ("backends/sql/backend.php");

getWishlist();

function getWishlist() {

	global $mysqlc, $divtype, $prefs;
	if ($mysqlc === null) {
		connect_to_database();
	}

	$qstring = "SELECT
		IFNULL(r.Rating, 0) AS rating,
		".SQL_TAG_CONCAT." AS tags,
		tr.TTindex AS ttid,
		tr.Title AS title,
		tr.Duration AS time,
		tr.Albumindex AS albumindex,
		a.Artistname AS albumartist,
		tr.DateAdded AS DateAdded,
		ws.SourceName AS SourceName,
		ws.SourceImage AS SourceImage,
		ws.SourceUri AS SourceUri
		FROM
		Tracktable AS tr
		LEFT JOIN Ratingtable AS r ON tr.TTindex = r.TTindex
		LEFT JOIN TagListtable AS tl ON tr.TTindex = tl.TTindex
		LEFT JOIN Tagtable AS t USING (Tagindex)
		LEFT JOIN WishlistSourcetable AS ws USING (Sourceindex)
		JOIN Artisttable AS a ON (tr.Artistindex = a.Artistindex)
		WHERE
		tr.Uri IS NULL AND tr.Hidden = 0
		GROUP BY ttid
		ORDER BY ";

	switch ($_REQUEST['sortby']) {
		case 'artist':
			foreach ($prefs['artistsatstart'] as $a) {
				$qstring .= "CASE WHEN LOWER(albumartist) = LOWER('".$a."') THEN 1 ELSE 2 END, ";
			}
			if (count($prefs['nosortprefixes']) > 0) {
				$qstring .= "(CASE ";
				foreach($prefs['nosortprefixes'] AS $p) {
					$phpisshitsometimes = strlen($p)+2;
					$qstring .= "WHEN LOWER(albumartist) LIKE '".strtolower($p)." %' THEN LOWER(SUBSTR(albumartist,".
						$phpisshitsometimes.")) ";
				}
				$qstring .= "ELSE LOWER(albumartist) END)";
			} else {
				$qstring .= "LOWER(albumartist)";
			}
			$qstring .= ", DateAdded, SourceName";
			break;

		case 'date':
			$qstring .= "DateAdded, SourceName";
			break;

		case 'station':
			$qstring .= 'SourceName, DateAdded';
			break;

		default:
			$qstring .= "rating, DateAdded";
			break;

	}

	$result = generic_sql_query($qstring);
	if (count($result) > 0) {
		print '<div class="containerbox padright noselection"><button class="fixed infoclick plugclickable clickclearwishlist">Clear Wishlist</button><div class="expand"></div></div>';
		print '<div class="configtitle brick_wide">Sort By</div>';
		print '<div class="containerbox padright noselection">';
		print '<div class="fixed brianblessed styledinputs"><input id="wishlist_sort_artist" class="topcheck savulon" type="radio" name="sortwishlistby" value="artist"><label for="wishlist_sort_artist">'.get_int_text('label_artist').'</label></div>';
		print '<div class="fixed brianblessed styledinputs"><input id="wishlist_sort_date" class="topcheck savulon" type="radio" name="sortwishlistby" value="date"><label for="wishlist_sort_date">'.get_int_text('label_dateadded').'</label></div>';
		print '<div class="fixed brianblessed styledinputs"><input id="wishlist_sort_station" class="topcheck savulon" type="radio" name="sortwishlistby" value="station"><label for="wishlist_sort_station">'.get_int_text('label_radiostation').'</label></div>';
		print '<div class="fixed brianblessed styledinputs"><input id="wishlist_sort_rating" class="topcheck savulon" type="radio" name="sortwishlistby" value="rating"><label for="wishlist_sort_rating">'.get_int_text('label_rating').'</label></div>';
		print '</div>';
	}
	foreach ($result as $obj) {
		logger::log("WISHLIST", "Found Track",$obj['title'],"by",$obj['albumartist']);

		print '<div class="containerbox vertical robotlove" id="walbum'.$obj['albumindex'].'">';
		print '<div class="containerbox fixed">';
		if ($obj['SourceImage']) {
			print '<div class="smallcover fixed"><img class="smallcover" src="'.$obj['SourceImage'].'" /></div>';
		}
		print '<div class="expand containerbox vertical">';
		print '<div class="fixed tracktitle"><b>'.$obj['title'].'</b></div>';
		print '<div class="fixed playlistrow2 trackartist">'.$obj['albumartist'].'</div>';
		if ($obj['rating'] > 0) {
			print '<div class="fixed playlistrow2 trackrating"><i class="icon-'.$obj['rating'].'-stars rating-icon-small nopointer"></i></div>';
		}
		if ($obj['tags']) {
			print '<div class="fixed playlistrow2 tracktags"><i class="icon-tags collectionicon"></i>'.$obj['tags'].'</div>';
		}
		print '</div>';
		print '<div class="expand containerbox vertical">';
		print '<div class="fixed playlistrow2">Added On : '.date('r', strtotime($obj['DateAdded'])).'</div>';
		if ($obj['SourceUri']) {
			print '<div class="fixed playlistrow2 playable clickstream" name="'.rawurlencode($obj['SourceUri']).'" streamname="'.$obj['SourceName'].'" streamimg="'.$obj['SourceImage'].'">While Listening To : <b>'.$obj['SourceName'].'</b></div>';
		}
		print '</div>';
		print '<i class="icon-search smallicon infoclick clicksearchtrack plugclickable fixed tooltip" title="'.get_int_text('label_searchtrack').'"></i>';
		print '<input type="hidden" value="'.$obj['title'].'" />';
		print '<input type="hidden" value="'.$obj['albumartist'].'" />';
		print '<i class="icon-cancel-circled smallicon fixed clickicon clickremdb infoclick plugclickable tooltip" title="'.get_int_text('label_removefromwl').'"></i>';
		print '<input type="hidden" value="'.$obj['ttid'].'" />';
		print '</div>';
		print '</div>';
	}
}

?>
