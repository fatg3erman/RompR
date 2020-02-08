<?php
require_once("skins/search.php");

startAdvSearchOptions();
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
