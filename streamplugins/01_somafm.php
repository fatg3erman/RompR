<?php
if (array_key_exists('populate', $_REQUEST)) {

    chdir('..');

    include ("includes/vars.php");
    include ("includes/functions.php");
    include ("international.php");

    print '<div class="containerbox indent padright">';
    print '<b>'.get_int_text("label_soma").'<br>';
    print '<a href="http://somafm.com" target="_blank">'.get_int_text("label_soma_beg").'</a></b>';
    print '</div>';

    $content = url_get_contents("http://api.somafm.com/channels.xml", $_SERVER['HTTP_USER_AGENT'], false, true);
    if ($content['status'] == "200") {
        debuglog("Loaded Soma FM channels list","SOMAFM");
        $x = simplexml_load_string($content['contents']);
        print '<div class="containerbox noselection wrap pipl indent">';
        foreach ($x->channel as $channel) {
            debuglog("Channel : ".$channel->title,"SOMAFM");
            if ($channel->highestpls) {
                $pls = (string) $channel->highestpls;
            } else {
                $pls = (string) $channel->fastpls[0];
            }
            print '<div class="pluginitem radioplugin_normal clickable clickstream draggable" name="'.$pls.'" streamname="'.(string) $channel->title.'" streamimg="'.getimage($channel).'">';
            print '<div class="helpfulalbum fullwidth">';
            print '<img class="masochist" src="'.getimage($channel).'" />';
            print '<div class="tagh albumthing sponklick title-menu artistnamething">'.utf8_encode($channel->title).'</div>';
            print '<div class="tagh albumthing"><i>'.utf8_encode($channel->genre).'</i></div>';
            if ($channel->description) {
                print '<div class="tagh albumthing playlistrow2">'.utf8_encode($channel->description).'</div>';
            }
            print '<div class="tagh albumthing bordered nosides">';
            if ($channel->highestpls) {
                format_listenlink($channel, $channel->highestpls, "HQ");
            }
            foreach ($channel->fastpls as $h) {
                format_listenlink($channel, $h, "");
            }
            foreach ($channel->slowpls as $h) {
                format_listenlink($channel, $h, "LQ");
            }
            print '</div>';
            if ($channel->twitter || $channel->dj) {
                print '<div class="tagh albumthing bordered nosides"><div class="containerbox line"><div class="expand">';
                if ($channel->twitter) {
                    print '<a href="http://twitter.com/@'.$channel->twitter.'" target="_blank">';
                    print '<i class="icon-twitter-logo smallicon padright"></i>';
                    print '</a>';
                }
                if ($channel->dj) {
                    print '<b>DJ: </b>'.$channel->dj.'</i>';
                }
                print '</div></div></div>';
            }
            if ($channel->listeners) {
                print '<div class="tagh albumthing playlistrow2">'.$channel->listeners.' '.get_int_text("lastfm_listeners").'</div>';
            }
            if ($channel->lastPlaying) {
                print '<div class="tagh albumthing" style="padding-bottom:0px"><b>Last Played</b></div><div class="tagh albumthing playlistrow2">'.$channel->lastPlaying.'</div>';
            }
            print '</div>';
            print '</div>';
        }

        print '</div>';

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
    print '<div class="clickable clickstream draggable containerbox line" name="'.(string) $p.'" streamimg="'.$img.'" streamname="'.$c->title.'">';
    print '<div class="expand">';
    print '<i class="'.audioClass($p[0]['format']).' smallicon"></i>';
    print $label.'&nbsp';
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
