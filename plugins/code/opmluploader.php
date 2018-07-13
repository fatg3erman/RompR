<?php

chdir('../..');

include ("includes/vars.php");
include ("includes/functions.php");
include ("backends/sql/backend.php");

$output = array();

debuglog("Uploading OPML File", "OPML IMPORTER");
foreach ($_FILES['opmlfile'] as $key => $value) {
    debuglog("  ".$key." = ".$value,"OPML IMPORTER");
}

$file = $_FILES['opmlfile']['name'];
$download_file= "";
$download_file = get_user_file($file, basename($file), $_FILES['opmlfile']['tmp_name']);

$x = simplexml_load_file($download_file);
$v = (string) $x['version'];
debuglog("OPML version is ".$v, "OPML IMPORTER");

foreach ($x->body->outline as $o) {
    $att = $o->attributes();
    debuglog("  Text is ".$att['text'].", type is ".$att['type'], "OPML IMPORTER");
    switch ($att['type']) {
        case 'rss':
            array_push($output, array(
                'Title' => (string) $att['text'],
                'feedURL' => (string) $att['xmlUrl'],
                'htmlURL' => (string) $att['htmlUrl'],
                'subscribed' => podcast_is_subscribed((string) $att['xmlUrl'])
            ));
            break;
        
        default:
            debuglog("Unknown outline type ".$att['type'], "OPML IMPORTER");
            break;
    }
}

print json_encode($output);

function podcast_is_subscribed($feedURL) {
    $r = sql_prepare_query(false, PDO::FETCH_ASSOC, null, null,
        "SELECT Title FROM Podcasttable WHERE Subscribed = 1 AND FeedURL = ?", $feedURL);
    if (count($r) > 0) {
        debuglog("    Already Subscribed To Podcast ".$feedURL,"OPML Imoprter");
        return true;
    }
    return false;
}

?>
