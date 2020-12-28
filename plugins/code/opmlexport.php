<?php

chdir('../..');

include ("includes/vars.php");
include ("includes/functions.php");
prefs::$datbase = new opml_io();

$opml = new SimpleXMLElement('<opml version="2.0"></opml>');
$head = $opml->addChild('head');
$head->addChild('title', 'Rompr Podcast Subscriptions');
$head->addChild('dateCreated', date('d M y H:i:s O'));
$body = $opml->addChild('body');

$result = prefs::$database->get_podcasts();
foreach ($result as $obj) {
    $o = $body->addChild('outline');
    $o->addAttribute('text', htmlspecialchars($obj->Title));
    $o->addAttribute('title', htmlspecialchars($obj->Title));
    $o->addAttribute('xmlUrl', $obj->FeedURL);
    $o->addAttribute('type', 'rss');
}

header('Content-Type: text/xml');
$dom = new DOMDocument();
$dom->loadXML($opml->asXML());
$dom->formatOutput = true;
print $dom->saveXML();

?>