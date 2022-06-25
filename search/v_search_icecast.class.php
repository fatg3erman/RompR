<?php
class v_search_icecast {
	public function __construct(&$sh) {
		$sterms = array(
			"label_anything" => "any",
		);
		$sh->add_search_entry($sterms, 'icecastsearch');
		$sh->add_search_holder('icecastlist', 'fullwidth noselection is-albumlist');
	}
}
?>
