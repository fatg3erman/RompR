<?php

function parse_rss_feed($url, $id = false, $lastpubdate = null, $gettracks = true) {
    global $prefs;
    $url = preg_replace('#^itpc://#', 'http://', $url);
    $url = preg_replace('#^feed://#', 'http://', $url);
    $d = new url_downloader(array('url' => $url));
    if (!$d->get_data_to_string()) {
        header('HTTP/1.0 404 Not Found');
        print "Feed Not Found";
        debuglog("Failed to get ".$url,"PODCASTS",2);
        exit;
    }

    // For debugging
    file_put_contents('prefs/temp/feed.xml', $d->get_data());

    if ($id) {
        if (!is_dir('prefs/podcasts/'.$id)) {
            mkdir('prefs/podcasts/'.$id, 0755);
        }
        file_put_contents('prefs/podcasts/'.$id.'/feed.xml', $d->get_data());
    }
    $feed = simplexml_load_string($d->get_data());

    debuglog("Parsing Feed ".$url,"PODCASTS");

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
                $podcast['RefreshOption'] = REFRESHOPTION_NEVER;
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
                $podcast['RefreshOption'] = REFRESHOPTION_NEVER;
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
        debuglog("Image is ".$podcast['Image'],"PODCASTS");
    } else if ($m && $m->image) {
        $podcast['Image'] = $m->image[0]->attributes()->href;
    } else {
        $podcast['Image'] = "newimages/podcast-logo.svg";
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

    debuglog("  Artist is ".$podcast['Artist'],"PODCASTS");

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
    debuglog("  Category is ".$podcast['Category'],"PODCASTS");

    // Title
    $podcast['Title'] = (string) $feed->channel->title;

    // Description
    $podcast['Description'] = (string) $feed->channel->description;

    // Tracks
    $podcast['tracks'] = array();
    $podcast['LastPubDate'] = null;
    if ($gettracks) {
        foreach($feed->channel->item as $item) {
            $track = array();

            $m = $item->children('media', TRUE);

            // Track Title
            $track['Title'] = (string) $item->title;
            debuglog("  Found track ".$track['Title'],"PODCASTS",8);

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
            debuglog("    Track URI is ".$uri,"PODCASTS",8);

            if ($item->guid) {
                $track['GUID'] = $item->guid;
            } else {
                $track['GUID'] = $uri;
            }

            if ($uri == null) {
                debuglog("Could Not Find URI for track!","PODCASTS",3);
                debuglog("  Track Title is ".$track['Title'],"PODCASTS",3);
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
                        debuglog("Non-numeric duration field encountered in podcast! - ".$track['Duration'],"PODCASTS",4);
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
            if ($podcast['LastPubDate'] === null || $t > $podcast['LastPubDate']) {
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

    if ($lastpubdate !== null) {
        if ($podcast['LastPubDate'] == $lastpubdate) {
            debuglog("Podcast has not been updated since last refresh","PODCASTS");
            return false;
        }
    }

    return $podcast;

}

function getNewPodcast($url, $subbed = 1, $gettracks = true) {
    global $mysqlc, $prefs;
    debuglog("Getting podcast ".$url,"PODCASTS");
    $newpodid = null;
    $podcast = parse_rss_feed($url, false, null, $gettracks);
    $r = check_if_podcast_is_subscribed(array(  'feedUrl' => $podcast['FeedURL'],
                                                'collectionName' => $podcast['Title'],
                                                'artistName' => $podcast['Artist']));
    if (count($r) > 0) {
        foreach ($r as $a) {
            debuglog("  Already subscribed to podcast ".$a['Title'],"PODCASTS");
        }
        header('HTTP/1.0 404 Not Found');
        print 'You are already to subscrtibed to '.$podcast['Title'];
        exit(0);
    }
    debuglog("Adding New Podcast ".$podcast['Title'],"PODCASTS");

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
                    debuglog("  Added Track ".$track['Title'],"PODCASTS");
                } else {
                    debuglog("  FAILED Adding Track ".$track['Title'],"PODCASTS",2);
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
        debuglog($podcast['Title']." last pub date is null","PODCASTS");
        return time();
    }
    switch ($podcast['RefreshOption']) {
        case REFRESHOPTION_NEVER:
        case REFRESHOPTION_HOURLY:
        case REFRESHOPTION_DAILY:
            return time();
            break;

    }
    debuglog("Working out best update time for ".$podcast['Title'],"PODCASTS");
    $dt = new DateTime(date('c', $podcast['LastPubDate']));
    debuglog("  Last Pub Date is ".$podcast['LastPubDate'].' ('.$dt->format('c').')',"PODCASTS");
    debuglog("  Podcast Refresh interval is ".$podcast['RefreshOption'],"PODCASTS");
    while ($dt->getTimestamp() < time()) {
        switch ($podcast['RefreshOption']) {

            case REFRESHOPTION_WEEKLY:
                $dt->modify('+1 week');
                break;

            case REFRESHOPTION_MONTHLY:
                $dt->modify('+1 month');
                break;

            default:
                debuglog("  Unknown refresh option for podcast ID ".$podcast['podid'], "PODCASTS");
                return time();
                break;
        }

    }
    debuglog("  Worked out update time based on pubDate and RefreshOption: ".$dt->format('r').' ('.$dt->getTImestamp().')',"PODCASTS");
    debuglog("  Give it an hour's grace","PODCASTS");
    $dt->modify('+1 hour');

    switch ($podcast['RefreshOption']) {

        case REFRESHOPTION_WEEKLY:
            $dt->modify('-1 week');
            break;

        case REFRESHOPTION_MONTHLY:
            $dt->modify('-1 month');
            break;

    }

    debuglog("  Therefore setting lastupdate to: ".$dt->format('r').' ('.$dt->getTImestamp().')',"PODCASTS");

    return $dt->getTimestamp();

}

function download_image($url, $podid, $title) {

    $albumimage = new albumImage(array(
        'artist' => 'PODCAST',
        'albumpath' => $podid,
        'album' => $title,
        'source' => $url
    ));
    $albumimage->download_image();
    $albumimage->update_image_database();

}

function check_podcast_upgrade($podetails, $podid, $podcast) {
    if ($podetails->Version < ROMPR_PODCAST_TABLE_VERSION) {
        if ($podcast === false) {
            debuglog("Podcast needs to be upgraded, must re-parse the feed","PODCASTS");
            $podcast = parse_rss_feed($podetails->FeedURL, $podid, null);
        }
        upgrade_podcast($podid, $podetails, $podcast);
    }
}

function refreshPodcast($podid) {
    global $prefs;
    debuglog("Refreshing podcast ".$podid,"PODCASTS");
    $result = generic_sql_query("SELECT * FROM Podcasttable WHERE PODindex = ".$podid, false, PDO::FETCH_OBJ);
    if (count($result) > 0) {
        $podetails = $result[0];
        debuglog("  Podcast title is ".$podetails->Title,"PODCASTS");
    } else {
        debuglog("ERROR Looking up podcast ".$podid,"PODCASTS",2);
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
            debuglog("  Found existing track ".$track['Title'],"PODCASTS");
            sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET JustUpdated=?, Duration=?, Link=? WHERE PODTrackindex=?",1,$track['Duration'], $track['Link'], $trackid);
        } else {
            if (sql_prepare_query(true, null, null, null,
                "INSERT INTO PodcastTracktable
                (JustUpdated, PODindex, Title, Artist, Duration, PubDate, FileSize, Description, Link, Guid, New)
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                1, $podid, $track['Title'], $track['Artist'], $track['Duration'], $track['PubDate'],
                $track['FileSize'], $track['Description'], $track['Link'], $track['GUID'], 1))
            {
                debuglog("  Added Track ".$track['Title'],"PODCASTS");
            } else {
                debuglog("  FAILED Adding Track ".$track['Title'],"PODCASTS",2);
            }
        }
    }
    check_tokeep($podetails, $podid);
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
                debuglog("  Old episodes were removed from podcast ID ".$podid,"PODCASTS");
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
            debuglog("  Num To Keep is ".$podetails->NumToKeep." and there are ".$num." episodes that can be pruned. Removing ".$getrid,"PODCASTS");
            if ($getrid > 0) {
                $qstring = "SELECT PODTrackindex FROM PodcastTracktable WHERE PODindex=".$podid." AND Deleted = 0";
                if ($podetails->KeepDownloaded == 1) {
                    $qstring .= " AND Downloaded=0";
                }
                $qstring .= " ORDER BY PubDate ASC LIMIT ".$getrid;
                $pods = sql_get_column($qstring, 'PODTrackindex');
                foreach ($pods as $i) {
                    debuglog("  Removing Track ".$i,"PODCASTS");
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
                debuglog("Updating Podcast ".$podetails->Title." to version 2","PODCASTS");
                foreach ($podcast['tracks'] as $track) {
                    $t = sql_prepare_query(false, PDO::FETCH_OBJ, null, null, "SELECT * FROM PodcastTracktable WHERE Link=? OR OrigLink=?", $track['Link'], $track['Link']);
                    foreach($t as $result) {
                        debuglog("  Updating Track ".$result->Title,"PODCASTS");
                        debuglog("    GUID is ".$track['GUID'],"PODCASTS");
                        $dlfilename = null;
                        if ($result->Downloaded == 1) {
                            $dlfilename = basename($result->Link);
                            debuglog("    Track has been downloaded to ".$dlfilename,"PODCASTS");
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
                debuglog("Updating Podcast ".$podetails->Title." to version 4","PODCASTS");
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
                    debuglog("  Updating Podcast ".$podcast['Title']." to version 3","PODCASTS");
                    $newest_track = generic_sql_query("SELECT PubDate FROM PodcastTracktable WHERE PODindex = ".$podcast['PODindex']." ORDER BY PubDate DESC LIMIT 1");
                    $podcast['LastPubDate'] = $newest_track[0]['PubDate'];
                    debuglog("    Last episode for this podcast was published on ".date('c', $podcast['LastPubDate']),"PODCASTS");
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
        debuglog("Getting feed for unsubscribed podcast ".$y->FeedURL,"PODCASTS");
        refreshPodcast($y->PODindex);
        $a = generic_sql_query("SELECT * FROM Podcasttable WHERE PODindex = ".$y->PODindex, false, PDO::FETCH_OBJ);
        if (count($a) > 0) {
            $y = $a[0];
        } else {
            debuglog("ERROR looking up podcast","PODCASTS");
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
        print '<i title="'.get_int_text("podcast_configure").'" class="icon-cog-alt podicon clickable '.
            'clickicon podconf fixed tooltip" name="podconf_'.$pm.'"></i>';
        print '<i title="'.get_int_text("podcast_refresh").'" class="icon-refresh podicon podaction clickable '.
            'clickicon fixed tooltip" name="refresh_'.$pm.'"></i>';
        print '<i title="'.get_int_text("podcast_download_all").'" class="icon-download podicon '.
            'clickable clickicon podgroupload fixed tooltip" name="podgroupload_'.$pm.'"></i>';
        print '<i title="'.get_int_text("podcast_mark_all").'" class="icon-headphones podicon podaction '.
            'clickable clickicon fixed tooltip" name="channellistened_'.$pm.'"></i>';
        print '<i title="'.get_int_text("podcast_undelete").'" class="icon-trash podicon podaction oneeighty '.
            'clickable clickicon fixed tooltip" name="channelundelete_'.$pm.'"></i>';
        print '<i title="'.get_int_text("podcast_removedownloaded").'" class="icon-download podicon podaction oneeighty '.
            'clickable clickicon fixed tooltip" name="removedownloaded_'.$pm.'"></i>';
        print '<div class="expand"></div>';
        print '<i title="'.get_int_text("podcast_delete").'" class="icon-cancel-circled podicon '.
                'clickable clickicon podremove fixed tooltip" name="podremove_'.$pm.'"></i>';
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

        print '</div>';

        print '<div class="containerbox fixed bumpad styledinputs">';
        print '<input type="checkbox" class="topcheck" id="podkd"';
        if ($y->KeepDownloaded == 1) {
            print ' checked';
        }
        print '><label for="podkd" class="tooltip" title="'.get_int_text("podcast_kd_tooltip").
            '" name="KeepDownloaded" onclick="podcasts.changeOption(event)">'.
            get_int_text("podcast_keep_downloaded").'</label></div>';

        // print '<div class="containerbox fixed bumpad styledinputs">';
        // print '<input type="checkbox" class="topcheck podautodown" id="podad"';
        // if ($y->AutoDownload == 1) {
        //     print ' checked';
        // }
        // print '><label for="podad" name="AutoDownload" onclick="podcasts.changeOption(event)">'.
        //     get_int_text("podcast_auto_download").'</label></div>';

        print '<div class="containerbox fixed bumpad styledinputs">';
        print '<input type="checkbox" class="topcheck" id="podhd"';
        if ($y->HideDescriptions == 1) {
            print ' checked';
        }
        print '><label for="podhd" name="HideDescriptions" onclick="podcasts.changeOption(event)">'.
            get_int_text("podcast_hidedescriptions").'</label></div>';

        print '</div>';
    }
    if ($do_searchbox) {
        print '<div class="containerbox noselection"><div class="expand">
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
    debuglog($qstring,"PODCASTS");
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
        print '<div class="containerbox clickable clicktrack playable draggable dropdown-container" name="'.get_base_url().$item->Localfilename.'">';
    } else {
        print '<div class="containerbox clickable clicktrack playable draggable dropdown-container" name="'.$item->Link.'">';
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

    print '<i class="'.$class.' menu mh fixed" name="poddesc_'.$item->PODTrackindex.'"></i>';
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
            print '<i class="icon-download podicon clickable clickicon tleft poddownload'.$extraclass.' tooltip" title="'.
                get_int_text("podcast_tooltip_download").'" name="poddownload_'.$item->PODTrackindex.'"></i>';
        }
        if ($item->Listened == 0) {
            print '<i class="icon-headphones podicon clickable clickicon tleft podmarklistened tooltip" title="'.
                get_int_text("podcast_tooltip_mark").'" name="podmarklistened_'.$item->PODTrackindex.'"></i>';
        }
        print '<i class="icon-cancel-circled podicon clickable clickicon tright podtrackremove tooltip" title="'.
            get_int_text("podcast_tooltip_delepisode").'" name="podtrackremove_'.$item->PODTrackindex.'" ></i>';
        print '</div>';
    }
    print '</div>';
}

function doPodcastHeader($y) {

    $i = getDomain($y->Image);
    if ($i == "http" || $i == "https") {
        $img = "getRemoteImage.php?url=".$y->Image;
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
        $extra .= '<i class="clickicon clickable clickpodsubscribe icon-rss podicon tooltip" title="Subscribe to this podcast"></i><input type="hidden" value="'.$y->PODindex.'" />';
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

    print '<div id="podcast_'.$y->PODindex.'" class="indent dropmenu padright"><div class="configtitle textcentre"><b>'.get_int_text('label_loading').'</b></div></div>';
}

function removePodcast($podid) {
    debuglog("Removing podcast ".$podid,"PODCASTS");
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
        debuglog("Marking ".$pod->PODTrackindex." from ".$podid." as listened","PODCASTS");
        sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET Listened = 1, New = 0, Progress = 0 WHERE PODTrackindex=?",$pod->PODTrackindex);
    }
    return $podid;
}

function deleteTrack($trackid, $channel) {
    debuglog("Marking ".$trackid." from ".$channel." as deleted","PODCASTS");
    generic_sql_query("UPDATE PodcastTracktable SET Deleted = 1 WHERE PODTrackindex = ".$trackid, true);
    if (is_dir('prefs/podcasts/'.$channel.'/'.$trackid)) {
        rrmdir('prefs/podcasts/'.$channel.'/'.$trackid);
    }
    return $channel;
}

function markKeyAsListened($trackid, $channel) {
    debuglog("Marking ".$trackid." from ".$channel." as listened","PODCASTS");
    generic_sql_query("UPDATE PodcastTracktable SET Listened = 1, New = 0, Progress = 0 WHERE PODTrackindex = ".$trackid, true);
    return $channel;
}

function changeOption($option, $val, $channel) {
    debuglog("Changing Option ".$option." to ".$val." on channel ".$channel,"PODCASTS");
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
        debuglog("Changed Refresh Option for podcast ".$channel.". Last Update Was ".$dt->format('c'),"PODCASTS");
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
    debuglog("Downloading ".$key." from ".$channel,"PODCASTS");
    $url = null;
    $filesize = 0;
    $result = generic_sql_query("SELECT Link, FileSize FROM PodcastTracktable WHERE PODTrackindex = ".$key, false, PDO::FETCH_OBJ);
    foreach ($result as $obj) {
        $url = $obj->Link;
        $filesize = $obj->FileSize;
    }
    if ($url === null) {
        debuglog("  Failed to find URL for podcast","PODCASTS",3);
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
            debuglog("Failed to open monitor.xml","PODCASTS",2);
            return $channel;
        }
        $xml = '<?xml version="1.0" encoding="utf-8"?><download><filename>';
        $xml = $xml . 'prefs/podcasts/'.$channel.'/'.$key.'/'.$filename;
        $xml = $xml . '</filename><filesize>'.$filesize.'</filesize></download>';
        $fp = fopen('prefs/monitor.xml', 'w');
        fwrite($fp, $xml);
        fclose($fp);
        debuglog('Downloading To prefs/podcasts/'.$channel.'/'.$key.'/'.$filename,"PODCASTS");
        $d = new url_downloader(array('url' => $url));
        if ($d->get_data_to_file('prefs/podcasts/'.$channel.'/'.$key.'/'.$filename, true)) {
            sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET Downloaded=?, Localfilename=? WHERE PODTrackindex=?", 1, '/prefs/podcasts/'.$channel.'/'.$key.'/'.$filename, $key);
        } else {
            header('HTTP/1.0 404 Not Found');
            system ('rm -fR prefs/podcasts/'.$channel.'/'.$key);
        }
    } else {
        debuglog('Failed to create directory prefs/podcasts/'.$channel.'/'.$key,"PODCASTS",2);
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
        debuglog("Checking for refresh to podcast ".$pod['podid'].' refreshoption is '.$pod['refreshoption']." LastUpdate is ".$dt->format('c'),"PODCASTS");
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
                debuglog("Not automatic update option for podcast id ".$pod['podid'],"PODCASTS",3);
                continue 2;
        }
        $updatetime = $dt->getTimestamp();
        debuglog('  lastupdate is '.$pod['lastupdate'].' update time is '.$updatetime.' current time is '.$now,"PODCASTS");
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
    debuglog('Next update is required in '.$nextupdate_seconds.' seconds',"PODCASTS");
    $updated['nextupdate'] = $nextupdate_seconds;
    return $updated;
}

function search_itunes($term) {
    global $prefs;
    debuglog("Searching iTunes for podcasts '".$term."'","PODCASTS",6);
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
                        debuglog("  Search found EXISTING podcast ".$a['Title'],"PODCASTS");
                    }
                    continue;
                }

                if (array_key_exists('artworkUrl600', $podcast) && $podcast['artworkUrl600'] != '' && $podcast['artworkUrl600'] != null) {
                    $img = 'getRemoteImage.php?url='.$podcast['artworkUrl600'];
                } else {
                    $img = 'newimages/podcast-logo.svg';
                }
                debuglog("Search found podcast : ".$podcast['collectionName'], "PODCASTS");

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
        debuglog("Updating Playback Progress for Podcast ".$podcast->PODTrackindex." in channel ".$podid." to ".$progress,"PODCASTS");
        generic_sql_query("UPDATE PodcastTracktable SET Progress = ".$progress." WHERE PODTrackindex = ".$podcast->PODTrackindex);
    }
    return $podid;
}

function refresh_all_podcasts() {
    $result = generic_sql_query("SELECT PODindex FROM Podcasttable WHERE Subscribed = 1", false, PDO::FETCH_OBJ);
    foreach ($result as $obj) {
        refreshPodcast($obj->PODindex);
    }
    return false;
}

?>
