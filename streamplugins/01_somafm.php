<?php
if (array_key_exists('populate', $_REQUEST)) {

    chdir('..');

    include ("includes/vars.php");
    include ("includes/functions.php");
    include ("international.php");

    print '<div class="containerbox padright">';
    print '<b>'.get_int_text("label_soma").'<br>';
    print '<a href="http://somafm.com" target="_blank">'.get_int_text("label_soma_beg").'</a></b>';
    print '</div>';

    $content = url_get_contents("http://api.somafm.com/channels.xml", $_SERVER['HTTP_USER_AGENT'], false, true);
    if ($content['status'] == "200") {
        debuglog("Loaded Soma FM channels list","SOMAFM");
        $x = simplexml_load_string($content['contents']);
        $count = 0;
        foreach ($x->channel as $channel) {
            debuglog("Channel : ".$channel->title,"SOMAFM");
            if ($channel->highestpls) {
                $pls = (string) $channel->highestpls;
            } else {
                $pls = (string) $channel->fastpls[0];
            }
            print '<div class="albumheader clickable clickstream draggable containerbox menuitem" name="'.$pls.'" streamname="'.(string) $channel->title.'" streamimg="'.getimage($channel).'">';
            print '<i class="icon-toggle-closed menu mh fixed" name="somafm_'.$count.'"></i>';
            print '<div class="smallcover fixed">';
            print '<img class="smallcover fixed" src="'.getimage($channel).'" />';
            print '</div>';
            print '<div class="expand">'.utf8_encode($channel->title).'<br><span class="notbold"><i>'.utf8_encode($channel->genre).'</i></span></div>';
            print '</div>';
            
            print '<div id="somafm_'.$count.'" class="dropmenu">';
            
            if ($channel->description) {
                print '<div class="containerbox ninesix indent padright">'.utf8_encode($channel->description).'</div>';
            }
            
            print '<div class="containerbox rowspacer"></div>';
            print '<div class="containerbox expand ninesix indent padright"><b>Listen:</b></div>';

            if ($channel->highestpls) {
                format_listenlink($channel, $channel->highestpls, "HQ");
            }
            foreach ($channel->fastpls as $h) {
                format_listenlink($channel, $h, "");
            }
            foreach ($channel->slowpls as $h) {
                format_listenlink($channel, $h, "LQ");
            }

            print '<div class="containerbox rowspacer"></div>';

            if ($channel->twitter && $channel->dj) {
                print '<a href="http://twitter.com/@'.$channel->twitter.'" target="_blank">';
                print '<div class="containerbox indent padright">';
                print '<i class="icon-twitter-logo playlisticon fixed"></i>';
                print '<div class="expand"><b>DJ: </b>'.$channel->dj.'</div>';
                print '</div></a>';
            }
            if ($channel->listeners) {
                print '<div class="containerbox indent padright">';
                print '<div class="expand">'.$channel->listeners.' '.get_int_text("lastfm_listeners").'</div>';
                print '</div>';
            }
            if ($channel->lastPlaying) {
                print '<div class="containerbox vertical indent padright">';
                print '<div class="fixed"><b>Last Played</b></div>';
                print '<div class="fixed playlistrow2">'.$channel->lastPlaying.'</div>';
                print '</div>';
            }

            print '</div>';
            $count++;
        }
        
    }

} else {

    print '<div id="somafmplugin">';
    print '<div class="containerbox menuitem noselection multidrop">';
    print '<i class="icon-toggle-closed mh menu fixed" name="somafmlist"></i>';
    print '<i class="icon-somafm fixed smallcover smallcover-svg"></i>';
    print '<div class="expand"><h3>'.get_int_text('label_somafm').'</h3></div>';
    print '</div>';
    print '<div id="somafmlist" class="dropmenu"></div>';
    print '</div>';

}

function getimage($c) {
    $img = (string) $c->xlimage;
    if (!$img) {
        $img = (string) $c->largeimage;
    }
    if (!$img) {
        $img = (string) $c->image;
    }
    return $img;
}

function format_listenlink($c, $p, $label) {
    $img = getimage($c);
    print '<div class="clickable clickstream draggable indent containerbox padright" name="'.(string) $p.'" streamimg="'.$img.'" streamname="'.$c->title.'">';
    print '<i class="'.audioClass($p[0]['format']).' playlisticon fixed"></i>';
    print '<div class="expand">'.$label.'&nbsp';
    switch ($p[0]['format']) {
        case 'mp3':
            print 'MP3';
            break;
        case 'aac':
            print 'AAC';
            break;
        case 'aacp':
            print 'AAC Plus';
            break;
        default:
            print 'Unknown Format';
            break;

    }
    print '</div>';
    print '</div>';
}
?>
