<?php

if (($d = return_a_value(3)) !== false) {
    echo $d, PHP_EOL;
}

function return_a_value($v) {
    return $v;
}

?>
