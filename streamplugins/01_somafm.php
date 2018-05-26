<?php
if (array_key_exists('populate', $_REQUEST)) {

    chdir('..');

    include ("includes/vars.php");
    include ("includes/functions.php");
    include ("international.php");
    include ("skins/".$skin."/ui_elements.php");

    directoryControlHeader('somafmlist', get_int_text('label_somafm'));
    print '<div class="containerbox padright indent ninesix bumpad">';
    print '<a href="http://somafm.com" target="_blank">'.get_int_text("label_soma_beg").'</a>';
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
            
            print albumHeader(array(
                'id' => 'somafm_'.$count,
                'Image' => getimage($channel),
                'Searched' => 1,
                'AlbumUri' => null,
                'Year' => null,
                'Artistname' => utf8_encode($channel->genre),
                'Albumname' => utf8_encode($channel->title),
                'why' => 'whynot',
                'ImgKey' => 'none',
                'streamuri' => $pls,
                'streamname' => (string) $channel->title,
                'streamimg' => getimage($channel)
            ));
            
            print '<div id="somafm_'.$count.'" class="dropmenu">';
            trackControlHeader('','','somafm_'.$count, array(array('Image' => getimage($channel))));
            if ($channel->description) {
                print '<div class="containerbox ninesix indent padright">'.utf8_encode($channel->description).'</div>';
            }
            if ($channel->listeners) {
                print '<div class="containerbox indent padright">';
                print '<div class="expand">'.$channel->listeners.' '.trim(get_int_text("lastfm_listeners"),':').'</div>';
                print '</div>';
            }
            
            print '<div class="containerbox rowspacer"></div>';
            print '<div class="containerbox expand ninesix indent padright"><b>Listen:</b></div>';

            if ($channel->highestpls) {
                format_listenlink($channel, $channel->highestpls, "High Quality");
            }
            foreach ($channel->fastpls as $h) {
                format_listenlink($channel, $h, "Standard Quality");
            }
            foreach ($channel->slowpls as $h) {
                format_listenlink($channel, $h, "Low Quality");
            }

            print '<div class="containerbox rowspacer"></div>';

            if ($channel->twitter && $channel->dj) {
                print '<a href="http://twitter.com/@'.$channel->twitter.'" target="_blank">';
                print '<div class="containerbox indent padright menuitem">';
                print '<i class="icon-twitter-logo playlisticon fixed"></i>';
                print '<div class="expand"><b>DJ: </b>'.$channel->dj.'</div>';
                print '</div></a>';
            }
            if ($channel->lastPlaying) {
                print '<div class="containerbox indent padright menuitem notbold">';
                print '<b>'.get_int_text('label_last_played').'</b>&nbsp;';
                print $channel->lastPlaying;
                print '</div>';
            }

            print '</div>';
            $count++;
        }
        
    }

} else {

    print '<div id="somafmplugin">';
    print albumHeader(array(
        'id' => 'somafmlist',
        'Image' => 'newimages/somafmlogo.gif',
        'Searched' => 1,
        'AlbumUri' => null,
        'Year' => null,
        'Artistname' => '',
        'Albumname' => get_int_text('label_somafm'),
        'why' => null,
        'ImgKey' => 'none'
    ));
    print '<div id="somafmlist" class="dropmenu notfilled">';
    print '<div class="textcentre">Loading...</div></div>';
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
    print '<div class="clickable clickstream draggable indent containerbox padright menuitem" name="'.(string) $p.'" streamimg="'.$img.'" streamname="'.$c->title.'">';
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
