<?php
class uibits extends ui_elements {

	public const ONLY_PLUGINS_ON_MENU = false;
	public const SNAPCAST_IN_VOLUME = true;

	public static function artistHeader($id, $name) {
		$h = '<div class="openmenu menu containerbox menuitem artist" name="'.$id.'">';
		$h .= '<div class="expand">'.$name.'</div>';
		$h .= '</div>';
		return $h;
	}

	public static function albumHeader($obj) {
		$obj = array_merge(self::DEFAULT_ALBUM_PARAMS, $obj);
		$h = '';

		// In this skin, no albumheaders are playable except for radio channels, so anything with a
		// streamuri is not openable and anything without one is openable

		if ($obj['streamuri']) {
			$h .= '<div class="clickstream playable clickicon '.$obj['class'].'" name="'.rawurlencode($obj['streamuri']).'" streamname="'.$obj['streamname'].'" streamimg="'.$obj['streamimg'].'">';
		} else {
			if ($obj['plpath'])
				$h .= '<input type="hidden" name="dirpath" value="'.$obj['plpath'].'" />';

			$h .= '<div class="openmenu menu '.$obj['class'].'" name="'.$obj['id'].'">';
		}
		$h .= '<div class="containerbox menuitem">';
		$h .= '<div class="smallcover fixed">';
		$albumimage = new baseAlbumImage(array('baseimage' => $obj['Image']));
		$h .= $albumimage->html_for_image($obj, 'smallcover fixed', 'small');
		$h .= '</div>';
		$h .= domainHtml($obj['AlbumUri']);
		$h .= artistNameHtml($obj);

		if ($obj['podcounts'])
			$h .= $obj['podcounts'];

		$h .= '</div>';
		$h .= '<div class="progressbar invisible wafflything"><div class="wafflebanger"></div></div>';
		$h .= '</div>';
		return $h;
	}

	public static function radioChooser($obj) {
		return self::albumHeader($obj);
	}

	public static function albumControlHeader($fragment, $why, $what, $who, $artist, $playall = true) {
		if ($fragment || $who == 'root') {
			return '';
		}
		$html = '<div class="menu backmenu openmenu" name="'.$why.$what.$who.'">';
		$html .='</div>';
		$html .= uibits::ui_config_header([
			'label_text' => $artist,
			'lefticon' => self::get_header_icon($why, $what)
		]);
		if ($playall) {
			$html .= '<div class="textcentre clickalbum playable ninesix noselect" name="'.$why.$what.$who.'">'.language::gettext('label_play_all').'</div>';
		}
		return $html;
	}

	private static function get_header_icon($why, $what) {
		if ($what == 'artist' || $what == 'album') {
			switch ($why) {
				case 'a':
					return 'icon-music';
					break;

				case 'b':
					return 'icon-search';
					break;

				case 'z':
					return 'icon-audiobook';
					break;
			}
		}
		return null;
	}

	public static function trackControlHeader($why, $what, $who, $when, $dets) {
		$db_album = ($when === null) ? $who : $who.'_'.$when;
		$html = '<div class="menu backmenu openmenu" name="'.$why.$what.$db_album.'"></div>';
		if ($dets['Albumname'])
			$html .= uibits::ui_config_header([
				'label_text' => $dets['Albumname'],
				'lefticon' => self::get_header_icon($why, $what)
			]);

		$albumimage = new baseAlbumImage(array('baseimage' => $dets['Image']));
		$images = $albumimage->get_images();
		$html .= '<div class="album-menu-header"><img class="album_menu_image" src="'.$images['asdownloaded'].'" /></div>';
		$html .= self::make_track_control_buttons($why, $what, $who, $when, $dets);
		print $html;
	}

	public static function printDirectoryItem($fullpath, $displayname, $prefix, $dircount, $printcontainer = false) {
		$c = ($printcontainer) ? "searchdir" : "directory";
		print '<input type="hidden" name="dirpath" value="'.rawurlencode($fullpath).'" />';
		print '<div class="'.$c.' openmenu menu containerbox menuitem" name="'.$prefix.$dircount.'">';
		print '<i class="icon-folder-open-empty fixed collectionitem"></i>';
		print '<div class="expand">'.htmlentities(urldecode($displayname)).'</div>';
		print '</div>';
		if ($printcontainer) {
			print '<div class="dropmenu" id="'.$prefix.$dircount.'"><div class="menu backmenu openmenu" name="'.$prefix.$dircount.'"></div>';
		}
	}

	public static function directoryControlHeader($prefix, $name = null, $icon = 'icon-folder-open-empty') {
		print '<div class="menu backmenu openmenu" name="'.trim($prefix, '_').'"></div>';
		if ($name !== null)
			print uibits::ui_config_header(['label_text' => $name, 'iconsize' => 'smallicon', 'lefticon' => $icon]);

	}

	public static function printRadioDirectory($att, $closeit, $prefix) {
		$name = md5($att['URL']);
		print '<input type="hidden" value="'.rawurlencode($att['URL']).'" />';
		print '<input type="hidden" value="'.rawurlencode($att['text']).'" />';
		print '<div class="menu openmenu '.$prefix.' directory containerbox menuitem is-coverable" name="'.$prefix.'_'.$name.'">';
		print '<i class="icon-folder-open-empty fixed collectionitem"></i>';
		print '<div class="expand">'.$att['text'].'</div>';
		print '</div>';
		if ($closeit) {
			print '</div>';
		}
	}

	public static function playlistPlayHeader($name, $text) {
		$albumimage = new albumImage(array('artist' => "PLAYLIST", 'album' => $text));
		$image = $albumimage->get_image_if_exists();
		if ($image) {
			$images = $albumimage->get_images();
			print '<div class="album-menu-header"><img class="lazy album_menu_image" data-src="'.$images['asdownloaded'].'" /></div>';
		}
		print '<div class="textcentre clickloadplaylist playable ninesix" name="'.$name.'">'.language::gettext('label_play_all');
		print '<input type="hidden" name="dirpath" value="'.$name.'" />';
		print '</div>';
	}

	public static function albumSizer() {
		print '<div class="sizer"></div>';
	}

	public static function ui_pre_nowplaying_icons() {
		print '<div id="playcount" class="topstats"></div>';
		print '<div id="dbtags" class="invisible topstats">'."\n";
		print '</div>';
	}

	public static function ui_post_nowplaying_icons() {
	}

}
?>
