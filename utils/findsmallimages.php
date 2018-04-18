<?php
chdir('..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("utils/imagefunctions.php");
include ("backends/sql/backend.php");
$results = array();
$convert_path = find_executable("identify");
if ($convert_path !== false) {
	if ($r = generic_sql_query('SELECT Image, ImgKey FROM Albumtable')) {
		while ($obj = $r->fetch(PDO::FETCH_OBJ)) {
			$image = $obj->Image;
			if (dirname($image) == 'albumart/small') {
				$image = 'albumart/asdownloaded/'.basename($image);
				$size = get_image_dimensions($image);
				if ($size['width'] > -1) {
					// Should catch all the old small images we downloaded from Last.FM
					if ($size['height'] < 300) {
						debuglog("Image ".$image." is too small : ".$size['width'].'x'.$size['height'], "ALBUMART");
						array_push($results, $obj->ImgKey);
					}
				}
			}
		}
	}
}
header('Content-Type: application/json; charset=utf-8');
print json_encode($results);
?>