<?php
chdir('../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
logger::mark("USERRATING", "--------------------------START---------------------");

$error = 0;
$count = 1;
$download_file = "";

prefs::$database = new metaDatabase();
prefs::$database->open_transaction();

$params = json_decode(file_get_contents('php://input'), true);

// If you add new actions remember to update actions_requring_cleanup in metahandlers.js

foreach($params as $p) {

	logger::log("METADATA", "  Doing action",strtoupper($p['action']));
	foreach ($p as $i => $v) {
		logger::trace("Parameter", "    ",$i,':',$v);
	}

	prefs::$database->sanitise_data($p);

	switch ($p['action']) {

		// Things that return information about modified items
		case 'set':
		case 'seturi':
		case "add":
		case 'inc':
		case 'remove':
		case 'cleanup':
		case 'amendalbum':
		case 'deletealbum':
		case 'setasaudiobook':
		case 'usetrackimages':
		case 'delete':
		case 'deletewl':
		case 'deleteid':
		case 'resetresume':
			prefs::$database->create_foundtracks();
			prefs::$database->{$p['action']}($p);
			prefs::$database->prepare_returninfo();
			break;

		case 'youtubedl':
			set_time_limit(0);
			prefs::$database->close_transaction();
			prefs::$database->create_foundtracks();
			$progress_file = prefs::$database->{$p['action']}($p);
			prefs::$database->prepare_returninfo();
			unlink($progress_file);
			$progress_file = $progress_file.'_result';
			file_put_contents($progress_file, json_encode(prefs::$database->returninfo));
			exit(0);
			break;


		// Things that return information but do not modify items
		case 'get':
			prefs::$database->{$p['action']}($p);
			break;

		// Things that do not return information
		case 'setalbummbid':
		case 'clearwishlist':
			break;

		default:
			logger::warn("USERRATINGS", "Unknown Request",$p['action']);
			header('HTTP/1.1 400 Bad Request');
			break;
	}
	prefs::$database->check_transaction();
}

prefs::$database->close_transaction();
print json_encode(prefs::$database->returninfo);

logger::mark("USERRATING", "---------------------------END----------------------");

?>
