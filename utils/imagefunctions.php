<?php

class baseAlbumImage {
    /*
    Can be initialised in one of several ways
    
    1. With baseimage (eg albumart/small/thignrirvu.jpg) in order to calculate the paths for the other sizes
        and, optionally, artist, album etc so an image key can be generated
    2. With a key, in which case the artist info etc will be looked up in the collection
    3. With Artist and Album info (see image_info_from_album_info) in order to do an image search or calculate an image key
        For 'artist' of PODCAST, albumpath must be set to the podcasts' base directory (the PODindex).
        A key must NOT be supplied in this case.
    */
    
    // Remember to keep albumart_translator in uifunctions.js in step with this
    
    public function __construct($params) {
        global $prefs;
        foreach (array('artist', 'album', 'key', 'source', 'file', 'base64data', 'mbid', 'albumpath', 'albumuri') as $param) {
            if (array_key_exists($param, $params) && $params[$param] != '') {
                $this->{$param} = $params[$param];
            } else {
                $this->{$param} = null;
            }
        }
        // We need to be able to send baseimage as '' for it all to work with the collection,
        // And X-AlbumImage defaults to null for all kinds of reasons, probably.
        if (array_key_exists('baseimage', $params)) {
            $this->baseimage = $params['baseimage'];
        } else {
            $this->baseimage = 'No-Image';
        }
        if (array_key_exists("ufile", $_FILES)) {
            $this->file = $_FILES['ufile']['name'];
        }
        if (preg_match('/\d+/', $this->mbid) && !preg_match('/-/', $this->mbid)) {
            debuglog(" Supplied MBID of ".$mbid." looks more like a Discogs ID", "ALBUMIMAGE");
            $this->mbid = null;
        }
        if ($prefs['player_backend'] == 'mopidy') {
            $this->albumpath = urldecode($this->albumpath);
        }
        $this->image_downloaded = false;
        if ($this->baseimage != 'No-Image') {
            $this->images = $this->image_paths_from_base_image($params['baseimage']);
            $this->key = $this->make_image_key();
        } else if ($this->key !== null) {
            $this->image_info_from_database();
        } else {
            $this->images = $this->image_info_from_album_info();
        }
    }
    
    public function get_image_key() {
        return $this->key;
    }
    
    private function image_exists($image) {
        debuglog("Checking for existence of file ".$image,"ALBUMIMAGE");
        return file_exists($image);
    }
    
    public function get_image_if_exists() {
        if ($this->image_exists($this->images['small'])) {
            return $this->images['small'];
        } else {
            return null;
        }
    }
    
    public function get_images() {
        return $this->images;
    }
    
    private function check_if_image_already_downloaded() {
        $checkimages = $this->image_info_from_album_info();
        if ($this->image_exists($checkimages['small'])) {
            debuglog("  ..  File exists","ALBUMIMAGE");
            $this->images = $checkimages;
            return true;
        } else {
            return false;
        }
    }
    
    public function is_collection_image() {
        return preg_match('#albumart/small/#', $this->images['small']);
    }

    public function check_image($domain, $type, $in_playlist = false) {
        global $doing_search;
        // If there's no image, see if we can set a default
        // Note we don't set defaults for streams because coverscaper handles those
        // so it can set them in the playlist even when auto art download is off
        
        $disc_checked = false;
        if ($this->images['small'] == '' || $this->images['small'] === null) {
            if ($this->artist == 'STREAM') {
                // Stream images may not be in the database
                // BUT they may be present anyway, if a stream was added eg from a playlist
                // of streams and coverscraper found one. So check, otherwise coverscraper
                // will search for it every time the playlist repopulated.
                if ($this->check_if_image_already_downloaded()) {
                    return true;
                }
                $disc_checked = true;
            }

            // Checking if the file already exists on disc doesn't help at all
            // when we're building a collection (and only slows things down).
            // If the album is already in the collection it'll have an image (or not) and
            // this will be in the database. The collection update will not change it
            // if this returns no image because we use best_value()
            
            if ($in_playlist) {
                if (!$disc_checked && $this->check_if_image_already_downloaded()) {
                    // Image may have already been downloaded if we've added the album
                    // to the Current Playlist from search results.
                } else {
                    // Different defaults for the Playlist, we'd like to be able
                    // to download images and the information we get in the playlist
                    // (from Mopidy anyway, which is where these matter), is more complete
                    // than what comes from search results which often lack any sort
                    // of useful information.
                    switch ($domain) {
                        case 'bassdrive':
                        // case 'dirble':
                        case 'internetarchive':
                        case 'oe1':
                        // case 'podcast':
                        // case 'radio-de':
                        case 'soundcloud':
                        // case 'tunein':
                        case 'youtube':
                            $this->images = $this->image_paths_from_base_image('newimages/'.$domain.'-logo.svg');
                            break;
                    }
                }
                return true;
            }
            
            if ($doing_search) {
                if (!$disc_checked && $this->check_if_image_already_downloaded()) {
                    // We may have searched for it before
                    return true;
                }
            }
            
            switch ($domain) {
                case 'bassdrive':
                case 'dirble':
                case 'internetarchive':
                case 'oe1':
                case 'podcast':
                case 'radio-de':
                case 'soundcloud':
                case 'tunein':
                case 'youtube':
                    $this->images = $this->image_paths_from_base_image('newimages/'.$domain.'-logo.svg');
                    break;

            }
        }
    }
    
    private function image_paths_from_base_image($image) {
        $images = array(
            'small' => $image,
            'medium' => preg_replace('#albumart/small/#', 'albumart/medium/', $image),
            'asdownloaded' => preg_replace('#albumart/small/#', 'albumart/asdownloaded/', $image)
        );
        if (substr($image, 0, 14) == 'getRemoteImage') {
            array_walk($images, function(&$v, $k) {
                $v .= '&amp;rompr_resize_size='.$k;
            });
        }
        return $images;
    }
    
    protected function change_file_extension($new) {
        foreach ($this->images as $size => $path) {
            $p = pathinfo($path);
            $this->images[$size] = $p['dirname'].'/'.$p['filename'].'.'.$new;
        }
    }
    
    private function image_info_from_database() {
        $this->basepath = 'albumart/';
        $info = get_imagesearch_info($this->key);
        foreach ($info as $k => $v) {
            $this->{$k} = $v;
        }
        $smallimage = $this->basepath.'small/'.$this->key.'.jpg';
        $this->images = $this->image_paths_from_base_image($smallimage);
    }
    
    protected function image_info_from_album_info() {
        switch ($this->artist) {
            case 'PLAYLIST':
                $this->key = $this->make_image_key();
                $this->basepath = 'prefs/plimages/'.$this->key.'/albumart/';
                break;
                
            case 'STREAM':
                $this->key = $this->make_image_key();
                $this->basepath = 'prefs/userstreams/'.$this->key.'/albumart/';
                break;
                
            case 'PODCAST':
                $this->key = $this->make_image_key();
                $this->basepath = 'prefs/podcasts/'.$this->albumpath.'/albumart/';
                break;
                
            default:
                $this->key = $this->make_image_key();
                $this->basepath = 'albumart/';
                break;
        }
        $smallimage = $this->basepath.'small/'.$this->key.'.jpg';
        return $this->image_paths_from_base_image($smallimage);
    }
    
    private function make_image_key() {
        $key = strtolower($this->artist.$this->album);
        return md5($key);
    }
    
    public function html_for_image($obj, $imageclass, $size) {
        $extra = (array_key_exists('userplaylist', $obj)) ? 'plimage '.$imageclass : $imageclass;
        if (!$this->images['small'] && $obj['Searched'] != 1) {
            return '<img class="notexist '.$extra.'" name="'.$obj['ImgKey'].'" />'."\n";
        } else  if (!$this->images['small'] && $obj['Searched'] == 1) {
            return '<img class="notfound '.$extra.'" name="'.$obj['ImgKey'].'" />'."\n";
        } else {
            return '<img class="'.$extra.'" name="'.$obj['ImgKey'].'" src="'.$this->images[$size].'" />'."\n";
        }
    }
    
}

class albumImage extends baseAlbumImage {
    
    public function set_source($src) {
        $this->source = $src;
    }
    
    public function has_source() {
        if ($this->source === null && $this->file === null && $this->base64data === null) {
            return false;
        } else {
            return true;
        }
    }
    
    public function download_image() {
        if (!$this->has_source()) {
            return false;
        }
        $retval = false;
        if ($this->source) {
            $retval = $this->download_remote_file();
        } else if ($this->file) {
            $retval = get_user_file($this->file, $this->key, $_FILES['ufile']['tmp_name']);
        } else if ($this->base64data) {
            $retval = $this->save_base64_data();
        }
        if ($retval !== false) {
            $this->image_downloaded = true;
            $retval = $this->saveImage($retval);
        }
        return $retval;
    }
    
    public function update_image_database() {
        switch ($this->artist) {
            case 'PLAYLIST';
                break;
                
            case 'STREAM':
                if ($this->image_downloaded) {
                    update_stream_image($this->album, $this->images['small']);
                }
                break;
                
            case 'PODCAST':
                if ($this->image_downloaded) {
                    update_podcast_image($this->albumpath, $this->images['small']);
                }
                break;
                
            default:
                update_image_db($this->key, $this->image_downloaded, $this->images['small']);
                break;
                
        }
    }

    public function get_artist_for_search() {
        switch ($this->artist) {
            case 'PLAYLIST':
            case 'STREAM':
                return '';
                break;

            case 'PODCAST':
            case 'Podcasts':
                return 'Podcast';
                break;
                
            default:
                return $this->artist;
                break;
        }
    }

    public function change_name($new_name) {
        switch ($this->artist) {
            case 'PLAYLIST':
                debuglog("Playlist name changing from ".$this->album." to ".$new_name,"FUCKINGHELL");
                if (file_exists($this->images['small'])) {
                    $oldbasepath = dirname($this->basepath);
                    $oldkey = $this->key;
                    $this->album = $new_name;
                    $this->images = $this->image_info_from_album_info();
                    $newbasepath = dirname($this->basepath);
                    debuglog("Renaming Playlist Image from ".$oldbasepath." to ".$newbasepath,"ALBUMIMAGE");
                    rename($oldbasepath, $newbasepath);
                    foreach ($this->images as $image) {
                        $oldimage = dirname($image).'/'.$oldkey.'.jpg';
                        rename($oldimage, $image);
                    }
                }
                break;
        }
    }
    
    private function saveImage($download_file) {
        $imagehandler = new imageHandler($download_file);
        switch ($imagehandler->checkImage()) {
            case IMAGETYPE_PNG:
                $this->change_file_extension('png');
                break;
                
            case IMAGETYPE_SVG:
                $this->change_file_extension('svg');
                break;
        }
        foreach ($this->images as $image) {
            $dir = dirname($image);
            $size = basename($dir);
            if (file_exists($image)) {
                unlink($image);
            }
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            debuglog("  Creating file ".$image,"ALBUMIMAGE");
            switch ($size) {
                case 'small':
                    $imagehandler->resizeToWidth(100);
                    $imagehandler->save($image, 75);
                    break;
    
                case 'medium':
                    $imagehandler->resizeToWidth(400);
                    $imagehandler->save($image, 70);
                    break;
    
                case 'asdownloaded':
                    $imagehandler->reset();
                    $imagehandler->save($image, 90);
                    break;
            }
        }
        unlink($download_file);
        return $this->images;
    
    }
    
    private function download_remote_file() {
        $download_file = 'prefs/temp/'.$this->key;
        $retval = $download_file;
        $d = new url_downloader(array('url' => $this->source));
        if ($d->get_data_to_file($download_file, true)) {
            $content_type = $d->get_content_type();
            if (substr($content_type,0,5) != 'image' && $content_type != 'application/octet-stream') {
        		debuglog("  .. Content type is ".$content_type." - not an image file! ".$this->source,"ALBUMIMAGE");
                $retval = false;
            }
    	} else {
            $retval = false;
    	}
        return $retval;
    }
    
    private function save_base64_data() {
        debuglog("  Saving base64 data","ALBUMIMAGE");
        $image = explode('base64,',$this->base64data);
        $download_file = 'prefs/temp/'.$this->key;
        file_put_contents($download_file, base64_decode($image[1]));
        return $download_file;
    }
            
}

function artist_for_image($type, $artist) {
	switch ($type) {
		case 'stream':
			$artistforimage = 'STREAM';
			break;
            
		default:
			$artistforimage = $artist;
			break;
	}
	return $artistforimage;
}

class imageHandler {
    
    private $filename;
    private $image;
    
    public function __construct($filename) {
        if (extension_loaded('gd')) {
            $this->image = new gdImage($filename);
            if ($this->image->checkImage() === false) {
                $this->image = new imageMagickImage($filename);
            }
        } else {
            $this->image = new imageMagickImage($filename);
        }
    }
    
    public function checkImage() {
        return $this->image->checkImage();
    }
    
    public function reset() {
        $this->image->reset();
    }
    
    public function save($filename, $compression = 75) {
        $this->image->save($filename, $compression);
    }
    
    public function outputResizedFile($size) {
        $this->image->outputResizedFile($size);
    }
    
    public function resizeToWidth($width) {
        $this->image->resizeToWidth($width);
    }
    
    public function get_image_dimensions() {
        return $this->image->get_image_dimensions();
    }
        
}

class imageMagickImage {
    
    private $filename;
    private $convert_path;
    private $resize_to = 0;
    private $image_type;
    
    public function __construct($filename) {
        $this->filename = $filename;
        $this->convert_path = find_executable('convert');
        $this->image_type = mime_content_type($this->filename);
    }
    
    public function reset() {
        $this->resize_to = 0;
    }
    
    public function checkImage() {
        debuglog("  Image type is ".$this->image_type,"IMAGEMAGICK");
        return $this->image_type;
    }
    
    public function save($filename, $compression) {
        if ($this->image_type == IMAGETYPE_SVG) {
            debuglog("  Copying SVG file instead of converting","IMAGEMAGICK");
            copy($this->filename, $filename);
        } else if ($this->convert_path === false) {
            debuglog("WARNING! ImageMagick not installed","IMAGEMAGICK",2);
            copy($this->filename, $filename);
        } else {
            if ($this->image_type == IMAGETYPE_PNG) {
                $params = ' -quality 95';
            } else {
                $params = ' -quality '.$compression.' -alpha remove';
            }
            if ($this->resize_to > 0) {
                $params .= ' -resize '.$this->resize_to;
            }
            $cmd = 'convert "'.$this->filename.'"'.$params.' "'.$filename.'" 2>&1';
            debuglog("  Command is ".$cmd,"IMAGEMAGICK",8);
            exec($this->convert_path.$cmd, $o);
        }
    }
    
    public function outputResizedFile() {
        header('Content-type: '.$this->image_type);
        readfile($this->filename);
    }
    
    public function resizeToWidth($width) {
        $this->resize_to = $width;
    }
    
    public function get_image_dimensions() {
        $width = -1;
        $height = -1;
        if ($this->image_type != IMAGETYPE_SVG) {
            $c = $this->convert_path."identify \"".$this->filename."\" 2>&1";
            $o = array();
            $r = exec($c, $o);
            if (preg_match('/ (\d+)x(\d+) /', $r, $matches)) {
                $width = $matches[1];
                $height = $matches[2];
            }
        }
        return array('width' => $width, 'height' => $height);
    }
    
}

class gdImage {

    private $image;
    private $resizedimage;
    private $image_type;
    private $filename;

    public function __construct($filename) {
        $this->filename = $filename;
        $image_info = getimagesize($filename);
        $this->image_type = $image_info[2];
        $imgtypes = imagetypes();
        
        // We're being very careful here to check that the image is of a supported type
        // without throwing any errors. Belt and braces, since different PHP-GD installations
        // have different supported types, and IMG_BMP wasn't introduced until PHP7.2
        // The case values for the switch statement are always defined to something, since vars.php sets them
        // to the MIME type of that image if they aren't already defined, as that's what imageMagickImage uses
        // for its image_type
        
        // So if GD is loaded but doesn't support a particular image this sets image_type to false, and imageHandler
        // falls back to imagemagick.
        
        // This list contains all the image types that GD might be able to read, currently.
        
        switch ($this->image_type) {
            case IMAGETYPE_JPEG:
                debuglog("Image type is JPEG","GD-IMAGE");
                if (defined('IMG_JPG') && ($imgtypes && IMG_JPG) && function_exists('imagecreatefromjpeg') && function_exists('imagejpeg')) {
                    $this->image = imagecreatefromjpeg($filename);
                } else {
                    $this->image_type = false;
                }
                break;
            
            case IMAGETYPE_GIF:
                debuglog("Image type is GIF","GD-IMAGE");
                if (defined('IMG_GIF') && ($imgtypes && IMG_GIF) && function_exists('imagecreatefromgif')) {
                    $this->image = imagecreatefromgif($filename);
                } else {
                    $this->image_type = false;
                }
                break;
            
            case IMAGETYPE_PNG:
                debuglog("Image type is PNG","GD-IMAGE");
                if (defined('IMG_PNG') && ($imgtypes && IMG_PNG) && function_exists('imagecreatefrompng') && function_exists('imagepng')) {
                    $this->image = imagecreatefrompng($filename);
                } else {
                    $this->image_type = false;
                }
                break;
            
            case IMAGETYPE_WBMP:
                debuglog("Image type is WBMP","GD-IMAGE");
                if (defined('IMG_WBMP') && ($imgtypes && IMG_WBMP) && function_exists('imagecreatefromwbmp')) {
                    $this->image = imagecreatefromwbmp($filename);
                } else {
                    $this->image_type = false;
                }
                break;

            case IMAGETYPE_XBM:
                debuglog("Image type is XBM","GD-IMAGE");
                if (defined('IMG_XPM') && ($imgtypes && IMG_XPM) && function_exists('imagecreatefromxbm')) {
                    $this->image = imagecreatefromxbm($filename);
                } else {
                    $this->image_type = false;
                }
                break;

            case IMAGETYPE_WEBP:
                debuglog("Image type is WEBP","GD-IMAGE");
                if (defined('IMG_WEBP') && ($imgtypes && IMG_WEBP) && function_exists('imagecreatefromwebp')) {
                    $this->image = imagecreatefromwebp($filename);
                } else {
                    $this->image_type = false;
                }
                break;

            case IMAGETYPE_BMP:
                debuglog("Image type is BMP","GD-IMAGE");
                if (defined('IMG_BMP') && ($imgtypes && IMG_BMP) && function_exists('imagecreatefrombmp')) {
                    $this->image = imagecreatefrombmp($filename);
                } else {
                    $this->image_type = false;
                }
                break;

            default:
                $this->image_type = false;
                break;
          
        }
        if ($this->image_type !== false) {
            $this->reset();
        }
    }
    
    public function checkImage() {
        if ($this->image_type === false) {
            debuglog("  Unsupported Image Type", "GD-IMAGE");
        }
        return $this->image_type;
    }
   
    public function reset() {
        $this->resizedimage = $this->image;
        imagealphablending($this->resizedimage, false);
        imagesavealpha($this->resizedimage, true);
    }
   
    public function save($filename, $compression) {
        if ($this->image_type == IMAGETYPE_PNG) {
            // Be aware - We always save PNGs as PNGs to preserve alpha channel
            imagepng($this->resizedimage, $filename, 9);
        } else {
            imagejpeg($this->resizedimage, $filename, $compression);
        }
    }

    public function outputResizedFile($size) {
        if ($this->image_type == IMAGETYPE_PNG) {
            debuglog("  Outputting PNG file of size ".$size,"GD-IMAGE");
            header('Content-type: image/png');
        } else {
            debuglog("  Outputting JPEG file of size ".$size,"GD-IMAGE");
            header('Content-type: image/jpeg');
        }
        switch ($size) {
            case 'small':
                $this->resizeToWidth(100);
                $this->save(null, 75);
                break;

            case 'medium':
                $this->resizeToWidth(400);
                $this->save(null, 70);
                break;
                
            default:
                $this->save(null, 90);
                break;
                
        }
    }

    public function resizeToWidth($width) {
        $ratio = $width / $this->getWidth();
        $height = $this->getheight() * $ratio;
        $this->resize($width,$height);
    }

    private function getWidth() {
        return imagesx($this->image);
    }

    private function getHeight() {
        return imagesy($this->image);
    }

    private function resize($width, $height) {
       $this->resizedimage = imagecreatetruecolor($width, $height);
       imagealphablending($this->resizedimage, false);
       imagesavealpha($this->resizedimage, true);
       imagecopyresampled($this->resizedimage, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
    }

    public function get_image_dimensions() {
        return array('width' => $this->getWidth(), 'height' => $this->getHeight());
    }

}

?>
