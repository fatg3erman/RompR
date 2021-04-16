<?php
class uibits {
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
		// Outer container
		if ($data['playable'] == 1 or $data['playable'] == 3) {
			// Note - needs clicktrack and name in case it is a removeable track
			print '<div class="unplayable clicktrack ninesix indent containerbox padright" name="'.rawurlencode($data['uri']).'">';
		} else if ($data['uri'] == null) {
			print '<div class="playable '.$class.' ninesix draggable indent containerbox padright" name="'.$data['ttid'].'">';
		} else {
			print '<div class="playable '.$class.' ninesix draggable indent containerbox padright" name="'.rawurlencode($data['uri']).'">';
		}

		print domainIcon($d, 'collectionicon');

		// Track Number
		if ($data['numtracks'] > 0) {
			print '<div class="tracknumber fixed" style="width:'.strlen($data['numtracks']).'em">';
			if ($data['trackno'] > 0)
				print $data['trackno'];

			print '</div>';
		}

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
			print '<i class="icon-tags collectionicon"></i>'.$data['tags'];
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
			$button_class = "icon-plus playlisticonr fixed clickable clickicon invisibleicon clicktrackmenu";
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

	public static function artistHeader($id, $name) {
		$h = '<div class="clickalbum playable draggable containerbox menuitem" name="'.$id.'">';
		$h .= '<i class="icon-toggle-closed menu mh openmenu fixed artist" name="'.$id.'"></i>';
		$h .= '<div class="expand">'.$name.'</div>';
		$h .= '</div>';
		return $h;
	}

	public static function noAlbumsHaeder() {
		print '<div class="playlistrow2" style="padding-left:64px">'.
			language::gettext("label_noalbums").'</div>';
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
				$h .= '<div class="icon-menu playlisticonr fixed clickable clickicon clickalbummenu '.implode(' ',$classes).'" name="'.$id.'" who="'.$albumid.'" why="'.$obj['why'].'" spalbumid="'.$spalbumid.'" uri="'.rawurlencode($obj['AlbumUri']).'"></div>';
			}
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
		print '<i class="icon-folder-open-empty fixed collectionicon"></i>';
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
		print '<i class="icon-folder-open-empty fixed collectionicon"></i>';
		print '<div class="expand">'.$att['text'].'</div>';
		print '</div>';
		// print '<div id="'.$prefix.'_'.$name.'" class="dropmenu notfilled is-albumlist removeable">';
		if ($closeit) {
			print '</div>';
		}
	}

	public static function playlistPlayHeader($name, $text) {

	}

	public static function addPodcastCounts($html, $extra) {
		$out = phpQuery::newDocument($html);
		$out->find('.menuitem')->append($extra);
		return $out;
	}

	public static function addUserRadioButtons($html, $index, $uri, $name, $image) {
		$out = phpQuery::newDocument($html);
		$extra = '<div class="fixed clickable clickradioremove clickicon yourradio" name="'.$index.'"><i class="icon-cancel-circled playlisticonr"></i></div>';
		$out->find('.menuitem')->append($extra);
		return $out;
	}

	public static function addPlaylistControls($html, $delete, $is_user, $name) {
		$out = phpQuery::newDocument($html);
		if ($delete) {
			$add = ($is_user) ? "user" : "";
			$h = '<div class="fixed containerbox vertical">';
			$h .= '<i class="icon-floppy expand collectionicon clickable clickicon clickrename'.$add.'playlist"></i>';
			$h .= '<input type="hidden" value="'.$name.'" />';
			$h .= '<i class="icon-cancel-circled fixed collectionicon clickable clickicon clickdelete'.$add.'playlist"></i>';
			$h .= '<input type="hidden" value="'.$name.'" />';
			$h .= '</div>';
			$out->find('.menuitem')->append($h);
		}
		return $out;
	}

	public static function albumSizer() {
		// print '<div class="sizer"></div>';
	}
}
?>
