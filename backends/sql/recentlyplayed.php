<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("backends/sql/backend.php");

print '<table align="center" style="border-collapse:collapse;width:90%">';
$date = '';
$result = generic_sql_query(sql_recently_played(), false, PDO::FETCH_OBJ);
foreach ($result as $obj) {
	$thisdate = date('l, jS F Y', $obj->unixtime);
	if ($thisdate != $date) {
		$date = $thisdate;
		print '<tr class="tagh datetag"><th colspan="3">'.$date.'</th></tr>';
	}
	print '<tr class="infoclick draggable clickable clicktrack spacerboogie" name="'.rawurlencode($obj->Uri).'">';
	print '<td width="40px"><img class="smallcover';
	if ($obj->Image) {
		print '" src="'.$obj->Image;
	} else if (file_exists('albumart/small/'.$obj->ImgKey.'.jpg')) {
		print '" src="albumart/small/'.$obj->ImgKey.'.jpg"';
	} else {
		print ' notfound';
	}
	print '" /></td>';
	print '<td class="dan"><b>'.$obj->Title.'</b><br><i>by</i> <b>'.$obj->Artistname.'</b><br><i>on</i> <b>'.$obj->Albumname.'</b></td>';
	print '<td class="dan">'.date('H:i', $obj->unixtime).'</td>';
	print '</tr>';
}
print '</table>';
?>