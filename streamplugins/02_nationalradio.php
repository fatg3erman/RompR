<?php

class dirbleplugin {

    public function __construct() {
        global $prefs;
        $this->base_url = 'http://api.dirble.com/v2/';
        $this->to_get = '';
        $this->country = $prefs['newradiocountry'];
        $this->page = 1;
        $this->searchterm = '';
    }

    public function doHeader() {
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
            'ImgKey' => 'none',
            'class' => 'radio',
            'expand' => true
        ));
        print '<div id="bbclist" class="dropmenu notfilled"><div class="configtitle textcentre"><b>'.get_int_text('label_loading').'</b></div></div>';
        print '</div>';
    }

    public function parseParams() {
        if (array_key_exists('country', $_REQUEST)) {
            $this->to_get = $this->base_url.$_REQUEST['country'].'/stations';
            $this->country = $_REQUEST['country'];
        }
        if (array_key_exists('page', $_REQUEST)) {
            $this->page = $_REQUEST['page'];
        }
        if (array_key_exists('url', $_REQUEST)) {
            $this->to_get = rawurldecode($_REQUEST['url']);
        }
        if (array_key_exists('search', $_REQUEST)) {
            $this->to_get = null;
            $this->searchterm = rawurldecode($_REQUEST['search']);
        }
        debuglog("Country Is ".$this->country,"DIRBLE");
    }

    public function doDropdownHeader() {
        $countries = array();
        $categories = array();
        directoryControlHeader('bbclist', get_int_text('label_streamradio'));
        $json = $this->get_from_dirble($this->base_url.'countries');
        if (count($json['json']) == 0) {
            print '<div class="configttitle textcentre brick_wide"><h3>Got no response from Dirble!</h3></div>"';
            exit(0);
        }
        foreach ($json['json'] as $station) {
            $countries[$station['name']] = 'countries/'.$station['country_code'];
        }
        ksort($countries);

        $json = $this->get_from_dirble($this->base_url.'categories/tree');
        foreach ($json['json'] as $station) {
            $categories[$station['title']] = 'category/'.$station['id'];
            foreach ($station['children'] as $child) {
                $categories[$child['title']] = 'category/'.$child['id'];
            }
        }
        print '<div class="fullwidth padright brick_wide" style="margin-bottom:0px"><div class="selectholder" style="width:100%">';
        print '<select id="radioselector" onchange="nationalRadioPlugin.changeradiocountry()">';
        print '<option disabled>_______________COUNTRIES______________</option>';
        foreach ($countries as $name => $link) {
            print '<option value="'.$link.'"';
            if ($link == $this->country) {
                print ' selected';
            }
            print '>'.$name.'</option>';
        }
        print '<option disabled>_______________CATEGORIES______________</option>';
        foreach ($categories as $name => $link) {
            print '<option value="'.$link.'"';
            if ($link == $this->country) {
                print ' selected';
            }
            print '>'.$name.'</option>';
        }
        print '</select></div></div>';

        print '<div class="fullwidth padright brick_wide" style="margin-bottom:0px"><div class="containerbox padright noselection fullwidth"><div class="expand">
            <input class="enter clearbox searchdirble" name="radiosearcher" type="text" ';
        if ($this->searchterm) {
            print 'value="'.$this->searchterm.'" ';
        }
        print '/></div><button class="fixed dirblesearch searchbutton iconbutton" name="bumfeatures"></button></div></div>';

    }

    public function doStations() {
        $json = array('json' => array());
        if ($this->to_get) {
            $json = $this->get_from_dirble($this->to_get, $this->page);
        } else if ($this->searchterm) {
            $json = $this->dirble_search($this->searchterm, $this->page, $this->country);
        }
        usort($json['json'], 'dirble_sort_by_station');
        $this->do_page_buttons($json, false);
        $count = 0;
        foreach ($json['json'] as $station) {
            $this->doStation($station, $count);
            $count++;
        }
        $this->do_page_buttons($json, true);
        print '</div>';

    }

    // -- Private Functions -- //

    private function doStation($station, $count) {
        $streams = $this->check_streams($station['streams']);
        if (count($streams) > 0) {
            debuglog("Station ".$station['name'].' '.count($station['streams']).' streams',"RADIO");
            $image = null;
            $streamimage = '';
            if ($station['image']['url']) {
                $streamimage = 'getRemoteImage.php?url='.$station['image']['url'];
                $image = $streamimage.'&rompr_backup_type=stream';
            } else if ($station['image']['thumb']['url']) {
                $streamimage = 'getRemoteImage.php?url='.$station['image']['thumb']['url'];
                $image = $streamimage.'&rompr_backup_type=stream';
            } else {
                $image = "newimages/broadcast.svg";
            }
            $k = $this->check_for_playlist($streams);

            print albumHeader(array(
                'id' => 'radio_'.$count,
                'Image' => $image,
                'Searched' => 1,
                'AlbumUri' => null,
                'Year' => null,
                'Artistname' => $this->get_categories($station),
                'Albumname' => $station['name'],
                'why' => 'whynot',
                'ImgKey' => 'none',
                'streamuri' => $k,
                'streamname' => $station['name'],
                'streamimg' => $streamimage,
                'class' => 'radiochannel',
                'expand' => true
            ));

            print '<div id="radio_'.$count.'" class="dropmenu">';

            trackControlHeader('','','radio_'.$count, array(array('Image' => $image)));

            print '<div class="containerbox rowspacer"></div>';
            print '<div class="containerbox expand ninesix indent padright"><b>Listen:</b></div>';

            foreach ($streams as $s) {
                debuglog("Content type ".$s['content_type']." and uri ".$s['stream'],"DIRBLE");
                print '<div class="clickable clickstream playable draggable indent containerbox padright menuitem" name="'.trim($s['stream']).'" streamname="'.trim($station['name']).'" streamimg="'.$streamimage.'">';
                print '<i class="'.audioClass($s['content_type']).' smallicon fixed"></i>';
                print '<div class="expand">';
                print $this->get_speed($s['bitrate']);
                print '</div>';
                print '</div>';
            }

            print '<div class="containerbox rowspacer"></div>';

            if (array_key_exists('website', $station) && $station['website'] != '') {
                print '<a href="'.$station['website'].'" target="_blank">';
                print '<div class="containerbox indent padright menuitem">';
                print '<i class="icon-www smallicon fixed"></i>';
                print '<div class="expand">'.get_int_text('label_station_website').'</div>';
                print '</div>';
                print '</a>';
            }
            if (array_key_exists('facebook', $station) && $station['facebook'] != '') {
                print '<a href="'.$station['facebook'].'" target="_blank">';
                print '<div class="containerbox indent padright menuitem">';
                print '<i class="icon-facebook-logo smallicon fixed"></i><div class="expand">Facebook</div>';
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
                print '<i class="icon-twitter-logo smallicon fixed"></i><div class="expand">Twitter</div>';
                print '</div>';
                print '</a>';
            }

            print '</div>';
        }
    }

    private function get_from_dirble($url, $page = 1) {
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

        $d = new url_downloader(array('url' => $url.'?page='.$page.'&per_page='.$per_page.'&token='.$token));
        if ($d->get_data_to_string()) {
            $result['json'] = array_merge($result['json'], json_decode($d->get_data(), true));
            if (($p = $d->get_header('X-Page')) !== false) {
                debuglog("  Got Page ".$p,"DIRBLE");
            }
            if (($p = $d->get_header('X-Total')) !== false) {
                debuglog("  Total Stations ".$p,"DIRBLE");
                $result['total'] = $p;
            }
            if (($p = $d->get_header('X-Next-Page')) !== false) {
                debuglog("  Next Page ".$p,"DIRBLE");
                $result['nextpage'] = $p;
            }
            $result['num'] = $result['first'] + count($result['json']) - 1;
            debuglog('Showing '.$result['first'].' to '.$result['num'].' of '.$result['total'],"DIRBLE");
        }
        return $result;
    }

    private function dirble_search($term, $page, $country) {
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
        $d = new url_downloader(array(
            'url' => 'http://api.dirble.com/v2/search?token='.$token,
            'postfields' => $sterms
        ));
        if ($d->get_data_to_string()) {
            $result['json'] = array_merge($result['json'], json_decode($d->get_data(), true));
            $result['num'] = $result['first'] + count($result['json']) - 1;
        }
        return $result;
    }

    private function do_page_buttons($json, $is_bottom) {
        if ($json['total'] > 0) {
            print '<div class="fullwidth"><div class="containerbox padright noselection menuitem">';
            $class = ($json['prevpage'] == 0) ? ' button-disabled' : ' clickable clickicon clickradioback';
            print '<i class="fixed icon-left-circled medicon'.$class.'"></i>';
            print '<div class="expand textcentre">Showing '.$json['first'].' to '.$json['num'].' of '.$json['total'].'</div>';
            $class = ($json['nextpage'] == 0) ? ' button-disabled' : ' clickable clickicon clickradioforward';
            print '<i class="fixed icon-right-circled medicon'.$class.'"></i>';
            print '</div>';

            $page = $json['prevpage']+1;
            $firstpage = max(1, $page-5);
            $lastpage = min($firstpage+9, round(($json['total']/$json['perpage']), 0, PHP_ROUND_HALF_DOWN)+1);
            print '<div class="textcentre brick_wide containerbox wrap menuitem">';
            for ($p = $firstpage; $p < $lastpage; $p++) {
                print '<div class="clickable clickicon clickdirblepager expand';
                if ($p == $page) {
                    print ' highlighted';
                }
                print '" name="'.$p.'">'.$p.'</div>';
            }
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

    private function check_streams($streams) {
        $ss = array();
        foreach ($streams as $s) {
            if (!preg_match('/\.ram$/', $s['stream']) && !preg_match('/\.qtl$/', $s['stream']) && audioClass($s['content_type']) != 'notastream') {
                $ss[] = $s;
            }
        }
        return $ss;
    }

    private function check_for_playlist($streams) {
        foreach ($streams as $s) {
            if (audioClass($s['content_type']) == 'icon-doc-text') {
                debuglog("   Using Playlist ".$s['stream'],"DIRBLE");
                return $s['stream'];
            }
        }
        return $streams[0]['stream'];
    }

    private function get_categories($station) {
        $cat = array();
        foreach ($station['categories'] as $c) {
            $cat[] = $c['title'];
        }
        return implode(', ',$cat);
    }

    private function get_speed($b) {
        if ($b > 0) {
            return $b." Kbps";
        } else {
            return "Unknown Kbps";
        }
    }

}

if (array_key_exists('populate', $_REQUEST)) {

    chdir('..');

    include("includes/vars.php");
    include("includes/functions.php");
    include("international.php");
    include("skins/".$skin."/ui_elements.php");

    $dirble = new dirbleplugin();
    $dirble->parseParams();
    if ($_REQUEST['populate'] != 3) {
        $dirble->doDropdownHeader();
    }
    $dirble->doStations();

} else {

    $dirble = new dirbleplugin();
    $dirble->doHeader();

}

function dirble_sort_by_station($a, $b) {
    return ($a['name'] < $b['name']) ? -1 : 1;
}


?>
