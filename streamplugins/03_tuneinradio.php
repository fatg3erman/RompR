<?php

class tuneinplugin {

    public function __construct() {
        $this->url = 'http://opml.radiotime.com/';
        $this->title = '';
    }

    public function doHeader() {
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
        print '<div id="tuneinlist" class="dropmenu notfilled"><div class="configtitle textcentre"><b>'.get_int_text('label_loading').'</b></div></div>';
        print '</div>';
    }

    public function parseParams() {
        if (array_key_exists('url', $_REQUEST)) {
            $this->url = $_REQUEST['url'];
        } else {
            directoryControlHeader('tuneinlist', get_int_text('label_tuneinradio'));
            print '<div class="fullwidth padright" style="margin-bottom:0px"><div class="containerbox padright noselection fullwidth"><div class="expand">
                <input class="enter clearbox tuneinsearchbox" name="tuneinsearcher" type="text" ';
            if (array_key_exists('search', $_REQUEST)) {
                print 'value="'.$_REQUEST['search'].'" ';
            }
            print '/></div><button class="fixed tuneinsearchbutton searchbutton iconbutton" name="sonicthehedgehog"></button></div></div>';
        }
        if (array_key_exists('title', $_REQUEST)) {
            $this->title = $_REQUEST['title'];
            directoryControlHeader($_REQUEST['target'], htmlspecialchars($this->title));
        }
        if (array_key_exists('search', $_REQUEST)) {
            directoryControlHeader('tuneinlist', get_int_text('label_tuneinradio'));
            $this->url .= 'Search.ashx?query='.urlencode($_REQUEST['search']);
        }
    }

    public function getUrl() {
        debuglog("Getting URL ".$this->url,"TUNEIN");
        $d = new url_downloader(array('url' => $this->url));
        if ($d->get_data_to_string()) {
            $x = simplexml_load_string($d->get_data());
            $v = (string) $x['version'];
            debuglog("OPML version is ".$v, "TUNEIN", 8);
            $this->parse_tree($x->body, $this->title);
        }
    }

    private function parse_tree($node, $title) {

        foreach ($node->outline as $o) {
            $att = $o->attributes();
            debuglog("  Text is ".$att['text'].", type is ".$att['type'], "TUNEIN",8);
            switch ($att['type']) {

                case '':
                    print '<div class="configtitle textcentre brick_wide">';
                    print '<div class="expand">'.$att['text'].'</div>';
                    print '</div>';
                    $this->parse_tree($o, $title);
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

}

if (array_key_exists('populate', $_REQUEST)) {

    chdir('..');

    include ("includes/vars.php");
    include ("includes/functions.php");
    include ("international.php");
    include ("skins/".$skin."/ui_elements.php");

    $tunein = new tuneinplugin();
    $tunein->parseParams();
    $tunein->getUrl();

} else {

    $tunein = new tuneinplugin();
    $tunein->doHeader();
}

?>
