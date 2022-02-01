<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
prefs::$database = new get_wishlist();
$wishlist = prefs::$database->getwishlist($_REQUEST['sortby']);
if (count($wishlist) > 0) {
	uibits::ui_config_button([
		'label' => 'button_clearwishlist',
		'typeclass' => 'infoclick plugclickable clickclearwishlist config-button'
	]);
	uibits::ui_config_header([
		'label' => 'label_sortby'
	]);
	uibits::ui_radio([
		[
			'name' => 'sortwishlistby',
			'id' => 'wishlist_sort_artist',
			'value'=> 'artist',
			'label' => 'label_artist'
		],
		[
			'name' => 'sortwishlistby',
			'id' => 'wishlist_sort_date',
			'value'=> 'date',
			'label' => 'label_dateadded'
		],
		[
			'name' => 'sortwishlistby',
			'id' => 'wishlist_sort_station',
			'value'=> 'station',
			'label' => 'label_radiostation'
		],
		[
			'name' => 'sortwishlistby',
			'id' => 'wishlist_sort_rating',
			'value'=> 'rating',
			'label' => 'label_rating'
		]
	]);
	uibits::ui_config_header([
		'label' => 'label_wishlist'
	]);
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
		print '<div class="fixed playlistrow2 tracktags"><i class="icon-tags inline-icon"></i>'.$obj['tags'].'</div>';
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
