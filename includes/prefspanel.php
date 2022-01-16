<?php

// There may appear to be a lot of unnecessary divs wrapping around things here
// but it makes it work in Safari. DO NOT CHANGE IT!

print '<div class="configtitle"><i class="medicon"></i><div class="textcentre expand"><b>'.language::gettext('settings_appearance').'</b></div></div>';

// Skin

print '<div class="pref containerbox vertical-centre"><div class="divlabel">'.
	language::gettext('config_skin').
	'</div><div class="selectholder"><select id="skinselector" class="saveomatic">';
$skins = glob("skins/*");
foreach($skins as $sk) {
	if (is_dir($sk)) {
		print '<option value="'.basename($sk).'">'.ucfirst(basename($sk)).'</option>';
	}
}
print '</select></div></div>';

// Theme
print '<div class="pref containerbox vertical-centre"><div class="divlabel">'.
	language::gettext('config_theme').
	'</div><div class="selectholder"><select id="themeselector" class="saveomatic">';
$themes = glob("themes/*.css");
foreach($themes as $theme) {
	print '<option value="'.basename($theme).'">'.preg_replace('/\.css$/', "", basename($theme)).'</option>';
}
print '</select></div></div>';

// Icon Theme
print '<div class="pref containerbox vertical-centre"><div class="divlabel">'.
	language::gettext('config_icontheme').
	'</div><div class="selectholder"><select id="iconthemeselector" class="saveomatic">';
$themes = glob("iconsets/*");
foreach($themes as $theme) {
	if (is_dir($theme) && basename($theme) != 'iconfactory') {
		print '<option value="'.basename($theme).'">'.basename($theme).'</option>';
	}
}
print '</select></div></div>';

// Font
print '<div class="pref containerbox vertical-centre"><div class="divlabel">'.
	language::gettext('config_fontname').
	'</div><div class="selectholder"><select id="fontfamilyselector" class="saveomatic">';
$themes = glob("fonts/*.css");
foreach($themes as $theme) {
	print '<option value="'.preg_replace("#fonts/#", "", $theme).'">'.
		preg_replace('/fonts\/(.*?)\.css$/', "$1", $theme).'</option>';
}
print '</select></div></div>';

//Font Size
print '<div class="pref containerbox vertical-centre"><div class="divlabel">'.
	language::gettext('config_fontsize').
	'</div><div class="selectholder"><select id="fontsizeselector" class="saveomatic">';
$themes = glob("sizes/*.css");
foreach($themes as $theme) {
	print '<option value="'.preg_replace("#sizes/#", "", $theme).'">'.
		preg_replace('/sizes\/\d+-(.*?)\.css$/', "$1", $theme).'</option>';
}
print '</select></div></div>';

// Album Cover Size
print '<div class="pref containerbox vertical-centre"><div class="divlabel">'.
	language::gettext('config_coversize').
	'</div><div class="selectholder"><select id="coversizeselector" class="saveomatic">';
$themes = glob("coversizes/*.css");
foreach($themes as $theme) {
	print '<option value="'.preg_replace("#coversizes/#", "", $theme).'">'.
		preg_replace('/coversizes\/\d+-(.*?)\.css$/', "$1", $theme).'</option>';
}
print '</select></div></div>';

// Custom Background
print '<div id="custombackground">';

print '<div class="configtitle"><i class="medicon"></i><div class="textcentre expand open_magic_div"><b>'.language::gettext('config_background').'</b></div></div>';

print '<div id="cusbgoptions">';

print '<div class="pref styledinputs">
<div><input type="radio" id="attach_centre" name="backgroundposition" value="center center" /><label for="attach_centre">'.language::gettext('label_centre').'</label></div>
<div><input type="radio" id="attach_topleft" name="backgroundposition" value="top left" /><label for="attach_topleft">'.language::gettext('label_topleft').'</label></div>
<div><input type="radio" id="attach_topright" name="backgroundposition" value="top right" /><label for="attach_topright">'.language::gettext('label_topright').'</label></div>
<div><input type="radio" id="attach_bottomleft" name="backgroundposition" value="bottom left" /><label for="attach_bottomleft">'.language::gettext('label_bottomleft').'</label></div>
<div><input type="radio" id="attach_bottomright" name="backgroundposition" value="bottom right" /><label for="attach_bottomright">'.language::gettext('label_bottomright').'</label></div>
</div>';

print '<div class="pref containerbox vertical-centre"><div class="divlabel">'.
	language::gettext('label_changevery').
	'</div><div class="selectholder"><select id="changeeveryselector">';
foreach (BG_IMAGE_TIMEOUTS as $label => $value) {
	print '<option value="'.$value.'">'.$label.'</option>';
}
print '</select></div></div>';

print '<div class="pref styledinputs">
<input type="checkbox" id="cus_bg_random" />
<label for="cus_bg_random">'.language::gettext('label_random_order').'</label>
</div>';

print '</div>';

print '<div class="clearfix">';
print '<div class="pref tright"><button onclick="prefs.manage_bg_images()">'.language::gettext('manage_bgs').'</button></div>';
print '</div>';

print '<div class="pref styledinputs invisible magic_div"><b>Browser ID</b>'.
	'<input class="saveotron" id="browser_id" type="text" />
	</div>';

print '</div>';

// Players
print '<div class="configtitle"><i class="medicon"></i><div class="textcentre expand"><b>'.language::gettext('config_players').'</b></div></div>';
print '<div class="fullwidth">';
print '<div class="clearfix">';
print '<div class="pref styledinputs tleft" name="playerdefs">';
print '</div>';
print '<div class="pref tright"><button onclick="player.defs.edit()">'.language::gettext('button_edit_players').'</button></div>';
print '</div>';
print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="player_in_titlebar" />
<label for="player_in_titlebar">'.language::gettext('config_playerintitlebar').'</label>';

print '<input class="autoset toggle" type="checkbox" id="consume_workaround" />
<label for="consume_workaround">'.language::gettext('config_consumeworkaround').'</label>
</div>';

print '</div>';

// Snapcast
print '<div class="configtitle"><i class="icon-snapcast medicon"></i><div class="textcentre expand"><b>'.language::gettext('config_snapcast').'</b></div></div>';
print '<div class="fullwidth">';
if (!$snapcast_in_volume) {
	print '<div class="pref" id="snapcastgroups">';
	print '</div>';
}
print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="hide_master_volume" />
<label for="hide_master_volume">'.language::gettext('config_hidemastervolume').'</label>
</div>';

print '<div class="pref styledinputs containerbox vertical-centre">';
print '<input class="saveotron expand" id="snapcast_server" type="text" placeholder="'.language::gettext('config_snapcast_server').'" />';
// print '<input class="saveotron fixed" id="snapcast_port" style="width:4em;margin-left:1em" type="text" size="4" placeholder="'.language::gettext('config_snapcast_port').'" />';
print '<input class="saveotron fixed" id="snapcast_http" style="width:4em;margin-left:1em" type="text" size="4" placeholder="'.language::gettext('config_snapcast_http').'" />';
print '</div>';
print '</div>';

// Sources Panel Hiding
print '<div class="configtitle"><i class="medicon"></i><div class="textcentre expand"><b>'.language::gettext('settings_panels').'</b></div></div>';
print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="hide_albumlist" />
<label for="hide_albumlist">'.language::gettext('config_hidealbumlist').'</label>
</div>';
print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="hide_searcher" />
<label for="hide_searcher">'.language::gettext('config_hidesearcher').'</label>
</div>';
print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="hide_filelist" />
<label for="hide_filelist">'.language::gettext('config_hidefileslist').'</label>
</div>';
print '<div class="pref styledinputs">';
print '<input class="autoset toggle" type="checkbox" id="hide_radiolist" />
<label for="hide_radiolist">'.language::gettext('config_hideradio').'</label>
</div>';
print '<div class="pref styledinputs">';
print '<input class="autoset toggle" type="checkbox" id="hide_podcastslist" />
<label for="hide_podcastslist">'.language::gettext('config_hidepodcasts').'</label>
</div>';
print '<div class="pref styledinputs">';
print '<input class="autoset toggle" type="checkbox" id="hide_audiobooklist" />
<label for="hide_audiobooklist">'.language::gettext('config_hideaudiobooks').'</label>
</div>';
print '<div class="pref styledinputs">';
print '<input class="autoset toggle" type="checkbox" id="hide_playlistslist" />
<label for="hide_playlistslist">'.language::gettext('config_hideplaylistslist').'</label>
</div>';
print '<div class="pref styledinputs">';
print '<input class="autoset toggle" type="checkbox" id="hide_pluginplaylistslist" />
<label for="hide_pluginplaylistslist">'.language::gettext('config_hidepluginplaylistslist').'</label>
</div>';
if ($skin == "desktop") {
print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="hidebrowser" />
<label for="hidebrowser">'.language::gettext('config_hidebrowser').'</label>
</div>';
}

// Interface
print '<div class="configtitle"><i class="medicon"></i><div class="textcentre expand"><b>'.language::gettext('settings_interface').'</b></div></div>';
print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="scrolltocurrent" />
<label for="scrolltocurrent">'.language::gettext('config_autoscroll').'</label>
</div>';
if ($use_plugins) {
	print '<div class="pref styledinputs">
	<input class="autoset toggle" type="checkbox" id="auto_discovembobulate" />
	<label for="auto_discovembobulate">'.language::gettext('config_discovembobulate').'</label>
	</div>';
}
print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="displaycomposer" />
<label for="displaycomposer">'.language::gettext('config_displaycomposer').'</label>
</div>';
print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="use_albumart_in_playlist" />
<label for="use_albumart_in_playlist">'.language::gettext('config_albumartinplaylist').'</label>
</div>';

// Click Policy
print '<div class="pref styledinputs">';
print '<input type="radio" class="topcheck savulon" name="clickmode" value="double" id="clickd" />
<label for="clickd">'.language::gettext('config_doubleclick').'</label><br/>
<input type="radio" class="topcheck savulon" name="clickmode" value="single" id="clicks" />
<label for="clicks">'.language::gettext('config_singleclick').'</label><br>
</div>';
print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="cdplayermode" />
<label for="cdplayermode">'.language::gettext('config_cdplayermode').'</label>
</div>';
if ($skin != "phone") {
print '<div class="pref styledinputs">'.language::gettext('config_wheelspeed').
	'<input class="saveotron" id="wheelscrollspeed" style="width:4em;margin-left:1em" type="text" size="4" />
	</div>';
print '<div class="pref textcentre"><button onclick="shortcuts.edit()">'.
	language::gettext('config_editshortcuts').'</button></div>'."\n";
} else {
	print '<div class="pref styledinputs">
	<input class="autoset toggle" type="checkbox" id="playlistswipe" />
	<label for="playlistswipe">'.language::gettext('config_playlistswipe').'</label>
	</div>';
}
if (prefs::$prefs['player_backend'] == "mpd") {
print '<div class="pref containerbox vertical-centre">
	<div class="fixed" style="margin-right:2em">'.
	language::gettext('config_crossfade').
	'</div>
	<input class="saveotron fixed" style="width:4em" id="crossfade_duration" type="text" size="3" />
	</div>';
}

// Biography and Language
print '<div class="configtitle"><i class="medicon"></i><div class="textcentre expand"><b>'.language::gettext('settings_language').'</b></div></div>';

print '<div class="pref containerbox vertical-centre"><div class="divlabel">'.
language::gettext('settings_interface').
'</div><div class="selectholder"><select id="langselector" onchange="prefs.changelanguage()">';
foreach(language::get_language_list() as $key => $value) {
	print '<option value="'.$key.'">'.$value.'</option>';
}
print '</select></div></div>';

print '<div class="pref containerbox vertical-centre"><div class="divlabel">'.
language::gettext('config_lastfmlang').
'</div><div class="selectholder"><select class="saveomatic" id="lastfmlangselector"">';
print '<option value="default">'.language::gettext('config_lastfmdefault').'</option>';
print '<option value="interface">'.language::gettext('config_lastfminterface').'</option>';
print '<option value="browser">'.language::gettext('config_lastfmbrowser').'</option>';
$l = json_decode(file_get_contents('resources/iso639.json'), true);
foreach ($l as $language) {
	print '<option value="'.$language['alpha2'].'">'.$language['English'].'</option>';
}
print '</select></div></div>';

print '<div class="pref containerbox vertical-centre"><div class="divlabel">'.
language::gettext('config_country').
'</div><div class="selectholder"><select class="saveomatic" id="lastfm_country_codeselector">';
$x = simplexml_load_file('resources/iso3166.xml');
foreach($x->CountryEntry as $i => $c) {
	print '<option value="'.$c->CountryCode.'">'.
		mb_convert_case($c->CountryName, MB_CASE_TITLE, "UTF-8")."</option>\n";
}
print '</select></div></div>';

// Album Art
print '<div class="configtitle"><i class="icon-cd medicon"></i><div class="textcentre expand"><b>'.language::gettext('albumart_title').'</b></div></div>';
print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="downloadart" />
<label for="downloadart">'.language::gettext('config_autocovers').'</label>
</div>
<div class="pref">
<div class="tiny">'.language::gettext('config_musicfolders').'</div>
<input class="saveotron prefinput" id="music_directory_albumart" type="text" size="40" />
</div>';
// print '<div class="pref"><div class="tiny">If you want to use Google Images to get Album Art you need to sign up for an API Key. <a href="https://fatg3erman.github.io/RompR/Album-Art-Manager" target="_blank">'.language::gettext('config_read_the_docs').'</a></div></div>';


// Smart Radio
print '<div class="configtitle"><i class="icon-wifi medicon"></i><div class="textcentre expand"><b>'.language::gettext('label_pluginplaylists').'</b></div></div>';
print '<div class="pref styledinputs">'.language::gettext('config_smart_chunksize').
	'<input class="saveotron" id="smartradio_chunksize" style="width:4em;margin-left:1em" type="text" size="4" />
	</div>';

// Audiobooks
print '<div class="configtitle"><i class="icon-audiobook medicon"></i><div class="textcentre expand"><b>'.language::gettext('label_audiobooks').'</b></div></div>';
print '<div class="pref">'.language::gettext('config_audiobook_directory').'
<input class="prefinput saveotron" id="audiobook_directory" type="text" size="40" />
</div>';
print '<div class="pref">'.language::gettext('config_audiobook_tags').'
<input class="prefinput saveotron arraypref" id="auto_audiobook" type="text" size="40" />
</div>';

// Podcasts
print '<div class="configtitle">
<i class="icon-podcast-circled medicon"></i><div class="textcentre expand"><b>'.language::gettext('label_podcasts').'</b></div></div>';

print '<div class="pref"><b>'.language::gettext('config_podcast_defaults').'</b></div>';

print '<div class="pref containerbox vertical-centre"><div class="divlabel">'.
	language::gettext("podcast_display").'</div>';
print '<div class="selectholder">';
print '<select id="default_podcast_display_modeselector" class="saveomatic">';
$options =  '<option value="'.DISPLAYMODE_ALL.'">'.language::gettext("podcast_display_all").'</option>'.
			'<option value="'.DISPLAYMODE_NEW.'">'.language::gettext("podcast_display_onlynew").'</option>'.
			'<option value="'.DISPLAYMODE_UNLISTENED.'">'.language::gettext("podcast_display_unlistened").'</option>'.
			'<option value="'.DISPLAYMODE_DOWNLOADEDNEW.'">'.language::gettext("podcast_display_downloadnew").'</option>'.
			'<option value="'.DISPLAYMODE_DOWNLOADED.'">'.language::gettext("podcast_display_downloaded").'</option>'.
			'<option value="'.DISPLAYMODE_NUD.'">'.language::gettext("podcast_display_nud").'</option>';
print $options;
print '</select>';
print '</div></div>';

print '<div class="pref containerbox vertical-centre"><div class="divlabel">'.
	language::gettext("podcast_refresh").'</div>';
print '<div class="selectholder">';
print '<select id="default_podcast_refresh_modeselector" class="saveomatic">';
$options =  '<option value="'.REFRESHOPTION_NEVER.'">'.language::gettext("podcast_refresh_never").'</option>'.
			'<option value="'.REFRESHOPTION_HOURLY.'">'.language::gettext("podcast_refresh_hourly").'</option>'.
			'<option value="'.REFRESHOPTION_DAILY.'">'.language::gettext("podcast_refresh_daily").'</option>'.
			'<option value="'.REFRESHOPTION_WEEKLY.'">'.language::gettext("podcast_refresh_weekly").'</option>'.
			'<option value="'.REFRESHOPTION_MONTHLY.'">'.language::gettext("podcast_refresh_monthly").'</option>';
print $options;
print '</select>';
print '</div></div>';

print '<div class="pref containerbox vertical-centre"><div class="divlabel">'.
	language::gettext("podcast_sortmode").'</div>';
print '<div class="selectholder">';
print '<select id="default_podcast_sort_modeselector" class="saveomatic">';
$options =  '<option value="'.SORTMODE_NEWESTFIRST.'">'.language::gettext("podcast_newestfirst").'</option>'.
			'<option value="'.SORTMODE_OLDESTFIRST.'">'.language::gettext("podcast_oldestfirst").'</option>';
print $options;
print '</select>';
print '</div></div>';

print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="podcast_mark_new_as_unlistened" />
<label for="podcast_mark_new_as_unlistened">'.language::gettext('config_marknewasunlistened').'</label>
</div>';

// Last.FM
print '<div class="configtitle">
<i class="icon-lastfm-1 medicon"></i><div class="textcentre expand"><b>'.language::gettext('label_lastfm').'</b>
</div></div>';

print '<div class="pref">'.language::gettext('config_lastfmusername').'<br/><div class="containerbox"><div class="expand">'.
	'<input class="enter" name="lfmuser" type="text" size="30" value="'.prefs::$prefs['lastfm_user'].'"/>'.
	'</div><button id="lastfmloginbutton" class="fixed">'.language::gettext('config_loginbutton').
	'</button></div>';
print '</div>';

print '<div class="pref styledinputs">
<input class="autoset toggle" type="checkbox" id="lastfm_autocorrect" />
<label for="lastfm_autocorrect">'.language::gettext('config_autocorrect').'</label>
</div>';

print '<div class="pref styledinputs lastfmlogin-required">
<input class="autoset toggle" type="checkbox" id="sync_lastfm_playcounts" />
<label for="sync_lastfm_playcounts">'.language::gettext('config_lastfm_playcounts').'</label>
</div>';

print '<div class="pref styledinputs lastfmlogin-required">
<input class="autoset toggle" type="checkbox" id="sync_lastfm_at_start" />
<label for="sync_lastfm_at_start">'.language::gettext('config_sync_lastfm_playcounts').'</label>
</div>';

print '<div class="pref styledinputs lastfmlogin-required">
<input class="autoset toggle" type="checkbox" id="lastfm_scrobbling" />
<label for="lastfm_scrobbling">'.language::gettext('config_scrobbling').'</label>
</div>
<div class="pref lastfmlogin-required">'.language::gettext('config_scrobblepercent').'<br/>
<div id="scrobwrangler"></div>
</div>
<div class="pref lastfmlogin-required">'.language::gettext('config_tagloved').'
<input class="prefinput saveotron" id="autotagname" type="text" size="40" />
</div>';

// Tags and Ratings
print '<div class="pref styledinputs lastfmlogin-required">
<input class="autoset toggle" type="checkbox" id="synctags" />
<label for="synctags">'.language::gettext('config_synctags').'</label>';
?>
</div>
<div class="pref containerbox vertical-centre lastfmlogin-required">
<?php
print '<div class="divlabel styledinputs"><input class="autoset toggle" type="checkbox" id="synclove" />
<label for="synclove">'.language::gettext('config_loveis').'</label></div>';
?>
<div class="selectholder"><select id="synclovevalueselector" class="saveomatic">
<?php
print '<option value="5">5 '.language::gettext('stars').'</option>
<option value="4">4 '.language::gettext('stars').'</option>
<option value="3">3 '.language::gettext('stars').'</option>
<option value="2">2 '.language::gettext('stars').'</option>
<option value="1">1 '.language::gettext('star').'</option>';
print '</select>
</div></div>';

// Collection Options
print '<div class="configtitle"><i class="icon-music medicon"></i><div class="textcentre expand">
	<b>'.language::gettext('button_local_music').'</b></div></div>';

// Album Sorting
print '<div class="pref"><b>'.language::gettext('config_artistfirst').'
<input class="saveotron prefinput arraypref" id="artistsatstart" type="text" size="256" />
</b></div>';
print '<div class="pref"><b>'.language::gettext('config_nosortprefixes').'
<input class="saveotron prefinput arraypref" id="nosortprefixes" type="text" size="128" />
</b></div>';

if (prefs::$prefs['multihosts'][prefs::$prefs['currenthost']]['mopidy_remote'] == false) {

	if (prefs::$prefs['collection_player'] == prefs::$prefs['player_backend'] || prefs::$prefs['collection_player'] == null) {

		print '<div class="pref styledinputs">
		<input class="autoset toggle" type="checkbox" id="use_original_releasedate" />
		<label for="use_original_releasedate">'.language::gettext('config_use_original_releasedate').'</label>
		</div>';


		print '<div class="pref styledinputs">
			<div class="clearfix">
				<div class="tleft">
					<input class="autoset toggle" type="checkbox" id="updateeverytime" /><label for="updateeverytime">'.language::gettext('config_updateonstart').'</label>
				</div>';
		print '<button class="tright" name="donkeykong">'.language::gettext('config_updatenow').'</button>';
		print '</div>';
		if (prefs::$prefs['player_backend'] == "mpd" && prefs::$prefs['collection_player'] !== null) {
			print '<div class="clearfix"><button class="tright" name="dinkeyking">'.language::gettext('config_rescan').'</button></div>';
		}
		print '</div>';
	}

	logger::info('PREFSPANEL', 'Collection Player is', prefs::$prefs['collection_player']);
	logger::info('PREFSPANEL', 'Player Backend is', prefs::$prefs['player_backend']);

	if ((prefs::$prefs['collection_player'] == "mopidy" || prefs::$prefs['collection_player'] == null) && prefs::$prefs['player_backend'] == 'mopidy') {
		print '<div class="pref" id="mopidycollectionoptions">'.
		'<b>'.language::gettext('config_collectionfolders').'</b></div>';
		print '<div class="pref">'.language::gettext('config_beetsserver').'
		<input class="prefinput saveotron" id="beets_server_location" type="text" size="40" />
		</div>';

		print '<div class="pref styledinputs">
		<input class="autoset toggle" type="checkbox" id="preferlocalfiles" />
		<label for="preferlocalfiles">'.language::gettext('config_preferlocal').'</label></div>';
	}

	if (prefs::$prefs['collection_player'] == prefs::$prefs['player_backend'] || prefs::$prefs['collection_player'] == null) {
		print '<div class="pref styledinputs">
		<input class="autoset toggle" type="checkbox" id="sortbycomposer" />
		<label for="sortbycomposer">'.language::gettext('config_sortbycomposer').'</label>
		</div>';
		print '<div class="pref indent styledinputs">
		<input class="autoset toggle" type="checkbox" id="composergenre" />
		<label for="composergenre">'.language::gettext('config_composergenre').'</label>
		</div>';
		print '<div class="pref indent">
		<input class="saveotron prefinput arraypref" id="composergenrename" type="text" size="40" />
		</div>';
	}
}
?>
