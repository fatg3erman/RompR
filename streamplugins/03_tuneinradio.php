<?php

if (array_key_exists('populate', $_REQUEST)) {

    chdir('..');

    include ("includes/vars.php");
    include ("includes/functions.php");
    include ("international.php");
    include ("skins/".$skin."/ui_elements.php");
    
    $url = 'http://opml.radiotime.com/';
    $title = null;
    if (array_key_exists('url', $_REQUEST)) {
        $url = $_REQUEST['url'];
    } else {
        directoryControlHeader('tuneinlist', get_int_text('label_tuneinradio'));
        print '<div class="fullwidth padright" style="margin-bottom:0px"><div class="containerbox padright noselection fullwidth"><div class="expand">
            <input class="enter clearbox tuneinsearchbox" name="tuneinsearcher" type="text" ';
        if (array_key_exists('search', $_REQUEST)) {
            print 'value="'.$_REQUEST['search'].'" ';
        }
        print '/></div><button class="fixed tuneinsearchbutton" name="sonicthehedgehog">'.get_int_text('button_search').'</button></div></div>';
    }
    if (array_key_exists('title', $_REQUEST)) {
        $title = $_REQUEST['title'];
        directoryControlHeader($_REQUEST['target'], htmlspecialchars($title));
    }
    if (array_key_exists('search', $_REQUEST)) {
        directoryControlHeader('tuneinlist', get_int_text('label_tuneinradio'));
        $url .= 'Search.ashx?query='.urlencode($_REQUEST['search']);
    }
    
    debuglog("Getting URL ".$url,"TUNEIN");

    $result = url_get_contents($url);
    $opml = $result['contents'];
    $x = simplexml_load_string($opml);
    
    $v = (string) $x['version'];
    debuglog("OPML version is ".$v, "TUNEIN");
    
    parse_tree($x->body, $title);
    
    // print '<hr>';
    
} else {
    print '<div id="tuneinradio">';
    print albumHeader(array(
        'id' => 'tuneinlist',
        'Image' => 'newimages/tunein-logo.svg',
        'Searched' => 1,
        'AlbumUri' => null,
        'Year' => null,
        'Artistname' => '',
        'Albumname' => get_int_text('label_tuneinradio'),
        'why' => null,
        'ImgKey' => 'none',
        'class' => 'radio',
        'expand' => true
    ));
    print '<div id="tuneinlist" class="dropmenu notfilled">Loading...</div>';
    print '</div>';
}


function parse_tree($node, $title) {

    foreach ($node->outline as $o) {
        $att = $o->attributes();
        debuglog("  Text is ".$att['text'].", type is ".$att['type']);
        switch ($att['type']) {
            
            case '':
                print '<div class="configtitle textcentre brick_wide">';
                print '<div class="expand">'.$att['text'].'</div>';
                print '</div>';
                parse_tree($o, $title);
                break;
            
            case 'link':
                printRadioDirectory($att);
                break;
                
            case 'audio':
                switch ($att['item']) {
                    case 'station':
                        $sname = $att['text'];
                        $year = 'Radio Station';
                        break;
                        
                    case 'topic':
                        $sname = $title;
                        $year = 'Podcast Episode';
                        break;
                      
                    default:
                        $sname = $title;
                        $year = ucfirst($att['item']);
                        break;
                        
                }
                
                print albumHeader(array(
                    'id' => 'nodrop',
                    'Image' => 'getRemoteImage.php?url='.$att['image'],
                    'Searched' => 1,
                    'AlbumUri' => null,
                    'Year' => $year,
                    'Artistname' => ((string) $att['playing'] != (string) $att['subtext']) ? $att['subtext'] : null,
                    'Albumname' => $att['text'],
                    'why' => 'whynot',
                    'ImgKey' => 'none',
                    'streamuri' => $att['URL'],
                    'streamname' => $sname,
                    'streamimg' => 'getRemoteImage.php?url='.$att['image'],
                    'class' => 'radiochannel'
                ));
                break;
                
        }
    }
    
}

?>
