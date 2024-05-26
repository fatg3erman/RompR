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

function format_podcast_text($d) {
	$d = preg_replace('/style\s*=\s*\".*?\"/', '', $d);
	$d = preg_replace('/<p>\s*<\/p>/', '', $d);
	$d = preg_replace('/<p>&nbsp;<\/p>/', '', $d);
	$d = preg_replace('/\n|(\r\n)/', '<br/>', $d);
	$d = preg_replace('/(<br\s*\/*>)+/', '<br/>', $d);
	$d = preg_replace('/<\/p><br>/', '</p>', $d);
	// $d = preg_replace('/& /', '&amp; ', $d);
	$d = preg_replace('/<hr *\/*>/', '<br />', $d);
	$d = preg_replace('/<a/', '<a terget="_blank"', $d);
	return $d;
}

function update_audio_tags($download_file, $tags, $track_image_file) {
	if (pathinfo($download_file, PATHINFO_EXTENSION) == 'm4a') {
		return update_m4a_tags($download_file, $tags, $track_image_file);
	} else {
		return update_id3_tags($download_file, $tags, $track_image_file);
	}
}

function update_id3_tags($download_file, $tags, $track_image_file) {
	logger::log('TAGGER', 'Writing ID3 tags to',$download_file);
	getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'write.php', __FILE__, true);
	$tagwriter = new getid3_writetags();

	// If we have an image, whether track image or podcast image, add that to the tags now
	if ($track_image_file) {
		if ($fd = @fopen($track_image_file, 'rb')) {
				$APICdata = fread($fd, filesize($track_image_file));
				fclose ($fd);
			$tags['attached_picture'][0]['data']            = $APICdata;
			$tags['attached_picture'][0]['picturetypeid']   = 0x03;                 // 'Cover (front)'
			$tags['attached_picture'][0]['description']     = 'Cover Image';
			$tags['attached_picture'][0]['mime']            = mime_content_type($track_image_file);
		} else {
			logger::warn('TAGGER', 'Could not open image file',$track_image_file,'to embed into audio');
			@unlink($track_image_file);
			$track_image_file = null;
		}
	}

	$tagwriter->filename       = $download_file;
	$tagwriter->tagformats = array('id3v2.3');
	$tagwriter->overwrite_tags = true;
	$tagwriter->tag_encoding   = 'UTF-8';
	$tagwriter->remove_other_tags = true;
	$tagwriter->tag_data = $tags;
	if ($tagwriter->WriteTags()) {
		logger::trace('TAGGER', 'Successfully wrote tags');
		if (!empty($tagwriter->warnings)) {
			logger::warn('TAGGER', 'There were some warnings'.implode(' ', $tagwriter->warnings));
		}
	} else {
		logger::error('TAGGER', 'Failed to write tags!', implode(' ', $tagwriter->errors));
	}

	return $track_image_file;
}

function update_m4a_tags($download_file, $tags, $track_image_file) {
	$ap = find_executable('AtomicParsley');
	if ($ap !== false) {
		logger::log('TAGGER', "Wrting Tags using AtomicParsley");
		foreach ($tags as &$tag) {
			$tag[0] = str_replace('"', '\"', $tag[0]);
		}
		$cmdline = $ap.'AtomicParsley "'.$download_file
			.'" --artist "'.$tags['artist'][0]
			.'" --title "'.$tags['title'][0]
			.'" --album "'.$tags['album'][0]
			.'" --albumArtist "'.$tags['albumartist'][0]
			.'" --comment "'.$tags['comment'][0].'"';
		if (array_key_exists('track_number', $tags)) {
			$cmdline .= ' --tracknum '.$tags['track_number'][0];
		}
		if ($track_image_file && is_file($track_image_file)) {
			$cmdline .= ' --artwork "'.$track_image_file.'"';
		}
		logger::trace('TAGGER', "cmdline is", $cmdline);
		$o = [];
		exec($cmdline, $o);
		foreach ($o as $l) {
			logger::trace('TAGGER', $o);
		}
		$fname = pathinfo($download_file, PATHINFO_FILENAME);
		$tlf = dirname($download_file).'/'.$fname.'-temp*.m4a';
		logger::trace("TAGGER", "Checking for", $tlf);
		$tmp = glob($tlf);
		foreach ($tmp as $t) {
			logger::log("TAGGER", "AtomicParsley left temp file behind", $t);
			unlink($download_file);
			rename($t, $download_file);
			break;
		}
	} else {
		logger::warn("TAGGER", "File is m4a, please install AtomicParsley to write tags");
	}

	return $track_image_file;
}

// This is for converting a time parameter in seconds into something like 2 Days 12:34:15
// Pass it a value in seconds. It does not use date functions since they're timezone aware
// and will add one to the hours value during BST.
function format_time($t, $f = ':') {

	// PHP 8.1 moans about losing precision. I KNOW. I DON'T CARE.
	$t = (int) round($t);

	if ($t == 0)
		return '0:00';

	$retval = '';
	$hours = 0;
	$days = 0;

	if (($t/86400) >= 1) {
		$days = floor($t/86400);
		$retval = $days . ' ' . language::gettext('label_days') . ' ';
		$t -= ($days*86400);
	}

	if ($t == 0)
		return $retval;

	if (($t/3600) >= 1 || $days > 0) {
		$hours = floor($t/3600);
		$retval .= $hours.$f;
		$t -= ($hours*3600);
	}

	$mins = floor($t/60);
	if ($hours > 0 || $days > 0)
		$mins = sprintf('%02d', $mins);

	$retval .= $mins.$f.sprintf('%02d', ($t%60));

	return $retval;
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

	// Try to find an executable program by testing various paths until we find it.
	// EXECUTABLES_PATHS will prioritise Homebrew (on macOS) over builtin utilities.
	// Returns boolean false if the program is not found, or the path (not including the program name)
	$retval = false;
	foreach (EXECUTABLES_PATHS as $c) {
		if (is_executable($c.$prog)) {
			$retval = $c;
			break;
		}
	}
	if ($retval === false) {
		logger::warn("BITS", "Executable",$prog,"Not Found!");
	} else {
		logger::log("BITS", "Found",$prog,"at",$retval.$prog);
	}
	return $retval;

}

function sql_init_fail($message) {
	global $title, $setup_error;
	header("HTTP/1.1 500 Internal Server Error");
	$setup_error = error_message($message);
	$title = 'RompЯ encountered an error while checking your '.ucfirst(prefs::get_pref('collection_type')).' database';
	include('setupscreen.php');
	exit(0);
}

function big_bad_fail($message) {
	global $title, $setup_error;
	header("HTTP/1.1 500 Internal Server Error");
	$setup_error = error_message($message);
	$title = 'RompЯ encountered an error while checking your installation';
	include('setupscreen.php');
	exit(0);
}

function error_message($message) {
	return '<h3 align="center">You may find it helpful to <a href="https://fatg3erman.github.io/RompR/" target="_blank">Read The Docs</a></h3>
	<h3 align="center">The error message was:</h3><br>
	<div class="border-red" style="width:75%;margin:auto"><p align="center"><b>'.
	$message.'</b></p></div><br>';
}

function connect_fail($t) {
	global $title, $setup_error;
	logger::warn("INIT", "MPD Connection Failed");
	$title = $t;
	include("setupscreen.php");
	exit();
}

function backend_init_fail() {
	global $title, $setup_error;
	logger::warn('INIT', 'Backend Daemon Not Running');
	$title = 'RompЯ Backend Daemon Not Running';
	$setup_error = '<h3 align="center">The RompЯ Backend Daemon is not running. This is now a requirement.</h3>'.
	'<h3 align="center">Please <a href="https://fatg3erman.github.io/RompR/Backend-Dameon" target="_blank">Read The Docs</a></h3>';
	include("setupscreen.php");
	exit();
}

function backend_version_fail() {
	global $title, $setup_error;
	logger::warn('INIT', 'Backend Daemon Version Failure');
	$title = 'RompЯ Backend Daemon Is Out Of Date';
	$setup_error = '<h3 align="center">The RompЯ Backend Daemon needs to be restarted before you can access RompЯ. RompR has tried to restart it but it did not work.</h3>'.
	'<h3 align="center">Please <a href="https://fatg3erman.github.io/RompR/Backend-Dameon" target="_blank">Read The Docs</a></h3>';
	include("setupscreen.php");
	exit();
}

function concatenate_artist_names($art) {
	$art = getArray($art);
	$f = array_pop($art);
	if (count($art) == 0) {
		return $f;
	} else {
		return implode(' & ', [implode(', ', $art), $f]);
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
		return [$a];
	}
}

function getYear($date) {
	if ($date && preg_match('/(\d\d\d\d)/', $date, $matches)) {
		return $matches[1];
	} else {
		return null;
	}
}

function get_filename($file) {
	return pathinfo($file, PATHINFO_FILENAME);
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
		case "aac.h.264":
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
		case "vorbis":
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

		case 'unknown':
			return "icon-blank";
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
		case 'vkontakte':
		case 'ytmusic':
		case 'internetarchive':
		case 'podcast':
		case 'dirble':
		case 'youtube':
		case 'bandcamp':
		case 'qobuz':
			return 'icon-'.$domain.'-circled';
			break;

		case 'tunein':
			return 'icon-tunein';
			break;

		case "yt":
			return 'icon-youtube-circled';
			break;

		default:
			return $default;
			break;

	}

}

function getDomain($d) {

	if ($d === null)
		return 'local';

	$a = strtok($d, ':');

	// We have to do a little munging here because mpd local tracks don't start with
	// a protocol like local: but they MAY have a : in the Uri as part of the filename.
	// Hence the need to check all the supported protocols and return local
	// if it isn't one of those.
	// The Bahama Soul Club/The Cuban Tapes/15 The Bahama Soul Club feat. Arema Arega - Tiki Suite Pt 2: Mirando Al Mar (Club des Belugas remix).mp3
	// returns 'The' as the domain if we just use the value returned by strtok($d, ':') + strtok($a, ' ');

	switch ($a) {
		case '':
			return 'local';
			break;

		case 'http':
		case 'https':
			if (strpos($d, 'api.soundcloud') !== false) {
				// MPD playing a soundcloud track
				return "soundcloud";
			} else if (strpos($d, 'vk.me') !== false) {
				return 'vkontakte';
			} else if (strpos($d, 'oe1:archive') !== false) {
				return 'oe1';
			} else if (strpos($d, 'http://leftasrain.com') !== false) {
				return 'leftasrain';
			} else if (strpos($d, 'archives.bassdrivearchive.com') !== false || strpos($d, 'bassdrive.com') !== false) {
				return 'bassdrive';
			} else {
				return $a;
			}
			break;

		case 'mms':
		case 'mmsh':
		case 'mmst':
		case 'mmsu':
		case 'gopher':
		case 'rtp':
		case 'rtsp':
		case 'rtmp':
		case 'rtmpt':
		case 'rtmps':
			return $a;
			break;

		default:
			if (prefs::get_pref('player_backend') == 'mpd') {
				return 'local';
			} else {
				return strtok($a, ' ');
			}
			break;

	}

}

function domainIcon($d, $c) {
	$h = '';
	switch($d) {
		case "spotify":
		case "ytmusic":
		case "youtube":
		case "yt":
		case "internetarchive":
		case "soundcloud":
		case "podcast":
		case "dirble":
		case "tunein":
		case "bandcamp":
		case 'qobuz':
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
	$h = domainIcon(getDomain($uri), 'inline-icon');
	if ($h == '' && $uri != null) {
		if (strtolower(pathinfo($uri, PATHINFO_EXTENSION)) == "cue") {
			$h = '<i class="icon-doc-text inline-icon fixed"></i>';
		}
	}
	return $h;
}

function artistNameHtml($obj) {
	$h = '<div class="artistnamething">'.$obj['Albumname'];
	if ($obj['Year'] && (prefs::get_pref('sortbydate') || $obj['year_always'])) {
		$h .= ' <span class="notbold">';
		if (!$obj['year_always'])
			$h .= '(';

		$h .= $obj['Year'];

		if (!$obj['year_always'])
			$h .= ')';

		'</span>';
	}

	if ($obj['Artistname'])
		$h .= '<br /><span class="notbold">'.$obj['Artistname'].'</span>';

	foreach($obj['extralines'] as $line) {
		$h .= '<br /><span class="notbold">'.$line.'</span>';
	}

	$h .= '</div>';
	return $h;
}

function get_player_ip() {
	// SERVER_ADDR reflects the address typed into the browser
	logger::log("INIT", "Server Address is ".$_SERVER['SERVER_ADDR']);
	// REMOTE_ADDR is the address of the machine running the browser
	logger::log("INIT", "Remote Address is ".$_SERVER['REMOTE_ADDR']);
	$pdef = prefs::get_player_def();
	logger::log("INIT", "Prefs for mpd host is ".$pdef['host']);
	$pip = '';
	if ($pdef['socket'] != '') {
		$pip = nice_server_address($pdef['host']);
	} else {
		$pip = nice_server_address($pdef['host']).':'.$pdef['port'];
	}
	if (prefs::get_player_param('websocket') !== false) {
		// Get the number off the end
		$poop = explode(':', prefs::get_player_param('websocket'))[1];
		// Remove anything like /mopidy/ws after the number
		$poop = explode('/', $poop)[0];
		$pip .= '/'.$poop;
	}
	logger::log("INIT", "Displaying Player IP as: ".$pip);
	return $pip;
}

function nice_server_address($host) {
 	if ($host == "localhost" || $host == "127.0.0.1" || $host == '::1') {
 		// Turn localhost into something prettier. localhost is only localhost relative to the server, not the
 		// browser. We want to use it for websocket, which is a websocket and so localhost is useless
 		// to us in that context. Also we want to be able to display 'connected to Mopidy at [hostname]' in the browser
 		// not Connected to Mopidy at localhost, for similar reasons.
 		$ip = $_SERVER['HTTP_HOST'];
 		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
 			// We'll get here if a valid IPv4 or IPv6 address was used.
 			// We will not get here if that address contains a port number because filter_var thinks they're invalid
 			// We'll not gete here if someone used a hostname either.
 			logger::log('SERVER', 'Using valid IP address from HTTP_HOST',$ip);
 			return $ip;
 		} else if (strpos($ip, ':') === false) {
 			// Server address might have a : in it for a port number. We don't want this
 			// because it breaks translation of localhost to an IP address we can use.
 			// This does mean that if we're using hostname:port then we skip this and just use the IP
 			logger::log('SERVER', 'Using address from HTTP_HOST',$ip);
 			return $ip;
 		} else {
 			// In this case just return the IP address.
 			logger::log('SERVER', 'HTTP_HOST not useful',$ip,'using SERVER_ADDR instead',$_SERVER['SERVER_ADDR']);
			return $_SERVER['SERVER_ADDR'];
		}
	} else {
		return $host;
	}
}

function get_user_file($src, $fname, $tmpname) {
	global $error;
	logger::info("GETALBUMCOVER", " Uploading ".$src." ".$fname." ".$tmpname);
	$download_file = "prefs/temp/".$fname;
	logger::debug("GETALBUMCOVER", "Checking Temp File ".$tmpname);
	if (!is_file($tmpname)) {
		logger::warn('GETALBUMCOVER', $tmpname, 'does not exist');
	}
	if (move_uploaded_file($tmpname, $download_file)) {
		logger::log("GETALBUMCOVER", "File ".$src." is valid, and was successfully uploaded.");
	} else {
		logger::warn("GETALBUMCOVER", "Possible file upload attack!");
		header('HTTP/1.0 403 Forbidden');
		ob_flush();
		exit(0);
	}
	return $download_file;
}

function format_bytes($size, $precision = 1)
{
	$base = log($size, 1024);
	$suffixes = array('', 'K', 'M', 'G', 'T');
	return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
}

function set_version_string() {
	global $version_string;
	if (prefs::get_pref('dev_mode')) {
		// This adds an extra parameter to the version number - the short
		// hash of the most recent git commit, or a timestamp. It's for use in testing,
		// to make sure the browser pulls in the latest version of all the files.
		if (prefs::get_pref('live_mode')) {
			logger::log('INIT', 'Using Live Mode for Version String');
			$version_string = ROMPR_VERSION.".".time();
		} else {
			logger::debug('INIT', 'Dev Mode starting in',getcwd());
			$gitpath = find_executable('git');
			// DO NOT USE OUTSIDE A git REPO!
			$git_ver = exec($gitpath."/git log --pretty=format:'%h' -n 1 2>&1", $output);
			logger::core('INIT', print_r($output, true));
			if (count($output) == 1) {
				$version_string = ROMPR_VERSION.".".$output[0];
			} else {
				logger::warn('INIT', 'Could not work out git thing for dev mode!');
				$version_string = ROMPR_VERSION.".".time();
			}
		}
		logger::log('INIT', 'Dev mode - version string is '.$version_string);
	} else {
		$version_string = ROMPR_VERSION;
		logger::log('INIT', 'NOT Dev mode - version string is '.$version_string);
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
		logger::log("REMOTEFILESIZE", "Read file size remotely as ".$cstring);
		return $cstring;
	} else {
		logger::log("FUNCTIONS", "Couldn't read filesize remotely. Using default value of ".$default);
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
	if ($a != "") {
		return $a;
	} else {
		return $empty;
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
	logger::log('SQL', 'New Image',$newurl);
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
	$a = preg_match('/(a|b|c|r|t|y|u|z|x)(.*?)(\d+|root)_*(\d+)*/', $which, $matches);
	switch ($matches[1]) {
		case 'b':
			if ($matches[2] == 'artist' && is_numeric($matches[3])) {
				if (prefs::$database->check_artist_browse($matches[3]))
					return 'sortby_artist';
			}
			if (prefs::get_pref('actuallysortresultsby') == 'results_as_tree') {
				return false;
			} else {
				return 'sortby_'.prefs::get_pref('actuallysortresultsby');
			}
			break;

		default:
			return 'sortby_'.prefs::get_pref('sortcollectionby');
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

function saveCollectionPlayer($type) {
	logger::mark("DATABASE", "Setting Collection Type to",$type);
	prefs::set_pref(['collection_player' => $type]);
	prefs::save();
}

function calculate_best_update_time($podcast) {
    $os = php_uname();

	if ($podcast['RefreshOption'] == REFRESHOPTION_NEVER)
		return time() + 117600;

	if ($podcast['LastPubDate'] === null) {
		logger::warn("PODCASTS", $podcast['Title'],"last pub date is null");
		$podcast['LastPubDate'] = time();
	}

	logger::log("PODCASTS", "Working out best update time for ".$podcast['Title']);
	$dt = new DateTime(date('c', $podcast['LastPubDate']));

	// DateTime::format crashes into hyperspace on macOS for reasons unknown
    if (strpos($os, 'Darwin') === false)
		logger::trace("PODCASTS", "Last Pub Date is ".$podcast['LastPubDate'].' ('.$dt->format('c').')');

	logger::trace("PODCASTS", "Podcast Refresh interval is ".$podcast['RefreshOption']);
	while ($dt->getTimestamp() < time()) {
		switch ($podcast['RefreshOption']) {

			case REFRESHOPTION_HOURLY:
				$dt->modify('+1 hour');
				break;

			case REFRESHOPTION_DAILY:
				$dt->modify('+1 day');
				break;

			case REFRESHOPTION_WEEKLY:
				$dt->modify('+1 week');
				break;

			case REFRESHOPTION_MONTHLY:
				$dt->modify('+1 month');
				break;

			default:
				logger::error("PODCASTS", "  Unknown refresh option",$podcast['RefreshOption'],"for podcast ID",$podcast['Title']);
				return time();
				break;
		}

	}

    if (strpos($os, 'Darwin') === false)
		logger::log("PODCASTS", "Worked out update time based on pubDate and RefreshOption: ".$dt->format('r').' ('.$dt->getTImestamp().')');

	return $dt->getTimestamp();

}

function printFileSearch(&$tree) {
	$prefix = "sdirholder";
	print '<div class="menuitem">';
	print "<h3>".language::gettext("label_searchresults")."</h3>";
	print "</div>";
	$tree->getHTML($prefix);
}

function printFileItem($displayname, $fullpath, $time) {
	global $prefs;
	$ext = strtolower(pathinfo($fullpath, PATHINFO_EXTENSION));
	print '<div class="clickable clicktrack playable ninesix draggable indent containerbox line" name="'.
		rawurlencode($fullpath).'">';

	if (prefs::get_pref('music_directory_albumart') != '' &&
		 (prefs::get_pref('player_backend') == 'mpd' || strpos($fullpath, 'local:track:') === 0)) {
		$dlp = str_replace('local:track:', '', $fullpath);
		print '<a href="'.dirname(dirname(get_base_url())).'/prefs/MusicFolders/'.$dlp.'" download="'.rawurldecode(basename($dlp)).'">';
	}

	print '<i class="'.audioClass($ext, getDomain($fullpath)).' fixed inline-icon"></i>';

	if (prefs::get_pref('music_directory_albumart') != '' &&
		 (prefs::get_pref('player_backend') == 'mpd' || strpos($fullpath, 'local:track:') === 0)) {
		print '</a>';
	}

	print '<div class="expand">'.$displayname.'</div>';
	if ($time > 0) {
		print '<div class="fixed playlistrow2 tracktime">'.format_time($time).'</div>';
	}
	print '</div>';
}

function printPlaylistItem($displayname, $fullpath) {
	print '<div class="clickable clickcue playable ninesix draggable indent containerbox line" name="'.
		rawurlencode($fullpath).'">';
	print '<i class="icon-doc-text fixed collectionitem"></i>';
	print '<div class="expand">'.$displayname.'</div>';
	print '</div>';
}

function print_performance_measurements() {
	global $performance;
	logger::info('TIMINGS','-------------------------------------------------------------------------');
	$tot = $performance['total'];
	foreach ($performance as $k => $v) {
		$pc = ($v/$tot)*100;
		logger::info('TIMINGS', ucfirst($k),':',$v,'seconds, or',$pc.'%');
	}
	logger::info('TIMINGS','-------------------------------------------------------------------------');
}

function type_to_extension($mime) {
	switch ($mime) {
		case 'image/jpeg':
		case 'image/jpg':
			return 'jpg';
			break;

		case 'image/png':
			return 'png';
			break;

		default:
			return null;
			break;
	}
}

function close_browser_connection() {
	// For requests that take > 3 minutes we need to close the browser
	// connection without terminating the script, otherwise the browser
	// will retry the request and this will be catastrophic.
	$sapi_type = php_sapi_name();
	logger::log('COLLECTION','SAPI Name is',$sapi_type);
	if (preg_match('/fpm/', $sapi_type) || preg_match('/fcgi/', $sapi_type)) {
		logger::info('COLLECTION', 'Closing Request The FastCGI Way');
		print('<html></html>');
		fastcgi_finish_request();
	} else {
		logger::info('COLLECTION', 'Closing Request The Apache Way');
		ob_end_clean();
		ignore_user_abort(true); // just to be safe
		ob_start();
		print('<html></html>');
		$size = ob_get_length();
		header("Content-Length: $size");
		header("Content-Encoding: none");
		header("Connection: close");
		ob_end_flush();
		ob_flush();
		flush();
		if (ob_get_contents()) {
			ob_end_clean();
		}
	}

	if (session_id()) {
		session_write_close();
	}

}

// Check that the backend daemon is running and start it if it isn't
// or if it's an older version, restart it.
function check_backend_daemon() {
	global $version_string;
	logger::mark('INIT', 'Checking backend daemon', $version_string);
	$pwd = getcwd();
	$b = $pwd.'/rompr_backend.php';
	logger::log('INIT', 'Checking for',$b);
	if (get_pid($b) === false) {
		logger::info('INIT', 'Backend Daemon is not running. Trying to start it');
		start_process($b);
	    sleep(1);
		if (get_pid($b) === false) {
			backend_init_fail();
		}
	} else {
		logger::info('INIT', 'Backend Daemon is already running.');
		if (prefs::get_pref('backend_version') != $version_string || array_key_exists('force_restart', $_REQUEST)) {
			logger::info('INIT', 'Backend Daemon',prefs::get_pref('backend_version'),'is different from',$version_string,'. Restarting it');
			kill_process(get_pid($b));
			while (($pid = get_pid('romonitor.php')) !== false) {
				logger::log('INIT', 'Killing romonitor process', $pid);
				kill_process($pid);
			}
			while (($pid = get_pid('alarmclock.php')) !== false) {
				logger::log('INIT', 'Killing alarmclock process', $pid);
				kill_process($pid);
			}
			start_process($b);
		    sleep(3);
			if (get_pid($b) === false) {
				logger::info('INIT', 'Backend failed to start');
				backend_init_fail();
			}
			prefs::load();
			if (prefs::get_pref('backend_version') != $version_string) {
				logger::info('INIT', 'Backend version mismatch after restart');
				backend_version_fail();
			}
		}
	}
}

// Start a process that is detached and will keep running when we exit
// and also detahced from the php-fpm process so it frees that up too.
function start_process($cmd, $exe='php') {
    logger::log('DAEMON', 'Starting Process', $cmd);
    $os = php_uname();
    if (strpos($os, 'Darwin') === false) {
    	// On Linux
    	exec('nohup '.$exe.' '.$cmd.' > /dev/null & > /dev/null');
   	} else {
   		// On macOS
   		logger::debug('PROCESS', 'Using macOS form of start command');
    	exec($exe.' '.$cmd.' < /dev/null > /dev/null 2>&1 &');
    }
    return get_pid($cmd);
}

function kill_process($pid) {
	logger::trace('INIT', 'Killing PID',$pid);
    exec('kill '.$pid);
}

function get_pid($cmd) {
    logger::core('INIT', 'Looking for PID for',$cmd);
    exec('ps aux | grep "'.$cmd.'" | grep -v grep', $processinfo);
    if (is_array($processinfo) && count($processinfo) > 0) {
        $processinfo = array_filter(explode(' ', $processinfo[0]));
        $user = array_shift($processinfo);
        return array_shift($processinfo);
    }
    logger::core('INIT', 'No PID Found');
    return false;
}

function create_body_tag($base_class) {
	require_once('includes/MobileDetect.php');
	print '<body class="'.$base_class;
	$md = new Detection\MobileDetect;
	if ($md->isMobile() || $md->isTablet() || $md->isiOS()) {
		logger::log('INIT', 'Mobile_Detect detected Mobile Browser');
		print ' mobilebrowser';
	} else {
		logger::log('INIT', 'Mobile_Detect detected Desktop Browser');
		print ' desktopbrowser';
	}
	print '">'."\n";
}

function strip_track_name($thing) {
	if (!$thing)
		return $thing;

	// Convert to lower case, change & to and, and remove punctuation
	// Don't remove brackety stuff because this is used for preventing duplicate
	// tracks in the smart_uri table, and we want all the versions that () might denote
	$thing = strtolower($thing);
	$thing = preg_replace('/\s+\&\s+/', ' and ', $thing);
	$thing = preg_replace("/\pP/", '', $thing);
	return trim($thing);
}

// Remove any of our 'Ignore this prefix' values from the start of a string.
// TYhis is for comparing eg Back Doors and The Back Doors, which is a
// a common type of problem.
function strip_prefixes($name) {
	$pf = array_map('strtolower', prefs::get_pref('nosortprefixes'));
	$stripname = strtolower($name);
	foreach ($pf as $prefix) {
		if (strpos($stripname, $prefix.' ') === 0) {
			return substr($name, strlen($prefix)+1);
		}
	}
	return $name;
}

function metaphone_compare($search_term, $found_term, $match_distance = null) {
	// Metaphones were an experiment. They were a bit too fuzzy for our porpoises.
	// This is a fuzzy compare function for comparing album names, artist names, etc.
	// Search term should be first, to ensure accuracy of the percentage measurement.
	// https://www.php.net/manual/en/function.levenshtein.php

	// The smaller the value of $match_distance the more exact the comparison.
	// If match_distance is not supplied we use a value calculated as 10% of the length
	// of search_term or 1, whichever is higher.
	// You will probably want to tune this value by trial and error depending on the use case.
	// A value of 0 seems best for artists.

	// mb_detect_encdoing is VeRY slow, but we don't know what encoding the source
	// material is.

	$new_search = mb_convert_encoding($search_term, 'ASCII', mb_detect_encoding($search_term));
	$new_found = mb_convert_encoding($found_term, 'ASCII', mb_detect_encoding($found_term));

	$new_search = preg_replace('/ \(.+?\)$/', '', $new_search);
	$new_found = preg_replace('/ \(.+?\)$/', '', $new_found);

	$new_search = strip_track_name($new_search);
	$new_found = strip_track_name($new_found);

	$dist = levenshtein($new_search, $new_found);
	if ($match_distance === null)
		$match_distance = max(1, (strlen($new_search) * 0.10));

	if ($dist <= $match_distance) {
		logger::core('METAPHONE', $found_term,'matches',$search_term);
		return true;
	} else {
		logger::core('METAPHONE', $new_found,'does not match',$new_search);
		return false;
	}
}

?>
