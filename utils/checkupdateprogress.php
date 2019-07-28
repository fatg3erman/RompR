<?php
// Reads the last line of the monitor file without loading the whole thing
// into memory (important on low memory systems with large collections)
chdir('..');
$LastLine = "Preparing...";
if (file_exists('prefs/monitor')) {
	$LastLine = '';
	if ($fp = fopen('prefs/monitor', 'r')) {
		fseek($fp, -1, SEEK_END);
		$pos = ftell($fp);
		// Loop backward util "\n" is found.
		if ($pos > 0) {
	    	fseek($fp, $pos--);
		}
		while((($C = fgetc($fp)) != "\n") && ($pos > 0)) {
	    	$LastLine = $C.$LastLine;
	    	fseek($fp, $pos--);
		}
		fclose($fp);
	}
}
print json_encode(array('current' => $LastLine));
?>