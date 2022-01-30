<?php
// print '<div id="faveradioplugin">';
print uibits::albumHeader(array(
	'id' => 'yourradiolist',
	'Image' => 'newimages/broadcast.svg',
	'Albumname' => language::gettext('label_yourradio'),
	'class' => 'radio yourradioroot',
));
print '<div id="yourradiolist" class="dropmenu notfilled is-albumlist">';
print '</div>';
// print '</div>';
?>
