<?php
class ui_elements {

	const DEFAULT_TRACK_PARAMS = [
		'tags' => '',
		'rating' => 0,
		'ttid' => null,
		'title' => '',
		'trackno' => 0,
		'time' => 0,
		'lm' => 0,
		'disc' => 0,
		'uri' => null,
		'isSearchResult' => 0,
		'playable' => 0,
		'artist' => '',
		'trackartistindex' => null,
		'albumartistindex' => null,
		'lastplayed' => 0,
		'isaudiobook' => 0,
		'ismostrecent' => false,
		'numdiscs' => 1,
		'discclass' => 'disc1',
		'trackno_width' => 'tracks_0'
	];

	// Params for albumheader
	// openable     true if this has a dropdown (ie an album)
	// playable		true if this is a playable object
	// id 			either the id of a dropdown to open or an albumid, or null
	// Image		the album image
	// Searched 	1 if the album image should not be searched for
	// AlbumUri 	the album URI (for spotify)
	// Year			the album year
	// Albumname	the Album Name
	// Artistname	the Artist Name
	// why			the collection $why, or null if this is a non-collection object
	//				Both values make us skip creating the buttons and album mneu.
	// ImgKey		The image key or 'none'
	// streamuri	If this is a stream to play, the URI. streamname and streamimg must also be supplied
	// streamname	The name of the stream. Probably the same as Albumname?
	// streamimg	The image to use for the stream. Probably the same as Image?
	// plpath		something to do with playlists
	// userplaylist something to do with playlists
	// class		Any extra classes to be added to the container
	// podcounts	For a podcast, the HTML for the counts
	// extralines	Any extra lines of info to go underneath Artistname

	// NOTE - Radio channels are albumheader because they have an image, but they are always playbale
	// and NEVER openable. Podcast user an albumheader

	const DEFAULT_ALBUM_PARAMS = [
		'openable' => true,
		'playable' => true,
		'id' => null,
		'Image' => null,
		'Searched' => 1,
		'AlbumUri' => null,
		'Year' => null,
		'Albumname' => null,
		'Artistname' => '',
		'why' => null,
		'ImgKey' => 'none',
		'streamuri' => null,
		'streamname' => null,
		'streamimg' => null,
		'plpath' => null,
		'userplaylist' => null,
		'class' => '',
		'podcounts' => null,
		'extralines' => []
	];

	public static function albumTrack($data) {

		$data = array_merge(self::DEFAULT_TRACK_PARAMS, $data);

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

		print '<div class="tracknumber fixed '.$data['trackno_width'].'">';
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


	//
	// $why is collection key - eg 'a', 'b' etc
	//    Set it to '' or null to skip this bit
	// $what is the collection what eg 'album'
	// $who is the collection index eg for aalbum123 $who = 123
	// $when is the subkey for sort modes such as tag eg aalbum123_15 (=15)
	//

	protected static function make_track_control_buttons($why, $what, $who, $when, $det) {
		if ($why == '' || $why == null)
			return '';

		$det = array_merge(['buttons' => true, 'iconclass' => 'expand noselect'], $det);

		$db_album = ($when === null) ? $who : $who.'_'.$when;
		$iab = -1;
		$play_col_button = 'icon-music';
		if ($what == 'album' && ($why == 'a' || $why == 'z')) {
			$iab = prefs::$database->album_is_audiobook($who);
			$play_col_button = ($iab == 0) ? 'icon-music' : 'icon-audiobook';
		}
		$html = '';
		if ($det['buttons']) {
			$html .= '<div class="containerbox wrap album-play-controls">';
			if ($det['AlbumUri']) {
				$albumuri = rawurlencode($det['AlbumUri']);
				if (strtolower(pathinfo($albumuri, PATHINFO_EXTENSION)) == "cue") {
					$html .= '<div class="icon-no-response-playbutton track-control-icon expand playable clickcue noselect tooltip" name="'.$albumuri.'" title="'.language::gettext('label_play_whole_album').'"></div>';
				} else {
					$html .= '<div class="icon-no-response-playbutton track-control-icon expand clicktrack playable noselect tooltip" name="'.$albumuri.'" title="'.language::gettext('label_play_whole_album').'"></div>';
					$html .= '<div class="'.$play_col_button.' track-control-icon expand clickalbum playable noselect tooltip" name="'.$why.'album'.$who.'" title="'.language::gettext('label_from_collection').'"></div>';
				}
			} else {
				$html .= '<div class="'.$play_col_button.' track-control-icon expand clickalbum playable noselect tooltip" name="'.$why.'album'.$who.'" title="'.language::gettext('label_from_collection').'"></div>';
			}
			$html .= '<div class="icon-single-star track-control-icon expand clickicon clickalbum playable noselect tooltip" name="ralbum'.$db_album.'" title="'.language::gettext('label_with_ratings').'"></div>';
			$html .= '<div class="icon-tags track-control-icon expand clickicon clickalbum playable noselect tooltip" name="talbum'.$db_album.'" title="'.language::gettext('label_with_tags').'"></div>';
			$html .= '<div class="icon-ratandtag track-control-icon expand clickicon clickalbum playable noselect tooltip" name="yalbum'.$db_album.'" title="'.language::gettext('label_with_tagandrat').'"></div>';
			$html .= '<div class="icon-ratortag track-control-icon expand clickicon clickalbum playable noselect tooltip" name="ualbum'.$db_album.'" title="'.language::gettext('label_with_tagorrat').'"></div>';
		}

		$classes = array();
		if ($why != 'b') {
			if (prefs::$database->num_collection_tracks($who) == 0) {
				$classes[] = 'clickamendalbum clickremovealbum';
			}
			if ($iab == 0) {
				$classes[] = 'clicksetasaudiobook';
			} else if ($iab == 2) {
				$classes[] = 'clicksetasmusiccollection';
			}

			if (array_key_exists('useTrackIms', $det)) {
				if ($det['useTrackIms'] == 1) {
					$classes[] = 'clickunusetrackimages';
				} else {
					$classes[] = 'clickusetrackimages';
				}
			}
		}

		if (!$det['buttons']) {
			if ($det['AlbumUri']) {
				$classes[] = 'clickalbumoptions';
			} else {
				$classes[] = 'clickcolloptions';
			}
		}

		if ($why == 'b' && $det['AlbumUri'] && preg_match('/spotify:album:(.*)$/', $det['AlbumUri'], $matches)) {
			$classes[] = 'clickaddtollviabrowse clickaddtocollectionviabrowse';
			$spalbumid = $matches[1];
		} else {
			$spalbumid = '';
		}

		if (!$det['buttons'])
			$classes[] = 'clickratedtracks';

		if (count($classes) > 0) {
			$classes[] = $det['iconclass'];
			$html .= '<div class="icon-menu track-control-icon clickable clickicon clickalbummenu '
					.implode(' ',$classes).'" db_album="'.$db_album.'" why="'.$why.'" who="'.$who.'" spalbumid="'.$spalbumid;

			if (in_array('clickalbumoptions', $classes))
				$html .= '" uri="'.rawurlencode($det['AlbumUri']);

			$html .= '"></div>';
		}

		if ($det['buttons'])
			$html .= '</div>';

		return $html;
	}

	// id is the pref parameter for which this is a select box
	// If id is null this will not be a pref box, if it is it will
	// have an id of [id]selector
	// options is an array of key => value pairs
	//  key = the select value
	//  value = the text to display
	// This is a confusing way round but it's obvious in the code
	// that that is the logical way for it to be
	// disabled is an aray of keys from the above that should
	// be in the list but disabled
	const DEFAULT_SELECT_BOX = [
		'id' => null,
		'options' => [],
		'selected' => null,
		'disabled' => [],
		'label' => null,
		'class' => '',
		'typeclass' => 'saveomatic'
	];

	public static function ui_select_box($opts) {
		$opts = array_merge(self::DEFAULT_SELECT_BOX, $opts);
		if ($opts['label']) {
			print '<div class="pref containerbox vertical-centre';
			if ($opts['class'] != '')
				print ' '.$opts['class'];

			print '">';
			print '<div class="divlabel">'			;
			print $opts['label'];
			print '</div>';
		} else {
			print '<div class="fullwidth containerbox vertical-centre brick_wide';
			if ($opts['class'] != '')
				print ' '.$opts['class'];

			print '">';
		}
		print '<div class="selectholder expand">';
		if ($opts['id']) {
			print '<select id="'.$opts['id'].'selector"';
			if ($opts['typeclass'])
				print ' class="'.$opts['typeclass'].'"';

			print '>';
		} else {
			print '<select>';
		}
		foreach ($opts['options'] as $value => $text) {
			print '<option value="'.$value.'"';
			if ($value == $opts['selected'])
				print ' selected';

			if (in_array($value, $opts['disabled']))
				print ' disabled';

			print '>'.$text.'</option>';
		}
		print '</select>';
		print '</div>';
		print '</div>';

	}

	const DEFAULT_TEXTENTRY = [
		'label' => null,
		'size' => 255,
		'id' => null,
		'type' => 'text',
		'class' => '',
		'is_array' => false
	];

	public static function ui_textentry($opts) {
		$opts = array_merge(self::DEFAULT_TEXTENTRY, $opts);

		if ($opts['size'] < 20) {
			print '<div class="pref containerbox vertical-centre';
			if ($opts['class'] != '')
				print ' '.$opts['class'];

			print '">';
			print '<div class="expand">'.language::gettext($opts['label']).'</div>';
			print '<input class="saveotron fixed';
			if ($opts['is_array'])
				print ' arraypref';

			print '" id="'.$opts['id'].'" type="'.$opts['type'].'" size="'.$opts['size'].'" ';
			print 'style="margin-left: 1em;width: '.($opts['size']+1).'em" />';
		} else {
			print '<div class="pref';
			if ($opts['class'] != '')
				print ' '.$opts['class'];

			print '">';
			if ($opts['label'])
				print language::gettext($opts['label']);

			print '<input class="saveotron prefinput';
			if ($opts['is_array'])
				print ' arraypref';

			print '" id="'.$opts['id'].'" type="'.$opts['type'].'" size="'.$opts['size'].'" />';
		}

		print '</div>';

	}

	const DEFAULT_CHECKBOX = [
		'id' => null,
		'label' => null,
		'class' => '',
		'typeclass' => 'autoset toggle'
	];
	// This will create a holder div and then as many select boxes as are specified
	// in $opts. $opts can be an array with just one checkbox details, or an array
	// of arrays of checkboxes
	// class is applied to the outer container and only needs to be defined in the first entry
	public static function ui_checkbox($opts) {
		if (!array_key_exists(0, $opts)) {
			$opts = [$opts];
		}

		foreach ($opts as $i => $box) {
			$opts[$i] = array_merge(self::DEFAULT_CHECKBOX, $box);
		}

		print '<div class="pref styledinputs';
		if ($opts[0]['class'] != '')
			print ' '.$opts[0]['class'];

		print '">';
		foreach ($opts as $box) {
			print '<input';
			if ($box['typeclass'])
				print ' class="'.$box['typeclass'].'"';

			print ' type="checkbox" id="'.$box['id'].'" />';
			print '<label for="'.$box['id'].'">'.language::gettext($box['label']).'</label>';
		}
		print '</div>';
	}

	const DEFAULT_RADIO = [
		'typeclass' => 'topcheck savulon',
		'name' => null,
		'id' => null,
		'label' => null,
		'value' => null,
		'class' => ''
	];

	public static function ui_radio($opts) {
		if (!array_key_exists(0, $opts)) {
			$opts = [$opts];
		}

		foreach ($opts as $i => $box) {
			$opts[$i] = array_merge(self::DEFAULT_RADIO, $box);
		}

		print '<div class="pref styledinputs';
		if ($opts[0]['class'] != '')
			print ' '.$opts[0]['class'];

		print '">';

		foreach ($opts as $box) {
			print '<input';
			if ($box['typeclass'])
				print ' class="'.$box['typeclass'].'"';

			print ' type="radio" name="'.$box['name'].'" value="'.$box['value'].'" id="'.$box['id'].'" />';
			print '<label for="'.$box['id'].'">'.language::gettext($box['label']).'</label>';
		}
		print '</div>';

	}

	const DEFAULT_CONFIG_HEADER = [
		'lefticon' => null,
		'righticon' => null,
		'label' => null,
		'main_icon' => null,
		'class' => ''
	];

	public static function ui_config_header($opts) {
		$opts = array_merge(self::DEFAULT_CONFIG_HEADER, $opts);
		print '<div class="configtitle">';
		print '<i class="medicon';
		if ($opts['lefticon'])
			print ' '.$opts['lefticon'];

		print '"></i>';
		if ($opts['label']) {
			print '<div class="textcentre expand';
			if ($opts['class'] != '')
				print ' '.$opts['class'];

			print '"><b>'.language::gettext($opts['label']).'</b></div>';
		} else if ($opts['main_icon']) {
			print '<i class="expand alignmid '.$opts['main_icon'].'"></i>';
		}
		print '<i class="medicon';
		if ($opts['righticon'])
			print ' '.$opts['righticon'];

		print '"></i>';
		print '</div>';
	}

	const DEFAULT_BUTTON = [
		'label' => null,
		'onclick' => null,
		'name' => null
	];

	public static function ui_config_button($opts) {
		if (!array_key_exists(0, $opts)) {
			$opts = [$opts];
		}

		foreach ($opts as $i => $box) {
			$opts[$i] = array_merge(self::DEFAULT_BUTTON, $box);
		}

		print '<div class="containerbox textcentre vertical-centre center wrap">';
		foreach ($opts as $box) {
			print '<button class="expand config-button"';
			if ($box['onclick'])
				print ' onclick="'.$box['onclick'].'"';

			if ($box['name'])
				print ' name="'.$box['name'].'"';

			print '>';
			print language::gettext($box['label']);
			print '</button>';
		}
		print '</div>';

	}

}

?>