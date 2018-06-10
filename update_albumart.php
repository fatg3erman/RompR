<?php
include ("includes/vars.php");
include ("includes/functions.php");
include ("utils/imagefunctions.php");
include ("backends/sql/backend.php");
$na =  generic_sql_query("SELECT COUNT(Albumindex) AS NumAlbums FROM Albumtable WHERE Image LIKE 'albumart/small/%'", false, null, 'NumAlbums', 0);
debuglog("There are ".$na." albums","AA_UPGRADE");

$k = generic_sql_query("SELECT ImgKey FROM Albumtable WHERE Image LIKE 'albumart/small/%' AND ImgVersion < ".ROMPR_IMAGE_VERSION." LIMIT 1", false, null, 'ImgKey', null);
if ($k) {
    $convert_path = find_executable('convert');
    debuglog("Converting image ".$k,"AA_UPGRADE");
    $source = "albumart/asdownloaded/".$k.".jpg";
    $dest = "albumart/medium/".$k.".jpg";
    $r = exec( $convert_path."convert \"".$source."\" -quality 70 -thumbnail 400x400 -alpha remove \"".$dest."\" 2>&1", $o);
    generic_sql_query("UPDATE Albumtable SET ImgVersion = ".ROMPR_IMAGE_VERSION." WHERE ImgKey = '".$k."'");
}

$oa =  generic_sql_query("SELECT COUNT(ImgVersion) AS NumOldAlbums FROM Albumtable WHERE Image LIKE 'albumart/small/%' AND ImgVersion < ".ROMPR_IMAGE_VERSION, false, null, 'NumOldAlbums', 0);
debuglog("There are ".$oa." albums with old-style album art","AA_UPGRADE");

if ($oa == 0) {
    print json_encode(array('percent' => 100));
} else {
    $pc = 100 - (($oa/$na)*100);
    debuglog("Done ".$pc." percent of album art","AA_UPGRADE");
    print json_encode(array('percent' => intval($pc)));
}

?>
