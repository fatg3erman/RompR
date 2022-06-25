<?php
class g_search_commradio {
	public function __construct(&$sh) {
		$sterms = array(
			"label_anything" => "any",
			"soundcloud_Country" => 'country',
			// "soundcloud_state" => 'state',
			"settings_language" => 'language',
			"irrelevant" => 'tag'
		);
		$sh->add_search_entry($sterms, 'commradiosearch');
		$cr = new commradioplugin();
		$sh->create_select_for_term('country', $cr->get_country_list());
		$sh->create_select_for_term('language', $cr->get_language_list());
		$sh->add_search_holder('commradio_search', 'fullwidth noselection is-albumlist');
	}
}
?>
