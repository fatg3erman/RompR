<?php

class backgroundImages extends database {

	public function get_background_images($theme, $browser_id) {
		$retval = array();
		$images = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, null, 'SELECT * FROM BackgroundImageTable WHERE Skin = ? AND BrowserID = ?', $theme, $browser_id);
		$thisbrowseronly = true;
		if (count($images) == 0) {
			logger::log("BACKIMAGE", "No Custom Backgrounds Exist for",$theme,$browser_id);
			$images = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, null, 'SELECT * FROM BackgroundImageTable WHERE Skin = ? AND BrowserID IS NULL', $theme);
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
		if (is_numeric(basename(dirname($file)))) {
			$this->check_empty_directory(dirname($file));
		}
	}

	public function clear_all_backgrounds($theme, $browser_id) {
		// Remove these here, just in case the folder has been deleted for some reason
		$this->sql_prepare_query(true, null, null, null, 'DELETE FROM BackgroundImageTable WHERE Skin = ? AND BrowserID = ?', $theme, $browser_id);
		if (is_dir('prefs/userbackgrounds/'.$theme.'/'.$browser_id)) {
			logger::log("BACKIMAGE", "Removing All Backgrounds For ".$theme.'/'.$browser_id);
			$this->delete_files('prefs/userbackgrounds/'.$theme.'/'.$browser_id);
			$this->check_empty_directory('prefs/userbackgrounds/'.$theme.'/'.$browser_id);
		} else if (is_dir('prefs/userbackgrounds/'.$theme)) {
			logger::log("BACKIMAGE", "Removing All Backgrounds For ".$theme);
			$this->delete_files('prefs/userbackgrounds/'.$theme);
			$this->sql_prepare_query(true, null, null, null, 'DELETE FROM BackgroundImageTable WHERE Skin = ? AND BrowserID IS NULL', $theme);
		}
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