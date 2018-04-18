<?php
chdir('../..');
ob_start();
include ("includes/vars.php");
include ("includes/functions.php");

if(array_key_exists("url", $_REQUEST)) {
    get_spotify_page( $_REQUEST['url']);
} else {
    header('HTTP/1.1 400 Bad Request');
}
ob_flush();

function get_spotify_page($url) {
    debuglog("Getting Spotify Page ".$url,"SPOTIBIO");
    if (file_exists('prefs/jsoncache/spotify/'.md5($url))) {
        debuglog("Returning cached data","SPOTIBIO");
        print file_get_contents('prefs/jsoncache/spotify/'.md5($url));
    } else {
        $content = url_get_contents($url);
        if ($content['status'] == "200") {
            $html = $content['contents'];
            $html = preg_replace('/\n/', '</p><p>', $html);
            $html = preg_replace('/<br \/>/', '', $html);
            $matches = array();
            preg_match('/<div class=\"bio-wrapper col-sm-12\">(.*?)<\/div>/', $html, $matches);
            $r = "";
            if (array_key_exists(1, $matches)) {
                $r = preg_replace('/<button id=\"btn-reveal\".*?<\/button>/', '', $matches[1]);
                $r = preg_replace('/<a .*?>/', '', $r);
                $r = preg_replace('/<\/a>/', '', $r);
            }
            file_put_contents('prefs/jsoncache/spotify/'.md5($url), '<p>'.$r.'</p>');
            print "<p>".$r."</p>";
        } else {
            debuglog("No Spotify Page Found or something - status was ".$content['status'],"GETSPOTIBIO",3);
            file_put_contents('prefs/jsoncache/spotify/'.md5($url), '<p></p>');
            print '<p></p>';
        }
    }
}

?>