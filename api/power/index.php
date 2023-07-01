<?php

chdir('../..');

if (array_key_exists('off', $_REQUEST)) {
	exec('sudo /usr/sbin/shutdown -h now');
} else if (array_key_exists('restart', $_REQUEST)) {
	exec('sudo /usr/sbin/shutdown -r now');
}

?>