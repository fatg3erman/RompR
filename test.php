<?php

$a = 'podcast+http://thig.fdsfs/';
echo getDomain($a),PHP_EOL;

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

?>
