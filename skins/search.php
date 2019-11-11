
<?php
print '<table style="width:100%">';
foreach ($sterms as $label => $term) {
    print '<tr class="searchitem" name="'.$term.'">';
    print '<td class="searchlabel slt"><b>'.ucwords(strtolower(get_int_text($label))).'</td>';
    print '<td width="100%"><input class="searchterm enter clearbox" name="'.$term.'" type="text" /></td>';
    print '</tr>';
}

print '<tr>';
print '<td class="searchlabel slt nohide"><b>'.get_int_text("label_rating").'</b></td>';
print '<td width="100%"><div class="selectholder" style="width:100%">
<select name="searchrating">
<option value="5">5 '.get_int_text('stars').'</option>
<option value="4">4 '.get_int_text('stars').'</option>
<option value="3">3 '.get_int_text('stars').'</option>
<option value="2">2 '.get_int_text('stars').'</option>
<option value="1">1 '.get_int_text('star').'</option>
<option value="" selected></option>
</select>';
print '</div></td>';
print '</tr>';

print '<tr>';
print '<td class="searchlabel slt nohide"></td>';
print '<td width="100%" class="combobox"></td>';
print '</tr>';

print '</table>';

print '<div class="containerbox dropdown-container">';
print '<i class="icon-toggle-closed mh menu openmenu fixed" name="advsearchoptions"></i>';
print '<div class="expand">Advanced Options...</div>';
print '</div>';

print '<div id="advsearchoptions" class="toggledown invisible marged">';
    print '<div class="marged styledinputs podoptions">';
    print '<input type="radio" class="topcheck savulon" name="displayresultsas" value="collection" id="resultsascollection">
    <label for="resultsascollection">'.ucfirst(get_int_text('label_resultscollection')).'</label>
    <input type="radio" class="topcheck savulon" name="displayresultsas" value="tree" id="resultsastree">
    <label for="resultsastree">'.ucfirst(get_int_text('label_resultstree')).'</label>
    </div>';
?>
