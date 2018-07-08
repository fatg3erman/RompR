<?php

$pods = glob("prefs/podcasts/*");
foreach($pods as $pod) {
    if (is_dir($pod)) {
        if (file_exists($pod.'/info.xml')) {
        	debuglog("Importing Podcast ".$pod,"SCHEMA_18",5);
            $x = simplexml_load_file($pod.'/info.xml');
            $feedurl = htmlspecialchars_decode($x->feedurl);
            $lastupdate = $x->lastupdate;
            $image = $x->image;
            $title = htmlspecialchars_decode($x->album);
            $artist = htmlspecialchars_decode($x->albumartist);
            switch($x->refreshoption) {
            	case 'never':
            		$refreshoption = REFRESHOPTION_NEVER;
            		break;
            	case 'hourly':
            		$refreshoption = REFRESHOPTION_HOURLY;
            		break;
            	case 'daily':
            		$refreshoption = REFRESHOPTION_DAILY;
            		break;
            	case 'weekly':
            		$refreshoption = REFRESHOPTION_WEEKLY;
            		break;
            	case 'monthly':
            		$refreshoption = REFRESHOPTION_MONTHLY;
            		break;
            	default:
            		debuglog("  Unknown Refresh option".$x->refreshoption,"SCHEMA_18",5);
            		$refreshoption = REFRESHOPTION_NEVER;
            		break;
            }
            switch($x->sortmode) {
            	case 'newestfirst':
            		$sortmode = SORTMODE_NEWESTFIRST;
            		break;
            	case 'oldestfirst':
            		$sortmode = SORTMODE_OLDESTFIRST;
            		break;
            	default:
            		debuglog("  Unknown Sortmode option".$x->sortmode,"SCHEMA_18",5);
            		$sortmode = SORTMODE_NEWESTFIRST;
            		break;
            }
            $hidedescriptions = ($x->hidedescriptions == 'true') ? 1 : 0;
            switch($x->displaymode) {
            	case 'all':
            		$displaymode = DISPLAYMODE_ALL;
            		break;
            	case 'new':
            		$displaymode = DISPLAYMODE_NEW;
            		break;
            	case 'unlistened':
            		$displaymode = DISPLAYMODE_UNLISTENED;
            		break;
            	case 'downloadednew':
            		$displaymode = DISPLAYMODE_DOWNLOADEDNEW;
            		break;
            	case 'downloaded':
            		$displaymode = DISPLAYMODE_DOWNLOADED;
            		break;
            	default:
            		debuglog("  Unknown Displaymode option".$x->displaymode,"SCHEMA_18",5);
            		$displaymode = DISPLAYMODE_ALL;
            		break;
            }
            $daystokeep = $x->daystokeep;
            $numtokeep = $x->numtokeep;
            $keepdownloaded = ($x->keepdownloaded == 'true') ? 1 : 0;
            $autodownload = ($x->autodownload == 'true') ? 1 : 0;
            $dayslive = $x->daysLive;
            $description = htmlspecialchars_decode($x->description);
            if (sql_prepare_query(true, null, null, null,
            	"INSERT INTO Podcasttable
            	(FeedURL, LastUpdate, Image, Title, Artist, RefreshOption, SortMode, HideDescriptions, DisplayMode, DaysToKeep, NumToKeep, KeepDownloaded, AutoDownload, DaysLive, Description)
            	VALUES
            	(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            	$feedurl, $lastupdate, $image, $title, $artist, $refreshoption, $sortmode, $hidedescriptions, $displaymode, $daystokeep, $numtokeep, $keepdownloaded, $autodownload, $dayslive, $description))
            {
            	$newpodid = $mysqlc->lastInsertId();
            	foreach($x->trackList->track as $item) {
            		$title = htmlspecialchars_decode($item->title);
            		$artist = htmlspecialchars_decode($item->artist);
            		$duration = $item->duration;
            		$pubdate = strtotime($item->pubdate);
            		$filesize = $item->filesize;
            		$description = htmlspecialchars_decode($item->description);
            		$link = htmlspecialchars_decode($item->link);
            		$listened = ($item->listened == 'no') ? 0 : 1;
            		$new = ($item->new == 'yes') ? 1 : 0;
            		$deleted = ($item->deleted == 'yes') ? 1 : 0;
            		$key = $item->key;
            		if ($item->origlink) {
            			$origlink = $item->origlink;
            		} else {
            			$origlink = "NO_ORIGINAL_LINK";
            		}
            		if (is_dir($pod.'/'.$key)) {
            			$downloaded = 1;
            		} else {
            			$downloaded = 0;
            		}
		            if (sql_prepare_query(true, null, null, null,
		            	"INSERT INTO PodcastTracktable
		            	(PODindex, Title, Artist, Duration, PubDate, FileSize, Description, Link, OrigLink, Downloaded, Listened, New, Deleted)
		            	VALUES
		            	(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
		            	$newpodid, $title, $artist, $duration, $pubdate, $filesize, $description, $link, $origlink, $downloaded, $listened, $new, $deleted))
		            {
		            	debuglog("  Imported Track ".$title,"SCHEMA_18",6);
    	            	$newtrackid = $mysqlc->lastInsertId();
    	            	if (is_dir($pod.'/'.$key)) {
    	            		debuglog("  Renaming ".$pod.'/'.$key." to ".$pod.'/'.$newtrackid,"SCHEMA_18",6);
    	            		rename($pod.'/'.$key, $pod.'/'.$newtrackid);
    	            		if ($origlink != "NO_ORIGINAL_LINK") {
    	            			$fname = basename($link);
    	            			$newname = get_base_url().'/prefs/podcasts/'.$newpodid.'/'.$newtrackid.'/'.$fname;
    	            			if (sql_prepare_query(true, null, null, null, "UPDATE PodcastTracktable SET Link=? WHERE PODTrackindex=?",$newname,$newtrackid)) {
    	            				debuglog("    Updated local link for ".$fname,"SCHEMA_18",6);
    	            			} else {
    	            				debuglog("ERROR updating local link for ".$fname,"SCHEMA_18",2);
    	            			}
    	            		}
    	            	}

		            } else {
		            	debuglog("  ERROR importing track ".$title,"SCHEMA_18", 2);
		            }
            	}
            } else {
            	debuglog("ERROR Inserting Podcast ".$title." into database!","SCHEMA_18",2);
            }
            unlink($pod.'/info.xml');
            if (preg_match('#^prefs/podcasts#', $image)) {
            	$image = 'prefs/podcasts/'.$newpodid.'/'.basename($image);
    			if (sql_prepare_query(true, null, null, null, "UPDATE Podcasttable SET Image=? WHERE PODindex=?",$image,$newpodid)) {
    				debuglog("    Updated image link","SCHEMA_18",6);
    			} else {
    				debuglog("ERROR updating image link","SCHEMA_18",2);
    			}
            }
            rename($pod, "prefs/podcasts/".$newpodid);
        }
    }
}

?>
