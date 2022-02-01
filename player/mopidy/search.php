<?php
require_once("skins/search.php");

startAdvSearchOptions();

uibits::ui_checkbox([
	'id' => 'searchcollectiononly',
	'label' => 'label_searchcollectiononly'
]);

// print '<div class="styledinputs" style="padding-top:4px">';
// print '<input class="autoset toggle" type="checkbox" id="searchcollectiononly">
// <label for="searchcollectiononly">'.language::gettext("label_searchcollectiononly").'</label>';
// print '</div>';

uibits::ui_checkbox([
	'id' => 'search_limit_limitsearch',
	'label' => 'label_limitsearch'
]);

// print '<div id="searchdomaincontrol" class="podoptions containerbox vertical-centre styledinputs" style="padding-top:4px">';
// print '<input class="autoset toggle" type="checkbox" id="search_limit_limitsearch" /><label for="search_limit_limitsearch">'.language::gettext("label_limitsearch").'</label>';
// print '</div>';

print '<div class="marged styledinputs tiny" id="mopidysearchdomains" style="margin-top:4px;padding-left:8px">';
print '</div>';
print '</div>';


print '<div id="collectionsearcher">';
$sterms = array(
	"label_artist" => "artist",
	"label_albumartist" => "albumartist",
	"label_album" => "album",
	"label_track" => "title",
	"label_genre" => "genre",
	"musicbrainz_date" => "date",
	"label_composer" => "composer",
	"label_performer" => "performer",
	"label_filename" => "file",
	"label_anything" => "any"
);

doSearchBoxes($sterms);
print '</div>';

?>

