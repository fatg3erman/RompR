<?php

// Copy the files into iconfactory/[subdir]
// The original will be copied into the same icon dir
// and then new copies will be made for all the other colours

// Don't forget Colourful, and to edit the theme.css!

// New-Dark-Circled icons are 205mm square with a circle of 18mm

// Flat Colours

// Start with -
// Modern-Dark icon (0, 0, 0)
//  create
//      Modern-Light (FFFFFF)

// Bobalophagus-Dark icon (30, 30, 30) but black will work
//  create
//      Bobalophagus-Light (EEEEEE)
//      Bobalophagus-Orange (FF4D00)

// New-Dark-Circled icon (0, 0,0)
//  create
//      New-Light-Circled (FFFFFF)
//      New-Orange-Circled (FF4D00)
//      New-Cyan-Circled (3ED3D5)

// Gradients

// Fiery icon (circled) (gradient 255, 246,99 to 251, 134, 38)
//  create
//      Purpletrator (CA91BE to BA3E91)
//      Greydient (DDDDDD to 333333)

// Bobalofire icon (gradient 255, 246,99 to 251, 134, 38)
//  create
//      Bobalophatrator (CA91BE to BA3E91)

// Slush-Dark icon (radial gradient on centre 101, 101, 101 to 0, 0, 0)
//  create
//      Slush (FFFFFF to AAAAAA)
//      Slush-Fire (FFF663 to FB8626)
//      Slush-Purple (CA91BE to BA3E91)

$dirs = [
    'Modern-Dark' => 'convert_modern_dark',
    'Bobalophagus-Dark' => 'convert_bobalophagus_dark',
    'New-Dark-Circled' => 'convert_new_dark_circled',
    'Fiery' => 'convert_fiery',
    'Bobalofire' => 'convert_bobalofire',
    'Slush-Dark' => 'convert_slush_dark'
];

foreach ($dirs as $dir => $function) {

    $files = glob('iconfactory/'.$dir.'/*.svg');

    foreach ($files as $file) {
        print $file."\n";
        $function($file);
        copy ($file, $dir.'/'.basename($file));
    }
}

function convert_modern_dark($file) {
    $colours = [
        'Modern-Light' => '#ffffff'
    ];
    foreach ($colours as $dest => $newcolour) {
        print '  '.$dest."\n";
        $hack = file_get_contents($file);
        $hack = preg_replace('/stroke:#000000/', 'stroke:'.$newcolour, $hack);
        $hack = preg_replace('/fill:#000000/', 'fill:'.$newcolour, $hack);
        $hack = preg_replace('/fill:#010002/', 'fill:'.$newcolour, $hack);
        $hack = preg_replace('/fill:#020202/', 'fill:'.$newcolour, $hack);
        $hack = preg_replace('/fill:#333333/', 'fill:'.$newcolour, $hack);
        $hack = preg_replace('/fill="#000"/', 'fill="'.$newcolour.'"', $hack);
        $hack = preg_replace('/stroke="#000"/', 'stroke="'.$newcolour.'"', $hack);
        $hack = preg_replace('/(style="stroke-width:\d\.*\d*p*x*\%*)"/', '$1;fill:'.$newcolour.';fill-opacity:1"', $hack);
        file_put_contents($dest.'/'.basename($file), $hack);
    }
}

function convert_bobalophagus_dark($file) {
    $colours = [
        'Bobalophagus-Light' => '#eeeeee',
        'Bobalophagus-Orange' => '#ff4d00'
    ];
    foreach ($colours as $dest => $newcolour) {
        print '  '.$dest."\n";
        $hack = file_get_contents($file);
        $hack = preg_replace('/stroke:#000000/', 'stroke:'.$newcolour, $hack);
        $hack = preg_replace('/fill:#000000/', 'fill:'.$newcolour, $hack);
        $hack = preg_replace('/fill:#333333/', 'fill:'.$newcolour, $hack);
        $hack = preg_replace('/fill="#000"/', 'fill="'.$newcolour.'"', $hack);
        $hack = preg_replace('/stroke="#000"/', 'stroke="'.$newcolour.'"', $hack);
        $hack = preg_replace('/(style="stroke-width:\d\.*\d*p*x*\%*)"/', '$1;fill:'.$newcolour.';fill-opacity:1"', $hack);
        $hack = preg_replace('/stroke:#1e1e1e/', 'stroke:'.$newcolour, $hack);
        $hack = preg_replace('/fill:#1e1e1e/', 'fill:'.$newcolour, $hack);
        $hack = preg_replace('/stroke:#1E1E1E/', 'stroke:'.$newcolour, $hack);
        $hack = preg_replace('/fill:#1E1E1E/', 'fill:'.$newcolour, $hack);
        $hack = preg_replace('/stop-color:#1e1e1e/', 'stop-color:'.$newcolour, $hack);
        file_put_contents($dest.'/'.basename($file), $hack);
    }
}

function convert_new_dark_circled($file) {
    $colours = [
         'New-Light-Circled' => '#FFFFFF',
         'New-Orange-Circled' => '#FF4D00',
         'New-Cyan-Circled' => '#3ED3D5'
    ];
    foreach ($colours as $dest => $newcolour) {
        print '  '.$dest."\n";
        $hack = file_get_contents($file);
        $hack = preg_replace('/stroke:#000000/', 'stroke:'.$newcolour, $hack);
        $hack = preg_replace('/fill:#000000/', 'fill:'.$newcolour, $hack);
        $hack = preg_replace('/fill:#010002/', 'fill:'.$newcolour, $hack);
        $hack = preg_replace('/fill:#020202/', 'fill:'.$newcolour, $hack);
        $hack = preg_replace('/fill:#333333/', 'fill:'.$newcolour, $hack);
        $hack = preg_replace('/fill="#000"/', 'fill="'.$newcolour.'"', $hack);
        $hack = preg_replace('/stroke="#000"/', 'stroke="'.$newcolour.'"', $hack);
        $hack = preg_replace('/(style="stroke-width:\d\.*\d*p*x*\%*)"/', '$1;fill:'.$newcolour.';fill-opacity:1"', $hack);
        file_put_contents($dest.'/'.basename($file), $hack);
    }
}

function convert_fiery($file) {
    $colours = [
        'Purpletrator' => array('#CA91BE', '#BA3E91'),
        'Greydient' => array('#DDDDDD', '#333333')
    ];
    foreach ($colours as $dest => $newcolour) {
        print '  '.$dest."\n";
        $hack = file_get_contents($file);
        $hack = preg_replace('/#fff663/i', $newcolour[0], $hack);
        $hack = preg_replace('/#fb8626/i', $newcolour[1], $hack);
        $hack = preg_replace('/#fb8a26/i', $newcolour[1], $hack);
        file_put_contents($dest.'/'.basename($file), $hack);
    }

}

function convert_bobalofire($file) {
    $colours = [
        'Bobalophatrator' => array('#CA91BE', '#BA3E91')
    ];
    foreach ($colours as $dest => $newcolour) {
        print '  '.$dest."\n";
        $hack = file_get_contents($file);
        $hack = preg_replace('/#fff663/i', $newcolour[0], $hack);
        $hack = preg_replace('/#fb8626/i', $newcolour[1], $hack);
        $hack = preg_replace('/#fb8a26/i', $newcolour[1], $hack);
        file_put_contents($dest.'/'.basename($file), $hack);
    }

}

function convert_slush_dark($file) {
    $colours = [
         'Slush' => array('#FFFFFF', '#AAAAAA'),
         'Slush-Fire' => array('#FFF663', '#FB8626'),
         'Slush-Purple' => array('#CA91BE', '#BA3E91')
    ];
    foreach ($colours as $dest => $newcolour) {
        print '  '.$dest."\n";
        $hack = file_get_contents($file);
        $hack = preg_replace('/#656565/i', $newcolour[0], $hack);
        $hack = preg_replace('/#000000/i', $newcolour[1], $hack);
        file_put_contents($dest.'/'.basename($file), $hack);
    }

}

?>
