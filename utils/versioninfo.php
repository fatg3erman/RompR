<?php
chdir('..');
include ('includes/vars.php');
if (file_exists('updateinfo/'.ROMPR_VERSION.'/info.html')) {
    readfile('updateinfo/'.ROMPR_VERSION.'/info.html');
} else {
    header('HTTP/1.1 204 No Content');
}
?>