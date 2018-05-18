<?php
print '<div id="faveradioplugin">';
print '<div class="containerbox menuitem noselection multidrop">';
print '<i class="icon-toggle-closed mh menu fixed" name="yourradiolist"></i>';
print '<i class="icon-radio-tower fixed smallcover smallcover-svg"></i>';
print '<div class="expand"><h3>'.get_int_text('label_yourradio').'</h3></div>';
print '</div>';
print '<div id="yourradiolist" class="dropmenu">';
print '<div id="anaconda" class="noselection fullwidth">';
print '<div class="containerbox"><div class="expand">'.get_int_text("label_radioinput").'</div></div>';
print '<div class="containerbox"><div class="expand"><input class="enter" id="yourradioinput" type="text" /></div>';
print '<button class="fixed" name="spikemilligan">'.get_int_text("button_playradio").'</button></div>';
print '<div id="yourradiostations" clas="pipl"></div>';
print '</div>';
print '</div>';
print '</div>';
?>
