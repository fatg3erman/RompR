<?php

chdir('../..');

include ("includes/vars.php");
include ("includes/functions.php");
prefs::$database = new opml_io();
$output = array();

logger::mark("OPML IMPORTER", "Uploading OPML File");
foreach ($_FILES['opmlfile'] as $key => $value) {
	logger::log("OPML IMPORTER", "  ".$key." = ".$value);
}

$file = $_FILES['opmlfile']['name'];
$download_file= "";
$download_file = get_user_file($file, basename($file), $_FILES['opmlfile']['tmp_name']);

$x = simplexml_load_file($download_file);
$v = (string) $x['version'];
logger::log("OPML IMPORTER", "OPML version is ".$v);

foreach ($x->body->outline as $o) {
	$att = $o->attributes();
	logger::log("OPML IMPORTER", "  Text is ".$att['text'].", type is ".$att['type']);
	switch ($att['type']) {
		case 'rss':
			array_push($output, array(
				'Title' => (string) $att['text'],
				'feedURL' => (string) $att['xmlUrl'],
				'htmlURL' => (string) $att['htmlUrl'],
				'subscribed' => prefs::$database->podcast_is_subscribed((string) $att['xmlUrl'])
			));
			break;

		default:
			logger::log("OPML IMPORTER", "Unknown outline type ".$att['type']);
			break;
	}
}

print json_encode($output);

?>
