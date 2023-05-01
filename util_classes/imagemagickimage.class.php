<?php
class imageMagickImage {

	// imageMagickImage will be used when:
	// GD is installed but does not support the format of the image (GD never supports SVG or ICO)
	// OR GD is not installed

	// If Imagemagick is not installed either, then it defaults to just copying files which, for outputResizedImage,
	// will only work for PNG and JPEG images, and no resizing will occur

	private $filename;
	private $convert_path;
	private $resize_to = 0;
	private $image_type;
	private $cmdline_file;

	public function __construct($filename) {
		$this->filename = $filename;
		$this->convert_path = find_executable('convert');
		$this->image_type = mime_content_type($this->filename);
		logger::debug('IMAGEMAGICK', 'Construct Image Type is "'.$this->image_type.'"');
		if ($this->image_type == 'text/plain') {
			$this->image_type = 'image-svg+xml';
		}
		if ($this->image_type == 'image/svg') {
			// MUST use image/svg+xml for MIME type for output, but some versions of PHP
			// mime_content_type return image/svg
			$this->image_type = 'image-svg+xml';
		}
		if ($this->image_type == 'image/x-icon') {
			// Imagemagick can't auto-detect .ico files that don't have the .ico extension
			// and we don't use file extensions in the cache. This forces IM to decode it as a .ico
			$this->cmdline_file = 'ico:'.$filename;
		} else {
			$this->cmdline_file = $filename;
		}
	}

	public function reset() {
		$this->resize_to = 0;
	}

	public function checkImage() {
		logger::debug("IMAGEMAGICK", "  Check Image type is ".$this->image_type);
		$c = $this->convert_path."identify \"".$this->cmdline_file."\" 2>&1";
		$o = array();
		$r = exec($c, $o);
		if (preg_match('/no decode delegate/', $r)) {
			logger::warn('IMAGEMAGICK', 'Unsupported image');
			return false;
		}
		return $this->image_type;
	}

	public function save($filename, $compression) {
		if ($this->image_type == 'image/svg+xml') {
			logger::debug("IMAGEMAGICK", "  Copying SVG file instead of converting");
			$this->justCopy($filename);
		} else if ($this->convert_path === false) {
			logger::warn("IMAGEMAGICK", "WARNING! ImageMagick not installed");
			$this->justCopy($filename);
		} else {
			if ($this->image_type == "image/png") {
				$params = ' -quality 95';
			} else {
				$params = ' -quality '.$compression.' -alpha remove';
			}
			if ($this->resize_to > 0) {
				$params .= ' -resize '.$this->resize_to;
			}
			$cmd = 'convert "'.$this->cmdline_file.'"'.$params.' "'.$filename.'"';
			logger::debug("IMAGEMAGICK", "  Command is ".$cmd);
			if (substr($filename, -1) == '-') {
				// Output is to STDOUT
				passthru($this->convert_path.$cmd, $ret);
			} else {
				$cmd .= ' 2>&1';
				$r = exec($this->convert_path.$cmd, $o, $ret);
				logger::debug("IMAGEMAGICK", "    Final line of output was ".$r);
			}
			logger::debug("IMAGEMAGICK", "    Return Value was ".$ret);
			// No point trying a copy file fallback, as if ImageMagick can't handle it it's shite.
		}
	}

	private function justCopy($filename) {
		if (substr($filename, -1) == '-') {
			readfile($this->filename);
		} else {
			copy($this->filename, $filename);
		}
	}

	public function outputResizedFile($size) {
		// Set content type and output to STDOUT
		switch ($this->image_type) {
			case 'image/svg+xml':
				$content_type = 'image/svg+xml';
				// NOTE SVG is not a valid type for ImageMagick output, this is just a placeholder
				// with a '-' on the end to denote output to STDOUT. We always just copy SVG files.
				$outputfile = 'SVG:-';
				break;
			case 'image/png':
				$content_type = 'image/png';
				$outputfile = 'PNG:-';
				break;
			default:
				$content_type = 'image/jpeg';
				$outputfile = 'JPEG:-';
				break;
		}
		logger::debug('IMAGEMAGICK', 'Outputting',$this->filename,'as',$content_type,'size',$size);
		header('Content-type: '.$content_type);
		switch ($size) {
			case 'small':
				$this->resizeToWidth(IMAGESIZE_SMALL);
				return $this->save($outputfile, IMAGEQUALITY_SMALL);
				break;

			case 'smallish':
				$this->resizeToWidth(IMAGESIZE_SMALLISH);
				return $this->save($outputfile, IMAGEQUALITY_SMALLISH);
				break;

			case 'medium':
				$this->resizeToWidth(IMAGESIZE_MEDIUM);
				return $this->save($outputfile, IMAGEQUALITY_MEDIUM);
				break;

			default:
				return $this->save($outputfile, IMAGEQUALITY_ASDOWNLOADED);
				break;

		}
	}

	public function resizeToWidth($width) {
		$s = $this->get_image_dimensions();
		if ($width > $s['width']) {
			logger::debug('IMAGEMAGICK', 'Not resizing as requested size is larger than image');
		} else {
			$this->resize_to = $width;
		}
	}

	public function get_image_dimensions() {
		$width = -1;
		$height = -1;
		if ($this->image_type != 'image/svg+xml' && $this->convert_path !== false) {
			$c = $this->convert_path."identify \"".$this->cmdline_file."\" 2>&1";
			$o = array();
			$r = exec($c, $o);
			if (preg_match('/ (\d+)x(\d+) /', $r, $matches)) {
				$width = $matches[1];
				$height = $matches[2];
			}
		}
		logger::debug('IMAGEMAGICK', 'Dimensions are',$width,'x',$height);
		return array('width' => $width, 'height' => $height);
	}

	public function destroy() {

	}

}
?>