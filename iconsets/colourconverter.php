<?php

// Nasty, hacky, but very effective way to convert the New-Dark-Circled icontheme into a different colour.
// ONLY start with a CLEAN copy of New-Dark-Circled

$dir = 'Fiery';
# cyan
# $newcolour = '#3ED3D5';
# white
# $newcolour = '#FFFFFF';
# orange
$newcolours = array('#FFF663');
$files = glob($dir.'/*.svg');

foreach ($files as $file) {

    print $file."\n";
    $newcolour = $newcolours[rand(0, count($newcolours)-1)];
    $hack = file_get_contents($file);

    $hack = preg_replace('/stroke:#000000/', 'stroke:'.$newcolour, $hack);
    $hack = preg_replace('/fill:#000000/', 'fill:'.$newcolour, $hack);
    $hack = preg_replace('/fill:#010002/', 'fill:'.$newcolour, $hack);
    $hack = preg_replace('/fill:#020202/', 'fill:'.$newcolour, $hack);
    $hack = preg_replace('/fill:#333333/', 'fill:'.$newcolour, $hack);
    $hack = preg_replace('/fill="#000"/', 'fill="'.$newcolour.'"', $hack);
    $hack = preg_replace('/stroke="#000"/', 'stroke="'.$newcolour.'"', $hack);
    $hack = preg_replace('/(style="stroke-width:\d\.*\d*p*x*\%*)"/', '$1;fill:'.$newcolour.';fill-opacity:1"', $hack);

    $hack = preg_replace('/<\/svg>/', '<style id="stylebodge1" type="text/css">[id^="polygon"]{fill:'.$newcolour.';fill-opacity:1}</style></svg>', $hack);
    $hack = preg_replace('/<\/svg>/', '<style id="stylebodge2" type="text/css">[id^="path"]{fill:'.$newcolour.';fill-opacity:1}</style></svg>', $hack);

    file_put_contents($file, $hack);

}

?>
