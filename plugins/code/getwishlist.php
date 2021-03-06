<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
prefs::$database = new get_wishlist();
$wishlist = prefs::$database->getwishlist($_REQUEST['sortby']);
if (count($wishlist) > 0) {
	print '<div class="containerbox padright noselection"><button class="fixed infoclick plugclickable clickclearwishlist">Clear Wishlist</button><div class="expand"></div></div>';
	print '<div class="configtitle brick_wide">Sort By</div>';
	print '<div class="containerbox padright noselection">';
	print '<div class="fixed brianblessed styledinputs"><input id="wishlist_sort_artist" class="topcheck savulon" type="radio" name="sortwishlistby" value="artist"><label for="wishlist_sort_artist">'.language::gettext('label_artist').'</label></div>';
	print '<div class="fixed brianblessed styledinputs"><input id="wishlist_sort_date" class="topcheck savulon" type="radio" name="sortwishlistby" value="date"><label for="wishlist_sort_date">'.language::gettext('label_dateadded').'</label></div>';
	print '<div class="fixed brianblessed styledinputs"><input id="wishlist_sort_station" class="topcheck savulon" type="radio" name="sortwishlistby" value="station"><label for="wishlist_sort_station">'.language::gettext('label_radiostation').'</label></div>';
	print '<div class="fixed brianblessed styledinputs"><input id="wishlist_sort_rating" class="topcheck savulon" type="radio" name="sortwishlistby" value="rating"><label for="wishlist_sort_rating">'.language::gettext('label_rating').'</label></div>';
	print '</div>';
}
foreach ($wishlist as $obj) {
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
	print '<i class="icon-search smallicon infoclick clicksearchtrack plugclickable fixed tooltip" title="'.language::gettext('label_searchtrack').'"></i>';
	print '<input type="hidden" value="'.$obj['title'].'" />';
	print '<input type="hidden" value="'.$obj['albumartist'].'" />';
	print '<i class="icon-cancel-circled smallicon fixed clickicon clickremdb infoclick plugclickable tooltip" title="'.language::gettext('label_removefromwl').'"></i>';
	print '<input type="hidden" value="'.$obj['ttid'].'" />';
	print '</div>';
	print '</div>';
}
?>
