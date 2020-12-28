<?php
print '<div id="faveradioplugin">';
print uibits::albumHeader(array(
	'id' => 'yourradiolist',
	'Image' => 'newimages/broadcast.svg',
	'Searched' => 1,
	'AlbumUri' => null,
	'Year' => null,
	'Artistname' => '',
	'Albumname' => language::gettext('label_yourradio'),
	'why' => null,
	'ImgKey' => 'none',
	'class' => 'radio yourradioroot',
	'expand' => true
));
print '<div id="yourradiolist" class="dropmenu notfilled is-albumlist holderthing">';
print '</div>';
print '</div>';
?>
