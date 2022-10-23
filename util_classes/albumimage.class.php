<?php
class albumImage extends baseAlbumImage {

	public function set_source($src) {
		$this->source = trim($src);
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
					prefs::$database->update_stream_image($this->album, $this->images['small']);
				}
				break;

			case 'PODCAST':
				if ($this->image_downloaded) {
					prefs::$database->update_podcast_image($this->albumpath, $this->images['small']);
				}
				break;

			default:
				prefs::$database->update_image_db($this->key, $this->image_downloaded, $this->images['small']);
				break;

		}
	}

	public function check_archive_image_exists() {
		logger::log('ALBUMIMAGE', 'Checking for', $this->images['small']);
		if (file_exists($this->images['small'])) {
			logger::log('ALBUMIMAGE', 'Downloaded image already exists');
			$this->image_downloaded = true;
			return $this->images;
		}
		return false;
	}

	public function set_default() {
		if ($this->artist == "STREAM") {
			// Set a default image for streams when we are doing an album art download
			// otherwise they get searched for on every refresh of the playlist if they're
			// not in the database and nothing gets found the first time.
			$this->source = 'newimages/broadcast.svg';
			return $this->download_image();
		}
		return false;
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
				logger::log("FUCKINGHELL", "Playlist name changing from ".$this->album." to ".$new_name);
				if (file_exists($this->images['small'])) {
					$ext = pathinfo($this->images['small'], PATHINFO_EXTENSION);
					$oldbasepath = dirname($this->basepath);
					$oldkey = $this->key;
					$this->album = $new_name;
					$this->images = $this->image_info_from_album_info($ext);
					$newbasepath = dirname($this->basepath);
					logger::log("ALBUMIMAGE", "Renaming Playlist Image from ".$oldbasepath." to ".$newbasepath);
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
			// Include the MIME types here in case we're using ImageMagick after an error
			// was thrown in gd_Image
			case IMAGETYPE_PNG:
			case 'image/png':
				$this->change_file_extension('png');
				break;

			case IMAGETYPE_SVG:
			case 'image/svg':
			case 'image/svg+xml':
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
			logger::log("ALBUMIMAGE", "  Creating file ".$image);
			switch ($size) {
				case 'small':
					$imagehandler->resizeToWidth(IMAGESIZE_SMALL);
					$imagehandler->save($image, IMAGEQUALITY_SMALL);
					break;

				case 'smallish':
					$imagehandler->resizeToWidth(IMAGESIZE_SMALLISH);
					$imagehandler->save($image, IMAGEQUALITY_SMALLISH);
					break;

				case 'medium':
					$imagehandler->resizeToWidth(IMAGESIZE_MEDIUM);
					$imagehandler->save($image, IMAGEQUALITY_MEDIUM);
					break;

				case 'asdownloaded':
					$imagehandler->reset();
					$imagehandler->save($image, IMAGEQUALITY_ASDOWNLOADED);
					break;
			}
		}
		if (file_exists($download_file))
			unlink($download_file);
		$imagehandler->destroy();
		return $this->images;

	}

	private function download_remote_file() {
		$download_file = 'prefs/temp/'.$this->key;
		$retval = $download_file;
		if (preg_match('/^https*:/', trim($this->source)) || preg_match('/^getRemoteImage.php/', trim($this->source))) {
			$d = new url_downloader(array('url' => $this->source));
			if ($d->get_data_to_file($download_file, true)) {
				$content_type = $d->get_content_type();
				if (substr($content_type,0,5) != 'image' && $content_type != 'application/octet-stream') {
					logger::warn("ALBUMIMAGE", "  .. Content type is ".$content_type." - not an image file! ".$this->source);
					$retval = false;
				}
			} else {
				$retval = false;
			}
		} else {
			try {
				logger::log("ALBUMIMAGE", "  .. Copying apparent local file",$this->source,'to',$download_file);
				if (!copy(rawurldecode($this->source), $download_file)) {
					logger::warn("ALBUMIMAGE", "    .. File Copy Failed");
					$retval = false;
				}
			} catch (Exception $e) {
				logger::warn("ALBUMIMAGE", "    .. File Copy Failed Fatally with exception");
				$retval = false;
			}
		}
		return $retval;
	}

	private function save_base64_data() {
		logger::log("ALBUMIMAGE", "  Saving base64 data");
		$download_file = 'prefs/temp/'.$this->key;
		imageFunctions::create_image_from_base64($this->base64data, $download_file);
		return $download_file;
	}

}
?>