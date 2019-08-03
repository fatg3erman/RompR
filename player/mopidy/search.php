<div id="collectionsearcher">
<?php
$sterms = array(
    "label_artist" => "artist",
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

print '</div><div class="styledinputs" style="padding-top:4px">';
print '<input class="autoset toggle" type="checkbox" id="searchcollectiononly">
<label for="searchcollectiononly">'.get_int_text("label_searchcollectiononly").'</label>';

print '</div>';

print '<div id="searchdomaincontrol" class="podoptions containerbox padright dropdown-container styledinputs" style="padding-top:4px">';
print '<input class="autoset toggle" type="checkbox" id="search_limit_limitsearch"><label for="search_limit_limitsearch">'.get_int_text("label_limitsearch").'</label>';
print '</div>';

print '<div class="marged styledinputs tiny" id="mopidysearchdomains" style="margin-top:4px;padding-left:8px">';
print '</div>';

print '</div>';

?>

<div class="containerbox">
    <div class="expand"></div>
<?php
print '<button class="searchbutton iconbutton" style="margin-right:4px" class="fixed" onclick="player.controller.search(\'search\')"></button>';
?>
</div>
</div>
