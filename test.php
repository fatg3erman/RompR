<?php

$image = new SimpleImage('/Users/bob/Desktop/0b81b984c5e5b568a2443fb9ba1204a2.bmp');
echo $image->checkImage(), PHP_EOL;

class SimpleImage {

    private $image;
    private $resizedimage;
    private $image_type;
    private $filename;

    public function __construct($filename) {
        $this->filename = $filename;
        $image_info = getimagesize($filename);
        $this->image_type = $image_info[2];
        $imgtypes = imagetypes();
        switch ($this->image_type) {
            case IMAGETYPE_JPEG:
                debuglog("Image type is JPEG","IMAGE_GD");
                if (defined('IMG_JPG') && ($imgtypes && IMG_JPG) && function_exists('imagecreatefromjpeg')) {
                    $this->image = imagecreatefromjpeg($filename);
                } else {
                    $this->image_type = false;
                }
                break;
            
            case IMAGETYPE_GIF:
                debuglog("Image type is GIF","IMAGE_GD");
                if (defined('IMG_GIF') && ($imgtypes && IMG_GIF) && function_exists('imagecreatefromgif')) {
                    $this->image = imagecreatefromgif($filename);
                } else {
                    $this->image_type = false;
                }
                break;
            
            case IMAGETYPE_PNG:
                debuglog("Image type is PNG","IMAGE_GD");
                if (defined('IMG_PNG') && ($imgtypes && IMG_PNG) && function_exists('imagecreatefrompng')) {
                    $this->image = imagecreatefrompng($filename);
                } else {
                    $this->image_type = false;
                }
                break;
            
            case IMAGETYPE_WBMP:
                debuglog("Image type is WBMP","IMAGE_GD");
                if (defined('IMG_WBMP') && ($imgtypes && IMG_WBMP) && function_exists('imagecreatefromwbmp')) {
                    $this->image = imagecreatefromwbmp($filename);
                } else {
                    $this->image_type = false;
                }
                break;

            case IMAGETYPE_XBM:
                debuglog("Image type is XBM","IMAGE_GD");
                if (defined('IMG_XPM') && ($imgtypes && IMG_XPM) && function_exists('imagecreatefromxbm')) {
                    $this->image = imagecreatefromxbm($filename);
                } else {
                    $this->image_type = false;
                }
                break;

            case IMAGETYPE_WEBP:
                debuglog("Image type is WEBP","IMAGE_GD");
                if (defined('IMG_WEBP') && ($imgtypes && IMG_WEBP) && function_exists('imagecreatefromwebp')) {
                    $this->image = imagecreatefromwebp($filename);
                } else {
                    $this->image_type = false;
                }
                break;

            case IMAGETYPE_BMP:
                debuglog("Image type is BMP","IMAGE_GD");
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
        $this->resizedimage = $this->image;
    }
    
    public function checkImage() {
        if ($this->image_type === false) {
            debuglog("  Unsupported Image Type", "IMAGE_GD");
        }
        return $this->image_type;
    }
   
    public function reset() {
        $this->resizedimage = $this->image;
    }
   
    public function save($filename, $image_type=IMAGETYPE_JPEG, $compression=75) {
        if ($this->image_type === false) {
            copy($this->filename, $filename);
        } else if( $image_type == IMAGETYPE_JPEG ) {
            imagejpeg($this->resizedimage,$filename,$compression);
        } else if( $image_type == IMAGETYPE_GIF ) {
            imagegif($this->resizedimage,$filename);
        } else if( $image_type == IMAGETYPE_PNG ) {
            imagepng($this->resizedimage,$filename);
        }
    }

    public function getWidth() {
        return imagesx($this->image);
    }

    public function getHeight() {
        return imagesy($this->image);
    }

    public function resizeToHeight($height) {
        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        $this->resize($width,$height);
    }

    public function resizeToWidth($width) {
        $ratio = $width / $this->getWidth();
        $height = $this->getheight() * $ratio;
        $this->resize($width,$height);
    }

    public function scale($scale) {
        $width = $this->getWidth() * $scale/100;
        $height = $this->getheight() * $scale/100;
        $this->resize($width,$height);
    }

    public function resize($width,$height) {
        if ($this->image_type === false) {
           return true;
        } else {
           $this->resizedimage = imagecreatetruecolor($width, $height);
           imagecopyresampled($this->resizedimage, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        }
    }

}

function debuglog($a, $b) {
    echo $a,PHP_EOL;
}

?>
