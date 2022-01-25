<?php
chdir('../..');
include("includes/vars.php");
include("includes/functions.php");
require_once ('getid3/getid3.php');
set_error_handler('handle_error', E_ALL);
prefs::$database = new poDatabase();
$subflag = 1;
$dtz = ini_get('date.timezone');
if (!$dtz) {
	date_default_timezone_set('UTC');
}
$podid = null;
if (array_key_exists('url', $_REQUEST)) {
	prefs::$database->getNewPodcast(rawurldecode($_REQUEST['url']));
} else if (array_key_exists('refresh', $_REQUEST)) {
	$podid = array(prefs::$database->refreshPodcast($_REQUEST['refresh']));
} else if (array_key_exists('remove', $_REQUEST)) {
	prefs::$database->removePodcast($_REQUEST['remove']);
} else if (array_key_exists('listened', $_REQUEST)) {
	$podid = array(prefs::$database->markAsListened(rawurldecode($_REQUEST['listened'])));
} else if (array_key_exists('checklistened', $_REQUEST)) {
	$podid = array(prefs::$database->checkListened(rawurldecode($_REQUEST['title']), rawurldecode($_REQUEST['album']), rawurldecode($_REQUEST['artist'])));
} else if (array_key_exists('removetrack', $_REQUEST)) {
	$podid = array(prefs::$database->deleteTrack($_REQUEST['removetrack'], $_REQUEST['channel']));
} else if (array_key_exists('downloadtrack', $_REQUEST)) {
	$podid = prefs::$database->downloadTrack($_REQUEST['downloadtrack'], $_REQUEST['channel']);
} else if (array_key_exists('undownloadtrack', $_REQUEST)) {
	$podid = array(prefs::$database->undownloadTrack($_REQUEST['undownloadtrack'], $_REQUEST['channel']));
} else if (array_key_exists('markaslistened', $_REQUEST)) {
	$podid = array(prefs::$database->markKeyAsListened($_REQUEST['markaslistened'], $_REQUEST['channel']));
} else if (array_key_exists('markasunlistened', $_REQUEST)) {
	$podid = array(prefs::$database->markKeyAsUnlistened($_REQUEST['markasunlistened'], $_REQUEST['channel']));
} else if (array_key_exists('channellistened', $_REQUEST)) {
	$podid = array(prefs::$database->markChannelAsListened($_REQUEST['channellistened']));
} else if (array_key_exists('channelundelete', $_REQUEST)) {
	$podid = array(prefs::$database->undeleteFromChannel($_REQUEST['channelundelete']));
} else if (array_key_exists('setprogress', $_REQUEST)) {
	$podid = array(prefs::$database->setPlaybackProgress($_REQUEST['setprogress'], rawurldecode($_REQUEST['track'])));
} else if (array_key_exists('removedownloaded', $_REQUEST)) {
	$podid = array(prefs::$database->removeDownloaded($_REQUEST['removedownloaded']));
} else if (array_key_exists('option', $_REQUEST)) {
	$podid = array(prefs::$database->changeOption($_REQUEST['option'], $_REQUEST['val'], $_REQUEST['channel']));
} else if (array_key_exists('loadchannel', $_REQUEST)) {
	$podid = $_REQUEST['loadchannel'];
} else if (array_key_exists('search', $_REQUEST)) {
	prefs::$database->search_itunes($_REQUEST['search']);
	$subflag = 0;
} else if (array_key_exists('subscribe', $_REQUEST)) {
	prefs::$database->subscribe($_REQUEST['subscribe']);
} else if (array_key_exists('getcounts', $_REQUEST)) {
	$podid = prefs::$database->get_all_counts();
} else if (array_key_exists('checkrefresh', $_REQUEST)) {
	$podid = prefs::$database->check_podcast_refresh();
} else if (array_key_exists('markalllistened', $_REQUEST)) {
	$podid = prefs::$database->mark_all_episodes_listened();
} else if (array_key_exists('refreshall', $_REQUEST)) {
	$podid = prefs::$database->refresh_all_podcasts();
} else if (array_key_exists('undeleteall', $_REQUEST)) {
	$podid = prefs::$database->undelete_all();
} else if (array_key_exists('removealldownloaded', $_REQUEST)) {
	$podid = prefs::$database->remove_all_downloaded();
}

if ($podid === false) {
	logger::log('PODCASTS', 'Returning No Content');
	header('HTTP/1.1 204 No Content');
} else if (is_array($podid)) {
	if (array_key_exists(0, $podid) && $podid[0] === false) {
		logger::log('PODCASTS', 'Returning No Content for array return');
		header('HTTP/1.1 204 No Content');
	} else {
		logger::log('PODCASTS', 'Returning podid');
		header('Content-Type: application/json');
		print json_encode($podid);
	}
} else if ($podid !== null) {
	logger::log('PODCASTS', 'Returning podcast HTML');
	header('Content-Type: text/htnml; charset=utf-8');
	prefs::$database->outputPodcast($podid);
} else {
	logger::log('PODCASTS', 'Returning podcast list');
	header('Content-Type: text/htnml; charset=utf-8');
	prefs::$database->doPodcastList($subflag);
}


function handle_error($errno, $errstr, $errfile, $errline) {
	header('HTTP/1.1 400 Bad Request');
	logger::error("PODCASTS", "Error",$errno,$errstr,"in",$errfile,"at line",$errline);
	print "Error ".$errstr." in ".$errfile." at line ".$errline;
	exit(0);
}

?>
