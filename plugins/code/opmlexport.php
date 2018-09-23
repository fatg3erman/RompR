<?php

chdir('../..');

include ("includes/vars.php");
include ("includes/functions.php");
include ("backends/sql/backend.php");

$opml = new SimpleXMLElement('<opml version="2.0"></opml>');
$head = $opml->addChild('head');
$head->addChild('title', 'Rompr Podcast Subscriptions');
$head->addChild('dateCreated', date('d M y H:i:s O'));
$body = $opml->addChild('body');

$result = generic_sql_query("SELECT FeedURL, Title FROM Podcasttable WHERE Subscribed = 1", false, PDO::FETCH_OBJ);
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