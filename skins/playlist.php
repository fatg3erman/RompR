<div id="horse" class="fullwidth">
<table width="100%"><tr><td align="left" style="width:17px">
<?php
print '<i id="giblets" style="margin-left:4px" onclick="togglePlaylistButtons()" title="'.get_int_text('button_playlistcontrols').
	'" class="icon-menu playlisticon clickicon lettuce"></i>';
?>
</td>
<td align="left" id="pltracks"></td>
<td align="right" id="pltime"></td>
<td align="right" style="width:17px">
<?php
print '<i title="'.get_int_text('button_clearplaylist').'" class="icon-trash playlisticon clickicon lettuce clear_playlist"></i>';
?>
</td>
</tr>
<tr><td colspan="4" align="center"><div id="plmode"></div></td></tr>
</table>
</div>
<div id="playlistbuttons" class="invisible">
<?php
if ($prefs['player_backend'] == "mpd") {
	// Different layout for playlist controls for each player
	// since mopidy say they're unlikely ever to support crossfade or replay gain
?>
<table width="90%" align="center">
<tr>
<?php
print '<td width="50%" align="right">'.
		'<div class="togglecontainer">'.
			'<div class="togglediv tgtl">'.get_int_text('button_random').'</div>'.
			'<div class="togglebutton clickicon icon-toggle-off" id="random"></div>'.
		'</div>'.
	'</td>';
print '<td width="50%" align="left">'.
		'<div class="togglecontainer">'.
			'<div class="togglebutton clickicon icon-toggle-off" id="crossfade" '.
				'onclick="player.controller.toggleCrossfade()"></div>'.
			'<div class="togglediv tgtr">'.get_int_text('button_crossfade').'</div>'.
		'</div>'.
	'</td>';
print '</tr><tr>';
print '<td width="50%" align="right">'.
		'<div class="togglecontainer">'.
			'<div class="togglediv tgtl">'.get_int_text('button_repeat').'</div>'.
			'<div class="togglebutton clickicon icon-toggle-off" id="repeat"></div>'.
		'</div>'.
	'</td>';
print '<td width="50%" align="left">'.
		'<div class="togglecontainer">'.
			'<div class="togglebutton clickicon icon-toggle-off" id="consume"></div>'.
			'<div class="togglediv tgtr">'.get_int_text('button_consume').'</div>'.
		'</div>'.
	'</td>';
?>
</tr>
</table><hr>
<table width="90%" align="center">
	<tr>
		<td rowspan="2">
			<div class="togglecontainer"><div class="togglediv tgtl">REPLAY GAIN</div></div>
		</td>
		<td align="center">
			<div class="togglecontainer">
				<div class="togglediv">Off</div>
			</div>
		</td>
		<td align="center">
			<div class="togglecontainer">
				<div class="togglediv">Track</div>
			</div>
		</td>
		<td align="center">
			<div class="togglecontainer">
				<div class="togglediv">Album</div>
			</div>
		</td>
		<td align="center">
			<div class="togglecontainer">
				<div class="togglediv">Auto</div>
			</div>
		</td>
	</tr>
	<tr>
		<td align="center">
			<div class="togglecontainer">
				<div class="togglebutton clickicon clickreplaygain icon-toggle-off"
					id="replaygain_off"></div>
			</div>
		</td>
		<td align="center">
			<div class="togglecontainer">
				<div class="togglebutton clickicon clickreplaygain icon-toggle-off"
					id="replaygain_track"></div>
			</div>
		</td>
		<td align="center">
			<div class="togglecontainer">
				<div class="togglebutton clickicon clickreplaygain icon-toggle-off"
					id="replaygain_album"></div>
			</div>
		</td>
		<td align="center">
			<div class="togglecontainer">
				<div class="togglebutton clickicon clickreplaygain icon-toggle-off"
					id="replaygain_auto"></div>
			</div>
		</td>
	</tr>
</table>
<?php
} else {
	print '<table width="90%" align="center"><tr>';
	print '<td align="center"><div class="togglecontainer"><div class="togglediv">'.
		get_int_text('button_random').'</div></td>';
	print '<td align="center"><div class="togglecontainer"><div class="togglediv">'.
		get_int_text('button_repeat').'</div></td>';
	print '<td align="center"><div class="togglecontainer"><div class="togglediv">'.
		get_int_text('button_consume').'</div></td>';
	print '</tr><tr>';
	print '<td align="center"><div class="togglecontainer">
		<div class="togglebutton clickicon icon-toggle-off" id="random"></div></div></td>';
	print '<td align="center">
		<div class="togglecontainer">
		<div class="togglebutton clickicon icon-toggle-off" id="repeat"></div></div></td>';
	print '<td align="center">
		<div class="togglecontainer">
		<div class="togglebutton clickicon icon-toggle-off" id="consume"></div></div></td>';
	print '</tr></table>';
}
?>
</div>
<div id="pscroller">
    <div id="sortable" class="noselection">
    </div>
</div>
