<?php

$a = "http://wsdownload.bbc.co.uk/worldservice/meta/live/shoutcast/mp3/einws.pls";
$ext = pathinfo($a, PATHINFO_EXTENSION);

print $ext . "\n";

?>
