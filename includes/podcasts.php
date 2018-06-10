<?php

if (array_key_exists('populate', $_REQUEST)) {

    chdir('..');
    include("includes/vars.php");
    include("includes/functions.php");
    include("includes/podcastfunctions.php");
    include("international.php");
    include( "backends/sql/connect.php");
    include( "skins/".$skin."/ui_elements.php");
    include("utils/phpQuery.php");
    connect_to_database();
    $subflag = 1;
    $dtz = ini_get('date.timezone');
    if (!$dtz) {
        date_default_timezone_set('UTC');
    }
    $podid = null;
    if (array_key_exists('url', $_REQUEST)) {
        getNewPodcast(rawurldecode($_REQUEST['url']));
    } else if (array_key_exists('refresh', $_REQUEST)) {
        $podid = refreshPodcast($_REQUEST['refresh']);
    } else if (array_key_exists('remove', $_REQUEST)) {
        removePodcast($_REQUEST['remove']);
    } else if (array_key_exists('listened', $_REQUEST)) {
        $podid = markAsListened(rawurldecode($_REQUEST['listened']));
    } else if (array_key_exists('removetrack', $_REQUEST)) {
        $podid = deleteTrack($_REQUEST['removetrack'], $_REQUEST['channel']);
    } else if (array_key_exists('downloadtrack', $_REQUEST)) {
        $podid = downloadTrack($_REQUEST['downloadtrack'], $_REQUEST['channel']);
    } else if (array_key_exists('markaslistened', $_REQUEST)) {
        $podid = markKeyAsListened($_REQUEST['markaslistened'], $_REQUEST['channel']);
    } else if (array_key_exists('channellistened', $_REQUEST)) {
        $podid = markChannelAsListened($_REQUEST['channellistened']);
    } else if (array_key_exists('channelundelete', $_REQUEST)) {
        $podid = undeleteFromChannel($_REQUEST['channelundelete']);
    } else if (array_key_exists('removedownloaded', $_REQUEST)) {
        $podid = removeDownloaded($_REQUEST['removedownloaded']);
    } else if (array_key_exists('option', $_REQUEST)) {
        $podid = changeOption($_REQUEST['option'], $_REQUEST['val'], $_REQUEST['channel']);
    } else if (array_key_exists('loadchannel', $_REQUEST)) {
        $podid = $_REQUEST['loadchannel'];
    } else if (array_key_exists('search', $_REQUEST)) {
        search_itunes($_REQUEST['search']);
        $subflag = 0;
    } else if (array_key_exists('subscribe', $_REQUEST)) {
        subscribe($_REQUEST['subscribe']);
    } else if (array_key_exists('getcounts', $_REQUEST)) {
        $count = get_all_counts();
        print json_encode($count);
        exit(0);
    } else if (array_key_exists('checkrefresh', $_REQUEST)) {
        $refreshers = check_podcast_refresh();
        print json_encode($refreshers);
        exit(0);
    }

    if ($podid !== null) {
        outputPodcast($podid);
    } else {
        doPodcastList($subflag);
    }
} else {

    include("includes/podcastfunctions.php");
    require_once("skins/".$skin."/ui_elements.php");
    include("utils/phpQuery.php");
    doPodcastBase();
    print '<div id="fruitbat" class="noselection fullwidth">';
    doPodcastList(1);
    print '</div>';

}

function doPodcastBase() {
    print '<div class="containerbox"><div class="configtitle textcentre expand"><b>'.get_int_text('label_podcasts').'</b></div></div>';
    print '<div id="cocksausage">';
    print '<div class="containerbox indent"><div class="expand">'.get_int_text("podcast_entrybox").'</div></div>';
    print '<div class="containerbox indent"><div class="expand"><input class="enter" id="podcastsinput" type="text" /></div>';
    print '<button class="fixed" onclick="podcasts.doPodcast(\'podcastsinput\')">'.get_int_text("label_retrieve").'</button></div>';
    print '<div class="containerbox indent"><div class="expand">'.get_int_text("label_searchfor").' (iTunes)</div></div>';
    print '<div class="containerbox indent"><div class="expand"><input class="enter" id="podcastsearch" type="text" /></div>';
    print '<button class="fixed" onclick="podcasts.search()">'.get_int_text("button_search").'</button></div>';

    print '<div class="fullwidth noselection clearfix"><img id="podsclear" class="tright icon-cancel-circled podicon clickicon padright" onclick="podcasts.clearsearch()" style="display:none;margin-bottom:4px" /></div>';
    print '</div>';
    print '<div id="podcast_search" class="fullwidth noselection padright"></div>';
}

function doPodcastList($subscribed) {
    // directoryControlHeader(null);
    $result = generic_sql_query("SELECT * FROM Podcasttable WHERE Subscribed = ".$subscribed." ORDER BY Artist, Title", false, PDO::FETCH_OBJ);
    foreach ($result as $obj) {
        doPodcastHeader($obj);
    }

}

?>
