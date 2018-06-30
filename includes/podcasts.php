<?php

if (array_key_exists('populate', $_REQUEST)) {

    chdir('..');
    include("includes/vars.php");
    include("includes/functions.php");
    require_once("includes/podcastfunctions.php");
    include("international.php");
    include( "backends/sql/connect.php");
    include( "skins/".$skin."/ui_elements.php");
    include("utils/phpQuery.php");
    connect_to_database();
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
    print '<div class="fixed" style="padding-right:4px"><i onclick="podcasts.toggleButtons()" class="icon-menu playlisticon clickicon"></i></div>';
    print '<div class="configtitle textcentre expand"><b>'.get_int_text('label_podcasts').'</b></div></div>';
    print '<div id="podcastbuttons" class="invisible">';

    print '<div id="cocksausage">';
    print '<div class="containerbox indent"><div class="expand">'.get_int_text("podcast_entrybox").'</div></div>';
    print '<div class="containerbox indent"><div class="expand"><input class="enter" id="podcastsinput" type="text" /></div>';
    print '<button class="fixed" onclick="podcasts.doPodcast(\'podcastsinput\')">'.get_int_text("label_retrieve").'</button></div>';
    print '</div>';
    
    print '<div class="containerbox indent"><div class="expand">'.get_int_text("label_searchfor").' (iTunes)</div></div>';
    print '<div class="containerbox indent"><div class="expand"><input class="enter" id="podcastsearch" type="text" /></div>';
    print '<button class="fixed" onclick="podcasts.search()">'.get_int_text("button_search").'</button></div>';

    print '<div class="fullwidth noselection clearfix"><img id="podsclear" class="tright icon-cancel-circled podicon clickicon padright" onclick="podcasts.clearsearch()" style="display:none;margin-bottom:4px" /></div>';
    print '<div id="podcast_search" class="fullwidth noselection padright"></div>';
    print '</div>';
}

function doPodcastList($subscribed) {
    // directoryControlHeader(null);
    $result = generic_sql_query("SELECT * FROM Podcasttable WHERE Subscribed = ".$subscribed." ORDER BY Artist, Title", false, PDO::FETCH_OBJ);
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
