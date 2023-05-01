<?php
class gd_Image {

	private $image;
	private $resizedimage;
	private $image_type = false;
	private $filename;

	public static function gd_handle_error($errno, $errstr, $errfile, $errline) {
		logger::warn("GD_IMAGE", "Error",$errno,$errstr,"in",$errfile,"at line",$errline);
		throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		return true;
	}

	public function __construct($filename) {

		set_error_handler('gd_Image::gd_handle_error', E_ALL);

		$this->filename = $filename;
		logger::core("GD-IMAGE", "Checking File ".$filename);
		$imgtypes = imagetypes();
		try {
			$image_info = getimagesize($filename);
			$image_type = $image_info[2];
			logger::core("GD-IMAGE", "Image Type is ".$image_type);
		} catch (Exception $e) {
			logger::warn("GD-IMAGE", "  GD threw an error when handling",$filename);
			$image_type = false;
		}

		// We're being very careful here to check that the image is of a supported type
		// without throwing any errors. Belt and braces, since different PHP-GD installations
		// have different supported types, and IMG_BMP wasn't introduced until PHP7.2
		// The case values for the switch statement are always defined to something, since vars.php sets them
		// to the MIME type of that image if they aren't already defined, as that's what imageMagickImage uses
		// for its image_type

		// So if GD is loaded but doesn't support a particular image this sets image_type to false, and imageHandler
		// falls back to imagemagick.

		// This list contains all the image types that GD might be able to read, currently.

		// In the outside chance that an error occurs on a supported image type - sometimes libpng throws a fatal wobbler on some images -
		// the error handler catches it and we fall back to Imagemagick

		switch ($image_type) {
			case IMAGETYPE_JPEG:
				logger::core("GD-IMAGE", "Image type is JPEG");
				if (defined('IMG_JPG') && ($imgtypes && IMG_JPG) && function_exists('imagecreatefromjpeg') && function_exists('imagejpeg')) {
					try {
						$this->image = imagecreatefromjpeg($filename);
					} catch (Exception $e) {
						logger::warn("GD-IMAGE", "  GD threw an error when handling",$filename);
						$image_type = false;
					}
				} else {
					$image_type = false;
				}
				break;

			case IMAGETYPE_GIF:
				logger::core("GD-IMAGE", "Image type is GIF");
				if (defined('IMG_GIF') && ($imgtypes && IMG_GIF) && function_exists('imagecreatefromgif')) {
					try {
						$this->image = imagecreatefromgif($filename);
					} catch (Exception $e) {
						logger::warn("GD-IMAGE", "  GD threw an error when handling",$filename);
						$image_type = false;
					}
				} else {
					$image_type = false;
				}
				break;

			case IMAGETYPE_PNG:
				logger::core("GD-IMAGE", "Image type is PNG");
				if (defined('IMG_PNG') && ($imgtypes && IMG_PNG) && function_exists('imagecreatefrompng') && function_exists('imagepng')) {
					try {
						$this->image = imagecreatefrompng($filename);
					} catch (Exception $e) {
						logger::warn("GD-IMAGE", "  GD threw an error when handling",$filename);
						$image_type = false;
					}
				} else {
					$image_type = false;
				}
				break;

			case IMAGETYPE_WBMP:
				logger::core("GD-IMAGE", "Image type is WBMP");
				if (defined('IMG_WBMP') && ($imgtypes && IMG_WBMP) && function_exists('imagecreatefromwbmp')) {
					try {
						$this->image = imagecreatefromwbmp($filename);
					} catch (Exception $e) {
						logger::warn("GD-IMAGE", "  GD threw an error when handling",$filename);
						$image_type = false;
					}
				} else {
					$image_type = false;
				}
				break;

			case IMAGETYPE_XBM:
				logger::core("GD-IMAGE", "Image type is XBM");
				if (defined('IMG_XPM') && ($imgtypes && IMG_XPM) && function_exists('imagecreatefromxbm')) {
					try {
						$this->image = imagecreatefromxbm($filename);
					} catch (Exception $e) {
						logger::warn("GD-IMAGE", "  GD threw an error when handling",$filename);
						$image_type = false;
					}
				} else {
					$image_type = false;
				}
				break;

			case IMAGETYPE_WEBP:
				logger::core("GD-IMAGE", "Image type is WEBP");
				if (defined('IMG_WEBP') && ($imgtypes && IMG_WEBP) && function_exists('imagecreatefromwebp')) {
					try {
						$this->image = imagecreatefromwebp($filename);
					} catch (Exception $e) {
						logger::log("GD-IMAGE", "  GD threw an error when handling",$filename);
						$image_type = false;
					}
				} else {
					$image_type = false;
				}
				break;

			case IMAGETYPE_BMP:
				logger::core("GD-IMAGE", "Image type is BMP");
				if (defined('IMG_BMP') && ($imgtypes && IMG_BMP) && function_exists('imagecreatefrombmp')) {
					try {
						$this->image = imagecreatefrombmp($filename);
					} catch (Exception $e) {
						logger::log("GD-IMAGE", "  GD threw an error when handling",$filename);
						$image_type = false;
					}
				} else {
					$image_type = false;
				}
				break;

			default:
				$image_type = false;
				$this->image_type = false;
				break;
		}

		if ($image_type !== false) {
			$this->image_type = $image_type;
			$this->reset();
		}

	}

	public function checkImage() {
		if ($this->image_type === false) {
			logger::warn("GD-IMAGE", "  Unsupported Image Type");
		}
		return $this->image_type;
	}

	public function reset() {
		$this->resizedimage = $this->image;
		imagealphablending($this->resizedimage, false);
		imagesavealpha($this->resizedimage, true);
	}

	public function save($filename, $compression) {
		// If $filename is null this outputs to STDOUT
		if ($this->image_type == IMAGETYPE_PNG) {
			// Be aware - We always save PNGs as PNGs to preserve alpha channel
			imagepng($this->resizedimage, $filename, 9);
		} else {
			imagejpeg($this->resizedimage, $filename, $compression);
		}
	}

	public function outputResizedFile($size, $filename = null) {
		if ($this->image_type == IMAGETYPE_PNG) {
			logger::core("GD-IMAGE", "  Outputting PNG file of size",$size);
			header('Content-type: image/png');
		} else {
			logger::core("GD-IMAGE", "  Outputting JPEG file of size",$size);
			header('Content-type: image/jpeg');
		}
		switch ($size) {
			case 'small':
				$this->resizeToWidth(IMAGESIZE_SMALL);
				$this->save($filename, IMAGEQUALITY_SMALL);
				break;

			case 'smallish':
				$this->resizeToWidth(IMAGESIZE_SMALLISH);
				$this->save($filename, IMAGEQUALITY_SMALLISH);
				break;

			case 'medium':
				$this->resizeToWidth(IMAGESIZE_MEDIUM);
				$this->save($filename, IMAGEQUALITY_MEDIUM);
				break;

			default:
				$this->save($filename, IMAGEQUALITY_ASDOWNLOADED);
				break;
		}
		return true;
	}

	public function resizeToWidth($width) {
		$ratio = $width / $this->getWidth();
		if ($ratio > 1) {
			logger::debug('GD-IMAGE', "Not resizing as requested size is larger than image. Asked for",$width,"but image is",$this->getWidth());
			$this->reset();
		} else {
			$height = $this->getheight() * $ratio;
			$this->resize(floor($width), floor($height));
		}
	}

	private function getWidth() {
		return imagesx($this->image);
	}

	private function getHeight() {
		return imagesy($this->image);
	}

	private function resize($width, $height) {
		logger::debug('GD-IMAGE', 'Resizing to',$width,'x',$height);
		$this->resizedimage = imagecreatetruecolor($width, $height);
		imagealphablending($this->resizedimage, false);
		imagesavealpha($this->resizedimage, true);
		imagecopyresampled($this->resizedimage, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
	}

	public function get_image_dimensions() {
		return array('width' => $this->getWidth(), 'height' => $this->getHeight());
	}

	public function destroy() {
		imagedestroy($this->image);
	}

}
?>