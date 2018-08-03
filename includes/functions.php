<?php
function format_for_mpd($term) {
    $term = str_replace('"','\\"',$term);
    return trim($term);
}

function join_command_string($cmd) {
    $c = $cmd[0];
    for ($i = 1; $i < count($cmd); $i++) {
        $c .= ' "'.format_for_mpd($cmd[$i]).'"';
    }
    return $c;
}

function do_mpd_command_list($cmds) {
    global $connection;
    $done = 0;
    $cmd_status = null;
    if (count($cmds) > 1) {
        send_command("command_list_begin");
        foreach ($cmds as $c) {
            debuglog("Command List: ".$c,"POSTCOMMAND",6);
            // Note. We don't use send_command because that closes and re-opens the connection
            // if it fails to fputs, and that loses our command list status. Also if this fputs
            // fails it means the connection has dropped anyway, so we're screwed whatever happens.
            fputs($connection, $c."\n");
            $done++;
            // Command lists have a maximum length, 50 seems to be the default
            if ($done == 50) {
                do_mpd_command("command_list_end", true);
                send_command("command_list_begin");
                $done = 0;
            }
        }
        $cmd_status = do_mpd_command("command_list_end", true, false);
    } else if (count($cmds) == 1) {
        debuglog("Command : ".$cmds[0],"POSTCOMMAND",6);
        $cmd_status = do_mpd_command($cmds[0], true, false);
    }
    return $cmd_status;
}

function wait_for_player_state($expected_state) {
    if ($expected_state !== null) {
        $status = do_mpd_command ("status", true, false);
        $retries = 20;
        while ($retries > 0 && array_key_exists('state', $status) && $status['state'] != $expected_state) {
            usleep(500000);
            $retries--;
            $status = do_mpd_command ("status", true, false);
        }
    }
}

function format_for_disc($filename) {
    $filename = str_replace("\\","_",$filename);
    $filename = str_replace("/","_",$filename);
    $filename = str_replace("'","_",$filename);
    $filename = str_replace('"',"_",$filename);
    return $filename;
}

function format_for_url($filename) {
    return preg_replace('/#|&|\?|%|@|\+/', '_', $filename);
}

function format_tracknum($tracknum) {
    $matches = array();
    if (preg_match('/^\s*0*(\d+)/', $tracknum, $matches)) {
        if (strlen($matches[1]) < 4) {
            return $matches[1];
        }
    }
    if (preg_match('/0*(\d+) of \d+/i', $tracknum, $matches)) {
        return $matches[1];
    }
    return 0;
}

function format_text($d) {
    $d = preg_replace('/(<a href=.*?)>/', '$1 target="_blank">', $d);
    $d = preg_replace('/(<a rel="nofollow" href=.*?)>/', '$1 target="_blank">', $d);
    $d = preg_replace('/style\s*=\s*\".*?\"/', '', $d);
    $d = preg_replace('/<p>\s*<\/p>/', '', $d);
    $d = preg_replace('/<p>&nbsp;<\/p>/', '', $d);
    $d = preg_replace('/\n|(\r\n)/', '<br/>', $d);
    $d = preg_replace('/(<br\s*\/*>)+/', '<br/>', $d);
    $d = preg_replace('/<\/p><br>/', '</p>', $d);
    return $d;
}

class url_downloader {

    private $default_options = array(
        'useragent' => ROMPR_IDSTRING,
        'timeout' => 120,
        'connection_timeout' => 60,
        'url' => '',
        'header' => null,
        'postfields' => null,
        'cache' => null,
        'return_data' => false,
        'send_cache_headers' => false
    );

    private $ch;
    private $headerarray = array();
    private $headerlen = 0;
    private $content;
    private $content_type;
    private $info;
    private $status;

    public function __construct($options) {
        global $prefs;
        $this->options = array_merge($this->default_options, $options);
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_URL, $this->options['url']);
        curl_setopt($this->ch, CURLOPT_ENCODING, '');
        if ($this->options['useragent']) {
            curl_setopt($this->ch, CURLOPT_USERAGENT, $this->options['useragent']);
        }
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->options['timeout']);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, $this->options['connection_timeout']);
        if ($prefs['proxy_host'] != "") {
            curl_setopt($this->ch, CURLOPT_PROXY, $prefs['proxy_host']);
        }
        if ($prefs['proxy_user'] != "" && $prefs['proxy_password'] != "") {
            curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, $prefs['proxy_user'].':'.$prefs['proxy_password']);
        }
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        if ($this->options['header']) {
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->options['header']);
        }
        if ($this->options['postfields'] !== null) {
            $fields_string = '';
            foreach ($this->options['postfields'] as $key => $value) {
                $fields_string .= $key.'='.$value.'&';
            }
            rtrim($fields_string,'&');
            curl_setopt($this->ch, CURLOPT_POST, count($this->options['postfields']));
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $fields_string);
        }
    }

    public function get_data_to_string() {
        debuglog("Downloading ".$this->options['url'],"URL_DOWNLOADER");
        if ($this->options['send_cache_headers']) {
            header("Pragma: Not Cached");
        }
        curl_setopt($this->ch, CURLOPT_HEADER, true);
        curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, function($curl, $header)
            {
                $len = strlen($header);
                $this->headerlen += $len;
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                    return $len;

                $name = ($header[0]);
                $this->headerarray[$name] = trim($header[1]);
                return $len;
            }
        );
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        $this->content = curl_exec($this->ch);
        return $this->get_final_info();
    }

    public function get_data_to_file($file = null, $binary = false) {
        if ($file === null && $this->options['cache'] === null) {
            debuglog("  No file or cache dir for request, returning data as string","URL_DOWNLOADER");
            return $this->get_data_to_string();
        } else if ($this->options['cache'] !== null) {
            $file = 'prefs/jsoncache/'.$this->options['cache'].'/'.md5($this->options['url']);
        }
        if ($this->options['cache'] !== null && $this->check_cache($file)) {
            debuglog("Returning cached data ".$file,"URL_DOWNLOADER");
            $this->content = file_get_contents($file);
            return true;
        } else {
            debuglog("Downloading ".$this->options['url']." to ".$file,"URL_DOWNLOADER");
            if (file_exists($file)) {
                unlink ($file);
            }
            $open_mode = $binary ? 'wb' : 'w';
            $fp = fopen($file, $open_mode);
            curl_setopt($this->ch, CURLOPT_FILE, $fp);
            curl_exec($this->ch);
            fclose($fp);
            if ($this->options['return_data']) {
                $this->content = file_get_contents($file);
            }
            if (curl_getinfo($this->ch,CURLINFO_RESPONSE_CODE) != '200') {
                unlink($file);
            }
            return $this->get_final_info();
        }
    }

    private function check_cache($file) {
        if (file_exists($file)) {
            if ($this->options['send_cache_headers']) {
                header("Pragma: From Cache");
            }
            return true;
        } else {
            if ($this->options['send_cache_headers']) {
                header("Pragma: Not Cached");
            }
            return false;
        }
    }

    private function get_final_info() {
        $this->status = curl_getinfo($this->ch, CURLINFO_RESPONSE_CODE);
        $this->content_type = curl_getinfo($this->ch, CURLINFO_CONTENT_TYPE);
        $this->info = curl_getinfo($this->ch);
        curl_close($this->ch);
        if ($this->get_status() == '200') {
            debuglog("  ..  Download Success","URL_DOWNLOADER");
            return true;
        } else {
            debuglog("  ..  Download Failed With Status Code ".$this->get_status(),"URL_DOWNLOADER");
            return false;
        }
    }

    public function get_data() {
        return substr($this->content, $this->headerlen);
    }

    public function get_headers() {
        return $this->headerarray;
    }

    public function get_header($h) {
        if (array_key_exists($h, $this->headerarray)) {
            return $this->headerarray[$h];
        } else {
            return false;
        }
    }

    public function get_status() {
        return $this->status;
    }

    public function get_info() {
        return $this->info;
    }

    public function get_content_type() {
        return $this->content_type;
    }

}

function format_time($t,$f=':') {
    if (($t/86400) >= 1) {
        return sprintf("%d%s%2d%s%02d%s%02d", ($t/86400), " ".get_int_text("label_days")." ",
            ($t/3600)%24, $f, ($t/60)%60, $f, $t%60);
    }
    if (($t/3600) >= 1) {
        return sprintf("%2d%s%02d%s%02d", ($t/3600), $f, ($t/60)%60, $f, $t%60);
    } else {
        return sprintf("%02d%s%02d", ($t/60)%60, $f, $t%60);
    }
}

function munge_album_name($name) {
    $b = preg_replace('/(\(|\[)disc\s*\d+.*?(\)|\])/i', "", $name);     // (disc 1) or (disc 1 of 2) or (disc 1-2) etc (or with [ ])
    $b = preg_replace('/(\(|\[)*cd\s*\d+.*?(\)|\])*/i', "", $b);        // (cd 1) or (cd 1 of 2) etc (or with [ ])
    $b = preg_replace('/\sdisc\s*\d+.*?$/i', "", $b);                   //  disc 1 or disc 1 of 2 etc
    $b = preg_replace('/\scd\s*\d+.*?$/i', "", $b);                     //  cd 1 or cd 1 of 2 etc
    $b = preg_replace('/(\(|\[)\d+\s*of\s*\d+(\)|\])/i', "", $b);       // (1 of 2) or (1of2) (or with [ ])
    $b = preg_replace('/(\(|\[)\d+\s*-\s*\d+(\)|\])/i', "", $b);        // (1 - 2) or (1-2) (or with [ ])
    $b = preg_replace('/(\(|\[)Remastered(\)|\])/i', "", $b);           // (Remastered) (or with [ ])
    $b = preg_replace('/(\(|\[).*?bonus .*(\)|\])/i', "", $b);          // (With Bonus Tracks) (or with [ ])
    $b = preg_replace('/\s+-\s*$/', "", $b);                            // Chops any stray - off the end that could have been left by the previous
    $b = preg_replace('#\s+$#', '', $b);
    $b = preg_replace('#^\s+#', '', $b);
    return $b;
}

function sanitsizeDiscogsResult($name) {
    $b = preg_replace('/\* /',' ', $name);
    return $b;
}

function alistheader($nart, $nalb, $ntra, $tim) {
    return '<div style="margin-bottom:4px">'.
    '<table width="100%" class="playlistitem">'.
    '<tr><td align="left">'.$nart.' '.get_int_text("label_artists").
    '</td><td align="right">'.$nalb.' '.get_int_text("label_albums").'</td></tr>'.
    '<tr><td align="left">'.$ntra.' '.get_int_text("label_tracks").
    '</td><td align="right">'.$tim.'</td></tr>'.
    '</table>'.
    '</div>';
}

function get_base_url() {
    /* First we need to get the protocol the website is using */
    $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"], 0, 5)) == 'https://' ? 'https://' : 'http://';

    /* returns /myproject/index.php */
    $path = $_SERVER['PHP_SELF'];

    /*
     * returns an array with:
     * Array (
     *  [dirname] => /myproject/
     *  [basename] => index.php
     *  [extension] => php
     *  [filename] => index
     * )
     */
    $path_parts = pathinfo($path);
    $directory = $path_parts['dirname'];
    /*
     * If we are visiting a page off the base URL, the dirname would just be a "/",
     * If it is, we would want to remove this
     */
    $directory = ($directory == "/") ? "" : $directory;

    /* Returns localhost OR mysite.com */
    $host = $_SERVER['HTTP_HOST'];

    // This handles the case where we're getting http://mypc.local/rompr/utils/something.php
    $directory = preg_replace('#/utils$#', '', $directory);
    $directory = preg_replace('#/streamplugins$#', '', $directory);
    $directory = preg_replace('#/includes$#', '', $directory);
    /*
     * Returns:
     * http://localhost/mysite
     * OR
     * https://mysite.com
     */
    return $protocol . $host . $directory;
}

function scan_for_images($albumpath) {
    debuglog("Album Path Is ".$albumpath, "LOCAL IMAGE SCAN");
    $result = array();
    if (is_dir("prefs/MusicFolders") && $albumpath != ".") {
        $albumpath = munge_filepath($albumpath);
        $result = array_merge($result, get_images($albumpath));
        // Is the album dir part of a multi-disc set?
        if (preg_match('/^CD\s*\d+$|^disc\s*\d+$/i', basename($albumpath))) {
            $albumpath = dirname($albumpath);
            $result = array_merge($result, get_images($albumpath));
        }
        // Are there any subdirectories?
        $globpath = preg_replace('/(\*|\?|\[)/', '[$1]', $albumpath);
        $lookfor = glob($globpath."/*", GLOB_ONLYDIR);
        foreach ($lookfor as $i => $f) {
            if (is_dir($f)) {
                $result = array_merge($result, get_images($f));
            }
        }
    }
    return $result;
}

function get_images($dir_path) {

    $funkychicken = array();
    $a = basename($dir_path);
    debuglog("    Scanning : ".$dir_path,"GET_IMAGES");
    $globpath = preg_replace('/(\*|\?|\[)/', '[$1]', $dir_path);
    debuglog("      Glob Path is ".$globpath,"GET_IMAGES");
    // $files = glob($globpath."/*.{jpg,png,bmp,gif,jpeg,JPEG,JPG,BMP,GIF,PNG}", GLOB_BRACE);
    // foreach($files as $i => $f) {
    //     $f = preg_replace('/%/', '%25', $f);
    //     debuglog("        Found : ".get_base_url()."/".preg_replace('/ /', "%20", $f),"GET_IMAGES");
    //     array_push($funkychicken, get_base_url()."/".preg_replace('/ /', "%20", $f));
    // }
    $funkychicken = glob($globpath."/*.{jpg,png,bmp,gif,jpeg,JPEG,JPG,BMP,GIF,PNG}", GLOB_BRACE);
    debuglog("    Checking for embedded images","GET_IMAGES");
    $files = glob($globpath."/*.{mp3,MP3,mp4,MP4,flac,FLAC,ogg,OGG}", GLOB_BRACE);
    $testfile = array_shift($files);
    if ($testfile) {
        $getID3 = new getID3;
        $tags = $getID3->analyze($testfile);
    	getid3_lib::CopyTagsToComments($tags);
        if (array_key_exists('comments', $tags) && array_key_exists('picture', $tags['comments'])) {
            foreach ($tags['comments']['picture'] as $picture) {
                if (array_key_exists('picturetype', $picture)) {
                    if ($picture['picturetype'] == 'Cover (front)') {
                        debuglog("    .. found embedded front cover image","GET_IMAGES");
                        $filename = 'prefs/temp/'.md5($globpath);
                        file_put_contents($filename, $picture['data']);
                        // array_unshift($funkychicken, get_base_url()."/".preg_replace('/ /', "%20", $filename));
                        array_unshift($funkychicken, $filename);
                    }
                }
            }
        }
    }
    return $funkychicken;
}

function munge_filepath($p) {
    global $prefs;
    $p = rawurldecode(html_entity_decode($p));
    $f = "file://".$prefs['music_directory_albumart'];
    if (substr($p, 0, strlen($f)) == $f) {
        $p = substr($p, strlen($f), strlen($p));
    }
    return "prefs/MusicFolders/".$p;
}

function find_executable($prog) {

    // Test to see if $prog is on the path and then try Homebrew and MacPorts paths until we find it
    // returns boolean false if the program is not found
    debuglog("    Looking for executable program ".$prog,"BITS",9);
    $paths_to_try = array('', '/usr/local/bin/', '/opt/local/bin/', '/usr/bin/', './');
    $retval = false;
    foreach ($paths_to_try as $c) {
        $r = exec($c.$prog." 2>&1", $o, $a);
        if ($a != 127) {
            $retval = $c;
            break;
        }
    }
    if ($retval === false) {
        debuglog("      Program ".$prog." Not Found!","BITS",2);
    } else {
        debuglog("      program is ".$retval.$prog,"BITS",9);
    }
    return $retval;

}

function get_browser_language() {
    // TODO - this method is not good enough.
    if (array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER)) {
        return substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    } else {
        return 'en';
    }
}

function getStreamFolder($url) {
    $f = dirname($url);
    if ($f == "." || $f == "") $f = $url;
    return $f;
}

function getDummyStation($url) {
    $f = getDomain($url);
    switch ($f) {
        case "http":
        case "https":
        case "mms":
        case "mmsh":
        case "mmst":
        case "mmsu":
        case "gopher":
        case "rtp":
        case "rtsp":
        case "rtmp":
        case "rtmpt":
        case "rtmps":
            return "Radio";
            break;

        default:
            return ucfirst($f);
            break;
    }
}

function sql_init_fail($message) {

    global $prefs;
    header("HTTP/1.1 500 Internal Server Error");
?>
<html><head>
<link rel="stylesheet" type="text/css" href="css/layout-january.css" />
<link rel="stylesheet" type="text/css" href="themes/Darkness.css" />
<title>Badgers!</title>
</head>
<body>
<h2 align="center" style="font-size:200%">Collection Database Error</h2>
<h4 align="center">It's all gone horribly wrong</h2>
<br>
<?php
print '<h3 align="center">RompЯ encountered an error while checking your '.
    ucfirst($prefs['collection_type']).' database.</h3>';
?>
<h3 align="center">An SQLite or MySQL database is required to run RompЯ</h3>
<h3 align="center">You may find it helpful to <a href="https://fatg3erman.github.io/RompR/" target="_blank">Read The Docs</a></h3>
<h3 align="center">The error message was:</h3><br>
<?php
    print '<div class="bordered" style="width:75%;margin:auto"><p align="center"><b>'.
        $message.'</b></p></div><br><br></body></html>';
        $title = "";
    include('setupscreen.php');
    exit(0);

}

function imagePath($image) {
    return ($image) ? $image : '';
}

function concatenate_artist_names($art) {
    if (!is_array($art)) {
        return $art;
    }
    if (count($art) == 0) {
        return '';
    } else if (count($art) == 1) {
        return $art[0];
    } else if (count($art) == 2) {
        return implode(' & ',$art);
    } else {
        $f = array_slice($art, 0, count($art) - 1);
        return implode($f, ", ")." & ".$art[count($art) - 1];
    }
}

function unwanted_array($a) {

    if (is_array($a)) {
        return $a[0];
    } else {
        return $a;
    }
}

function getArray($a) {
    if ($a === null) {
        return array();
    } else if (is_array($a)) {
        return $a;
    } else {
        return array($a);
    }
}

function getYear($date) {
    if (preg_match('/(\d\d\d\d)/', $date, $matches)) {
        return $matches[1];
    } else {
        return null;
    }
}

function trim_content_type($filetype) {
    $filetype = preg_replace('/;.*/','',$filetype);
    $filetype = trim(strtolower($filetype));
    return $filetype;
}

function audioClass($filetype, $domain = '') {
    $filetype = trim_content_type($filetype);
    switch ($filetype) {
        case "mp3":
        case "audio/mpeg":
            return 'icon-mp3-audio';
            break;

        case "mp4":
        case "m4a":
        case "aac":
        case "aacplus":
        case "aac+":
        case "aacp":
        case "audio/aac":
        case "audio/aacp":
            return 'icon-aac-audio';
            break;

        case "flac":
            return 'icon-flac-audio';
            break;

        case "wma":
        case "windows media":
            return 'icon-wma-audio';
            break;

        case "ogg":
        case "ogg vorbis":
            return 'icon-ogg-audio';
            break;

        case "cue":
        case "pls":
        case "m3u":
        case "audio/x-mpegurl":
        case "audio/x-scpls":
        case "video/x-ms-asf":
            return "icon-doc-text";
            break;

        case "?":
        case 'text/html':
        case '':
        case ' ';
            return domainCheck('notastream', $domain);
            break;

        default:
            return domainCheck('icon-music', $domain);
            break;

    }

}

function domainCheck($default, $domain) {
    if ($domain == '') {
        return $default;
    }
    switch ($domain) {
        case 'soundcloud':
        case 'spotify':
        case 'gmusic':
        case 'vkontakte':
        case 'internetarchive':
        case 'podcast':
        case 'dirble':
        case 'youtube':
            return 'icon-'.$domain.'-circled';
            break;

        case 'tunein':
            return 'icon-tunein';
            break;

        default:
            return $default;
            break;

    }

}

function getDomain($d) {
    if ($d === null || $d == "") {
        return "local";
    }
    $d = urldecode($d);
    $pos = strpos($d, ":");
    $a = substr($d,0,$pos);
    if ($a == "") {
        return "local";
    }
    $s = substr($d,$pos+3,15);
    if ($s == "api.soundcloud.") {
        return "soundcloud";
    }
    if ($a == 'http' || $a == 'https') {
        if (strpos($d, 'vk.me') !== false) {
    		return 'vkontakte';
    	} else if (strpos($d, 'oe1:archive') !== false) {
    		return 'oe1';
    	} else if (strpos($d, 'http://leftasrain.com') !== false) {
    		return 'leftasrain';
    	} else if (strpos($d, 'archives.bassdrivearchive.com') !== false ||
                    strpos($d, 'bassdrive.com') !== false) {
            return 'bassdrive';
        }
    }
    return strtok($a, ' ');
}

function domainIcon($d, $c) {
    $h = '';
    switch($d) {
        case "spotify":
        case "gmusic":
        case "youtube":
        case "internetarchive":
        case "soundcloud":
        case "podcast":
        case "dirble":
        case "tunein":
            $h = '<i class="'.domainCheck('icon-music', $d).' '.$c.' fixed"></i>';
            break;

        case "radio-de":
        case "bassdrive":
            $h = '<div class="'.$c.' fixed"><img class="imgfill" src="newimages/'.$d.'-logo.svg" /></div>';
            break;

    }
    return $h;
}

function domainHtml($uri) {
    $h = domainIcon(getDomain($uri), 'collectionicon');
    if ($h == '') {
        if (strtolower(pathinfo($uri, PATHINFO_EXTENSION)) == "cue") {
            $h = '<i class="icon-doc-text collectionicon fixed"></i>';
        }
    }
    return $h;
}

function artistNameHtml($obj) {
    global $prefs;
    $h = '';
    if ($prefs['sortcollectionby'] == 'albumbyartist' && $obj['Artistname']) {
        $h .= '<div class="expand">'.$obj['Albumname'];
        $h .= '<br><span class="notbold">'.$obj['Artistname'].'</span>';
        if ($obj['Year'] && $prefs['sortbydate']) {
            $h .= ' <span class="notbold">('.$obj['Year'].')</span>';
        }
    } else {
        $h .= '<div class="expand">'.$obj['Albumname'];
        if ($obj['Year'] && $prefs['sortbydate']) {
            $h .= ' <span class="notbold">('.$obj['Year'].')</span>';
        }
        if ($obj['Artistname']) {
            $h .= '<br><span class="notbold">'.$obj['Artistname'].'</span>';
        }
    }
    return $h;
}

function checkComposerGenre($genre, $pref) {
    $gl = strtolower($genre);
    foreach ($pref as $g) {
        if ($gl == strtolower($g)) {
            return true;
        }
    }
    return false;
}

function get_player_ip() {
    global $prefs;
    // SERVER_ADDR reflects the address typed into the browser
    debuglog("Server Address is ".$_SERVER['SERVER_ADDR'],"INIT",7);
    // REMOTE_ADDR is the address of the machine running the browser
    debuglog("Remote Address is ".$_SERVER['REMOTE_ADDR'],"INIT",7);
    debuglog("Prefs for mpd host is ".$prefs['mpd_host'],"INIT",7);
    $pip = '';
    if ($prefs['unix_socket'] != '') {
        $pip = $_SERVER['HTTP_HOST'];
    } else if ($prefs['mpd_host'] == "localhost" || $prefs['mpd_host'] == "127.0.0.1" || $prefs['mpd_host'] == '::1') {
        $pip = $_SERVER['HTTP_HOST'] . ':' . $prefs['mpd_port'];
    } else {
        $pip = $prefs['mpd_host'] . ':' . $prefs['mpd_port'];
    }
    debuglog("Displaying Player IP as: ".$pip,"INIT",7);
    return $pip;
}

function getCacheData($uri, $cache, $use_cache = true) {

    $me = strtoupper($cache);
    debuglog("Getting ".$uri, $me);
    if ($use_cache == false) {
        debuglog("  Not using cache for this request","CACHE");
    }
    $options = array(
        'url' => $uri,
        'send_cache_headers' => true,
        'cache' => $use_cache ? $cache : null,
        'return_data' => true
    );
    $d = new url_downloader($options);
    if ($d->get_data_to_file()) {
        print $d->get_data();
    } else {
        if ($d->get_status() > 0) {
            $header = $d->get_status().' '.http_status_code_string($d->get_status());
            debuglog("HTTP ERROR ".$header,"CACHE",6);
        } else {
            $header = '500 '.http_status_code_string(500);
        }
        header('HTTP/1.1 '.$header);
        if ($d->get_data() != '') {
            print $d->get_data();
        } else {
            print json_encode(array('error' => $header));
        }
    }
}

function get_user_file($src, $fname, $tmpname) {
    global $error;
    debuglog("  Uploading ".$src." ".$fname." ".$tmpname,"GETALBUMCOVER");
    $download_file = "prefs/temp/".$fname;
    debuglog("Checking Temp File ".$tmpname,"GETALBUMCOVER");
    if (move_uploaded_file($tmpname, $download_file)) {
        debuglog("    File ".$src." is valid, and was successfully uploaded.","GETALBUMCOVER");
    } else {
        debuglog("    Possible file upload attack!","GETALBUMCOVER");
        header('HTTP/1.0 403 Forbidden');
        ob_flush();
        exit(0);
    }
    return $download_file;
}

function albumImageBuggery() {
    // This was used to update album art to a new format but it's really old now and we've totally refactored the album image code
    // In the eventuality that someone is still using a version that old we'll keep the function but just use it to remove all album art
    // and start again.
    rrmdir('albumart/small');
    rrmdir('albumart/asdownloaded');
    mkdir('albumart/small', 0755);
    mkdir('albumart/asdownloaded', 0755);
    generic_sql_query("UPDATE Albumtable SET Searched = 0, Image = ''");
}

function rejig_wishlist_tracks() {
    global $mysqlc;
    generic_sql_query("DELETE FROM Playcounttable WHERE TTindex IN (SELECT TTindex FROM Tracktable WHERE Hidden = 1 AND Uri IS NULL)", true);
    generic_sql_query("DELETE FROM Tracktable WHERE Hidden = 1 AND Uri IS NULL", true);
    $result = generic_sql_query("SELECT * FROM Tracktable WHERE Uri IS NULL");
    foreach ($result as $obj) {
        if (sql_prepare_query(true, null, null, null,
            "INSERT INTO
                Albumtable
                (Albumname, AlbumArtistindex, AlbumUri, Year, Searched, ImgKey, mbid, Domain, Image)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            'rompr_wishlist_'.microtime(true), $obj['Artistindex'], null, 0, 0, null, null, 'local', null)) {

            $albumindex = $mysqlc->lastInsertId();
            debuglog("    Created Album with Albumindex ".$albumindex,"MYSQL",7);
            generic_sql_query("UPDATE Tracktable SET Albumindex = ".$albumindex." WHERE TTindex = ".$obj['TTindex'], true);
        }
    }
}

function multi_implode($array, $glue = ', ') {
    $ret = '';

    if (!is_array($array)) {
        return $array;
    }

    foreach ($array as $key => $item) {
        if (is_array($item)) {
            $ret .= $key . '=[' . multi_implode($item, $glue) . ']' . $glue;
        } else {
            $ret .= $key . '=' . $item . $glue;
        }
    }

    $ret = substr($ret, 0, 0-strlen($glue));

    return $ret;
}

function emptyCollectionDisplay() {
    print '<div id="emptycollection" class="pref textcentre">
    <p>Your Music Collection Is Empty</p>
    <p>You can add files to it by tagging and rating them, or you can build a collection of all your music</p>';
    print '</div>';
}

function emptySearchDisplay() {
    print '<div class="pref textcentre">
    <p>No Results</p>
    </div>';
}

function debug_format($dbg) {
    if (is_array($dbg)) {
        $dbg = implode($dbg, ", ");
    }
    return $dbg;
}

function format_bytes($size, $precision = 1)
{
    $base = log($size, 1024);
    $suffixes = array('', 'K', 'M', 'G', 'T');

    return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
}

function fixup_links($s) {
    return preg_replace('/(^|\s+|\n|[^\s+"])(https*:\/\/.*?)(<|\n|\r|\s|\)|$|[<|\n|\r|\s|\)|$])/', '$1<a href="$2">$2</a>$3', $s);
}

function set_version_string() {
    global $version_string, $prefs;
    if ($prefs['dev_mode']) {
        // This adds an extra parameter to the version number - the short
        // hash of the most recent git commit, or a timestamp. It's for use in testing,
        // to make sure the browser pulls in the latest version of all the files.
        if ($prefs['live_mode']) {
            $version_string = ROMPR_VERSION.".".time();
        } else {
            // DO NOT USE OUTSIDE A git REPO!
            $git_ver = exec("git log --pretty=format:'%h' -n 1", $output);
            if (count($output) == 1) {
                $version_string = ROMPR_VERSION.".".$output[0];
            } else {
                $version_string = ROMPR_VERSION.".".time();
            }
        }
    } else {
        $version_string = ROMPR_VERSION;
    }
}

function update_stream_images($schemaver) {
    require_once('utils/imagefunctions.php');
    switch ($schemaver) {
        case 43:
            $stations = generic_sql_query("SELECT Stationindex, StationName, Image FROM RadioStationtable WHERE Image LIKE 'prefs/userstreams/STREAM_%'");
            foreach ($stations as $station) {
                debuglog("  Updating Image For Station ".$station['StationName'], "BACKEND");
                if (file_exists($station['Image'])) {
                    debuglog("    Image is ".$station['StationName'], "BACKEND");
                    $src = get_base_url().'/'.$station['Image'];
                    $albumimage = new albumImage(array('artist' => "STREAM", 'album' => $station['StationName'], 'source' => $src));
                    if ($albumimage->download_image()) {
                        // Can't call $albumimage->update_image_database because the functions that requires are in the backend
                        $images = $albumimage->get_images();
                        sql_prepare_query(true, null, null, null, "UPDATE RadioStationtable SET Image = ? WHERE StationName = ?",$images['small'],$station['StationName']);
                        sql_prepare_query(true, null, null, null, "UPDATE WishlistSourcetable SET Image = ? WHERE Image = ?",$images['small'],$station['Image']);
                        unlink($station['Image']);
                    } else {
                        debuglog("  Image Upgrade Failed!","BACKEND");
                    }
                } else {
                    generic_sql_query("UPDATE RadioStationtable SET IMAGE = NULL WHERE Stationindex = ".$station['Stationindex']);
                }
            }
            break;
    }
}

function empty_modified_cache_dirs($schemaver) {
    switch ($schemaver) {
        case 44:
            foreach(array('allmusic', 'lyrics', 'lastfm') as $d) {
                rrmdir('prefs/jsoncache/'.$d);
                mkdir('prefs/jsoncache/'.$d, 0755);
            }
            break;
    }
}

function getRemoteFilesize($url, $default) {
    $context = stream_context_create(array('http' => array('method' => 'HEAD')));
    $head = array_change_key_case(get_headers($url, 1, $context));
    // content-length of download (in bytes), read from Content-Length: field
    $clen = isset($head['content-length']) ? $head['content-length'] : 0;
    $cstring = $clen;
    if (is_array($clen)) {
        debuglog("Content Length is an array ".PHP_EOL.print_r($clen, true),"FUNCTIONS", 9);
        $cstring = 0;
        foreach ($clen as $l) {
            if ($l > $cstring) {
                $cstring = $l;
            }
        }
    }
    if ($cstring !== 0) {
        debuglog("  Read file size remotely as ".$cstring,"FUNCTIONS",8);
        return $cstring;
    } else {
        debuglog("  Couldn't read filesize remotely. Using default value of ".$default,"FUNCTIONS",8);
        return $default;
    }
}

function rrmdir($path) {
    $i = new DirectoryIterator($path);
    foreach ($i as $f) {
        if($f->isFile()) {
            unlink($f->getRealPath());
        } else if (!$f->isDot() && $f->isDir()) {
            rrmdir($f->getRealPath());
        }
    }
    rmdir($path);
}

function collectionButtons() {
    print '<div id="collectionbuttons" class="invisible">';
    print '<div class="pref styledinputs">';
    print '<input type="radio" class="topcheck savulon" name="sortcollectionby" value="artist" id="sortbyartist">
    <label for="sortbyartist">'.ucfirst(get_int_text('label_artists')).'</label><br/>
    <input type="radio" class="topcheck savulon" name="sortcollectionby" value="album" id="sortbyalbum">
    <label for="sortbyalbum">'.ucfirst(get_int_text('label_albums')).'</label><br/>
    <input type="radio" class="topcheck savulon" name="sortcollectionby" value="albumbyartist" id="sortbyalbumbyartist">
    <label for="sortbyalbumbyartist">'.ucfirst(get_int_text('label_albumsbyartist')).'</label>
    <div class="pref">
    <input class="autoset toggle" type="checkbox" id="showartistbanners">
    <label for="showartistbanners">'.get_int_text('config_showartistbanners').'</label>
    </div>
    </div>
    <div class="pref styledinputs">
    <input class="autoset toggle" type="checkbox" id="sortbydate">
    <label for="sortbydate">'.get_int_text('config_sortbydate').'</label>
    <div class="pref">
    <input class="autoset toggle" type="checkbox" id="notvabydate">
    <label for="notvabydate">'.get_int_text('config_notvabydate').'</label>
    </div>
    </div>
    <div class="pref styledinputs">
    <input type="radio" class="topcheck savulon" name="collectionrange" value="'.ADDED_ALL_TIME.'" id="collalltime">
    <label for="collalltime">'.get_int_text('label_all_time').'</label><br/>
    <input type="radio" class="topcheck savulon" name="collectionrange" value="'.ADDED_TODAY.'" id="colltoday">
    <label for="colltoday">'.get_int_text('label_today').'</label><br/>
    <input type="radio" class="topcheck savulon" name="collectionrange" value="'.ADDED_THIS_WEEK.'" id="collweek">
    <label for="collweek">'.get_int_text('label_thisweek').'</label><br/>
    <input type="radio" class="topcheck savulon" name="collectionrange" value="'.ADDED_THIS_MONTH.'" id="collmonth">
    <label for="collmonth">'.get_int_text('label_thismonth').'</label><br/>
    <input type="radio" class="topcheck savulon" name="collectionrange" value="'.ADDED_THIS_YEAR.'" id="collyear">
    <label for="collyear">'.get_int_text('label_thisyear').'</label><br/>
    </div>
    <div class="pref textcentre">
    <button name="donkeykong">'.get_int_text('config_updatenow').'</button>
    </div>';
    print '</div>';

}

function http_status_code_string($code)
{
	// Source: http://en.wikipedia.org/wiki/List_of_HTTP_status_codes

	switch( $code )
	{
		// 1xx Informational
		case 100: $string = 'Continue'; break;
		case 101: $string = 'Switching Protocols'; break;
		case 102: $string = 'Processing'; break; // WebDAV
		case 122: $string = 'Request-URI too long'; break; // Microsoft

		// 2xx Success
		case 200: $string = 'OK'; break;
		case 201: $string = 'Created'; break;
		case 202: $string = 'Accepted'; break;
		case 203: $string = 'Non-Authoritative Information'; break; // HTTP/1.1
		case 204: $string = 'No Content'; break;
		case 205: $string = 'Reset Content'; break;
		case 206: $string = 'Partial Content'; break;
		case 207: $string = 'Multi-Status'; break; // WebDAV

		// 3xx Redirection
		case 300: $string = 'Multiple Choices'; break;
		case 301: $string = 'Moved Permanently'; break;
		case 302: $string = 'Found'; break;
		case 303: $string = 'See Other'; break; //HTTP/1.1
		case 304: $string = 'Not Modified'; break;
		case 305: $string = 'Use Proxy'; break; // HTTP/1.1
		case 306: $string = 'Switch Proxy'; break; // Depreciated
		case 307: $string = 'Temporary Redirect'; break; // HTTP/1.1

		// 4xx Client Error
		case 400: $string = 'Bad Request'; break;
		case 401: $string = 'Unauthorized'; break;
		case 402: $string = 'Payment Required'; break;
		case 403: $string = 'Forbidden'; break;
		case 404: $string = 'Not Found'; break;
		case 405: $string = 'Method Not Allowed'; break;
		case 406: $string = 'Not Acceptable'; break;
		case 407: $string = 'Proxy Authentication Required'; break;
		case 408: $string = 'Request Timeout'; break;
		case 409: $string = 'Conflict'; break;
		case 410: $string = 'Gone'; break;
		case 411: $string = 'Length Required'; break;
		case 412: $string = 'Precondition Failed'; break;
		case 413: $string = 'Request Entity Too Large'; break;
		case 414: $string = 'Request-URI Too Long'; break;
		case 415: $string = 'Unsupported Media Type'; break;
		case 416: $string = 'Requested Range Not Satisfiable'; break;
		case 417: $string = 'Expectation Failed'; break;
		case 422: $string = 'Unprocessable Entity'; break; // WebDAV
		case 423: $string = 'Locked'; break; // WebDAV
		case 424: $string = 'Failed Dependency'; break; // WebDAV
		case 425: $string = 'Unordered Collection'; break; // WebDAV
		case 426: $string = 'Upgrade Required'; break;
		case 449: $string = 'Retry With'; break; // Microsoft
		case 450: $string = 'Blocked'; break; // Microsoft

		// 5xx Server Error
		case 500: $string = 'Internal Server Error'; break;
		case 501: $string = 'Not Implemented'; break;
		case 502: $string = 'Bad Gateway'; break;
		case 503: $string = 'Service Unavailable'; break;
		case 504: $string = 'Gateway Timeout'; break;
		case 505: $string = 'HTTP Version Not Supported'; break;
		case 506: $string = 'Variant Also Negotiates'; break;
		case 507: $string = 'Insufficient Storage'; break; // WebDAV
		case 509: $string = 'Bandwidth Limit Exceeded'; break; // Apache
		case 510: $string = 'Not Extended'; break;

		// Unknown code:
		default: $string = 'Unknown';  break;
	}

	return $string;
}

function check_slave_actions($cmds) {
    global $prefs;
    //
    // Re-check all add and playlistadd commands if we're using a Mopidy File Backend Slave
    //
    if ($prefs['mopidy_slave']) {
        debuglog("Translating tracks for Mopidy Slave","MOPIDY",9);
        foreach ($cmds as $key => $cmd) {
            // add "local:track:
            // playlistadd "local:track:
            if (substr($cmd, 0, 17) == 'add "local:track:' ||
                substr($cmd, 0,25) == 'playlistadd "local:track:') {
                $cmds[$key] = swap_local_for_file($cmd);
            }

        }
    }
    return $cmds;
}

function check_reverse_slave_actions($cmds) {
    global $prefs;
    //
    // Re-check all add and playlistadd commands if we're using a Mopidy File Backend Slave
    //
    foreach ($cmds as $key => $cmd) {
        debuglog("Translating tracks from Mopidy Slave","MOPIDY",9);
        // add "local:track:
        // playlistadd "local:track:
        if (substr($cmd, 0, 12) == 'add "file://' ||
            substr($cmd, 0,20) == 'playlistadd "file://') {
            $cmds[$key] = swap_file_for_local($cmd);
        }

    }
    return $cmds;
}

function check_player_type_actions($cmds, $collection_type) {

    // Experimental translation to and from MPD/Mopidy Local URIs

    global $prefs;
    if ($prefs['player_backend'] != $collection_type) {
        debuglog("Translating Track Uris from ".$collection_type.' to '.$prefs['player_backend'], "PLAYER", 9);
        foreach ($cmds as $key => $cmd) {
            if (substr($cmd, 0, 4) == 'add ') {
                if ($collection_type == 'mopidy') {
                    $cmds[$key] = mopidy_to_mpd($cmd);
                } else {
                    $file = trim(substr($cmd, 4), '" ');
                    $cmds[$key] = 'add '.mpd_to_mopidy($file);
                }
            }
        }
    }
    return $cmds;

}

function mopidy_to_mpd($file) {
    return rawurldecode(preg_replace('#local:track:#', '', $file));
}

function mpd_to_mopidy($file) {
    if (substr($file, 0, 5) != 'http:' && substr($file, 0, 6) != 'https:') {
        return 'local:track:'.implode("/", array_map("rawurlencode", explode("/", $file)));
    } else {
        return $file;
    }
}

function swap_local_for_file($string) {
    // url encode the album art directory
    global $prefs;
    $path = implode("/", array_map("rawurlencode", explode("/", $prefs['music_directory_albumart'])));
    debuglog('Replacing with '.$path,'MOPIDYSLAVE');
    return preg_replace('#local:track:#', 'file://'.$path.'/', $string);
}

function swap_file_for_local($string) {
    global $prefs;
    $path = 'file://'.implode("/", array_map("rawurlencode", explode("/", $prefs['music_directory_albumart']))).'/';
    return preg_replace('#'.$path.'#', 'local:track:', $string);
}

function probe_player_type() {
    global $oldmopidy, $prefs;
    $oldmopidy = false;
    debuglog("Probing Player Type....","INIT",4);
    $r = do_mpd_command('tagtypes', true, true);
    if (is_array($r) && array_key_exists('tagtype', $r)) {
        if (in_array('X-AlbumUri', $r['tagtype'])) {
            debuglog("    ....tagtypes test says we're running Mopidy","INIT",4);
            $prefs['player_backend'] = "mopidy";
        } else {
            debuglog("    ....tagtypes test says we're running MPD","INIT",4);
            $prefs['player_backend'] = "mpd";
        }
    } else {
        debuglog("WARNING! No output for 'tagtypes' - probably an old version of Mopidy. RompЯ may not function correctly","INIT",2);
        $prefs['player_backend'] = "mopidy";
        $oldmopidy = true;
    }
}

?>
