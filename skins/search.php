
<div class="containerbox">
<?php
    print '<div class="configtitle textcentre expand"><b>'.get_int_text('label_searchfor').'</b></div>';
?>
</div>

<div class="containerbox">
<?php
if ($prefs['tradsearch']) {
    print '<i>'.get_int_text("label_multiterms").'</i>';
} else {
    print '<i>'.get_int_text("label_tradsearch_header").'</i>';
}
?>
</div>

<?php
foreach ($sterms as $label => $term) {
    if ($prefs['tradsearch']) {
        print '<div class="containerbox dropdown-container">';
    	print '<div class="fixed searchlabel"><span class="slt"><b>'.ucwords(strtolower(get_int_text($label))).'</b></span></div>';
        print '<div class="expand"><input class="searchterm enter" name="'.$term.'" type="text" /></div>';
        print '</div>';
    } else if ($term == 'any') {
        print '<div class="containerbox dropdown-container">';
        print '<div class="expand"><input class="searchterm enter" name="'.$term.'" type="text" /></div>';
        print '</div>';
    }
}

?>
<div class="containerbox dropdown-container combobox">
</div>

<div class="containerbox dropdown-container">
<?php
print '<div class="fixed searchlabel"><span class="slt"><b>'.get_int_text("label_rating").'</b></span></div>
        <div class="expand selectholder">
        <select name="searchrating">
        <option value="5">5 '.get_int_text('stars').'</option>
        <option value="4">4 '.get_int_text('stars').'</option>
        <option value="3">3 '.get_int_text('stars').'</option>
        <option value="2">2 '.get_int_text('stars').'</option>
        <option value="1">1 '.get_int_text('star').'</option>
        <option value="" selected></option>
        </select>
       </div>
</div>';


print '<div class="containerbox menuitem noselection multidrop">';
print '<i class="icon-toggle-closed mh menu fixed" name="advsearchoptions"></i>';
print '<div class="expand">Advanced Options...</div>';
print '</div>';

print '<div id="advsearchoptions" class="dropmenu">';

    print '<div class="styledinputs">';
    print '<div class="containerbox padright" style="margin-top:0.5em;margin-bottom:0.5em"><b>'.get_int_text('label_displayresultsas').'</b></div>';
    print '<div class="dropmenu" style="display:block">';
    print '<input type="radio" class="topcheck savulon" name="displayresultsas" value="collection" id="resultsascollection">
    <label for="resultsascollection">'.ucfirst(get_int_text('label_resultscollection')).'</label><br/>
    <input type="radio" class="topcheck savulon" name="displayresultsas" value="tree" id="resultsastree">
    <label for="resultsastree">'.ucfirst(get_int_text('label_resultstree')).'</label>
    </div>
    </div>';
?>
