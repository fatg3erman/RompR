<?php
chdir('..');
$LastLine = "";
if (file_exists('prefs/backupmonitor')) {
	if ($fp = fopen('prefs/backupmonitor', 'r')) {
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