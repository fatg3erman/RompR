<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
$r = json_decode(file_get_contents('php://input'), true);

prefs::$database = new lfm_importer();

switch ($r['action']) {

	case "getchunk":
		$arse = prefs::$database->get_chunk_of_data($r['offset'], $r['limit']);
		logger::trace("LFMIMPORTER", "Got ".count($arse)." rows");
		if (count($arse) == 0) {
			logger::log("LFMIMPORTER", "Updating LastFM Import time");
			prefs::set_pref(['lfm_importer_last_import' => time()]);
			prefs::save();
		}

		header('Content-Type: application/json; charset=utf-8');
		print json_encode($arse);
		break;

	case "gettotal":
		$arse = prefs::$database->get_total_tracks();
		logger::trace("LFMIMPORTER", "Got ".$arse[0]['total']." tracks");
		header('Content-Type: application/json; charset=utf-8');
		print json_encode(array('total' => $arse[0]['total']));
		break;

}

?>