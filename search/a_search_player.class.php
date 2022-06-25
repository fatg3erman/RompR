<?php
class a_search_player {
	public function __construct(&$sh) {
		$sterms = array(
			"label_anything" => "any",
			"label_artist" => "artist",
			"label_albumartist" => "albumartist",
			"label_album" => "album",
			"label_track" => "title",
			"label_genre" => "genre",
			"musicbrainz_date" => "date",
			"label_composer" => "composer",
			"label_performer" => "performer",
			"label_filename" => "file",
			"irrelevant" => 'rating',
			"irrelevent" => 'tag'
		);
		$sh->add_search_entry($sterms, 'playersearch');
		$sh->add_search_holder('searchresultholder', 'noborder selecotron is-albumlist');
	}
}
?>

