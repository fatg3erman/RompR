<?php

chdir ('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("backends/sql/backend.php");
include ('utils/phpQuery.php');

logger::log("USERSTREAMS", "Doing User Radio Stuff");
if (array_key_exists('populate', $_REQUEST)) {
	do_radio_header();
} else if (array_key_exists('remove', $_REQUEST)) {
	remove_user_radio_stream($_REQUEST['remove']);
	header('HTTP/1.1 204 No Content');
} else if (array_key_exists('order', $_REQUEST)) {
	save_radio_order($_REQUEST['order']);
	header('HTTP/1.1 204 No Content');
} else if (array_key_exists('addfave', $_REQUEST)) {
	add_fave_station($_REQUEST);
	header('HTTP/1.1 204 No Content');
} else if (array_key_exists('updatename', $_REQUEST)) {
	update_radio_station_name($_REQUEST);
	header('HTTP/1.1 204 No Content');
}

function do_radio_header() {
	directoryControlHeader('yourradiolist', language::gettext('label_yourradio'));
	print '<div id="anaconda" class="noselection fullwidth">';
		print '<div class="containerbox dropdown-container">';
			print '<div class="expand"><input class="enter clearbox" id="yourradioinput" type="text" placeholder="'.language::gettext("label_radioinput").'" /></div>';
			print '<button class="fixed iconbutton icon-no-response-playbutton" name="spikemilligan"></button>';
		print '</div>';
	print '</div>';
	do_radio_list();
}

function do_radio_list() {

	$playlists = get_user_radio_streams();

	logger::log("USERSTREAMS", "Doing User Radio List");

	foreach($playlists as $playlist) {

		logger::log('USERSTREAMS', 'Station',$playlist['StationName']);

		$albumimage = new albumImage(array('artist' => 'STREAM', 'album' => $playlist['StationName']));

		$html = albumHeader(array(
			'id' => 'nodrop',
			'Image' => $playlist['Image'],
			'Searched' => 1,
			'AlbumUri' => null,
			'Year' => null,
			'Artistname' => null,
			'Albumname' => utf8_encode($playlist['StationName']),
			'why' => 'whynot',
			'ImgKey' => $albumimage->get_image_key(),
			'streamuri' => $playlist['PlaylistUrl'],
			'streamname' => $playlist['StationName'],
			'streamimg' => $playlist['Image'],
			'class' => 'faveradio',
			'expand' => true
		));

		$out = addUserRadioButtons($html, $playlist['Stationindex'], $playlist['PlaylistUrl'], $playlist['StationName'], $playlist['Image']);
		print $out->html();

	}

}

?>
