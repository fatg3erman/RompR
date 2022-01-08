<?php
chdir('..');
include ('includes/vars.php');
if (preg_match('/^(\d+\.\d+)/', ROMPR_VERSION, $matches)) {
	if (file_exists('updateinfo/'.$matches[1].'/info.html')) {
		readfile('updateinfo/'.$matches[1].'/info.html');
		exit(0);
	}
}
header('HTTP/1.1 204 No Content');
?>