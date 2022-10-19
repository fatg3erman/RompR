<?php
chdir('../../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
$p = json_decode(file_get_contents('php://input'), true);
$returninfo = false;
prefs::$database = new metaquery();
logger::log("METADATA", "  Doing action",strtoupper($p['action']));
foreach ($p as $i => $v) {
	logger::trace("Parameter", "    ",$i,':',$v);
}

switch ($p['action']) {
	case 'gettags':
	case 'getgenres':
	case 'getartists':
	case 'getcharts':
	case 'getalbumartists':
	case 'getfaveartists':
	case 'getlistenlater':
	case 'resetallsyncdata':
		$returninfo = prefs::$database->{$p['action']}($p);
		break;

	case 'getrecommendationseeds';
		$returninfo = prefs::$database->get_recommendation_seeds($p['days'], $p['limit'], $p['top']);
		break;

	case 'addtolistenlater':
		prefs::$database->addToListenLater($p['json']);
		break;

	case 'browsetoll':
		prefs::$database = new metaDatabase();
		prefs::$database->browsetoll($p['uri']);
		break;

	case 'removelistenlater':
		prefs::$database->removeListenLater($p['index']);
		break;

	default:
		logger::warn("USERRATINGS", "Unknown Request",$p['action']);
		header('HTTP/1.1 400 Bad Request');
		break;

}
if ($returninfo) {
	print json_encode($returninfo);
} else {
	header('HTTP/1.1 204 No Content');
}
?>