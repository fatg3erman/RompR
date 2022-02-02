<?php

// There may appear to be a lot of unnecessary divs wrapping around things here
// but it makes it work in Safari. DO NOT CHANGE IT!

print uibits::ui_config_header([
	'label' => 'button_prefs',
	'icon_size' => 'smallicon'
]);


// =======================================================
//
// Players
//
// =======================================================
print uibits::ui_config_header([
	'label' => 'config_players'
]);
print '<div class="fullwidth">';
print '<div class="clearfix">';
print '<div class="pref styledinputs tleft" name="playerdefs">';
print '</div>';
print '<div class="pref tright"><button onclick="player.defs.edit()">'.language::gettext('button_edit_players').'</button></div>';
print '</div>';

uibits::ui_checkbox(['id' => 'player_in_titlebar', 'label' => 'config_playerintitlebar']);
uibits::ui_checkbox(['id' => 'consume_workaround', 'label' => 'config_consumeworkaround']);
if (prefs::$prefs['player_backend'] == "mpd") {
	uibits::ui_textentry([
		'label' => 'config_crossfade',
		'size' => 3,
		'id' => 'crossfade_duration',
		'type' => 'number'
	]);
}
print '</div>';

// =======================================================
//
// Snapcast
//
// =======================================================
print uibits::ui_config_header([
	'main_icon' => 'icon-snapcast'
]);
print '<div class="fullwidth">';
if (!$snapcast_in_volume) {
	print '<div class="pref" id="snapcastgroups">';
	print '</div>';
}
uibits::ui_checkbox(['id' => 'hide_master_volume', 'label' => 'config_hidemastervolume']);

print '<div class="pref styledinputs containerbox vertical-centre">';
print '<input class="saveotron expand" id="snapcast_server" type="text" placeholder="'.language::gettext('config_snapcast_server').'" />';
print '<input class="saveotron fixed" id="snapcast_http" style="width:4em;margin-left:1em" type="text" size="4" placeholder="'.language::gettext('config_snapcast_http').'" />';
print '</div>';
print '</div>';

// =======================================================

// =======================================================
//
// Skin
//
// =======================================================
print uibits::ui_config_header([
	'label' => 'settings_appearance'
]);
$skins = glob("skins/*");
uibits::ui_select_box([
	'id' => 'skin',
	'options' => array_combine(
		array_map('basename', array_filter($skins, 'is_dir')),
		array_map('ucfirst', array_map('basename', array_filter($skins, 'is_dir'))),
	),
	'label' => language::gettext('config_skin')
]);

//
// Theme
//
$themes = glob("themes/*.css");
uibits::ui_select_box([
	'id' => 'theme',
	'options' => array_combine(
		array_map('basename', $themes),
		array_map('get_filename', $themes),
	),
	'label' => language::gettext('config_theme')
]);

//
// Icon Theme
//
$themes = glob("iconsets/*");
uibits::ui_select_box([
	'id' => 'icontheme',
	'options' => array_combine(
		array_map('basename', array_filter($themes, 'is_dir')),
		array_map('basename', array_filter($themes, 'is_dir')),
	),
	'label' => language::gettext('config_icontheme')
]);

//
// Font
//
$themes = glob("fonts/*.css");
uibits::ui_select_box([
	'id' => 'fontfamily',
	'options' => array_combine(
		array_map('basename', $themes),
		array_map('get_filename', $themes),
	),
	'label' => language::gettext('config_fontname')
]);

//
//Font Size
//
uibits::ui_select_box([
	'id' => 'fontsize',
	'options' => array_flip(FONT_SIZES),
	'label' => language::gettext('config_fontsize')
]);

//
// Album Cover Size
//
uibits::ui_select_box([
	'id' => 'coversize',
	'options' => array_flip(COVER_SIZES),
	'label' => language::gettext('config_coversize')
]);

// =======================================================
//
// Custom Background
//
// =======================================================
print '<div id="custombackground">';

print uibits::ui_config_header([
	'label' => 'config_background',
	'class' => 'open_magic_div'
]);

print '<div id="cusbgoptions">';

uibits::ui_radio([
	[
		'typeclass' => null,
		'name' => 'backgroundposition',
		'id' => 'attach_centre',
		'value' => 'center center',
		'label' => 'label_centre'
	],
	[
		'typeclass' => null,
		'name' => 'backgroundposition',
		'id' => 'attach_topleft',
		'value' => 'top left',
		'label' => 'label_topleft'
	],
	[
		'typeclass' => null,
		'name' => 'backgroundposition',
		'id' => 'attach_topcentre',
		'value' => 'top centrer',
		'label' => 'label_topcentre'
	],
	[
		'typeclass' => null,
		'name' => 'backgroundposition',
		'id' => 'attach_topright',
		'value' => 'top right',
		'label' => 'label_topright'
	],
	[
		'typeclass' => null,
		'name' => 'backgroundposition',
		'id' => 'attach_bottomleft',
		'value' => 'bottom left',
		'label' => 'label_bottomleft'
	],
	[
		'typeclass' => null,
		'name' => 'backgroundposition',
		'id' => 'attach_bottomcentre',
		'value' => 'bottom center',
		'label' => 'label_bottomcentre'
	],
	[
		'typeclass' => null,
		'name' => 'backgroundposition',
		'id' => 'attach_bottomright',
		'value' => 'bottom right',
		'label' => 'label_bottomright'
	]
]);

uibits::ui_select_box([
	'id' => 'changeevery',
	'options' => array_flip(BG_IMAGE_TIMEOUTS),
	'label' => language::gettext('label_changevery'),
	'typeclass' => null
]);

uibits::ui_checkbox(['id' => 'cus_bg_random', 'label' => 'label_random_order', 'typeclass' => null]);

print '</div>';

uibits::ui_config_button([
	'label' => 'manage_bgs',
	'onclick' => 'prefs.manage_bg_images()'
]);

uibits::ui_textentry([
	'label' => 'config_browserid',
	'id' => 'browser_id',
	'class' => 'invisible magic_div'
]);

print '</div>';

// =======================================================
//
// Sources Panel Hiding
//
// =======================================================
print uibits::ui_config_header([
	'label' => 'settings_panels'
]);

uibits::ui_checkbox(['id' => 'hide_albumlist', 'label' => 'config_hidealbumlist']);
uibits::ui_checkbox(['id' => 'hide_searcher', 'label' => 'config_hidesearcher']);
uibits::ui_checkbox(['id' => 'hide_filelist', 'label' => 'config_hidefileslist']);
uibits::ui_checkbox(['id' => 'hide_radiolist', 'label' => 'config_hideradio']);
uibits::ui_checkbox(['id' => 'hide_podcastslist', 'label' => 'config_hidepodcasts']);
uibits::ui_checkbox(['id' => 'hide_audiobooklist', 'label' => 'config_hideaudiobooks']);
uibits::ui_checkbox(['id' => 'hide_playlistslist', 'label' => 'config_hideplaylistslist']);
uibits::ui_checkbox(['id' => 'hide_pluginplaylistslist', 'label' => 'config_hidepluginplaylistslist']);
if ($skin == "desktop")
	uibits::ui_checkbox(['id' => 'hidebrowser', 'label' => 'config_hidebrowser']);

// =======================================================
//
// Interface
//
// =======================================================
print uibits::ui_config_header([
	'label' => 'settings_interface'
]);
uibits::ui_checkbox(['id' => 'scrolltocurrent', 'label' => 'config_autoscroll']);
if ($use_plugins)
	uibits::ui_checkbox(['id' => 'auto_discovembobulate', 'label' => 'config_discovembobulate']);

uibits::ui_checkbox(['id' => 'displaycomposer', 'label' => 'config_displaycomposer']);
uibits::ui_checkbox(['id' => 'use_albumart_in_playlist', 'label' => 'config_albumartinplaylist']);


//
// Click Policy
//
uibits::ui_radio([
	[
		'name' => 'clickmode',
		'value' => 'double',
		'id' => 'clickd',
		'label' => 'config_doubleclick',
		'class' => ''
	],
	[
		'name' => 'clickmode',
		'value' => 'single',
		'id' => 'clicks',
		'label' => 'config_singleclick',
		'class' => ''
	]
]);

uibits::ui_checkbox(['id' => 'cdplayermode', 'label' => 'config_cdplayermode']);

if ($skin != "phone") {
	uibits::ui_textentry([
		'label' => 'config_wheelspeed',
		'size' => 4,
		'id' => 'wheelscrollspeed',
		'type' => 'number'
	]);
	uibits::ui_config_button([
		'label' => 'config_editshortcuts',
		'onclick' => 'shortcuts.edit()'
	]);
} else {
	uibits::ui_checkbox(['id' => 'playlistswipe', 'label' => 'config_playlistswipe']);
}

// =======================================================
//
// Biography and Language
//
// =======================================================
print uibits::ui_config_header([
	'label' => 'settings_language'
]);

uibits::ui_select_box([
	'id' => 'interface_language',
	'options' => language::get_language_list(),
	'label' => language::gettext('settings_interface')
]);

$lfm = [
	'default' => language::gettext('config_lastfmdefault'),
	'interface' => language::gettext('config_lastfminterface'),
	'browser' => language::gettext('config_lastfmbrowser')
];
$l = json_decode(file_get_contents('resources/iso639.json'), true);
foreach ($l as $language) {
	$lfm[$language['alpha2']] = $language['English'];
}
uibits::ui_select_box([
	'id' => 'lastfmlang',
	'options' => $lfm,
	'label' => language::gettext('config_lastfmlang')
]);

$countries = [];
$x = simplexml_load_file('resources/iso3166.xml');
foreach($x->CountryEntry as $i => $c) {
	$countries[(string) $c->CountryCode] = mb_convert_case($c->CountryName, MB_CASE_TITLE, "UTF-8");
}
uibits::ui_select_box([
	'id' => 'lastfm_country_code',
	'options' => $countries,
	'label' => language::gettext('config_country')
]);

// =======================================================
//
// Album Art
//
// =======================================================
print uibits::ui_config_header([
	'label' => 'albumart_title',
	'lefticon' => 'icon-cd'
]);
uibits::ui_checkbox(['id' => 'downloadart', 'label' => 'config_autocovers']);

uibits::ui_textentry([
	'label' => 'config_musicfolders',
	'id' => 'music_directory_albumart'
]);

// =======================================================
//
// Smart Radio
//
// =======================================================
print uibits::ui_config_header([
	'label' => 'label_pluginplaylists',
	'lefticon' => 'icon-wifi'
]);
uibits::ui_textentry([
	'label' => 'config_smart_chunksize',
	'id' => 'smartradio_chunksize',
	'size' => 4,
	'type' => 'number'
]);

// =======================================================
//
// Audiobooks
//
// =======================================================
print uibits::ui_config_header([
	'label' => 'label_audiobooks',
	'lefticon' => 'icon-audiobook'
]);

uibits::ui_textentry([
	'label' => 'config_audiobook_directory',
	'id' => 'audiobook_directory'
]);

uibits::ui_textentry([
	'label' => 'config_audiobook_tags',
	'id' => 'auto_audiobook',
	'is_array' => true
]);

// =======================================================
//
// Podcasts
//
// =======================================================
print uibits::ui_config_header([
	'label' => 'label_podcasts',
	'lefticon' => 'icon-podcast-circled'
]);

print '<div class="pref"><b>'.language::gettext('config_podcast_defaults').'</b></div>';
uibits::ui_select_box([
	'id' => 'default_podcast_display_mode',
	'label' => language::gettext('podcast_display'),
	'options' => [
		DISPLAYMODE_ALL => language::gettext("podcast_display_all"),
		DISPLAYMODE_NEW => language::gettext("podcast_display_onlynew"),
		DISPLAYMODE_UNLISTENED => language::gettext("podcast_display_unlistened"),
		DISPLAYMODE_DOWNLOADEDNEW => language::gettext("podcast_display_downloadnew"),
		DISPLAYMODE_DOWNLOADED => language::gettext("podcast_display_downloaded"),
		DISPLAYMODE_NUD => language::gettext("podcast_display_nud")
	]
]);

uibits::ui_select_box([
	'id' => 'default_podcast_refresh_mode',
	'label' => language::gettext('podcast_refresh'),
	'options' => [
  		REFRESHOPTION_NEVER => language::gettext("podcast_refresh_never"),
		REFRESHOPTION_HOURLY => language::gettext("podcast_refresh_hourly"),
		REFRESHOPTION_DAILY => language::gettext("podcast_refresh_daily"),
		REFRESHOPTION_WEEKLY => language::gettext("podcast_refresh_weekly"),
		REFRESHOPTION_MONTHLY => language::gettext("podcast_refresh_monthly")
	]
]);

uibits::ui_select_box([
	'id' => 'default_podcast_sort_mode',
	'label' => language::gettext('podcast_sortmode'),
	'options' => [
		SORTMODE_NEWESTFIRST => language::gettext("podcast_newestfirst"),
		SORTMODE_OLDESTFIRST => language::gettext("podcast_oldestfirst")
	]
]);

uibits::ui_checkbox(['id' => 'podcast_mark_new_as_unlistened', 'label' => 'config_marknewasunlistened']);

// =======================================================
//
// Last.FM
//
// =======================================================
print uibits::ui_config_header([
	'main_icon' => 'icon-lastfm-logo'
]);

print '<div class="pref">'.language::gettext('config_lastfmusername').'<br/><div class="containerbox"><div class="expand">'.
	'<input class="enter" name="lfmuser" type="text" size="30" value="'.prefs::$prefs['lastfm_user'].'"/>'.
	'</div><button id="lastfmloginbutton" class="fixed">'.language::gettext('config_loginbutton').
	'</button></div>';
print '</div>';

uibits::ui_checkbox(['id' => 'lastfm_autocorrect', 'label' => 'config_autocorrect']);
uibits::ui_checkbox(['id' => 'sync_lastfm_playcounts', 'label' => 'config_lastfm_playcounts', 'class' => 'lastmlogin-required']);
uibits::ui_checkbox(['id' => 'sync_lastfm_at_start', 'label' => 'config_sync_lastfm_playcounts', 'class' => 'lastmlogin-required']);
uibits::ui_checkbox(['id' => 'lastfm_scrobbling', 'label' => 'config_scrobbling', 'class' => 'lastmlogin-required']);
uibits::ui_checkbox(['id' => 'synctags', 'label' => 'config_synctags', 'class' => 'lastmlogin-required']);

print '<div class="pref lastfmlogin-required">'.language::gettext('config_scrobblepercent').'<br/>
<div id="scrobwrangler"></div>
</div>';

uibits::ui_textentry([
	'label' => 'config_tagloved',
	'id' => 'autotagname',
	'class' => 'lastfmlogin-required'
]);

uibits::ui_select_box([
	'id' => 'synclovevalue',
	'label' => language::gettext('config_loveis'),
	'options' => [
		0 => 'Nothing',
		1 => '1 '.language::gettext('star'),
		2 => '2 '.language::gettext('stars'),
		3 => '3 '.language::gettext('stars'),
		4 => '4 '.language::gettext('stars'),
		5 => '5 '.language::gettext('stars'),
	]
]);

// =======================================================
//
// Collection Options
//
// =======================================================
print uibits::ui_config_header([
	'label' => 'button_local_music',
	'lefticon' => 'icon-music'
]);

$update_buttons = [
	[
		'label' => 'config_updatenow',
		'name' => 'donkeykong'
	]
];
if (prefs::$prefs['player_backend'] == "mpd" && prefs::$prefs['collection_player'] !== null) {
	$update_buttons[] = [
		'label' => 'config_rescan',
		'name' => 'dinkeyking'
	];
}
uibits::ui_config_button($update_buttons);

//
// Album Sorting
//
uibits::ui_textentry([
	'label' => 'config_artistfirst',
	'id' => 'artistsatstart',
	'is_array' => true
]);
uibits::ui_textentry([
	'label' => 'config_nosortprefixes',
	'id' => 'nosortprefixes',
	'is_array' => true
]);

if (prefs::$prefs['multihosts'][prefs::$prefs['currenthost']]['mopidy_remote'] == false) {

	if (prefs::$prefs['collection_player'] == prefs::$prefs['player_backend'] || prefs::$prefs['collection_player'] == null) {
		uibits::ui_checkbox(['id' => 'use_original_releasedate', 'label' => 'config_use_original_releasedate']);
		uibits::ui_checkbox(['id' => 'updateeverytime', 'label' => 'config_updateonstart']);
	}

	logger::info('PREFSPANEL', 'Collection Player is', prefs::$prefs['collection_player']);
	logger::info('PREFSPANEL', 'Player Backend is', prefs::$prefs['player_backend']);

	if ((prefs::$prefs['collection_player'] == "mopidy" || prefs::$prefs['collection_player'] == null) && prefs::$prefs['player_backend'] == 'mopidy') {
		print '<div class="pref" id="mopidycollectionoptions">'.
		'<b>'.language::gettext('config_collectionfolders').'</b></div>';

		uibits::ui_textentry([
			'label' => 'config_beetsserver',
			'id' => 'beets_server_location'
		]);
		uibits::ui_checkbox(['id' => 'preferlocalfiles', 'label' => 'config_preferlocal']);
	}

	if (prefs::$prefs['collection_player'] == prefs::$prefs['player_backend'] || prefs::$prefs['collection_player'] == null) {
		uibits::ui_checkbox(['id' => 'sortbycomposer', 'label' => 'config_sortbycomposer']);
		uibits::ui_checkbox(['id' => 'composergenre', 'label' => 'config_composergenre', 'class' => 'indent']);
		uibits::ui_textentry([
			'id' => 'composergenrename',
			'is_array' => true,
			'class' => 'indent'
		]);
	}
}

?>
