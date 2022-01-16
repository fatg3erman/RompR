<div id="playlist_top" class="fullwidth">
<table width="100%"><tr><td align="left" class="smallicon">
<?php
print '<i style="margin-left:4px" title="'.language::gettext('button_playlistcontrols').
	'" class="icon-menu smallicon clickicon openmenu tooltip" name="playlistbuttons"></i>';
?>
</td>
<td align="left" id="pltracks"></td>
<td align="right" id="pltime"></td>
<td align="right" class="smallicon">
<?php
print '<i title="'.language::gettext('button_clearplaylist').'" class="icon-trash smallicon clickicon tooltip spinable clear_playlist"></i>';
?>
</td>
</tr>
<tr><td colspan="4" align="center"><div id="plmode"></div></td></tr>
</table>
</div>
<div id="playlistbuttons" class="toggledown invisible">
<?php

print '<div id="flowcontrols" class="noborder containerbox">';
print '<i id="random" class="icon-random tooltip podicon clickicon expand flow-off" title="'.language::gettext('button_random').'"></i>';
if (prefs::$prefs['player_backend'] == "mpd") {
	print '<i id="crossfade" class="icon-crossfade tooltip podicon clickicon expand flow-off" title="'.language::gettext('button_crossfade').'"></i>';
}
print '<i id="repeat" class="icon-repeat tooltip podicon clickicon expand flow-off" title="'.language::gettext('button_repeat').'"></i>';
print '<i id="consume" class="icon-consume tooltip podicon clickicon expand flow-off" title="'.language::gettext('button_consume').'"></i>';
print '</div>';

if (prefs::$prefs['player_backend'] == "mpd") {
?>
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
}
?>
</div>
<div id="pscroller">
	<div id="sortable" class="noselection is-albumlist">
	</div>
</div>
