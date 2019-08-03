
<?php
print '<table>';
foreach ($sterms as $label => $term) {
    print '<tr class="searchitem" name="'.$term.'">';
    print '<td class="searchlabel slt"><b>'.ucwords(strtolower(get_int_text($label))).'</td>';
    print '<td width="100%"><input class="searchterm enter clearbox" name="'.$term.'" type="text" /></td>';
    print '</tr>';
}
print '</table>'

?>
<div class="containerbox dropdown-container combobox">
</div>

<div class="containerbox dropdown-container">
<?php
print '<div class="fixed searchlabel nohide"><span class="slt"><b>'.get_int_text("label_rating").'</b></span></div>
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


print '<div class="containerbox dropdown-container">';
print '<i class="icon-toggle-closed mh menu openmenu fixed" name="advsearchoptions"></i>';
print '<div class="expand">Advanced Options...</div>';
print '</div>';

print '<div id="advsearchoptions" class="toggledown invisible marged">';
    print '<div class="styledinputs">';
    print '<div class="containerbox padright" style="margin-top:0.5em;margin-bottom:0.5em"><b>'.get_int_text('label_displayresultsas').'</b></div>';
    print '<div class="marged">';
    print '<input type="radio" class="topcheck savulon" name="displayresultsas" value="collection" id="resultsascollection">
    <label for="resultsascollection">'.ucfirst(get_int_text('label_resultscollection')).'</label><br/>
    <input type="radio" class="topcheck savulon" name="displayresultsas" value="tree" id="resultsastree">
    <label for="resultsastree">'.ucfirst(get_int_text('label_resultstree')).'</label>
    </div>
    </div>';
?>
