<?php

class playlistpanel extends uibits {

	public static function make_playlist_panel() {

		print '<div id="playlist_top" class="fixed fullwidth">';
		print '<table width="100%"><tr><td align="left" class="smallicon">';
		print '<i style="margin-left:4px" title="'.language::gettext('button_playlistcontrols').'" class="icon-menu smallicon clickicon openmenu tooltip" name="playlistbuttons"></i>';
		print '</td>';
		print '<td align="left" id="pltracks"></td>';
		print '<td align="right" id="pltime"></td>';
		print '<td align="right" class="smallicon">';
		print '<i title="'.language::gettext('button_clearplaylist').'" class="icon-trash smallicon clickicon tooltip spinable clear_playlist"></i>';
		print '</td>';
		print '</tr>';
		print '<tr><td colspan="4" align="center"><div id="plmode"></div></td></tr>';
		print '</table>';

		// print '<div id="plmode" class="fullwidth"></div>';

		print '<div id="playlist-progress-holder" class="containerbox vertical-centre">';
		print '<div id="playlist-progress" class="expand"></div>';
		print '<div id="playlist-time-remaining" class="fixed"></div>';
		print '</div>';

		print '</div>';
		print '<div id="playlistbuttons" class="fixed toggledown invisible">';

		print '<div id="flowcontrols" class="noborder containerbox">';
		print '<i id="interrupt" class="icon-interrupt tooltip inline-icon clickicon expand" title="'.language::gettext('label_interrupt').'"></i>';
		print '<i id="random" class="icon-random tooltip inline-icon clickicon expand flow-off" title="'.language::gettext('button_random').'"></i>';
		if (prefs::get_pref('player_backend') == "mpd") {
			print '<i id="crossfade" class="icon-crossfade tooltip inline-icon clickicon expand flow-off" title="'.language::gettext('button_crossfade').'"></i>';
		}
		print '<i id="repeat" class="icon-repeat tooltip inline-icon clickicon expand flow-off" title="'.language::gettext('button_repeat').'"></i>';
		print '<i id="consume" class="icon-consume tooltip inline-icon clickicon expand flow-off" title="'.language::gettext('button_consume').'"></i>';
		print '</div>';

		if (prefs::get_pref('player_backend') == "mpd") {
			print '<table width="90%" align="center">';
				print '<tr>';
					print '<td rowspan="2">';
						print '<div class="togglecontainer"><div class="togglediv tgtl">REPLAY GAIN</div></div>';
					print '</td>';
					print '<td align="center">';
						print '<div class="togglecontainer">';
							print '<div class="togglediv">Off</div>';
						print '</div>';
					print '</td>';
					print '<td align="center">';
						print '<div class="togglecontainer">';
							print '<div class="togglediv">Track</div>';
						print '</div>';
					print '</td>';
					print '<td align="center">';
						print '<div class="togglecontainer">';
							print '<div class="togglediv">Album</div>';
						print '</div>';
					print '</td>';
					print '<td align="center">';
						print '<div class="togglecontainer">';
							print '<div class="togglediv">Auto</div>';
						print '</div>';
					print '</td>';
				print '</tr>';
				print '<tr>';
					print '<td align="center">';
						print '<div class="togglecontainer">';
							print '<div class="togglebutton clickicon clickreplaygain icon-toggle-off" id="replaygain_off"></div>';
						print '</div>';
					print '</td>';
					print '<td align="center">';
						print '<div class="togglecontainer">';
							print '<div class="togglebutton clickicon clickreplaygain icon-toggle-off" id="replaygain_track"></div>';
						print '</div>';
					print '</td>';
					print '<td align="center">';
						print '<div class="togglecontainer">';
							print '<div class="togglebutton clickicon clickreplaygain icon-toggle-off" id="replaygain_album"></div>';
						print '</div>';
					print '</td>';
					print '<td align="center">';
						print '<div class="togglecontainer">';
							print '<div class="togglebutton clickicon clickreplaygain icon-toggle-off" id="replaygain_auto"></div>';
						print '</div>';
					print '</td>';
				print '</tr>';
			print '</table>';
		}
		print '</div>';

		print '<div id="pscroller" class="expand">';
			print '<div id="sortable" class="noselection is-albumlist">';
			print '</div>';
		print '</div>';
	}
}

?>


