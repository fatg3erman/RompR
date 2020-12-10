<?php
function format_for_mpd($term) {
	$term = str_replace('"','\\"',$term);
	return trim($term);
}

function join_command_string($cmd) {
	$c = $cmd[0];
	for ($i = 1; $i < count($cmd); $i++) {
		$c .= ' "'.format_for_mpd($cmd[$i]).'"';
	}
	return $c;
}

function format_for_disc($filename) {
	$filename = str_replace("\\","_",$filename);
	$filename = str_replace("/","_",$filename);
	$filename = str_replace("'","_",$filename);
	$filename = str_replace('"',"_",$filename);
	return $filename;
}

function format_tracknum($tracknum) {
	$matches = array();
	if (preg_match('/^\s*0*(\d+)/', $tracknum, $matches)) {
		if (strlen($matches[1]) < 4) {
			return $matches[1];
		}
	}
	if (preg_match('/0*(\d+) of \d+/i', $tracknum, $matches)) {
		return $matches[1];
	}
	return 0;
}

function format_text($d) {
	$d = preg_replace('/(<a href=.*?)>/', '$1 target="_blank">', $d);
	$d = preg_replace('/(<a rel="nofollow" href=.*?)>/', '$1 target="_blank">', $d);
	$d = preg_replace('/style\s*=\s*\".*?\"/', '', $d);
	$d = preg_replace('/<p>\s*<\/p>/', '', $d);
	$d = preg_replace('/<p>&nbsp;<\/p>/', '', $d);
	$d = preg_replace('/\n|(\r\n)/', '<br/>', $d);
	$d = preg_replace('/(<br\s*\/*>)+/', '<br/>', $d);
	$d = preg_replace('/<\/p><br>/', '</p>', $d);
	return $d;
}

function format_time($t,$f=':') {
	if (($t/86400) >= 1) {
		return sprintf("%d%s%2d%s%02d%s%02d", ($t/86400), " ".language::gettext("label_days")." ",
			($t/3600)%24, $f, ($t/60)%60, $f, $t%60);
	}
	if (($t/3600) >= 1) {
		return sprintf("%2d%s%02d%s%02d", ($t/3600), $f, ($t/60)%60, $f, $t%60);
	} else {
		return sprintf("%02d%s%02d", ($t/60)%60, $f, $t%60);
	}
}

function munge_album_name($name) {
	$b = preg_replace('/(\(|\[)disc\s*\d+.*?(\)|\])/i', "", $name);     // (disc 1) or (disc 1 of 2) or (disc 1-2) etc (or with [ ])
	$b = preg_replace('/(\(|\[)*cd\s*\d+.*?(\)|\])*/i', "", $b);        // (cd 1) or (cd 1 of 2) etc (or with [ ])
	$b = preg_replace('/\sdisc\s*\d+.*?$/i', "", $b);                   //  disc 1 or disc 1 of 2 etc
	$b = preg_replace('/\scd\s*\d+.*?$/i', "", $b);                     //  cd 1 or cd 1 of 2 etc
	$b = preg_replace('/(\(|\[)\d+\s*of\s*\d+(\)|\])/i', "", $b);       // (1 of 2) or (1of2) (or with [ ])
	$b = preg_replace('/(\(|\[)\d+\s*-\s*\d+(\)|\])/i', "", $b);        // (1 - 2) or (1-2) (or with [ ])
	$b = preg_replace('/(\(|\[)Remastered(\)|\])/i', "", $b);           // (Remastered) (or with [ ])
	$b = preg_replace('/(\(|\[).*?bonus .*(\)|\])/i', "", $b);          // (With Bonus Tracks) (or with [ ])
	$b = preg_replace('/\s+-\s*$/', "", $b);                            // Chops any stray - off the end that could have been left by the previous
	$b = preg_replace('#\s+$#', '', $b);
	$b = preg_replace('#^\s+#', '', $b);
	return $b;
}

function get_base_url() {
	/* First we need to get the protocol the website is using */
	$protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"], 0, 5)) == 'https://' ? 'https://' : 'http://';

	/* returns /myproject/index.php */
	$path = $_SERVER['PHP_SELF'];

	/*
	 * returns an array with:
	 * Array (
	 *  [dirname] => /myproject/
	 *  [basename] => index.php
	 *  [extension] => php
	 *  [filename] => index
	 * )
	 */
	$path_parts = pathinfo($path);
	$directory = $path_parts['dirname'];
	/*
	 * If we are visiting a page off the base URL, the dirname would just be a "/",
	 * If it is, we would want to remove this
	 */
	$directory = ($directory == "/") ? "" : $directory;

	/* Returns localhost OR mysite.com */
	$host = $_SERVER['HTTP_HOST'];

	// This handles the case where we're getting http://mypc.local/rompr/utils/something.php
	$directory = preg_replace('#/utils$#', '', $directory);
	$directory = preg_replace('#/streamplugins$#', '', $directory);
	$directory = preg_replace('#/includes$#', '', $directory);
	/*
	 * Returns:
	 * http://localhost/mysite
	 * OR
	 * https://mysite.com
	 */
	return $protocol . $host . $directory;
}

function find_executable($prog) {

	// Test to see if $prog is on the path and then try Homebrew and MacPorts paths until we find it
	// returns boolean false if the program is not found
	logger::debug("BITS", "    Looking for executable program ",$prog);
	$paths_to_try = array( '/usr/local/bin/', '/opt/local/bin/', '/usr/bin/', './', '');
	$retval = false;
	foreach ($paths_to_try as $c) {
		$r = exec($c.$prog." 2>&1", $o, $a);
		if ($a != 127) {
			$retval = $c;
			break;
		}
	}
	if ($retval === false) {
		logger::info("BITS", "      Program ".$prog." Not Found!");
	} else {
		logger::debug("BITS", "      program is ".$retval.$prog);
	}
	return $retval;

}

function sql_init_fail($message) {
	header("HTTP/1.1 500 Internal Server Error");
?>
<html><head>
<link rel="stylesheet" type="text/css" href="css/layout-january.css" />
<link rel="stylesheet" type="text/css" href="themes/Darkness.css" />
<title>Badgers!</title>
</head>
<body>
<h2 align="center" style="font-size:200%">Collection Database Error</h2>
<h4 align="center">It's all gone horribly wrong</h2>
<br>
<?php
print '<h3 align="center">RompЯ encountered an error while checking your '.
	ucfirst(prefs::$prefs['collection_type']).' database.</h3>';
?>
<h3 align="center">An SQLite or MySQL database is required to run RompЯ</h3>
<h3 align="center">You may find it helpful to <a href="https://fatg3erman.github.io/RompR/" target="_blank">Read The Docs</a></h3>
<h3 align="center">The error message was:</h3><br>
<?php
	print '<div class="bordered" style="width:75%;margin:auto"><p align="center"><b>'.
		$message.'</b></p></div><br><br></body></html>';
		$title = "";
	include('setupscreen.php');
	exit(0);

}

function concatenate_artist_names($art) {
	if (!is_array($art)) {
		return $art;
	}
	if (count($art) == 0) {
		return '';
	} else if (count($art) == 1) {
		return $art[0];
	} else if (count($art) == 2) {
		return implode(' & ',$art);
	} else {
		$f = array_slice($art, 0, count($art) - 1);
		return implode(', ', $f)." & ".$art[count($art) - 1];
	}
}

function unwanted_array($a) {
	if (is_array($a)) {
		return $a[0];
	} else {
		return $a;
	}
}

function getArray($a) {
	if ($a === null) {
		return array();
	} else if (is_array($a)) {
		return $a;
	} else {
		return array($a);
	}
}

function getYear($date) {
	if (preg_match('/(\d\d\d\d)/', $date, $matches)) {
		return $matches[1];
	} else {
		return null;
	}
}

function trim_content_type($filetype) {
	$filetype = preg_replace('/;.*/','',$filetype);
	$filetype = trim(strtolower($filetype));
	return $filetype;
}

function audioClass($filetype, $domain = '') {
	$filetype = trim_content_type($filetype);
	switch ($filetype) {
		case "mp3":
		case "audio/mpeg":
			return 'icon-mp3-audio';
			break;

		case "mp4":
		case "m4a":
		case "aac":
		case "aacplus":
		case "aac+":
		case "aacp":
		case "audio/aac":
		case "audio/aacp":
			return 'icon-aac-audio';
			break;

		case "flac":
			return 'icon-flac-audio';
			break;

		case "wma":
		case "windows media":
			return 'icon-wma-audio';
			break;

		case "ogg":
		case "ogg vorbis":
			return 'icon-ogg-audio';
			break;

		case "cue":
		case "pls":
		case "m3u":
		case "audio/x-mpegurl":
		case "audio/x-scpls":
		case "video/x-ms-asf":
			return "icon-doc-text";
			break;

		case "?":
		case 'text/html':
		case '':
		case ' ';
			return domainCheck('notastream', $domain);
			break;

		default:
			return domainCheck('icon-music', $domain);
			break;

	}

}

function domainCheck($default, $domain) {
	if ($domain == '') {
		return $default;
	}
	switch ($domain) {
		case 'soundcloud':
		case 'spotify':
		case 'gmusic':
		case 'vkontakte':
		case 'internetarchive':
		case 'podcast':
		case 'dirble':
		case 'youtube':
			return 'icon-'.$domain.'-circled';
			break;

		case 'tunein':
			return 'icon-tunein';
			break;

		default:
			return $default;
			break;

	}

}

function getDomain($d) {
	if ($d === null || $d == "") {
		return "local";
	}
	$d = urldecode($d);
	$pos = strpos($d, ":");
	$a = substr($d,0,$pos);
	if ($a == "") {
		return "local";
	}
	$s = substr($d,$pos+3,15);
	if ($s == "api.soundcloud.") {
		return "soundcloud";
	}
	if ($a == 'http' || $a == 'https') {
		if (strpos($d, 'vk.me') !== false) {
			return 'vkontakte';
		} else if (strpos($d, 'oe1:archive') !== false) {
			return 'oe1';
		} else if (strpos($d, 'http://leftasrain.com') !== false) {
			return 'leftasrain';
		} else if (strpos($d, 'archives.bassdrivearchive.com') !== false ||
					strpos($d, 'bassdrive.com') !== false) {
			return 'bassdrive';
		}
	}
	return strtok($a, ' ');
}

function domainIcon($d, $c) {
	$h = '';
	switch($d) {
		case "spotify":
		case "gmusic":
		case "youtube":
		case "internetarchive":
		case "soundcloud":
		case "podcast":
		case "dirble":
		case "tunein":
			$h = '<i class="'.domainCheck('icon-music', $d).' '.$c.' fixed"></i>';
			break;

		case "radio-de":
		case "bassdrive":
			$h = '<div class="'.$c.' fixed"><img class="imgfill" src="newimages/'.$d.'-logo.svg" /></div>';
			break;

	}
	return $h;
}

function domainHtml($uri) {
	$h = domainIcon(getDomain($uri), 'collectionicon');
	if ($h == '') {
		if (strtolower(pathinfo($uri, PATHINFO_EXTENSION)) == "cue") {
			$h = '<i class="icon-doc-text collectionicon fixed"></i>';
		}
	}
	return $h;
}

function artistNameHtml($obj) {
	$h = '<div class="expand">'.$obj['Albumname'];
	if ($obj['Year'] && prefs::$prefs['sortbydate']) {
		$h .= ' <span class="notbold">('.$obj['Year'].')</span>';
	}
	if ($obj['Artistname']) {
		$h .= '<br><span class="notbold">'.$obj['Artistname'].'</span>';
	}
	return $h;
}

function checkComposerGenre($genre, $pref) {
	$gl = strtolower($genre);
	foreach ($pref as $g) {
		if ($gl == strtolower($g)) {
			return true;
		}
	}
	return false;
}

function get_player_ip() {
	// SERVER_ADDR reflects the address typed into the browser
	logger::log("INIT", "Server Address is ".$_SERVER['SERVER_ADDR']);
	// REMOTE_ADDR is the address of the machine running the browser
	logger::log("INIT", "Remote Address is ".$_SERVER['REMOTE_ADDR']);
	logger::log("INIT", "Prefs for mpd host is ".prefs::$prefs['multihosts'][prefs::$prefs['currenthost']]['host']);
	$pip = '';
	if (prefs::$prefs['multihosts'][prefs::$prefs['currenthost']]['socket'] != '') {
		$pip = $_SERVER['HTTP_HOST'];
	} else {
		$pip = nice_server_address(prefs::$prefs['multihosts'][prefs::$prefs['currenthost']]['host']).':'.
			prefs::$prefs['multihosts'][prefs::$prefs['currenthost']]['port'];
	}
	if (prefs::$prefs['mopidy_http_port'] !== false) {
		$pip .= '/'.explode(':', prefs::$prefs['mopidy_http_port'])[1];
	}
	logger::log("INIT", "Displaying Player IP as: ".$pip);
	return $pip;
}

function nice_server_address($host) {
 	if ($host == "localhost" || $host == "127.0.0.1" || $host == '::1') {
		return $_SERVER['HTTP_HOST'];
	} else {
		return $host;
	}
}

function get_user_file($src, $fname, $tmpname) {
	global $error;
	logger::mark("GETALBUMCOVER", "  Uploading ".$src." ".$fname." ".$tmpname);
	$download_file = "prefs/temp/".$fname;
	logger::log("GETALBUMCOVER", "Checking Temp File ".$tmpname);
	if (move_uploaded_file($tmpname, $download_file)) {
		logger::log("GETALBUMCOVER", "    File ".$src." is valid, and was successfully uploaded.");
	} else {
		logger::warn("GETALBUMCOVER", "    Possible file upload attack!");
		header('HTTP/1.0 403 Forbidden');
		ob_flush();
		exit(0);
	}
	return $download_file;
}

function albumImageBuggery() {
	// This was used to update album art to a new format but it's really old now and we've totally refactored the album image code
	// In the eventuality that someone is still using a version that old we'll keep the function but just use it to remove all album art
	// and start again.
	rrmdir('albumart/small');
	rrmdir('albumart/asdownloaded');
	mkdir('albumart/small', 0755);
	mkdir('albumart/asdownloaded', 0755);
	generic_sql_query("UPDATE Albumtable SET Searched = 0, Image = ''");
}

function rejig_wishlist_tracks() {
	global $mysqlc;
	generic_sql_query("DELETE FROM Playcounttable WHERE TTindex IN (SELECT TTindex FROM Tracktable WHERE Hidden = 1 AND Uri IS NULL)", true);
	generic_sql_query("DELETE FROM Tracktable WHERE Hidden = 1 AND Uri IS NULL", true);
	$result = generic_sql_query("SELECT * FROM Tracktable WHERE Uri IS NULL");
	foreach ($result as $obj) {
		if (sql_prepare_query(true, null, null, null,
			"INSERT INTO
				Albumtable
				(Albumname, AlbumArtistindex, AlbumUri, Year, Searched, ImgKey, mbid, Domain, Image)
			VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?)",
			'rompr_wishlist_'.microtime(true), $obj['Artistindex'], null, 0, 0, null, null, 'local', null)) {

			$albumindex = $mysqlc->lastInsertId();
			logger::log("REJIG", "    Created Album with Albumindex ".$albumindex);
			generic_sql_query("UPDATE Tracktable SET Albumindex = ".$albumindex." WHERE TTindex = ".$obj['TTindex'], true);
		}
	}
}

function format_bytes($size, $precision = 1)
{
	$base = log($size, 1024);
	$suffixes = array('', 'K', 'M', 'G', 'T');
	return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
}

function fixup_links($s) {
	return preg_replace('/(^|\s+|\n|[^\s+"])(https*:\/\/.*?)(<|\n|\r|\s|\)|$|[<|\n|\r|\s|\)|$])/', '$1<a href="$2">$2</a>$3', $s);
}

function set_version_string() {
	global $version_string;
	if (prefs::$prefs['dev_mode']) {
		// This adds an extra parameter to the version number - the short
		// hash of the most recent git commit, or a timestamp. It's for use in testing,
		// to make sure the browser pulls in the latest version of all the files.
		if (prefs::$prefs['live_mode']) {
			$version_string = ROMPR_VERSION.".".time();
		} else {
			// DO NOT USE OUTSIDE A git REPO!
			$git_ver = exec("git log --pretty=format:'%h' -n 1", $output);
			if (count($output) == 1) {
				$version_string = ROMPR_VERSION.".".$output[0];
			} else {
				$version_string = ROMPR_VERSION.".".time();
			}
		}
		logger::log('INIT', 'Dev mode - version string is '.$version_string);
	} else {
		$version_string = ROMPR_VERSION;
		logger::log('INIT', 'NOT Dev mode - version string is '.$version_string);
	}
}

function update_stream_images($schemaver) {
	switch ($schemaver) {
		case 43:
			$stations = generic_sql_query("SELECT Stationindex, StationName, Image FROM RadioStationtable WHERE Image LIKE 'prefs/userstreams/STREAM_%'");
			foreach ($stations as $station) {
				logger::log("BACKEND", "  Updating Image For Station ".$station['StationName']);
				if (file_exists($station['Image'])) {
					logger::debug("BACKEND", "    Image is ".$station['StationName']);
					$src = get_base_url().'/'.$station['Image'];
					$albumimage = new albumImage(array('artist' => "STREAM", 'album' => $station['StationName'], 'source' => $src));
					if ($albumimage->download_image()) {
						// Can't call $albumimage->update_image_database because the functions that requires are in the backend
						$images = $albumimage->get_images();
						sql_prepare_query(true, null, null, null, "UPDATE RadioStationtable SET Image = ? WHERE StationName = ?",$images['small'],$station['StationName']);
						sql_prepare_query(true, null, null, null, "UPDATE WishlistSourcetable SET Image = ? WHERE Image = ?",$images['small'],$station['Image']);
						unlink($station['Image']);
					} else {
						logger::warn("BACKEND", "  Image Upgrade Failed!");
					}
				} else {
					generic_sql_query("UPDATE RadioStationtable SET IMAGE = NULL WHERE Stationindex = ".$station['Stationindex']);
				}
			}
			break;
	}
}

function empty_modified_cache_dirs($schemaver) {
	switch ($schemaver) {
		case 44:
			foreach(array('allmusic', 'lyrics', 'lastfm') as $d) {
				rrmdir('prefs/jsoncache/'.$d);
				mkdir('prefs/jsoncache/'.$d, 0755);
			}
			break;
	}
}

function getRemoteFilesize($url, $default) {
	$def_options = stream_context_get_options(stream_context_get_default());
	stream_context_set_default(array('http' => array('method' => 'HEAD')));
	$head = array_change_key_case(get_headers($url, 1));
	// content-length of download (in bytes), read from Content-Length: field
	$clen = isset($head['content-length']) ? $head['content-length'] : 0;
	$cstring = $clen;
	if (is_array($clen)) {
		logger::debug("REMOTEFILESIZE", "Content Length is an array ", $clen);
		$cstring = 0;
		foreach ($clen as $l) {
			if ($l > $cstring) {
				$cstring = $l;
			}
		}
	}
	stream_context_set_default($def_options);
	if ($cstring !== 0) {
		logger::log("REMOTEFILESIZE", "  Read file size remotely as ".$cstring);
		return $cstring;
	} else {
		logger::log("FUNCTIONS", "  Couldn't read filesize remotely. Using default value of ".$default);
		return $default;
	}
}

function rrmdir($path) {
	$i = new DirectoryIterator($path);
	foreach ($i as $f) {
		if($f->isFile()) {
			unlink($f->getRealPath());
		} else if (!$f->isDot() && $f->isDir()) {
			rrmdir($f->getRealPath());
		}
	}
	rmdir($path);
}

function collectionButtons() {
	print '<div id="collectionbuttons" class="invisible toggledown">';

	print '<div class="containerbox dropdown-container">';
	print '<div class="selectholder">';
	print '<select id="sortcollectionbyselector" class="saveomatic">';
	foreach (COLLECTION_SORT_MODES as $mode => $key) {
		print '<option value="'.$mode.'">'.ucfirst(language::gettext($key)).'</option>';
	}
	print '</select>';
	print '</div>';
	print '</div>';

	print '<div class="containerbox dropdown-container">';
	print '<div class="selectholder">';
	print '<select id="collectionrangeselector" class="saveomatic">';
	print '<option value="'.ADDED_ALL_TIME.'">'.language::gettext('label_all_time').'</option>';
	print '<option value="'.ADDED_TODAY.'">'.language::gettext('label_today').'</option>';
	print '<option value="'.ADDED_THIS_WEEK.'">'.language::gettext('label_thisweek').'</option>';
	print '<option value="'.ADDED_THIS_MONTH.'">'.language::gettext('label_thismonth').'</option>';
	print '<option value="'.ADDED_THIS_YEAR.'">'.language::gettext('label_thisyear').'</option>';
	print '</select>';
	print '</div>';
	print '</div>';

	print '<div class="styledinputs">
	<input class="autoset toggle" type="checkbox" id="sortbydate">
	<label for="sortbydate">'.language::gettext('config_sortbydate').'</label>
	<div class="pref">
	<input class="autoset toggle" type="checkbox" id="notvabydate">
	<label for="notvabydate">'.language::gettext('config_notvabydate').'</label>
	</div>
	</div>';

	if (prefs::$prefs['multihosts'][prefs::$prefs['currenthost']]['mopidy_remote'] == false) {
		if (prefs::$prefs['collection_player'] == prefs::$prefs['player_backend'] || prefs::$prefs['collection_player'] == null) {
			print '<div class="textcentre">
			<button name="donkeykong">'.language::gettext('config_updatenow').'</button>
			</div>';
		}
	}
	print '</div>';

}

function http_status_code_string($code)
{
	// Source: http://en.wikipedia.org/wiki/List_of_HTTP_status_codes

	switch( $code )
	{
		// 1xx Informational
		case 100: $string = 'Continue'; break;
		case 101: $string = 'Switching Protocols'; break;
		case 102: $string = 'Processing'; break; // WebDAV
		case 122: $string = 'Request-URI too long'; break; // Microsoft

		// 2xx Success
		case 200: $string = 'OK'; break;
		case 201: $string = 'Created'; break;
		case 202: $string = 'Accepted'; break;
		case 203: $string = 'Non-Authoritative Information'; break; // HTTP/1.1
		case 204: $string = 'No Content'; break;
		case 205: $string = 'Reset Content'; break;
		case 206: $string = 'Partial Content'; break;
		case 207: $string = 'Multi-Status'; break; // WebDAV

		// 3xx Redirection
		case 300: $string = 'Multiple Choices'; break;
		case 301: $string = 'Moved Permanently'; break;
		case 302: $string = 'Found'; break;
		case 303: $string = 'See Other'; break; //HTTP/1.1
		case 304: $string = 'Not Modified'; break;
		case 305: $string = 'Use Proxy'; break; // HTTP/1.1
		case 306: $string = 'Switch Proxy'; break; // Depreciated
		case 307: $string = 'Temporary Redirect'; break; // HTTP/1.1

		// 4xx Client Error
		case 400: $string = 'Bad Request'; break;
		case 401: $string = 'Unauthorized'; break;
		case 402: $string = 'Payment Required'; break;
		case 403: $string = 'Forbidden'; break;
		case 404: $string = 'Not Found'; break;
		case 405: $string = 'Method Not Allowed'; break;
		case 406: $string = 'Not Acceptable'; break;
		case 407: $string = 'Proxy Authentication Required'; break;
		case 408: $string = 'Request Timeout'; break;
		case 409: $string = 'Conflict'; break;
		case 410: $string = 'Gone'; break;
		case 411: $string = 'Length Required'; break;
		case 412: $string = 'Precondition Failed'; break;
		case 413: $string = 'Request Entity Too Large'; break;
		case 414: $string = 'Request-URI Too Long'; break;
		case 415: $string = 'Unsupported Media Type'; break;
		case 416: $string = 'Requested Range Not Satisfiable'; break;
		case 417: $string = 'Expectation Failed'; break;
		case 422: $string = 'Unprocessable Entity'; break; // WebDAV
		case 423: $string = 'Locked'; break; // WebDAV
		case 424: $string = 'Failed Dependency'; break; // WebDAV
		case 425: $string = 'Unordered Collection'; break; // WebDAV
		case 426: $string = 'Upgrade Required'; break;
		case 449: $string = 'Retry With'; break; // Microsoft
		case 450: $string = 'Blocked'; break; // Microsoft

		// 5xx Server Error
		case 500: $string = 'Internal Server Error'; break;
		case 501: $string = 'Not Implemented'; break;
		case 502: $string = 'Bad Gateway'; break;
		case 503: $string = 'Service Unavailable'; break;
		case 504: $string = 'Gateway Timeout'; break;
		case 505: $string = 'HTTP Version Not Supported'; break;
		case 506: $string = 'Variant Also Negotiates'; break;
		case 507: $string = 'Insufficient Storage'; break; // WebDAV
		case 509: $string = 'Bandwidth Limit Exceeded'; break; // Apache
		case 510: $string = 'Not Extended'; break;

		// Unknown code:
		default: $string = 'Unknown';  break;
	}

	return $string;
}

function format_artist($artist, $empty = null) {
	$a = concatenate_artist_names($artist);
	if ($a != '.' && $a != "") {
		return $a;
	} else {
		return $empty;
	}
}

function format_sortartist($tags, $return_albumartist = false) {
	$sortartist = null;
	if (prefs::$prefs['sortbycomposer'] && $tags['Composer'] !== null) {
		if (prefs::$prefs['composergenre'] && $tags['Genre'] &&
			checkComposerGenre($tags['Genre'], prefs::$prefs['composergenrename'])) {
				$sortartist = $tags['Composer'];
		} else if (!prefs::$prefs['composergenre']) {
			$sortartist = $tags['Composer'];
		}
	}
	if ($sortartist == null) {
		if ($return_albumartist || $tags['AlbumArtist'] != null) {
			$sortartist = $tags['AlbumArtist'];
		} else if ($tags['Artist'] != null) {
			$sortartist = $tags['Artist'];
		} else if ($tags['station'] != null) {
			$sortartist = $tags['station'];
		}
	}
	$sortartist = concatenate_artist_names($sortartist);
	//Some discogs tags have 'Various' instead of 'Various Artists'
	if ($sortartist == "Various") {
		$sortartist = "Various Artists";
	}
	return $sortartist;
}

function update_remote_image_urls() {
	logger::log('SQL', 'Updating Remote Images in Albumtable');
	$albums = generic_sql_query("SELECT Albumindex, Image FROM Albumtable WHERE Image LIKE 'getRemoteImage%'");
	foreach ($albums as $album) {
		logger::log('SQL', '  Albumindex',$album['Albumindex'],'Image',$album['Image']);
		$newurl = get_encoded_image($album['Image']);
		sql_prepare_query(true, null, null, null,
			"UPDATE Albumtable SET Image = ? WHERE Albumindex = ?",
			$newurl,
			$album['Albumindex']
		);
	}

	logger::log('SQL', 'Updating Remote Images in Podcasttable');
	$albums = generic_sql_query("SELECT PODindex, Image FROM Podcasttable WHERE Image LIKE 'getRemoteImage%'");
	foreach ($albums as $album) {
		logger::log('SQL', '  PODindex',$album['PODindex'],'Image',$album['Image']);
		$newurl = get_encoded_image($album['Image']);
		sql_prepare_query(true, null, null, null,
			"UPDATE Podcasttable SET Image = ? WHERE PODindex = ?",
			$newurl,
			$album['PODindex']
		);
	}

	logger::log('SQL', 'Updating Remote Images in RadioStationtable');
	$albums = generic_sql_query("SELECT Stationindex, Image FROM RadioStationtable WHERE Image LIKE 'getRemoteImage%'");
	foreach ($albums as $album) {
		logger::log('SQL', '  Stationindex',$album['Stationindex'],'Image',$album['Image']);
		$newurl = get_encoded_image($album['Image']);
		sql_prepare_query(true, null, null, null,
			"UPDATE RadioStationtable SET Image = ? WHERE Stationindex = ?",
			$newurl,
			$album['Stationindex']
		);
	}

	logger::log('SQL', 'Updating Remote Images in WishlistSourcetable');
	$albums = generic_sql_query("SELECT Sourceindex, SourceImage FROM WishlistSourcetable WHERE SourceImage LIKE 'getRemoteImage%'");
	foreach ($albums as $album) {
		logger::log('SQL', '  Sourceindex',$album['Sourceindex'],'Image',$album['SourceImage']);
		$newurl = get_encoded_image($album['SourceImage']);
		sql_prepare_query(true, null, null, null,
			"UPDATE WishlistSourcetable SET SourceImage = ? WHERE Sourceindex = ?",
			$newurl,
			$album['Sourceindex']
		);
	}
}

function get_encoded_image($image) {
	$i = explode('?', $image);
	$bits = explode('&', $i[1]);
	$newparts = array();
	foreach ($bits as $bit) {
		$a = explode('=', $bit);
		if ($a[0] == 'url') {
			$url = $a[1];
		}
	}
	foreach ($bits as $bit) {
		$a = explode('=', $bit);
		if (substr($a[0], 0, 6) == 'rompr_') {
			$newparts[$a[0]] = $a[1];
		} else if ($a[0] != 'url') {
			$url .= '&'.$bit;
		}
	}
	$newparts['url'] = $url;
	$newurl = 'getRemoteImage.php?'.http_build_query($newparts);
	logger::log('SQL', '    New Image',$newurl);
	return $newurl;
}

function upgrade_saved_crazies() {
	logger::mark('INIT', "Upgrading saved crazy playlists");
	$files = glob('prefs/crazyplaylists/*.json');
	foreach ($files as $file) {
		logger::log('INIT', ' '.$file);
		$data = json_decode(file_get_contents($file), true);
		$data['tempo']['min'] = round($data['tempo']['min'] * 300, 2);
		$data['tempo']['max'] = round($data['tempo']['max'] * 300, 2);
		file_put_contents($file, json_encode($data));
	}
}

function choose_sorter_by_key($which) {
	$a = preg_match('/(a|b|c|r|t|y|u|z)(.*?)(\d+|root)_*(\d+)*/', $which, $matches);
	switch ($matches[1]) {
		case 'b':
			return 'sortby_'.prefs::$prefs['actuallysortresultsby'];
			break;

		default:
			return 'sortby_'.prefs::$prefs['sortcollectionby'];
			break;
	}
}

function upgrade_old_collections() {
	$collections = glob('prefs/collection_{mpd,mopidy}.sq3', GLOB_BRACE);
	if (count($collections) > 0) {
		logger::mark('UPGRADE', 'Old-style twin sqlite collections found');
		@mkdir('prefs/oldcollections');
		$time = 0;
		$newest = null;
		foreach ($collections as $file) {
			if (filemtime($file) > $time) {
				$newest = $file;
				$time = filemtime($file);
			}
		}
		logger::mark('UPGRADE', "Newest file is",$newest);
		copy($newest, 'prefs/collection.sq3');
		foreach ($collections as $file) {
			logger::log('UPGRADE', 'Moving',$file,'to','prefs/oldcollections/'.basename($file));
			rename($file, 'prefs/oldcollections/'.basename($file));
		}
	}
}

?>
