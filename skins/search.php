
<?php
function doSearchBoxes($sterms) {
	print '<div class="cleargroupparent fullwidth">';
	foreach ($sterms as $label => $term) {
		print '<div class="searchitem vertical-centre containerbox" name="'.$term.'">';
		print '<input class="expand searchterm enter clearbox cleargroup" name="'.$term.'" type="text" placeholder="'.ucwords(strtolower(language::gettext($label))).'"/>';
		print '</div>';
	}

	print '<div id="ratingsearch" class="selectholder">
	<select name="searchrating">
	<option value="5">5 '.language::gettext('stars').'</option>
	<option value="4">4 '.language::gettext('stars').'</option>
	<option value="3">3 '.language::gettext('stars').'</option>
	<option value="2">2 '.language::gettext('stars').'</option>
	<option value="1">1 '.language::gettext('star').'</option>
	<option value="" selected></option>
	</select>';
	print '</div>';

	print '<div class="containerbox vertical-centre combobox">';
	print '</div>';

	print '<div class="containerbox">';
	print '<div class="expand"></div>';
	print '<button name="playersearch" class="searchbutton iconbutton cleargroup spinable" class="fixed" onclick="player.controller.search(\'search\')"></button>';
	print '</div>';

	print '</div>';
}

function startAdvSearchOptions() {
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

}
?>
