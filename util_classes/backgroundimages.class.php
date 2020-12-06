<?php

class backgroundImages {

	public static function first_upgrade_of_user_backgrounds() {
		$folders = glob('prefs/userbackgrounds/*');
		foreach ($folders as $folder) {
			self::analyze_background_folder($folder, false);
		}
	}

	private static function analyze_background_folder($folder, $is_browser) {
		logger::log("USERBACKGROUNDS", "Analysing",$folder);
		if ($is_browser) {
			$browserid = basename($folder);
			$skin = basename(dirname($folder));
			sql_prepare_query(true, null, null, null, 'DELETE FROM BackgroundImageTable WHERE Skin = ? AND BrowserID = ?', $skin, $browserid);
		} else {
			$skin = basename($folder);
			$browserid = null;
			sql_prepare_query(true, null, null, null, 'DELETE FROM BackgroundImageTable WHERE Skin = ? AND BrowserID IS NULL', $skin);
		}
		$files = glob($folder.'/*');
		foreach ($files as $file) {
			if (is_dir($file)) {
				self::analyze_background_folder($file, true);
			} else {
				$orientation = self::analyze_background_image($file);
				sql_prepare_query(true, null, null, null, 'INSERT INTO BackgroundImageTable (Skin, BrowserID, Filename, Orientation) VALUES (?, ?, ?, ?)', $skin, $browserid, $file, $orientation);
			}
		}
	}

	public static function analyze_background_image($image) {
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
}
?>