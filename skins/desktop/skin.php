<body class="desktop">
<div id="pset" class="invisible"></div>
<div id="notifications"></div>

<div id="infobar" class="coloured containerbox">
    <div id="buttonbox" class="fixed">
        <div id="buttonholder" class="containerbox vertical bordered infobarlayout">
            <div id="buttons" class="fixed">
<?php
                print '<i title="'.get_int_text('button_previous').
                    '" class="icon-fast-backward clickicon controlbutton-small lettuce"></i>';
                print '<i title="'.get_int_text('button_play').
                    '" class="icon-play-circled shiftleft clickicon controlbutton lettuce"></i>';
                print '<i title="'.get_int_text('button_stop').
                    '" class="icon-stop-1 shiftleft2 clickicon controlbutton-small lettuce"></i>';
                print '<i title="'.get_int_text('button_stopafter').
                    '" class="icon-to-end-1 shiftleft3 clickicon controlbutton-small lettuce"></i>';
                print '<i title="'.get_int_text('button_next').
                    '" class="icon-fast-forward shiftleft4 clickicon controlbutton-small lettuce"></i>';
?>
            </div>
            <div id="progress" class="fixed"></div>
            <div id="playbackTime" class="fixed">
            </div>
        </div>
    </div>

    <div id="volumebox" class="fixed">
        <div class="infobarlayout bordered containerbox vertical">
<?php
            print '<div title="'.get_int_text('button_volume').
                '" id="volumecontrol" class="lettuce expand containerbox vertical"><div id="volume" class="expand"></div></div>';
            include('player/mpd/outputs.php');
            if (count($outputdata) == 1) {
                // There's only one output so we'll treat it like a Mute button
                print '<div class="tooltip fixed" title="'.$outputdata[0]['outputname'].'" style="height:14px">';
                $f = ($outputdata[0]['outputname'] == "Mute") ? 0 : 1;
                $c = ($outputdata[0]['outputenabled'] == $f) ? 'icon-output' : 'icon-output-mute';
                print '<i id="mutebutton" onclick="player.controller.doMute()" class="'.$c.' outhack clickicon"></i>';
                print '</div>';
            } else {
                print '<div class="tooltip fixed" title="'.get_int_text('config_audiooutputs').'" style="height:14px">';
                print '<i id="mutebutton" onclick="toggleAudioOutputs()" class="icon-sliders outhack clickicon"></i>';
                print '</div>';
            }

?>
        </div>
    </div>

<?php
    if (count($outputdata) > 1) {
        print '<div id="outputbox" class="fixed" style="display:none">';
        print '<div class="infobarlayout bordered">';
        print '<div style="padding:4px">';
        printOutputCheckboxes();
        print '</div>';
        print '</div>';
        print '</div>';
    }

?>
    <div id="patrickmoore" class="infobarlayout bordered noselection expand containerbox">
        <div id="albumcover" class="fixed">
            <img id="albumpicture" class="notexist" />
        </div>
        <div id="firefoxisshitwrapper" class="expand">
            <div id="nowplaying">
                <div id="nptext"></div>
            </div>
            <div id="amontobin" class="clearfix">
                <div id="subscribe" class="invisible topstats">
                    <?php
                    print '<i title="'.get_int_text('button_subscribe').
                        '" class="icon-rss npicon clickicon lettuce"></i>';
                    ?>
                    <input type="hidden" id="nppodiput" value="" />
                </div>
                <div id="stars" class="invisible topstats">
                    <i id="ratingimage" class="icon-0-stars rating-icon-big"></i>
                    <input type="hidden" value="-1" />
                </div>
                <div id="lastfm" class="invisible topstats">
                    <?php
                    print '<i title="'.get_int_text('button_love').
                        '" class="icon-heart npicon clickicon lettuce" id="love"></i>';
                    ?>
                </div>
                <div id="playcount" class="topstats"></div>
                <div id="dbtags" class="invisible topstats">
                </div>
            </div>
        </div>
    </div>
</div>

<div id="headerbar" class="noborder fullwidth">

<div id="sourcescontrols" class="column noborder tleft containerbox headercontainer">
<div class="expand topbox">
<?php
print '<i title="'.get_int_text('button_local_music').'" class="icon-music tooltip topimg choose_albumlist"></i>';
print '<i title="'.get_int_text('button_searchmusic').'" class="icon-search topimg tooltip choose_searcher"></i>';
print '<i title="'.get_int_text('button_file_browser').'" class="icon-folder-open-empty tooltip topimg choose_filelist"></i>';
print '<i title="'.get_int_text('button_internet_radio').'" class="icon-radio-tower tooltip topimg choose_radiolist"></i>';
print '<i title="'.get_int_text('label_podcasts').'" class="icon-podcast-circled tooltip topimg choose_podcastslist"></i>';
print '<i title="'.get_int_text('button_loadplaylist').'" class="icon-doc-text tooltip topimg choose_playlistslist"></i>';
print '<i title="'.get_int_text('label_pluginplaylists').'" class="icon-wifi tooltip topimg choose_pluginplaylistslist"></i>';
?>
</div>
</div>

<div id="infopanecontrols" class="cmiddle noborder tleft containerbox headercontainer">
<?php
 print '<i title="'.get_int_text('button_togglesources').'" class="icon-angle-double-left tooltip topimg fixed topbox" id="expandleft"></i>';
 ?>

<div id="chooserbuttons" class="noborder expand center topbox containerbox">
<?php
print '<div class="topdrop fixed"><i class="icon-menu topimg tooltip" title="'.get_int_text('button_plugins').'"></i>';
?>
<div class="topdropmenu dropshadow leftmenu normalmenu">
    <div id="specialplugins" class="clearfix"></div>
</div>
</div>
<?php
print '<div class="topdrop fixed"><i class="icon-versions topimg tooltip" title="'.get_int_text('button_history').'"></i>';
?>
<div class="topdropmenu dropshadow leftmenu widemenu" id="hpscr">
    <div id="historypanel" class="clearfix"></div>
</div>
</div>

<?php
print '<i title="'.get_int_text('button_back').'" id="backbutton" class="icon-left-circled topimg tooltip button-disabled fixed"></i>';
print '<i title="'.get_int_text('button_forward').'" id="forwardbutton" class="icon-right-circled tooltip topimg button-disabled fixed"></i>';
?>
</div>

<?php
print '<i class="icon-angle-double-right tooltip topimg fixed topbox" title="'.get_int_text('button_toggleplaylist').'" id="expandright"></i>';
?>
</div>

<div id="playlistcontrols" class="column noborder tleft containerbox headercontainer">
<div class="expand"></div><div class="fixed topbox" id="righthandtop">

<?php
print '<div class="topdrop"><i title="'.get_int_text('button_albumart').'" class="icon-cd tooltip topimg open_albumart"></i></div>';
print '<div class="topdrop"><i class="icon-cog-alt topimg tooltip choose_prefs" title="'.get_int_text('button_prefs').'"></i>';
?>
<div class="topdropmenu dropshadow rightmenu widemenu stayopen" id="configpanel">
<?php
include ("includes/prefspanel.php");
?>
</div>
</div>

<?php
print '<div class="topdrop"><i class="icon-floppy topimg tooltip" title="'.get_int_text('button_saveplaylist').'"></i>';
?>
<div class="topdropmenu dropshadow rightmenu widemenu stayopen" id="plsaver">
<?php
print '<div class="configtitle textcentre"><b>'.get_int_text('button_saveplaylist').'</b></div>';
print '<div class="containerbox"><div class="expand">
<input class="enter" id="playlistname" type="text" size="200"/></div>';
print '<button class="fixed">'.get_int_text('button_save').'</button></div>';
?>
</div>
</div>

</div>
</div>
</div>

<div id="bottompage" class="clearfix">

<div id="sources" class="column noborder tleft">

    <div id="albumlist" class="invisible noborder">
<?php
    print '<div class="menuitem containerbox" style="padding-left:8px">';
    print '<div class="fixed" style="padding-right:4px"><i onclick="toggleCollectionButtons()" title="'.get_int_text('button_collectioncontrols').'" class="icon-menu playlisticon clickicon lettuce"></i></div>';
    print '<div class="configtitle textcentre expand"><b>'.get_int_text('button_local_music').'</b></div>';
    print '</div>';
?>
    <div id="collectionbuttons" class="invisible">
<?php
    print '<div class="pref styledinputs">';
    print '<input type="radio" class="topcheck savulon" name="sortcollectionby" value="artist" id="sortbyartist">
    <label for="sortbyartist">'.ucfirst(get_int_text('label_artists')).'</label><br/>
    <input type="radio" class="topcheck savulon" name="sortcollectionby" value="album" id="sortbyalbum">
    <label for="sortbyalbum">'.ucfirst(get_int_text('label_albums')).'</label><br/>
    <input type="radio" class="topcheck savulon" name="sortcollectionby" value="albumbyartist" id="sortbyalbumbyartist">
    <label for="sortbyalbumbyartist">'.ucfirst(get_int_text('label_albumsbyartist')).'</label>
    <div class="pref">
    <input class="autoset toggle" type="checkbox" id="showartistbanners">
    <label for="showartistbanners">'.get_int_text('config_showartistbanners').'</label>
    </div>
    </div>
    <div class="pref styledinputs">
    <input class="autoset toggle" type="checkbox" id="sortbydate">
    <label for="sortbydate">'.get_int_text('config_sortbydate').'</label>
    <div class="pref">
    <input class="autoset toggle" type="checkbox" id="notvabydate">
    <label for="notvabydate">'.get_int_text('config_notvabydate').'</label>
    </div>
    </div>
    <div class="pref textcentre">
    <button name="donkeykong" onclick="checkCollection(true, false)">'.get_int_text('config_updatenow').'</button>
    </div>';
?>
    
    </div>
    <div id="collection" class="noborder selecotron"></div>
    </div>

    <div id="searcher" class="invisible noborder">
<?php
include("player/".$prefs['player_backend']."/search.php");
?>
    <div id="searchresultholder" class="nosborder selecotron"></div>
    </div>

    <div id="filelist" class="invisible">
    <div id="filecollection" class="noborder selecotron"></div>
    </div>

    <div id="radiolist" class="invisible">
<?php
    print '<div class="configtitle textcentre"><b>'.get_int_text('button_internet_radio').'</b></div>';
?>
<?php
$sp = glob("streamplugins/*.php");
foreach($sp as $p) {
    include($p);
}
?>
    </div>
    <div id="podcastslist" class="invisible selecotron">
<?php
include("includes/podcasts.php");
?>
    </div>
    <div id="playlistslist" class="invisible">
        <div id="storedplaylists" class="noborder selecotron"></div>
    </div>

    <div id="pluginplaylistslist" class="invisible padleft noselection">
<?php
print '<div class="configtitle textcentre"><b>'.get_int_text('label_pluginplaylists').'</b></div>';

if ($prefs['player_backend'] == "mopidy") {
    print '<div class="textcentre textunderline"><b>Music From Your Collection</b></div>';
}
?>
<div class="fullwidth" id="pluginplaylists"></div>


<?php
if ($prefs['player_backend'] == "mopidy") {
    print '<div class="textcentre textunderline"><b>Music From Spotify</b></div>';
}
?>
<div class="fullwidth" id="pluginplaylists_spotify"></div>

<?php
if ($prefs['player_backend'] == "mopidy") {
    print '<div class="textcentre textunderline"><b>Music From Everywhere</b></div>';
    print '<div id="radiodomains" class="pref"><b>Play From These Sources:</b></div>';
}
?>
<div class="fullwidth" id="pluginplaylists_everywhere"></div>

<div class="clearfix containerbox vertical" id="pluginplaylists_crazy"></div>
</div>
</div>

<div id="infopane" class="cmiddle noborder infowiki tleft">
    <div id="artistchooser" class="infotext noselection invisible"></div>
<?php
print '<div id="artistinformation" class="infotext noselection"><h2 align="center">'.get_int_text('label_emptyinfo').'</h2></div>';
?>
<div id="albuminformation" class="infotext noselection"></div>
<div id="trackinformation" class="infotext"></div>
</div>

<div id="playlist" class="column noborder tright">
<?php
include("skins/playlist.php");
?>
</div>
</div>
<div id="tagadder" class="dropmenu dropshadow mobmenu">
    <div class="configtitle textcentre moveable" style="padding-top:4px"><b>
<?php
print get_int_text("lastfm_addtags").'</b><i class="icon-cancel-circled clickicon playlisticonr tright" onclick="tagAdder.close()"></i></div><div>'.get_int_text("lastfm_addtagslabel");
?>
    </div>
    <div class="containerbox padright dropdown-container tagaddbox"></div>
</div>
