<?php
include ("includes/vars.php");
include ("includes/functions.php");

$url = $_REQUEST['url'];

foreach ($_GET as $k => $v) {
	if ($k != 'url') {
		$url .= '&'.$k.'='.$v;
	}
}

if (!$url) {
	debuglog("Asked to download image but no URL given!","TOMATO",3);
    header("HTTP/1.1 404 Not Found");
    exit(0);
} else {
	debuglog("Getting Remote Image ".$url,"TOMATO",7);
	$ext = explode('.',$url);
	$outfile = 'prefs/imagecache/'.md5($url);
	if (!file_exists($outfile)) {
	    debuglog("  ... Downloading it", "TOMATO",8);
	    $fp = fopen($outfile, 'w');
		$aagh = url_get_contents($url, ROMPR_IDSTRING, false, true, false, $fp);
		fclose($fp);
		if ($aagh['status'] == "200") {
			debuglog("Cached Image ".$outfile,"TOMATO",9);
		} else {
			debuglog("Failed to download ".$url." - status was ".$aagh['status'],"TOMATO",5);
		    header("HTTP/1.1 404 Not Found");
		    exit(0);
		}
	}

	$mime = 'image/'.end($ext);
	$convert_path = find_executable("identify");
	if ($convert_path === false) {
		exec('rm '.$outfile);
		header('Content-type: text/xml');
		readfile('newimages/imgnotfound.svg');
	    exit(0);
	}
	$o = array();
	$r = exec($convert_path."identify -verbose ".$outfile." | grep Mime");
	if (preg_match('/Mime type:\s+(.*)$/', $r, $o)) {
		if ($o[1]) {
			$mime = $o[1];
		}
	} else {
		$r = exec($convert_path."identify -verbose ".$outfile." | grep Format");
		if (preg_match('/Format:\s+(.*?) /', $r, $o)) {
			if ($o[1]) {
				$mime = 'image/'.strtolower($o[1]);
			}
		} else {
			debuglog("Image format not recognnised!","TOMATO",5);
			exec('rm '.$outfile);
			header('Content-type: text/xml');
			readfile('newimages/imgnotfound.svg');
		    exit(0);
		}
	}
	header('Content-type: '.$mime);
	readfile($outfile);
}
?>
