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
	'class' => 'radio yourradioroot',
	'expand' => true
));
print '<div id="yourradiolist" class="dropmenu notfilled is-albumlist">';
print '</div>';
print '</div>';
?>
