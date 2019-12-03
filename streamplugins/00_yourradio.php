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
    'ImgKey' => 'none',
    'class' => 'radio',
    'expand' => true
));
print '<div id="yourradiolist" class="dropmenu">';
directoryControlHeader('yourradiolist', get_int_text('label_yourradio'));
print '<div id="anaconda" class="noselection fullwidth">';
print '<div class="containerbox dropdown-container"><div class="expand"><input class="enter clearbox" id="yourradioinput" type="text" placeholder="'.get_int_text("label_radioinput").'" /></div>';
print '<button class="fixed iconbutton icon-no-response-playbutton" name="spikemilligan"></button></div>';
print '<div id="yourradiostations" class="holderthing"></div>';
print '</div>';
print '</div>';
print '</div>';
?>
