<?php

// There may appear to be a lot of unnecessary divs wrapping around things here
// but it makes it work in Safari. DO NOT CHANGE IT!

print '<div class="textcentre configtitle"><b>'.get_int_text('settings_appearance').'</b></div>';

// Skin

print '<div class="pref containerbox dropdown-container"><div class="divlabel">'.
    get_int_text('config_skin').
    '</div><div class="selectholder"><select id="skinselector" class="saveomatic">';
$skins = glob("skins/*");
foreach($skins as $sk) {
    if (is_dir($sk)) {
        print '<option value="'.basename($sk).'">'.ucfirst(basename($sk)).'</option>';
    }
}
print '</select></div></div>';

// Theme
print '<div class="pref containerbox dropdown-container"><div class="divlabel">'.
    get_int_text('config_theme').
    '</div><div class="selectholder"><select id="themeselector" class="saveomatic">';
$themes = glob("themes/*.css");
foreach($themes as $theme) {
    print '<option value="'.basename($theme).'">'.preg_replace('/\.css$/', "", basename($theme)).'</option>';
}
print '</select></div></div>';

// Custom Background
print '<div id="custombackground" class="pref">';

print '<div class="containerbox dropdown-container">
<i class="icon-menu smallicon clickicon fixed" onclick="prefs.openBgImageBox()"></i>
<div class="expand"><b>'.get_int_text('config_background').'</b></div>
</div>';

print '<div id="custombgdropper" class="invisible">';

print '<form id="backimageform" action="backimage.php" method="post" enctype="multipart/form-data">
<input type="hidden" name="currbackground" value="" />
<input type="hidden" name="browser_id" value="" />
<div class="filebutton textcentre" style="width:auto">
<input type="file" name="imagefile[]" id="imagefile" class="inputfile" multiple="multiple" />
<label for="imagefile">'.get_int_text('label_choosefiles').'</label>
</div>
<input type="button" class="invisible" onclick="prefs.changeBackgroundImage()" value="'.get_int_text('albumart_uploadbutton').'" />
<div class="styledinputs">
<input type="checkbox" id="thisbrowseronly" name="thisbrowseronly" /><label for="thisbrowseronly">For this browser only</label>
</div>
</form>';

print '<div class="containerbox">';

print '<div id="backimageposition" class="tiny styledinputs invisible divlabel">
<div class="spacer"></div>
<div class="textcentre"><b>Display Options</b></div>
<div class="spacer"></div>
<div><input type="radio" id="attach_centre" name="backgroundposition" value="center center" /><label for="attach_centre">'.get_int_text('label_centre').'</label></div>
<div><input type="radio" id="attach_topleft" name="backgroundposition" value="top left" /><label for="attach_topleft">'.get_int_text('label_topleft').'</label></div>
<div><input type="radio" id="attach_topright" name="backgroundposition" value="top right" /><label for="attach_topright">'.get_int_text('label_topright').'</label></div>
<div><input type="radio" id="attach_bottomleft" name="backgroundposition" value="bottom left" /><label for="attach_bottomleft">'.get_int_text('label_bottomleft').'</label></div>
<div><input type="radio" id="attach_bottomright" name="backgroundposition" value="bottom right" /><label for="attach_bottomright">'.get_int_text('label_bottomright').'</label></div>
<div id="cusbgcontrols"></div>
</div>

<div class="selectholder-noselect">
<div id="cusbgname" class="tiny styledinputs"></div>
</div>

</div>
</div>
</div>';

// Icon Theme
print '<div class="pref containerbox dropdown-container"><div class="divlabel">'.
    get_int_text('config_icontheme').
    '</div><div class="selectholder"><select id="iconthemeselector" class="saveomatic">';
$themes = glob("iconsets/*");
foreach($themes as $theme) {
    if (is_dir($theme)) {
        print '<option value="'.basename($theme).'">'.basename($theme).'</option>';
    }
}
print '</select></div></div>';

// Font
print '<div class="pref containerbox dropdown-container"><div class="divlabel">'.
    get_int_text('config_fontname').
    '</div><div class="selectholder"><select id="fontfamilyselector" class="saveomatic">';
$themes = glob("fonts/*.css");
foreach($themes as $theme) {
    print '<option value="'.preg_replace("#fonts/#", "", $theme).'">'.
        preg_replace('/fonts\/(.*?)\.css$/', "$1", $theme).'</option>';
}
print '</select></div></div>';

//Font Size
print '<div class="pref containerbox dropdown-container"><div class="divlabel">'.
    get_int_text('config_fontsize').
    '</div><div class="selectholder"><select id="fontsizeselector" class="saveomatic">';
$themes = glob("sizes/*.css");
foreach($themes as $theme) {
    print '<option value="'.preg_replace("#sizes/#", "", $theme).'">'.
        preg_replace('/sizes\/\d+-(.*?)\.css$/', "$1", $theme).'</option>';
}
print '</select></div></div>';

// Album Cover Size
print '<div class="pref containerbox dropdown-container"><div class="divlabel">'.
    get_int_text('config_coversize').
    '</div><div class="selectholder"><select id="coversizeselector" class="saveomatic">';
$themes = glob("coversizes/*.css");
foreach($themes as $theme) {
    print '<option value="'.preg_replace("#coversizes/#", "", $theme).'">'.
        preg_replace('/coversizes\/\d+-(.*?)\.css$/', "$1", $theme).'</option>';
}
print '</select></div></div>';

// Players
print '<div class="textcentre configtitle"><b>'.get_int_text('config_players').'</b></div>';
print '<div class="fullwidth">';
print '<div class="clearfix">';
print '<div class="pref styledinputs tleft" name="playerdefs">';
print '</div>';
print '<div class="pref tright"><button onclick="player.defs.edit()">'.get_int_text('button_edit_players').'</button></div>';
print '</div>';
print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="player_in_titlebar" />
<label for="player_in_titlebar">'.get_int_text('config_playerintitlebar').'</label>
</div>';
print '</div>';

// Sources Panel Hiding
print '<div class="textcentre configtitle"><b>'.get_int_text('settings_panels').'</b></div>';
print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="hide_albumlist" />
<label for="hide_albumlist">'.get_int_text('config_hidealbumlist').'</label>
</div>';
print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="hide_filelist" />
<label for="hide_filelist">'.get_int_text('config_hidefileslist').'</label>
</div>';
print '<div class="pref styledinputs">';
print '<input class="autoset toggle" type="checkbox" id="hide_radiolist" />
<label for="hide_radiolist">'.get_int_text('config_hideradio').'</label>
</div>';
print '<div class="pref styledinputs">';
print '<input class="autoset toggle" type="checkbox" id="hide_podcastslist" />
<label for="hide_podcastslist">'.get_int_text('config_hidepodcasts').'</label>
</div>';
print '<div class="pref styledinputs">';
print '<input class="autoset toggle" type="checkbox" id="hide_playlistslist" />
<label for="hide_playlistslist">'.get_int_text('config_hideplaylistslist').'</label>
</div>';
print '<div class="pref styledinputs">';
print '<input class="autoset toggle" type="checkbox" id="hide_pluginplaylistslist" />
<label for="hide_pluginplaylistslist">'.get_int_text('config_hidepluginplaylistslist').'</label>
</div>';
if ($skin == "desktop") {
print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="hidebrowser" />
<label for="hidebrowser">'.get_int_text('config_hidebrowser').'</label>
</div>';
}

// Interface
print '<div class="textcentre configtitle"><b>'.get_int_text('settings_interface').'</b></div>';
print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="scrolltocurrent" />
<label for="scrolltocurrent">'.get_int_text('config_autoscroll').'</label>
</div>';
if ($use_plugins) {
    print '<div class="pref styledinputs">
    <input class="autoset toggle" type="checkbox" id="auto_discovembobulate" />
    <label for="auto_discovembobulate">'.get_int_text('config_discovembobulate').'</label>
    </div>';
}
print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="displaycomposer" />
<label for="displaycomposer">'.get_int_text('config_displaycomposer').'</label>
</div>';
print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="use_albumart_in_playlist" />
<label for="use_albumart_in_playlist">'.get_int_text('config_albumartinplaylist').'</label>
</div>';

// Click Policy
print '<div class="pref styledinputs">';
print '<input type="radio" class="topcheck savulon" name="clickmode" value="double" id="clickd" />
<label for="clickd">'.get_int_text('config_doubleclick').'</label><br/>
<input type="radio" class="topcheck savulon" name="clickmode" value="single" id="clicks" />
<label for="clicks">'.get_int_text('config_singleclick').'</label><br>
</div>';
print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="cdplayermode" />
<label for="cdplayermode">'.get_int_text('config_cdplayermode').'</label>
</div>';
if ($skin != "phone") {
print '<div class="pref styledinputs">'.get_int_text('config_wheelspeed').
    '<input class="saveotron" id="wheelscrollspeed" style="width:4em;margin-left:1em" type="text" size="4" />
    </div>';
print '<div class="pref textcentre"><button onclick="shortcuts.edit()">'.
    get_int_text('config_editshortcuts').'</button></div>'."\n";
} else {
    print '<div class="pref styledinputs">
    <input class="autoset toggle" type="checkbox" id="playlistswipe" />
    <label for="playlistswipe">'.get_int_text('config_playlistswipe').'</label>
    </div>';
}
if ($prefs['player_backend'] == "mpd") {
print '<div class="pref containerbox dropdown-container">
    <div class="fixed" style="margin-right:2em">'.
    get_int_text('config_crossfade').
    '</div>
    <input class="saveotron fixed" style="width:4em" id="crossfade_duration" type="text" size="3" />
    </div>';
}

// Smart Radio
print '<div class="textcentre configtitle"><b>'.get_int_text('label_pluginplaylists').'</b></div>';
print '<div class="pref styledinputs">'.get_int_text('config_smart_chunksize').
    '<input class="saveotron" id="smartradio_chunksize" style="width:4em;margin-left:1em" type="text" size="4" />
    </div>';

// Biography and Language
print '<div class="textcentre ucfirst configtitle"><b>'.get_int_text('settings_language').'</b></div>';

print '<div class="pref containerbox dropdown-container"><div class="divlabel">'.
get_int_text('settings_interface').
'</div><div class="selectholder"><select id="langselector" onchange="prefs.changelanguage()">';
$langs = glob("international/*.php");
foreach($langs as $lang) {
    if (basename($lang) != "en.php" && basename($lang) != $interface_language.".php") {
        include($lang);
    }
}
foreach($langname as $key => $value) {
    print '<option value="'.$key.'">'.$value.'</option>';
}
print '</select></div></div>';

print '<div class="pref styledinputs">
<b>'.get_int_text("config_lastfmlang").'</b><br/>
<input type="radio" class="topcheck savulon" name="lastfmlang" value="default" id="langdefault" />
<label for="langdefault">'.get_int_text('config_lastfmdefault').'</label><br/>
<input type="radio" class="topcheck savulon" name="lastfmlang" value="interface" id="langint" />
<label for="langint">'.get_int_text('config_lastfminterface').'</label><br/>
<input type="radio" class="topcheck savulon" name="lastfmlang" value="browser" id="langbr" />
<label for="langbr">'.get_int_text('config_lastfmbrowser').'</label><br/>
<input type="radio" class="topcheck savulon" name="lastfmlang" value="user" id="languser" />
<label for="languser">'.get_int_text('config_lastfmlanguser').
'</label><input class="saveotron" id="user_lang" style="width:4em;margin-left:1em" type="text" size="4" /><br/>
<div class="tiny">'.get_int_text('config_langinfo').'</div>
</div>';

print '<div class="pref containerbox dropdown-container"><div class="divlabel">'.
get_int_text('config_country').
'</div><div class="selectholder"><select class="saveomatic" id="lastfm_country_codeselector">';
$x = simplexml_load_file('iso3166.xml');
foreach($x->CountryEntry as $i => $c) {
    print '<option value="'.$c->CountryCode.'">'.
        mb_convert_case($c->CountryName, MB_CASE_TITLE, "UTF-8")."</option>\n";
}
print '</select></div></div>';

// Album Art
print '<div class="textcentre configtitle"><b>'.get_int_text('albumart_title').'</b></div>';
print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="downloadart" />
<label for="downloadart">'.get_int_text('config_autocovers').'</label>
</div>
<div class="pref">
<div class="tiny">'.get_int_text('config_musicfolders').'</div>
<input class="saveotron prefinput" id="music_directory_albumart" type="text" size="40" />
</div>';
print '<div class="pref"><div class="tiny">If you want to use Google Images to get Album Art you need to sign up for an API Key. <a href="https://fatg3erman.github.io/RompR/Album-Art-Manager" target="_blank">'.get_int_text('config_read_the_docs').'</a></div></div>';

print '<div class="textcentre configtitle">
<i class="icon-podcast-circled medicon"></i><b>'.get_int_text('label_podcasts').'</b></div>';

print '<div class="pref"><b>'.get_int_text('config_podcast_defaults').'</b></div>';

print '<div class="pref containerbox dropdown-container"><div class="divlabel">'.
    get_int_text("podcast_display").'</div>';
print '<div class="selectholder">';
print '<select id="default_podcast_display_modeselector" class="saveomatic">';
$options =  '<option value="'.DISPLAYMODE_ALL.'">'.get_int_text("podcast_display_all").'</option>'.
            '<option value="'.DISPLAYMODE_NEW.'">'.get_int_text("podcast_display_onlynew").'</option>'.
            '<option value="'.DISPLAYMODE_UNLISTENED.'">'.get_int_text("podcast_display_unlistened").'</option>'.
            '<option value="'.DISPLAYMODE_DOWNLOADEDNEW.'">'.get_int_text("podcast_display_downloadnew").'</option>'.
            '<option value="'.DISPLAYMODE_DOWNLOADED.'">'.get_int_text("podcast_display_downloaded").'</option>';
print $options;
print '</select>';
print '</div></div>';

print '<div class="pref containerbox dropdown-container"><div class="divlabel">'.
    get_int_text("podcast_refresh").'</div>';
print '<div class="selectholder">';
print '<select id="default_podcast_refresh_modeselector" class="saveomatic">';
$options =  '<option value="'.REFRESHOPTION_NEVER.'">'.get_int_text("podcast_refresh_never").'</option>'.
            '<option value="'.REFRESHOPTION_HOURLY.'">'.get_int_text("podcast_refresh_hourly").'</option>'.
            '<option value="'.REFRESHOPTION_DAILY.'">'.get_int_text("podcast_refresh_daily").'</option>'.
            '<option value="'.REFRESHOPTION_WEEKLY.'">'.get_int_text("podcast_refresh_weekly").'</option>'.
            '<option value="'.REFRESHOPTION_MONTHLY.'">'.get_int_text("podcast_refresh_monthly").'</option>';
print $options;
print '</select>';
print '</div></div>';

print '<div class="pref containerbox dropdown-container"><div class="divlabel">'.
    get_int_text("podcast_sortmode").'</div>';
print '<div class="selectholder">';
print '<select id="default_podcast_sort_modeselector" class="saveomatic">';
$options =  '<option value="'.SORTMODE_NEWESTFIRST.'">'.get_int_text("podcast_newestfirst").'</option>'.
            '<option value="'.SORTMODE_OLDESTFIRST.'">'.get_int_text("podcast_oldestfirst").'</option>';
print $options;
print '</select>';
print '</div></div>';

print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="podcast_mark_new_as_unlistened" />
<label for="podcast_mark_new_as_unlistened">'.get_int_text('config_marknewasunlistened').'</label>
</div>';

// Last.FM
print '<div class="textcentre configtitle">
<i class="icon-lastfm-1 medicon"></i><b>'.get_int_text('label_lastfm').'</b>
</div>';

print '<div class="pref">'.get_int_text('config_lastfmusername').'<br/><div class="containerbox"><div class="expand">'.
    '<input class="enter" name="lfmuser" type="text" size="30" value="'.$prefs['lastfm_user'].'"/>'.
    '</div><button id="lastfmloginbutton" class="fixed">'.get_int_text('config_loginbutton').
    '</button></div>';
print '</div>';

print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="lastfm_autocorrect" />
<label for="lastfm_autocorrect">'.get_int_text('config_autocorrect').'</label>
</div>';

print '<div class="pref styledinputs lastfmlogin-required">
<input class="autoset toggle" type="checkbox" id="sync_lastfm_playcounts" />
<label for="sync_lastfm_playcounts">'.get_int_text('config_lastfm_playcounts').'</label>
</div>';

print '<div class="pref styledinputs lastfmlogin-required">
<input class="autoset toggle" type="checkbox" id="sync_lastfm_at_start" />
<label for="sync_lastfm_at_start">'.get_int_text('config_sync_lastfm_playcounts').'</label>
</div>';

print '<div class="pref styledinputs lastfmlogin-required">
<input class="autoset toggle" type="checkbox" id="lastfm_scrobbling" />
<label for="lastfm_scrobbling">'.get_int_text('config_scrobbling').'</label>
</div>
<div class="pref lastfmlogin-required">'.get_int_text('config_scrobblepercent').'<br/>
<div id="scrobwrangler"></div>
</div>
<div class="pref lastfmlogin-required">'.get_int_text('config_tagloved').'
<input class="prefinput saveotron" id="autotagname" type="text" size="40" />
</div>';

// Tags and Ratings
print '<div class="pref styledinputs lastfmlogin-required">
<input class="autoset toggle" type="checkbox" id="synctags" />
<label for="synctags">'.get_int_text('config_synctags').'</label>';
?>
</div>
<div class="pref containerbox dropdown-container lastfmlogin-required">
<?php
print '<div class="divlabel styledinputs"><input class="autoset toggle" type="checkbox" id="synclove" />
<label for="synclove">'.get_int_text('config_loveis').'</label></div>';
?>
<div class="selectholder"><select id="synclovevalueselector" class="saveomatic">
<?php
print '<option value="5">5 '.get_int_text('stars').'</option>
<option value="4">4 '.get_int_text('stars').'</option>
<option value="3">3 '.get_int_text('stars').'</option>
<option value="2">2 '.get_int_text('stars').'</option>
<option value="1">1 '.get_int_text('star').'</option>';
print '</select>
</div></div>';

// Collection Options
print '<div class="textcentre ucfirst configtitle"><i class="icon-music medicon"></i><b>'.get_int_text('button_local_music').'</b></div>';
print '<div class="tiny textcentre" style="margin-bottom:1em">These options affect everyone who uses this installation of RompЯ</div>';

// Album Sorting
print '<div class="pref"><b>'.get_int_text('config_artistfirst').'
<input class="saveotron prefinput arraypref" id="artistsatstart" type="text" size="256" />
</b></div>';
print '<div class="pref"><b>'.get_int_text('config_nosortprefixes').'
<input class="saveotron prefinput arraypref" id="nosortprefixes" type="text" size="128" />
</b></div>';

if ($prefs['mopidy_slave'] == false) {

    if ($collection_type == $prefs['player_backend']) {
        print '<div class="pref styledinputs">
            <div class="clearfix">
                <div class="tleft">
                    <input class="autoset toggle" type="checkbox" id="updateeverytime" /><label for="updateeverytime">'.get_int_text('config_updateonstart').'</label>
                </div>';
        print '<button class="tright" name="donkeykong">'.get_int_text('config_updatenow').'</button>';
        if ($prefs['player_backend'] == "mpd") {
            print '<button class="tright" name="dinkeyking">'.get_int_text('config_rescan').'</button>';
        }
        print '</div></div>';
    }

    if ($collection_type == "mopidy" && $prefs['player_backend'] == 'mopidy') {
        print '<div class="pref" id="mopidycollectionoptions">'.
        '<b>'.get_int_text('config_collectionfolders').'</b></div>';
        print '<div class="pref">'.get_int_text('config_beetsserver').'
        <input class="prefinput saveotron" id="beets_server_location" type="text" size="40" />
        </div>';

        print '<div class="pref styledinputs">
        <input class="autoset toggle" type="checkbox" id="preferlocalfiles" />
        <label for="preferlocalfiles">'.get_int_text('config_preferlocal').'</label></div>';
    }

    if ($collection_type == $prefs['player_backend']) {
        print '<div class="pref styledinputs">
        <input class="autoset toggle" type="checkbox" id="sortbycomposer" />
        <label for="sortbycomposer">'.get_int_text('config_sortbycomposer').'</label>
        </div>';
        print '<div class="pref indent styledinputs">
        <input class="autoset toggle" type="checkbox" id="composergenre" />
        <label for="composergenre">'.get_int_text('config_composergenre').'</label>
        </div>';
        print '<div class="pref indent">
        <input class="saveotron prefinput arraypref" id="composergenrename" type="text" size="40" />
        </div>';
    }
}
?>
