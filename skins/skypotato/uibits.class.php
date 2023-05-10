<?php
class uibits extends ui_elements {
	public static function artistHeader($id, $name) {
		$h = '<div class="menu openmenu containerbox menuitem artist" name="'.$id.'">';
		$h .= '<div class="expand">'.$name.'</div>';
		$h .= '</div>';
		return $h;
	}

	public static function browse_artistHeader($id, $name) {
		return '';
	}

	public static function albumHeader($obj) {
		$obj = array_merge(self::DEFAULT_ALBUM_PARAMS, $obj);

		$h = '<div class="collectionitem fixed selecotron clearfix">';

		// In this skin, no albumheaders are playable except for radio channels, so anything with a
		// streamuri is not openable and anything without one is openable

		if ($obj['streamuri']) {
			$h .= '<div class="containerbox wrap clickstream playable draggable clickicon '.$obj['class'].'" name="'.rawurlencode($obj['streamuri']).'" streamname="'.$obj['streamname'].'" streamimg="'.$obj['streamimg'].'">';
		} else {
			if ($obj['plpath'])
				$h .= '<input type="hidden" name="dirpath" value="'.$obj['plpath'].'" />';

			$h .= '<div class="containerbox openmenu menu '.$obj['class'].'" name="'.$obj['id'].'">';
		}

		$h .= '<div class="helpfulalbum expand">';
		$albumimage = new baseAlbumImage(array('baseimage' => $obj['Image']));
		$h .= $albumimage->html_for_image($obj, 'jalopy', 'medium');

		if ($obj['podcounts'])
			$h .= $obj['podcounts'];

		$h .= '<div class="tagh albumthing">';
		$h .= '<div class="progressbar invisible wafflything"><div class="wafflebanger"></div></div>';
		$h .= '<div class="title-menu">';

		$h .= domainHtml($obj['AlbumUri']);
		$h .= artistNameHtml($obj);

		$h .= '</div>';
		$h .= '</div>';
		$h .= '</div>';
		$h .= '</div>';
		$h .= '</div>';

		return $h;
	}

	// public static function radioChooser($obj) {
	// 	return self::albumheader($obj);
	// }

	public static function radioChooser($obj) {
		$obj = array_merge(self::DEFAULT_ALBUM_PARAMS, $obj);
		$h = '<div class="collectionitem radiosection fixed">';
		$h .= '<div class="containerbox openmenu menuitem menu '.$obj['class'].'" name="'.$obj['id'].'">';
		$albumimage = new baseAlbumImage(array('baseimage' => $obj['Image']));
		$h .= $albumimage->html_for_image($obj, 'svg-square fixed', 'small');
		$h .= artistNameHtml($obj);
		// $h .= '<div class="expand">'.$obj['Albumname'].'</div>';
		$h .= '</div>';
		$h .= '</div>';
		return $h;
	}

	public static function albumControlHeader($fragment, $why, $what, $who, $artist, $playall = true) {
		// TODO Probably Don't Need This Bit Now
		if ($fragment || $who == 'root') {
			return '';
		}
		$html = self::ui_config_header([
			'label_text' => $artist
		]);
		if ($playall) {
			$html .= '<div class="textcentre clickalbum playable fullwidth noselect" name="'.$why.'artist'.$who.'">'.language::gettext('label_play_all').'</div>';
		}
		return $html;
	}

	public static function trackControlHeader($why, $what, $who, $when, $dets) {
		print self::make_track_control_buttons($why, $what, $who, $when, $dets);
	}

	public static function printDirectoryItem($fullpath, $displayname, $prefix, $dircount, $printcontainer = false) {
		$c = ($printcontainer) ? "searchdir" : "directory";
		print '<input type="hidden" name="dirpath" value="'.rawurlencode($fullpath).'" />';
		print '<div class="'.$c.' menu openmenu containerbox menuitem fullwidth" name="'.$prefix.$dircount.'">';
		print '<i class="icon-folder-open-empty fixed inline-icon"></i>';
		print '<div class="expand">'.htmlentities(urldecode($displayname)).'</div>';
		print '</div>';
		if ($printcontainer) {
			print '<div class="dropmenu" id="'.$prefix.$dircount.'">';
		}
	}

	public static function directoryControlHeader($prefix, $name = null, $icon = 'icon-folder-open-empty') {
		if ($name !== null && !preg_match('/^pholder_/', $prefix)) {
			print self::ui_config_header([
				'label_text' => $name,
				'lefticon' => $icon
			]);
		}
	}

	public static function printRadioDirectory($att, $closeit, $prefix) {
		$name = md5($att['URL']);
		print '<input type="hidden" value="'.rawurlencode($att['URL']).'" />';
		print '<input type="hidden" value="'.rawurlencode($att['text']).'" />';
		print '<div class="menu openmenu '.$prefix.' directory containerbox menuitem fullwidth" name="'.$prefix.'_'.$name.'">';
		print '<i class="icon-folder-open-empty fixed inline-icon"></i>';
		print '<div class="expand">'.$att['text'].'</div>';
		print '</div>';
		if ($closeit) {
			print '</div>';
		}
	}

	public static function playlistPlayHeader($name, $text) {
		print '<div class="textcentre clickloadplaylist playable ninesix" name="'.$name.'">'.language::gettext('label_play_all');
		print '<input type="hidden" name="dirpath" value="'.$name.'" />';
		print '</div>';
	}

	public static function albumSizer() {
		print '<div class="sizer"></div>';
	}

}
?>
