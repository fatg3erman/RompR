<?php

// Nasty, hacky, but very effective way to convert the New-Dark-Circled icontheme into a different colour.
// ONLY start with a CLEAN copy of New-Dark-Circled, or Fiery (see lower down) or Bobalophagus-Dark.
// Comment out the preg-replace lines you don't need

$dir = 'Bobalophatrator';
# cyan
# $newcolour = '#3ED3D5';
# white
# $newcolour = '#FFFFFF';
# orange
$newcolour = '#EEEEEE';
// Highlight colour for hover over media control buttons, blobdown, and alarm on - Bobalophagus only
$newhighlight = '#FF4d00';

// Fiery Colours:
// 255, 246, 99 to 251, 134, 38

// Purple 186,62,145 to 202,145,190
//  BA3E91 to CA91BE


$files = glob($dir.'/*.svg');

foreach ($files as $file) {

    print $file."\n";
    $hack = file_get_contents($file);

    // Highlight for Bobalophagus-Dark - do these first
    // $hack = preg_replace('/fill:#464646/', 'fill:'.$newhighlight, $hack);
    // $hack = preg_replace('/stroke:#ff4d00/', 'stroke:'.$newhighlight, $hack);

    // These for New-Dark-Circled AND Bobalophagus-Dark
    // $hack = preg_replace('/stroke:#000000/', 'stroke:'.$newcolour, $hack);
    // $hack = preg_replace('/fill:#000000/', 'fill:'.$newcolour, $hack);
    // $hack = preg_replace('/fill:#010002/', 'fill:'.$newcolour, $hack);
    // $hack = preg_replace('/fill:#020202/', 'fill:'.$newcolour, $hack);
    // $hack = preg_replace('/fill:#333333/', 'fill:'.$newcolour, $hack);
    // $hack = preg_replace('/fill="#000"/', 'fill="'.$newcolour.'"', $hack);
    // $hack = preg_replace('/stroke="#000"/', 'stroke="'.$newcolour.'"', $hack);
    // $hack = preg_replace('/(style="stroke-width:\d\.*\d*p*x*\%*)"/', '$1;fill:'.$newcolour.';fill-opacity:1"', $hack);

    // These are for Bobalophagus-Dark
    // $hack = preg_replace('/stroke:#1e1e1e/', 'stroke:'.$newcolour, $hack);
    // $hack = preg_replace('/fill:#1e1e1e/', 'fill:'.$newcolour, $hack);
    // $hack = preg_replace('/stroke:#1E1E1E/', 'stroke:'.$newcolour, $hack);
    // $hack = preg_replace('/fill:#1E1E1E/', 'fill:'.$newcolour, $hack);
    // $hack = preg_replace('/stop-color:#1e1e1e/', 'stop-color:'.$newcolour, $hack);

    // These are needed for New-Dark-Cricled
    // $hack = preg_replace('/<\/svg>/', '<style id="stylebodge1" type="text/css">[id^="polygon"]{fill:'.$newcolour.';fill-opacity:1}</style></svg>', $hack);
    // $hack = preg_replace('/<\/svg>/', '<style id="stylebodge2" type="text/css">[id^="path"]{fill:'.$newcolour.';fill-opacity:1}</style></svg>', $hack);

    // These lines convert the gradient as used in Fiery or Bobalofire
    $hack = preg_replace('/#fff663/i', '#CA91BE', $hack);
    $hack = preg_replace('/#fb8626/i', '#BA3E91', $hack);
    $hack = preg_replace('/#fb8a26/i', '#BA3E91', $hack);

    // These lines convert the gradient as used in Slush-Dark.
    // Don't forget to edit (or remove) the #pset and #pmaxset in theme.css
    // $hack = preg_replace('/#656565/i', '#654321', $hack);
    // $hack = preg_replace('/#000000/i', '#123456', $hack);


    file_put_contents($file, $hack);

}

?>
