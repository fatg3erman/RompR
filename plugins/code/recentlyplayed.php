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
	if ($obj['playdate'] != $date) {
		$date = $obj['playdate'];
		print '<tr class="tagh plugin_rpl_datetag"><th colspan="3">'.$date.'</th></tr>';
	}
	print '<tr class="draggable clicktrack playable spacerboogie" name="'.rawurlencode($obj['Uri']).'">';
	print '<td><div class="smallcover"><img class="smallcover';
	if ($obj['Image']) {
		print '" src="'.$obj['Image'];
	} else {
		print ' notfound';
	}
	print '" /></div></td>';
	print '<td class="dan"><b>'.$obj['Title'].'</b><br><i>by</i> <b>'.$obj['Artistname'].'</b><br><i>on</i> <b>'.$obj['Albumname'].'</b></td>';
	print '<td class="dan">'.$obj['playtime'].'</td>';
	print '</tr>';
}
print '</table>';
?>
