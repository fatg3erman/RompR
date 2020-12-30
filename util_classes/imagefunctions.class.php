<?php

class imageFunctions {

	public static function munge_filepath($p) {
		$p = rawurldecode(html_entity_decode($p));
		$f = "file://".prefs::$prefs['music_directory_albumart'];
		if (substr($p, 0, strlen($f)) == $f) {
			$p = substr($p, strlen($f), strlen($p));
		}
		return "prefs/MusicFolders/".$p;
	}

	public static function create_image_from_base64($base64, $download_file) {
		$image = explode('base64,',$base64);
		file_put_contents($download_file, base64_decode($image[1]));
	}

	public static function artist_for_image($type, $artist) {
		return ($type == 'stream') ? 'STREAM' : $artist;
	}

	public static function scan_for_local_images($albumpath) {
		logger::log("LOCAL IMAGE SCAN", "Album Path Is ".$albumpath);
		logger::log("LOCAL IMAGE SCAN", getcwd());
		$result = array();
		if ((is_dir("prefs/MusicFolders") || is_link('prefs/MusicFolders')) && $albumpath != ".") {
			$albumpath = self::munge_filepath($albumpath);
			$result = array_merge($result, self::get_images($albumpath));
			// Is the album dir part of a multi-disc set?
			if (preg_match('/^CD\s*\d+$|^disc\s*\d+$/i', basename($albumpath))) {
				$albumpath = dirname($albumpath);
				$result = array_merge($result, self::get_images($albumpath));
			}
			// Are there any subdirectories?
			$globpath = preg_replace('/(\*|\?|\[)/', '[$1]', $albumpath);
			$lookfor = glob($globpath."/*", GLOB_ONLYDIR);
			foreach ($lookfor as $i => $f) {
				if (is_dir($f)) {
					$result = array_merge($result, self::get_images($f));
				}
			}
		} else {
			logger::log('LOCAL IMAGE SCAN', 'Nope');
		}
		return $result;
	}

	private static function get_images($dir_path) {

		$funkychicken = array();
		$a = basename($dir_path);
		logger::trace("GET_IMAGES", "    Scanning :",$dir_path);
		$globpath = preg_replace('/(\*|\?|\[)/', '[$1]', $dir_path);
		logger::debug("GET_IMAGES", "      Glob Path is",$globpath);
		$funkychicken = glob($globpath."/*.{jpg,png,bmp,gif,jpeg,JPEG,JPG,BMP,GIF,PNG}", GLOB_BRACE);
		logger::trace("GET_IMAGES", "    Checking for embedded images");
		$files = glob($globpath."/*.{mp3,MP3,mp4,MP4,flac,FLAC,ogg,OGG}", GLOB_BRACE);
		$testfile = array_shift($files);
		if ($testfile) {
			$getID3 = new getID3;
			$tags = $getID3->analyze($testfile);
			getid3_lib::CopyTagsToComments($tags);
			if (array_key_exists('comments', $tags) && array_key_exists('picture', $tags['comments'])) {
				foreach ($tags['comments']['picture'] as $picture) {
					if (array_key_exists('picturetype', $picture)) {
						if ($picture['picturetype'] == 'Cover (front)') {
							logger::log("GET_IMAGES", "    .. found embedded front cover image");
							$filename = 'prefs/temp/'.md5($globpath);
							file_put_contents($filename, $picture['data']);
							array_unshift($funkychicken, $filename);
						}
					}
				}
			}
		}
		return $funkychicken;
	}
}

?>
