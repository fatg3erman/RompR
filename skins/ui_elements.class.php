<?php
class ui_elements {

	public static function albumTrack($data) {
		if (substr($data['title'],0,6) == "Album:") return 2;
		if (substr($data['title'],0,7) == "Artist:") {
			logger::warn('ALBUMTRACK', 'Found artist link in album - this should not be here!');
			return 1;
		}

		$d = getDomain($data['uri']);

		if (prefs::$prefs['player_backend'] == "mpd" && $d == "soundcloud") {
			$class = 'clickcue';
		} else {
			$class = 'clicktrack';
		}
		$class .= $data['discclass'];

		if ($data['ismostrecent'])
			$class .= ' mostrecent';

		// Outer container
		if ($data['playable'] == 1 or $data['playable'] == 3) {
			// Note - needs clicktrack and name in case it is a removeable track
			print '<div class="unplayable clicktrack ninesix indent containerbox" name="'.rawurlencode($data['uri']).'">';
		} else if ($data['uri'] == null) {
			print '<div class="playable '.$class.' ninesix draggable indent containerbox" name="'.$data['ttid'].'">';
		} else {
			print '<div class="playable '.$class.' ninesix draggable indent containerbox" name="'.rawurlencode($data['uri']).'">';
		}

		print domainIcon($d, 'inline-icon');

		print '<div class="tracknumber fixed '.$data['numtracks'].'">';
		if ($data['trackno'] > 0)
			print $data['trackno'];

		print '</div>';

		// Track Title, Artist, and Rating
		if ((string) $data['title'] == "") $data['title'] = urldecode($data['uri']);
		print '<div class="expand containerbox vertical">';

		if ($data['ismostrecent']) {
			print '<div class="fixed playlistrow2">'.language::gettext('label_upnext').'</div>';
		}

		print '<div class="fixed tracktitle">'.$data['title'].'</div>';
		if ($data['artist'] && $data['trackartistindex'] != $data['albumartistindex']) {
			print '<div class="fixed playlistrow2 trackartist">'.$data['artist'].'</div>';
		}
		if ($data['rating']) {
			print '<div class="fixed playlistrow2 trackrating">';
			print '<i class="icon-'.trim($data['rating']).'-stars rating-icon-small"></i>';
			print '</div>';
		}
		if ($data['tags']) {
			print '<div class="fixed playlistrow2 tracktags">';
			print '<i class="icon-tags inline-icon"></i>'.$data['tags'];
			print '</div>';
		}
		print '</div>';

		// Track Duration
		print '<div class="fixed playlistrow2 tracktime">';
		if ($data['time'] > 0) {
			print format_time($data['time']);
		}
		print '</div>';

		// Menu Button
		if ($data['ttid']) {
			$button_class = "icon-menu inline-icon fixed clickable clickicon invisibleicon clicktrackmenu spinable";
			if ($data['lm'] === null) {
				$button_class .= ' clickremovedb';
			}
			if ($data['progress'] > 0) {
				$button_class .= ' clickresetresume';
			}
			if ($d == 'youtube' || $d == 'yt') {
				$button_class .= ' clickyoutubedl';
			}
			print '<div class="'.$button_class.'" rompr_id="'.$data['ttid'].'" rompr_tags="'.rawurlencode($data['tags']).'"></div>';
		}

		print '</div>';

		if ($data['progress'] > 0) {
			print '<input type="hidden" class="resumepos" value="'.$data['progress'].'" />';
			print '<input type="hidden" class="length" value="'.$data['time'].'" />';
		}

		return 0;
	}

	public static function browse_artistHeader($id, $name) {
		return self::artistHeader($id, $name);
	}

	public static function noAlbumsHeader() {
		print '<div class="playlistrow2" style="padding-left:64px">'.
			language::gettext("label_noalbums").'</div>';
	}

}

?>