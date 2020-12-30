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
		foreach (array('artist', 'album', 'key', 'source', 'file', 'base64data', 'mbid', 'albumpath', 'albumuri', 'trackuri') as $param) {
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
		if ($this->mbid !== null) {
			if (preg_match('/\d+/', $this->mbid) && !preg_match('/-/', $this->mbid)) {
				logger::debug("ALBUMIMAGE", " Supplied MBID of ".$this->mbid." looks more like a Discogs ID");
				$this->mbid = null;
			}
		}
		if (prefs::$prefs['player_backend'] == 'mopidy') {
			$this->albumpath = urldecode($this->albumpath);
		}
		$this->image_downloaded = false;
		if ($this->baseimage != 'No-Image') {
			$this->images = $this->image_paths_from_base_image($params['baseimage']);
			$this->key = $this->make_image_key();
		} else if ($this->key !== null) {
			logger::log('ALBUMIMAGE', 'Image Infor From Database');
			$this->image_info_from_database();
		} else {
			$this->images = $this->image_info_from_album_info();
		}
	}

	public function get_image_key() {
		return $this->key;
	}

	private function image_exists($image) {
		logger::trace("ALBUMIMAGE", "Checking for existence of file ".$image);
		return file_exists($image);
	}

	public function get_image_if_exists($size = 'small') {
		foreach (array('png', 'svg', 'jpg') as $ext) {
			$this->change_file_extension($ext);
			if ($this->image_exists($this->images[$size])) {
				return $this->images[$size];
			}
		}
		if ($this->artist == 'PLAYLIST' && $this->album == 'Discover Weekly (by spotify)') {
			$this->images['small'] = 'newimages/discoverweekly.jpg';
			$this->images['medium'] = 'newimages/discoverweekly.jpg';
			$this->images['asdownloaded'] = 'newimages/discoverweekly.jpg';
			return 'newimages/discoverweekly.jpg';
		}
		return null;
	}

	public function get_images() {
		return $this->images;
	}

	private function check_if_image_already_downloaded() {
		logger::log('ALBUMIMAGE', 'Checking if image exists for', $this->artist, $this->album);
		$checkimages = $this->image_info_from_album_info();
		if ($this->image_exists($checkimages['small'])) {
			logger::log("ALBUMIMAGE", "  ..  File exists");
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
		// If there's no image, see if we can set a default
		// Note we don't set defaults for streams because coverscaper handles those
		// so it can set them in the playlist even when auto art download is off

		$disc_checked = false;
		if ($this->images['small'] == '' || $this->images['small'] === null) {
			if ($this->artist == 'STREAM') {
				// Stream images may not be in the database
				// BUT they may be present anyway, if a stream was added eg from a playlist
				// of streams and coverscraper found one. So check, otherwise coverscraper
				// will search for it every time the playlist repopulates.
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
						case 'internetarchive':
						case 'oe1':
							$this->images = $this->image_paths_from_base_image('newimages/'.$domain.'-logo.svg');
							break;
					}
				}
				return true;
			}

			if (prefs::$database->get_option('doing_search')) {
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
				// case 'soundcloud':
				case 'tunein':
				// case 'youtube':
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
				$v .= '&rompr_resize_size='.$k;
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
		$info = prefs::$database->get_imagesearch_info($this->key);
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

	public function album_has_no_image() {
		// return true if the image does not exist
		if (substr($this->images['small'], 0, 4) == 'http' || substr($this->images['small'], 0, 14) == 'getRemoteImage') {
			return false;
		}
		if (!$this->images['small']) {
			return true;
		}
		if (!file_exists($this->images['small'])) {
			return true;
		}
		return false;
	}

	public function html_for_image($obj, $imageclass, $size, $lazy = true, $check_exists = false) {
		$extra = (array_key_exists('userplaylist', $obj)) ? 'plimage '.$imageclass : $imageclass;
		if ($check_exists && $this->album_has_no_image()) {
			$this->images['small'] = null;
			$this->images['medium'] = null;
			$this->images['asdownloaded'] = null;
		}
		if (!$this->images['small'] && $obj['Searched'] != 1) {
			return '<img class="notexist '.$extra.'" name="'.$obj['ImgKey'].'" src="newimages/transparent.png" />'."\n";
		} else  if (!$this->images['small'] && $obj['Searched'] == 1) {
			return '<img class="notfound '.$extra.'" name="'.$obj['ImgKey'].'" src="newimages/transparent.png"/>'."\n";
		} else {
			if ($lazy) {
				return '<img class="lazy '.$extra.'" name="'.$obj['ImgKey'].'" src="newimages/transparent.png" data-src="'.$this->images[$size].'" />'."\n";
			} else {
				return '<img class="'.$extra.'" name="'.$obj['ImgKey'].'" src="'.$this->images[$size].'" />'."\n";
			}
		}
	}

}
?>