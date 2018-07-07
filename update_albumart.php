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
            $source = "albumart/asdownloaded/".$k.".jpg";
            debuglog("Converting image ".$k,"AA_UPGRADE");
            if (extension_loaded('gd')) {
                $simpleimage = new SimpleImage($source);
                $simpleimage->resizeToWidth(400);
                $simpleimage->save("albumart/medium/".$k.".jpg", IMAGETYPE_JPEG, 70);
                $simpleimage->resizeToWidth(100);
                $simpleimage->save("albumart/small/".$k.".jpg", IMAGETYPE_JPEG, 75);
            } else {
                $convert_path = find_executable('convert');
                $dest = "albumart/medium/".$k.".jpg";
                $r = exec( $convert_path."convert \"".$source."\" -quality 70 -resize 400 -alpha remove \"".$dest."\" 2>&1", $o);
                $dest = "albumart/small/".$k.".jpg";
                $r = exec( $convert_path."convert \"".$source."\" -quality 75 -resize 100 -alpha remove \"".$dest."\" 2>&1", $o);
            }
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
