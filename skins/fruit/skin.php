<body class="desktop">
<div id="pset" class="invisible"></div>
<div id="notifications"></div>

<div class="fullwidth containerbox" id="thetopbit">
    <div class="fixed">
        <div id="albumcover" class="fixed">
            <img id="albumpicture" />
        </div>
    </div>
    <div id="groonburger" class="fixed containerbox vertical controlbutton-left">
<?php
        print '<i title="'.get_int_text('button_previous').'" class="prev-button fixed icon-fast-backward clickicon controlbutton-small tooltip"></i>';
        print '<i title="'.get_int_text('button_play').'" class="play-button fixed icon-play-circled clickicon controlbutton-main tooltip"></i>';
        print '<i title="'.get_int_text('button_stop').'" class="stop-button fixed icon-stop-1 clickicon controlbutton-small tooltip"></i>';
        print '<i title="'.get_int_text('button_stopafter').'" class="stopafter-button fixed icon-to-end-1 clickicon controlbutton-small tooltip"></i>';
        print '<i title="'.get_int_text('button_next').'" class="next-button fixed icon-fast-forward clickicon controlbutton-small tooltip"></i>';
?>
    </div>
    <!-- <div class="expand"> -->
<div id="infobar" class="fixed containerbox vertical">
    <div id="patrickmoore" class="infobarlayout noselection fixed containerbox">
        <div id="firefoxisshitwrapper" class="expand">
            <div id="nowplaying">
                <div id="nptext"></div>
            </div>
            <div id="amontobin" class="clearfix">
                <div id="subscribe" class="invisible topstats">
                    <?php
                    print '<i title="'.get_int_text('button_subscribe').
                        '" class="icon-rss npicon clickicon tooltip"></i>';
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
                        '" class="icon-heart npicon clickicon tooltip" id="love"></i>';
                    ?>
                </div>
                <div id="playcount" class="topstats"></div>
                <div id="dbtags" class="invisible topstats">
                </div>
            </div>
        </div>
        <div id="gronky" class="containerbox vertical">
            <div class="containerbox vertical fixed" id="righthandtop"></div>
<?php
            print '<div class="fixed topdrop"><i class="icon-floppy smallpluginicon clickicon"></i>';
?>
                <div class="topdropmenu dropshadow rightmenu widemenu stayopen" id="plsaver">
<?php
                    print '<div class="configtitle textcentre"><b>'.get_int_text('button_saveplaylist').'</b></div>';
                    print '<div class="containerbox">
                        <div class="expand">
                            <input class="enter clearbox" id="playlistname" type="text" size="200"/>
                        </div>';
                        print '<button class="fixed">'.get_int_text('button_save').'</button>
                    </div>';
?>
                </div>
            </div>
<?php
            print '<div class="fixed topdrop"><i title="'.get_int_text('button_albumart').'" class="icon-cd tooltip smallpluginicon clickicon open_albumart"></i></div>';
            print '<div class="fixed topdrop"><i class="icon-cog-alt smallpluginicon clickicon tooltip choose_prefs" title="'.get_int_text('button_prefs').'"></i>';
?>
                <div class="topdropmenu dropshadow rightmenu widemenu stayopen" id="configpanel">
<?php
include ("includes/prefspanel.php");
?>
                </div>
            </div>

            <div class="fixed topdrop"><i class="icon-menu smallpluginicon clickicon"></i>
                <div class="topdropmenu dropshadow rightmenu widemenu stayopen" id="phacker">
                <div class="configtitle textcentre"><b>Play Queue</b></div>
                    <?php
                    include("skins/playlist.php");
                    ?>
                </div>
            </div>

        </div>
    <div id="volumebox" class="fixed">
        <div class="infobarlayout nopadright containerbox vertical">
<?php
            print '<div title="'.get_int_text('button_volume').
                '" id="volumecontrol" class="tooltip expand containerbox vertical"><div id="volume" class="expand"></div></div>';
            include('player/mpd/outputs.php');
            if (count($outputdata) == 1) {
                // There's only one output so we'll treat it like a Mute button
                print '<div class="tooltip fixed" title="'.$outputdata[0]['outputname'].'" style="height:18px">';
                $f = ($outputdata[0]['outputname'] == "Mute") ? 0 : 1;
                $c = ($outputdata[0]['outputenabled'] == $f) ? 'icon-output' : 'icon-output-mute';
                print '<i id="mutebutton" onclick="player.controller.doMute()" class="'.$c.' outhack clickicon"></i>';
                print '</div>';
            } else {
                print '<div class="tooltip fixed" title="'.get_int_text('config_audiooutputs').'" style="height:18px">';
                print '<i id="mutebutton" onclick="layoutProcessor.toggleAudioOutpts()" class="icon-sliders outhack clickicon"></i>';
                print '</div>';
            }

?>
        </div>
    </div>

<?php
    if (count($outputdata) > 1) {
        print '<div id="outputbox" class="fixed" style="display:none">';
        print '<div class="infobarlayout nopadright">';
        print '<div style="padding:4px">';
        printOutputCheckboxes();
        print '</div>';
        print '</div>';
        print '</div>';
    }

?>
    </div>
    <div id="theotherthing" class="fixed infobarnoheight containerbox">
            <div id="playposss" class="fixed timebox tboxl"></div>
            <div class="expand"><div id="progress"></div></div>
            <div id="tracktimess" class="fixed timebox tboxr clickicon"></div>
    </div>
</div>

<!-- </div> -->
</div>
<div id="headerbar" class="noborder fullwidth">

<div id="sourcescontrols" class="noborder tleft containerbox headercontainer topbox">
<?php
print '<i title="'.get_int_text('button_local_music').'" class="icon-music tooltip topimg choose_albumlist expand"></i>';
print '<i title="'.get_int_text('button_searchmusic').'" class="icon-search topimg tooltip choose_searcher expand"></i>';
print '<i title="'.get_int_text('button_file_browser').'" class="icon-folder-open-empty tooltip topimg choose_filelist expand"></i>';
print '<i title="'.get_int_text('button_internet_radio').'" class="icon-radio-tower tooltip topimg choose_radiolist expand"></i>';
print '<i title="'.get_int_text('label_podcasts').'" class="icon-podcast-circled tooltip topimg choose_podcastslist expand"></i>';
print '<i title="'.get_int_text('button_loadplaylist').'" class="icon-doc-text tooltip topimg choose_playlistslist expand"></i>';
print '<i title="'.get_int_text('label_pluginplaylists').'" class="icon-wifi tooltip topimg choose_pluginplaylistslist expand"></i>';
?>
<div class="expand"></div>
</div>

<div id="infopanecontrols" class="noborder tleft containerbox headercontainer topbox">
<?php
 print '<i title="'.get_int_text('button_togglesources').'" class="icon-angle-double-left tooltip topimg expandslightly background-left" id="expandleft"></i>';
 ?>

<div id="chooserbuttons" class="noborder expandalot center containerbox headercontainer">
<?php
print '<i class="icon-menu topimg tooltip topdrop expand" title="'.get_int_text('button_plugins').'">';
?>
<div class="topdropmenu dropshadow leftmenu normalmenu">
    <div id="specialplugins" class="clearfix"></div>
</div>
</i>
<?php
print '<i class="icon-versions topimg tooltip topdrop expand" title="'.get_int_text('button_history').'">';
?>
<div class="topdropmenu dropshadow leftmenu widemenu" id="hpscr">
    <div id="historypanel" class="clearfix"></div>
</div>
</i>

<?php
print '<i title="'.get_int_text('button_back').'" id="backbutton" class="icon-left-circled topimg tooltip button-disabled expand"></i>';
print '<i title="'.get_int_text('button_forward').'" id="forwardbutton" class="icon-right-circled tooltip topimg button-disabled expand"></i>';
?>
</div>

</div>

<div id="bottompage" class="clearfix">

<div id="sources" class="column noborder tleft">

    <div id="albumlist" class="invisible noborder">
<?php
    print '<div class="menuitem containerbox" style="padding-left:8px">';
    print '<div class="fixed" style="padding-right:4px"><i onclick="toggleCollectionButtons()" title="'.get_int_text('button_collectioncontrols').'" class="icon-menu playlisticon clickicon tooltip"></i></div>';
    print '<div class="configtitle textcentre expand"><b>'.get_int_text('button_local_music').'</b></div>';
    print '</div>';
    collectionButtons();
?>
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

<div class="clearfix containerbox vertical" id="pluginplaylists_crazy">
</div>
</div>
</div>

<div id="infopane" class="cmiddle noborder infowiki tleft">
    <div id="artistchooser" class="infotext noselection invisible"></div>
<?php
print '<div id="artistinformation" class="infotext noselection"><h2 align="center">'.
    get_int_text('label_emptyinfo').'</h2></div>';
?>
<div id="albuminformation" class="infotext noselection"></div>
<div id="trackinformation" class="infotext"></div>
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
