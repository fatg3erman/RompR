<?php

include ("getid3/getid3.php");

class lyrics {

	const BASE_URL = "https://lrclib.net/api/get?";

	private static function request($url) {
		$opts = [
			'url' => $url,
			'cache' => 'lyrics',
			'return_value' => true
		];
		$cache = new cache_handler($opts);

		return [$cache->get_cache_data(1), $cache->from_cache, $cache->get_status(), $cache->get_content_type()];
	}

	public static function get_lyrics($params, $pd) {

		# Always try to get synced lyrics and always prefer local lyrics over LRCLib

		$file = $params['file'];
		unset($params['file']);
		$prefer_synced = prefs::get_pref('prefer_synced_lyrics');

		$retval = [];
		$r = self::try_local($file);
		if (count($r) > 0) {
			$retval = $r;
		}
		if ($prefer_synced && array_key_exists('syncedLyrics', $retval)) {
			logger::log("LYRICS", "Returning synced lyrics from local file");
			self::finish($retval, true);
		}
		if (!$prefer_synced && array_key_exists('plainLyrics', $retval)) {
			logger::log("LYRICS", "Returning plain lyrics from local file");
			self::finish($retval, true);
		}

		$url = self::BASE_URL . http_build_query($params);
		$retries = 5;
		while ($retries > 0) {
			list($result, $c, $status, $content_type) = self::request($url);
			if ($status == 429 || $status == 503) {
				logger::warn("LYRICS", "Rate Limit Exceeded or server too busy");
				sleep(2);
			} else {
				$retries = 0;
			}
		}

		if ($status == 200 || $c) {
			$lyrics = json_decode($result, true);
		if (array_key_exists('syncedLyrics', $lyrics) && $lyrics['syncedLyrics'] != null && !array_key_exists('syncedLyrics', $retval)) {
				logger::log("LYRICS", "Returning synced lyrics from LRCLib");
				$retval['syncedLyrics'] = self::parse_synced($lyrics['syncedLyrics']);
			}
			if (array_key_exists('plainLyrics', $lyrics) && $lyrics['plainLyrics'] != null && !array_key_exists('plainLyrics', $retval)) {
				logger::log("LYRICS", "Returning plain lyrics from LRCLib");
				$retval['plainLyrics'] = $lyrics['plainLyrics'];
			}
		} else {
			if ($content_type == 'application/json') {
				$lyrics = json_decode($result, true);
				if (array_key_exists('error', $lyrics)) {
					$retval = ['error' => $lyrics['error']];
				}
			} else {
				$retval = ['error' => $result];
			}
		}
		if (count($retval) == 0) {
			$retval = ['error' => ""];
		}
		self::finish($retval, $c);
	}

	private static function finish($retval, $c) {
		if ($c) {
			header("Pragma: From Cache");
		} else {
			header("Pragma: Not Cached");
		}
		header('Content-Type: application/json');
		print json_encode($retval);
		exit(0);
	}

	private static function parse_synced($lyrics) {
		$la = explode("\n", $lyrics);
		$nl = [];
		foreach ($la as $l) {
			# [02:48.12] A dream of sweet illusion
			if (preg_match('/\[(\d+):(\d+\.\d+)\]\s*(.+)*$/', $l, $matches)) {
				$mins = $matches[1];
				$secs = $matches[2];
				$line = (count($matches) > 3) ? $matches[3] : "";
				$nl[] = [
					'time' => ($mins*60) + $secs,
					'line' => $line
				];
			} else {
				logger::warn("LYRICS", "Could not parse", $l);
			}
		}
		return $nl;
	}

	private static function try_local($file) {
		$retval = [];
		$fname = rawurldecode($file);
		$fname = preg_replace('/local:track:/','',$fname);
		$fname = preg_replace('#file://#','',$fname);
		$fname = 'prefs/MusicFolders/'.$fname;
		logger::mark("LYRICS", "Looking for lyrics in",$fname);
		$result = self::read_tags($fname);
		if ($result != null) {
			if (preg_match('/\[(\d+):(\d+\.\d+)\]/', $result)) {
				$retval = ['syncedLyrics' => self::parse_synced($result), 'instrumental' => false];
			} else {
				$retval = ['plainLyrics' => $result, 'instrumental' => false];
			}
		}
		return $retval;
	}

	private static function read_tags($fname) {
		$retval = null;
		if (file_exists($fname)) {
			logger::log("LYRICS", "    File Exists");
			$getID3 = new getID3;
			$tags = $getID3->analyze($fname);
			getid3_lib::CopyTagsToComments($tags);
			if (array_key_exists('comments', $tags) &&
					array_key_exists('lyrics', $tags['comments'])) {
				$retval = $tags['comments']['lyrics'][0];
			} else if (array_key_exists('comments', $tags) &&
						array_key_exists('unsynchronised_lyric', $tags['comments'])) {
				$retval = $tags['comments']['unsynchronised_lyric'][0];
			} else if (array_key_exists('quicktime', $tags) &&
						array_key_exists('moov', $tags['quicktime']) &&
						array_key_exists('subatoms', $tags['quicktime']['moov'])) {
				$retval = self::read_apple_awfulness($tags['quicktime']['moov']['subatoms']);
			}
		}
		return $retval;

	}

	private static function read_apple_awfulness($a) {
		// Whoever came up with this was on something.
		// All we want to do is read some metadata...
		// why do you have to store it in such a horrible, horrible, way?
		foreach ($a as $atom) {
			if (array_key_exists('name', $atom)) {
				if (preg_match('/lyr$/', $atom['name'])) {
					return preg_replace( '/^.*?data/', '', $atom['data']);
				}
			}
			if (array_key_exists('subatoms', $atom)) {
				read_apple_awfulness($atom['subatoms']);
			}
		}
		return null;
	}

}

?>