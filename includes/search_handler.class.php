<?php

/*

This is how we do pluggable search engines.

To create a search plugin add a file into search/*.php
That file MUST:

1) call searchhandler->add_search_entry($entry, $class);
$entry is an array of [label => term_name, ... ] which will be used as search entries
in the UI. Multiple plugins can use the same term names. The first one defines the label.
'rating' and 'tag' are special cases which will always be present. If you want to search by
them the plugin must still have them in its $terms, but the label is irrelevant.

It's expected (but not I suppose essential) that every plugin will at least include an 'any'
term, as by default that's the only things that's visible.

$class is a class which will be added to the entry. Each plugin should use its own
unique class.

2) call searchhandler->add_search_holder($id, $classes) to create the div into which the javascript
side will load the search results.
$id is the id to give to the div element, and $classes is a text list of classes for it,


See searchManager.js for what happens from then on.

*/

class search_handler {

	private $search_entries = [];
	private $search_holders = [];

	public function __construct() {
		logger::log('SEARCHHANDLER', 'initiating Blomquist Transfusion');
	}

	public function create_search_panel() {
		print '<div id="advsearchoptions" class="toggledown invisible">';

		print '<div>'.language::gettext('label_displayresultsas').'</div>';

		$sopts = ['sameas' => language::gettext('label_sameascollection')];
		$sopts = array_merge($sopts, array_map('ucfirst', array_map('language::gettext', COLLECTION_SORT_MODES)));
		$sopts = array_merge($sopts, ['results_as_tree' => language::gettext('label_resultstree')]);
		uibits::ui_select_box([
			'id' => 'sortresultsby',
			'options' => $sopts
		]);

		uibits::ui_checkbox([
			'id' => 'tradsearch',
			'label' => 'label_tradsearch'
		]);

		print '<div id="searchcategories" style="margin-top:4px">';
		print '</div>';
		print '</div>';

		logger::log('INIT', 'Doing Search Bits');

		$plugins = glob('search/*.php');
		foreach ($plugins as $plugin) {
			logger::log('INIT', 'Including', $plugin);
			$classname = pathinfo(pathinfo($plugin, PATHINFO_FILENAME), PATHINFO_FILENAME);
			$s = new $classname($this);
		}
		$this->make_search_control();
	}

	public function finish_search_panel() {
		$this->make_search_holders();
	}

	public function add_search_entry($entry, $class) {
		foreach ($entry as $label => $term) {
			if (!array_key_exists($term, $this->search_entries)) {
				$this->search_entries[$term] = ['label' => $label, 'classes' => [], 'list' => []];
			}
			$this->search_entries[$term]['classes'][] = $class;
		}
	}

	public function create_select_for_term($term, $options) {
		$this->search_entries[$term]['list'] = $options;
	}

	public function add_search_holder($id, $classes) {
		$this->search_holders[$id] = $classes;
	}

	private function make_text_box($term, $params) {
		print '<div class="searchitem vertical-centre containerbox" name="'.$term.'">';
		print '<input class="expand searchterm enter clearbox cleargroup '.implode(' ', $params['classes']);
		print '" name="'.$term.'" type="text" placeholder="'.ucwords(strtolower(language::gettext($params['label']))).'" />';
		print '</div>';
	}

	private function make_select_box($term, $params) {
		// No idea whay I have to add the margin-bottom style. Css seems to cram them together and ignore
		// any value I set.
		print '<div class="selectholder searchitem" style="margin-bottom:8px">';
		// Use of 'required' allows me to style the default to be grey so it looks like
		// the placeholder in the text boxes selec:required:invalid - works because the value is ""
		// and we can use it fine because we're not submitting this as a form.
		print '<select required class="searchterm '.implode(' ', $params['classes']).'" name="'.$term.'">';
		print '<option value="" selected>'.ucfirst($term).'</option>';
		foreach ($params['list'] as $option) {
			print '<option value="'.$option.'">'.ucfirst($option).'</option>';
		}
		print '</select>';
		print '</div>';
	}

	public function make_search_control() {
		print '<div id="collectionsearcher">';
			print '<div class="cleargroupparent fullwidth">';
			foreach ($this->search_entries as $term => $params) {
				if ($term != 'rating' && $term != 'tag') {
					if (count($params['list']) > 0) {
						$this->make_select_box($term, $params);
					} else {
						$this->make_text_box($term, $params);
					}
				}
			}

			print '<div id="ratingsearch" class="selectholder searchitem">
			<select class="searchterm '.implode(' ', $this->search_entries['rating']['classes']).'" name="rating">
			<option value="5">5 '.language::gettext('stars').'</option>
			<option value="4">4 '.language::gettext('stars').'</option>
			<option value="3">3 '.language::gettext('stars').'</option>
			<option value="2">2 '.language::gettext('stars').'</option>
			<option value="1">1 '.language::gettext('star').'</option>
			<option value="" selected></option>
			</select>';
			print '</div>';

			// This will become the tag menu
			print '<div class="containerbox vertical-centre searchitem combobox '.implode(' ', $this->search_entries['tag']['classes']).'">';
			print '</div>';

			print '<div class="containerbox">';
			print '<div class="expand"></div>';
			print '<button name="globalsearch" class="searchbutton iconbutton cleargroup spinable" class="fixed" onclick="searchManager.search()"></button>';
			print '</div>';

			print '</div>';
		print '</div>';
	}

	public function make_search_holders() {
		foreach ($this->search_holders as $id => $classes) {
			logger::log('INIT', 'Making Search Panel', $id);
			print '<div id="'.$id.'" class="search_result_box '.$classes.'"></div>';
		}
	}

}

?>