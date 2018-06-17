<?php

function xmlnode($node, $content) {
    return '<'.$node.'>'.htmlspecialchars($content).'</'.$node.'>'."\n";
}

function format_for_mpd($term) {
    $term = str_replace('"','\\"',$term);
    return trim($term);
}


function format_for_disc($filename) {
    $filename = str_replace("\\","_",$filename);
    $filename = str_replace("/","_",$filename);
    $filename = str_replace("'","_",$filename);
    $filename = str_replace('"',"_",$filename);
    return $filename;
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

# url_get_contents function by Andy Langton: http://andylangton.co.uk/
function url_get_contents(  $url,
                            $useragent = ROMPR_IDSTRING,
                            $headers = false,
                            $follow_redirects = true,
                            $debug = false,
                            $fp = null,
                            $header = null,
                            $postfields = null,
                            $timeout = 120,
                            $conntimeout = 60) {

    global $prefs;
    $headerarray = [];
    $headerlen = 0;
    $url = preg_replace('/ /', '%20', $url);
    # initialise the CURL library
    $ch = curl_init();
    # specify the URL to be retrieved
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_ENCODING , "");
    if ($fp === null) {
        # we want to get the contents of the URL and store it in a variable
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    } else {
        curl_setopt($ch, CURLOPT_FILE, $fp);
    }
    # specify the useragent: this is a required courtesy to site owners
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
    # ignore SSL errors
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $conntimeout);
    if ($prefs['proxy_host'] != "") {
        curl_setopt($ch, CURLOPT_PROXY, $prefs['proxy_host']);
    }
    if ($prefs['proxy_user'] != "" && $prefs['proxy_password'] != "") {
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $prefs['proxy_user'].':'.$prefs['proxy_password']);
    }

    # return headers as requested
    if ($headers === true) {
        debuglog("Requesting Headers","BLURBLE");
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION,
            function($curl, $header) use (&$headerarray, &$headerlen)
            {
                $len = strlen($header);
                $headerlen += $len;
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                    return $len;

                $name = ($header[0]);
                $headerarray[$name] = trim($header[1]);
                return $len;
            }
        );
    }

    # only return headers
    if ($headers === 'headers only') {
        curl_setopt($ch, CURLOPT_NOBODY, true);
    }

    # follow redirects - note this is disabled by default in most PHP installs from 4.4.4 up
    if ($follow_redirects==true) {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    }

    if ($header !== null) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }

    if ($postfields !== null) {
        $fields_string = '';
        foreach ($postfields as $key=>$value) {
            $fields_string .= $key.'='.$value.'&';
        }
        rtrim($fields_string,'&');
        curl_setopt($ch, CURLOPT_POST, count($postfields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    }

    if ($fp === null) {
        $result['contents'] = curl_exec($ch);
    } else {
        curl_exec($ch);
    }

    $result['status'] = curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $result['content-type'] = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    if ($headers === true) {
        $result['headers'] = $headerarray;
        $result['contents'] = substr($result['contents'], $headerlen);
    }
    # if debugging, return an array with CURL's debug info and the URL contents
    if ($debug==true) {
        $result['info'] = curl_getinfo($ch);
    }
    # free resources
    curl_close($ch);

    # send back the data
    return $result;
}

function format_time($t,$f=':') // t = seconds, f = separator
{
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

function format_time2($t,$f=':') // t = seconds, f = separator
{
    if (($t/86400) >= 1) {
        return sprintf("%d%s", ($t/86400), " ".get_int_text("label_days"));
    }
    if (($t/3600) >= 1) {
        return sprintf("%d%s", ($t/3600), " ".get_int_text("label_hours"));
    } else {
        return sprintf("%d%s", ($t/60)%60, " ".get_int_text("label_minutes"));
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

    // I found this function on CleverLogic:
    // http://www.cleverlogic.net/tutorials/how-dynamically-get-your-sites-main-or-base-url

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

    /*
     * Returns:
     * http://localhost/mysite
     * OR
     * https://mysite.com
     */
    $directory = preg_replace('#/utils$#', '', $directory);
    $directory = preg_replace('#/streamplugins$#', '', $directory);
    $directory = preg_replace('#/includes$#', '', $directory);
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
    $files = glob($globpath."/*.{jpg,png,bmp,gif,jpeg,JPEG,JPG,BMP,GIF,PNG}", GLOB_BRACE);
    foreach($files as $i => $f) {
        $f = preg_replace('/%/', '%25', $f);
        debuglog("        Found : ".get_base_url()."/".preg_replace('/ /', "%20", $f),"GET_IMAGES");
        array_push($funkychicken, get_base_url()."/".preg_replace('/ /', "%20", $f));
    }
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
                        array_unshift($funkychicken, get_base_url()."/".preg_replace('/ /', "%20", $filename));
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
            $h = '<i class="icon-'.$d.'-circled '.$c.' fixed"></i>';
            break;

        case "tunein":
        case "radio-de":
        case "dirble":
        case "bassdrive":
            $h = '<div class="'.$c.' fixed"><img class="imgfill" src="newimages/'.$d.'-logo.svg" /></div>';
            break;

    }
    return $h;
}

function getImageForAlbum(&$filedata, $imagekey) {
    if ($filedata['ImageForPlaylist'] !== null && $filedata['ImageForPlaylist'] !== '') {
        return preg_replace('#/small/#', '/asdownloaded/',  $filedata['ImageForPlaylist']);
    } else {
        $im = cacheOrDefaultImage($filedata['X-AlbumImage'], $imagekey, 'asdownloaded', $filedata['domain']);
        if ($im == null) $im = '';
        return $im;
    }
}

function getImageKey(&$filedata, $albumartist) {
    if ($filedata['ImgKey'] !== null) {
        return $filedata['ImgKey'];
    } else {
        return make_image_key($albumartist, $filedata['Album']);
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

function audioClass($filetype) {
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
            return 'notastream';
            break;

        default:
            return 'icon-library';
            break;

    }

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

function getWishlist() {

    global $mysqlc, $divtype, $prefs;
    if ($mysqlc === null) {
        connect_to_database();
    }

    $qstring = "SELECT
        IFNULL(r.Rating, 0) AS rating,
        ".SQL_TAG_CONCAT." AS tags,
        tr.TTindex AS ttid,
        tr.Title AS title,
        tr.Duration AS time,
        tr.Albumindex AS albumindex,
        a.Artistname AS albumartist,
        tr.DateAdded AS DateAdded,
        ws.SourceName AS SourceName,
        ws.SourceImage AS SourceImage,
        ws.SourceUri AS SourceUri
        FROM
        Tracktable AS tr
        LEFT JOIN Ratingtable AS r ON tr.TTindex = r.TTindex
        LEFT JOIN TagListtable AS tl ON tr.TTindex = tl.TTindex
        LEFT JOIN Tagtable AS t USING (Tagindex)
        LEFT JOIN WishlistSourcetable AS ws USING (Sourceindex)
        JOIN Artisttable AS a ON (tr.Artistindex = a.Artistindex)
        WHERE
        tr.Uri IS NULL AND tr.Hidden = 0
        GROUP BY ttid
        ORDER BY ";

    switch ($_REQUEST['sortby']) {
        case 'artist':
            foreach ($prefs['artistsatstart'] as $a) {
                $qstring .= "CASE WHEN LOWER(albumartist) = LOWER('".$a."') THEN 1 ELSE 2 END, ";
            }
            if (count($prefs['nosortprefixes']) > 0) {
                $qstring .= "(CASE ";
                foreach($prefs['nosortprefixes'] AS $p) {
                    $phpisshitsometimes = strlen($p)+2;
                    $qstring .= "WHEN LOWER(albumartist) LIKE '".strtolower($p)." %' THEN LOWER(SUBSTR(albumartist,".
                        $phpisshitsometimes.")) ";
                }
                $qstring .= "ELSE LOWER(albumartist) END)";
            } else {
                $qstring .= "LOWER(albumartist)";
            }
            $qstring .= ", DateAdded, SourceName";
            break;
            
        case 'date':
            $qstring .= "DateAdded, SourceName";
            break;
            
        case 'station':
            $qstring .= 'SourceName, DateAdded';
            break;
            
        default:
            $qstring .= "rating, DateAdded";
            break;
        
    }

    $result = generic_sql_query($qstring);
    if (count($result) > 0) {
        print '<div class="containerbox padright noselection"><button class="fixed infoclick plugclickable clickclearwishlist">Clear Wishlist</button><div class="expand"></div></div>';
        print '<div class="configtitle brick_wide">Sort By</div>';
        print '<div class="containerbox padright noselection">';
        print '<div class="fixed brianblessed styledinputs"><input id="wishlist_sort_artist" class="topcheck savulon" type="radio" name="sortwishlistby" value="artist"><label for="wishlist_sort_artist">'.get_int_text('label_artist').'</label></div>';
        print '<div class="fixed brianblessed styledinputs"><input id="wishlist_sort_date" class="topcheck savulon" type="radio" name="sortwishlistby" value="date"><label for="wishlist_sort_date">'.get_int_text('label_dateadded').'</label></div>';
        print '<div class="fixed brianblessed styledinputs"><input id="wishlist_sort_station" class="topcheck savulon" type="radio" name="sortwishlistby" value="station"><label for="wishlist_sort_station">'.get_int_text('label_radiostation').'</label></div>';
        print '<div class="fixed brianblessed styledinputs"><input id="wishlist_sort_rating" class="topcheck savulon" type="radio" name="sortwishlistby" value="rating"><label for="wishlist_sort_rating">'.get_int_text('label_rating').'</label></div>';
        print '</div>';
    }
    foreach ($result as $obj) {
        debuglog("Found Track ".$obj['title']." by ".$obj['albumartist'],"WISHLIST");

        print '<div class="containerbox vertical" id="walbum'.$obj['albumindex'].'">';
        print '<div class="containerbox fixed">';
        if ($obj['SourceImage']) {
            print '<div class="smallcover fixed"><img class="smallcover" src="'.$obj['SourceImage'].'" /></div>';
        }
        print '<div class="expand containerbox vertical">';
        print '<div class="fixed tracktitle"><b>'.$obj['title'].'</b></div>';
        print '<div class="fixed playlistrow2 trackartist">'.$obj['albumartist'].'</div>';
        if ($obj['rating'] > 0) {
            print '<div class="fixed playlistrow2 trackrating"><i class="icon-'.$obj['rating'].'-stars rating-icon-small nopointer"></i></div>';
        }
        if ($obj['tags']) {
            print '<div class="fixed playlistrow2 tracktags"><i class="icon-tags smallicon"></i>'.$obj['tags'].'</div>';
        }
        print '</div>';
        print '<div class="expand containerbox vertical">';
        print '<div class="fixed playlistrow2">Added On : '.date('r', strtotime($obj['DateAdded'])).'</div>';
        if ($obj['SourceUri']) {
            print '<div class="fixed playlistrow2 clickable infoclick plugclickable clickstream" name="'.$obj['SourceUri'].'" streamname="'.$obj['SourceName'].'" streamimg="'.$obj['SourceImage'].'">While Listening To : <b>'.$obj['SourceName'].'</b></div>';
        }
        print '</div>';
        print '<i class="icon-search smallicon infoclick clicksearchtrack plugclickable fixed"></i>';
        print '<input type="hidden" value="'.$obj['title'].'" />';
        print '<input type="hidden" value="'.$obj['albumartist'].'" />';
        print '<i class="icon-cancel-circled playlisticonr fixed clickicon clickremdb infoclick plugclickable"></i>';
        print '<input type="hidden" value="'.$obj['ttid'].'" />';
        print '</div>';
        print '</div>';
    }
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

function getCacheData($uri, $cache, $returndata = false, $use_cache = true) {

    $me = strtoupper($cache);
    debuglog("Getting ".$uri, $me);
    if ($use_cache == false) {
        debuglog("  Not using cache for this request","CACHE");
    }

    if ($use_cache && file_exists('prefs/jsoncache/'.$cache.'/'.md5($uri))) {
        debuglog("Returning cached data",$me);
        if ($returndata) {
            return json_decode(file_get_contents('prefs/jsoncache/'.$cache.'/'.md5($uri)));
        } else {
            header("Pragma: From Cache");
            print file_get_contents('prefs/jsoncache/'.$cache.'/'.md5($uri));
        }
    } else {
        $content = url_get_contents($uri);
        $s = $content['status'];
        debuglog("Response Status was ".$s, $me);
        if ($s == "200") {
            if ($use_cache) {
                file_put_contents('prefs/jsoncache/'.$cache.'/'.md5($uri), $content['contents']);
            }
            if ($returndata) {
                return json_decode($content['contents']);
            } else {
                header("Pragma: Not Cached");
                print $content['contents'];
            }
        } else {
            $a = array( 'error' => get_int_text($cache."_error"));
            if ($returndata) {
                return $a;
            } else {
                header("Pragma: Not Cached");
                print json_encode($a);
            }
        }
    }

}

function get_user_file($src, $fname, $tmpname) {
    global $error;
    debuglog("  Uploading ".$src." ".$fname." ".$tmpname,"GETALBUMCOVER");
    $download_file = "prefs/".$fname;
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

function make_image_key($artist,$album) {
    $c = strtolower($artist.$album);
    return md5($c);
}

function albumImageBuggery() {
    set_time_limit(600);
    $result = generic_sql_query(
        "SELECT Albumindex, Artistname, Albumname, ImgKey, Image FROM Albumtable JOIN Artisttable ON Albumtable.AlbumArtistindex = Artisttable.Artistindex", false, PDO::FETCH_OBJ);
    open_transaction();
foreach ($result as $obj) {
        $oldkey = $obj->ImgKey;
        $newkey = make_image_key($obj->Artistname, $obj->Albumname);
        $oldimage = $obj->Image;
        $newimage = $oldimage;
        if (preg_match('#^albumart/#', $oldimage)) {
            if (file_exists($oldimage)) {
                debuglog("Renaming albumart image ".$oldkey." to ".$newkey,"BACKEND_UPGRADE");
                $newimage = 'albumart/small/'.$newkey.'.jpg';
                exec( 'mv albumart/small/'.$oldkey.'.jpg '.$newimage);
                exec( 'mv albumart/asdownloaded/'.$oldkey.'.jpg albumart/asdownloaded/'.$newkey.'.jpg');
                sql_prepare_query(true, null, null, null, "UPDATE Albumtable SET ImgKey = ?, Image = ? WHERE Albumindex = ?",$newkey,$newimage,$obj->Albumindex);
            }
        } else if (preg_match('#^prefs/imagecache/#', $oldimage)) {
            if (file_exists($oldimage)) {
                debuglog("Renaming imagecache image ".$oldkey." to ".$newkey,"BACKEND_UPGRADE");
                $newimage = 'prefs/imagecache/'.$newkey.'_small.jpg';
                exec('mv prefs/imagecache/'.$oldkey.'_small.jpg '.$newimage);
                exec('mv prefs/imagecache/'.$oldkey.'_asdownloaded.jpg prefs/imagecache/'.$newkey.'_asdownloaded.jpg');
                sql_prepare_query(true, null, null, null, "UPDATE Albumtable SET ImgKey = ?, Image = ? WHERE Albumindex = ?",$newkey,$newimage,$obj->Albumindex);
            }
        }
        check_transaction();
    }
    close_transaction();
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

function get_stream_imgkey($i) {
    return "STREAM_".$i;
}

function stream_index_from_key($key) {
    return preg_replace('/STREAM_/','',$key);
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


?>
