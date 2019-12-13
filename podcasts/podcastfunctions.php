<?php

function parse_rss_feed($url, $id = false, $lastpubdate = null, $gettracks = true) {
	global $prefs;
	$url = preg_replace('#^itpc://#', 'http://', $url);
	$url = preg_replace('#^feed://#', 'http://', $url);
	logger::shout("PARSE_RSS", "Parsing Feed ".$url);
	$d = new url_downloader(array('url' => $url));
	if (!$d->get_data_to_string()) {
		header('HTTP/1.0 404 Not Found');
		print "Feed Not Found";
		logger::fail("PARSE_RSS", "  Failed to Download ".$url);
		exit;
	}

	// For debugging
	// file_put_contents('prefs/temp/feed.xml', $d->get_data());

	if ($id) {
		if (!is_dir('prefs/podcasts/'.$id)) {
			mkdir('prefs/podcasts/'.$id, 0755);
		}
		file_put_contents('prefs/podcasts/'.$id.'/feed.xml', $d->get_data());
	}
	$feed = simplexml_load_string($d->get_data());

	logger::trace("PARSE_RSS", "  Our LastPubDate is ".$lastpubdate);

	// Begin RSS Parse
	$podcast = array();
	$podcast['FeedURL'] = $url;
	$domain = preg_replace('#^(http://.*?)/.*$#', '$1', $url);
	$ppg = $feed->channel->children('ppg', TRUE);
	$m = $feed->channel->children('itunes', TRUE);
	$sy = $feed->channel->children('sy', TRUE);

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
				$podcast['RefreshOption'] = $prefs['default_podcast_refresh_mode'];
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
				$podcast['RefreshOption'] = $prefs['default_podcast_refresh_mode'];
				break;
		}
	} else {
		$podcast['RefreshOption'] = $prefs['default_podcast_refresh_mode'];
	}

	// Episode Expiry
	if ($ppg && $ppg->seriesDetails && $ppg->seriesDetails[0]->attributes()->daysLive) {
		$podcast['DaysLive'] = $ppg->seriesDetails[0]->attributes()->daysLive;
	} else {
		$podcast['DaysLive'] = -1;
	}

	// Image
	if ($feed->channel->image) {
		$podcast['Image'] = html_entity_decode($feed->channel->image->url);
		logger::log("PARSE_RSS", "  Image is ".$podcast['Image']);
	} else if ($m && $m->image) {
		$podcast['Image'] = $m->image[0]->attributes()->href;
		logger::log("PARSE_RSS", "  Image is ".$podcast['Image']);
	} else {
		$podcast['Image'] = "newimages/podcast-logo.svg";
		logger::trace("PARSE_RSS", "  No Image Found");
	}
	if (preg_match('#^/#', $podcast['Image'])) {
		// Image link with a relative URL. Duh.
		$podcast['Image'] = $domain.$image;
	}

	// Artist
	if ($m && $m->author) {
		$podcast['Artist'] = (string) $m->author;
	} else {
		$podcast['Artist'] = '';
	}

	logger::log("PARSE_RSS", "  Artist is ".$podcast['Artist']);

	// Category
	$cats = array();
	if ($m && $m->category) {
		for ($i = 0; $i < count($m->category); $i++) {
			$cat = html_entity_decode((string) $m->category[$i]->attributes()->text);
			if (!in_array($cat, $cats)) {
				$cats[] = $cat;
			}
		}
	}
	$spaz = array_diff($cats, array('Podcasts'));
	natsort($spaz);
	$podcast['Category'] = implode(', ', $spaz);
	logger::log("PARSE_RSS", "  Category is ".$podcast['Category']);

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
			download_image($podcast['Image'], $id, $podcast['Title']);
		}
	}

	// Description
	$podcast['Description'] = (string) $feed->channel->description;

	// Tracks
	$podcast['tracks'] = array();
	$podcast['LastPubDate'] = $lastpubdate;
	if ($gettracks) {
		foreach($feed->channel->item as $item) {
			$track = array();

			$m = $item->children('media', TRUE);

			// Track Title
			$track['Title'] = (string) $item->title;
			logger::log("PARSE_RSS", "  Found track ".$track['Title']);

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
			logger::log("PARSE_RSS", "    Track URI is ".$uri);

			if ($item->guid) {
				$track['GUID'] = $item->guid;
			} else {
				$track['GUID'] = $uri;
			}

			if ($uri == null) {
				logger::fail("PARSE_RSS", "    Could Not Find URI for track!");
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
						logger::warn("PARSE_RSS", "    Non-numeric duration field encountered in podcast! -",$track['Duration']);
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
			logger::log("PARSE_RSS", "    Track PubDate is ",(string) $item->pubDate,"(".$t.")");
			if ($t === false) {
				logger::warn("PARSE_RSS", "      ERROR - Could not parse episode Publication Date",(string) $item->pubDate);
			} else if ($t > $podcast['LastPubDate']) {
				logger::log("PARSE_RSS", "      This is a new episode");
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

			if ($m && $m->summary) {
				$track['Description'] = $m->summary;
			} else {
				$track['Description'] = $item->description;
			}

			$podcast['tracks'][] = $track;
		}
	}

	// if ($lastpubdate !== null) {
	//     if ($podcast['LastPubDate'] !== false && $podcast['LastPubDate'] == $lastpubdate) {
	//         logger::mark("PARSE_RSS", "Podcast has not been updated since last refresh");
	//         return false;
	//     }
	// }

	return $podcast;

}

function getNewPodcast($url, $subbed = 1, $gettracks = true) {
	global $mysqlc, $prefs;
	logger::mark("PODCASTS", "Getting podcast",$url);
	$newpodid = null;
	$podcast = parse_rss_feed($url, false, null, $gettracks);
	$r = check_if_podcast_is_subscribed(array(  'feedUrl' => $podcast['FeedURL'],
												'collectionName' => $podcast['Title'],
												'artistName' => $podcast['Artist']));
	if (count($r) > 0) {
		foreach ($r as $a) {
			logger::fail("PODCASTS", "  Already subscribed to podcast",$a['Title']);
		}
		header('HTTP/1.0 404 Not Found');
		print 'You are already to subscrtibed to '.$podcast['Title'];
		exit(0);
	}
	logger::mark("PODCASTS", "Adding New Podcast",$podcast['Title']);

	$lastupdate = calculate_best_update_time($podcast);

	if (sql_prepare_query(true, null, null, null,
		"INSERT INTO Podcasttable
		(FeedURL, LastUpdate, Image, Title, Artist, RefreshOption, SortMode, DisplayMode, DaysLive, Description, Version, Subscribed, LastPubDate, Category)
		VALUES
		(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
		$podcast['FeedURL'],
		calculate_best_update_time($podcast),
		$podcast['Image'],
		$podcast['Title'],
		$podcast['Artist'],
		$podcast['RefreshOption'],
		$prefs['default_podcast_sort_mode'],
		$prefs['default_podcast_display_mode'],
		$podcast['DaysLive'],
		$podcast['Description'],
		ROMPR_PODCAST_TABLE_VERSION,
		$subbed,
		$podcast['LastPubDate'],
		$podcast['Category']))
	{
		$newpodid = $mysqlc->lastInsertId();
		if (is_dir('prefs/podcasts/'.$newpodid)) {
			rrmdir('prefs/podcasts/'.$newpodid);
		}
		mkdir('prefs/podcasts/'.$newpodid, 0755);
		download_image($podcast['Image'], $newpodid, $podcast['Title']);
		if ($subbed == 1) {
			foreach ($podcast['tracks'] as $track) {
				if (sql_prepare_query(true, null, null, null,
					"INSERT INTO PodcastTracktable
					(PODindex, Title, Artist, Duration, PubDate, FileSize, Description, Link, Guid, New)
					VALUES
					(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
					$newpodid, $track['Title'], $track['Artist'], $track['Duration'], $track['PubDate'],
					$track['FileSize'], $track['Description'], $track['Link'], $track['GUID'], 1))
				{
					logger::log("PODCASTS", "  Added Track ".$track['Title']);
				} else {
					logger::fail("PODCASTS", "  FAILED Adding Track ".$track['Title']);
				}
			}
		}
	}
	return $newpodid;
}

function calculate_best_update_time($podcast) {

	// Note: this returns a value for LastUpdate, since that is what refresh is based on.
	// The purpose of this is try to get the refresh in sync with the podcast's publication date.

	if ($podcast['LastPubDate'] === null) {
		logger::log("PODCASTS", $podcast['Title'],"last pub date is null");
		return time();
	}
	switch ($podcast['RefreshOption']) {
		case REFRESHOPTION_NEVER:
		case REFRESHOPTION_HOURLY:
		case REFRESHOPTION_DAILY:
			return time();
			break;

	}
	logger::log("PODCASTS", "Working out best update time for ".$podcast['Title']);
	$dt = new DateTime(date('c', $podcast['LastPubDate']));
	logger::log("PODCASTS", "  Last Pub Date is ".$podcast['LastPubDate'].' ('.$dt->format('c').')');
	logger::log("PODCASTS", "  Podcast Refresh interval is ".$podcast['RefreshOption']);
	while ($dt->getTimestamp() < time()) {
		switch ($podcast['RefreshOption']) {

			case REFRESHOPTION_WEEKLY:
				$dt->modify('+1 week');
				break;

			case REFRESHOPTION_MONTHLY:
				$dt->modify('+1 month');
				break;

			default:
				logger::error("PODCASTS", "  Unknown refresh option",$podcast['RefreshOption'],"for podcast ID",$podcast['podid']);
				return time();
				break;
		}

	}
	logger::log("PODCASTS", "  Worked out update time based on pubDate and RefreshOption: ".$dt->format('r').' ('.$dt->getTImestamp().')');
	logger::log("PODCASTS", "  Give it an hour's grace");
	$dt->modify('+1 hour');

	switch ($podcast['RefreshOption']) {

		case REFRESHOPTION_WEEKLY:
			$dt->modify('-1 week');
			break;

		case REFRESHOPTION_MONTHLY:
			$dt->modify('-1 month');
			break;

	}

	logger::log("PODCASTS", "  Therefore setting lastupdate to: ".$dt->format('r').' ('.$dt->getTImestamp().')');

	return $dt->getTimestamp();

}

function download_image($url, $podid, $title) {

	$albumimage = new albumImage(array(
		'artist' => 'PODCAST',
		'albumpath' => $podid,
		'album' => $title,
		'source' => $url
	));
	if ($albumimage->get_image_if_exists() === null) {
		$albumimage->download_image();
		$albumimage->update_image_database();
	}

}

function check_podcast_upgrade($podetails, $podid, $podcast) {
	if ($podetails->Version < ROMPR_PODCAST_TABLE_VERSION) {
		if ($podcast === false) {
			logger::blurt("PODCASTS", "Podcast needs to be upgraded, must re-parse the feed");
			$podcast = parse_rss_feed($podetails->FeedURL, $podid, null);
		}
		upgrade_podcast($podid, $podetails, $podcast);
	}
}

function refreshPodcast($podid) {
	global $prefs;
	check_refresh_pid();
	logger::shout("PODCASTS", "---------------------------------------------------");
	logger::shout("PODCASTS", "Refreshing podcast ",$podid);
	$result = generic_sql_query("SELECT * FROM Podcasttable WHERE PODindex = ".$podid, false, PDO::FETCH_OBJ);
	if (count($result) > 0) {
		$podetails = $result[0];
		logger::log("PODCASTS", "  Podcast title is ".$podetails->Title);
	} else {
		logger::error("PODCASTS", "ERROR Looking up podcast ".$podid);
		return $podid;
	}
	$podcast = parse_rss_feed($podetails->FeedURL, $podid, $podetails->LastPubDate);
	if ($podetails->Subscribed == 1 && $prefs['podcast_mark_new_as_unlistened']) {
		generic_sql_query("UPDATE PodcastTracktable SET New = 0 WHERE PODindex = ".$podetails->PODindex);
	}
	if ($podcast === false) {
		check_podcast_upgrade($podetails, $podid, $podcast);
		// Podcast pubDate has not changed, hence we didn't re-parse the feed.
		// Still calculate the best next update time
		sql_prepare_query(true, null, null, null, "UPDATE Podcasttable SET LastUpdate = ? WHERE PODindex = ?",
			calculate_best_update_time(
				array(
					'LastPubDate' => $podetails->LastPubDate,
					'RefreshOption' => $podetails->RefreshOption,
					'Title' => $podetails->Title
				)
			),
			$podid);
		// Still check to keep (days to keep still needs to be honoured)
		if (check_tokeep($podetails, $podid) || $prefs['podcast_mark_new_as_unlistened']) {
			return $podid;
		} else {
			return false;
		}
	}
	check_podcast_upgrade($podetails, $podid, $podcast);
	if ($podetails->Subscribed == 0) {
		sql_prepare_query(true, null, null, null, "UPDATE Podcasttable SET Description = ?, DaysLive = ?, RefreshOption = ?, LastUpdate = ?, LastPubDate = ? WHERE PODindex = ?",
			$podcast['Description'],
			$podcast['DaysLive'],
			$podcast['RefreshOption'],
			calculate_best_update_time($podcast),
			$podcast['LastPubDate'],
			$podid);
		sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET New=?, JustUpdated=?, Listened = 0 WHERE PODindex=?", 1, 0, $podid);
	} else {
		// Make sure we use the Refresh Option from the database, otherwise it gets replaced with the value
		// calculated in parse_rss_feed
		$podcast['RefreshOption'] = $podetails->RefreshOption;
		sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET New=?, JustUpdated=? WHERE PODindex=?", 0, 0, $podid);
		sql_prepare_query(true, null, null, null, "UPDATE Podcasttable SET Description=?, LastUpdate=?, DaysLive=?, LastPubDate=? WHERE PODindex=?",
			$podcast['Description'],
			calculate_best_update_time($podcast),
			$podcast['DaysLive'],
			$podcast['LastPubDate'],
			$podid);
	}
	download_image($podcast['Image'], $podid, $podetails->Title);
	foreach ($podcast['tracks'] as $track) {
		$trackid = sql_prepare_query(false, null, 'PODTrackindex' , null, "SELECT PODTrackindex FROM PodcastTracktable WHERE Guid=? AND PODindex = ?", $track['GUID'], $podid);
		if ($trackid !== null) {
			logger::trace("PODCASTS", "  Found existing track ".$track['Title']);
			sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET JustUpdated=?, Duration=?, Link=? WHERE PODTrackindex=?",1,$track['Duration'], $track['Link'], $trackid);
		} else {
			if (sql_prepare_query(true, null, null, null,
				mb4_bodge()."INSERT INTO PodcastTracktable
				(JustUpdated, PODindex, Title, Artist, Duration, PubDate, FileSize, Description, Link, Guid, New)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
				1, $podid, $track['Title'], $track['Artist'], $track['Duration'], $track['PubDate'],
				$track['FileSize'], $track['Description'], $track['Link'], $track['GUID'], 1))
			{
				logger::log("PODCASTS", "  Added Track ".$track['Title']);
			} else {
				logger::fail("PODCASTS", "  FAILED Adding Track ".$track['Title']);
			}
		}
	}
	check_tokeep($podetails, $podid);
	clear_refresh_pid();
	return $podid;
}

function check_tokeep($podetails, $podid) {
	$retval = false;
	// Remove tracks that are no longer in the feed and haven't been downloaded
	if ($podetails->Subscribed == 1) {
		sql_prepare_query(true, null, null, null, "DELETE FROM PodcastTracktable WHERE PODindex=? AND JustUpdated=? AND Downloaded=?",$podid, 0, 0);

		// Remove tracks that have been around longer than DaysToKeep - honoring KeepDownloaded
		if ($podetails->DaysToKeep > 0) {
			$oldesttime = time() - ($podetails->DaysToKeep * 86400);
			$numthen = simple_query("COUNT(PODTrackindex)", "PodcastTracktable", 'Deleted = 0 AND PODindex', $podid, 0);
			$qstring = "UPDATE PodcastTracktable SET Deleted=1 WHERE PODindex = ".$podid." AND PubDate < ".$oldesttime." AND Deleted = 0";
			if ($podetails->KeepDownloaded == 1) {
				$qstring .= " AND Downloaded = 0";
			}
			generic_sql_query($qstring, true);
			$numnow = simple_query("COUNT(PODTrackindex)", "PodcastTracktable", 'Deleted = 0 AND PODindex', $podid, 0);
			if ($numnow != $numthen) {
				logger::log("PODCASTS", "  Old episodes were removed from podcast ID ".$podid);
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
			$num = generic_sql_query($qstring, false, null, 'num', 0);
			$getrid = $num - $podetails->NumToKeep;
			logger::log("PODCASTS", "  Num To Keep is ".$podetails->NumToKeep." and there are ".$num." episodes that can be pruned. Removing ".$getrid);
			if ($getrid > 0) {
				$qstring = "SELECT PODTrackindex FROM PodcastTracktable WHERE PODindex=".$podid." AND Deleted = 0";
				if ($podetails->KeepDownloaded == 1) {
					$qstring .= " AND Downloaded=0";
				}
				$qstring .= " ORDER BY PubDate ASC LIMIT ".$getrid;
				$pods = sql_get_column($qstring, 0);
				foreach ($pods as $i) {
					logger::trace("PODCASTS", "  Removing Track ".$i);
					generic_sql_query("UPDATE PodcastTracktable SET Deleted=1 WHERE PODTrackindex=".$i, true);
					$retval = true;
				}
			}
		}
	}
	return $retval;
}

function upgrade_podcast($podid, $podetails, $podcast) {
	$v = $podetails->Version;
	while ($v < ROMPR_PODCAST_TABLE_VERSION) {
		switch ($v) {
			case 1:
				logger::mark("PODCASTS", "Updating Podcast ".$podetails->Title." to version 2");
				foreach ($podcast['tracks'] as $track) {
					$t = sql_prepare_query(false, PDO::FETCH_OBJ, null, null, "SELECT * FROM PodcastTracktable WHERE Link=? OR OrigLink=?", $track['Link'], $track['Link']);
					foreach($t as $result) {
						logger::log("PODCASTS", "  Updating Track ".$result->Title);
						logger::trace("PODCASTS", "    GUID is ".$track['GUID']);
						$dlfilename = null;
						if ($result->Downloaded == 1) {
							$dlfilename = basename($result->Link);
							logger::log("PODCASTS", "    Track has been downloaded to ".$dlfilename);
						}
						sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET Link = ?, Guid = ?, Localfilename = ?, OrigLink = NULL WHERE PODTrackindex = ?", $track['Link'], $track['GUID'], $dlfilename, $result->PODTrackindex);
					}
				}
				generic_sql_query("UPDATE Podcasttable SET Version = 2 WHERE PODindex = ".$podid, true);
				$v++;
				break;

			case 2:
				// This will have been done by the function below
				$v++;
				break;

			case 3:
				logger::mark("PODCASTS", "Updating Podcast ".$podetails->Title." to version 4");
				sql_prepare_query(true, null, null, null, "UPDATE Podcasttable SET Version = ?, Category = ? WHERE PODindex = ?", 4, $podcast['Category'], $podid);
				$v++;
				break;

		}
	}
}

function upgrade_podcasts_to_version() {
	$pods = generic_sql_query('SELECT * FROM Podcasttable WHERE Subscribed = 1 AND Version < '.ROMPR_PODCAST_TABLE_VERSION);
	foreach ($pods as $podcast) {
		$v = $podcast['Version'];
		while ($v < ROMPR_PODCAST_TABLE_VERSION) {
			switch ($v) {
				case 2;
					logger::mark("PODCASTS", "  Updating Podcast ".$podcast['Title']." to version 3");
					$newest_track = generic_sql_query("SELECT PubDate FROM PodcastTracktable WHERE PODindex = ".$podcast['PODindex']." ORDER BY PubDate DESC LIMIT 1");
					$podcast['LastPubDate'] = $newest_track[0]['PubDate'];
					logger::log("PODCASTS", "    Last episode for this podcast was published on ".date('c', $podcast['LastPubDate']));
					switch($podcast['RefreshOption']) {
						case REFRESHOPTION_WEEKLY:
						case REFRESHOPTION_MONTHLY:
							$podcast['LastUpdate'] = calculate_best_update_time($podcast);
							break;
					}
					generic_sql_query("UPDATE Podcasttable SET LastUpdate = ".$podcast['LastUpdate'].", LastPubDate = ".$podcast['LastPubDate'].", Version = 3 WHERE PODindex = ".$podcast['PODindex']);
					$v++;
					break;

				case 3;
					// Upgrade to version 4 can only happen after feed has been re-parsed
					$v++;
					break;
			}
		}
	}
}

function outputPodcast($podid, $do_searchbox = true) {
	$result = generic_sql_query("SELECT * FROM Podcasttable WHERE PODindex = ".$podid, false, PDO::FETCH_OBJ);
	foreach ($result as $obj) {
		doPodcast($obj, $do_searchbox);
	}
}

function doPodcast($y, $do_searchbox) {

	if ($y->Subscribed == 0) {
		logger::mark("PODCASTS", "Getting feed for unsubscribed podcast ".$y->FeedURL);
		refreshPodcast($y->PODindex);
		$a = generic_sql_query("SELECT * FROM Podcasttable WHERE PODindex = ".$y->PODindex, false, PDO::FETCH_OBJ);
		if (count($a) > 0) {
			$y = $a[0];
		} else {
			logger::fail("PODCASTS", "ERROR looking up podcast",$y->FeedURL);
			return;
		}
	}

	$aa = $y->Artist;
	if ($aa != '') {
		$aa = $aa . ' - ';
	}
	$pm = $y->PODindex;
	trackControlHeader('','','podcast_'. $pm,array(array('Image' => $y->Image)));
	print '<div class="whatdoicallthis">'.format_text($y->Description).'</div>';
	if ($y->Subscribed == 1) {
		print '<div class="containerbox bumpad">';
		print '<i title="'.get_int_text("podcast_configure").'" class="icon-cog-alt podicon '.
			'clickicon openmenu fixed tooltip spinable" name="podconf_'.$pm.'"></i>';
		print '<i title="'.get_int_text("podcast_refresh").'" class="icon-refresh podicon podaction podcast clickable '.
			'clickicon fixed tooltip spinable" name="refresh_'.$pm.'"></i>';
		print '<i title="'.get_int_text("podcast_download_all").'" class="icon-download podicon '.
			'clickable clickicon podgroupload podcast fixed tooltip spinable" name="podgroupload_'.$pm.'"></i>';
		print '<i title="'.get_int_text("podcast_mark_all").'" class="icon-headphones podicon podcast podaction '.
			'clickable clickicon fixed tooltip spinable" name="channellistened_'.$pm.'"></i>';
		print '<div class="expand"></div>';
		print '<i title="'.get_int_text("podcast_undelete").'" class="icon-trash podicon podcast podaction oneeighty '.
			'clickable clickicon fixed tooltip spinable" name="channelundelete_'.$pm.'"></i>';
		print '<i title="'.get_int_text("podcast_removedownloaded").'" class="icon-download podicon podcast podaction oneeighty '.
			'clickable clickicon fixed tooltip spinable" name="removedownloaded_'.$pm.'"></i>';
		print '<i title="'.get_int_text("podcast_delete").'" class="icon-cancel-circled podicon '.
				'clickable clickicon podremove podcast fixed tooltip spinable" name="podremove_'.$pm.'"></i>';
		print '</div>';

		print '<div class="marged whatdoicallthis toggledown invisible podconfigpanel" id="podconf_'.$pm.'">';
		print '<div class="containerbox vertical podoptions">';
		print '<div class="containerbox fixed dropdown-container"><div class="divlabel">'.
			get_int_text("podcast_display").'</div>';
		print '<div class="selectholder">';
		print '<select name="DisplayMode" onchange="podcasts.changeOption(event)">';
		$options =  '<option value="'.DISPLAYMODE_ALL.'">'.get_int_text("podcast_display_all").'</option>'.
					'<option value="'.DISPLAYMODE_NEW.'">'.get_int_text("podcast_display_onlynew").'</option>'.
					'<option value="'.DISPLAYMODE_UNLISTENED.'">'.get_int_text("podcast_display_unlistened").'</option>'.
					'<option value="'.DISPLAYMODE_DOWNLOADEDNEW.'">'.get_int_text("podcast_display_downloadnew").'</option>'.
					'<option value="'.DISPLAYMODE_DOWNLOADED.'">'.get_int_text("podcast_display_downloaded").'</option>';
		print preg_replace('/(<option value="'.$y->DisplayMode.'")/', '$1 selected', $options);
		print '</select>';
		print '</div></div>';

		print '<div class="containerbox fixed dropdown-container"><div class="divlabel">'.
			get_int_text("podcast_refresh").'</div>';
		print '<div class="selectholder">';
		print '<select name="RefreshOption" onchange="podcasts.changeOption(event)">';
		$options =  '<option value="'.REFRESHOPTION_NEVER.'">'.get_int_text("podcast_refresh_never").'</option>'.
					'<option value="'.REFRESHOPTION_HOURLY.'">'.get_int_text("podcast_refresh_hourly").'</option>'.
					'<option value="'.REFRESHOPTION_DAILY.'">'.get_int_text("podcast_refresh_daily").'</option>'.
					'<option value="'.REFRESHOPTION_WEEKLY.'">'.get_int_text("podcast_refresh_weekly").'</option>'.
					'<option value="'.REFRESHOPTION_MONTHLY.'">'.get_int_text("podcast_refresh_monthly").'</option>';
		print preg_replace('/(<option value="'.$y->RefreshOption.'")/', '$1 selected', $options);
		print '</select>';
		print '</div></div>';

		print '<div class="containerbox fixed dropdown-container"><div class="divlabel">'.
			get_int_text("podcast_expire").'</div>';
		print '<div class="selectholder">';
		print '<select title="'.get_int_text("podcast_expire_tooltip").
			'" name="DaysToKeep" class="tooltip" onchange="podcasts.changeOption(event)">';
		$options =  '<option value="0">'.get_int_text("podcast_expire_never").'</option>'.
					'<option value="7">'.get_int_text("podcast_expire_week").'</option>'.
					'<option value="14">'.get_int_text("podcast_expire_2week").'</option>'.
					'<option value="30">'.get_int_text("podcast_expire_month").'</option>'.
					'<option value="60">'.get_int_text("podcast_expire_2month").'</option>'.
					'<option value="182">'.get_int_text("podcast_expire_6month").'</option>'.
					'<option value="365">'.get_int_text("podcast_expire_year").'</option>';
		print preg_replace('/(<option value="'.$y->DaysToKeep.'")/', '$1 selected', $options);
		print '</select>';
		print '</div></div>';

		print '<div class="containerbox fixed dropdown-container"><div class="divlabel">'.
			get_int_text("podcast_keep").'</div>';
		print '<div class="selectholder">';
		print '<select title="'.get_int_text("podcast_keep_tooltip").
			'" name="NumToKeep" class="tooltip" onchange="podcasts.changeOption(event)">';
		$options =  '<option value="0">'.get_int_text("podcast_keep_0").'</option>'.
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

		print '<div class="containerbox fixed dropdown-container"><div class="divlabel">'.
			get_int_text("podcast_sortmode").'</div>';
		print '<div class="selectholder">';
		print '<select name="SortMode" onchange="podcasts.changeOption(event)">';
		$options =  '<option value="'.SORTMODE_NEWESTFIRST.'">'.get_int_text("podcast_newestfirst").'</option>'.
					'<option value="'.SORTMODE_OLDESTFIRST.'">'.get_int_text("podcast_oldestfirst").'</option>';
		print preg_replace('/(<option value="'.$y->SortMode.'")/', '$1 selected', $options);
		print '</select>';
		print '</div></div>';

		print '<div class="containerbox fixed bumpad styledinputs">';
		print '<input type="checkbox" class="topcheck" id="podkd"';
		if ($y->KeepDownloaded == 1) {
			print ' checked';
		}
		print '><label for="podkd" class="tooltip" title="'.get_int_text("podcast_kd_tooltip").
			'" name="KeepDownloaded" onclick="podcasts.changeOption(event)">'.
			get_int_text("podcast_keep_downloaded").'</label></div>';

		print '<div class="containerbox fixed bumpad styledinputs">';
		print '<input type="checkbox" class="topcheck" id="podhd"';
		if ($y->HideDescriptions == 1) {
			print ' checked';
		}
		print '><label for="podhd" name="HideDescriptions" onclick="podcasts.changeOption(event)">'.
			get_int_text("podcast_hidedescriptions").'</label></div>';

		print '</div>';

		print '</div>';
	}
	if ($do_searchbox) {
		print '<div class="containerbox noselection dropdown-container"><div class="expand">
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
	logger::debug("PODCASTS", $qstring);
	$result = generic_sql_query($qstring, false, PDO::FETCH_OBJ);
	foreach ($result as $episode) {
		format_episode($y, $episode, $pm);
	}
}

function format_episode(&$y, &$item, $pm) {
	if ($item->Deleted == 1) {
		return;
	}
	if ($y->DisplayMode == DISPLAYMODE_DOWNLOADEDNEW && ($item->Downloaded == 0 && $item->New == 0))
	{
		return;
	}
	if ($y->DisplayMode == DISPLAYMODE_NEW && $item->New == 0) {
		return;
	}
	if ($y->DisplayMode == DISPLAYMODE_UNLISTENED && $item->Listened == 1) {
		// Track cannot be new and unlistened, that can't happen because it makes no sense
		return;
	}
	if ($y->DisplayMode == DISPLAYMODE_DOWNLOADED && $item->Downloaded == 0) {
		return;
	}
	print '<div class="item podcastitem">';
	if ($item->Downloaded == 1 && $y->Version > 1) {
		print '<div class="containerbox clicktrack playable draggable dropdown-container" name="'.rawurlencode(get_base_url().$item->Localfilename).'">';
	} else {
		print '<div class="containerbox clicktrack playable draggable dropdown-container" name="'.rawurlencode($item->Link).'">';
	}
	if ($y->Subscribed == 1) {
		if ($item->New == 1) {
			print '<i title="'.get_int_text("podcast_tooltip_new").
				'" class="icon-sun fixed newpodicon tooltip"></i>';
		} else if ($item->Listened == 0) {
			print '<i title="'.get_int_text("podcast_tooltip_notnew").
				'" class="icon-unlistened fixed oldpodicon tooltip"></i>';
		}
	}
	print '<div class="podtitle expand">'.htmlspecialchars(html_entity_decode($item->Title)).'</div>';
	print '<i class="fixed icon-no-response-playbutton podicon"></i>';
	print '</div>';

	if ($item->Progress > 0) {
		print '<input type="hidden" class="resumepos" value="'.$item->Progress.'" />';
		print '<input type="hidden" class="length" value="'.$item->Duration.'" />';
	}

	$pee = date(DATE_RFC2822, $item->PubDate);
	$pee = preg_replace('/ \+\d\d\d\d$/','',$pee);
	print '<div class="whatdoicallthis padright containerbox dropdown-container podtitle notbold">';
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
	print '<div id="poddesc_'.$item->PODTrackindex.'" class="'.$class.'">'.format_text(fixup_links($item->Description)).'</div>';
	// Usually very inaccurate
	// if ($item->FileSize > 0) {
	//     print '<div class="fsize">'.format_bytes($item->FileSize).'Bytes</div>';
	// }
	if ($y->Subscribed == 1) {
		print '<div class="clearfix" name="podcontrols_'.$pm.'">';
		if ($item->Downloaded == 1) {
			print '<i class="icon-floppy podicon tleft tooltip" title="'.
				get_int_text("podcast_tooltip_downloaded").'"></i>';
		} else {
			if ($item->New == 1) {
				$extraclass = ' podnewdownload';
			} else {
				$extraclass = '';
			}
			print '<i class="icon-download podicon clickable clickicon tleft podcast poddownload spinable'.$extraclass.' tooltip" title="'.
				get_int_text("podcast_tooltip_download").'" name="poddownload_'.$item->PODTrackindex.'"></i>';
		}
		if ($item->Listened == 0) {
			print '<i class="icon-headphones podicon clickable clickicon tleft podcast podmarklistened tooltip spinable" title="'.
				get_int_text("podcast_tooltip_mark").'" name="podmarklistened_'.$item->PODTrackindex.'"></i>';
		}
		print '<i class="icon-cancel-circled podicon clickable clickicon tright podtrackremove podcast tooltip spinable" title="'.
			get_int_text("podcast_tooltip_delepisode").'" name="podtrackremove_'.$item->PODTrackindex.'" ></i>';
		if ($item->Listened == 1) {
			print '<i class="icon-headphones podicon clickable clickicon tright podcast podmarkunlistened tooltip spinable oneeighty" title="'.
				get_int_text("podcast_tooltip_unlistened").'" name="podmarkunlistened_'.$item->PODTrackindex.'"></i>';
		}
		print '</div>';
	}
	print '</div>';
}

function doPodcastHeader($y) {

	$i = getDomain($y->Image);
	if ($i == "http" || $i == "https") {
		$img = "getRemoteImage.php?url=".rawurlencode($y->Image);
	} else {
		$img = $y->Image;
	}

	$aname = htmlspecialchars(html_entity_decode($y->Artist));
	if ($y->Category) {
		$aname .= '<br /><span class="playlistrow2">'.htmlspecialchars($y->Category).'</span>';
	}

	$html = albumHeader(array(
		'id' => 'podcast_'.$y->PODindex,
		'Image' => $img,
		'Searched' => 1,
		'AlbumUri' => null,
		'Year' => null,
		'Artistname' => $aname,
		'Albumname' => htmlspecialchars(html_entity_decode($y->Title)),
		'why' => null,
		'ImgKey' => 'none',
		'class' => 'podcast'
	));

	$extra = '<div class="fixed">';
	if ($y ->Subscribed == 1) {
		$uc = get_podcast_counts($y->PODindex);
		$extra .= '<span id="podnumber_'.$y->PODindex.'"';
		if ($uc['new'] > 0) {
			$extra .= ' class="newpod">'.$uc['new'].'</span>';
		} else {
			$extra .= '></span>';
		}
		if ($uc['unlistened'] > 0) {
			$extra .= '<span class="unlistenedpod">'.$uc['unlistened'].'</span>';
		} else {
			$extra .= '<span></span>';
		}
	} else {
		$extra .= '<i class="clickicon clickable clickpodsubscribe podcast icon-rss podicon tooltip spinable" title="Subscribe to this podcast"></i><input type="hidden" value="'.$y->PODindex.'" />';
	}
	$extra .= '</div>';

	// phpQuery is something like 160K of extra code. Just to do this.
	// The fact that I'm willing to include it indicates just how crap php's DOMDocument is

	// phpQuery barfs at our '&rompr_resize_size' because it's expecting an HTML entity after &
	$html = preg_replace('/&rompr_/','&amp;rompr_', $html);
	$out = addPodcastCounts($html, $extra);
	$h = $out->html();
	$html = preg_replace('/&amp;rompr_/','&rompr_', $h);
	print $html;

	print '<div id="podcast_'.$y->PODindex.'" class="indent dropmenu padright"><div class="configtitle"><div class="textcentre expand"><b>'.get_int_text('label_loading').'</b></div></div></div>';
}

function removePodcast($podid) {
	logger::mark("PODCASTS", "Removing podcast ".$podid);
	if (is_dir('prefs/podcasts/'.$podid)) {
		rrmdir('prefs/podcasts/'.$podid);
	}
	generic_sql_query("DELETE FROM Podcasttable WHERE PODindex = ".$podid, true);
	generic_sql_query("DELETE FROM PodcastTracktable WHERE PODindex = ".$podid, true);
}

function markAsListened($url) {
	$podid = false;
	$pods = sql_prepare_query(false, PDO::FETCH_OBJ, null, null, "SELECT PODindex, PODTrackindex FROM PodcastTracktable WHERE Link = ? OR Localfilename = ?", $url, basename($url));
	foreach ($pods as $pod) {
		$podid = $pod->PODindex;
		logger::mark("PODCASTS", "Marking track",$pod->PODTrackindex,"from podcast",$podid,"as listened");
		sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET Listened = 1, New = 0, Progress = 0 WHERE PODTrackindex=?",$pod->PODTrackindex);
	}
	return $podid;
}

function deleteTrack($trackid, $channel) {
	logger::mark("PODCASTS", "Marking track",$trackid,"from podcast",$channel,"as deleted");
	generic_sql_query("UPDATE PodcastTracktable SET Deleted = 1 WHERE PODTrackindex = ".$trackid, true);
	if (is_dir('prefs/podcasts/'.$channel.'/'.$trackid)) {
		rrmdir('prefs/podcasts/'.$channel.'/'.$trackid);
	}
	return $channel;
}

function markKeyAsListened($trackid, $channel) {
	logger::mark("PODCASTS", "Marking track",$trackid,"from podcast",$channel,"as listened");
	generic_sql_query("UPDATE PodcastTracktable SET Listened = 1, New = 0, Progress = 0 WHERE PODTrackindex = ".$trackid, true);
	return $channel;
}

function markKeyAsUnlistened($trackid, $channel) {
	logger::mark("PODCASTS", "Marking track",$trackid,"from podcast",$channel,"as unlistened");
	generic_sql_query("UPDATE PodcastTracktable SET Listened = 0, New = 0, Progress = 0 WHERE PODTrackindex = ".$trackid, true);
	return $channel;
}

function changeOption($option, $val, $channel) {
	logger::log("PODCASTS", "Changing Option",$option,"to",$val,"on channel",$channel);
	if ($val === 'true') {
		$val = 1;
	}
	if ($val === 'false') {
		$val = 0;
	}
	generic_sql_query("UPDATE Podcasttable SET ".$option."=".$val." WHERE PODindex=".$channel, true);
	if ($option == 'DaysToKeep' || $option == 'NumToKeep') {
		refreshPodcast($channel);
	}
	if ($option == 'RefreshOption') {
		$podcast = generic_sql_query("SELECT * FROM Podcasttable WHERE PODindex = ".$channel, false, PDO::FETCH_ASSOC);
		$dt = new DateTime(date('c', $podcast[0]['LastUpdate']));
		logger::log("PODCASTS", "Changed Refresh Option for podcast ".$channel.". Last Update Was ".$dt->format('c'));
		switch($podcast[0]['RefreshOption']) {
			case REFRESHOPTION_HOURLY:
				$dt->modify('+1 hour');
				break;
			case REFRESHOPTION_DAILY:
				$dt->modify('+24 hours');
				break;
			case REFRESHOPTION_WEEKLY:
				$dt->modify('+1 week');
				break;
			case REFRESHOPTION_MONTHLY:
				$dt->modify('+1 month');
				break;
			default:
				$dt->modify('+10 years');
				break;
		}
		$updatetime = $dt->getTimestamp();
		if ($updatetime <= time()) {
			refreshPodcast($channel);
		} else {
			generic_sql_query("UPDATE Podcasttable SET LastUpdate = ".calculate_best_update_time($podcast[0])." WHERE PODindex = ".$channel);
		}
	}
	return $channel;
}

function markChannelAsListened($channel) {
	generic_sql_query("UPDATE PodcastTracktable SET Listened = 1, New = 0, Progress = 0 WHERE PODindex = ".$channel, true);
	return $channel;
}

function mark_all_episodes_listened() {
	generic_sql_query("UPDATE PodcastTracktable SET Listened = 1, New = 0, Progress = 0 WHERE PODindex IN (SELECT PODindex FROM Podcasttable WHERE Subscribed = 1)");
	return false;
}

function undeleteFromChannel($channel) {
	generic_sql_query("UPDATE PodcastTracktable SET Downloaded=0 WHERE PODindex=".$channel." AND Deleted=1", true);
	generic_sql_query("UPDATE PodcastTracktable SET Deleted=0 WHERE PODindex=".$channel." AND Deleted=1", true);
	return $channel;
}

function undelete_all() {
	generic_sql_query("UPDATE PodcastTracktable SET Downloaded = 0 WHERE PODindex IN (SELECT PODindex FROM Podcasttable WHERE Subscribed = 1) AND Deleted = 1", true);
	generic_sql_query("UPDATE PodcastTracktable SET Deleted = 0 WHERE PODindex IN (SELECT PODindex FROM Podcasttable WHERE Subscribed = 1) AND Deleted = 1", true);
	return false;
}

function remove_all_downloaded() {
	$pods = glob('prefs/podcasts/*');
	foreach ($pods as $channel) {
		removeDownloaded(basename($channel));
	}
	return false;
}

function removeDownloaded($channel) {
	if (is_dir('prefs/podcasts/'.$channel)) {
		$things = glob('prefs/podcasts/'.$channel.'/*');
		foreach ($things as $thing) {
			if (is_dir($thing) && basename($thing) != 'albumart') {
				rrmdir($thing);
			}
		}
	}
	generic_sql_query("UPDATE PodcastTracktable SET Downloaded=0, Localfilename=NULL WHERE PODindex=".$channel, true);
	return $channel;
}

function downloadTrack($key, $channel) {
	logger::mark("PODCASTS", "Downloading track",$key,"from podcast",$channel);
	$url = null;
	$filesize = 0;
	$result = generic_sql_query("SELECT Link, FileSize FROM PodcastTracktable WHERE PODTrackindex = " . intval($key), false, PDO::FETCH_OBJ);
	foreach ($result as $obj) {
		$url = $obj->Link;
		$filesize = $obj->FileSize;
	}
	if ($url === null) {
		logger::fail("PODCASTS", "  Failed to find URL for podcast",$channel);
		return $channel;
	}
	// The file size reported in the RSS is often VERY inaccurate. Probably based on raw audio prior to converting to MP3
	// To make the progress bars look better in the GUI we attempt to read the actual filesize
	$filesize = getRemoteFilesize($url, $filesize);
	if (is_dir('prefs/podcasts/'.$channel.'/'.$key) || mkdir ('prefs/podcasts/'.$channel.'/'.$key, 0755, true)) {
		$filename = basename($url);
		$filename = preg_replace('/\?.*$/','',$filename);

		$fp = fopen('prefs/monitor.xml', 'w');
		if ($fp === false) {
			logger::warn("PODCASTS", "Failed to open monitor.xml");
			return $channel;
		}
		$xml = '<?xml version="1.0" encoding="utf-8"?><download><filename>';
		$xml = $xml . 'prefs/podcasts/'.$channel.'/'.$key.'/'.$filename;
		$xml = $xml . '</filename><filesize>'.$filesize.'</filesize></download>';
		$fp = fopen('prefs/monitor.xml', 'w');
		fwrite($fp, $xml);
		fclose($fp);
		logger::log("PODCASTS", "Downloading To prefs/podcasts/".$channel.'/'.$key.'/'.$filename);
		$d = new url_downloader(array('url' => $url));
		if ($d->get_data_to_file('prefs/podcasts/'.$channel.'/'.$key.'/'.$filename, true)) {
			sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET Downloaded=?, Localfilename=? WHERE PODTrackindex=?", 1, '/prefs/podcasts/'.$channel.'/'.$key.'/'.$filename, $key);
		} else {
			header('HTTP/1.0 404 Not Found');
			system (escapeshellarg('rm -fR prefs/podcasts/'.$channel.'/'.$key));
		}
	} else {
		logger::warn("PODCASTS", 'Failed to create directory prefs/podcasts/'.$channel.'/'.$key);
		return $channel;
	}

	return $channel;
}

function get_podcast_counts($podid) {
	if ($podid !== null) {
		$ext = ' AND PODindex = '.$podid;
	} else {
		$ext = '';
	}
	$qstring = "SELECT COUNT(PODTrackindex) AS num FROM PodcastTracktable JOIN Podcasttable USING (PODindex) WHERE Subscribed = 1 AND New = 1 AND Listened = 0 AND Deleted = 0";
	$results['new'] = generic_sql_query($qstring.$ext, false, null, 'num', 0);

	$qstring = "SELECT COUNT(PODTrackindex) AS num FROM PodcastTracktable JOIN Podcasttable USING (PODindex) WHERE Subscribed = 1 AND New = 0 AND Listened = 0 AND Deleted = 0";
	$results['unlistened'] = generic_sql_query($qstring.$ext, false, null, 'num', 0);
	return $results;
}

function get_all_counts() {
	$counts = array();
	$counts['totals'] = get_podcast_counts(null);
	$result = generic_sql_query("SELECT PODindex FROM Podcasttable WHERE Subscribed = 1", false, PDO::FETCH_OBJ);
	foreach ($result as $obj) {
		$counts[$obj->PODindex] = get_podcast_counts($obj->PODindex);
	}
	return $counts;
}

function check_podcast_refresh() {
	check_refresh_pid();
	$tocheck = array();
	$nextupdate_seconds = 2119200;
	$result = generic_sql_query("SELECT PODindex, LastUpdate, RefreshOption FROM Podcasttable WHERE RefreshOption > 0 AND Subscribed = 1", false, PDO::FETCH_OBJ);
	foreach ($result as $obj) {
		$tocheck[] = array('podid' => $obj->PODindex, 'lastupdate' => $obj->LastUpdate, 'refreshoption' => $obj->RefreshOption);
	}
	$updated = array('nextupdate' => $nextupdate_seconds, 'updated' => array());
	$now = time();
	foreach ($tocheck as $pod) {
		$dt = new DateTime(date('c', $pod['lastupdate']));
		logger::mark("PODCASTS", "Checking for refresh to podcast",$pod['podid'],'refreshoption is',$pod['refreshoption'],"LastUpdate is",$dt->format('c'));
		switch($pod['refreshoption']) {
			case REFRESHOPTION_HOURLY:
				$dt->modify('+1 hour');
				$tempnextupdate = 3600;
				break;
			case REFRESHOPTION_DAILY:
				$dt->modify('+24 hours');
				$tempnextupdate = 86400;
				break;
			case REFRESHOPTION_WEEKLY:
				$dt->modify('+1 week');
				$tempnextupdate = 604800;
				break;
			case REFRESHOPTION_MONTHLY:
				$dt->modify('+1 month');
				// Seconds in a month is roughly 2419200, but this value is TOO BIG
				// for Javascript's setTimeout which is 32 bit signed milliseconds.
				$tempnextupdate = 2119200;
				break;
			default:
				logger::log("PODCASTS", "Not automatic update option for podcast id ".$pod['podid']);
				continue 2;
		}
		$updatetime = $dt->getTimestamp();
		logger::trace("PODCASTS", "  lastupdate is",$pod['lastupdate'],"update time is",$updatetime,"current time is",$now);
		if ($updatetime <= $now) {
			$retval = refreshPodcast($pod['podid']);
			if ($retval !== false) {
				$updated[] = $retval;
			}
			if ($tempnextupdate < $nextupdate_seconds) {
				$nextupdate_seconds = $tempnextupdate;
			}
		} else {
			$a = $updatetime - $now;
			if ($a < $nextupdate_seconds) {
				$nextupdate_seconds = $a;
			}
		}
	}
	logger::log("PODCASTS", "Next update is required in",$nextupdate_seconds,"seconds");
	$updated['nextupdate'] = $nextupdate_seconds;
	clear_refresh_pid();
	return $updated;
}

function search_itunes($term) {
	global $prefs;
	logger::mark("PODCASTS", "Searching iTunes for '".$term."'");
	generic_sql_query("DELETE FROM PodcastTracktable WHERE PODindex IN (SELECT PODindex FROM Podcasttable WHERE Subscribed = 0)", true);
	generic_sql_query("DELETE FROM Podcasttable WHERE Subscribed = 0", true);
	$d = new url_downloader(array('url' => 'https://itunes.apple.com/search?term='.$term.'&entity=podcast'));
	if ($d->get_data_to_string()) {
		$pods = json_decode(trim($d->get_data()), true);
		foreach ($pods['results'] as $podcast) {
			if (array_key_exists('feedUrl', $podcast)) {
				// Bloody hell they can't even be consistent!
				$podcast['feedURL'] = $podcast['feedUrl'];
			}
			if (array_key_exists('feedURL', $podcast)) {
				$r = check_if_podcast_is_subscribed($podcast);
				if (count($r) > 0) {
					foreach ($r as $a) {
						logger::log("PODCASTS", "  Search found EXISTING podcast ".$a['Title']);
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

				sql_prepare_query(true, null, null, null,
					"INSERT INTO Podcasttable
					(FeedURL, LastUpdate, Image, Title, Artist, RefreshOption, SortMode, DisplayMode, DaysLive, Description, Version, Subscribed, Category)
					VALUES
					(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
					$podcast['feedUrl'],
					time(),
					$img,
					$podcast['collectionName'],
					$podcast['artistName'],
					$prefs['default_podcast_refresh_mode'],
					$prefs['default_podcast_sort_mode'],
					$prefs['default_podcast_display_mode'],
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

function subscribe($index) {
	refreshPodcast($index);
	generic_sql_query("UPDATE Podcasttable SET Subscribed = 1 WHERE PODindex = ".$index, true);
}

function check_if_podcast_is_subscribed($podcast) {
	return sql_prepare_query(false, PDO::FETCH_ASSOC, null, null, "SELECT Title FROM Podcasttable WHERE Subscribed = 1 AND (FeedURL = ? OR (Title = ? AND Artist = ?))", $podcast['feedUrl'], $podcast['collectionName'], $podcast['artistName']);
}

function setPlaybackProgress($progress, $uri) {
	$podid = false;
	$pod = sql_prepare_query(false, PDO::FETCH_OBJ, null, null, "SELECT PODindex, PODTrackindex FROM PodcastTracktable WHERE Link = ? OR LocalFilename = ?", $uri, $uri);
	foreach ($pod as $podcast) {
		$podid = $podcast->PODindex;
		logger::log("PODCASTS", "Updating Playback Progress for track",$podcast->PODTrackindex,"in podcast",$podid,"to",$progress);
		generic_sql_query("UPDATE PodcastTracktable SET Progress = ".$progress." WHERE PODTrackindex = ".$podcast->PODTrackindex);
	}
	return $podid;
}

function refresh_all_podcasts() {
	check_refresh_pid();
	$result = generic_sql_query("SELECT PODindex FROM Podcasttable WHERE Subscribed = 1", false, PDO::FETCH_OBJ);
	foreach ($result as $obj) {
		refreshPodcast($obj->PODindex);
	}
	clear_refresh_pid();
	return false;
}

function checkListened($title, $album, $artist) {
	logger::mark("PODCASTS", "Checking Podcast",$album,"for track",$title);
	$podid = false;
	$pods = sql_prepare_query(false, PDO::FETCH_OBJ, null, null,
		"SELECT PODindex, PODTrackindex FROM Podcasttable JOIN PodcastTracktable USING (PODindex)
		WHERE
		-- Podcasttable.Artist = ? AND
		Podcasttable.Title = ? AND
		PodcastTracktable.Title = ?",
		// $artist,
		$album,
		$title);
	foreach ($pods as $pod) {
		$podid = $pod->PODindex;
		logger::log("PODCASTS", "Marking",$pod->PODTrackindex,"from",$podid,"as listened");
		sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET Listened = 1, New = 0, Progress = 0 WHERE PODTrackindex=?",$pod->PODTrackindex);
	}
	return $podid;

}

function check_refresh_pid() {
	// $pid = getmypid();
	// $rpid = simple_query('Value', 'Statstable', 'Item', 'PodUpPid', null);
	// if ($rpid === null) {
	//     header('HTTP/1.1 500 Internal Server Error');
	//     exit(0);
	// } else if ($rpid != 0) {
	//     header('HTTP/1.1 412 Precondition Failed');
	//     exit(0);
	// }
	// generic_sql_query("UPDATE Statstable SET Value = '.$pid.' WHERE Item = 'PodUpPid'");
}

function clear_refresh_pid() {
	// generic_sql_query("UPDATE Statstable SET Value = 0 WHERE Item = 'PodUpPid'");
}

?>
