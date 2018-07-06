<?php

// [playlist]
// numberofentries=3
// File1=http://streamer-dtc-aa04.somafm.com:80/stream/1018
// Title1=SomaFM: Groove Salad (#1 128k mp3): A nicely chilled plate of ambient/downtempo beats and grooves.
// Length1=-1
// File2=http://mp2.somafm.com:8032
// Title2=SomaFM: Groove Salad (#2 128k mp3): A nicely chilled plate of ambient/downtempo beats and grooves.
// Length2=-1
// File3=http://ice.somafm.com/groovesalad
// Title3=SomaFM: Groove Salad (Firewall-friendly 128k mp3) A nicely chilled plate of ambient/downtempo beats and grooves.
// Length3=-1
// Version=2

class plsFile {

	public function __construct($data, $url, $station, $image) {
		$this->url = $url;
		$this->station = $station;
		$this->image = $image;
		$this->tracks = array();

		$parts = explode(PHP_EOL, $data);
		$pointer = -1;
		foreach ($parts as $line) {
			$bits = explode("=", $line);
			if (preg_match('/File/', $bits[0])) {
				$pointer++;
				$this->tracks[$pointer] = array('TrackUri' => trim(implode('=', array_slice($bits,1))), 'PrettyStream' => '');
			}
			if (preg_match('/Title/', $bits[0])) {
				$this->tracks[$pointer]['PrettyStream'] = $bits[1];
			}
		}
		if ($this->tracks[0]['PrettyStream'] != '') {
			$this->station = checkStationAgain($this->station, $this->tracks[0]['PrettyStream']);
		}
	}

	public function updateDatabase() {
		$stationid = check_radio_station($this->url, $this->station, $this->image);
		if ($stationid) {
			check_radio_tracks($stationid, $this->tracks);
		} else {
			debuglog("ERROR! Null station ID!","RADIO",2);
			header('HTTP/1.1 417 Expectation Failed');
			exit(0);
		}
	}

	public function getTracksToAdd() {
		$tracks = array();
		foreach ($this->tracks as $r) {
			$tracks[] = 'add "'.format_for_mpd($r['TrackUri']).'"';
		}
		return $tracks;
	}

}

// <ASX version="3.0">
// 	<ABSTRACT>http://www.bbc.co.uk/5livesportsextra/</ABSTRACT>
// 	<TITLE>BBC Radio 5 live sports extra</TITLE>
// 	<AUTHOR>BBC</AUTHOR>
// 	<COPYRIGHT>(c) British Broadcasting Corporation</COPYRIGHT>
// 	<MOREINFO HREF="http://www.bbc.co.uk/5livesportsextra/" />
// 	<PARAM NAME="HTMLView" VALUE="http://www.bbc.co.uk/5livesportsextra/" />
// 	<PARAM NAME="GEO" VALUE="UK" />

// 	<Entry>
// 		<ref href="mms://wmlive-acl.bbc.co.uk/wms/bbc_ami/radio5/5spxtra_bb_live_ep1_sl0?BBC-UID=649f8b917418780a1d247f88818c39cb39a24514c0e00211d248f0c4e30ce058&amp;SSO2-UID=" />
// 	</Entry>
// 	<Entry>
// 		<ref href="mms://wmlive-acl.bbc.co.uk/wms/bbc_ami/radio5/5spxtra_bb_live_eq1_sl0?BBC-UID=649f8b917418780a1d247f88818c39cb39a24514c0e00211d248f0c4e30ce058&amp;SSO2-UID=" />
// 	</Entry>


class asxFile {

	public function __construct($data, $url, $station, $image) {
		$this->url = $url;
		$xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
		if ($xml === false) {
			debuglog("ERROR could not parse XML from ".$url,"RADIO",2);
			header('HTTP/1.1 417 Expectation Failed');
			exit(0);
		}
		$this->station = ($xml->TITLE != null && $xml->TITLE != '') ? $xml->TITLE : $station;
		$this->image = $image;
		$this->prettystream = ($xml->COPYRIGHT != null && $xml->COPYRIGHT != '') ? $xml->COPYRIGHT : "";
		$this->tracks = array();
	    foreach($xml->Entry as $r) {
	    	$this->tracks[] = array('TrackUri' => $r->ref['href'], 'PrettyStream' => $prettystream);
	    }
	}

	public function updateDatabase() {
		$stationid = check_radio_station($this->url, $this->station, $this->image);
		if ($stationid) {
			check_radio_tracks($stationid, $this->tracks);
		} else {
			debuglog("ERROR! Null station ID!","RADIO",2);
			header('HTTP/1.1 417 Expectation Failed');
			exit(0);
		}
	}

	public function getTracksToAdd() {
		return array('load "'.format_for_mpd(htmlspecialchars_decode($this->url)).'"');
	}

}

// <playlist version="1" xmlns="http://xspf.org/ns/0/">
//     <title>I Love Radio (www.iloveradio.de)</title>
//     <info>http://www.iloveradio.de</info>
//     <trackList>
//         <track><location>http://87.230.53.70:80/iloveradio1.mp3</location></track>
//         <track><location>http://80.237.157.79:80/iloveradio1.mp3</location></track>
//         <track><location>http://80.237.156.44:80/iloveradio1.mp3</location></track>
//         <track><location>http://87.230.100.70:80/iloveradio1.mp3</location></track>
//         <track><location>http://80.237.158.62:80/iloveradio1.mp3</location></track>
//         <track><location>http://80.237.157.81:80/iloveradio1.mp3</location></track>
//         <track><location>http://80.237.157.76:80/iloveradio1.mp3</location></track>
//         <track><location>http://80.237.158.76:80/iloveradio1.mp3</location></track>
//         <track><location>http://89.149.254.214:80/iloveradio1.mp3</location></track>
//         <track><location>http://80.237.157.64:80/iloveradio1.mp3</location></track>
//     </trackList>
// </playlist>

class xspfFile {

	public function __construct($data, $url, $station, $image) {
		$this->url = $url;
		// Handle badly formed XML that some stations return
		$data = preg_replace('/ & /', ' &amp; ', $data);
		$data = preg_replace('/ < /', ' &lt; ', $data);
		$data = preg_replace('/ > /', ' &gt; ', $data);
		$xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
		if ($xml === false) {
			debuglog("ERROR could not parse XML from ".$url,"RADIO",2);
			header('HTTP/1.1 417 Expectation Failed');
			exit(0);
		}
		$this->station = $xml->title != null ? $xml->title : $station;
		$this->image = $image;
		$prettystream = $xml->info != null ? $xml->info : "";
		$this->tracks = array();
	    foreach($xml->trackList->track as $r) {
	    	$this->tracks[] = array('TrackUri' => (string) $r->location, 'PrettyStream' => $prettystream);
	    }
	}

	public function updateDatabase() {
		$stationid = check_radio_station($this->url, $this->station, $this->image);
		if ($stationid) {
			check_radio_tracks($stationid, $this->tracks);
		} else {
			debuglog("ERROR! Null station ID!","RADIO",2);
			header('HTTP/1.1 417 Expectation Failed');
			exit(0);
		}
	}

	public function getTracksToAdd() {
		$tracks = array();
		foreach ($this->tracks as $r) {
			$tracks[] = 'add "'.format_for_mpd($r['TrackUri']).'"';
		}
		return $tracks;
	}
}

// #This is a comment
//
// http://uk1.internet-radio.com:15614/

// or

// #EXTM3U
// #EXTINF:duration,Artist - Album

class m3uFile {

	public function __construct($data, $url, $station, $image) {
		debuglog("New M3U Station ".$station,"RADIO PLAYLIST");
		$this->url = $url;
		$this->station = $station;
		$this->image = $image;
		$this->tracks = array();
		$prettystream = '';

		$parts = explode(PHP_EOL, $data);
		foreach ($parts as $line) {
			if (preg_match('/#EXTINF:(.*?),(.*?)$/', $line, $matches)) {
				$prettystream = $matches[2];
			} else if (preg_match('/^\#/', $line) || preg_match('/^\s*$/', $line)) {

			} else {
				$this->tracks[] = array('TrackUri' => trim($line), 'PrettyStream' => $prettystream);
			}
		}
	}

	public function updateDatabase() {
		$stationid = check_radio_station($this->url, $this->station, $this->image);
		if ($stationid) {
			check_radio_tracks($stationid, $this->tracks);
		} else {
			debuglog("ERROR! Null station ID!","RADIO",2);
			header('HTTP/1.1 417 Expectation Failed');
			exit(0);
		}
	}

	public function getTracksToAdd() {
		return array('load "'.format_for_mpd(htmlspecialchars_decode($this->url)).'"');
	}
	
	public function get_first_track() {
		$return = $this->tracks[0]['TrackUri'];
		foreach ($this->tracks as $track) {
			$ext = pathinfo($track['TrackUri'], PATHINFO_EXTENSION);
			if ($ext == 'pls' || $ext == 'm3u' || $ext == 'xspf' || $ext == 'asx') {
				$return = $track['TrackUri'];
				break;
			}
		}
		debuglog("  Checking ".$return,"RADIO_PLAYLIST");
		return $return;
	}
}

// [Reference]
// Ref1=http://wmlive-lracl.bbc.co.uk/wms/england/lrleicester?MSWMExt=.asf
// Ref2=http://212.58.252.33:80/wms/england/lrleicester?MSWMExt=.asf

class asfFile {

	public function __construct($data, $url, $station, $image) {
		$this->url = $url;
		$this->station = $station;
		$this->image = $image;
		$this->tracks = array();

		$parts = explode(PHP_EOL, $data);
		foreach ($parts as $line) {
			if (preg_match('/^Ref\d+=(.*)/', $line, $matches)) {
				$uri = trim($matches[1]);
				$this->tracks[] = array('TrackUri' => preg_replace('/http:/','mms:', $uri), 'PrettyStream' => '');
			}
		}
	}

	public function updateDatabase() {
		$stationid = check_radio_station($this->url, $this->station, $this->image);
		if ($stationid) {
			check_radio_tracks($stationid, $this->tracks);
		} else {
			debuglog("ERROR! Null station ID!","RADIO",2);
			header('HTTP/1.1 417 Expectation Failed');
			exit(0);
		}
	}

	public function getTracksToAdd() {
		return array('load "'.format_for_mpd(htmlspecialchars_decode($this->url)).'"');
	}

}

// Fallback - if it's not any of the above types, treat it as a single stream URL and see what happens.

class possibleStreamUrl {

	public function __construct($url, $station, $image) {
		$this->url = $url;
		$this->station = $station;
		$this->image = $image;
		$this->tracks = array(array('TrackUri' => $this->url, 'PrettyStream' => ''));
	}

	public function updateDatabase() {
		$stationid = check_radio_station($this->url, $this->station, $this->image);
		if ($stationid) {
			check_radio_tracks($stationid, $this->tracks);
		} else {
			debuglog("ERROR! Null station ID!","RADIO",2);
			header('HTTP/1.1 417 Expectation Failed');
			exit(0);
		}
	}

	public function getTracksToAdd() {
		return array('add "'.format_for_mpd(htmlspecialchars_decode($this->url)).'"');
	}
}

function checkStationAgain($currenttitle, $tracktitle) {
	// For MPD, we can get a station name from the pls file
	// For Mopidy, we'll let mopidy's scanner find one for us. This is more accurate
	global $prefs;
	$currenttitle = (string)$currenttitle;
	if (preg_match('/'.ROMPR_UNKNOWN_STREAM.'/', $currenttitle) && $prefs['player_backend'] == "mpd") {
		$a = preg_replace('/\(.*?\)/', '', $tracktitle);
		if ($a != '') {
			$currenttitle = $a;
		}
	}
	return $currenttitle;
}

?>
