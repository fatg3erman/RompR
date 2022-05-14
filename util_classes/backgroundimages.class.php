<?php

class backgroundImages extends database {

	public function get_next_background($theme, $browser_id, $random) {
		// We know we're only going to be in here if the browser knows we have images to use
		// First see if we have thisbrowseronly images
		$thisbo = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, 'thisbo', 0,
			"SELECT COUNT(BgImageIndex) AS thisbo FROM BackgroundImageTable WHERE Skin = ? AND BrowserID = ?",
			$theme,
			$browser_id
		);
		foreach ([ORIENTATION_PORTRAIT, ORIENTATION_LANDSCAPE] AS $o) {
			if ($thisbo > 0) {
				$num = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, 'num', 0,
					"SELECT COUNT(BgImageIndex) AS num FROM BackgroundImageTable WHERE Skin = ? AND BrowserID = ? AND Orientation = ? AND Used = ?",
					$theme,
					$browser_id,
					$o,
					0
				);
				if ($num == 0) {
					logger::log('BACKIMAGE', "Resetting Used flag for",$theme,$browser_id,$o);
					$this->sql_prepare_query(true, null, null, null,
						"UPDATE BackgroundImageTable SET Used = 0 WHERE Skin = ? AND BrowserID = ? AND Orientation = ?",
						$theme,
						$browser_id,
						$o
					);
				}
			} else {
				$num = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, 'num', 0,
					"SELECT COUNT(BgImageIndex) AS num FROM BackgroundImageTable WHERE Skin = ? AND BrowserID IS NULL AND Orientation = ? AND Used = ?",
					$theme,
					$o,
					0
				);
				if ($num == 0) {
					logger::log('BACKIMAGE', "Resetting Used flag for",$theme,$o);
					$this->sql_prepare_query(true, null, null, null,
						"UPDATE BackgroundImageTable SET Used = 0 WHERE Skin = ? AND BrowserID IS NULL AND Orientation = ?",
						$theme,
						$o
					);
				}
			}
		}

		$landscape_image = $this->get_image($theme, $browser_id, $random, ORIENTATION_LANDSCAPE, $thisbo);
		$portrait_image = $this->get_image($theme, $browser_id, $random, ORIENTATION_PORTRAIT, $thisbo);

		if ($landscape_image == null)
			$landscape_image = $portrait_image;

		if ($portrait_image == null)
			$portrait_image = $landscape_image;

		logger::trace('BACKIMAGE','Portrait  Is',$portrait_image);
		logger::trace('BACKIMAGE','Landscape Is',$landscape_image);

		foreach ([$landscape_image, $portrait_image] AS $i) {
			if ($thisbo > 0) {
				$this->sql_prepare_query(true, null, null, null,
					"UPDATE BackgroundImageTable SET Used = 1 WHERE Skin = ? AND BrowserID = ? AND Filename = ?",
					$theme,
					$browser_id,
					$i
				);
			} else {
				$this->sql_prepare_query(true, null, null, null,
					"UPDATE BackgroundImageTable SET Used = 1 WHERE Skin = ? AND BrowserID IS NULL AND Filename = ?",
					$theme,
					$i
				);
			}
		}

		return ['landscape' => $landscape_image, 'portrait' => $portrait_image];

	}

	private function get_image($theme, $browser_id, $random, $orientation, $thisbo) {
		if ($random == 'true') {
			$sort = database::SQL_RANDOM_SORT;
		} else {
			$sort = 'Filename ASC';
		}
		if ($thisbo > 0) {
			return $this->sql_prepare_query(false, PDO::FETCH_ASSOC, 'Filename', null,
				"SELECT Filename FROM BackgroundImageTable WHERE Skin = ? AND BrowserID = ? AND Orientation = ? AND Used = 0 ORDER BY $sort LIMIT 1",
				$theme,
				$browser_id,
				$orientation
			);
		} else {
			return $this->sql_prepare_query(false, PDO::FETCH_ASSOC, 'Filename', null,
				"SELECT Filename FROM BackgroundImageTable WHERE Skin = ? AND BrowserID IS NULL AND Orientation = ? AND Used = 0 ORDER BY $sort LIMIT 1",
				$theme,
				$orientation
			);
		}
	}

	public function get_background_images($theme, $browser_id) {
		$retval = array();
		$images = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, null, 'SELECT * FROM BackgroundImageTable WHERE Skin = ? AND BrowserID = ? ORDER BY Filename ASC', $theme, $browser_id);
		$thisbrowseronly = true;
		if (count($images) == 0) {
			logger::log("BACKIMAGE", "No Custom Backgrounds Exist for",$theme,$browser_id);
			$images = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, null, 'SELECT * FROM BackgroundImageTable WHERE Skin = ? AND BrowserID IS NULL ORDER BY Filename ASC', $theme);
			$thisbrowseronly = false;
		} else {
			logger::log("BACKIMAGE", "Custom Backgrounds Exist for",$theme,$browser_id);
		}
		if (count($images) > 0) {
			logger::log("BACKIMAGE", "Custom Backgrounds Exist for",$theme);
			$retval = array('images' => array('portrait' => array(), 'landscape' => array()), 'thisbrowseronly' => $thisbrowseronly);
			foreach ($images as $image) {
				if ($image['Orientation'] == ORIENTATION_PORTRAIT) {
					$retval['images']['portrait'][] = $image['Filename'];
				} else {
					$retval['images']['landscape'][] = $image['Filename'];
				}
			}
		}
		return $retval;
	}

	public function clear_background($file) {
		$this->sql_prepare_query(true, null, null, null, 'DELETE FROM BackgroundImageTable WHERE Filename = ?', $file);
		unlink($file);

		// This removes the thumbnails created by the background image manager via imagehandler.php
		// This is a bit of a hack, we could probably use imagehandler to remove them
		// but that's a bit of extra work.
		$directory = dirname($file).'/thumbs';
		$thumbfile = $directory.'/'.pathinfo($file, PATHINFO_FILENAME).'.smallish.'.pathinfo($file, PATHINFO_EXTENSION);
		if (file_exists($thumbfile)) {
			unlink($thumbfile);
			$this->check_empty_directory($directory);
		}

		if (is_numeric(basename(dirname($file)))) {
			$this->check_empty_directory(dirname($file));
		}
	}

	public function clear_all_backgrounds($theme, $browser_id) {
		// Remove these here, just in case the folder has been deleted for some reason
		$this->sql_prepare_query(true, null, null, null, 'DELETE FROM BackgroundImageTable WHERE Skin = ? AND BrowserID = ?', $theme, $browser_id);
		$browser_dir = 'prefs/userbackgrounds/'.$theme.'/'.$browser_id;
		$global_dir = 'prefs/userbackgrounds/'.$theme;
		if (is_dir($browser_dir)) {
			logger::log("BACKIMAGE", "Removing All Backgrounds For",$browser_dir);
			$this->delete_files($browser_dir);
			if (is_dir($browser_dir.'/thumbs')) {
				$this->delete_files($browser_dir.'/thumbs');
				$this->check_empty_directory($browser_dir.'/thumbs');
			}
			$this->check_empty_directory($browser_dir);
		} else if (is_dir($global_dir)) {
			logger::log("BACKIMAGE", "Removing All non browser-specific Backgrounds For",$global_dir);
			$this->delete_files($global_dir);
			if (is_dir($global_dir.'/thumbs')) {
				$this->delete_files($global_dir.'/thumbs');
				$this->check_empty_directory($global_dir.'/thumbs');
			}
			$this->sql_prepare_query(true, null, null, null, 'DELETE FROM BackgroundImageTable WHERE Skin = ? AND BrowserID IS NULL', $theme);
		}
	}

	public function switch_backgrounds($theme, $browser_id, $thisbrowseronly) {
		// $thisbrowseronly is the NEW state
		if ($thisbrowseronly == 1) {
			$source_path = 'prefs/userbackgrounds/'.$theme;
			$dest_path = 'prefs/userbackgrounds/'.$theme.'/'.$browser_id;
		} else {
			$source_path = 'prefs/userbackgrounds/'.$theme.'/'.$browser_id;
			$dest_path = 'prefs/userbackgrounds/'.$theme;
		}

		if (!is_dir($dest_path))
			mkdir($dest_path);

		logger::log('BACKIMAGE', 'Moving from',$source_path,'to',$dest_path);

		$source_thumbs = $source_path.'/thumbs';
		$dest_thumbs = $dest_path.'/thumbs';
		$newbrowserid = ($thisbrowseronly == 1) ? $browser_id : null;

		$all_files = glob($source_path.'/*.*');
		foreach ($all_files as $file) {
			$dest_file = $dest_path.'/'.basename($file);
			if (file_exists($dest_file)) {
				logger::log('BACKIMAGE', $dest_file,'already exists');
				$this->sql_prepare_query(true, null, null, null,
					"DELETE FROM BackgroundImageTable WHERE Filename = ?",
					$file
				);
				unlink($file);
			} else {
				rename($file, $dest_file);
				$this->sql_prepare_query(true, null, null, null,
					"UPDATE BackgroundImageTable SET Filename = ?, BrowserID = ? WHERE Filename = ?",
					$dest_file,
					$newbrowserid,
					$file
				);
			}
		}

		if (is_dir($source_thumbs)) {
			if (!is_dir($dest_thumbs))
				mkdir($dest_thumbs);

			$all_thumbs = glob($source_thumbs.'/*.*');
			foreach ($all_thumbs as $thumb) {
				$dest_thumb = $dest_thumbs.'/'.basename($thumb);
				if (file_exists($dest_thumb)) {
					unlink($thumb);
				} else {
					rename($thumb, $dest_thumb);
				}
			}
			$this->check_empty_directory($source_thumbs);
		}

		$this->check_empty_directory($source_path);

	}

	public function upload_backgrounds($theme) {
		$base = $theme;
		$browserid = null;
		if (array_key_exists('thisbrowseronly', $_REQUEST)) {
			$base .= '/'.$_REQUEST['browser_id'];
			$browserid = $_REQUEST['browser_id'];
		}

		$files = $this->make_files_useful($_FILES['imagefile']);
		foreach ($files as $filedata) {
			$file = $filedata['name'];
			logger::log("BACKIMAGE", "Uploading File ".$file);
			$fname = $this->format_for_url(format_for_disc(basename($file)));
			$download_file = get_user_file($file, $fname, $filedata['tmp_name']);
			if (!is_dir('prefs/userbackgrounds/'.$base)) {
				mkdir('prefs/userbackgrounds/'.$base, 0755, true);
			}
			$file = 'prefs/userbackgrounds/'.$base.'/'.$fname;
			if (file_exists($file)) {
				logger::trace("BACKIMAGE", "Image",$file,"already exists");
				unlink($download_file);
			} else {
				rename($download_file, $file);
				$orientation = $this->analyze_background_image($file);
				$this->sql_prepare_query(true, null, null, null, 'INSERT INTO BackgroundImageTable (Skin, BrowserID, Filename, Orientation) VALUES (?, ?, ?, ?)', $theme, $browserid, $file, $orientation);
			}
		}
	}

	private function analyze_background_image($image) {
		$ih = new imageHandler($image);
		$size = $ih->get_image_dimensions();
		$retval = false;
		if ($size['width'] > $size['height']) {
			logger::log("BACKIMAGE", "  Landscape Image ".$image);
			$retval = ORIENTATION_LANDSCAPE;
		} else {
			logger::log("BACKIMAGE", "  Portrait Image ".$image);
			$retval = ORIENTATION_PORTRAIT;
		}
		$ih->destroy();
		return $retval;
	}

	private function check_empty_directory($dir) {
		if (is_dir($dir) && !(new FilesystemIterator($dir))->valid()) {
			rmdir($dir);
		}
	}

	private function delete_files($path, $expr = '*.*') {
		// Prevents file not found or could not stat errors
		$f = glob($path.'/'.$expr);
		foreach ($f as $file) {
			unlink($file);
		}
	}

	private function make_files_useful($arr) {
		$new = array();
		foreach ($arr as $key => $all) {
			foreach ($all as $i => $val) {
				$new[$i][$key] = $val;
			}
		}
		return $new;
	}

	private function format_for_url($filename) {
		return preg_replace('/#|&|\?|%|@|\+/', '_', $filename);
	}

}
?>