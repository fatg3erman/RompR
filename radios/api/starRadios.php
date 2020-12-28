<?php
chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
$params = json_decode(file_get_contents('php://input'), true);
prefs::$database = new collection_radio();
foreach($params as $p) {
	switch ($p['action']) {

		case 'getplaylist':
			prefs::$database->preparePlaylist();
		case 'repopulate':
			print json_encode(prefs::$database->doPlaylist($p['playlist'], $p['numtracks']));
			break;

		default:
			logger::warn("USERRATINGS", "Unknown Request",$p['action']);
			header('HTTP/1.1 400 Bad Request');
			break;

	}
}
?>