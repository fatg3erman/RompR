<?php

chdir ('../..');
include ("includes/vars.php");
include ("includes/functions.php");
prefs::$database = new collection_base();

logger::log("USERSTREAMS", "Doing User Radio Stuff");
if (array_key_exists('populate', $_REQUEST)) {
	do_radio_header();
} else if (array_key_exists('remove', $_REQUEST)) {
	prefs::$database->remove_user_radio_stream($_REQUEST['remove']);
	header('HTTP/1.1 204 No Content');
} else if (array_key_exists('order', $_REQUEST)) {
	prefs::$database->save_radio_order($_REQUEST['order']);
	header('HTTP/1.1 204 No Content');
} else if (array_key_exists('addfave', $_REQUEST)) {
	prefs::$database->add_fave_station($_REQUEST);
	header('HTTP/1.1 204 No Content');
} else if (array_key_exists('updatename', $_REQUEST)) {
	prefs::$database->update_radio_station_name($_REQUEST);
	header('HTTP/1.1 204 No Content');
}

function do_radio_header() {
	uibits::directoryControlHeader('yourradiolist', language::gettext('label_yourradio'));
	print '<div id="anaconda" class="noselection fullwidth">';
		print '<div class="containerbox dropdown-container">';
			print '<div class="expand"><input class="enter clearbox" id="yourradioinput" type="text" placeholder="'.language::gettext("label_radioinput").'" /></div>';
			print '<button class="fixed iconbutton icon-no-response-playbutton" name="spikemilligan"></button>';
		print '</div>';
	print '</div>';
	do_radio_list();
}

function do_radio_list() {

	$playlists = prefs::$database->get_user_radio_streams();

	logger::log("USERSTREAMS", "Doing User Radio List");

	foreach($playlists as $playlist) {

		logger::log('USERSTREAMS', 'Station',$playlist['StationName']);

		$albumimage = new albumImage(array('artist' => 'STREAM', 'album' => $playlist['StationName']));

		print uibits::albumHeader(array(
			'openable' => false,
			'Image' => $playlist['Image'],
			'Albumname' => $playlist['StationName'],
			'ImgKey' => $albumimage->get_image_key(),
			'streamuri' => $playlist['PlaylistUrl'],
			'streamname' => $playlist['StationName'],
			'streamimg' => $playlist['Image'],
			'class' => 'faveradio',
			'podcounts' => '<i class="clickable clickradioremove clickicon yourradio icon-cancel-circled fixed inline-icon" name="'.$playlist['Stationindex'].'"></i>'
		));

	}

}

?>
