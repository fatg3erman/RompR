<?php

function parse_rss_feed($url, $id = false) {
    $url = preg_replace('#^itpc://#', 'http://', $url);
    $url = preg_replace('#^feed://#', 'http://', $url);
    $result = url_get_contents($url);
    if ($result['status'] != "200") {
        header('HTTP/1.0 404 Not Found');
        print "Feed Not Found";
        debuglog("Failed to get ".$url,"PODCASTS",2);
        exit;
    }
    debuglog("Feed retrieved from ".$url,"PODCASTS");
    if ($id) {
        if (!is_dir('prefs/podcasts/'.$id)) {
            exec('mkdir prefs/podcasts/'.$id);
        }
        file_put_contents('prefs/podcasts/'.$id.'/feed.xml', $result['contents']);
    }
    $feed = simplexml_load_string($result['contents']);

    // Begin RSS Parse
    $podcast = array();
    $podcast['FeedURL'] = $url;
    $domain = preg_replace('#^(http://.*?)/.*$#', '$1', $url);
    $ppg = $feed->channel->children('ppg', TRUE);
    $m = $feed->channel->children('itunes', TRUE);

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
    } else {
        $podcast['RefreshOption'] = REFRESHOPTION_NEVER;
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
        $podcast['Image'] = "newimages/podcast-logo.png";
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

    // Title
    $podcast['Title'] = (string) $feed->channel->title;

    // Description
    $podcast['Description'] = (string) $feed->channel->description;

    // Tracks
    $podcast['tracks'] = array();
    foreach($feed->channel->item as $item) {
        $track = array();

        $m = $item->children('media', TRUE);

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

        if ($item->guid) {
            $track['GUID'] = $item->guid;
        } else {
            $track['GUID'] = $uri;
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
                $time += ($s * $mf);
            }
            $track['Duration'] = $time;
        }

        // Track Title
        $track['Title'] = (string) $item->title;

        // Track Author
        if ($m && $m->author) {
            $track['Artist'] = (string) $m->author;
        } else {
            $track['Artist'] = $podcast['Artist'];
        }

        // Track Publication Date
        $track['PubDate'] = strtotime((string) $item->pubDate);
        if ($item->enclosure && $item->enclosure->attributes()) {
            $track['FileSize'] = $item->enclosure->attributes()->length;
        }

        if ($m && $m->summary) {
            $track['Description'] = $m->summary;
        } else {
            $track['Description'] = $item->description;
        }

        if ($uri == null) {
            debuglog("Could Not Find URI for track!","PODCASTS",3);
            debuglog("  Track Title is ".$track['Title'],"PODCASTS",3);
            continue;
        }

        $podcast['tracks'][] = $track;
    }

    return $podcast;

}

function getNewPodcast($url) {
    global $mysqlc;
    debuglog("Getting podcast ".$url,"PODCASTS");
    $podcast = parse_rss_feed($url);
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
    if (sql_prepare_query(true, null, null, null,
        "INSERT INTO Podcasttable
        (FeedURL, LastUpdate, Image, Title, Artist, RefreshOption, DaysLive, Description, Version)
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?)",
        $podcast['FeedURL'], time(), $podcast['Image'], $podcast['Title'], $podcast['Artist'],
        $podcast['RefreshOption'], $podcast['DaysLive'], $podcast['Description'], ROMPR_PODCAST_TABLE_VERSION))
    {
        $newpodid = $mysqlc->lastInsertId();
        if (is_dir('prefs/podcasts/'.$newpodid)) {
            exec('rm -fR prefs/podcasts/'.$newpodid);
        }
        exec('mkdir prefs/podcasts/'.$newpodid);
        download_image($podcast['Image'], $newpodid);
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

function download_image($url, $podid) {
    debuglog("Downloading image ".$url." for podcast ".$podid,"PODCASTS");
    $fp = fopen('prefs/podcasts/tempimage', 'w');
    $aagh = url_get_contents($url, ROMPR_IDSTRING, false, true, false, $fp);
    fclose($fp);
    if ($aagh['status'] == "200") {
        if (preg_match('#image/(.*?)$#', $aagh['content-type'], $matches)) {
            $outfile = 'prefs/podcasts/'.$podid.'/image.'.$matches[1];
        } else {
            $outfile = 'prefs/podcasts/'.$podid.'/'.basename($url);
        }
        debuglog("  .. success. Saving as ".$outfile,"PODCASTS");
        if (file_exists($outfile)) {
            unlink($outfile);
        }
        exec('mv prefs/podcasts/tempimage "'.$outfile.'"');
        sql_prepare_query(true, null, null, null, 'UPDATE Podcasttable SET Image = ? WHERE PODindex = ?',$outfile,$podid);
    } else {
        debuglog("  .. failed to download image ".$aagh['status'],"PODCASTS");
    }
}

function refreshPodcast($podid) {
    debuglog("Refreshing podcast ".$podid,"PODCASTS");
    $result = generic_sql_query("SELECT * FROM Podcasttable WHERE PODindex = ".$podid, false, PDO::FETCH_OBJ);
    if (count($result) > 0) {
        $podetails = $result[0];
        debuglog("  Podcast title is ".$podetails->Title,"PODCASTS");
    } else {
        debuglog("ERROR Looking up podcast ".$podid,"PODCASTS",2);
        return $podid;
    }
    $podcast = parse_rss_feed($podetails->FeedURL, $podid);
    if ($podetails->Version < ROMPR_PODCAST_TABLE_VERSION) {
        upgrade_podcast($podid, $podetails, $podcast);
    }
    if ($podetails->Subscribed == 0) {
        sql_prepare_query(true, null, null, null, "UPDATE Podcasttable SET Description = ?, DaysLive = ?, RefreshOption = ?, LastUpdate = ? WHERE PODindex = ?",$podcast['Description'],$podcast['DaysLive'],$podcast['RefreshOption'],time(),$podid);
        sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET New=?, JustUpdated=? WHERE PODindex=?", 1, 0, $podid);
    } else {
        sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET New=?, JustUpdated=? WHERE PODindex=?", 0, 0, $podid);
        sql_prepare_query(true, null, null, null, "UPDATE Podcasttable SET Description=?, LastUpdate=?, DaysLive=? WHERE PODindex=?", $podcast['Description'], time(), $podcast['DaysLive'], $podid);
    }
    download_image($podcast['Image'], $podid);
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
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,? )",
                1, $podid, $track['Title'], $track['Artist'], $track['Duration'], $track['PubDate'],
                $track['FileSize'], $track['Description'], $track['Link'], $track['GUID'], 1))
            {
                debuglog("  Added Track ".$track['Title'],"PODCASTS");
            } else {
                debuglog("  FAILED Adding Track ".$track['Title'],"PODCASTS",2);
            }
        }
    }
    // Remove tracks that are no longer in the feed and haven't been downloaded
    if ($podetails->Subscribed == 1) {
        sql_prepare_query(true, null, null, null, "DELETE FROM PodcastTracktable WHERE PODindex=? AND JustUpdated=? AND Downloaded=?",$podid, 0, 0);

        // Remove tracks that have been around longer than DaysToKeep - honoring KeepDownloaded
        if ($podetails->DaysToKeep > 0) {
            $oldesttime = time() - ($podetails->DaysToKeep * 86400);
            $qstring = "UPDATE PodcastTracktable SET Deleted=1 WHERE PODindex = ".$podid." AND PubDate < ".$oldesttime." AND Deleted = 0";
            if ($podetails->KeepDownloaded == 1) {
                $qstring .= " AND Downloaded = 0";
            }
            generic_sql_query($qstring, true);
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
                }
            }
        }
    }
    return $podid;
}

function upgrade_podcast($podid, $podetails, $podcast) {
    $v = $podetails->Version;
    while ($v < ROMPR_PODCAST_TABLE_VERSION) {
        switch ($v) {
            case 1:
                debuglog("Updating Podcast ".$podetails->Title." to version ".ROMPR_PODCAST_TABLE_VERSION,"PODCASTS");
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
        }
    }
}

function doPodcast($y) {

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
    print '<div class="whatdoicallthis">'.format_text($y->Description).'</div>';
    if ($y->Subscribed == 1) {
        print '<div class="clearfix bumpad podhackshit">';
        print '<i title="'.get_int_text("podcast_delete").'" class="icon-cancel-circled podicon '.
            'clickable clickicon podremove tright fridge" name="podremove_'.$pm.'"></i>';
        print '<i title="'.get_int_text("podcast_configure").'" class="icon-cog-alt podicon clickable '.
            'clickicon podconf tleft fridge" name="podconf_'.$pm.'"></i>';
        print '<i title="'.get_int_text("podcast_refresh").'" class="icon-refresh podicon podaction clickable '.
            'clickicon tleft fridge" name="refresh_'.$pm.'"></i>';
        print '<i title="'.get_int_text("podcast_download_all").'" class="icon-download podicon '.
            'clickable clickicon podgroupload tleft fridge" name="podgroupload_'.$pm.'"></i>';
        print '<i title="'.get_int_text("podcast_mark_all").'" class="icon-headphones podicon podaction '.
            'clickable clickicon tleft fridge" name="channellistened_'.$pm.'"></i>';
        print '<i title="'.get_int_text("podcast_undelete").'" class="icon-trash podicon podaction oneeighty '.
            'clickable clickicon tleft fridge" name="channelundelete_'.$pm.'"></i>';
        print '<i title="'.get_int_text("podcast_removedownloaded").'" class="icon-download podicon podaction oneeighty '.
            'clickable clickicon tleft fridge" name="removedownloaded_'.$pm.'"></i>';
        print '</div>';
    }
    print '<div class="containerbox noselection"><div class="expand">
        <input class="enter clearbox" name="podsearcher_'.$y->PODindex.'" type="text" ';
    if (array_key_exists('searchterm', $_REQUEST)) {
        print 'value="'.urldecode($_REQUEST['searchterm']).'" ';
    }
    print '/></div><button class="fixed" onclick="podcasts.searchinpodcast('.$y->PODindex.')">'.get_int_text('button_search').'</button></div>';

    if ($y->Subscribed == 1) {
        $class = "dropmenu marged";
        if ((array_key_exists('channel', $_REQUEST) && $_REQUEST['channel'] == $pm) &&
            array_key_exists('option', $_REQUEST)) {
            // Don't rehide the config panel if we're choosing something from it
            $class .= " visible";
        }
        print '<div class="'.$class.'" id="podconf_'.$pm.'">';
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
            '" name="DaysToKeep" class="fridge" onchange="podcasts.changeOption(event)">';
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
            '" name="NumToKeep" class="fridge" onchange="podcasts.changeOption(event)">';
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
        print '><label for="podkd" class="fridge" title="'.get_int_text("podcast_kd_tooltip").
            '" name="KeepDownloaded" onclick="podcasts.changeOption(event)">'.
            get_int_text("podcast_keep_downloaded").'</label></div>';

        print '<div class="containerbox fixed bumpad styledinputs">';
        print '<input type="checkbox" class="topcheck podautodown" id="podad"';
        if ($y->AutoDownload == 1) {
            print ' checked';
        }
        print '><label for="podad" name="AutoDownload" onclick="podcasts.changeOption(event)">'.
            get_int_text("podcast_auto_download").'</label></div>';

        print '<div class="containerbox fixed bumpad styledinputs">';
        print '<input type="checkbox" class="topcheck" id="podhd"';
        if ($y->HideDescriptions == 1) {
            print ' checked';
        }
        print '><label for="podhd" name="HideDescriptions" onclick="podcasts.changeOption(event)">'.
            get_int_text("podcast_hidedescriptions").'</label></div>';

        print '</div>';
    }
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
    if ($item->Downloaded == 1 && $y->Version > 1) {
        print '<div class="clickable clicktrack item podcastitem draggable" name="'.get_base_url().'/prefs/podcasts/'.$y->PODindex.'/'.$item->PODTrackindex.'/'.$item->Localfilename.'">';
    } else {
        print '<div class="clickable clicktrack item podcastitem draggable" name="'.$item->Link.'">';
    }
    print '<div class="containerbox">';
    if ($y->Subscribed == 1) {
        if ($item->New == 1) {
            print '<i title="'.get_int_text("podcast_tooltip_new").
                '" class="icon-sun fixed newpodicon fridge"></i>';
        } else if ($item->Listened == 0) {
            print '<i title="'.get_int_text("podcast_tooltip_notnew").
                '" class="icon-unlistened fixed oldpodicon fridge"></i>';
        }
    }
    print '<div class="podtitle expand">'.htmlspecialchars(html_entity_decode($item->Title)).'</div></div>';
    $pee = date(DATE_RFC2822, $item->PubDate);
    $pee = preg_replace('/ \+\d\d\d\d$/','',$pee);
    print '<div class="whatdoicallthis padright clearfix">';
    if ($y->HideDescriptions == 0) {
        $class = 'icon-toggle-open';
    } else {
        $class = 'icon-toggle-closed';
    }

    print '<i class="'.$class.' menu mh fixed tleft" name="poddesc_'.$item->PODTrackindex.'"></i>';
    print '<span class="tleft"><i>'.$pee.'</i></span>';
    if ($item->Duration != 0) {
        print '<span class="tright">'.format_time($item->Duration).'</span>';
    }
    print '</div>';
    if ($y->HideDescriptions == 0) {
        $class = 'whatdoicallthis';
    } else {
        $class = 'invisible whatdoicallthis';
    }
    print '<div id="poddesc_'.$item->PODTrackindex.'" class="'.$class.'">'.format_text($item->Description).'</div>';
    print '<div class="fsize">'.format_bytes($item->FileSize).'Bytes</div>';
    if ($y->Subscribed == 1) {
        print '<div class="clearfix" name="podcontrols_'.$pm.'">';
        if ($item->Downloaded == 1) {
            print '<i class="icon-floppy podicon tleft fridge" title="'.
                get_int_text("podcast_tooltip_downloaded").'"></i>';
        } else {
            if ($item->New == 1) {
                $extraclass = ' podnewdownload';
            } else {
                $extraclass = '';
            }
            print '<i class="icon-download podicon clickable clickicon tleft poddownload'.$extraclass.' fridge" title="'.
                get_int_text("podcast_tooltip_download").'" name="poddownload_'.$item->PODTrackindex.'"></i>';
        }
        if ($item->Listened == 0) {
            print '<i class="icon-headphones podicon clickable clickicon tleft podmarklistened fridge" title="'.
                get_int_text("podcast_tooltip_mark").'" name="podmarklistened_'.$item->PODTrackindex.'"></i>';
        }
        print '<i class="icon-cancel-circled podicon clickable clickicon tright podtrackremove fridge" title="'.
            get_int_text("podcast_tooltip_delepisode").'" name="podtrackremove_'.$item->PODTrackindex.'" ></i>';
        print '</div>';
    }
    print '</div>';
}

function doPodcastHeader($y) {
    $aa = $y->Artist;
    if ($aa != '') {
        $aa = $aa . ' - ';
    }
    print '<div class="containerbox menuitem">';
    print '<i class="icon-toggle-closed menu podcastmenu mh fixed" romprpod="'.$y->PODindex.'" name="podcast_'.$y->PODindex.'"></i>';
    $i = getDomain($y->Image);
    if ($i == "http" || $i == "https") {
        $img = "getRemoteImage.php?url=".$y->Image;
    } else {
        $img = $y->Image;
    }
    print '<div class="smallcover fixed"><img class="smallcover" src="'.$img.'" /></div>';
    print '<div class="expand"><b>'.htmlspecialchars(html_entity_decode($aa.$y->Title)).'</b></div>';
    print '<div class="fixed padright">';
    if ($y ->Subscribed == 1) {
        $uc = get_podcast_counts($y->PODindex);
        print '<span id="podnumber_'.$y->PODindex.'"';
        if ($uc['new'] > 0) {
            print ' class="newpod">'.$uc['new'].'</span>';
        } else {
            print '></span>';
        }
        if ($uc['unlistened'] > 0) {
            print '<span class="unlistenedpod">'.$uc['unlistened'].'</span>';
        } else {
            print '<span></span>';
        }
    } else {
        print '<i class="clickicon clickable clickpodsubscribe icon-rss podicon fridge" title="Subscribe to this podcast"></i><input type="hidden" value="'.$y->PODindex.'" />';
    }
    print '</div>';
    print '</div>';
    print '<div id="podcast_'.$y->PODindex.'" class="indent dropmenu padright"></div>';
}

function removePodcast($podid) {
    debuglog("Removing podcast ".$podid,"PODCASTS");
    if (is_dir('prefs/podcasts/'.$podid)) {
        system('rm -fR prefs/podcasts/'.$podid);
    }
    generic_sql_query("DELETE FROM Podcasttable WHERE PODindex = ".$podid, true);
    generic_sql_query("DELETE FROM PodcastTracktable WHERE PODindex = ".$podid, true);
}

function markAsListened($url) {
    $podid = -1;
    $pods = sql_prepare_query(false, PDO::FETCH_OBJ, null, null, "SELECT PODindex, PODTrackindex FROM PodcastTracktable WHERE Link = ? OR Localfilename = ?", $url, basename($url));
    foreach ($pods as $pod) {
        debuglog("Marking ".$pod->PODTrackindex." from ".$podid." as listened","PODCASTS");
        $podid = $pod->PODindex;
        sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET Listened=1, New=0 WHERE PODTrackindex=?",$pod->PODTrackindex);
    }
    return $podid;
}

function deleteTrack($trackid, $channel) {
    debuglog("Marking ".$trackid." from ".$channel." as deleted","PODCASTS");
    generic_sql_query("UPDATE PodcastTracktable SET Deleted = 1 WHERE PODTrackindex = ".$trackid, true);
    if (is_dir('prefs/podcasts/'.$channel.'/'.$trackid)) {
        system('rm -fR prefs/podcasts/'.$channel.'/'.$trackid);
    }
    return $channel;
}

function markKeyAsListened($trackid, $channel) {
    debuglog("Marking ".$trackid." from ".$channel." as listened","PODCASTS");
    generic_sql_query("UPDATE PodcastTracktable SET Listened = 1, New = 0 WHERE PODTrackindex = ".$trackid, true);
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
    return $channel;
}

function markChannelAsListened($channel) {
    generic_sql_query("UPDATE PodcastTracktable SET Listened=1, New=0 WHERE PODindex=".$channel, true);
    return $channel;
}

function undeleteFromChannel($channel) {
    generic_sql_query("UPDATE PodcastTracktable SET Downloaded=0 WHERE PODindex=".$channel." AND Deleted=1", true);
    generic_sql_query("UPDATE PodcastTracktable SET Deleted=0 WHERE PODindex=".$channle." AND Deleted=1", true);
    return $channel;
}

function removeDownloaded($channel) {
    if (is_dir('prefs/podcasts/'.$channel)) {
        $things = glob('prefs/podcasts/'.$channel.'/*');
        foreach ($things as $thing) {
            if (is_dir($thing)) {
                system('rm -fR '.$thing);
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
        $fp = fopen('prefs/podcasts/'.$channel.'/'.$key.'/'.$filename, 'wb');
        if ($fp === false) {
            debuglog("Failed to open file","PODCASTS",2);
            return $channel;
        }
        $result = url_get_contents($url, ROMPR_IDSTRING, false, true, false, $fp);
        fclose($fp);
        if ($result['status'] != "200") {
            header('HTTP/1.0 404 Not Found');
            debuglog("Failed to get ".$url,"PODCASTS",2);
            debuglog("   Status was ".$result['status'],"PODCASTS",2);
            system ('rm -fR prefs/podcasts/'.$channel.'/'.$key);
            return $channel;
        }
        sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET Downloaded=?, Localfilename=? WHERE PODTrackindex=?",1,$filename,$key);
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
    // Seconds in a month is roughly 2419200, but this value is TOO BIG
    // for Javascript's setTimeout which is 32 bit signed milliseconds.
    $nextupdate_seconds = 2119200;
    $result = generic_sql_query("SELECT PODindex, LastUpdate, RefreshOption FROM Podcasttable WHERE RefreshOption > 0 AND Subscribed = 1", false, PDO::FETCH_OBJ);
    foreach ($result as $obj) {
        $tocheck[] = array('podid' => $obj->PODindex, 'lastupdate' => $obj->LastUpdate, 'refreshoption' => $obj->RefreshOption);
    }
    $updated = array('nextupdate' => $nextupdate_seconds, 'updated' => array());
    $now = time();
    foreach ($tocheck as $pod) {
        debuglog("Checking for refresh to podcast ".$pod['podid'].' refreshoption is '.$pod['refreshoption'],"PODCASTS");
        switch($pod['refreshoption']) {
            case REFRESHOPTION_HOURLY:
                $updatetime = $pod['lastupdate'] + 3600;
                $tempnextupdate = 3600;
                break;
            case REFRESHOPTION_DAILY:
                $updatetime = $pod['lastupdate'] + 86400;
                $tempnextupdate = 86400;
                break;
            case REFRESHOPTION_WEEKLY:
                $updatetime = $pod['lastupdate'] + 604800;
                $tempnextupdate = 604800;
                break;
            case REFRESHOPTION_MONTHLY:
                $updatetime = $pod['lastupdate'] + 2419200;
                $tempnextupdate = 2119200;
                break;
            default:
                debuglog("Unknown refresh option for podcast id ".$pod['podid'],"PODCASTS",3);
                continue 2;
        }
        debuglog('  lastupdate is '.$pod['lastupdate'].' update time is '.$updatetime.' current time is '.$now,"PODCASTS");
        if ($updatetime <= $now) {
            $updated['updated'][] = refreshPodcast($pod['podid']);
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
    debuglog("Searching iTunes for podcasts '".$term."'","PODCASTS",6);
    generic_sql_query("DELETE FROM PodcastTracktable WHERE PODindex IN (SELECT PODindex FROM Podcasttable WHERE Subscribed = 0)", true);
    generic_sql_query("DELETE FROM Podcasttable WHERE Subscribed = 0", true);
    $content = url_get_contents('https://itunes.apple.com/search?term='.$term.'&entity=podcast');
    if ($content['status'] == '200') {
        $pods = json_decode($content['contents'], true);
        foreach ($pods['results'] as $podcast) {

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
            debuglog("Search found podcast : ".$podcast['collectionName']);
            sql_prepare_query(true, null, null, null,
                "INSERT INTO Podcasttable
                (FeedURL, LastUpdate, Image, Title, Artist, RefreshOption, DaysLive, Description, Version, Subscribed)
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                $podcast['feedUrl'], time(), $img, $podcast['collectionName'], $podcast['artistName'],
                REFRESHOPTION_NEVER, 0, '', ROMPR_PODCAST_TABLE_VERSION, 0
            );
        }
    } else {
        debuglog("SEARCH ERROR - Status was ".$content['status'],"PODCASTS",3);
    }

}

function subscribe($index) {
    refreshPodcast($index);
    generic_sql_query("UPDATE Podcasttable SET Subscribed = 1 WHERE PODindex = ".$index, true);
}

function check_if_podcast_is_subscribed($podcast) {
    return sql_prepare_query(false, PDO::FETCH_ASSOC, null, null, "SELECT Title FROM Podcasttable WHERE Subscribed = 1 AND (FeedURL = ? OR (Title = ? AND Artist = ?))", $podcast['feedUrl'], $podcast['collectionName'], $podcast['artistName']);
}

?>
