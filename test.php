<?php

// $a = 'spotify:album:this%20is%20an%20album';
$a = 'this/is/a/file.mp3';

$t = microtime(true);
for ($b = 0; $b < 100000; $b++) {
    // $arse = preg_replace('/^.+?:(track|album|artist):/', '', $a);
    $cock = explode(':', $a);
    if (count($cock) > 1) {
        $arse = array_pop($cock);
        print $arse."\n";
    } else {
        print "No"."\n";
    }
    
}
$n = microtime(true);

$took = $n - $t;

print "Took ".$took." seconds\n";

?>
