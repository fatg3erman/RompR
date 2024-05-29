<?php

chdir ('../..');
include ("includes/vars.php");
include ("includes/functions.php");
$params = json_decode(file_get_contents("php://input"), true);
prefs::$database = new collection_base();
// if ($params === null)
// 	$params = [];

logger::mark("USERSTREAMS", "Doing User Radio Stuff", print_r($params, true));
if (array_key_exists('populate', $_REQUEST)) {
	do_radio_header();
} else if (array_key_exists('remove', $params)) {
	prefs::$database->remove_user_radio_stream($params['remove']);
	http_response_code(204);
} else if (array_key_exists('order', $params)) {
	prefs::$database->save_radio_order($params['order']);
	http_response_code(204);
} else if (array_key_exists('addfave', $params)) {
	prefs::$database->add_fave_station($params);
	http_response_code(204);
} else if (array_key_exists('updatename', $params)) {
	prefs::$database->update_radio_station_name($params);
	http_response_code(204);
}

function do_radio_header() {
	uibits::directoryControlHeader('yourradiolist', language::gettext('label_yourradio'));
	do_radio_list();
}

function do_radio_list() {

	$playlists = prefs::$database->get_user_radio_streams();

	logger::info("USERSTREAMS", "Doing User Radio List");

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
