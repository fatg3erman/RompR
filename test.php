<?php

$url = 'prefs/podcats/10/20/gumboots';
$a = preg_match('#prefs/podcasts/(\d+)/(\d+)/(.*)$#', $url, $matches);
print implode(', ',$matches);


?>