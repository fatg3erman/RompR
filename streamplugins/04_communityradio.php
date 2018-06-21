<?php
if (array_key_exists('populate', $_REQUEST)) {
    
    chdir('..');

    include ("includes/vars.php");
    include ("includes/functions.php");
    include ("international.php");
    include ("skins/".$skin."/ui_elements.php");

    foreach ($_REQUEST as $i => $r) {
        debuglog($i ."   : ".$r,"COMMRADIO");
    }
    
    $pagination = 100;
    $page = $_REQUEST['page'];
    $searchterms = array('name', 'country', 'state', 'language', 'tag');
    
    if ($_REQUEST['populate'] == 1) {

        directoryControlHeader('communityradiolist', get_int_text('label_communityradio'));

        print '<div class="configtitle textcentre brick_wide">List By:</div>';
        
        $content = url_get_contents('http://www.radio-browser.info/webservice/json/countries', ROMPR_IDSTRING, false, true);
        if ($content['status'] == '200') {
            $countries = json_decode($content['contents'], true);
            debuglog("Loaded Countries List","COMMRADIO");
            print '<div class="fullwidth padright brick_wide containerbox dropdown-container" style="margin-bottom:0px">';
            comm_radio_make_list_button('country');
            print '<div class="selectholder expand">';
            print '<select id="communityradiocountry">';
            foreach ($countries as $country) {
                $val = strtolower($country['value']);
                print '<option value="'.$val.'"';
                if ($val == $_REQUEST['country']) {
                    print ' selected';
                }
                print '>'.$country['value'].' ('.$country['stationcount'].' stations)</option>';
            }
            print '</select>';
            print '</div>';
            print '</div>';
        }

        $content = url_get_contents('http://www.radio-browser.info/webservice/json/languages', ROMPR_IDSTRING, false, true);
        if ($content['status'] == '200') {
            $langs = json_decode($content['contents'], true);
            debuglog("Loaded Languages List","COMMRADIO");
            print '<div class="fullwidth padright brick_wide containerbox dropdown-container" style="margin-bottom:0px">';
            comm_radio_make_list_button('language');
            print '<div class="selectholder expand">';
            
            print '<select id="communityradiolanguage">';
            foreach ($langs as $lang) {
                $val = strtolower($lang['value']);
                print '<option value="'.$val.'"';
                if ($val == $_REQUEST['language']) {
                    print ' selected';
                }
                print '>'.$lang['value'].' ('.$lang['stationcount'].' stations)</option>';
            }
            print '</select>';
            print '</div>';
            print '</div>';
        }

        $content = url_get_contents('http://www.radio-browser.info/webservice/json/tags', ROMPR_IDSTRING, false, true);
        if ($content['status'] == '200') {
            $tags = json_decode($content['contents'], true);
            debuglog("Loaded Tags List","COMMRADIO");
            print '<div class="fullwidth padright brick_wide containerbox dropdown-container" style="margin-bottom:0px">';
            comm_radio_make_list_button('tag');
            print '<div class="selectholder expand">';
            
            print '<select id="communityradiotag">';
            foreach ($tags as $tag) {
                $val = strtolower($tag['value']);
                print '<option value="'.$val.'"';
                if ($val == $_REQUEST['tag']) {
                    print ' selected';
                }
                print '>'.$tag['value'].' ('.$tag['stationcount'].' stations)</option>';
            }
            print '</select>';
            print '</div>';
            print '</div>';
        }
        
        print '<div class="configtitle textcentre brick_wide">Search All Stations:</div>';
        foreach ($searchterms as $term) {
            print '<div class="containerbox dropdown-container brick_wide fullwidth" name="'.$term.'">';
            print '<div class="fixed comm-search-label"><span class="cslt"><b>'.ucfirst($term).'</b></span></div>';
            print '<div class="expand">';
            print '<input class="comm_radio_searchterm" name="'.$term.'" type="text" />';
            print '</div>';
            print '</div>';
        }
        print '<div class="containerbox">';
        print '<div class="expand"></div>';
        print '<button class="fixed" name="commradiosearch">'.get_int_text('button_search').'</button>';
        print '</div>';
        
        print '<div class="configtitle textcentre brick_wide">Order By:</div>';
        foreach (array('name', 'country', 'language', 'state', 'tags', 'votes', 'bitrate') as $o) {
            print '<div class="styledinputs">';
            print '<input id="commradioorderby'.$o.'" class="topcheck" name="commradioorderby" value="'.$o.'" type="radio"';
            if ($_REQUEST['order'] == $o) {
                print ' checked';
            }
            print ' />';
            print '<label for="commradioorderby'.$o.'">'.ucfirst($o).'&nbsp;&nbsp;</label>';
            print '</div>';
        }

        print '<div id="communitystations" class="fullwidth padright holderthing">';
        
    }

        
    $url = '';
    switch ($_REQUEST['listby']) {
        case 'country':
            $url = 'http://www.radio-browser.info/webservice/json/stations/bycountryexact/'.rawurlencode($_REQUEST['country']).'?';
            print '<div class="configtitle textcentre brick_wide"><b>'.ucfirst($_REQUEST['listby']).' - '.ucwords($_REQUEST[$_REQUEST['listby']]).'</b></div>';
            break;

        case 'language':
            $url = 'http://www.radio-browser.info/webservice/json/stations/bylanguageexact/'.rawurlencode($_REQUEST['language']).'?';
            print '<div class="configtitle textcentre brick_wide"><b>'.ucfirst($_REQUEST['listby']).' - '.ucwords($_REQUEST[$_REQUEST['listby']]).'</b></div>';
            break;

        case 'tag':
            $url = 'http://www.radio-browser.info/webservice/json/stations/bytagexact/'.rawurlencode($_REQUEST['tag']).'?';
            print '<div class="configtitle textcentre brick_wide"><b>'.ucfirst($_REQUEST['listby']).' - '.ucwords($_REQUEST[$_REQUEST['listby']]).'</b></div>';
            break;
            
        case 'search':
            $url = 'http://www.radio-browser.info/webservice/json/stations/search?';
            $ourterms = array();
            foreach ($searchterms as $t) {
                if (array_key_exists($t, $_REQUEST) && $_REQUEST[$t] != '') {
                    $ourterms[] = $t.'='.rawurlencode($_REQUEST[$t]);
                }
            }
            print '<div class="configtitle textcentre brick_wide"><b>'.ucfirst($_REQUEST['listby']).' - '.rawurldecode(implode(', ', $ourterms)).'</b></div>';
            $url .= implode('&', $ourterms).'&';
            break;
    }
    
    $url .= 'order='.$_REQUEST['order'];
    
    switch ($_REQUEST['order']) {
        case 'bitrate':
        case 'votes':
            $url .= '&reverse=true';
            break;
    }

    $content = url_get_contents($url, ROMPR_IDSTRING, false, true);
    if ($content['status'] == '200') {
        $stations = json_decode($content['contents'], true);
        comm_radio_do_page_buttons($page, count($stations), $pagination);
        for ($i = 0; $i < $pagination; $i++) {
            $index = $page * $pagination + $i;
            if ($index >= count($stations)) {
                break;
            }
            $station = comm_radio_sanitise_station($stations[$index]);
            print albumHeader(array(
                'id' => 'communityradio_'.$index,
                'Image' => comm_radio_get_image($station),
                'Searched' => 1,
                'AlbumUri' => null,
                'Year' => null,
                'Artistname' => htmlspecialchars($station['tags']),
                'Albumname' => htmlspecialchars($station['name']),
                'why' => 'whynot',
                'ImgKey' => 'none',
                'streamuri' => $station['playurl'],
                'streamname' => $station['name'],
                'streamimg' => comm_radio_get_image($station),
                'class' => 'radiochannel'
            ));
            print '<div id="communityradio_'.$index.'" class="dropmenu">';
            trackControlHeader('','','communityradio_'.$index, array(array('Image' => comm_radio_get_image($station))));
            print '<div class="containerbox ninesix indent padright">'.utf8_encode($station['state']).utf8_encode($station['country']).'</div>';
            print '<div class="containerbox ninesix indent padright">'.utf8_encode($station['votes']).' Upvotes, '.utf8_encode($station['negativevotes']).' Downvotes</div>';
            if ($station['homepage']) {
                print '<a href="'.$station['homepage'].'" target="_blank">';
                print '<div class="containerbox indent padright menuitem">';
                print '<i class="icon-www playlisticon fixed"></i>';
                print '<div class="expand">'.get_int_text('label_station_website').'</div>';
                print '</div>';
                print '</a>';
            }
            print '<div class="containerbox expand ninesix indent padright"><b>Listen:</b></div>';
            print '<div class="clickable clickstream draggable indent containerbox padright menuitem" name="'.$station['playurl'].'" streamimg="'.comm_radio_get_image($station).'" streamname="'.$station['name'].'">';
            print '<i class="'.audioClass($station['codec']).' playlisticon fixed"></i>';
            print '<div class="expand">'.$station['bitrate'].'kbps &nbsp'.$station['codec'].'</div>';
            print '</div>';
            print '</div>';
        }
        comm_radio_do_page_buttons($page, count($stations), $pagination);
    } else {
        print 'ERROR Downloading Stations List';
    }
    
    if ($_REQUEST['populate'] == 1) {
        print '</div>';
    }

} else {

    print '<div id="communityradioplugin">';
    print albumHeader(array(
        'id' => 'communityradiolist',
        'Image' => 'newimages/broadcast.svg',
        'Searched' => 1,
        'AlbumUri' => null,
        'Year' => null,
        'Artistname' => '',
        'Albumname' => get_int_text('label_communityradio'),
        'why' => null,
        'ImgKey' => 'none',
        'class' => 'radio',
        'expand' => true
    ));
    print '<div id="communityradiolist" class="dropmenu notfilled">';
    print '<div class="textcentre">Loading...</div></div>';
    print '</div>';
    
}

function comm_radio_make_list_button($which) {
    print '<div class="fixed styledinputs commradiolistby">';
    print '<span class="cclb">';
    print '<input id="commradiolistby'.$which.'" class="topcheck" name="commradiolistby" value="'.$which.'" type="radio"';
    if ($_REQUEST['listby'] == $which) {
        print ' checked';
    }
    print ' />';
    print '<label for="commradiolistby'.$which.'"><b>'.ucfirst($which).'&nbsp;&nbsp;</b></label>';
    print '</span>';
    print '</div>';
}

function comm_radio_get_image($station) {
    if ($station['favicon']) {
        return 'getRemoteImage.php?url='.$station['favicon'];
    } else {
        return 'newimages/broadcast.svg';
    }
}

function comm_radio_do_page_buttons($page, $count, $per_page) {
    print '<div class="fullwidth brick_wide"><div class="containerbox padright noselection menuitem">';
    $class = ($page == 0) ? ' button-disabled' : ' clickable clickicon clickcommradioback';
    print '<i class="fixed icon-left-circled medicon'.$class.'"></i>';
    print '<div class="expand textcentre">Showing '.($page*$per_page+1).' to '.min(array(($page*$per_page+$per_page), $count)).' of '.$count.'</div>';
    $class = ((($page+1) * $per_page) >= $count || $count < $per_page) ? ' button-disabled' : ' clickable clickicon clickcommradioforward';
    print '<i class="fixed icon-right-circled medicon'.$class.'"></i>';
    print '</div>';
    print '</div>';
}

function comm_radio_sanitise_station($station) {
    global $prefs;
    $blank_station = array(
        'tags' => '',
        'name' => ROMPR_UNKNOWN_STREAM,
        'state' => '',
        'country' => '',
        'votes' => 0,
        'negativevotes' => 0,
        'codec' => 'Unknown Codec',
        'bitrate' => 'Unknown ',
        'favicon' => null,
        'homepage' => null
    );
    
    $result = array_merge($blank_station, $station);
    if ($result['state'] && $result['country']) {
        $result['state'] .= ', ';
    }
    if ($result['bitrate'] == 0) {
        $result['bitrate'] = 'Unknown ';
    }
    // No real idea whay one works for one player but not the other. MPD won't load the M3U files,
    // Mopidy won't load the PLS files. All I do is send a load/add and a URL.....
    if ($prefs['player_backend'] == 'mpd') {
        $result['playurl'] = 'http://www.radio-browser.info/webservice/v2/pls/url/'.$station['id'];
    } else {
        $result['playurl'] = 'http://www.radio-browser.info/webservice/v2/m3u/url/'.$station['id'];
    }
    return $result;
}

?>
