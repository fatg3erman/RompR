<?php
if (array_key_exists('populate', $_REQUEST)) {

    chdir('..');

    include("includes/vars.php");
    include("includes/functions.php");
    include("international.php");
    include("skins/".$skin."/ui_elements.php");

    $base_url = 'http://api.dirble.com/v2/';
    $countries = array();
    $categories = array();

    $to_get = '';
    $country = $prefs['newradiocountry'];
    $page = 1;
    $searchterm = '';
    if (array_key_exists('country', $_REQUEST)) {
        $to_get = $base_url.$_REQUEST['country'].'/stations';
        $country = $_REQUEST['country'];
    }
    if (array_key_exists('page', $_REQUEST)) {
        $page = $_REQUEST['page'];
    }
    if (array_key_exists('url', $_REQUEST)) {
        $to_get = rawurldecode($_REQUEST['url']);
    }
    if (array_key_exists('search', $_REQUEST)) {
        $to_get = null;
        $searchterm = rawurldecode($_REQUEST['search']);
    }
    debuglog("Country Is ".$country,"RADIO");

    if ($_REQUEST['populate'] == 2) {
        directoryControlHeader('bbclist', get_int_text('label_streamradio'));
        print '<div class="containerbox noselection wrap pipl">';
        $json = get_from_dirble($base_url.'/countries');
        foreach ($json['json'] as $station) {
            $countries[$station['name']] = 'countries/'.$station['country_code'];
        }
        ksort($countries);
        $json = get_from_dirble($base_url.'/categories/tree');
        foreach ($json['json'] as $station) {
            $categories[$station['title']] = 'category/'.$station['id'];
            foreach ($station['children'] as $child) {
                $categories[$child['title']] = 'category/'.$child['id'];
            }
        }
        print '<div class="fullwidth padright" style="margin-bottom:0px"><div class="selectholder" style="width:100%">';
        print '<select id="radioselector" onchange="nationalRadioPlugin.changeradiocountry()">';
        print '<option disabled>_______________COUNTRIES______________</option>';
        foreach ($countries as $name => $link) {
            print '<option value="'.$link.'"';
            if ($link == $country) {
                print ' selected';
            }
            print '>'.$name.'</option>';
        }
        print '<option disabled>_______________CATEGORIES______________</option>';
        foreach ($categories as $name => $link) {
            print '<option value="'.$link.'"';
            if ($link == $country) {
                print ' selected';
            }
            print '>'.$name.'</option>';
        }
        print '</select></div></div>';

        print '<div class="fullwidth padright" style="margin-bottom:0px"><div class="containerbox padright noselection fullwidth"><div class="expand">
            <input class="enter clearbox" name="radiosearcher" type="text" ';
        if ($searchterm) {
            print 'value="'.$searchterm.'" ';
        }
        print '/></div><button class="fixed" name="bumfeatures">'.get_int_text('button_search').'</button></div></div>';

        print '</div>';

        print '<div id="alltheradiostations">';
    }

    $json = array('json' => array());
    if ($to_get) {
        $json = get_from_dirble($to_get, $page);
    } else if ($searchterm) {
        $json = dirble_search($searchterm, $page, $country);
    }
    usort($json['json'], 'sort_by_station');
    do_page_buttons($json, false);
    $count = 0;
    foreach ($json['json'] as $station) {
        $streams = check_streams($station['streams']);
        if (count($streams) > 0) {
            debuglog("Station ".$station['name'].' '.count($station['streams']).' streams',"RADIO");
            $image = null;
            if ($station['image']['url']) {
                $image = $station['image']['url'];
            } else if ($station['image']['thumb']['url']) {
                $image = $station['image']['thumb']['url'];
            } else {
                $image = "newimages/broadcast.svg";
            }
            $k = check_for_playlist($streams);

            print albumHeader(array(
                'id' => 'radio_'.$count,
                'Image' => $image,
                'Searched' => 1,
                'AlbumUri' => null,
                'Year' => null,
                'Artistname' => get_categories($station),
                'Albumname' => $station['name'],
                'why' => 'whynot',
                'ImgKey' => 'none',
                'streamuri' => $k,
                'streamname' => $station['name'],
                'streamimg' => $image
            ));

            print '<div id="radio_'.$count.'" class="dropmenu">';

            trackControlHeader('','','radio_'.$count, array(array('Image' => $image)));
            
            print '<div class="containerbox rowspacer"></div>';
            print '<div class="containerbox expand ninesix indent padright"><b>Listen:</b></div>';

            foreach ($streams as $s) {
                debuglog("Content type ".$s['content_type']." and uri ".$s['stream'],"DIRBLE");
                print '<div class="clickable clickstream draggable indent containerbox padright menuitem" name="'.trim($s['stream']).'" streamname="'.trim($station['name']).'" streamimg="'.$image.'">';
                print '<i class="'.audioClass($s['content_type']).' playlisticon fixed"></i>';
                print '<div class="expand">';
                print get_speed($s['bitrate']);
                print '</div>';
                print '</div>';
            }
            
            print '<div class="containerbox rowspacer"></div>';

            if (array_key_exists('website', $station) && $station['website'] != '') {
                print '<a href="'.$station['website'].'" target="_blank">';
                print '<div class="containerbox indent padright menuitem">';
                print '<i class="icon-www playlisticon fixed"></i>';
                print '<div class="expand">'.get_int_text('label_station_website').'</div>';
                print '</div>';
                print '</a>';
            }
            if (array_key_exists('facebook', $station) && $station['facebook'] != '') {
                print '<a href="'.$station['facebook'].'" target="_blank">';
                print '<div class="containerbox indent padright menuitem">';
                print '<i class="icon-facebook-logo playlisticon fixed"></i><div class="expand">Facebook</div>';
                print '</div>';
                print '</a>';
            }
            if (array_key_exists('twitter', $station) && $station['twitter'] != '') {
                $t = $station['twitter'];
                if (preg_match('/^http/', $t)) {

                } else if (preg_match('/^@/', $t)) {
                    $t = 'http://twitter.com/'.$t;
                } else {
                    $t = 'http://twitter.com/@'.$t;
                }
                print '<a href="'.$t.'" target="_blank">';
                print '<div class="containerbox indent padright menuitem">';
                print '<i class="icon-twitter-logo playlisticon fixed"></i><div class="expand">Twitter</div>';
                print '</div>';
                print '</a>';
            }

            print '</div>';
        }
        $count++;
    }
    do_page_buttons($json, true);
    print '</div>';
    if ($_REQUEST['populate'] == 2) {
        print '</div>';
    }
} else {

    print '<div id="nationalradio">';
    print albumHeader(array(
        'id' => 'bbclist',
        'Image' => 'newimages/dirble-logo.svg',
        'Searched' => 1,
        'AlbumUri' => null,
        'Year' => null,
        'Artistname' => '',
        'Albumname' => get_int_text('label_streamradio'),
        'why' => null,
        'ImgKey' => 'none'
    ));
    print '<div id="bbclist" class="dropmenu notfilled"><div class="textcentre">Loading...</div></div>';
    print '</div>';

}

function check_streams($streams) {
    $ss = array();
    foreach ($streams as $s) {
        if (!preg_match('/\.ram$/', $s['stream']) && !preg_match('/\.qtl$/', $s['stream']) && audioClass($s['content_type']) != 'notastream') {
            $ss[] = $s;
        }
    }
    return $ss;
}

function check_for_playlist($streams) {
    foreach ($streams as $s) {
        if (audioClass($s['content_type']) == 'icon-doc-text') {
            debuglog("   Using Playlist ".$s['stream'],"DIRBLE");
            return $s['stream'];
        }
    }
    return $streams[0]['stream'];
}

function get_from_dirble($url, $page = 1) {
    debuglog("Getting ".$url,"DIRBLE");
    $token = "9dc8c8f09575129e9289717a9b8377658906b460";
    $per_page = 30;
    $result = array('url' => $url,
                    'perpage' => $per_page,
                    'first' => (($page-1)*$per_page)+1,
                    'prevpage' => $page-1,
                    'nextpage' => 0,
                    'json' => array(),
                    'spage' => 0,
                    'total' => 0);
    $content = url_get_contents($url.'?page='.$page.'&per_page='.$per_page.'&token='.$token, ROMPR_IDSTRING, true);
    $result['json'] = array_merge($result['json'], json_decode($content['contents'], true));
    if (array_key_exists('X-Page', $content['headers'])) {
        debuglog("  Got Page ".$content['headers']['X-Page'],"DIRBLE");
    }
    if (array_key_exists('X-Total', $content['headers'])) {
        debuglog("  Total ".$content['headers']['X-Total'],"DIRBLE");
        $result['total'] = $content['headers']['X-Total'];
    }
    if (array_key_exists('X-Next-Page', $content['headers'])) {
        debuglog("  Next Page : ".$content['headers']['X-Next-Page'],"DIRBLE");
        $result['nextpage'] = $content['headers']['X-Next-Page'];
    }
    $result['num'] = $result['first'] + count($result['json']) - 1;
    debuglog('Showing '.$result['first'].' to '.$result['num'].' of '.$result['total'],"DIRBLE");
    debuglog('Next Page : '.$result['nextpage'],"DIRBLE");
    debuglog('Prev Page : '.$result['prevpage'],"DIRBLE");
    return $result;
}

function dirble_search($term, $page, $country) {
    debuglog("Searching For ".$term." in ".$country,"RADIO");
    $token = "9dc8c8f09575129e9289717a9b8377658906b460";
    $per_page = 30;
    $result = array('url' => '',
                    'perpage' => $per_page,
                    'first' => (($page-1)*$per_page)+1,
                    'prevpage' => $page-1,
                    'nextpage' => 0,
                    'json' => array(),
                    'spage' => $page+1,
                    'total' => 0,
                    'term' => $term);
    $sterms = array('query' => $term, 'page' => $page);
    if (preg_match('#countries/(.*)#', $country, $matches)) {
        $sterms['country'] = $matches[1];
    } else if (preg_match('#category/(.*)#', $country, $matches)) {
        $sterms['category'] = $matches[1];
    }
    $content = url_get_contents('http://api.dirble.com/v2/search?token='.$token, ROMPR_IDSTRING, false, true, false, null, null, $sterms);
    $result['json'] = array_merge($result['json'], json_decode($content['contents'], true));
    $result['num'] = $result['first'] + count($result['json']) - 1;
    return $result;
}

function get_categories($station) {
    $cat = array();
    foreach ($station['categories'] as $c) {
        $cat[] = $c['title'];
    }
    return implode(', ',$cat);
}

function get_speed($b) {
    if ($b > 0) {
        return $b." Kbps";
    } else {
        return "Unknown Kbps";
    }
}

function sort_by_station($a, $b) {
    return ($a['name'] < $b['name']) ? -1 : 1;
}

function do_page_buttons($json, $is_bottom) {
    if ($json['total'] > 0) {
        print '<div class="fullwidth"><div class="containerbox padright noselection menuitem">';
        $class = ($json['prevpage'] == 0) ? ' button-disabled' : ' clickable clickicon clickradioback';
        print '<i class="fixed icon-left-circled medicon'.$class.'"></i>';
        print '<div class="expand textcentre">Showing '.$json['first'].' to '.$json['num'].' of '.$json['total'].'</div>';
        $class = ($json['nextpage'] == 0) ? ' button-disabled' : ' clickable clickicon clickradioforward';
        print '<i class="fixed icon-right-circled medicon'.$class.'"></i>';
        print '</div>';
        print '<input type="hidden" name="url" value="'.$json['url'].'" />';
        print '<input type="hidden" name="next" value="'.$json['nextpage'].'" />';
        print '<input type="hidden" name="prev" value="'.$json['prevpage'].'" />';
        print '</div>';
    } else if ($is_bottom && $json['spage'] > 0) {
        print '<div class="fullwidth"><div class="containerbox padright noselection fullwidth menuitem">';
        print '<div class="expand textcentre clickable clickicon clicksearchmore">Show More Results...</div>';
        print '</div>';
        print '<input type="hidden" name="spage" value="'.$json['spage'].'" />';
        print '<input type="hidden" name="term" value="'.rawurlencode($json['term']).'" />';
        print '</div>';
    }
}

?>
