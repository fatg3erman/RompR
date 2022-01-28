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
		$h = '';
		if ($obj['why'] === null) {
			$h .= '<div class="containerbox menuitem">';
		} else if ($obj['AlbumUri'] && strtolower(pathinfo($obj['AlbumUri'], PATHINFO_EXTENSION)) == "cue") {
			logger::log("UI", "Cue Sheet found for album ".$obj['Albumname']);
			$h .= '<div class="clickcue playable draggable containerbox menuitem" name="'.rawurlencode($obj['AlbumUri']).'">';
		} else if (array_key_exists('streamuri', $obj)) {
			$h .= '<div class="clickstream playable draggable containerbox menuitem" name="'.rawurlencode($obj['streamuri']).'" streamname="'.$obj['streamname'].'" streamimg="'.$obj['streamimg'].'">';
		} else if (array_key_exists('userplaylist', $obj)) {
			$h .= '<div class="playable '.$obj['userplaylist'].' draggable containerbox menuitem" name="'.$obj['plpath'].'">';
		} else {
			$h .= '<div class="clickalbum playable draggable containerbox menuitem" name="'.$obj['id'].'">';
		}
		if (array_key_exists('plpath', $obj)) {
			$h .= '<input type="hidden" name="dirpath" value="'.$obj['plpath'].'" />';
		}
		if ($obj['id'] != 'nodrop') {
			$h .= '<i class="icon-toggle-closed menu openmenu mh fixed '.$obj['class'].'" name="'.$obj['id'].'"></i>';
		}

		$h .= '<div class="smallcover fixed">';
		$albumimage = new baseAlbumImage(array('baseimage' => $obj['Image']));
		$h .= $albumimage->html_for_image($obj, 'smallcover', 'small');
		$h .= '</div>';

		$h .= domainHtml($obj['AlbumUri']);

		$h .= artistNameHtml($obj);

		$h .= '</div>';
		if ($obj['why'] == "a" || $obj['why'] == "z" || $obj['why'] == 'b') {
			$id = preg_replace('/^.album/','',$obj['id']);
			$albumid = preg_replace('/_\d+$/','',$id);
			$iab = prefs::$database->album_is_audiobook($albumid);
			$classes = array();
			if ($obj['why'] != 'b') {
				if (prefs::$database->num_collection_tracks($albumid) == 0) {
					$classes[] = 'clickamendalbum clickremovealbum';
				}
				if ($iab == 0) {
					$classes[] = 'clicksetasaudiobook';
				} else if ($iab == 2) {
					$classes[] = 'clicksetasmusiccollection';
				}
			}
			if (array_key_exists('useTrackIms', $obj)) {
				if ($obj['useTrackIms'] == 1) {
					$classes[] = 'clickunusetrackimages';
				} else {
					$classes[] = 'clickusetrackimages';
				}
			}
			if ($obj['AlbumUri']) {
				$classes[] = 'clickalbumoptions';
			} else {
				$classes[] = 'clickcolloptions';
			}
			if ($obj['why'] == 'b' && $obj['AlbumUri'] && preg_match('/spotify:album:(.*)$/', $obj['AlbumUri'], $matches)) {
				$classes[] = 'clickaddtollviabrowse clickaddtocollectionviabrowse';
				$spalbumid = $matches[1];
			} else {
				$spalbumid = '';
			}
			$classes[] = 'clickratedtracks';
			if (count($classes) > 0) {
				$h .= '<div class="icon-menu inline-icon fixed clickable clickicon clickalbummenu '.implode(' ',$classes).'" name="'.$id.'" who="'.$albumid.'" why="'.$obj['why'].'" spalbumid="'.$spalbumid.'" uri="'.rawurlencode($obj['AlbumUri']).'"></div>';
			}
		}

		if (array_key_exists('podcounts', $obj)) {
			$h .= $obj['podcounts'];
		}

		$h .= '</div>';
		return $h;
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

	public static function directoryControlHeader($prefix) {

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
}
?>
