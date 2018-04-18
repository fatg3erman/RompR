<?php

$files = glob('prefs/podcasts/10/{*.jpg,*.jpeg,*.JPEG,*.JPG,*.gif,*.GIF,*.png,*.PNG}', GLOB_BRACE);
print_r($files);

?>