<?php

function download_file($src, $fname, $convert_path) {
    global $error;

    $download_file = "albumart/".$fname;
    debuglog("   Downloading Image ".$src." to ".$download_file,"GETALBUMCOVER");

    $test = 'prefs/imagecache/'.md5($src);
    if (file_exists($test)) {
        debuglog("     Image already exists in image cache","GETALBUMCOVER");
        exec('cp "'.$test.'" "'.$download_file.'"');
        return $download_file;
    }

    if (file_exists($download_file)) {
        unlink ($download_file);
    }
    $aagh = url_get_contents($src,$_SERVER['HTTP_USER_AGENT']);
    $fp = fopen($download_file, "x");
    if ($fp) {
        fwrite($fp, $aagh['contents']);
        fclose($fp);
        check_file($download_file, $aagh['contents']);
        $o = array();
        $c = $convert_path."identify \"".$download_file."\" 2>&1";
        // debuglog("    Command is ".$c,"GETALBUMCOVER");
        $r = exec( $c, $o);
        debuglog("    Return value from identify was ".$r,"GETALBUMCOVER");
        if ($r == '' ||
            preg_match('/GIF 1x1/', $r) ||
            preg_match('/unable to open/', $r) ||
            preg_match('/no decode delegate/', $r)) {
            debuglog("      Broken/Invalid file returned","GETALBUMCOVER");
            $error = 1;
        }
    } else {
        debuglog("    File open failed!","GETALBUMCOVER");
        $error = 1;
    }
    return $download_file;
}

function saveImage($fname, $in_collection, $stream) {
    global $convert_path, $download_file;
    debuglog("  Saving Image ".$download_file,"GETALBUMCOVER");
    $small_file = null;
    $anglofile = null;
    if ($in_collection === 1) {
        debuglog("    Saving image to albumart folder","GETALBUMCOVER");
        $small_file = "albumart/small/".$fname.".jpg";
        $anglofile = "albumart/asdownloaded/".$fname.".jpg";
    } else if ($stream == ROMPR_PLAYLIST_KEY) {
        debuglog("    Saving image to user playlist folder","GETALBUMCOVER");
        $small_file = null;
        $anglofile = "prefs/plimages/".$fname.".jpg";
    } else if ($stream != '') {
        debuglog("    Saving image to userstream folder","GETALBUMCOVER");
        $small_file = null;
        $anglofile = "prefs/userstreams/".$stream.".jpg";
    }
    if ($small_file && file_exists($small_file)) {
        unlink($small_file);
    }
    if (file_exists($anglofile)) {
        unlink($anglofile);
    }
    // Ohhhhhh imagemagick is just... wow.
    // This resizes the images into a square box while adding padding to preserve the apsect ratio
    // -alpha remove removes the alpha (transparency) channel if it exists - JPEG doesn't have one of these and
    // trying to resize PNGs with alpha channels and save them as JPEGs gives horrid results with convert
    $o = array();
    if ($small_file) {
        debuglog("Creating file ".$small_file,"SAVEIMAGE");
        $r = exec( $convert_path."convert \"".$download_file."\" -resize 82x82 -background black -alpha remove -gravity center -extent 82x82 \"".$small_file."\" 2>&1", $o);
    }
    debuglog("Creating file ".$anglofile,"SAVEIMAGE");
    $r = exec( $convert_path."convert \"".$download_file."\" -background black -alpha remove \"".$anglofile."\" 2>&1", $o);

    return array($small_file, $anglofile);
}

function check_file($file, $data) {
    // NOTE. We've configured curl to follow redirects, so in truth this code should never do anything
    $matches = array();
    if (preg_match('/See: (.*)/', $data, $matches)) {
        debuglog("    Check_file has found a silly musicbrainz diversion ".$data,"GETALBUMCOVER");
        $new_url = $matches[1];
        system('rm "'.$file.'"');
        $aagh = url_get_contents($new_url);
        debuglog("    check_file is getting ".$new_url,"GETALBUMCOVER");
        $fp = fopen($file, "x");
        if ($fp) {
            fwrite($fp, $aagh['contents']);
            fclose($fp);
        }
    }
}

function archive_image($image, $imagekey) {

    global $error, $convert_path, $download_file, $doing_search;
    $retval = array( 'searched' => 0, 'image' => '');
    if (file_exists('albumart/small/'.$imagekey.'.jpg')) {
        $retval = array( 'searched' => 1, 'image' => 'albumart/small/'.$imagekey.'.jpg');
    } else if ($doing_search) {
        $retval = array(
            'searched' => ($image == '' || $image === null) ? 0 : 1,
            'image' => $image
        );
    } else if (preg_match('#^getRemoteImage\.php\?url=#', $image)) {
        $retval = array('searched' => 1, 'image' => $image);
    } else if (file_exists($image)) {
        $retval = array('searched' => 1, 'image' => $image);
    }

    return $retval;

}

function get_image_dimensions($image) {
    global $convert_path;
    $c = $convert_path."identify \"".$image."\" 2>&1";
    $o = array();
    $r = exec($c, $o);
    $width = -1;
    $height = -1;
    if (preg_match('/ (\d+)x(\d+) /', $r, $matches)) {
        $width = $matches[1];
        $height = $matches[2];
    }
    return array('width' => $width, 'height' => $height);
}


?>
