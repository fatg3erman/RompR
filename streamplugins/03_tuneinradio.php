<?php

if (array_key_exists('populate', $_REQUEST)) {

    chdir('..');

    include("includes/vars.php");
    include("includes/functions.php");
    include("international.php");
    
    $url = 'http://opml.radiotime.com/';
    $title = null;
    if (array_key_exists('url', $_REQUEST)) {
        $url = $_REQUEST['url'];
    } else {
        print '<div class="fullwidth padright" style="margin-bottom:0px"><div class="containerbox padright noselection fullwidth"><div class="expand">
            <input class="enter clearbox" name="tuneinsearcher" type="text" ';
        if (array_key_exists('search', $_REQUEST)) {
            print 'value="'.$_REQUEST['search'].'" ';
        }
        print '/></div><button class="fixed" name="sonicthehedgehog">'.get_int_text('button_search').'</button></div></div>';
    }
    if (array_key_exists('title', $_REQUEST)) {
        $title = $_REQUEST['title'];
    }
    if (array_key_exists('search', $_REQUEST)) {
        $url .= 'Search.ashx?query='.urlencode($_REQUEST['search']);
    }
    
    debuglog("Getting URL ".$url,"TUNEIN");


    $result = url_get_contents($url);
    $opml = $result['contents'];
    $x = simplexml_load_string($opml);
    parse_tree($x->body, $title);
    
    print '<hr>';
    
} else {
    print '<div id="tuneinradio">';
    print '<div class="containerbox menuitem noselection multidrop">';
    print '<i class="icon-toggle-closed mh menu fixed" name="tuneinlist"></i>';
    print '<i class="icon-tunein fixed smallcover smallcover-svg"></i>';
    print '<div class="expand"><h3>'.get_int_text('label_tuneinradio').'</h3></div>';
    print '</div>';
    print '<div id="tuneinlist" class="dropmenu"></div>';
    print '</div>';
}

function parse_tree($node, $title) {

    foreach ($node->outline as $o) {
        $att = $o->attributes();
        debuglog("  Text is ".$att['text'].", type is ".$att['type']);
        switch ($att['type']) {
            
            case '':
                print '<div class="configtitle textcentre">';
                print '<div class="expand">'.$att['text'].'</div>';
                print '</div>';
                parse_tree($o, $title);
                break;
            
            case 'link':
                $name = md5($att['URL']);
                print '<div class="directory containerbox menuitem">';
                print '<input type="hidden" value="'.rawurlencode($att['URL']).'" />';
                print '<input type="hidden" value="'.rawurlencode($att['text']).'" />';
                print '<i class="browse menu mh fixed icon-toggle-closed" name="tunein_'.$name.'"></i>';
                print '<i class="icon-folder-open-empty fixed smallicon"></i>';
                print '<div class="expand">'.$att['text'].'</div>';
                print '</div>';
                print '<div id="tunein_'.$name.'" class="dropmenu"></div>';
                break;
                
            case 'audio':
                switch ($att['item']) {
                    case 'station':
                        $sname = $att['text'];
                        break;
                      
                    default:
                        $sname = $title;
                        break;
                        
                }
                print '<div class="albumheader clickable clickstream draggable containerbox menuitem" name="'.$att['URL'].'" streamname="'.$sname.'" streamimg="getRemoteImage.php?url='.$att['image'].'">';
                print '<div class="smallcover fixed">';
                print '<img class="smallcover fixed" src="getRemoteImage.php?url='.$att['image'].'" />';
                print '</div>';
                print '<div class="expand">'.$att['text'];
                switch ($att['item']) {
                    case 'station':
                      print '<span class="notbold"> (Radio Station)</span>';
                      break;

                      case 'topic':
                        print '<span class="notbold"> (Podcast)</span>';
                        break;
                      
                    default:
                        print '<span class="notbold"> ('.ucfirst($att['item']).')</span>';
                        break;
                        
                }
                if ((string) $att['playing'] != (string) $att['subtext']) {
                    print '<br><span class="notbold"><i>'.$att['subtext'].'</i></span>';
                }
                print '</div>';
                print '</div>';
                break;
                
            
        }
    }
    
}

?>
