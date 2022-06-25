<?php
class m_search_tunein {
	public function __construct(&$sh) {
		$sterms = array(
			"label_anything" => "any",
		);
		$sh->add_search_entry($sterms, 'tuneinsearch');
		$sh->add_search_holder('tunein_search', 'fullwidth noselection is-albumlist');
	}
}
?>
