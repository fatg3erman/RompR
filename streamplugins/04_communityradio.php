<?php

class commradioplugin {

    public function __construct() {
        $this->pagination = 50;
        $this->searchterms = array('name', 'country', 'state', 'language', 'tag');
        $this->page = 0;
    }

    public function parseParams() {
        $this->page = $_REQUEST['page'];
        $this->country = array_key_exists('country', $_REQUEST) ? $_REQUEST['country'] : '';
        $this->language = array_key_exists('language', $_REQUEST) ? $_REQUEST['language'] : '';
        $this->tag = array_key_exists('tag', $_REQUEST) ? $_REQUEST['tag'] : '';
        $this->listby = $_REQUEST['listby'];
        $this->order = $_REQUEST['order'];
    }

    public function doHeader() {
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
        print '<div class="configtitle textcentre"><b>'.get_int_text('label_loading').'</b></div></div>';
        print '</div>';
    }

    public function doDropdownHeader() {
        directoryControlHeader('communityradiolist', get_int_text('label_communityradio'));

        print '<div class="configtitle textcentre brick_wide">List By:</div>';

        $d = new url_downloader(array('url' => 'http://www.radio-browser.info/webservice/json/countries'));
        if ($d->get_data_to_string()) {
            $countries = json_decode($d->get_data(), true);
            $this->makeSelector($countries, 'country', $this->country);
        }

        $d = new url_downloader(array('url' => 'http://www.radio-browser.info/webservice/json/languages'));
        if ($d->get_data_to_string()) {
            $langs = json_decode($d->get_data(), true);
            $this->makeSelector($langs, 'language', $this->language);
        }

        $d = new url_downloader(array('url' => 'http://www.radio-browser.info/webservice/json/tags'));
        if ($d->get_data_to_string()) {
            $tags = json_decode($d->get_data(), true);
            $this->makeSelector($tags, 'tag', $this->tag);
        }

        print '<div class="configtitle textcentre brick_wide">Search All Stations:</div>';
        print '<div class="padright fullwidth">';
        foreach ($this->searchterms as $term) {
            print '<div class="containerbox dropdown-container brick_wide fullwidth" name="'.$term.'">';
            print '<div class="fixed comm-search-label"><span class="cslt"><b>'.ucfirst($term).'</b></span></div>';
            print '<div class="expand">';
            print '<input class="comm_radio_searchterm" name="'.$term.'" type="text" />';
            print '</div>';
            print '</div>';
        }
        print '<div class="containerbox">';
        print '<div class="expand"></div>';
        print '<button class="fixed searchbutton iconbutton" name="commradiosearch"></button>';
        print '</div>';
        print '</div>';

        print '<div class="configtitle textcentre brick_wide">Order By:</div>';
        foreach (array('name', 'country', 'language', 'state', 'tags', 'votes', 'bitrate') as $o) {
            print '<div class="styledinputs">';
            print '<input id="commradioorderby'.$o.'" class="topcheck" name="commradioorderby" value="'.$o.'" type="radio"';
            if ($this->order == $o) {
                print ' checked';
            }
            print ' />';
            print '<label for="commradioorderby'.$o.'">'.ucfirst($o).'&nbsp;&nbsp;</label>';
            print '</div>';
        }

        print '<div id="communitystations" class="fullwidth padright holderthing">';

    }

    public function doRequest() {
        $url = '';
        switch ($this->listby) {
            case 'country':
                $url = 'http://www.radio-browser.info/webservice/json/stations/bycountryexact/'.rawurlencode($this->country).'?';
                print '<div class="configtitle textcentre brick_wide"><b>Country - '.ucwords($this->country).'</b></div>';
                break;

            case 'language':
                $url = 'http://www.radio-browser.info/webservice/json/stations/bylanguageexact/'.rawurlencode($this->language).'?';
                print '<div class="configtitle textcentre brick_wide"><b>Language - '.ucwords($this->language).'</b></div>';
                break;

            case 'tag':
                $url = 'http://www.radio-browser.info/webservice/json/stations/bytagexact/'.rawurlencode($this->tag).'?';
                print '<div class="configtitle textcentre brick_wide"><b>Tag - '.ucwords($this->tag).'</b></div>';
                break;

            case 'search':
                $url = 'http://www.radio-browser.info/webservice/json/stations/search?';
                $ourterms = array();
                foreach ($this->searchterms as $t) {
                    if (array_key_exists($t, $_REQUEST) && $_REQUEST[$t] != '') {
                        $ourterms[] = $t.'='.rawurlencode($_REQUEST[$t]);
                    }
                }
                print '<div class="configtitle textcentre brick_wide"><b>'.get_int_text('button_search').' - '.rawurldecode(implode(', ', $ourterms)).'</b></div>';
                $url .= implode('&', $ourterms).'&';
                break;
        }

        $url .= 'order='.$this->order;

        switch ($this->order) {
            case 'bitrate':
            case 'votes':
                $url .= '&reverse=true';
                break;
        }

        $d = new url_downloader(array('url' => $url));
        if ($d->get_data_to_string()) {
            $stations = json_decode($d->get_data(), true);
            $this->comm_radio_do_page_buttons($this->page, count($stations), $this->pagination);
            for ($i = 0; $i < $this->pagination; $i++) {
                $index = $this->page * $this->pagination + $i;
                if ($index >= count($stations)) {
                    break;
                }
                $this->doStation($this->comm_radio_sanitise_station($stations[$index]), $index);
            }
            $this->comm_radio_do_page_buttons($this->page, count($stations), $this->pagination);
        }

    }

    public function closeDropdown() {
        print '</div>';
    }

    // -- Private Functions -- //

    private function doStation($station, $index) {
        print albumHeader(array(
            'id' => 'communityradio_'.$index,
            'Image' => $this->comm_radio_get_image($station),
            'Searched' => 1,
            'AlbumUri' => null,
            'Year' => null,
            'Artistname' => preg_replace('/,/', ', ', htmlspecialchars($station['tags'])),
            'Albumname' => htmlspecialchars($station['name']),
            'why' => 'whynot',
            'ImgKey' => 'none',
            'streamuri' => $station['playurl'],
            'streamname' => $station['name'],
            'streamimg' => $this->comm_radio_get_stream_image($station),
            'class' => 'radiochannel'
        ));
        print '<div id="communityradio_'.$index.'" class="dropmenu">';
        trackControlHeader('','','communityradio_'.$index, array(array('Image' => $this->comm_radio_get_image($station))));
        print '<div class="containerbox expand ninesix indent padright"><b>Listen:</b></div>';
        print '<div class="clickstream playable draggable indent containerbox padright menuitem" name="'.$station['playurl'].'" streamimg="'.$this->comm_radio_get_stream_image($station).'" streamname="'.$station['name'].'">';
        print '<i class="'.audioClass($station['codec']).' smallicon fixed"></i>';
        print '<div class="expand">'.$station['bitrate'].'kbps &nbsp'.$station['codec'].'</div>';
        print '</div>';
        print '<div class="containerbox ninesix indent padright">'.utf8_encode($station['state']).utf8_encode($station['country']).'</div>';
        print '<div class="containerbox ninesix indent padright">'.utf8_encode($station['votes']).' Upvotes, '.utf8_encode($station['negativevotes']).' Downvotes</div>';
        if ($station['homepage']) {
            print '<a href="'.$station['homepage'].'" target="_blank">';
            print '<div class="containerbox indent padright menuitem">';
            print '<i class="icon-www smallicon fixed"></i>';
            print '<div class="expand">'.get_int_text('label_station_website').'</div>';
            print '</div>';
            print '</a>';
        }
        print '</div>';

    }

    private function makeSelector($json, $which, $setting) {
        print '<div class="fullwidth padright brick_wide containerbox dropdown-container" style="margin-bottom:0px">';
        $this->comm_radio_make_list_button($which);
        print '<div class="selectholder expand">';
        print '<select id="communityradio'.$which.'">';
        foreach ($json as $thing) {
            $val = strtolower($thing['value']);
            print '<option value="'.$val.'"';
            if ($val == $setting) {
                print ' selected';
            }
            print '>'.$thing['value'].' ('.$thing['stationcount'].' stations)</option>';
        }
        print '</select>';
        print '</div>';
        print '</div>';
    }

    private function comm_radio_make_list_button($which) {
        print '<div class="fixed styledinputs commradiolistby">';
        print '<span class="cclb">';
        print '<input id="commradiolistby'.$which.'" class="topcheck" name="commradiolistby" value="'.$which.'" type="radio"';
        if ($this->listby == $which) {
            print ' checked';
        }
        print ' />';
        print '<label for="commradiolistby'.$which.'"><b>'.ucfirst($which).'&nbsp;&nbsp;</b></label>';
        print '</span>';
        print '</div>';
    }

    private function comm_radio_get_image($station) {
        if ($station['favicon']) {
            if (substr($station['favicon'], 0, 10) == 'data:image') {
                return $station['favicon'];
            } else {
                return 'getRemoteImage.php?url='.$station['favicon'].'&rompr_backup_type=stream';
            }
        } else {
            return 'newimages/broadcast.svg';
        }
    }

    private function comm_radio_get_stream_image($station) {
        if ($station['favicon']) {
            if (substr($station['favicon'], 0, 10) == 'data:image') {
                // Sadly we can't handle base64 data as a stream image in this way. The URLs are too long
                return '';
            } else {
                return 'getRemoteImage.php?url='.$station['favicon'];
            }
        } else {
            return '';
        }
    }

    private function comm_radio_do_page_buttons($page, $count, $per_page) {
        print '<div class="fullwidth brick_wide"><div class="containerbox padright noselection menuitem">';
        $class = ($page == 0) ? ' button-disabled' : ' clickable clickicon commradio clickcommradioback';
        print '<i class="fixed icon-left-circled medicon'.$class.'"></i>';
        print '<div class="expand textcentre">Showing '.($page*$per_page+1).' to '.min(array(($page*$per_page+$per_page), $count)).' of '.$count.'</div>';
        $class = ((($page+1) * $per_page) >= $count || $count < $per_page) ? ' button-disabled' : ' clickable commradio clickicon clickcommradioforward';
        print '<i class="fixed icon-right-circled medicon'.$class.'"></i>';
        print '</div>';

        $firstpage = max(0, $page-4);
        $lastpage = min($firstpage+9, round(($count/$per_page), 0, PHP_ROUND_HALF_DOWN));
        print '<div class="textcentre brick_wide containerbox wrap menuitem">';
        for ($p = $firstpage; $p < $lastpage; $p++) {
            print '<div class="clickable commradio clickicon clickcommradiopager expand';
            if ($p == $page) {
                print ' highlighted';
            }
            print '" name="'.$p.'">'.($p+1).'</div>';
        }
        print '</div>';

        print '</div>';
    }

    private function comm_radio_sanitise_station($station) {
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

}

if (array_key_exists('populate', $_REQUEST)) {

    chdir('..');

    include ("includes/vars.php");
    include ("includes/functions.php");
    include ("international.php");
    include ("skins/".$skin."/ui_elements.php");

    foreach ($_REQUEST as $i => $r) {
        logger::log("COMMRADIO", $i,":",$r);
    }

    $commradio = new commradioplugin();
    $commradio->parseParams();

    if ($_REQUEST['populate'] == 1) {
        $commradio->doDropdownHeader();
    }

    $commradio->doRequest();

    if ($_REQUEST['populate'] == 1) {
        $commradio->closeDropdown();
    }

} else {

    $commradio = new commradioplugin();
    $commradio->doHeader();

}

?>
