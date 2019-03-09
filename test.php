<?php

require_once ("includes/vars.php");
$logger->setLevel(8);
$t = time();
$prefs['player_backend'] = 'mopidy';
$prefs['currenthost'] = 'Mopidy';
require_once ("includes/functions.php");
require_once ("player/".$prefs['player_backend']."/player.php");
$initmem = memory_get_usage();
debuglog("Memory Used is ".$initmem,"COLLECTION",4);
$player = new $PLAYER_TYPE();
$dirs = array('Local media/Albums');
while (count($dirs) > 0) {
	$dir = array_shift($dirs);
	foreach ($player->parse_list_output('lsinfo "'.format_for_mpd($dir).'"', $dirs, false) as $filedata) {
		print_r($filedata);
	}
}
$peakmem = memory_get_peak_usage();
$ourmem = $peakmem - $initmem;
debuglog("Peak Memory Used Was ".number_format($peakmem)." bytes  - meaning we used ".number_format($ourmem)." bytes.","COLLECTION",4);
$tim = time()-$t;
debuglog("We took ".$tim." seconds");
debuglog("======================================================================","TIMINGS",4);

?>
