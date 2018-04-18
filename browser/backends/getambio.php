<?php
chdir('../..');
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");

if(array_key_exists("url", $_REQUEST)) {
    $link = get_bio_link($_REQUEST['url']);
    if ($link !== false) {
        get_allmusic_page($link);
    } else {
        print '<p></p>';
    }
} else {
    header('HTTP/1.1 400 Bad Request');
}
ob_flush();

function get_bio_link($url) {
    $html = '';
    if (file_exists('prefs/jsoncache/allmusic/'.md5($url))) {
        $html = file_get_contents('prefs/jsoncache/allmusic/'.md5($url));
    } else {
        $content = url_get_contents($url);
        if ($content['status'] == "200") {
            $html = $content['contents'];
            file_put_contents('prefs/jsoncache/allmusic/'.md5($url), $html);
        } else {
            debuglog("No Allmusic Link Page Found or something - status was ".$content['status'],"GETSPOTIBIO",3);
            return null;
        }
    }
    $DOM = new DOMDocument;
    @$DOM->loadHTML($html);
    $els = getElementsByClass($DOM, 'li', 'biography');
    if (count($els) > 0) {
        $e = $els[0];
        $links = $e->GetElementsByTagName('a');
        for ($i = 0; $i < $links->length; $i++) {
            $link = $links->item($i)->getAttribute('href');
            debuglog("Found Bio Link ".$link,"MBBIO");
        }
        return 'http://www.allmusic.com'.$link;
    } else {
        return false;
    }
}

function get_allmusic_page($url) {
    debuglog("Getting allmusic Page ".$url,"AMBIO");
    $r = '<p></p>';
    if (file_exists('prefs/jsoncache/allmusic/'.md5($url))) {
        debuglog("Returning cached data ".'prefs/jsoncache/allmusic/'.md5($url),"AMBIO");
        $r = file_get_contents('prefs/jsoncache/allmusic/'.md5($url));
    } else {
        $content = url_get_contents($url);
        if ($content['status'] == "200") {
            $html = $content['contents'];
            $DOM = new DOMDocument;
            @$DOM->loadHTML($html);
            $els = getElementsByClass($DOM, 'div', 'text');
            foreach ($els as $el) {
                $a = $el->getAttribute('itemprop');
                if ($a == 'reviewBody') {
                    debuglog("Found Review Body","AMBIO");
                    $r = $el->nodeValue;
                }
            }
            $r = '<p>'.$r.'</p><p>Biography courtesy of AllMusic</p>';
            file_put_contents('prefs/jsoncache/allmusic/'.md5($url), $r);
        } else {
            debuglog("No Allmusic Page Found or something - status was ".$content['status'],"GETSPOTIBIO",3);
            file_put_contents('prefs/jsoncache/allmusic/'.md5($url), $r);
        }
    }
    print preg_replace('/\n/', '</p><p>',$r);
}

function getElementsByClass(&$parentNode, $tagName, $className) {
    $nodes=array();

    $childNodeList = $parentNode->getElementsByTagName($tagName);
    for ($i = 0; $i < $childNodeList->length; $i++) {
        $temp = $childNodeList->item($i);
        if (stripos($temp->getAttribute('class'), $className) !== false) {
            $nodes[]=$temp;
        }
    }

    return $nodes;
}

?>