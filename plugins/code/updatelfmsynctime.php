<?php
chdir('../..');
include ("includes/vars.php");
$prefs['last_lastfm_synctime'] = time();
savePrefs();
header('HTTP/1.1 204 No Content');
?>