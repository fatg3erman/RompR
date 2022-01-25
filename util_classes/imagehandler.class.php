<?php
class imageHandler {

	private $filename;
	private $image;

	public function __construct($filename) {
		if (extension_loaded('gd')) {
			$this->image = new gd_Image($filename);
			if ($this->image->checkImage() === false) {
				// GD does not support SVG or ICO files, plus different installations
				// have different built-in support. Hence we fall back to
				// imagemagick if gd doesn't support it. (GD is faster, so we prefer that)
				logger::info('IMAGEHANDLER', 'Switching to ImageMagick');
				$this->image = new imageMagickImage($filename);
			}
		} else {
			$this->image = new imageMagickImage($filename);
		}
		$this->filename = $filename;
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
		return $this->image->outputResizedFile($size);
	}

	public function resizeToWidth($width) {
		logger::trace('IMAGEHANDLER', 'Resizing to width', $width);
		$this->image->resizeToWidth(floor($width));
	}

	public function get_image_dimensions() {
		return $this->image->get_image_dimensions();
	}

	public function destroy() {
		$this->image->destroy();
	}

	public function output_thumbnail($size) {
		$directory = dirname($this->filename).'/thumbs';
		if (!is_dir($directory))
			mkdir($directory);

		$thumbfile = $directory.'/'.pathinfo($this->filename, PATHINFO_FILENAME).
			'.'.$size.'.'.pathinfo($this->filename, PATHINFO_EXTENSION);

		if (!file_exists($thumbfile)) {
			logger::log('IMAGEHANDLER', 'Creating Thumbnail',$thumbfile);
			$this->image->outputResizedFile($size, $thumbfile);
		}

		readfile($thumbfile);
	}

}
?>