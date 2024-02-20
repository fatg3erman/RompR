<?php
$outputdata = array();
$player = new player();
if ($player->is_connected()) {
	$outputs = $player->get_outputs();
	foreach ($outputs as $i => $n) {
		if ($i != 'attribute') {
			if (is_array($n)) {
				foreach ($n as $a => $b) {
					logger::debug("AUDIO OUTPUT", $i,"-",$b.":".$a);
					$outputdata[$a][$i] = $b;
				}
			} else {
				logger::debug("AUDIO OUTPUT", $i,"-",$n);
				$outputdata[0][$i] = $n;
			}
		}
	}
}
$player = null;

function printOutputCheckboxes() {
	global $outputdata;
	for ($i = 0; $i < count($outputdata); $i++) {
		print '<div class="styledinputs">';
		print '<input type="checkbox" id="outputbutton_'.$i.'"';
		if ($outputdata[$i]['outputenabled'] == 1) {
			print ' checked';
		}
		print "><label for=\"outputbutton_${i}\" onclick=\"player.controller.doOutput(${i})\">";
		if ( ! preg_match('/^http[s]*:\/\/.+$/', $outputdata[$i]['outputname'], $matches) ) { print $outputdata[$i]['outputname']; }
		else { print $outputdata[$i]['outputname'] . "	<a href=\"". $outputdata[$i]['outputname'] . "\" target=\"_blank\"> &#128279;</a></label>"; }
		print '</div>';
	}
}

?>
