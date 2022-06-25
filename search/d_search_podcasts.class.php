<?php
class d_search_podcasts {
	public function __construct(&$sh) {
		$sterms = array(
			"label_anything" => "any",
		);
		$sh->add_search_entry($sterms, 'podcastsearch');
		$sh->add_search_holder('podcast_search', 'fullwidth noselection is-albumlist');
	}
}
?>
