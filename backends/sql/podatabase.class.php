<?php
class poDatabase extends database {

	private function parse_rss_feed($url, $id = false, $lastpubdate = null, $gettracks = true) {
		$url = preg_replace('#^itpc://#', 'http://', $url);
		$url = preg_replace('#^feed://#', 'http://', $url);
		logger::mark("PARSE_RSS", "Getting Feed ".$url);
		$data = null;
		if ($id && !is_dir('prefs/podcasts/'.$id)) {
			mkdir('prefs/podcasts/'.$id, 0755);
		}

		$d = new url_downloader(array('url' => $url));
		if ($d->get_data_to_string()) {
			$data = $d->get_data();
		} else if ($id && file_exists('prefs/podcasts/'.$id.'/feed.xml')) {
			// This shouldn't really be necessary but this'll pick up older podcasts where
			// they're not updating because we didn't know about itunes:new-feed-url
			logger::warn('PODCASTS', 'Re-parsing previous feed for podcast', $id);
			$data = file_get_contents('prefs/podcasts/'.$id.'/feed.xml');
		} else {
			header('HTTP/1.0 404 Not Found');
			print "Feed Not Found";
			logger::warn("PARSE_RSS", "Failed to Download ".$url);
			return null;
		}

		if ($id) {
			file_put_contents('prefs/podcasts/'.$id.'/feed.xml', $data);
		}
		try {
			libxml_use_internal_errors(true);
			$feed = @simplexml_load_string($data);
			if ($feed === false) {
				logger::warn('PODCASTS', 'Could not parse RSS feed! (XML Load Failed)');
				return null;
			}
		} catch (Exception $e) {
			logger::warn('PODCASTS', 'Could not parse RSS feed! (Exception Rasied)');
			return null;
		}
		logger::debug("PARSE_RSS", "  Our LastPubDate is ".$lastpubdate);

		// Begin RSS Parse
		$podcast = array();
		$podcast['FeedURL'] = $url;
		$domain = preg_replace('#^(http://.*?)/.*$#', '$1', $url);
		$ppg = $feed->channel->children('ppg', TRUE);
		$itunes = $feed->channel->children('itunes', TRUE);
		$sy = $feed->channel->children('sy', TRUE);
		$googleplay = $feed->channel->children('googleplay', TRUE);

		if ($itunes && $itunes->{'new-feed-url'}) {
			logger::info('PODCASTS', 'Updating Feed URL to', (string) $itunes->{'new-feed-url'});
			$podcast['FeedURL'] = (string) $itunes->{'new-feed-url'};
		}

		// Automatic Refresh
		if ($ppg && $ppg->seriesDetails) {
			switch ($ppg->seriesDetails[0]->attributes()->frequency) {
				case "hourly":
					$podcast['RefreshOption'] = REFRESHOPTION_HOURLY;
					break;
				case "daily":
					$podcast['RefreshOption'] = REFRESHOPTION_DAILY;
					break;
				case "weekly":
					$podcast['RefreshOption'] = REFRESHOPTION_WEEKLY;
					break;
				case "monthly":
					$podcast['RefreshOption'] = REFRESHOPTION_MONTHLY;
					break;
				default:
					$podcast['RefreshOption'] = prefs::get_pref('default_podcast_refresh_mode');
					break;
			}
		} else if ($sy && $sy->updatePeriod) {
			switch ($sy->updatePeriod) {
				case "hourly":
					$podcast['RefreshOption'] = REFRESHOPTION_HOURLY;
					break;
				case "daily":
					$podcast['RefreshOption'] = REFRESHOPTION_DAILY;
					break;
				case "weekly":
					$podcast['RefreshOption'] = REFRESHOPTION_WEEKLY;
					break;
				case "monthly":
					$podcast['RefreshOption'] = REFRESHOPTION_MONTHLY;
					break;
				default:
					$podcast['RefreshOption'] = prefs::get_pref('default_podcast_refresh_mode');
					break;
			}
		} else {
			$podcast['RefreshOption'] = prefs::get_pref('default_podcast_refresh_mode');
		}

		// Episode Expiry
		// if ($ppg && $ppg->seriesDetails && $ppg->seriesDetails[0]->attributes()->daysLive) {
		// 	$podcast['DaysLive'] = $ppg->seriesDetails[0]->attributes()->daysLive;
		// } else {
			$podcast['DaysLive'] = -1;
		// }

		// Image
		if ($feed->channel->image) {
			try {
				foreach ($feed->channel->image as $i) {
					$podcast['Image'] = html_entity_decode((string) $i->url);
				}
			} catch (Exception $e) {
				$podcast['Image'] = html_entity_decode((string) $feed->channel->image->url);
			}
		} else if ($itunes && $itunes->image) {
			try {
				foreach ($itunes->image as $i) {
					$podcast['Image'] = $i->attributes()->href;
				}
			} catch (Exception $e) {
				try {
					$podcast['Image'] = $itunes->image[0]->attributes()->href;
				} catch (Exception $f) {
					$podcast['Image'] = $itunes->image->attributes()->href;
				}
			}
		} else {
			$podcast['Image'] = "newimages/podcast-logo.svg";
		}
		if (preg_match('#^/#', $podcast['Image'])) {
			// Image link with a relative URL. Duh.
			$podcast['Image'] = $domain.$image;
		}
		logger::trace("PARSE_RSS", "  Image is ".$podcast['Image']);

		// Artist
		if ($itunes && $itunes->author) {
			$podcast['Artist'] = (string) $itunes->author;
		} else {
			$podcast['Artist'] = '';
		}

		logger::trace("PARSE_RSS", "  Artist is ".$podcast['Artist']);

		// Category
		$cats = array();
		if ($itunes && $itunes->category) {
			for ($i = 0; $i < count($itunes->category); $i++) {
				$cat = html_entity_decode((string) $itunes->category[$i]->attributes()->text);
				logger::debug('POD', 'Found category',$cat);
				$cats[] = $cat;
			}
		}
		if ($googleplay && $googleplay->category) {
			for ($i = 0; $i < count($googleplay->category); $i++) {
				$cat = html_entity_decode((string) $googleplay->category[$i]->attributes()->text);
				$cats[] = $cat;
			}
		}
		$spaz = array_unique($cats);
		$spaz = array_diff($spaz, array('Podcasts'));
		natsort($spaz);
		$podcast['Category'] = implode(', ', $spaz);
		logger::debug("PARSE_RSS", "  Category is ".$podcast['Category']);

		// Title
		$podcast['Title'] = (string) $feed->channel->title;

		if ($id !== false) {
			$albumimage = new baseAlbumImage(array(
				'artist' => 'PODCAST',
				'albumpath' => $id,
				'album' => $podcast['Title']
			));
			if ($albumimage->get_image_if_exists() === null) {
				logger::mark("PODCASTS", "Replacing missing podcast image");
				$this->download_image($podcast['Image'], $id, $podcast['Title']);
			}
		}

		// Description
		$d1 = (string) $feed->channel->description;
		$d2 = '';
		if ($itunes && $itunes->summary) {
			$d2 = (string) $itunes->summary;
		}
		if (strlen($d2) > strlen($d1)) {
			$podcast['Description'] = $d2;
		} else {
			$podcast['Description'] = $d1;
		}

		// Tracks
		$podcast['tracks'] = array();
		$podcast['LastPubDate'] = $lastpubdate;
		if ($gettracks) {
			foreach($feed->channel->item as $item) {
				$track = array();

				$m = $item->children('media', TRUE);

				// Track Title
				$track['Title'] = (string) $item->title;
				logger::debug("PARSE_RSS", "  Found track ".$track['Title']);

				// Track URI
				$uri = null;
				if ($m && $m->content) {
					foreach ($m->content as $c) {
						if (preg_match('/audio/',(string) $c->attributes()->type)) {
							$uri = (string) $c->attributes()->url;
							break;
						}
					}
				}
				if ($item->enclosure && $uri == null) {
					$uri = (string) $item->enclosure->attributes()->url;
				}
				if ($item->link && $uri == null) {
					$uri = (string) $item->link;
				}

				$track['Link'] = $uri;
				logger::debug("PARSE_RSS", "    Track URI is ".$uri);

				if ($item->guid) {
					$track['GUID'] = $item->guid;
				} else {
					$track['GUID'] = $uri;
				}

				if ($uri == null) {
					logger::warn("PARSE_RSS", "    Could Not Find URI for track!");
					continue;
				}

				// Track Duration
				$track['Duration'] = 0;
				if ($m && $m->content) {
					if ($m->content[0]->attributes()->duration) {
						$track['Duration'] = (string) $m->content[0]->attributes()->duration;
					}
				}
				$m = $item->children('itunes', TRUE);
				if ($track['Duration'] == 0 && $m && $m->duration) {
					$track['Duration'] = (string) $m->duration;
				}
				$track['Image'] = null;
				if ($m && $m->image) {
					try {
						$track['Image'] = (string) $m->image->attributes()->href;
						logger::log('PODCASTS', '  Episode has an image', $track['Image']);
					} catch (Exception $e) {
						logger::warn('PODCASTS', '  Episode has an image but could not parse the href');
					}
				}
				if (preg_match('/:/', $track['Duration'])) {
					$timesplit = explode(':', $track['Duration']);
					$timefactors = array(1, 60, 3600, 86400);
					$hms = array_reverse($timesplit);
					$time = 0;
					foreach ($hms as $s) {
						$mf = array_shift($timefactors);
						if (is_numeric($s)) {
							$time += ($s * $mf);
						} else {
							logger::warn("PARSE_RSS", "Non-numeric duration field encountered in podcast! -",$track['Duration']);
							$time = 0;
							break;
						}
					}
					$track['Duration'] = $time;
				}

				// Track Author
				if ($m && $m->author) {
					$track['Artist'] = (string) $m->author;
				} else {
					$track['Artist'] = $podcast['Artist'];
				}

				// Track Publication Date
				$t = strtotime((string) $item->pubDate);
				logger::debug("PARSE_RSS", "Track PubDate is ",(string) $item->pubDate,"(".$t.")");
				if ($t === false) {
					logger::warn("PARSE_RSS", "  ERROR - Could not parse episode Publication Date",(string) $item->pubDate);
				} else if ($t > $podcast['LastPubDate']) {
					logger::log("PARSE_RSS", "Found a new episode",$track['Title']);
				}
				if ($t === false || $podcast['LastPubDate'] === null || $t > $podcast['LastPubDate']) {
					$podcast['LastPubDate'] = $t;
				}
				$track['PubDate'] = $t;
				if ($item->enclosure && $item->enclosure->attributes()) {
					$track['FileSize'] = $item->enclosure->attributes()->length;
				} else {
					$track['FileSize'] = 0;
				}

				if ($item->description) {
					$track['Description'] = $item->description;
				} else if ($m && $m->summary) {
					$track['Description'] = $m->summary;
				} else {
					$track['Description'] = '';
				}

				$podcast['tracks'][] = $track;
			}
		}

		if ($lastpubdate !== null) {
		    if ($podcast['LastPubDate'] !== false && $podcast['LastPubDate'] == $lastpubdate) {
		        logger::mark("PARSE_RSS", "Podcast has not been updated since last refresh");
		        if ($id) {
		        	// If we're returning false then we're not returning the FeedURL so we need
		        	// to update it here.
		        	$this->sql_prepare_query(true, null, null, null,
		        		"UPDATE Podcasttable SET FeedURL = ? WHERE PODindex = ?",
		        		$podcast['FeedURL'],
		        		$id
		        	);
		        }
		        return false;
		    }
		}

		return $podcast;

	}

	public function update_podcast_image($podid, $image) {
		logger::log('BACKEND', "Setting Image to",$image,"for podid",$podid);
		$this->sql_prepare_query(true, null, null, null, 'UPDATE Podcasttable SET Image = ? WHERE PODindex = ?',$image, $podid);
	}

	public function getNewPodcast($url, $subbed = 1, $gettracks = true) {
		logger::mark("PODCASTS", "Getting podcast",$url);
		$newpodid = null;
		$podcast = $this->parse_rss_feed($url, false, null, $gettracks);
		if ($podcast === false || $podcast === null) {
			logger::warn("PODCASTS", "  Failed to download RSS feed");
			header('HTTP/1.0 404 Not Found');
			print 'Could not download RSS feed '.$url;
			exit(0);
		}
		$r = $this->check_if_podcast_is_subscribed(array(  'feedUrl' => $podcast['FeedURL'],
													'collectionName' => $podcast['Title'],
													'artistName' => $podcast['Artist']));
		if (count($r) > 0) {
			foreach ($r as $a) {
				logger::warn("PODCASTS", "  Already subscribed to podcast",$a['Title']);
			}
			header('HTTP/1.0 404 Not Found');
			print 'You are already to subscrtibed to '.$podcast['Title'];
			exit(0);
		}
		logger::mark("PODCASTS", "Adding New Podcast",$podcast['Title']);
		$this->open_transaction();
		if ($this->sql_prepare_query(true, null, null, null,
			"INSERT INTO Podcasttable
			(FeedURL, Image, Title, Artist, RefreshOption, SortMode, DisplayMode, DaysLive, Description, Version, Subscribed, LastPubDate, NextUpdate, Category)
			VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
			$podcast['FeedURL'],
			$podcast['Image'],
			$podcast['Title'],
			$podcast['Artist'],
			$podcast['RefreshOption'],
			prefs::get_pref('default_podcast_sort_mode'),
			prefs::get_pref('default_podcast_display_mode'),
			$podcast['DaysLive'],
			$podcast['Description'],
			ROMPR_PODCAST_TABLE_VERSION,
			$subbed,
			$podcast['LastPubDate'],
			calculate_best_update_time($podcast),
			$podcast['Category']))
		{
			$newpodid = $this->mysqlc->lastInsertId();
			if (is_dir('prefs/podcasts/'.$newpodid)) {
				rrmdir('prefs/podcasts/'.$newpodid);
			}
			mkdir('prefs/podcasts/'.$newpodid, 0755);
			$this->download_image($podcast['Image'], $newpodid, $podcast['Title']);
			if ($subbed == 1) {
				foreach ($podcast['tracks'] as $track) {
					if ($this->sql_prepare_query(true, null, null, null,
						"INSERT INTO PodcastTracktable
						(PODindex, Title, Artist, Duration, PubDate, FileSize, Description, Link, Guid, New, Image)
						VALUES
						(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
						$newpodid, $track['Title'], $track['Artist'], $track['Duration'], $track['PubDate'],
						$track['FileSize'], $track['Description'], $track['Link'], $track['GUID'], 1, $track['Image']))
					{
						logger::trace("PODCASTS", "  Added Track ".$track['Title']);
					} else {
						logger::warn("PODCASTS", "  FAILED Adding Track ".$track['Title']);
					}
				}
			}
		}
		$this->close_transaction();
		return $newpodid;
	}

	private function download_image($url, $podid, $title) {

		$albumimage = new albumImage(array(
			'artist' => 'PODCAST',
			'albumpath' => $podid,
			'album' => $title,
			'source' => $url
		));
		// if ($albumimage->get_image_if_exists() === null) {
			$albumimage->download_image();
			$albumimage->update_image_database();
		// }

	}

	public function refreshPodcast($podid) {
		$this->check_refresh_pid();
		logger::mark("PODCASTS", "---------------------------------------------------");
		logger::mark("PODCASTS", "Refreshing podcast",$podid);
		$result = $this->generic_sql_query("SELECT * FROM Podcasttable WHERE PODindex = ".$podid, false, PDO::FETCH_OBJ);
		if (count($result) > 0) {
			$podetails = $result[0];
			logger::trace("PODCASTS", "Podcast title is ".$podetails->Title);
		} else {
			logger::error("PODCASTS", "ERROR Looking up podcast ".$podid);
			return $podid;
		}
		$podcast = $this->parse_rss_feed($podetails->FeedURL, $podid, $podetails->LastPubDate);
		if ($podcast === null) {
			logger::warn('PODCASTS', 'Could not refresh podcast',$podid, $podetails->Title);
			$this->check_next_refresh($podid, $podetails);
			return $podid;
		}
		$this->open_transaction();

		if ($podetails->Subscribed == 1) {
			if (prefs::get_pref('podcast_mark_new_as_unlistened')) {
				// Mark New As Unlistened, if required, on all subscribed podcasts - this option makes thi happen
				// even if no new episodes have been published.
				$this->generic_sql_query("UPDATE PodcastTracktable SET New = 0 WHERE PODindex = ".$podetails->PODindex);
			}
		}
		if ($podcast === false) {
			$this->check_next_refresh($podid, $podetails);
			// Still check to keep (days to keep still needs to be honoured)
			$this->close_transaction();
			if ($this->check_tokeep($podetails, $podid) || prefs::get_pref('podcast_mark_new_as_unlistened')) {
				return $podid;
			} else {
				return false;
			}
		}
		if ($podetails->Subscribed == 0) {
			// For an unsubscribed podcast, we're here if we browsed it. I can't remember why I do this bit.
			$this->sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET New = ?, JustUpdated = ?, Listened = ? WHERE PODindex = ?", 1, 0, 0, $podid);
		} else {
			// If we're here then the podcast has been updated since the last refresh. Mark All New tracks as not new.
			// Make sure we use the Refresh Option from the database, otherwise it gets replaced with the value
			// calculated in parse_rss_feed
			$podcast['RefreshOption'] = $podetails->RefreshOption;
			$this->sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET New = ?, JustUpdated = ? WHERE PODindex = ?", 0, 0, $podid);
		}
		$this->sql_prepare_query(true, null, null, null, "UPDATE Podcasttable SET FeedURL = ?, Description = ?, DaysLive = ?, RefreshOption = ?, NextUpdate = ?, LastPubDate = ?, UpRetry = ? WHERE PODindex = ?",
			$podcast['FeedURL'],
			$podcast['Description'],
			$podcast['DaysLive'],
			$podcast['RefreshOption'],
			calculate_best_update_time($podcast),
			$podcast['LastPubDate'],
			0,
			$podid);
		$this->download_image($podcast['Image'], $podid, $podetails->Title);
		//
		// NB we're doing a lookup and modify / insert because we can't put a UNIQUE KEY on the Guid column because it's a TEXT field
		//
		foreach ($podcast['tracks'] as $track) {
			$trackid = $this->sql_prepare_query(false, null, 'PODTrackindex' , null, "SELECT PODTrackindex FROM PodcastTracktable WHERE Guid = ? AND PODindex = ?", $track['GUID'], $podid);
			if ($trackid !== null) {
				logger::debug("PODCASTS", "Found existing track ".$track['Title']);
				$this->sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET JustUpdated = ?, Duration = ?, Link = ?, Image = ? WHERE PODTrackindex=?",1,$track['Duration'], $track['Link'], $track['Image'], $trackid);
			} else {
				if ($this->sql_prepare_query(true, null, null, null,
					"INSERT INTO PodcastTracktable
					(JustUpdated, PODindex, Title, Artist, Duration, PubDate, FileSize, Description, Link, Guid, New, Image)
					VALUES
					(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
					1, $podid, $track['Title'], $track['Artist'], $track['Duration'], $track['PubDate'],
					$track['FileSize'], $track['Description'], $track['Link'], $track['GUID'], 1, $track['Image']))
				{
					logger::log("PODCASTS", "Added Track ".$track['Title']);
				} else {
					logger::warn("PODCASTS", "FAILED Adding Track ".$track['Title']);
				}
			}
		}
		$this->check_tokeep($podetails, $podid);
		$this->close_transaction();
		$this->clear_refresh_pid();
		return $podid;
	}

	private function check_next_refresh($podid, $podetails) {
		// Podcast was not updated. Maybe we jyst need to try again later?
		if ($podetails->UpRetry < 2) {
			switch ($podetails->RefreshOption) {
				case REFRESHOPTION_DAILY:
					logger::log('PODCASTS', 'No new tracks found. Daily refresh, trying again in 2 hours');
					$nextup = $podetails->NextUpdate + 7200;
					break;

				case REFRESHOPTION_WEEKLY:
					logger::log('PODCASTS', 'No new tracks found. Weekly refresh, trying again tomorrow');
					$nextup = $podetails->NextUpdate + 86400;
					break;

				case REFRESHOPTION_MONTHLY:
					logger::log('PODCASTS', 'No new tracks found. Monthly refresh, trying again in 2 days');
					$nextup = $podetails->NextUpdate + 172800;
					break;

				default:
					$nextup = calculate_best_update_time(['LastPubDate' => $podetails->LastPubDate, 'RefreshOption' => $podetails->RefreshOption, 'Title' => $podetails->Title]);
					break;

			}
		} else {
			$nextup = calculate_best_update_time(['LastPubDate' => $podetails->LastPubDate, 'RefreshOption' => $podetails->RefreshOption, 'Title' => $podetails->Title]);
		}

		logger::info('PODCASTS', 'Next Update is', date('c', $nextup));

		$this->sql_prepare_query(true, null, null, null, "UPDATE Podcasttable SET NextUpdate = ?, UpRetry = ? WHERE PODindex = ?",
			$nextup,
			$podetails->UpRetry + 1,
			$podid
		);
	}

	private function check_tokeep($podetails, $podid) {
		$retval = false;
		// Remove tracks that are no longer in the feed and haven't been downloaded
		if ($podetails->Subscribed == 1) {
			$this->sql_prepare_query(true, null, null, null, "DELETE FROM PodcastTracktable WHERE PODindex=? AND JustUpdated=? AND Downloaded=?",$podid, 0, 0);

			// Remove tracks that have been around longer than DaysToKeep - honoring KeepDownloaded
			if ($podetails->DaysToKeep > 0) {
				$oldesttime = time() - ($podetails->DaysToKeep * 86400);
				$numthen = $this->simple_query("COUNT(PODTrackindex)", "PodcastTracktable", 'Deleted = 0 AND PODindex', $podid, 0);
				$qstring = "UPDATE PodcastTracktable SET Deleted=1 WHERE PODindex = ".$podid." AND PubDate < ".$oldesttime." AND Deleted = 0";
				if ($podetails->KeepDownloaded == 1) {
					$qstring .= " AND Downloaded = 0";
				}
				$this->generic_sql_query($qstring, true);
				$numnow = $this->simple_query("COUNT(PODTrackindex)", "PodcastTracktable", 'Deleted = 0 AND PODindex', $podid, 0);
				if ($numnow != $numthen) {
					logger::info("PODCASTS", "Old episodes were removed from podcast ID ".$podid);
					$retval = true;
				}
			}

			// Remove tracks where there are more than NumToKeep - honoring KeepDownloaded
			if ($podetails->NumToKeep > 0) {
				$getrid = 0;
				$qstring = "SELECT COUNT(PODTrackindex) AS num FROM PodcastTracktable WHERE PODindex=".$podid." AND Deleted = 0";
				if ($podetails->KeepDownloaded == 1) {
					$qstring .= " AND Downloaded = 0";
				}
				$num = $this->generic_sql_query($qstring, false, null, 'num', 0);
				$getrid = $num - $podetails->NumToKeep;
				logger::trace("PODCASTS", "  Num To Keep is ".$podetails->NumToKeep." and there are ".$num." episodes that can be pruned. Removing ".$getrid);
				if ($getrid > 0) {
					$qstring = "SELECT PODTrackindex FROM PodcastTracktable WHERE PODindex=".$podid." AND Deleted = 0";
					if ($podetails->KeepDownloaded == 1) {
						$qstring .= " AND Downloaded=0";
					}
					$qstring .= " ORDER BY PubDate ASC LIMIT ".$getrid;
					$pods = $this->sql_get_column($qstring, 0);
					foreach ($pods as $i) {
						logger::debug("PODCASTS", "  Removing Track ".$i);
						$this->generic_sql_query("UPDATE PodcastTracktable SET Deleted=1 WHERE PODTrackindex=".$i, true);
						$retval = true;
					}
				}
			}
		}
		return $retval;
	}

	public function outputPodcast($podid, $do_searchbox = true) {
		$result = $this->generic_sql_query("SELECT * FROM Podcasttable WHERE PODindex = ".$podid, false, PDO::FETCH_OBJ);
		foreach ($result as $obj) {
			$this->doPodcast($obj, $do_searchbox);
		}
	}

	private function doPodcast($y, $do_searchbox) {

		if ($y->Subscribed == 0) {
			logger::mark("PODCASTS", "Getting feed for unsubscribed podcast ".$y->FeedURL);
			$this->refreshPodcast($y->PODindex);
			$a = $this->generic_sql_query("SELECT * FROM Podcasttable WHERE PODindex = ".$y->PODindex, false, PDO::FETCH_OBJ);
			if (count($a) > 0) {
				$y = $a[0];
			} else {
				logger::warn("PODCASTS", "ERROR looking up podcast",$y->FeedURL);
				return;
			}
		}

		$aa = $y->Artist;
		if ($aa != '') {
			$aa = $aa . ' - ';
		}
		$pm = $y->PODindex;
		uibits::trackControlHeader('','','podcast_'. $pm, null, ['Image' => $y->Image, 'Albumname' => $y->Title]);
		print '<div class="whatdoicallthis">'.format_podcast_text($y->Description).'</div>';
		if ($y->Subscribed == 1) {
			print '<div class="containerbox bumpad">';
			print '<i title="'.language::gettext("podcast_configure").'" class="icon-cog-alt inline-icon '.
				'clickicon openmenu fixed tooltip spinable" name="podconf_'.$pm.'"></i>';
			print '<i title="'.language::gettext("podcast_refresh").'" class="icon-refresh inline-icon podaction podcast clickable '.
				'clickicon fixed tooltip spinable" name="refresh_'.$pm.'"></i>';
			print '<i title="'.language::gettext("podcast_download_all").'" class="icon-download inline-icon '.
				'clickable clickicon podgroupload podcast fixed tooltip spinable" name="podgroupload_'.$pm.'"></i>';
			print '<i title="'.language::gettext("podcast_mark_all").'" class="icon-headphones inline-icon podcast podaction '.
				'clickable clickicon fixed tooltip spinable" name="channellistened_'.$pm.'"></i>';
			print '<div class="expand"></div>';
			print '<i title="'.language::gettext("podcast_undelete").'" class="icon-trash inline-icon podcast podaction oneeighty '.
				'clickable clickicon fixed tooltip spinable" name="channelundelete_'.$pm.'"></i>';
			print '<i title="'.language::gettext("podcast_removedownloaded").'" class="icon-download inline-icon podcast podaction oneeighty '.
				'clickable clickicon fixed tooltip spinable" name="removedownloaded_'.$pm.'"></i>';
			print '<i title="'.language::gettext("podcast_delete").'" class="icon-cancel-circled inline-icon '.
					'clickable clickicon podremove podcast fixed tooltip spinable" name="podremove_'.$pm.'"></i>';
			print '</div>';

			if (array_key_exists('configvisible', $_REQUEST) && $_REQUEST['configvisible'] == 1) {
				print '<div class="whatdoicallthis toggledown invisible podconfigpanel" id="podconf_'.$pm.'" style="display:block">';
			} else {
				print '<div class="whatdoicallthis toggledown invisible podconfigpanel" id="podconf_'.$pm.'">';
			}
			print '<div class="containerbox vertical podoptions">';
			print '<div class="containerbox fixed vertical-centre"><div class="divlabel">'.
				language::gettext("podcast_display").'</div>';
			print '<div class="selectholder">';
			print '<select name="DisplayMode" onchange="podcasts.changeOption(event)">';
			$options =  '<option value="'.DISPLAYMODE_ALL.'">'.language::gettext("podcast_display_all").'</option>'.
						'<option value="'.DISPLAYMODE_NEW.'">'.language::gettext("podcast_display_onlynew").'</option>'.
						'<option value="'.DISPLAYMODE_UNLISTENED.'">'.language::gettext("podcast_display_unlistened").'</option>'.
						'<option value="'.DISPLAYMODE_DOWNLOADEDNEW.'">'.language::gettext("podcast_display_downloadnew").'</option>'.
						'<option value="'.DISPLAYMODE_DOWNLOADED.'">'.language::gettext("podcast_display_downloaded").'</option>'.
						'<option value="'.DISPLAYMODE_NUD.'">'.language::gettext("podcast_display_nud").'</option>';
			print preg_replace('/(<option value="'.$y->DisplayMode.'")/', '$1 selected', $options);
			print '</select>';
			print '</div></div>';

			print '<div class="containerbox fixed vertical-centre"><div class="divlabel">'.
				language::gettext("podcast_refresh").'</div>';
			print '<div class="selectholder">';
			print '<select name="RefreshOption" onchange="podcasts.changeOption(event)">';
			$options =  '<option value="'.REFRESHOPTION_NEVER.'">'.language::gettext("podcast_refresh_never").'</option>'.
						'<option value="'.REFRESHOPTION_HOURLY.'">'.language::gettext("podcast_refresh_hourly").'</option>'.
						'<option value="'.REFRESHOPTION_DAILY.'">'.language::gettext("podcast_refresh_daily").'</option>'.
						'<option value="'.REFRESHOPTION_WEEKLY.'">'.language::gettext("podcast_refresh_weekly").'</option>'.
						'<option value="'.REFRESHOPTION_MONTHLY.'">'.language::gettext("podcast_refresh_monthly").'</option>';
			print preg_replace('/(<option value="'.$y->RefreshOption.'")/', '$1 selected', $options);
			print '</select>';
			print '</div></div>';

			if ($y->RefreshOption != REFRESHOPTION_NEVER)
				print '<div class="playlistrow2 stumpy bumpad">Next Refresh Will Be On '.date('l jS M Y H:i:s', $y->NextUpdate).'</div>';

			print '<div class="containerbox fixed vertical-centre"><div class="divlabel">'.
				language::gettext("podcast_expire").'</div>';
			print '<div class="selectholder">';
			print '<select title="'.language::gettext("podcast_expire_tooltip").
				'" name="DaysToKeep" class="tooltip" onchange="podcasts.changeOption(event)">';
			$options =  '<option value="0">'.language::gettext("podcast_expire_never").'</option>'.
						'<option value="7">'.language::gettext("podcast_expire_week").'</option>'.
						'<option value="14">'.language::gettext("podcast_expire_2week").'</option>'.
						'<option value="30">'.language::gettext("podcast_expire_month").'</option>'.
						'<option value="60">'.language::gettext("podcast_expire_2month").'</option>'.
						'<option value="182">'.language::gettext("podcast_expire_6month").'</option>'.
						'<option value="365">'.language::gettext("podcast_expire_year").'</option>';
			print preg_replace('/(<option value="'.$y->DaysToKeep.'")/', '$1 selected', $options);
			print '</select>';
			print '</div></div>';

			print '<div class="containerbox fixed vertical-centre"><div class="divlabel">'.
				language::gettext("podcast_keep").'</div>';
			print '<div class="selectholder">';
			print '<select title="'.language::gettext("podcast_keep_tooltip").
				'" name="NumToKeep" class="tooltip" onchange="podcasts.changeOption(event)">';
			$options =  '<option value="0">'.language::gettext("podcast_keep_0").'</option>'.
						'<option value="1">1</option>'.
						'<option value="5">5</option>'.
						'<option value="10">10</option>'.
						'<option value="25">25</option>'.
						'<option value="50">50</option>'.
						'<option value="100">100</option>'.
						'<option value="200">200</option>';
			print preg_replace('/(<option value="'.$y->NumToKeep.'")/', '$1 selected', $options);
			print '</select>';
			print '</div></div>';

			print '<div class="containerbox fixed vertical-centre"><div class="divlabel">'.
				language::gettext("podcast_sortmode").'</div>';
			print '<div class="selectholder">';
			print '<select name="SortMode" onchange="podcasts.changeOption(event)">';
			$options =  '<option value="'.SORTMODE_NEWESTFIRST.'">'.language::gettext("podcast_newestfirst").'</option>'.
						'<option value="'.SORTMODE_OLDESTFIRST.'">'.language::gettext("podcast_oldestfirst").'</option>';
			print preg_replace('/(<option value="'.$y->SortMode.'")/', '$1 selected', $options);
			print '</select>';
			print '</div></div>';

			print '<div class="containerbox fixed bumpad styledinputs">';
			print '<input type="checkbox" class="topcheck" id="podkd"';
			if ($y->KeepDownloaded == 1) {
				print ' checked';
			}
			print '><label for="podkd" class="tooltip" title="'.language::gettext("podcast_kd_tooltip").
				'" name="KeepDownloaded" onclick="podcasts.changeOption(event)">'.
				language::gettext("podcast_keep_downloaded").'</label></div>';

			print '<div class="containerbox fixed bumpad styledinputs">';
			print '<input type="checkbox" class="topcheck" id="podhd"';
			if ($y->HideDescriptions == 1) {
				print ' checked';
			}
			print '><label for="podhd" name="HideDescriptions" onclick="podcasts.changeOption(event)">'.
				language::gettext("podcast_hidedescriptions").'</label></div>';

			print '<div class="containerbox fixed bumpad styledinputs">';
			print '<input type="checkbox" class="topcheck" id="podwt"';
			if ($y->WriteTags == 1) {
				print ' checked';
			}
			print '><label for="podwt" name="WriteTags" onclick="podcasts.changeOption(event)">'.
				language::gettext("podcast_writetags").'</label></div>';

			print '</div>';

			print '</div>';
		}
		if ($do_searchbox) {
			print '<div class="containerbox noselection vertical-centre"><div class="expand">
				<input class="enter clearbox" name="podsearcher_'.$y->PODindex.'" type="text" ';
			if (array_key_exists('searchterm', $_REQUEST)) {
				print 'value="'.urldecode($_REQUEST['searchterm']).'" ';
			}
			print '/></div><button class="fixed searchbutton iconbutton" onclick="podcasts.searchinpodcast('.$y->PODindex.')"></button></div>';
		}
		print '<div class="clearfix bumpad"></div>';
		if (array_key_exists('searchterm', $_REQUEST)) {
			$extrabit = ' AND (Title LIKE "%'.urldecode($_REQUEST['searchterm']).'%" OR Description LIKE "%'.urldecode($_REQUEST['searchterm']).'%")';
		} else {
			$extrabit = '';
		}
		$qstring = 'SELECT * FROM PodcastTracktable WHERE PODindex = '.$y->PODindex.' AND Deleted = 0'.$extrabit.' ORDER BY PubDate ';
		if ($y->SortMode == SORTMODE_OLDESTFIRST) {
			$qstring .= "ASC";
		} else {
			$qstring .= "DESC";
		}
		logger::core("PODCASTS", $qstring);
		$result = $this->generic_sql_query($qstring, false, PDO::FETCH_OBJ);
		foreach ($result as $episode) {
			$this->format_episode($y, $episode, $pm);
		}
	}

	private function format_episode(&$y, &$item, $pm) {
		if ($item->Deleted == 1)
			return;

		if ($y->DisplayMode == DISPLAYMODE_NUD && ($item->Downloaded == 0 && $item->New == 0 && $item->Listened == 1))
			return;

		if ($y->DisplayMode == DISPLAYMODE_DOWNLOADEDNEW && ($item->Downloaded == 0 && $item->New == 0))
			return;

		if ($y->DisplayMode == DISPLAYMODE_NEW && $item->New == 0)
			return;

		if ($y->DisplayMode == DISPLAYMODE_UNLISTENED && $item->Listened == 1)
			return;

		if ($y->DisplayMode == DISPLAYMODE_DOWNLOADED && $item->Downloaded == 0)
			return;


		print '<div class="item podcastitem">';

		if ($item->Image) {
			print '<div class="podcast-item-image-holder">';
			print '<img class="podcast-item-image lazy" data-src="getRemoteImage.php?rompr_resize_size=small&url='.rawurlencode($item->Image).'" />';
			print '</div>';
		}

		print '<div class="podcast-item-details">';

		if ($item->Downloaded == 1 && $y->Version > 1) {
			$uri_to_use = rawurlencode(dirname(dirname(get_base_url())).$item->Localfilename);
		} else {
			$uri_to_use = rawurlencode($item->Link);
		}
		print '<div class="containerbox podcasttrack clicktrack playable draggable vertical-centre" name="'.$uri_to_use.'">';
		if ($y->Subscribed == 1) {
			if ($item->New == 1) {
				print '<i title="'.language::gettext("podcast_tooltip_new").
					'" class="icon-sun fixed smallicon tooltip"></i>';
			} else if ($item->Listened == 0) {
				print '<i title="'.language::gettext("podcast_tooltip_notnew").
					'" class="icon-unlistened fixed smallicon tooltip"></i>';
			}
		}
		print '<div class="podtitle expand">'.htmlspecialchars(html_entity_decode($item->Title)).'</div>';
		// print '<i class="fixed icon-no-response-playbutton inline-icon"></i>';
		print '</div>';

		$bookmarks = $this->sql_prepare_query(false, PDO::FETCH_OBJ, null, array(),
			"SELECT * FROM PodBookmarktable WHERE PODTrackindex = ? AND Bookmark > 0 ORDER BY
			CASE WHEN Name = 'Resume' THEN 0 ELSE Bookmark END ASC",
			$item->PODTrackindex
		);
		foreach ($bookmarks as $book) {
			uibits::resume_bar($book->Bookmark, $item->Duration, $book->Name, $uri_to_use, 'podcast');
		}

		$pee = date(DATE_RFC2822, $item->PubDate);
		$pee = preg_replace('/ \+\d\d\d\d$/','',$pee);
		print '<div class="whatdoicallthis containerbox vertical-centre podtitle notbold">';
		if ($y->HideDescriptions == 0) {
			$class = 'icon-toggle-open';
		} else {
			$class = 'icon-toggle-closed';
		}

		print '<i class="'.$class.' menu mh fixed openmenu" name="poddesc_'.$item->PODTrackindex.'"></i>';
		print '<div class="expand"><i>'.$pee.'</i></div>';
		if ($item->Duration != 0) {
			print '<div class="fixed">'.format_time($item->Duration).'</div>';
		}
		print '</div>';
		if ($y->HideDescriptions == 0) {
			$class = 'whatdoicallthis toggledown';
		} else {
			$class = 'invisible whatdoicallthis toggledown';
		}
		print '<div id="poddesc_'.$item->PODTrackindex.'" class="'.$class.'">'.format_podcast_text($item->Description).'</div>';
		if ($y->Subscribed == 1) {
			print '<div class="clearfix" name="podcontrols_'.$pm.'">';
			if ($item->Downloaded == 1) {
				print '<i class="icon-floppy inline-icon tleft tooltip clickable clickicon podcast podremdownload spinable" title="'.
					language::gettext("podcast_tooltip_downloaded").'" name="podremdownload_'.$item->PODTrackindex.'"></i>';
			} else {
				if ($item->New == 1) {
					$extraclass = ' podnewdownload';
				} else {
					$extraclass = '';
				}
				print '<i class="icon-download inline-icon clickable clickicon tleft podcast poddownload spinable'.$extraclass.' tooltip" title="'.
					language::gettext("podcast_tooltip_download").'" name="poddownload_'.$item->PODTrackindex.'"></i>';
			}
			if ($item->Listened == 0) {
				print '<i class="icon-headphones inline-icon clickable clickicon tleft podcast podmarklistened tooltip spinable" title="'.
					language::gettext("podcast_tooltip_mark").'" name="podmarklistened_'.$item->PODTrackindex.'"></i>';
			}
			print '<i class="icon-cancel-circled inline-icon clickable clickicon tright podtrackremove podcast tooltip spinable" title="'.
				language::gettext("podcast_tooltip_delepisode").'" name="podtrackremove_'.$item->PODTrackindex.'" ></i>';
			if ($item->Listened == 1) {
				print '<i class="icon-headphones inline-icon clickable clickicon tright podcast podmarkunlistened tooltip spinable oneeighty" title="'.
					language::gettext("podcast_tooltip_unlistened").'" name="podmarkunlistened_'.$item->PODTrackindex.'"></i>';
			}
			print '</div>';
		}

		print '</div>';

		print '</div>';
	}

	private function doPodcastHeader($y, $subscribed) {

		$i = getDomain($y->Image);
		if ($i == "http" || $i == "https") {
			$img = "getRemoteImage.php?url=".rawurlencode($y->Image);
		} else {
			$img = $y->Image;
		}

		$aname = htmlspecialchars(html_entity_decode($y->Artist));
		$extralines = [];
		if ($y->Category)
			$extralines[] = '<i>'.htmlspecialchars($y->Category).'</i>';

		$cls = ($subscribed == 1) ? 'podcast subscribed-podcast' : 'podcast unsubscribed-podcast';

		$extra = '<div class="album-extra-controls">';
		if ($y ->Subscribed == 1) {
			// $uc = $this->get_podcast_counts($y->PODindex);
			$extra .= '<span id="podnumber_'.$y->PODindex.'"></span>';
			$extra .= '<span></span>';
		} else {
			$extra .= '<i class="clickicon clickable clickpodsubscribe podcast icon-rss inline-icon tooltip spinable" title="Subscribe to this podcast"></i><input type="hidden" value="'.$y->PODindex.'" />';
		}
		$extra .= '</div>';

		print uibits::albumHeader(array(
			'playable' => false,
			'id' => 'podcast_'.$y->PODindex,
			'Image' => $img,
			'Artistname' => htmlspecialchars(html_entity_decode($y->Artist)),
			'Albumname' => htmlspecialchars(html_entity_decode($y->Title)),
			'class' => $cls,
			'podcounts' => $extra,
			'extralines' => $extralines
		));

		print '<div id="podcast_'.$y->PODindex.'" class="indent dropmenu notfilled is-albumlist">';
		print uibits::ui_config_header(['label' => 'label_loading']);
		print '</div>';
	}

	public function removePodcast($podid) {
		logger::mark("PODCASTS", "Removing podcast ".$podid);
		if (is_dir('prefs/podcasts/'.$podid)) {
			rrmdir('prefs/podcasts/'.$podid);
		}
		$this->generic_sql_query("DELETE FROM Podcasttable WHERE PODindex = ".$podid, true);
		$this->generic_sql_query("DELETE FROM PodcastTracktable WHERE PODindex = ".$podid, true);
	}

	public function markAsListened($url) {
		$podid = false;
		logger::trace('PODCASTS', 'Doing the mark as listened thing with',$url);
		$pods = $this->sql_prepare_query(false, PDO::FETCH_OBJ, null, null, database::STUPID_CONCAT_THING, $url, $url);
		foreach ($pods as $pod) {
			$podid = $pod->PODindex;
			logger::log("PODCASTS", "Marking track",$pod->PODTrackindex,"from podcast",$podid,"as listened");
			$this->sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET Listened = 1, New = 0 WHERE PODTrackindex=?",$pod->PODTrackindex);
			$this->sql_prepare_query(true, null, null, null, "UPDATE PodBookmarktable SET Bookmark = 0 WHERE PODTrackindex = ? AND Name = ?", $pod->PODTrackindex, 'Resume');
		}
		return $podid;
	}

	public function deleteTrack($trackid, $channel) {
		logger::info("PODCASTS", "Marking track",$trackid,"from podcast",$channel,"as deleted");
		$this->generic_sql_query("UPDATE PodcastTracktable SET Deleted = 1 WHERE PODTrackindex = ".$trackid, true);
		if (is_dir('prefs/podcasts/'.$channel.'/'.$trackid)) {
			rrmdir('prefs/podcasts/'.$channel.'/'.$trackid);
		}
		return $channel;
	}

	public function markKeyAsListened($trackid, $channel) {
		logger::info("PODCASTS", "Marking track",$trackid,"from podcast",$channel,"as listened");
		$this->generic_sql_query("UPDATE PodcastTracktable SET Listened = 1, New = 0 WHERE PODTrackindex = ".$trackid, true);
		$this->sql_prepare_query(true, null, null, null, "UPDATE PodBookmarktable SET Bookmark = 0 WHERE PODTrackindex = ? AND Name = ?", $trackid, 'Resume');
		return $channel;
	}

	public function markKeyAsUnlistened($trackid, $channel) {
		logger::info("PODCASTS", "Marking track",$trackid,"from podcast",$channel,"as unlistened");
		$this->generic_sql_query("UPDATE PodcastTracktable SET Listened = 0, New = 0 WHERE PODTrackindex = ".$trackid, true);
		$this->sql_prepare_query(true, null, null, null, "UPDATE PodBookmarktable SET Bookmark = 0 WHERE PODTrackindex = ? AND Name = ?", $trackid, 'Resume');
		return $channel;
	}

	public function undownloadTrack($trackid, $channel) {
		logger::info("PODCASTS", "un-Downloading track",$trackid,"from podcast",$channel);
		$this->generic_sql_query("UPDATE PodcastTracktable SET Localfilename = NULL, Downloaded = 0 WHERE PODTrackindex = ".$trackid, true);
		if (is_dir('prefs/podcasts/'.$channel.'/'.$trackid)) {
			rrmdir('prefs/podcasts/'.$channel.'/'.$trackid);
		}
		return $channel;
	}

	public function changeOption($option, $val, $channel) {
		logger::log("PODCASTS", "Changing Option",$option,"to",$val,"on channel",$channel);
		if ($val === 'true') {
			$val = 1;
		}
		if ($val === 'false') {
			$val = 0;
		}
		$this->generic_sql_query("UPDATE Podcasttable SET ".$option."=".$val." WHERE PODindex=".$channel, true);
		if ($option == 'DaysToKeep' || $option == 'NumToKeep') {
			$this->refreshPodcast($channel);
		}
		if ($option == 'RefreshOption') {
			$podcast = $this->generic_sql_query("SELECT * FROM Podcasttable WHERE PODindex = ".$channel, false, PDO::FETCH_ASSOC);
			$this->sql_prepare_query(true, null, null, null,
				"UPDATE Podcasttable SET NextUpdate = ? WHERE PODindex = ?",
				calculate_best_update_time($podcast[0]),
				$channel
			);
		}
		return $channel;
	}

	public function markChannelAsListened($channel) {
		$this->generic_sql_query("UPDATE PodcastTracktable SET Listened = 1, New = 0 WHERE PODindex = ".$channel, true);
		$this->sql_prepare_query(true, null, null, null, "UPDATE PodBookmarktable SET Bookmark = 0 WHERE PODTrackindex IN (SELECT PODTrackindex FROM PodcastTracktable WHERE PODindex = ?) AND Name = ?", $channel, 'Resume');
		return $channel;
	}

	public function mark_all_episodes_listened() {
		$this->generic_sql_query("UPDATE PodcastTracktable SET Listened = 1, New = 0 WHERE PODindex IN (SELECT PODindex FROM Podcasttable WHERE Subscribed = 1)");
		$this->sql_prepare_query(true, null, null, null, "UPDATE PodBookmarktable SET Bookmark = 0 WHERE Name = ?", 'Resume');
		return false;
	}

	public function undeleteFromChannel($channel) {
		$this->generic_sql_query("UPDATE PodcastTracktable SET Downloaded=0 WHERE PODindex=".$channel." AND Deleted=1", true);
		$this->generic_sql_query("UPDATE PodcastTracktable SET Deleted=0 WHERE PODindex=".$channel." AND Deleted=1", true);
		return $channel;
	}

	public function undelete_all() {
		$this->generic_sql_query("UPDATE PodcastTracktable SET Downloaded = 0 WHERE PODindex IN (SELECT PODindex FROM Podcasttable WHERE Subscribed = 1) AND Deleted = 1", true);
		$this->generic_sql_query("UPDATE PodcastTracktable SET Deleted = 0 WHERE PODindex IN (SELECT PODindex FROM Podcasttable WHERE Subscribed = 1) AND Deleted = 1", true);
		return false;
	}

	public function remove_all_downloaded() {
		$pods = glob('prefs/podcasts/*');
		foreach ($pods as $channel) {
			$this->removeDownloaded(basename($channel));
		}
		return false;
	}

	public function removeDownloaded($channel) {
		if (is_dir('prefs/podcasts/'.$channel)) {
			$things = glob('prefs/podcasts/'.$channel.'/*');
			foreach ($things as $thing) {
				if (is_dir($thing) && basename($thing) != 'albumart') {
					rrmdir($thing);
				}
			}
		}
		$this->generic_sql_query("UPDATE PodcastTracktable SET Downloaded=0, Localfilename=NULL WHERE PODindex=".$channel, true);
		return $channel;
	}

	public function downloadTrack($key, $channel) {
		logger::mark("PODCASTS", "Downloading track",$key,"from podcast",$channel);
		$url = null;
		$filesize = 0;
		$result = $this->generic_sql_query("
			SELECT Link,
			FileSize,
			WriteTags,
			Duration,
			Podcasttable.Title AS album,
			Podcasttable.Artist AS artist,
			PodcastTracktable.Title AS title,
			PodcastTracktable.Image AS image
			FROM PodcastTracktable
			JOIN Podcasttable USING (PODindex)
			WHERE PODTrackindex = " . intval($key), false, PDO::FETCH_OBJ);
		foreach ($result as $obj) {
			$url = $obj->Link;
			$filesize = $obj->FileSize;
		}
		logger::log("PODCASTS", "  Artist is",$obj->artist);
		logger::log("PODCASTS", "   Album is",$obj->album);
		logger::log("PODCASTS", "   Title is",$obj->title);
		if ($url === null) {
			logger::warn("PODCASTS", "  Failed to find URL for podcast",$channel);
			header('HTTP/1.0 404 Not Found');
			exit(0);
		}
		// The file size reported in the RSS is often VERY inaccurate. Probably based on raw audio prior to converting to MP3
		// To make the progress bars look better in the GUI we attempt to read the actual filesize
		$filesize = getRemoteFilesize($url, $filesize);
		if (is_dir('prefs/podcasts/'.$channel.'/'.$key) || mkdir ('prefs/podcasts/'.$channel.'/'.$key, 0755, true)) {
			$filename = basename($url);
			$filename = preg_replace('/\?.*$/','',$filename);

			$xml = '<?xml version="1.0" encoding="utf-8"?><download><filename>';
			$xml = $xml . 'prefs/podcasts/'.$channel.'/'.$key.'/'.$filename;
			$xml = $xml . '</filename><filesize>'.$filesize.'</filesize></download>';
			$fp = fopen('prefs/monitor.xml', 'w');
			if ($fp) {
				fwrite($fp, $xml);
				fclose($fp);
			} else {
				@unlink('prefs/monitor.xml');
				logger::warn("PODCASTS", "Failed to open monitor.xml");
			}
			logger::trace("PODCASTS", "Downloading To prefs/podcasts/".$channel.'/'.$key.'/'.$filename);
			$d = new url_downloader(array('url' => $url));
			$download_file = 'prefs/podcasts/'.$channel.'/'.$key.'/'.$filename;
			if ($d->get_data_to_file($download_file, true)) {
				$getID3 = new getID3;
				$getID3->setOption(array('encoding'=>'UTF-8'));
				$getID3->analyze($download_file);

				$p = $getID3->info['playtime_seconds'];
				if ($p) {
					$obj->Duration = $p;
					logger::log('PODCASTS', 'Updating database duration field to',$p,'seconds');
				}

				// Note - we need to merge our info with the current tags, getID3 will not
				// merge them for us as aparently that's very hard. I synmpathise.
				$tags = [];
				if (array_key_exists('tags', $getID3->info)) {
					$current_tags = $getID3->info['tags'];
					if (is_array($current_tags) && array_key_exists('id3v2', $current_tags)) {
						logger::debug('PODCASTS', 'Using Current ID3v2 Tags');
						$tags = $current_tags['id3v2'];
					} else if (is_array($current_tags) && array_key_exists('id3v1', $current_tags)) {
						logger::debug('PODCASTS', 'Using Current ID3v1 Tags');
						$tags = $current_tags['id3v1'];
					}
				}

				if (array_key_exists('track_number', $tags) && is_array($tags['track_number'])) {
					$track_number = $tags['track_number'][0];
					logger::debug('PODCASTS', 'Using track number from tags',$track_number);
				} else {
					$track_number = format_tracknum($obj->title);
					logger::debug('PODCASTS', 'Using track number from title',$track_number);
					if ($track_number > 0)
						$tags['track_number'] = array($track_number);
				}

				$track_image_file = null;
				if ($obj->image) {
					logger::trace('PODCASTS', 'Downloading Image',$obj->image);
					$imgd = new url_downloader(['url' => $obj->image]);
					$ext = pathinfo($obj->image, PATHINFO_EXTENSION);
					$track_image_file = 'prefs/podcasts/'.$channel.'/'.$key.'/cover.'.$ext;
					if ($imgd->get_data_to_file($track_image_file, true)) {
						logger::log('PODCASTS', 'Downloaded Track Image to Embed into file');
					} else {
						logger::warn('PODCASTS', 'Failed to download track image');
						$track_image_file = null;
					}
				}

				if ($obj->WriteTags != 0) {
					logger::log('PODCASTS', 'Updating ID3 tags as requested by preference');
					$tags['artist'] = array($obj->artist);
					$tags['albumartist'] = array($obj->artist);
					$tags['album'] = array($obj->album);
					$tags['title'] = array($obj->title);
				}

				if ($track_image_file) {
					if ($fd = @fopen($track_image_file, 'rb')) {
  						$APICdata = fread($fd, filesize($track_image_file));
  						fclose ($fd);
						$tags['attached_picture'][0]['data']            = $APICdata;
						$tags['attached_picture'][0]['picturetypeid']   = 0x03;                 // 'Cover (front)'
						$tags['attached_picture'][0]['description']     = 'Cover Image';
						$tags['attached_picture'][0]['mime']            = mime_content_type($track_image_file);
					} else {
						logger::warn('PODCASTS', 'Could not open file',$track_image_file,'to embed into audio');
						@unlink($track_image_file);
						$track_image_file = null;
					}
				}

				if ($obj->WriteTags != 0 || $track_image_file != null) {
					logger::log('PODCASTS', 'Writing ID3 tags to',$download_file);
					getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'write.php', __FILE__, true);

					$tagwriter = new getid3_writetags();
					$tagwriter->filename       = $download_file;
					$tagwriter->tagformats     = array('id3v2.3');
					$tagwriter->overwrite_tags = true;
					$tagwriter->tag_encoding   = 'UTF-8';
					$tagwriter->remove_other_tags = true;
					$tagwriter->tag_data = $tags;
					if ($tagwriter->WriteTags()) {
						logger::trace('PODCASTS', 'Successfully wrote tags');
						if (!empty($tagwriter->warnings)) {
							logger::warn('PODCASTS', 'There were some warnings'.implode(' ', $tagwriter->warnings));
						}
					} else {
						logger::error('PODCASTS', 'Failed to write tags!', implode(' ', $tagwriter->errors));
					}
				} else {
					logger::log('PODCASTS', 'No Tags to Write');
				}
				if ($track_image_file)
					unlink($track_image_file);

				if ($track_number > 0 && is_numeric($track_number)) {
					$tn = str_pad((string) $track_number, 3, '0', STR_PAD_LEFT);
					$newfile = dirname($download_file).'/'.$tn.' - '.basename($download_file);
					logger::log('PODCASTS', 'Renaming downloaded file to', $newfile);
					rename($download_file, $newfile);
				} else {
					$newfile = $download_file;
				}
				$this->sql_prepare_query(true, null, null, null,
					"UPDATE PodcastTracktable SET Duration = ?, Downloaded = ?, Localfilename = ?
					WHERE PODTrackindex = ?",
					$obj->Duration,
					1,
					 '/'.$newfile,
					 $key
				);
			} else {
				logger::error('PODCASTS', 'Failed to download',$key, $channel, $url);
				header('HTTP/1.0 404 Not Found');
				system (escapeshellarg('rm -fR prefs/podcasts/'.$channel.'/'.$key));
				header('HTTP/1.0 404 Not Found');
				exit(0);
			}
		} else {
			logger::warn("PODCASTS", 'Failed to create directory prefs/podcasts/'.$channel.'/'.$key);
			header('HTTP/1.0 404 Not Found');
			exit(0);
		}

		return $channel;
	}

	public function get_all_counts() {
		$counts = array();
		$result = $this->generic_sql_query(
			"SELECT PODindex, IFNULL(new, 0) AS new, IFNULL(unlistened, 0) AS unlistened FROM
				(SELECT PODindex FROM Podcasttable WHERE Subscribed = 1) AS subbed
				LEFT JOIN
				(SELECT PODindex, COUNT(PODTrackindex) AS new FROM PodcastTracktable WHERE New = 1 AND Listened = 0 AND Deleted = 0 GROUP BY PODindex) AS nw USING (PODindex)
				LEFT JOIN
				(SELECT PODindex, COUNT(PODTrackindex) AS unlistened FROM PodcastTracktable WHERE New = 0 AND Listened = 0 AND Deleted = 0 GROUP BY PODindex) AS un USING (PODindex)
			ORDER BY PODindex ASC",
			false,
			PDO::FETCH_OBJ
		);
		foreach ($result as $obj) {
			$counts[$obj->PODindex] = ['new' => $obj->new, 'unlistened' => $obj->unlistened];
		}
		return $counts;
	}

	public function check_podcast_refresh() {
		$this->check_refresh_pid();
		// Give it 59 seconds backwards grace, as the backend daemon seems to check them a minute late
		$result = $this->sql_prepare_query(false, PDO::FETCH_OBJ, null, array(),
			"SELECT PODindex FROM Podcasttable WHERE RefreshOption > 0 AND Subscribed = 1 AND NextUpdate <= ?",
			time() - 59
		);
		$updated = array('updated' => array());
		foreach ($result as $pod) {
			$retval = $this->refreshPodcast($pod->PODindex);
			if ($retval !== false)
				$updated['updated'][] = $retval;

		}
		$this->clear_refresh_pid();
		return $updated;
	}

	public function search_itunes($term) {
		logger::mark("PODCASTS", "Searching iTunes for '".$term."'");
		$this->generic_sql_query("DELETE FROM PodcastTracktable WHERE PODindex IN (SELECT PODindex FROM Podcasttable WHERE Subscribed = 0)", true);
		$this->generic_sql_query("DELETE FROM Podcasttable WHERE Subscribed = 0", true);
		$d = new url_downloader(array('url' => 'https://itunes.apple.com/search?term='.$term.'&entity=podcast'));
		if ($d->get_data_to_string()) {
			$pods = json_decode(trim($d->get_data()), true);
			foreach ($pods['results'] as $podcast) {
				if (array_key_exists('feedUrl', $podcast)) {
					// Bloody hell they can't even be consistent!
					$podcast['feedURL'] = $podcast['feedUrl'];
				}
				if (array_key_exists('feedURL', $podcast)) {
					$r = $this->check_if_podcast_is_subscribed($podcast);
					if (count($r) > 0) {
						foreach ($r as $a) {
							logger::trace("PODCASTS", "  Search found EXISTING podcast ".$a['Title']);
						}
						continue;
					}

					if (array_key_exists('artworkUrl600', $podcast) && $podcast['artworkUrl600'] != '' && $podcast['artworkUrl600'] != null) {
						$img = 'getRemoteImage.php?url='.rawurlencode($podcast['artworkUrl600']);
					} else {
						$img = 'newimages/podcast-logo.svg';
					}
					logger::log("PODCASTS", "Search found podcast : ".$podcast['collectionName']);

					// IMPORTANT NOTE. We do NOT set LastPubDate here, because that would prevent the podcasts from being refreshed
					// if we subscribe to it. (If it hasn't been browsed then we need to refresh it to get all the episodes)
					// LastPubDate will get set by refreshPodcast if we subscribe

					$this->sql_prepare_query(true, null, null, null,
						"INSERT INTO Podcasttable
						(FeedURL, NextUpdate, Image, Title, Artist, RefreshOption, SortMode, DisplayMode, DaysLive, Description, Version, Subscribed, Category)
						VALUES
						(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
						$podcast['feedUrl'],
						time(),
						$img,
						$podcast['collectionName'],
						$podcast['artistName'],
						prefs::get_pref('default_podcast_refresh_mode'),
						prefs::get_pref('default_podcast_sort_mode'),
						prefs::get_pref('default_podcast_display_mode'),
						0,
						'',
						ROMPR_PODCAST_TABLE_VERSION,
						0,
						implode(', ', array_diff($podcast['genres'], array('Podcasts')))
					);
				}
			}
		}
	}

	public function subscribe($index) {
		$this->refreshPodcast($index);
		$this->generic_sql_query("UPDATE Podcasttable SET Subscribed = 1 WHERE PODindex = ".$index, true);
	}

	private function check_if_podcast_is_subscribed($podcast) {
		return $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, null, "SELECT Title FROM Podcasttable WHERE Subscribed = 1 AND (FeedURL = ? OR (Title = ? AND Artist = ?))", $podcast['feedUrl'], $podcast['collectionName'], $podcast['artistName']);
	}

	public function setPlaybackProgress($progress, $uri, $name) {
		$podid = false;
		$pod = $this->sql_prepare_query(false, PDO::FETCH_OBJ, null, null, "SELECT PODindex, PODTrackindex FROM PodcastTracktable WHERE Link = ? OR LocalFilename = ?", $uri, $uri);
		foreach ($pod as $podcast) {
			$podid = $podcast->PODindex;
			logger::info("PODCASTS", "Adding Bookmark",$name,"at",$progress,"for track",$podcast->PODTrackindex,"in podcast",$podid);
			$this->sql_prepare_query(true, null, null, null,
				"REPLACE INTO PodBookmarktable (PODTrackindex, Bookmark, Name) VALUES (?, ?, ?)",
				$podcast->PODTrackindex,
				$progress,
				$name
			);
		}
		return $podid;
	}

	public function refresh_all_podcasts() {
		$this->check_refresh_pid();
		$result = $this->generic_sql_query("SELECT PODindex FROM Podcasttable WHERE Subscribed = 1", false, PDO::FETCH_OBJ);
		foreach ($result as $obj) {
			$this->refreshPodcast($obj->PODindex);
		}
		$this->clear_refresh_pid();
		return false;
	}

	// public function checkListened($title, $album, $artist) {
	// 	logger::mark("PODCASTS", "Checking Podcast",$album,"for track",$title);
	// 	$podid = false;
	// 	$pods = $this->sql_prepare_query(false, PDO::FETCH_OBJ, null, null,
	// 		"SELECT PODindex, PODTrackindex FROM Podcasttable JOIN PodcastTracktable USING (PODindex)
	// 		WHERE
	// 		Podcasttable.Title = ? AND
	// 		PodcastTracktable.Title = ?",
	// 		$album,
	// 		$title);
	// 	foreach ($pods as $pod) {
	// 		$podid = $pod->PODindex;
	// 		logger::log("PODCASTS", "Marking",$pod->PODTrackindex,"from",$podid,"as listened");
	// 		$this->sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET Listened = 1, New = 0 WHERE PODTrackindex = ?",$pod->PODTrackindex);
	// 		$this->sql_prepare_query(true, null, null, null, "UPDATE PodBookmarktable SET Bookmark = 0 WHERE PODTrackindex = ? AND Name = ?",$pod->PODTrackindex, 'Resume');
	// 	}
	// 	return $podid;

	// }

	public function doPodcastList($subscribed) {
		$qstring = "SELECT Podcasttable.*, 0 AS new, 0 AS unlistened FROM Podcasttable WHERE Subscribed = ".$subscribed." ORDER BY";

		$sortarray = array();
		for ($i = 0; $i < prefs::get_pref('podcast_sort_levels'); $i++) {
			if (prefs::get_pref('podcast_sort_'.$i) == 'new' || prefs::get_pref('podcast_sort_'.$i) == 'unlistened') {
				$sortarray[] = ' '.prefs::get_pref('podcast_sort_'.$i).' DESC';
			} else {
				if (count(prefs::get_pref('nosortprefixes')) > 0) {
					$qqstring = "(CASE ";
					foreach(prefs::get_pref('nosortprefixes') AS $p) {
						$phpisshitsometimes = strlen($p)+2;
						$qqstring .= "WHEN LOWER(Podcasttable.".prefs::get_pref('podcast_sort_'.$i).") LIKE '".strtolower($p).
							" %' THEN LOWER(SUBSTR(Podcasttable.".prefs::get_pref('podcast_sort_'.$i).",".$phpisshitsometimes.")) ";
					}
					$qqstring .= "ELSE LOWER(Podcasttable.".prefs::get_pref('podcast_sort_'.$i).") END) ASC";
					$sortarray[] = $qqstring;
				} else {
					$sortarray[] = ' Podcasttable.'.prefs::get_pref('podcast_sort_'.$i).' ASC';
				}
			}
		}
		$qstring .= implode(', ', $sortarray);
		$result = $this->generic_sql_query($qstring, false, PDO::FETCH_OBJ);

		foreach ($result as $obj) {
			$this->doPodcastHeader($obj, $subscribed);
		}

	}

	private function check_refresh_pid() {
		// $pid = getmypid();
		// $rpid = $this->simple_query('Value', 'Statstable', 'Item', 'PodUpPid', null);
		// if ($rpid === null) {
		//     header('HTTP/1.1 500 Internal Server Error');
		//     exit(0);
		// } else if ($rpid != 0) {
		//     header('HTTP/1.1 412 Precondition Failed');
		//     exit(0);
		// }
		// $this->generic_sql_query("UPDATE Statstable SET Value = '.$pid.' WHERE Item = 'PodUpPid'");
	}

	private function clear_refresh_pid() {
		// $this->generic_sql_query("UPDATE Statstable SET Value = 0 WHERE Item = 'PodUpPid'");
	}
}
?>
