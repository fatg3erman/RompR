<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
prefs::$database =  new database();
print '<table align="center" style="border-collapse:collapse;width:90%">';
$date = '';
$result = prefs::$database->sql_recently_played();
prefs::$database->close_database();
foreach ($result as $obj) {
	$thisdate = date('l, jS F Y', $obj->unixtime);
	if ($thisdate != $date) {
		$date = $thisdate;
		print '<tr class="tagh datetag"><th colspan="3">'.$date.'</th></tr>';
	}
	print '<tr class="draggable clicktrack playable spacerboogie" name="'.rawurlencode($obj->Uri).'">';
	print '<td width="40px"><img class="smallcover';
	if ($obj->Image) {
		print '" src="'.$obj->Image;
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
