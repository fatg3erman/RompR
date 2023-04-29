<?php
class ui_elements {

	public const ONLY_PLUGINS_ON_MENU = false;
	public const SNAPCAST_IN_VOLUME = false;

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
	// year_always	true to always display Year regardless of sortbydate pref. Year will NOT
	//					be wrapped in () if this is true. TuneIn uses it for eg <br />(Podcast Episode)

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
		'extralines' => [],
		'year_always' => false
	];

	public static function albumTrack($data, $bookmarks) {

		$data = array_merge(self::DEFAULT_TRACK_PARAMS, $data);

		// if (substr($data['title'],0,7) == "Artist:") {
		// 	logger::warn('ALBUMTRACK', 'Found artist link in album - this should not be here!');
		// 	return 1;
		// }

		$d = getDomain($data['uri']);

		if (prefs::get_pref('player_backend') == "mpd" && $d == "soundcloud") {
			$class = 'clickcue';
		} else {
			$class = 'clicktrack';
		}
		$class .= $data['discclass'];

		if ($data['ismostrecent'])
			$class .= ' mostrecent';

		// Outer container
		if ($data['playable'] == 1 || $data['playable'] == 3 || $data['playable'] == 4) {
			// Note - needs clicktrack and name in case it is a removeable track
			print '<div class="unplayable clicktrack ninesix indent containerbox vertical-centre" name="'.rawurlencode($data['uri']).'">';
		} else if ($data['uri'] == null) {
			print '<div class="playable '.$class.' ninesix draggable indent containerbox vertical-centre" name="'.$data['ttid'].'">';
		} else {
			print '<div class="playable '.$class.' ninesix draggable indent containerbox vertical-centre" name="'.rawurlencode($data['uri']).'">';
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
			print '<div class="fixed trackrating">';
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
			if ($data['lm'] === null && $data['isSearchResult'] < 2) {
				$button_class .= ' clickremovedb';
			}
			// foreach ($bookmarks as $book) {
			// 	if ($book['Name'] == 'Resume') {
			// 		$button_class .= ' clickresetresume';
			// 		break;
			// 	}
			// }
			if ($d == 'youtube' || $d == 'yt') {
				$button_class .= ' clickyoutubedl';
			}
			$enc_tags = ($data['tags']) ? rawurlencode($data['tags']) : '';
			print '<div class="'.$button_class.'" rompr_id="'.$data['ttid'].'" rompr_tags="'.$enc_tags.'"></div>';
		}

		print '</div>';

		foreach ($bookmarks as $mark) {
			uibits::resume_bar($mark['Bookmark'], $data['time'], $mark['Name'], rawurlencode($data['uri']), 'local');
		}

		return 0;
	}

	public static function browse_artistHeader($id, $name) {
		return self::artistHeader($id, $name);
	}

	public static function noAlbumsHeader() {
		print '<div class="playlistrow2" style="padding-left:64px">'.language::gettext("label_noalbums").'</div>';
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
			$html .= '<div class="containerbox wrap album-play-controls vertical-centre">';
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
			if (prefs::$database->num_youtube_tracks($who) > 0)
				$classes[] = 'clickytdownloadall';
		}

		if (!$det['buttons']) {
			if ($det['AlbumUri']) {
				$classes[] = 'clickalbumoptions';
			} else {
				$classes[] = 'clickcolloptions';
			}
		}

		if ($why == 'b' && $det['AlbumUri'] &&
			(
				strpos($det['AlbumUri'], 'ytmusic:album:') !== false ||
				strpos($det['AlbumUri'], 'spotify:album:') !== false ||
				strpos($det['AlbumUri'], 'youtube:playlist:') !== false ||
				strpos($det['AlbumUri'], 'yt:playlist:') !== false
			)
		){
			$classes[] = 'clickaddtocollectionviabrowse';
			$classes[] = 'clickaddtollviabrowse';
		}

		if (!$det['buttons'])
			$classes[] = 'clickratedtracks';

		if (count($classes) > 0) {
			$classes[] = $det['iconclass'];
			$html .= '<div class="icon-menu inline-icon track-control-icon clickable clickicon clickalbummenu '
					.implode(' ',$classes).'" db_album="'.$db_album.'" why="'.$why.'" who="'.$who.'" aname="'.rawurlencode($det['Albumname']);

			if (in_array('clickalbumoptions', $classes) || in_array('clickaddtocollectionviabrowse', $classes) || in_array('clickaddtollviabrowse', $classes))
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
			print '<div class="fullwidth containerbox vertical-centre';
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
		'lefticon_name' => null,
		'righticon' => null,
		'label' => null,
		'main_icon' => null,
		'class' => '',
		'icon_size' => 'medicon',
		'label_text' => null,
		'title_class' => null,
		'id' => null
	];

	const HELP_LINKS = [
		'button_local_music' => 'https://fatg3erman.github.io/RompR/Music-Collection',
		'label_searchfor' => 'https://fatg3erman.github.io/RompR/Searching-For-Music',
		'button_internet_radio' => 'https://fatg3erman.github.io/RompR/Internet-Radio',
		'label_podcasts' => 'https://fatg3erman.github.io/RompR/Podcasts',
		'label_audiobooks' => 'https://fatg3erman.github.io/RompR/Spoken-Word',
		'label_pluginplaylists' => 'https://fatg3erman.github.io/RompR/Personalised-Radio',
		'config_players' => 'https://fatg3erman.github.io/RompR/Using-Multiple-Players',
		'icon-snapcast' => 'https://fatg3erman.github.io/RompR/snapcast',
		'icon-lastfm-logo' => 'https://fatg3erman.github.io/RompR/LastFM'
	];

	public static function ui_config_header($opts) {
		$opts = array_merge(self::DEFAULT_CONFIG_HEADER, $opts);
		$html = '';
		$html .= '<div class="configtitle';
		if ($opts['title_class'])
			$html .= ' '.$opts['title_class'];

		$html .= '"';
		if ($opts['id'])
			$html .= ' id="'.$opts['id'].'"';

		$html .= '>';
		$html .= '<i class="'.$opts['icon_size'];
		if ($opts['lefticon'])
			$html .= ' '.$opts['lefticon'];

		$html .= '"';
		if ($opts['lefticon_name'])
			$html .= ' name="'.$opts['lefticon_name'].'"';

		$html .= '></i>';
		if ($opts['label']) {
			$html .= '<div class="textcentre expand';
			if ($opts['class'] != '')
				$html .= ' '.$opts['class'];

			$html .= '"><b>'.language::gettext($opts['label']).'</b></div>';
		} else if ($opts['main_icon']) {
			$html .= '<i class="expand alignmid '.$opts['main_icon'].'"></i>';
		} else if ($opts['label_text']) {
			$html .= '<div class="textcentre expand';
			if ($opts['class'] != '')
				$html .= ' '.$opts['class'];

			$html .= '"><b>'.$opts['label_text'].'</b></div>';
		}

		if (array_key_exists($opts['label'], self::HELP_LINKS) && !$opts['righticon']) {
			$html .= '<a href="'.self::HELP_LINKS[$opts['label']].'" target="_blank">';
		}
		if (array_key_exists($opts['main_icon'], self::HELP_LINKS) && !$opts['righticon']) {
			$html .= '<a href="'.self::HELP_LINKS[$opts['main_icon']].'" target="_blank">';
		}
		$html .= '<i class="right-icon '.$opts['icon_size'];
		if ($opts['righticon']) {
			$html .= ' '.$opts['righticon'];
		} else if (array_key_exists($opts['label'], self::HELP_LINKS) ||
				   array_key_exists($opts['main_icon'], self::HELP_LINKS))
		{
			$html .= ' icon-info-circled';
		}

		$html .= '"></i>';
		if ((array_key_exists($opts['label'], self::HELP_LINKS) ||
 		    array_key_exists($opts['main_icon'], self::HELP_LINKS)) && !$opts['righticon'])
		{
			$html .= '</a>';
		}
		$html .= '</div>';
		return $html;
	}

	const DEFAULT_BUTTON = [
		'label' => null,
		'onclick' => null,
		'name' => null,
		'typeclass' => 'config-button'
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
			print '<button class="expand '.$box['typeclass'].'"';
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

	public static function infopane_default_contents() {
		print '<div id="artistchooser" class="infotext noselection invisible"></div>';
		print '<div id="artistinformation" class="infotext">';
		print '<h2 class="infobanner" align="center">'.language::gettext('label_emptyinfo').'</h2>';
		print '</div>';
		print '<div id="albuminformation" class="infotext"></div>';
		print '<div id="trackinformation" class="infotext"></div>';
	}

	const DEFAULT_PLUGINPLAYLISTS = [
		'class' => 'fullwidth'
	];

	public static function pluginplaylists_base($opts) {
		$opts = array_merge(self::DEFAULT_PLUGINPLAYLISTS, $opts);
		print self::ui_config_header([
			'lefticon' => 'icon-menu clickicon fixed openmenu',
			'lefticon_name' => 'smartradiobuttons',
			'label' => 'label_pluginplaylists',
			'icon_size' => 'smallicon'
		]);
		uibits::smartradio_options_box();
		if (prefs::get_pref('player_backend') == "mopidy") {
			print self::ui_config_header([
				'label' => 'label_mfyc'
			]);
		}
		/* Main Holder */
		print '<div class="'.$opts['class'].'" id="pluginplaylists"></div>';

		if (prefs::get_pref('player_backend') == "mopidy") {
			print uibits::ui_config_header([
				'label' => 'label_mfsp'
			]);
		}
		/* Music From Spotify */
		print '<div class="'.$opts['class'].'" id="pluginplaylists_spotify"></div>';

		if (prefs::get_pref('player_backend') == "mopidy") {
			print self::ui_config_header([
				'label' => 'label_mfe'
			]);
			print '<div id="radiodomains" class="pref"><b>Play From These Sources:</b></div>';
		}
		/* Music From Everywhere */
		print '<div class="'.$opts['class'].'" id="pluginplaylists_everywhere"></div>';
	}

	public static function main_play_buttons() {
		print '<i title="'.language::gettext('button_previous').
			'" class="fixed prev-button icon-fast-backward clickicon controlbutton-small tooltip"></i>';
		print '<i title="'.language::gettext('button_play').
			'" class="fixed play-button icon-play-circled shiftleft clickicon controlbutton tooltip"></i>';
		print '<i title="'.language::gettext('button_stop').
			'" class="fixed stop-button icon-stop-1 shiftleft2 clickicon controlbutton-small tooltip"></i>';
		print '<i title="'.language::gettext('button_stopafter').
			'" class="fixed stopafter-button icon-to-end-1 shiftleft3 clickicon controlbutton-small tooltip"></i>';
		print '<i title="'.language::gettext('button_next').
			'" class="fixed next-button icon-fast-forward shiftleft4 clickicon controlbutton-small tooltip"></i>';
	}

	public static function collection_options_box() {
		print '<div id="collectionbuttons" class="invisible toggledown is-coverable">';

		self::ui_select_box([
			'id' => 'sortcollectionby',
			'options' => array_map('ucfirst', array_map('language::gettext', COLLECTION_SORT_MODES))
		]);

		self::ui_select_box([
			'id' => 'collectionrange',
			'options' => array_map('language::gettext', COLLECTION_RANGE_OPTIONS)
		]);

		self::ui_checkbox(['id' => 'sortbydate', 'label' => 'config_sortbydate']);
		self::ui_checkbox(['id' => 'notvabydate', 'label' => 'config_notvabydate']);
		self::ui_config_button(['label' => 'config_updatenow', 'name' => 'donkeykong']);

		print'</div>';
	}

	public static function smartradio_options_box() {
		print '<div id="smartradiobuttons" class="invisible toggledown is-coverable">';
		self::ui_checkbox(['id' => 'smartradio_clearfirst', 'label' => 'config_clearfirst']);
		print'</div>';
	}

	public static function ui_pre_nowplaying_icons() {
		print '<div id="playcount" class="topstats"></div>';
	}

	public static function ui_nowplaying_icons() {
		print '<div id="stars" class="invisible topstats">';
		print '<i id="ratingimage" class="icon-0-stars rating-icon-big"></i>';
		print '<input type="hidden" value="-1" />';
		print '</div>';
		print '<div id="bookmark" class="invisible topstats">';
		print '<i title="'.language::gettext('button_bookmarks').'" class="icon-bookmark npicon clickicon tooltip"></i>';
		print '</div>';
		print '<div id="addtoplaylist" class="invisible topstats">';
		print '<i title="'.language::gettext('button_addtoplaylist').'" class="icon-doc-text npicon clickicon tooltip"></i>';
		print '</div>';
		print '<div id="lastfm" class="invisible topstats">';
		print '<i title="'.language::gettext('button_love').'" class="icon-heart npicon clickicon tooltip spinable" id="love"></i>';
		print '</div>';
		print '<div id="ban" class="invisible topstats">';
		print '<i title="'.language::gettext('button_ban').'" class="icon-block npicon clickicon tooltip"></i>';
		print '</div>';
		print '<div id="ptagadd" class="invisible topstats">';
		print '<i class="icon-tags npicon clickicon"></i>';
		print '</div>';
	}

	public static function ui_post_nowplaying_icons() {
		print '<div id="dbtags" class="invisible topstats">'."\n";
		print '</div>';
	}

	public static function prefs_hide_panels() {

	}

	public static function prefs_mouse_options() {
		print '<div class="kbdbits">';
		self::ui_textentry([
			'label' => 'config_wheelspeed',
			'size' => 4,
			'id' => 'wheelscrollspeed',
			'type' => 'number'
		]);
		self::ui_config_button([
			'label' => 'config_editshortcuts',
			'onclick' => 'shortcuts.edit()'
		]);
		print '</div>';
	}

	public static function prefs_touch_options() {

	}

	public static function resume_bar($pos, $length, $name, $uri, $type) {
		print '<input type="hidden" class="resumepos" value="'.$pos.'" />';
		print '<input type="hidden" class="length" value="'.$length.'" />';
		print '<input type="hidden" class="bookmark" value="'.$name.'" />';
		print '<input type="hidden" class="bkuri" value="'.$uri.'" />';
		print '<input type="hidden" class="type" value="'.$type.'" />';
	}

}

?>
