<?php

if (array_key_exists('populate', $_REQUEST)) {

    chdir('..');
    include("includes/vars.php");
    include("includes/functions.php");
    include("international.php");
    require_once("includes/podcastfunctions.php");
    include( "backends/sql/backend.php");
    include("utils/phpQuery.php");
    require_once('utils/imagefunctions.php');
    set_error_handler('handle_error', E_ALL);
    $subflag = 1;
    $dtz = ini_get('date.timezone');
    if (!$dtz) {
        date_default_timezone_set('UTC');
    }
    $podid = null;
    if (array_key_exists('url', $_REQUEST)) {
        getNewPodcast(rawurldecode($_REQUEST['url']));
    } else if (array_key_exists('refresh', $_REQUEST)) {
        $podid = array(refreshPodcast($_REQUEST['refresh']));
    } else if (array_key_exists('remove', $_REQUEST)) {
        removePodcast($_REQUEST['remove']);
    } else if (array_key_exists('listened', $_REQUEST)) {
        $podid = array(markAsListened(rawurldecode($_REQUEST['listened'])));
    } else if (array_key_exists('removetrack', $_REQUEST)) {
        $podid = array(deleteTrack($_REQUEST['removetrack'], $_REQUEST['channel']));
    } else if (array_key_exists('downloadtrack', $_REQUEST)) {
        $podid = downloadTrack($_REQUEST['downloadtrack'], $_REQUEST['channel']);
    } else if (array_key_exists('markaslistened', $_REQUEST)) {
        $podid = array(markKeyAsListened($_REQUEST['markaslistened'], $_REQUEST['channel']));
    } else if (array_key_exists('channellistened', $_REQUEST)) {
        $podid = array(markChannelAsListened($_REQUEST['channellistened']));
    } else if (array_key_exists('channelundelete', $_REQUEST)) {
        $podid = array(undeleteFromChannel($_REQUEST['channelundelete']));
    } else if (array_key_exists('setprogress', $_REQUEST)) {
        $podid = array(setPlaybackProgress($_REQUEST['setprogress'], rawurldecode($_REQUEST['track'])));
    } else if (array_key_exists('removedownloaded', $_REQUEST)) {
        $podid = array(removeDownloaded($_REQUEST['removedownloaded']));
    } else if (array_key_exists('option', $_REQUEST)) {
        $podid = array(changeOption($_REQUEST['option'], $_REQUEST['val'], $_REQUEST['channel']));
    } else if (array_key_exists('loadchannel', $_REQUEST)) {
        $podid = $_REQUEST['loadchannel'];
    } else if (array_key_exists('search', $_REQUEST)) {
        search_itunes($_REQUEST['search']);
        $subflag = 0;
    } else if (array_key_exists('subscribe', $_REQUEST)) {
        subscribe($_REQUEST['subscribe']);
    } else if (array_key_exists('getcounts', $_REQUEST)) {
        $podid = get_all_counts();
    } else if (array_key_exists('checkrefresh', $_REQUEST)) {
        $podid = check_podcast_refresh();
    } else if (array_key_exists('markalllistened', $_REQUEST)) {
        $podid = mark_all_episodes_listened();
    } else if (array_key_exists('refreshall', $_REQUEST)) {
        $podid = refresh_all_podcasts();
    } else if (array_key_exists('undeleteall', $_REQUEST)) {
        $podid = undelete_all();
    } else if (array_key_exists('removealldownloaded', $_REQUEST)) {
        $podid = remove_all_downloaded();
    }

    if ($podid === false) {
        header('HTTP/1.1 204 No Content');
    } else if (is_array($podid)) {
        if (array_key_exists(0, $podid) && $podid[0] === false) {
            header('HTTP/1.1 204 No Content');
        } else {
            header('Content-Type: application/json');
            print json_encode($podid);
        }
    } else if ($podid !== null) {
        header('Content-Type: text/htnml; charset=utf-8');
        outputPodcast($podid);
    } else {
        header('Content-Type: text/htnml; charset=utf-8');
        doPodcastList($subflag);
    }

} else {

    require_once("includes/podcastfunctions.php");
    require_once("skins/".$skin."/ui_elements.php");
    include("utils/phpQuery.php");
    doPodcastBase();
    print '<div id="fruitbat" class="noselection fullwidth">';
    doPodcastList(1);
    print '</div>';

}

function doPodcastBase() {
    global $prefs;
    print '<div class="containerbox menuitem" style="padding-left:8px">';
    print '<div class="fixed" style="padding-right:4px"><i onclick="podcasts.toggleButtons()" class="icon-menu playlisticon clickicon tooltip" title="'.get_int_text('label_podcastcontrols').'"></i></div>';
    print '<div class="configtitle textcentre expand"><b>'.get_int_text('label_podcasts').'</b></div></div>';
    print '<div id="podcastbuttons" class="invisible">';

    print '<div id="cocksausage">';
    print '<div class="containerbox indent"><div class="expand">'.get_int_text("podcast_entrybox").'</div></div>';
    print '<div class="containerbox indent"><div class="expand"><input class="enter" id="podcastsinput" type="text" /></div>';
    print '<button class="fixed iconbutton rssbutton" onclick="podcasts.doPodcast(\'podcastsinput\')"></button></div>';
    print '</div>';

    print '<div class="spacer"></div>';

    print '<div class="containerbox dropdown-container indent noselection">';
    print '<i class="icon-toggle-closed mh menu fixed" name="podcastsortoptions"></i>';
    print '<div class="expand"><b>'.get_int_text('label_global_controls').'</b></div>';
    print '</div>';

    print '<div id="podcastsortoptions" class="toggledown invisible marged">';

    print '<div class="spacer"></div>';

    print '<div class="containerbox indent bumpad">';
    print '<i class="icon-refresh smallicon clickable clickicon fixed lettuce podglobal" name="refreshall" title="'.get_int_text('podcast_refresh_all').'"></i>';
    print '<i class="icon-headphones smallicon clickable clickicon fixed lettuce podglobal" name="markalllistened" title="'.get_int_text('podcast_mark_all').'"></i>';
    print '<i class="icon-trash oneeighty smallicon clickable clickicon fixed lettuce podglobal" name="undeleteall" title="'.get_int_text('podcast_undelete').'"></i>';
    print '<i class="icon-download oneeighty smallicon clickable clickicon fixed lettuce podglobal" name="removealldownloaded" title="'.get_int_text('podcast_removedownloaded').'"></i>';
    print '</div>';

    print '<div class="spacer"></div>';

    $sortoptions = array(
        ucfirst(strtolower(get_int_text('title_title'))) => 'Title',
        get_int_text('label_publisher') => 'Artist',
        get_int_text('label_category') => 'Category',
        get_int_text('label_new_episodes') => 'new',
        get_int_text('label_unlistened_episodes') => 'unlistened'
    );

    print '<div class="containerbox indent"><b>'.get_int_text('label_sortby').'</b></div>';

    for ($count = 0; $count < $prefs['podcast_sort_levels']; $count++) {
        print '<div class="containerbox dropdown-container indent padright">';
        print '<div class="selectholder expand">';
        print '<select id="podcast_sort_'.$count.'selector" class="saveomatic">';
        $options = '';
        foreach ($sortoptions as $i => $o) {
            $options .= '<option value="'.$o.'">'.$i.'</option>';
        }
        print preg_replace('/(<option value="'.$prefs['podcast_sort_'.$count].'")/', '$1 selected', $options);
        print '</select>';
        print '</div>';
        print '</div>';
        if ($count < $prefs['podcast_sort_levels']-1) {
            print '<div class="indent playlistrow2">'.get_int_text('label_then').'</div>';
        }
    }
    print '</div>';

    print '<div class="spacer"></div>';

    print '<div class="containerbox indent"><div class="expand">'.get_int_text("label_searchfor").' (iTunes)</div></div>';
    print '<div class="containerbox indent"><div class="expand"><input class="enter" id="podcastsearch" type="text" /></div>';
    print '<button class="fixed searchbutton iconbutton" onclick="podcasts.search()"></button></div>';

    print '<div class="fullwidth noselection clearfix"><img id="podsclear" class="tright icon-cancel-circled podicon clickicon padright" onclick="podcasts.clearsearch()" style="display:none;margin-bottom:4px" /></div>';
    print '<div id="podcast_search" class="fullwidth noselection padright"></div>';
    print '</div>';

    print '<div class="menuitem configtitle textcentre brick_wide sensiblebox" style="margin-left:8px;margin-top:1em;margin-bottom:1em"><b>Subscribed Podcasts</b></div>';
}

function doPodcastList($subscribed) {
    global $prefs;
    if ($subscribed == 1) {
        $qstring = "SELECT Podcasttable.*, SUM(New = 1) AS new, SUM(Listened = 0) AS unlistened FROM Podcasttable JOIN PodcastTracktable USING(PODindex) WHERE Subscribed = 1 AND Deleted = 0 GROUP BY PODindex ORDER BY";
    } else {
        $qstring = "SELECT Podcasttable.*, 0 AS new, 0 AS unlistened FROM Podcasttable WHERE Subscribed = 0 ORDER BY";
    }
    $sortarray = array();
    for ($i = 0; $i < $prefs['podcast_sort_levels']; $i++) {
        if ($prefs['podcast_sort_'.$i] == 'new' || $prefs['podcast_sort_'.$i] == 'unlistened') {
            $sortarray[] = ' '.$prefs['podcast_sort_'.$i].' DESC';
        } else {
            $sortarray[] = ' '.$prefs['podcast_sort_'.$i].' ASC';
        }
    }
    $qstring .= implode(', ', $sortarray);
    $result = generic_sql_query($qstring, false, PDO::FETCH_OBJ);
    foreach ($result as $obj) {
        doPodcastHeader($obj);
    }

}

function handle_error($errno, $errstr, $errfile, $errline) {
    debuglog("Error ".$errno." ".$errstr." in ".$errfile." at line ".$errline,"PODCASTS");
    header('HTTP/1.1 400 Bad Request');
    exit(0);
}

?>
