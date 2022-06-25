<?php
create_body_tag('desktop');
?>
<div id="notifications"></div>

<div id="infobar" class="coloured containerbox">

	<!-- Play control buttons and playback time -->

	<div id="buttonbox" class="fixed">
		<div id="buttonholder" class="containerbox vertical bordered infobarlayout">
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

	<!-- Volume Controls -->

<?php
	include('player/utils/outputs.php');
	if (prefs::get_pref('hide_master_volume')) {
		print '<div id="snapcast-secondary" class="fixed containerbox bordered infobarlayout"></div>';
	} else {
		print '<div id="volumebox" class="fixed">';
		print '<div class="infobarlayout bordered containerbox vertical">';
			print '<div title="'.language::gettext('button_volume').
				'" id="volumecontrol" class="tooltip expand containerbox vertical"><div id="volume" class="expand"></div></div>';
			if (count($outputdata) == 1) {
				// There's only one output so we'll treat it like a Mute button
				print '<div class="tooltip fixed" title="'.$outputdata[0]['outputname'].'" style="height:14px">';
				$f = ($outputdata[0]['outputname'] == "Mute") ? 0 : 1;
				$c = ($outputdata[0]['outputenabled'] == $f) ? 'icon-output' : 'icon-output-mute';
				print '<i id="mutebutton" onclick="player.controller.doMute()" class="'.$c.' outhack clickicon"></i>';
				print '</div>';
			} else {
				print '<div class="tooltip fixed" title="'.language::gettext('config_audiooutputs').'" style="height:14px">';
				print '<i id="mutebutton" onclick="layoutProcessor.toggleAudioOutpts()" class="icon-sliders outhack clickicon"></i>';
				print '</div>';
			}
		print '</div>';
		print '</div>';
	}

	if (count($outputdata) > 1) {
		if (prefs::get_pref('hide_master_volume')) {
			print '<div id="outputbox" class="fixed">';
		} else {
			print '<div id="outputbox" class="fixed" style="display:none">';
		}
		print '<div class="infobarlayout bordered">';
		print '<div style="padding:4px">';
		printOutputCheckboxes();
		print '</div>';
		print '</div>';
		print '</div>';
	}

?>
	<div id="nowplaying-info-wrapper" class="infobarlayout bordered noselection expand containerbox">
		<div id="albumcover" class="fixed">
			<img id="albumpicture" />
		</div>
		<div id="nowplaying-text-wrapper" class="expand">
			<div id="nowplaying" class="containerbox vertical-centre">
				<div id="nptext"></div>
			</div>
			<div id="nowplaying_icons" class="clearfix">
				<?php
				uibits::ui_pre_nowplaying_icons();
				uibits::ui_nowplaying_icons();
				uibits::ui_post_nowplaying_icons();
				?>
			</div>
		</div>
	</div>
</div>

<div id="headerbar" class="noborder fullwidth containerbox">

<div id="sourcescontrols" class="noborder fixed containerbox headercontainer topbox">
<?php
print '<i title="'.language::gettext('button_local_music').'" class="icon-music tooltip topimg choosepanel expand" name="albumlist"></i>';
print '<i title="'.language::gettext('button_searchmusic').'" class="icon-search topimg tooltip choosepanel expand" name="searcher"></i>';
print '<i title="'.language::gettext('button_file_browser').'" class="icon-folder-open-empty tooltip topimg choosepanel expand" name="filelist"></i>';
print '<i title="'.language::gettext('button_internet_radio').'" class="icon-radio-tower tooltip topimg choosepanel expand" name="radiolist"></i>';
print '<i title="'.language::gettext('label_podcasts').'" class="icon-podcast-circled tooltip topimg choosepanel expand spinable" name="podcastslist"></i>';
print '<i title="'.language::gettext('label_audiobooks').'" class="icon-audiobook tooltip topimg choosepanel expand" name="audiobooklist"></i>';
print '<i title="'.language::gettext('button_loadplaylist').'" class="icon-doc-text tooltip topimg choosepanel expand spinable" name="playlistslist"></i>';
print '<i title="'.language::gettext('label_pluginplaylists').'" class="icon-wifi tooltip topimg choosepanel expand" name="pluginplaylistslist"></i>';
?>
<div class="expand"></div>
</div>

<div id="infopanecontrols" class="noborder expand containerbox headercontainer topbox">
<?php
 print '<i title="'.language::gettext('button_togglesources').'" class="icon-angle-double-left tooltip topimg expandslightly background-left" id="expandleft"></i>';
 ?>

<div id="chooserbuttons" class="noborder expandalot center containerbox headercontainer">
<?php
print '<i class="icon-menu topimg tooltip topdrop expand" title="'.language::gettext('button_plugins').'">';
?>
<div class="top_drop_menu dropshadow leftmenu normalmenu noscroll">
	<div id="specialplugins" class="clearfix"></div>
</div>
</i>
<?php
print '<i class="icon-versions topimg tooltip topdrop expand" title="'.language::gettext('button_history').'">';
?>
<div class="top_drop_menu dropshadow leftmenu widemenu stayopen" id="hpscr">
	<?php
	print uibits::ui_config_header([
		'label' => 'button_history',
		'icon_size' => 'smallicon'
	]);
	?>
	<div id="historypanel" class="clearfix"></div>
</div>
</i>

<?php
print '<i title="'.language::gettext('button_back').'" id="backbutton" class="icon-left-circled topimg tooltip button-disabled expand"></i>';
print '<i title="'.language::gettext('button_forward').'" id="forwardbutton" class="icon-right-circled tooltip topimg button-disabled expand"></i>';
?>
</div>

<?php
print '<i class="icon-angle-double-right tooltip topimg expandslightly background-right" title="'.language::gettext('button_toggleplaylist').'" id="expandright"></i>';
?>
</div>

<div id="playlistcontrols" class="noborder fixed containerbox headercontainer topbox righthandtop">
<div class="expand" id="rightspacer"></div>

<?php
print '<i title="'.language::gettext('button_albumart').'" class="icon-cd tooltip topimg open_albumart expand albumart-holder"></i>';
print '<i class="icon-cog-alt topimg tooltip topdrop expand choose_prefs" title="'.language::gettext('button_prefs').'">';
?>
<div class="top_drop_menu dropshadow rightmenu widemenu stayopen" id="configpanel">
<?php
prefspanel::make_prefs_panel();
?>
</div>
</i>

<?php
print '<i class="icon-floppy topimg tooltip topdrop expand" title="'.language::gettext('button_saveplaylist').'">';
?>
<div class="top_drop_menu dropshadow rightmenu widemenu stayopen noscroll" id="plsaver">
<?php
print uibits::ui_config_header([
	'label' => 'button_saveplaylist',
	'icon_size' => 'smallicon'
]);
print '<div class="containerbox vertical-centre"><div class="expand">
<input class="enter clearbox" id="playlistname" type="text" size="200"/></div>';
print '<button class="fixed iconbutton savebutton"></button></div>';
?>
</div>
</i>

</div>
</div>

<!-- Bottom Half of the Page  -->

<div id="bottompage" class="containerbox">

<div id="sources" class="column noborder fixed resizable">

	<!-- Music Collection -->

	<div id="albumlist" class="invisible noborder">
		<?php
		print uibits::ui_config_header([
			'lefticon' => 'icon-menu clickicon fixed openmenu',
			'lefticon_name' => 'collectionbuttons',
			'label' => 'button_local_music',
			'icon_size' => 'smallicon'
		]);
		uibits::collection_options_box();
		?>
		<div id="collection" class="noborder selecotron"></div>
	</div>

	<!-- Search Panel -->

	<div id="searcher" class="invisible noborder">
		<?php
		print uibits::ui_config_header([
			'lefticon' => 'icon-menu clickicon fixed openmenu',
			'lefticon_name' => 'advsearchoptions',
			'label' => 'label_searchfor',
			'icon_size' => 'smallicon'
		]);
		$sh = new search_handler();
		$sh->create_search_panel();
		$sh->make_search_holders();
		?>
	</div>

	<!-- File Browser -->

	<div id="filelist" class="invisible">
		<?php
		print uibits::ui_config_header([
			'label' => 'button_file_browser',
			'icon_size' => 'smallicon'
		]);
		?>
		<div id="filecollection" class="noborder selecotron"></div>
	</div>

	<!-- Internet Radio -->

	<div id="radiolist" class="invisible">
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

	<div id="podcastslist" class="invisible selecotron">
		<?php
		print uibits::ui_config_header([
			'lefticon' => 'icon-menu clickicon fixed openmenu',
			'lefticon_name' => 'podcastbuttons',
			'label' => 'label_subbed_podcasts',
			'icon_size' => 'smallicon'
		]);
		include("includes/podcast_base.php");
		?>
	</div>

	<!-- Spoken Word -->

	<div id="audiobooklist" class="invisible noborder">
		<?php
		print uibits::ui_config_header([
			'label' => 'label_audiobooks',
			'icon_size' => 'smallicon'
		]);
		?>
		<div id="audiobooks" class="noborder selecotron"></div>
	</div>

	<!-- Saved Playlists -->

	<div id="playlistslist" class="invisible">
		<?php
		print uibits::ui_config_header([
			'label' => 'button_loadplaylist',
			'icon_size' => 'smallicon'
		]);
		?>
		<div id="storedplaylists" class="noborder selecotron is-albumlist"></div>
	</div>

	<!-- Smart radio -->

	<div id="pluginplaylistslist" class="invisible noselection">
		<?php
		uibits::pluginplaylists_base([]);
		?>
	</div>
</div>

<!-- Info Panel -->

<div id="infopane" class="cmiddle noborder infowiki expand">
	<?php
	uibits::infopane_default_contents();
	?>
</div>

<!-- Play Queue -->

<div id="playlist" class="column noborder fixed containerbox vertical resizable">
	<?php
	playlistpanel::make_playlist_panel();
	?>
</div>

</div>

<!-- Floating Add Tags Popup -->

<div id="tagadder" class="dropmenu dropshadow mobmenu widemenu">
<?php
print uibits::ui_config_header([
	'lefticon' => 'icon-tags',
	'label' => 'lastfm_addtags',
	'title_class' => 'moveable',
	'icon_size' => 'smallicon',
	'righticon' => 'icon-cancel-circled clickicon close-tagadder'
])
?>
	<div class="containerbox vertical-centre tagaddbox"></div>
</div>

<div id="pladddropdown" class="dropmenu dropshadow mobmenu widemenu">
<?php
print uibits::ui_config_header([
	'lefticon' => 'icon-doc-text',
	'label' => 'button_addtoplaylist',
	'title_class' => 'moveable',
	'icon_size' => 'smallicon',
	'righticon' => 'icon-cancel-circled clickicon close-pladd'
])
?>
	<div id="addtoplaylistmenu" class="clearfix"></div>
</div>

<div id="bookmarkadddropdown" class="dropmenu dropshadow mobmenu widemenu">
<?php
print uibits::ui_config_header([
	'lefticon' => 'icon-bookmark',
	'label' => 'button_bookmarks',
	'title_class' => 'moveable',
	'icon_size' => 'smallicon',
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
