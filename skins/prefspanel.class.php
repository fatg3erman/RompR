<?php

class prefspanel extends uibits {

	public static function make_prefs_panel() {

		// There may appear to be a lot of unnecessary divs wrapping around things here
		// but it makes it work in Safari. DO NOT CHANGE IT!

		print self::ui_config_header([
			'label' => 'button_prefs',
			'icon_size' => 'smallicon'
		]);

		// =======================================================
		//
		// Players
		//
		// =======================================================
		// print self::ui_config_header([
		// 	'label' => 'config_players'
		// ]);
		// print '<div class="fullwidth">';
		// print '<div class="clearfix">';
		// print '<div class="pref styledinputs tleft" name="playerdefs">';
		// print '</div>';
		// print '<div class="pref tright"><button onclick="player.defs.edit()">'.language::gettext('button_edit_players').'</button></div>';
		// print '</div>';

		// self::ui_checkbox(['id' => 'player_in_titlebar', 'label' => 'config_playerintitlebar']);
		// // self::ui_checkbox(['id' => 'consume_workaround', 'label' => 'config_consumeworkaround']);
		// if (prefs::get_pref('player_backend') == "mpd") {
		// 	self::ui_textentry([
		// 		'label' => 'config_crossfade',
		// 		'size' => 3,
		// 		'id' => 'crossfade_duration',
		// 		'type' => 'number'
		// 	]);
		// }
		// print '</div>';

		// =======================================================
		//
		// Snapcast
		//
		// =======================================================
		// print self::ui_config_header([
		// 	'main_icon' => 'icon-snapcast'
		// ]);
		// print '<div class="fullwidth">';
		// if (!self::SNAPCAST_IN_VOLUME) {
		// 	print '<div class="pref" id="snapcastgroups">';
		// 	print '</div>';
		// }
		// self::ui_checkbox(['id' => 'hide_master_volume', 'label' => 'config_hidemastervolume']);

		// print '<div class="pref styledinputs containerbox vertical-centre">';
		// print '<input class="saveotron expand" id="snapcast_server" type="text" placeholder="'.language::gettext('config_snapcast_server').'" />';
		// print '<input class="saveotron fixed" id="snapcast_http" style="width:4em;margin-left:1em" type="text" size="4" placeholder="'.language::gettext('config_snapcast_http').'" />';
		// print '</div>';
		// print '</div>';

		// =======================================================
		//
		// Skin
		//
		// =======================================================
		print self::ui_config_header([
			'label' => 'settings_appearance'
		]);
		$skins = glob("skins/*");
		self::ui_select_box([
			'id' => 'skin',
			'options' => array_combine(
				array_map('basename', array_filter($skins, 'is_dir')),
				array_map('ucfirst', array_map('basename', array_filter($skins, 'is_dir')))
			),
			'label' => language::gettext('config_skin')
		]);

		//
		// Theme
		//
		$themes = glob("themes/*.css");
		self::ui_select_box([
			'id' => 'theme',
			'options' => array_combine(
				array_map('basename', $themes),
				array_map('get_filename', $themes)
			),
			'label' => language::gettext('config_theme')
		]);

		//
		// Icon Theme
		//
		$themes = glob("iconsets/*");
		self::ui_select_box([
			'id' => 'icontheme',
			'options' => array_combine(
				array_map('basename', array_filter($themes, 'is_dir')),
				array_map('basename', array_filter($themes, 'is_dir'))
			),
			'label' => language::gettext('config_icontheme')
		]);

		//
		// Font
		//
		$themes = glob("fonts/*.css");
		self::ui_select_box([
			'id' => 'fontfamily',
			'options' => array_combine(
				array_map('basename', $themes),
				array_map('get_filename', $themes)
			),
			'label' => language::gettext('config_fontname')
		]);

		//
		//Font Size
		//
		self::ui_select_box([
			'id' => 'fontsize',
			'options' => array_flip(FONT_SIZES),
			'label' => language::gettext('config_fontsize')
		]);

		//
		// Album Cover Size
		//
		self::ui_select_box([
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

		print self::ui_config_header([
			'label' => 'config_background',
			'class' => 'open_magic_div'
		]);

		print '<div id="cusbgoptions">';

		self::ui_radio([
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

		self::ui_checkbox(['id' => 'cus_bg_random', 'label' => 'label_random_order', 'typeclass' => null]);

		self::ui_select_box([
			'id' => 'changeevery',
			'options' => array_flip(BG_IMAGE_TIMEOUTS),
			'label' => language::gettext('label_changevery'),
			'typeclass' => null
		]);

		self::ui_config_button([
			'label' => 'label_remcur',
			'onclick' => 'prefs.removeCurrentBackground()'
		]);

		print '</div>';

		self::ui_config_button([
			'label' => 'manage_bgs',
			'onclick' => 'prefs.manage_bg_images()'
		]);

		self::ui_textentry([
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
		print self::ui_config_header([
			'label' => 'settings_panels'
		]);

		self::ui_checkbox(['id' => 'hide_albumlist', 'label' => 'config_hidealbumlist']);
		self::ui_checkbox(['id' => 'hide_searcher', 'label' => 'config_hidesearcher']);
		self::ui_checkbox(['id' => 'hide_filelist', 'label' => 'config_hidefileslist']);
		self::ui_checkbox(['id' => 'hide_radiolist', 'label' => 'config_hideradio']);
		self::ui_checkbox(['id' => 'hide_podcastslist', 'label' => 'config_hidepodcasts']);
		self::ui_checkbox(['id' => 'hide_audiobooklist', 'label' => 'config_hideaudiobooks']);
		self::ui_checkbox(['id' => 'hide_playlistslist', 'label' => 'config_hideplaylistslist']);
		self::ui_checkbox(['id' => 'hide_pluginplaylistslist', 'label' => 'config_hidepluginplaylistslist']);
		self::prefs_hide_panels();

		// =======================================================
		//
		// Interface
		//
		// =======================================================
		print self::ui_config_header([
			'label' => 'settings_interface'
		]);
		self::ui_checkbox(['id' => 'scrolltocurrent', 'label' => 'config_autoscroll']);
		// self::ui_checkbox(['id' => 'auto_discovembobulate', 'label' => 'config_discovembobulate']);

		self::ui_checkbox(['id' => 'displaycomposer', 'label' => 'config_displaycomposer']);
		self::ui_checkbox(['id' => 'use_albumart_in_playlist', 'label' => 'config_albumartinplaylist']);


		//
		// Click Policy
		//
		self::ui_radio([
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

		self::ui_checkbox(['id' => 'cdplayermode', 'label' => 'config_cdplayermode']);

		self::prefs_mouse_options();

		self::prefs_touch_options();

		// =======================================================
		//
		// Biography and Language
		//
		// =======================================================
		print self::ui_config_header([
			'label' => 'settings_language'
		]);

		self::ui_select_box([
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
		self::ui_select_box([
			'id' => 'lastfmlang',
			'options' => $lfm,
			'label' => language::gettext('config_lastfmlang')
		]);

		$countries = [];
		$x = simplexml_load_file('resources/iso3166.xml');
		foreach($x->CountryEntry as $i => $c) {
			$countries[(string) $c->CountryCode] = mb_convert_case($c->CountryName, MB_CASE_TITLE, "UTF-8");
		}
		self::ui_select_box([
			'id' => 'lastfm_country_code',
			'options' => $countries,
			'label' => language::gettext('config_country')
		]);

		// =======================================================
		//
		// Album Art
		//
		// =======================================================
		print self::ui_config_header([
			'label' => 'albumart_title',
			'lefticon' => 'icon-cd'
		]);
		self::ui_checkbox(['id' => 'downloadart', 'label' => 'config_autocovers']);

		self::ui_textentry([
			'label' => 'config_musicfolders',
			'id' => 'music_directory_albumart'
		]);

		// =======================================================
		//
		// Collection Options
		//
		// =======================================================
		print self::ui_config_header([
			'label' => 'button_local_music',
			'lefticon' => 'icon-music'
		]);

		$update_buttons = [
			[
				'label' => 'config_updatenow',
				'name' => 'donkeykong'
			]
		];
		if (prefs::get_pref('player_backend') == "mpd" && prefs::get_pref('collection_player') !== null) {
			$update_buttons[] = [
				'label' => 'config_rescan',
				'name' => 'dinkeyking'
			];
		}
		self::ui_config_button($update_buttons);

		//
		// Album Sorting
		//
		self::ui_textentry([
			'label' => 'config_artistfirst',
			'id' => 'artistsatstart',
			'is_array' => true
		]);
		self::ui_textentry([
			'label' => 'config_nosortprefixes',
			'id' => 'nosortprefixes',
			'is_array' => true
		]);

		$pd = prefs::get_player_def();
		if ($pd['mopidy_remote'] == false) {

			if (prefs::get_pref('collection_player') == prefs::get_pref('player_backend') || prefs::get_pref('collection_player') == null) {
				self::ui_checkbox(['id' => 'use_original_releasedate', 'label' => 'config_use_original_releasedate']);
				self::ui_checkbox(['id' => 'updateeverytime', 'label' => 'config_updateonstart']);
			}

			if ((prefs::get_pref('collection_player') == "mopidy" || prefs::get_pref('collection_player') == null)
				&& prefs::get_pref('player_backend') == 'mopidy')
			{
				print '<div class="pref" id="mopidycollectionoptions">'.
				'<b>'.language::gettext('config_collectionfolders').'</b></div>';

				self::ui_textentry([
					'label' => 'config_beetsserver',
					'id' => 'beets_server_location'
				]);
				self::ui_checkbox(['id' => 'preferlocalfiles', 'label' => 'config_preferlocal']);
			}

			if (prefs::get_pref('collection_player') == prefs::get_pref('player_backend') || prefs::get_pref('collection_player') == null) {
				self::ui_checkbox(['id' => 'sortbycomposer', 'label' => 'config_sortbycomposer']);
				self::ui_checkbox(['id' => 'composergenre', 'label' => 'config_composergenre', 'class' => 'indent']);
				self::ui_textentry([
					'id' => 'composergenrename',
					'is_array' => true,
					'class' => 'indent'
				]);
			}
		}

		// =======================================================
		//
		// Audiobooks
		//
		// =======================================================
		print self::ui_config_header([
			'label' => 'label_audiobooks',
			'lefticon' => 'icon-audiobook'
		]);

		self::ui_textentry([
			'label' => 'config_audiobook_directory',
			'id' => 'audiobook_directory'
		]);

		self::ui_textentry([
			'label' => 'config_audiobook_tags',
			'id' => 'auto_audiobook',
			'is_array' => true
		]);

		// =======================================================
		//
		// Podcasts
		//
		// =======================================================
		print self::ui_config_header([
			'label' => 'label_podcasts',
			'lefticon' => 'icon-podcast-circled'
		]);

		print '<div class="pref"><b>'.language::gettext('config_podcast_defaults').'</b></div>';
		self::ui_select_box([
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

		self::ui_select_box([
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

		self::ui_select_box([
			'id' => 'default_podcast_sort_mode',
			'label' => language::gettext('podcast_sortmode'),
			'options' => [
				SORTMODE_NEWESTFIRST => language::gettext("podcast_newestfirst"),
				SORTMODE_OLDESTFIRST => language::gettext("podcast_oldestfirst")
			]
		]);

		self::ui_checkbox(['id' => 'podcast_mark_new_as_unlistened', 'label' => 'config_marknewasunlistened']);

		// =======================================================
		//
		// Smart Radio
		//
		// =======================================================
		// print self::ui_config_header([
		// 	'label' => 'label_pluginplaylists',
		// 	'lefticon' => 'icon-wifi'
		// ]);
		// self::ui_textentry([
		// 	'label' => 'config_smart_chunksize',
		// 	'id' => 'smartradio_chunksize',
		// 	'size' => 4,
		// 	'type' => 'number'
		// ]);

		// =======================================================
		//
		// Last.FM
		//
		// =======================================================
		// print self::ui_config_header([
		// 	'main_icon' => 'icon-lastfm-logo'
		// ]);

		// print '<div class="pref">'.language::gettext('config_lastfmusername').'<br/><div class="containerbox"><div class="expand">'.
		// 	'<input name="lfmuser" type="text" size="30" value="'.prefs::get_pref('lastfm_user').'"/>'.
		// 	'</div><button id="lastfmloginbutton" class="fixed notenabled">'.language::gettext('config_loginbutton').
		// 	'</button></div>';
		// print '</div>';

		// self::ui_checkbox(['id' => 'lastfm_autocorrect', 'label' => 'config_autocorrect']);
		// self::ui_checkbox(['id' => 'sync_lastfm_playcounts', 'label' => 'config_lastfm_playcounts', 'class' => 'lastfmlogin-required']);
		// self::ui_checkbox(['id' => 'sync_lastfm_at_start', 'label' => 'config_sync_lastfm_playcounts', 'class' => 'lastfmlogin-required']);
		// self::ui_checkbox(['id' => 'lastfm_scrobbling', 'label' => 'config_scrobbling', 'class' => 'lastfmlogin-required']);
		// self::ui_checkbox(['id' => 'synctags', 'label' => 'config_synctags', 'class' => 'lastfmlogin-required']);

		// self::ui_select_box([
		// 	'id' => 'synclovevalue',
		// 	'label' => language::gettext('config_loveis'),
		// 	'class' => 'lastfmlogin-required',
		// 	'options' => [
		// 		0 => 'Nothing',
		// 		1 => '1 '.language::gettext('star'),
		// 		2 => '2 '.language::gettext('stars'),
		// 		3 => '3 '.language::gettext('stars'),
		// 		4 => '4 '.language::gettext('stars'),
		// 		5 => '5 '.language::gettext('stars')
		// 	]
		// ]);

		// print '<div class="pref lastfmlogin-required">'.language::gettext('config_scrobblepercent').'<br/>
		// <div id="scrobwrangler"></div>
		// </div>';

		// self::ui_textentry([
		// 	'label' => 'config_tagloved',
		// 	'id' => 'autotagname',
		// 	'class' => 'lastfmlogin-required'
		// ]);

		// =======================================================
		//
		// Defaults
		//
		// =======================================================
		print self::ui_config_header([
			'label' => 'label_ui_defaults'
		]);

		self::ui_config_button([[
			'label' => 'button_save_defaults',
			'name' => 'save-defaults',
			'onclick' => 'prefs.save_defaults()'
		]]);

		// =======================================================
		//
		// Defaults
		//
		// =======================================================
		print self::ui_config_header([
			'label' => 'label_power'
		]);

		self::ui_config_button([[
			'label' => 'button_power_off',
			'name' => 'power-off',
			'onclick' => 'prefs.power_off()'
		]]);


	}

}

?>
