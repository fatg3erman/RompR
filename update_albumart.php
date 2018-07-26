<?php
include ("includes/vars.php");
include ("includes/functions.php");
include ("utils/imagefunctions.php");
include ("backends/sql/backend.php");
$na = 1;
$oa = 0;
switch (ROMPR_IMAGE_VERSION) {
    case 4:
        $na =  generic_sql_query("SELECT COUNT(Albumindex) AS NumAlbums FROM Albumtable WHERE Image LIKE 'albumart/small/%'", false, null, 'NumAlbums', 0);
        debuglog("There are ".$na." albums","AA_UPGRADE");
        
        $k = generic_sql_query("SELECT ImgKey FROM Albumtable WHERE Image LIKE 'albumart/small/%' AND ImgVersion < ".ROMPR_IMAGE_VERSION." LIMIT 1", false, null, 'ImgKey', null);
        if ($k) {
            // We're dealing with a specific version here, where all images are jpg.
            // We'll mimic the behaviour of baseAlbumImage at the point in time, so if it changes
            // in the future it doesn't mess this up.
            $source = "albumart/asdownloaded/".$k.".jpg";
            debuglog("Converting image ".$k,"AA_UPGRADE");
            $ih = new imageHandler($source);
            $ih->resizeToWidth(400);
            $ih->save("albumart/medium/".$k.".jpg", 70);
            $ih->resizeToWidth(100);
            $ih->save("albumart/small/".$k.".jpg", 75);
            $ih->destroy();
            generic_sql_query("UPDATE Albumtable SET ImgVersion = ".ROMPR_IMAGE_VERSION." WHERE ImgKey = '".$k."'");
        }
        
        $oa =  generic_sql_query("SELECT COUNT(ImgVersion) AS NumOldAlbums FROM Albumtable WHERE Image LIKE 'albumart/small/%' AND ImgVersion < ".ROMPR_IMAGE_VERSION, false, null, 'NumOldAlbums', 0);
        debuglog("There are ".$oa." albums with old-style album art","AA_UPGRADE");
        break;
}

if ($oa == 0) {
    print json_encode(array('percent' => 100));
} else {
    $pc = 100 - (($oa/$na)*100);
    debuglog("Done ".$pc." percent of album art","AA_UPGRADE");
    print json_encode(array('percent' => intval($pc)));
}

?>
