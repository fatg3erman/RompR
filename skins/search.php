
<?php
function doSearchBoxes($sterms) {
	print '<div class="cleargroupparent fullwidth">';
	foreach ($sterms as $label => $term) {
		print '<div class="searchitem dropdown-container containerbox" name="'.$term.'">';
		print '<input class="expand searchterm enter clearbox cleargroup" name="'.$term.'" type="text" placeholder="'.ucwords(strtolower(get_int_text($label))).'"/>';
		print '</div>';
	}

	print '<div id="ratingsearch" class="selectholder">
	<select name="searchrating">
	<option value="5">5 '.get_int_text('stars').'</option>
	<option value="4">4 '.get_int_text('stars').'</option>
	<option value="3">3 '.get_int_text('stars').'</option>
	<option value="2">2 '.get_int_text('stars').'</option>
	<option value="1">1 '.get_int_text('star').'</option>
	<option value="" selected></option>
	</select>';
	print '</div>';

	print '<div class="containerbox dropdown-container combobox">';
	print '</div>';

	print '<div class="containerbox">
		<div class="expand"></div>';
	print '<button class="searchbutton iconbutton cleargroup" class="fixed" onclick="player.controller.search(\'search\')"></button>';
	print '</div>';

	print '</div>';
}

function startAdvSearchOptions() {
	print '<div id="advsearchoptions" class="invisible">';
		print '<div class="marged styledinputs podoptions">';
		print '<input type="radio" class="topcheck savulon" name="displayresultsas" value="collection" id="resultsascollection">
		<label for="resultsascollection">'.ucfirst(get_int_text('label_resultscollection')).'</label>
		<input type="radio" class="topcheck savulon" name="displayresultsas" value="tree" id="resultsastree">
		<label for="resultsastree">'.ucfirst(get_int_text('label_resultstree')).'</label>
		</div>';

	print '<div class="containerbox dropdown-container">';
	print '<div class="selectholder">';
	print '<select id="sortresultsbyselector" class="saveomatic">';
	foreach (COLLECTION_SORT_MODES as $mode => $key) {
		print '<option value="'.$mode.'">'.ucfirst(get_int_text($key)).'</option>';
	}
	print '</select>';
	print '</div>';
	print '</div>';

	print '<div class="styledinputs" style="padding-top:4px">';
	print '<input class="autoset toggle" type="checkbox" id="tradsearch">
	<label for="tradsearch">'.get_int_text("label_tradsearch").'</label>';
	print '</div>';

}
?>
