<div id="collectionsearcher">
<?php
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
include("skins/search.php");
print '<div class="styledinputs" style="padding-top:4px">';

print '<input class="autoset toggle" type="checkbox" id="tradsearch">
<label for="tradsearch">'.get_int_text("label_tradsearch").'</label>';

print '</div>';

print '</div>';

print '</div>';
