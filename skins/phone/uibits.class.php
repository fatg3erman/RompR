<?php
class uibits extends ui_elements {
	public static function artistHeader($id, $name) {
		$h = '<div class="openmenu menu containerbox menuitem artist" name="'.$id.'">';
		$h .= '<div class="expand">'.$name.'</div>';
		$h .= '</div>';
		return $h;
	}

	public static function albumHeader($obj) {
		$h = '';
		if ($obj['id'] == 'nodrop') {
			// Hacky at the moment, we only use nodrop for streams but here there is no checking
			// because I'm lazy.
			$h .= '<div class="clickstream playable clickicon '.$obj['class'].'" name="'.rawurlencode($obj['streamuri']).'" streamname="'.$obj['streamname'].'" streamimg="'.$obj['streamimg'].'">';
		} else {
			if (array_key_exists('plpath', $obj)) {
				logger::debug('ALBUMHEADER','plpath is',$obj['plpath']);
				$h .= '<input type="hidden" name="dirpath" value="'.$obj['plpath'].'" />';
			}
			$h .= '<div class="openmenu menu '.$obj['class'].'" name="'.$obj['id'].'">';
		}
		$h .= '<div class="containerbox menuitem">';
		$h .= '<div class="smallcover fixed">';
		$albumimage = new baseAlbumImage(array('baseimage' => $obj['Image']));
		$h .= $albumimage->html_for_image($obj, 'smallcover fixed', 'small');
		$h .= '</div>';
		$h .= domainHtml($obj['AlbumUri']);
		$h .= artistNameHtml($obj);
		$h .= '</div>';

		if (array_key_exists('podcounts', $obj)) {
			$h .= $obj['podcounts'];
		}

		$h .= '</div>';
		$h .= '<div class="progressbar invisible wafflything"><div class="wafflebanger"></div></div>';
		$h .= '</div>';
		return $h;
	}

	public static function albumControlHeader($fragment, $why, $what, $who, $artist, $playall = true) {
		if ($fragment || $who == 'root') {
			return '';
		}
		$html = '<div class="menu backmenu openmenu" name="'.$why.$what.$who.'">';
		$html .='</div>';
		$html .= '<div class="vertical-centre configtitle fullwidth"><div class="textcentre expand"><b>'.$artist.'</b></div></div>';
		if ($playall) {
			$html .= '<div class="textcentre clickalbum playable ninesix noselect" name="'.$why.$what.$who.'">'.language::gettext('label_play_all').'</div>';
		}
		return $html;
	}

	public static function trackControlHeader($why, $what, $who, $when, $dets) {
		$db_album = ($when === null) ? $who : $who.'_'.$when;
		$html = '<div class="menu backmenu openmenu" name="'.$why.$what.$db_album.'"></div>';
		$iab = -1;
		$play_col_button = 'icon-music';
		if ($what == 'album' && ($why == 'a' || $why == 'z')) {
			$iab = prefs::$database->album_is_audiobook($who);
			$play_col_button = ($iab == 0) ? 'icon-music' : 'icon-audiobook';
		}
		foreach ($dets as $det) {
			$albumimage = new baseAlbumImage(array('baseimage' => $det['Image']));
			$images = $albumimage->get_images();
			$html .= '<div class="album-menu-header"><img class="album_menu_image" src="'.$images['asdownloaded'].'" /></div>';
			if ($why != '') {
				$html .= '<div class="containerbox wrap album-play-controls">';
				if ($det['AlbumUri']) {
					$albumuri = rawurlencode($det['AlbumUri']);
					if (strtolower(pathinfo($albumuri, PATHINFO_EXTENSION)) == "cue") {
						$html .= '<div class="icon-no-response-playbutton medicon expand playable clickcue noselect tooltip" name="'.$albumuri.'" title="'.language::gettext('label_play_whole_album').'"></div>';
					} else {
						$html .= '<div class="icon-no-response-playbutton medicon expand clicktrack playable noselect tooltip" name="'.$albumuri.'" title="'.language::gettext('label_play_whole_album').'"></div>';
						$html .= '<div class="'.$play_col_button.' medicon expand clickalbum playable noselect tooltip" name="'.$why.'album'.$who.'" title="'.language::gettext('label_from_collection').'"></div>';
					}
				} else {
					$html .= '<div class="'.$play_col_button.' medicon expand clickalbum playable noselect tooltip" name="'.$why.'album'.$who.'" title="'.language::gettext('label_from_collection').'"></div>';
				}
				$html .= '<div class="icon-single-star medicon expand clickicon clickalbum playable noselect tooltip" name="ralbum'.$db_album.'" title="'.language::gettext('label_with_ratings').'"></div>';
				$html .= '<div class="icon-tags medicon expand clickicon clickalbum playable noselect tooltip" name="talbum'.$db_album.'" title="'.language::gettext('label_with_tags').'"></div>';
				$html .= '<div class="icon-ratandtag medicon expand clickicon clickalbum playable noselect tooltip" name="yalbum'.$db_album.'" title="'.language::gettext('label_with_tagandrat').'"></div>';
				$html .= '<div class="icon-ratortag medicon expand clickicon clickalbum playable noselect tooltip" name="ualbum'.$db_album.'" title="'.language::gettext('label_with_tagorrat').'"></div>';
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
				}
				if (array_key_exists('useTrackIms', $det)) {
					if ($det['useTrackIms'] == 1) {
						$classes[] = 'clickunusetrackimages';
					} else {
						$classes[] = 'clickusetrackimages';
					}
				}
				if ($why == 'b' && $det['AlbumUri'] && preg_match('/spotify:album:(.*)$/', $det['AlbumUri'], $matches)) {
					$classes[] = 'clickaddtollviabrowse clickaddtocollectionviabrowse';
					$spalbumid = $matches[1];
				} else {
					$spalbumid = '';
				}
				if (count($classes) > 0) {
					$html .= '<div class="icon-menu medicon expand clickable clickicon clickalbummenu noselect '.implode(' ',$classes).'" name="'.$who.'" why="'.$why.'" spalbumid="'.$spalbumid.'"></div>';
				}

				$html .= '</div>';
			}
		}
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

	public static function directoryControlHeader($prefix, $name = null) {
		print '<div class="menu backmenu openmenu" name="'.trim($prefix, '_').'"></div>';
		if ($name !== null) {
			print '<div class="vertical-centre configtitle fullwidth"><div class="textcentre expand"><b>'.$name.'</b></div></div>';
		}
	}

	public static function printRadioDirectory($att, $closeit, $prefix) {
		$name = md5($att['URL']);
		print '<input type="hidden" value="'.rawurlencode($att['URL']).'" />';
		print '<input type="hidden" value="'.rawurlencode($att['text']).'" />';
		print '<div class="menu openmenu '.$prefix.' directory containerbox menuitem is-coverable" name="'.$prefix.'_'.$name.'">';
		print '<i class="icon-folder-open-empty fixed collectionitem"></i>';
		print '<div class="expand">'.$att['text'].'</div>';
		print '</div>';
		// print '<div id="'.$prefix.'_'.$name.'" class="dropmenu notfilled is-albumlist removeable">';
		if ($closeit) {
			print '</div>';
		}
	}

	public static function playlistPlayHeader($name, $text) {
		logger::log("UI", "Getting image for playlist",$name);
		$albumimage = new albumImage(array('artist' => "PLAYLIST", 'album' => $text));
		$image = $albumimage->get_image_if_exists();
		if ($image) {
			$images = $albumimage->get_images();
			print '<div class="album-menu-header"><img class="lazy album_menu_image" data-src="'.$images['asdownloaded'].'" /></div>';
		}
		print '<div class="textcentre clickloadplaylist playable ninesix" name="'.$name.'">'.language::gettext('label_play_all');
		// logger::log('PLAYLISTPLAYHDR','name is',$name);
		print '<input type="hidden" name="dirpath" value="'.$name.'" />';
		print '</div>';
	}

	public static function albumSizer() {
		print '<div class="sizer"></div>';
	}

}
?>
