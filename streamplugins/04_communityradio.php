<?php

if (array_key_exists('populate', $_REQUEST)) {
    chdir('..');

    require_once ("includes/vars.php");
    require_once ("includes/functions.php");
    require_once ("international.php");
    require_once ("skins/".$skin."/ui_elements.php");

    foreach ($_REQUEST as $i => $r) {
        logger::trace("COMMRADIO", $i,":",$r);
    }
}

class commradioplugin {

    public function __construct() {
        $this->pagination = 50;
        $this->searchterms = array('name', 'country', 'state', 'language', 'tag');
        $this->url = array_key_exists('url', $_REQUEST) ? $_REQUEST['url'] : null;
        $this->page = array_key_exists('page', $_REQUEST) ? $_REQUEST['page'] : 0;
        $this->title = array_key_exists('title', $_REQUEST) ? $_REQUEST['title'] : null;
        $this->order = array_key_exists('order', $_REQUEST) ? $_REQUEST['order'] : 'name';
        $this->populate = array_key_exists('populate', $_REQUEST) ? $_REQUEST['populate'] : 0;
    }

    public function doWhatYoureTold() {
        switch ($this->populate) {
            case 0:
                $this->doHeader();
                break;

            case 1:
                $this->doDropdownHeader();
                break;

            case 2:
                if (substr($this->url, 0, 5) == 'json/') {
                    $this->browse();
                } else if ($this->url == 'getgenres') {
                    $this->doGenreList();
                } else {
                    $this->doRequest();
                }
                break;

            case 3:
                $this->doSearch();
                break;

            case 4:
                $this->doBrowseRoot();
                break;

        }
    }

    private function doHeader() {
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

    private function doDropdownHeader() {
        global $prefs;
        directoryControlHeader('communityradiolist', get_int_text('label_communityradio'));

        print '<div class="fullwidth padright brick_wide containerbox dropdown-container">';
        print '<div class="fixed comm-search-label"><span class="cslt"><b>Order By</b></span></div>';
        print '<div class="selectholder expand">';
        print '<select id="communityradioorderbyselector" class="saveomatic">';
        foreach (array('name', 'country', 'language', 'state', 'tags', 'votes', 'bitrate') as $o) {
            print '<option value="'.$o.'"';
            print '>'.ucfirst($o).'</option>';
        }
        print '</select>';
        print '</div>';
        print '</div>';

        print '<div class="padright fullwidth cleargroupparent">';
        foreach ($this->searchterms as $term) {
            print '<div class="containerbox dropdown-container brick_wide fullwidth" name="'.$term.'">';
            print '<div class="fixed comm-search-label"><span class="cslt"><b>'.ucfirst($term).'</b></span></div>';
            print '<div class="expand">';
            print '<input class="comm_radio_searchterm clearbox enter cleargroup" name="'.$term.'" type="text" />';
            print '</div>';
            print '</div>';
        }
        print '<div class="containerbox">';
        print '<div class="expand"></div>';
        print '<button class="fixed searchbutton iconbutton cleargroup" name="commradiosearch"></button>';
        print '</div>';
        print '</div>';

        print '<div id="communitystations" class="fullwidth padright holderthing">';

        $this->doBrowseRoot();

        print '</div>';

    }

    private function doBrowseRoot() {
        printRadioDirectory(array('URL' => 'json/countries', 'text' => 'Country'), false, 'commradio');
        print '</div>';

        printRadioDirectory(array('URL' => 'json/languages', 'text' => 'Language'), false, 'commradio');
        print '</div>';

        printRadioDirectory(array('URL' => 'getgenres', 'text' => 'Genres'), false, 'commradio');
        print '</div>';
    }

    private function doGenreList() {
        $genres = array(
            'breakbeat',
            'chart',
            'dance',
            'electronic',
            'jungle',
            'oldschool',
            'techno',
            'trip-hop',
            '50s',
            '60s',
            '70s',
            '80s',
            '90s',
            '00s',
            'contemporary',
            'hits',
            'rock',
            'pop',
            'afrobeat',
            'folk',
            'reggae',
            'dub',
            'acoustic',
            'alt',
            'ambient',
            'bluegrass',
            'blues',
            'brazil',
            'british',
            'chill',
            'classical',
            'comedy',
            'talk',
            'country',
            'dancehall',
            'deep-house',
            'disco',
            'dnb',
            'dubstep',
            'emo',
            'funk',
            'garage',
            'gospel',
            'goth',
            'grindcore',
            'groove',
            'grunge',
            'hardcore',
            'house',
            'idm',
            'indian',
            'indie',
            'industrial',
            'jazz',
            'metal',
            'rap',
            'progressive',
            'psych',
            'punk',
            'soul',
            'trance',
            'world'
        );
        sort($genres);
        foreach ($genres as $g) {
            printRadioDirectory(array('URL' => 'json/tags/'.$g, 'text' => $g), false, 'commradio');
            print '</div>';

        }
    }

    private function browse() {
        directoryControlHeader('commradio_'.md5($this->url), $this->title);
        $bits = getCacheData('http://www.radio-browser.info/webservice/'.$this->url, 'commradio', true, true);
        $bits = json_decode($bits, true);
        if ($this->url == 'json/countries') {
            $map = 'bycountryexact/';
        } else if ($this->url == 'json/languages') {
            $map = 'bylanguageexact/';
        } else {
            $map = 'bytagexact/';
        }
        $this->makeSelector($bits, $map);
    }

    private function doSearch() {
        $url = 'http://www.radio-browser.info/webservice/json/stations/search?';
        $ourterms = array();
        foreach ($this->searchterms as $t) {
            if (array_key_exists($t, $_REQUEST) && $_REQUEST[$t] != '') {
                $ourterms[] = $t.'='.rawurlencode($_REQUEST[$t]);
            }
        }
        $url .= implode('&', $ourterms).'&';
        $url = $this->addBits($url);
        $stations = getCacheData($url, 'commradio', true, true);
        $stations = json_decode($stations, true);
        foreach ($stations as $index => $station) {
            $this->doStation($this->comm_radio_sanitise_station($station), md5($index.$url.$station['id']));
        }            
    }

    private function addBits($url) {
        $url .= 'order='.$this->order;
        switch ($this->order) {
            case 'bitrate':
            case 'votes':
                $url .= '&reverse=true';
                break;
        }
        return $url;        
    }

    private function doRequest() {
        $url = $this->addBits('http://www.radio-browser.info/webservice/json/stations/'.$this->url.'?');
        $stations = getCacheData($url, 'commradio', true, true);
        $stations = json_decode($stations, true);
        $title = ($this->title) ? rawurldecode($this->title) : get_int_text('label_communityradio');
        directoryControlHeader('commradio_'.md5($this->url), ucfirst($title));
        print '<input type="hidden" value="'.rawurlencode($this->url).'" />';
        print '<input type="hidden" value="'.rawurlencode($title).'" />';
        $this->comm_radio_do_page_buttons($this->page, count($stations), $this->pagination);
        for ($i = 0; $i < $this->pagination; $i++) {
            $index = $this->page * $this->pagination + $i;
            if ($index >= count($stations)) {
                break;
            }
            $this->doStation($this->comm_radio_sanitise_station($stations[$index]), md5($index.$url.$stations[$index]['id']));
        }
        $this->comm_radio_do_page_buttons($this->page, count($stations), $this->pagination);
    }

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
        // print '<div class="containerbox expand ninesix indent padright"><b>Listen:</b></div>';
        print '<div class="containerbox ninesix indent padright">'.htmlspecialchars($station['state'].$station['country']).'</div>';
        print '<div class="clickstream playable draggable containerbox padright menuitem" name="'.rawurlencode($station['playurl']).'" streamimg="'.$this->comm_radio_get_stream_image($station).'" streamname="'.$station['name'].'">';
        print '<i class="'.audioClass($station['codec']).' smallicon fixed"></i>';
        print '<div class="expand">'.$station['bitrate'].'kbps &nbsp'.$station['codec'].'</div>';
        print '</div>';
        print '<div class="containerbox ninesix indent padright">'.$station['votes'].' Upvotes, '.$station['negativevotes'].' Downvotes</div>';
        if ($station['homepage']) {
            print '<a href="'.$station['homepage'].'" target="_blank">';
            print '<div class="containerbox padright menuitem">';
            print '<i class="icon-www smallicon fixed"></i>';
            print '<div class="expand">'.get_int_text('label_station_website').'</div>';
            print '</div>';
            print '</a>';
        }
        print '</div>';

    }

    private function makeSelector($json, $root) {
        foreach ($json as $thing) {
            $val = strtolower($thing['value']);
            $opts = array(
                'URL' => $root.rawurlencode($val),
                'text' => ucfirst($thing['value']).' ('.$thing['stationcount'].' stations)'
            );
            printRadioDirectory($opts, true, 'commradio');
        }
    }

    private function comm_radio_get_image($station) {
        if ($station['favicon']) {
            if (substr($station['favicon'], 0, 10) == 'data:image') {
                return $station['favicon'];
            } else {
                logger::debug('COMMRADIO', 'Image Is',$station['favicon']);
                if (preg_match('#http://www.bbc.co.uk///(rmp.files.bbci.co.uk/.*)#', $station['favicon'], $matches)) {
                    // This appears to be a database fuckup on their part
                    $station['favicon'] = 'http://'.$matches[1];
                }
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
        if ($count < $this->pagination) {
            return;
        }
        print '<div class="fullwidth brick_wide"><div class="containerbox padright noselection menuitem">';
        $class = ($page == 0) ? ' button-disabled' : ' clickable clickicon commradio clickcommradiopager';
        print '<i name="'.($page-1).'" class="fixed icon-left-circled medicon'.$class.'"></i>';
        print '<div class="expand textcentre">Showing '.($page*$per_page+1).' to '.min(array(($page*$per_page+$per_page), $count)).' of '.$count.'</div>';
        $class = ((($page+1) * $per_page) >= $count || $count < $per_page) ? ' button-disabled' : ' clickable commradio clickicon clickcommradiopager';
        print '<i name="'.($page+1).'" class="fixed icon-right-circled medicon'.$class.'"></i>';
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


$commradio = new commradioplugin();
$commradio->doWhatYoureTold();

?>
