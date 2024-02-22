<?php

class uibits extends ui_elements {

	public static function artistHeader($id, $name) {
		$h = '<div class="clickalbum playable draggable containerbox menuitem" name="'.$id.'">';
		$h .= '<i class="icon-toggle-closed menu mh openmenu fixed artist" name="'.$id.'"></i>';
		$h .= '<div class="expand">'.$name.'</div>';
		$h .= '</div>';
		return $h;
	}

	public static function albumHeader($obj) {
		$obj = array_merge(self::DEFAULT_ALBUM_PARAMS, $obj);
		$h = '';
		if ($obj['playable'] === false) {
			$h .= '<div class="containerbox menuitem">';
		} else if ($obj['AlbumUri']) {
			if (strtolower(pathinfo($obj['AlbumUri'], PATHINFO_EXTENSION)) == "cue") {
				$h .= '<div class="clickcue playable draggable containerbox menuitem '.$obj['class'].'" name="'.rawurlencode($obj['AlbumUri']).'">';
			} else {
				$h .= '<div class="clicktrack playable draggable containerbox menuitem '.$obj['class'].'" name="'.rawurlencode($obj['AlbumUri']).'">';
			}
		} else if ($obj['streamuri']) {
			$h .= '<div class="clickstream playable draggable containerbox menuitem '.$obj['class'].'" name="'.rawurlencode($obj['streamuri']).'" streamname="'.$obj['streamname'].'" streamimg="'.$obj['streamimg'].'">';
		} else if ($obj['userplaylist']) {
			$h .= '<div class="playable '.$obj['userplaylist'].' draggable containerbox menuitem '.$obj['class'].'" name="'.$obj['plpath'].'">';
		} else {
			$h .= '<div class="clickalbum playable draggable containerbox menuitem '.$obj['class'].'" name="'.$obj['id'].'">';
		}
		if ($obj['plpath'])
			$h .= '<input type="hidden" name="dirpath" value="'.$obj['plpath'].'" />';

		if ($obj['openable'])
			$h .= '<i class="icon-toggle-closed menu openmenu mh fixed '.$obj['class'].'" name="'.$obj['id'].'"></i>';

		$h .= '<div class="smallcover fixed">';
		$albumimage = new baseAlbumImage(array('baseimage' => $obj['Image']));
		$h .= $albumimage->html_for_image($obj, 'smallcover', 'small');
		$h .= '</div>';

		$h .= domainHtml($obj['AlbumUri']);

		$h .= artistNameHtml($obj);

		if ($obj['id'] !== null) {
			$a = preg_match('/(a|b|c|r|t|y|u|z|x)(.*?)(\d+|root)_*(\d+)*/', $obj['id'], $matches);
			if ($a) {
				$what = $matches[2];
				$who = $matches[3];
				$when = (array_key_exists(4, $matches)) ? $matches[4] : null;
				$h .= self::make_track_control_buttons(
					$obj['why'],
					$what,
					$who,
					$when,
					array_merge($obj, ['buttons' => false, 'iconclass' => 'fixed']),
					false
				);
			}
		}

		if ($obj['podcounts'])
			$h .= $obj['podcounts'];

		$h .= '</div>';
		return $h;
	}

	public static function radioChooser($obj) {
		return self::albumHeader($obj);
	}

	public static function albumControlHeader($fragment, $why, $what, $who, $artist, $playall = true) {
		return '';
	}

	public static function trackControlHeader($why, $what, $who, $when, $dets) {
	}

	public static function printDirectoryItem($fullpath, $displayname, $prefix, $dircount, $printcontainer = false) {
		$c = ($printcontainer) ? "searchdir" : "directory";
		print '<div class="'.$c.' clickalbum playable draggable containerbox menuitem" name="'.$prefix.$dircount.'">';
		print '<input type="hidden" name="dirpath" value="'.rawurlencode($fullpath).'" />';
		print '<i class="icon-toggle-closed menu openmenu mh fixed '.$c.'" name="'.$prefix.$dircount.'"></i>';
		print '<i class="icon-folder-open-empty fixed inline-icon"></i>';
		print '<div class="expand">'.htmlentities(urldecode($displayname)).'</div>';
		print '</div>';
		if ($printcontainer) {
			print '<div class="dropmenu" id="'.$prefix.$dircount.'">';
		}
	}

	public static function directoryControlHeader($prefix, $a = null, $b = null) {

	}

	public static function printRadioDirectory($att, $closeit, $prefix) {
		$name = md5($att['URL']);
		print '<div class="directory containerbox menuitem">';
		print '<input type="hidden" value="'.rawurlencode($att['URL']).'" />';
		print '<input type="hidden" value="'.rawurlencode($att['text']).'" />';
		print '<i class="openmenu mh menu directory '.$prefix.' fixed icon-toggle-closed" name="'.$prefix.'_'.$name.'"></i>';
		print '<i class="icon-folder-open-empty fixed inline-icon"></i>';
		print '<div class="expand">'.$att['text'].'</div>';
		print '</div>';
		if ($closeit) {
			print '</div>';
		}
	}

	public static function playlistPlayHeader($name, $text) {

	}

	public static function albumSizer() {
		// print '<div class="sizer"></div>';
	}

	public static function prefs_hide_panels() {
		self::ui_checkbox(['id' => 'hidebrowser', 'label' => 'config_hidebrowser']);
	}

}
?>
