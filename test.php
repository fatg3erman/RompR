<?php

include ("includes/vars.php");
include ("includes/functions.php");
include ("utils/phpQuery.php");

$getstr = "http://dir.xiph.org/";

$content = url_get_contents($getstr);
$icecast_shitty_page = preg_replace('/<\?xml.*?\?>/', '', $content['contents']);
$doc = phpQuery::newDocument($icecast_shitty_page);
$list = $doc->find('table.servers-list')->find('tr');
$page_title = $doc->find('#content')->children('h2')->text();
print "Page Title Is ".$page_title;
$count = 0;
$html = '<div class="configtitle textcentre">'.$page_title.'</div>';
foreach ($list as $server) {
    $server_web_link = '';
    $server_name = pq($server)->find('.stream-name')->children('.name')->children('a');
    $server_web_link = $server_name->attr('href');
    $server_name = $server_name->text();
    print "Server Name Is ".$server_name;
    $server_description = munge_ice_text($server->find('.stream-description').text());
    $stream_tags = array();
    $stream_tags_section = $server->find('stream_tags')->find('li');
    foreach ($stream_tags_section as $tag) {
        $stream_tags[] = $tag->children('a').text();
    }
    $listeners = $server->find('.listeners').text();
    $listenlinks = $server->find('.tune-in');
    $listenlink = '';
    $format = '';
    $ps = $listenlinks->find('p');
    foreach ($ps as $p) {
        if ($p->hasClass('format')) {
            $format = $p->attr('title');
        } else {
            foreach($p->children('a') as $a) {
                $l = $a->attr('href');
                if (substr($l, -5) == ".xspf") {
                    $listenlink = 'http://dir.xiph.org'.$l;
                }
            }
        }
    }
}
?>
