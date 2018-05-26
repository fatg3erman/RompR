<?php
print '<div id="faveradioplugin">';
print albumHeader(array(
    'id' => 'yourradiolist',
    'Image' => 'newimages/broadcast.svg',
    'Searched' => 1,
    'AlbumUri' => null,
    'Year' => null,
    'Artistname' => '',
    'Albumname' => get_int_text('label_yourradio'),
    'why' => null,
    'ImgKey' => 'none'
));
print '<div id="yourradiolist" class="dropmenu">';
directoryControlHeader('yourradiolist', get_int_text('label_yourradio'));
print '<div id="anaconda" class="noselection fullwidth">';
print '<div class="containerbox indent"><div class="expand">'.get_int_text("label_radioinput").'</div></div>';
print '<div class="containerbox indent"><div class="expand"><input class="enter" id="yourradioinput" type="text" /></div>';
print '<button class="fixed" name="spikemilligan">'.get_int_text("button_playradio").'</button></div>';
print '<div id="yourradiostations" clas="pipl"></div>';
print '</div>';
print '</div>';
print '</div>';
?>
