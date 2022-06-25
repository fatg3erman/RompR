<?php
create_body_tag('phone');
?>

<!-- Flowing Notifications Div -->
<div id="notifications"></div>

<!-- Wrapper for everything except the icon bar at the bottom -->
<div id="loadsawrappers">

<!-- Info Panel. This must be first, the CSS rules decree it -->

<div id="infopane" class="infowiki scroller mainpane invisible">
	<div class="fullwidth buttonbar noborder containerbox">
		<div id="chooserbuttons" class="noborder expand center topbox containerbox fullwidth headercontainer">
			<i id="choose_history" class="icon-versions topimg expand"></i>
			<i id="backbutton" class="icon-left-circled topimg button-disabled expand onlywide"></i>
			<i id="forwardbutton" class="icon-right-circled topimg button-disabled expand onlywide"></i>
		</div>
	</div>
	<div id="historyholder" class="fullwidth invisible">
		<?php
		print uibits::ui_config_header([
			'label' => 'button_history',
			'icon_size' => 'smallicon'
		]);
		?>
		<div id="historypanel"></div>
	</div>
<?php
	uibits::infopane_default_contents();
?>
</div>

<!-- Now Playing Area -->

<div id="infobar" class="mainpane invisible containerbox vertical">

	<div id="albumcover" class="fixed">
		<img id="albumpicture" />
	</div>

	<div id="cssisshit" class="containerbox vertical expand">

		<div id="nowplaying_icons" class="clearfix fixed">
			<?php
			uibits::ui_pre_nowplaying_icons();
			uibits::ui_nowplaying_icons();
			uibits::ui_post_nowplaying_icons();
			?>
		</div>

		<div id="nowplaying-text-buttons" class="expand containerbox vertical">

			<div id="nowplaying" class="expand containerbox vertical-centre">
				<div id="nptext" class="calculating">&nbsp;</div>
			</div>

			<div id="buttonholder" class="containerbox vertical fixed">
				<div id="buttons" class="fixed">
				<?php
				uibits::main_play_buttons();
				?>
				</div>
				<div id="progress" class="fixed"></div>
				<div id="playbackTime" class="fixed">
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Music Collection -->

<div id="albumlist" class="scroller mainpane invisible pright">
	<?php
	print uibits::ui_config_header([
		'lefticon' => 'icon-menu clickicon fixed openmenu',
		'lefticon_name' => 'collectionbuttons',
		'label' => 'button_local_music',
		'icon_size' => 'smallicon',
		'title_class' => 'is-coverable'
	]);
	uibits::collection_options_box();
	?>
	<div id="collection" class="noborder selecotron">
	</div>
</div>

<!-- Search Panel -->

<div id='searchpane' class="scroller mainpane invisible pright">
	<div id="search" class="noborder is-coverable">
	<?php
	print uibits::ui_config_header([
		'lefticon' => 'icon-menu clickicon fixed openmenu',
		'lefticon_name' => 'advsearchoptions',
		'label' => 'label_searchfor',
		'icon_size' => 'smallicon'
	]);
	$sh = new search_handler();
	$sh->create_search_panel();
	?>
	</div>
	<?php
	$sh->make_search_holders();
	?>
</div>

<!-- File Browser -->

<div id="filelist" class="scroller mainpane invisible pright">
	<?php
	print uibits::ui_config_header([
		'label' => 'button_file_browser',
		'icon_size' => 'smallicon',
		'title_class' => 'is-coverable'
	]);
	?>
	<div id="filecollection" class="noborder selecotron"></div>
</div>

<!-- Internet Radio -->

<div id="radiolist" class="scroller mainpane invisible pright">
	<?php
	print uibits::ui_config_header([
		'label' => 'button_internet_radio',
		'icon_size' => 'smallicon'
	]);
	$sp = glob("streamplugins/*.php");
	foreach($sp as $p) {
		include($p);
	}
	?>
</div>

<!-- Podcasts -->

<div id="podcastslist" class="scroller mainpane invisible pright">
	<?php
	print uibits::ui_config_header([
		'lefticon' => 'icon-menu clickicon fixed openmenu',
		'lefticon_name' => 'podcastbuttons',
		'label' => 'label_subbed_podcasts',
		'icon_size' => 'smallicon',
		'title_class' => 'is-coverable'
	]);
	include("includes/podcast_base.php");
	?>
</div>

<!-- Spoken Word -->

<div id="audiobooklist" class="scroller mainpane invisible pright">
	<?php
	print uibits::ui_config_header([
		'label' => 'label_audiobooks',
		'icon_size' => 'smallicon',
		'title_class' => 'is-coverable'
	]);
	?>
	<div id="audiobooks" class="noborder selecotron"></div>
</div>

<!-- Smart Radio -->

<div id="pluginplaylistholder" class="scroller mainpane invisible pright">
	<?php
	uibits::pluginplaylists_base([]);
	?>
</div>

<!-- Saved Playlists -->

<div id="playlistman" class="scroller mainpane invisible pright">
	<?php
	print uibits::ui_config_header([
		'label' => 'button_saveplaylist',
		'icon_size' => 'smallicon',
		'title_class' => 'is-coverable'
	]);
	?>
	<div class="containerbox vertical-centre is-coverable" style="margin-bottom:4px;"><div class="fixed">
	</div><div class="expand"><input class="enter clearbox" id="playlistname" type="text" size="200"/></div>
	<?php
	print '<button class="fixed iconbutton savebutton"></button>';
	?>
	</div>
	<?php
	print uibits::ui_config_header([
		'label' => 'button_loadplaylist',
		'icon_size' => 'smallicon',
		'title_class' => 'is-coverable'
	]);
	?>
		<div id="playlistslist">
			<div id="storedplaylists" class="is-albumlist"></div>
		</div>
</div>

<!-- Prefs Panel -->

<div id="prefsm" class="scroller mainpane invisible pright">
	<?php
	prefspanel::make_prefs_panel();
	?>
</div>

<!-- Play Queue This must be last and must not be .mainpane -->

<div id="playlistm" class="containerbox vertical">
	<?php
	playlistpanel::make_playlist_panel();
	?>
</div>

</div>

<!-- Bottom Screen Button Bar -->

<div id="headerbar" class="noborder fullwidth containerbox">
	<div id="sourcescontrols" class="expand center containerbox noborder">
		<div id="volumedropper" class="top_drop_menu rightmenu widemenu topshadow">
<?php
	include('player/utils/outputs.php');
	if (prefs::get_pref('hide_master_volume')) {

		print uibits::ui_config_header([
			'label' => 'button_volume',
			'title_class' => 'nohelp invisible',
			'id' => 'snapheader'
		]);

		print '<div class="pref" id="snapcastgroups"></div>';
		if (count($outputdata) > 1) {

			print uibits::ui_config_header([
				'label' => 'config_audiooutputs'
			]);

			print '<div class="pref">';
			printOutputCheckboxes();
			print '</div>';
		}
	} else {

		print uibits::ui_config_header([
			'label' => 'label_volume'
		]);

		print '<div id="volumecontrol" class="containerbox fullwidth menuitem">';
		print '<div id="volume" class="expand"></div>';
		if (count($outputdata) == 1) {
			$f = ($outputdata[0]['outputname'] == "Mute") ? 0 : 1;
			$c = ($outputdata[0]['outputenabled'] == $f) ? 'icon-output' : 'icon-output-mute';
			print '<i id="mutebutton" onclick="player.controller.doMute()" class="'.$c.' fixed inline-icon clickicon"></i>';
		}
		print '</div>';
		if (count($outputdata) > 1) {
			print uibits::ui_config_header([
				'label' => 'config_audiooutputs'
			]);
			print '<div class="pref">';
			printOutputCheckboxes();
			print '</div>';
		}
	}

	print uibits::ui_config_header([
		'label' => 'config_players',
		'title_class' => 'nohelp player-title'
	]);
	print '<div class="pref styledinputs" name="playerdefs"></div>';
	if (!prefs::get_pref('hide_master_volume')) {
			print uibits::ui_config_header([
				'main_icon' => 'icon-snapcast',
				'title_class' => 'nohelp invisible',
				'id' => 'snapheader'
			]);
			print '<div class="pref" id="snapcastgroups"></div>';
	}
?>

		</div>
		<div id="specialplugins" class="top_drop_menu rightmenu autohide topshadow">
			<div class="sptext"></div>
		</div>
		<div id="narrowscreenicons" class="top_drop_menu rightmenu autohide clearfix topshadow">
			<i class="noshrink icon-folder-open-empty topimg choosepanel tright" name="filelist"></i>
			<i class="noshrink choosepanel icon-doc-text topimg tright" name="playlistman"></i>
			<i class="noshrink icon-info-circled topimg choosepanel tright" name="infopane"></i>
			<i class="noshrink choosepanel icon-cog-alt topimg tright" name="prefsm"></i>
		</div>
		<i class="icon-no-response-playbutton topimg choosepanel expand" name="infobar"></i>
		<i class="icon-search topimg choosepanel expand" name="searchpane"></i>
		<i class="icon-music topimg choosepanel expand" name="albumlist"></i>
		<i class="choosepanel icon-audiobook topimg expand" name="audiobooklist"></i>
		<i class="icon-radio-tower topimg choosepanel expand" name="radiolist"></i>
		<i class="icon-podcast-circled topimg choosepanel expand spinable" name="podcastslist"></i>
		<i class="choosepanel icon-wifi topimg expand" name="pluginplaylistholder"></i>
		<i class="choosepanel onlywide icon-doc-text topimg expand spinable" name="playlistman"></i>
		<i class="icon-folder-open-empty onlywide topimg choosepanel expand" name="filelist"></i>
		<div id="pluginicons" class="onlywide containerbox expandabit">
		</div>
		<i class="icon-volume-up topimg expand topbarmenu" name="volumedropper"></i>
		<i class="icon-doc-text topimg choosepanel expand" name="playlistm"></i>
		<i class="onlywide icon-info-circled topimg choosepanel expand" name="infopane"></i>
		<i class="onlywide choosepanel icon-cog-alt topimg expand" name="prefsm"></i>
		<i class="icon-menu topimg ninety expand topbarmenu" name="specialplugins"></i>
		<i class="icon-menu topimg expand onlynarrow topbarmenu" name="narrowscreenicons"></i>
	</div>
</div>

<!-- Floating Add Tags Dropdown -->

<div id="tagadder" class="top_drop_menu dropmenu dropshadow">
	<?php
	print uibits::ui_config_header([
		'lefticon' => 'icon-tags',
		'label' => 'lastfm_addtags',
		'righticon' => 'icon-cancel-circled clickicon close-tagadder'
	])
	?>
	<div class="containerbox vertical-centre tagaddbox"></div>
</div>

<!-- Floating Add to Playlist dropdown -->

<div id="pladddropdown" class="top_drop_menu dropmenu dropshadow">
	<?php
	print uibits::ui_config_header([
		'lefticon' => 'icon-doc-text',
		'label' => 'button_addtoplaylist',
		'righticon' => 'icon-cancel-circled clickicon close-pladd'
	])
	?>
	<div id="addtoplaylistmenu" class="clearfix">
	</div>
</div>

<div id="bookmarkadddropdown" class="top_drop_menu dropmenu dropshadow">
<?php
print uibits::ui_config_header([
	'lefticon' => 'icon-bookmark',
	'label' => 'button_bookmarks',
	'righticon' => 'icon-cancel-circled clickicon close-bookmark'
])
?>
	<div id="bookmarkaddinfo"></div>
	<div class="containerbox vertical-centre">
		<div class="expand">
			<input type="text" autocomplete="off" class="enter" name="bookmarkname" placeholder="Bookmark Name" style="cursor: auto" />
		</div>
		<button class="fixed" style="margin-left: 8px" onclick="bookmarkAdder.add()">ADD</button>
	</div>
</div>

