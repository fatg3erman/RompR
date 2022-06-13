<?php
create_body_tag('desktop');
?>
<div id="notifications"></div>

<div class="fullwidth containerbox" id="thetopbit">

	<!-- Play Buttons -->

	<div id="groonburger" class="fixed containerbox vertical controlbutton-left">
<?php
	uibits::main_play_buttons();
?>
	</div>
	<div class="fixed">
		<div id="albumcover" class="fixed">
			<img id="albumpicture" />
		</div>
	</div>
	<!-- <div class="expand"> -->
<div id="infobar" class="expand containerbox vertical">
	<div id="nowplaying-info-wrapper" class="infobarlayout noselection fixed containerbox">
		<div id="nowplaying-text-wrapper" class="expand">
			<div id="nowplaying" class="containerbox vertical-centre">
				<div id="nptext"></div>
			</div>
			<div id="nowplaying_icons" class="clearfix">
				<div id="playcount" class="topstats"></div>
				<?php
				uibits::ui_nowplaying_icons();
				?>
			</div>
		</div>

		<!-- Vertical small icons -->

		<div id="gronky" class="containerbox vertical fixed">
			<!-- <div class="containerbox vertical fixed" id="righthandtop"></div> -->
<?php
			print '<div class="expand topdrop"><i class="icon-floppy smallpluginicon clickicon"></i>';
?>
				<div class="top_drop_menu dropshadow rightmenu widemenu stayopen noscroll" id="plsaver">
<?php
					print uibits::ui_config_header([
						'label' => 'button_saveplaylist',
						'icon_size' => 'smallicon'
					]);
					print '<div class="containerbox vertical-centre">
						<div class="expand">
							<input class="enter clearbox" id="playlistname" type="text" size="200"/>
						</div>';
						print '<button class="fixed iconbutton savebutton"></button>
					</div>';
?>
				</div>
			</div>
<?php
			print '<div class="expand topdrop albumart-holder"><i title="'.language::gettext('button_albumart').'" class="icon-cd tooltip smallpluginicon clickicon open_albumart"></i></div>';
			print '<div class="expand topdrop"><i class="icon-cog-alt smallpluginicon clickicon tooltip choose_prefs" title="'.language::gettext('button_prefs').'"></i>';
?>
				<div class="top_drop_menu dropshadow rightmenu widemenu stayopen" id="configpanel">
				<?php
				prefspanel::make_prefs_panel();
				?>
				</div>
			</div>

			<div class="expand topdrop"><i class="icon-menu smallpluginicon clickicon"></i>
				<div class="top_drop_menu dropshadow rightmenu widemenu stayopen containerbox vertical" id="phacker">
					<?php
					print uibits::ui_config_header([
						'label' => 'label_playqueue',
						'icon_size' => 'smallicon'
					]);
					playlistpanel::make_playlist_panel();
					?>
				</div>
			</div>

		</div>

<?php

	// Volume Control

	include('player/utils/outputs.php');

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

	if (prefs::get_pref('hide_master_volume')) {
		print '<div id="snapcast-secondary" class="fixed containerbox infobarlayout"></div>';
	} else {
		print '<div id="volumebox" class="fixed">';
		print '<div class="infobarlayout bordered containerbox vertical">';
			print '<div title="'.language::gettext('button_volume').
				'" id="volumecontrol" class="tooltip expand containerbox vertical"><div id="volume" class="expand"></div></div>';
			if (count($outputdata) == 1) {
				// There's only one output so we'll treat it like a Mute button
				print '<div class="tooltip fixed" title="'.$outputdata[0]['outputname'].'" style="height:18px">';
				$f = ($outputdata[0]['outputname'] == "Mute") ? 0 : 1;
				$c = ($outputdata[0]['outputenabled'] == $f) ? 'icon-output' : 'icon-output-mute';
				print '<i id="mutebutton" onclick="player.controller.doMute()" class="'.$c.' outhack clickicon"></i>';
				print '</div>';
			} else {
				print '<div class="tooltip fixed" title="'.language::gettext('config_audiooutputs').'" style="height:18px">';
				print '<i id="mutebutton" onclick="layoutProcessor.toggleAudioOutpts()" class="icon-sliders outhack clickicon"></i>';
				print '</div>';
			}
		print '</div>';
		print '</div>';
	}
?>
	</div>

	<!-- Progress Bar -->

	<div id="theotherthing" class="fixed infobarnoheight containerbox vertical-centre">
			<div id="playposss" class="fixed timebox tboxl"></div>
			<div class="expand"><div id="progress"></div></div>
			<div id="tracktimess" class="fixed timebox tboxr clickicon"></div>
	</div>
</div>

</div>

<div id="bottompage" class="containerbox">

<div id="headerbar" class="noborder fixed">
	<div id="sourcescontrols">
<?php
print '<i title="'.language::gettext('button_local_music').'" class="icon-music tooltip topimg choosepanel" name="albumlist"></i>';
print '<i title="'.language::gettext('button_searchmusic').'" class="icon-search topimg tooltip choosepanel" name="searcher"></i>';
print '<i title="'.language::gettext('button_file_browser').'" class="icon-folder-open-empty tooltip topimg choosepanel" name="filelist"></i>';
print '<i title="'.language::gettext('button_internet_radio').'" class="icon-radio-tower tooltip topimg choosepanel" name="radiolist"></i>';
print '<i title="'.language::gettext('label_podcasts').'" class="icon-podcast-circled tooltip topimg choosepanel spinable" name="podcastslist"></i>';
print '<i title="'.language::gettext('label_audiobooks').'" class="icon-audiobook tooltip topimg choosepanel" name="audiobooklist"></i>';
print '<i title="'.language::gettext('button_loadplaylist').'" class="icon-doc-text tooltip topimg choosepanel spinable" name="playlistslist"></i>';
print '<i title="'.language::gettext('label_pluginplaylists').'" class="icon-wifi tooltip topimg choosepanel" name="pluginplaylistslist"></i>';
print '<i title="'.language::gettext('button_infopanel').'" class="icon-info-circled tooltip topimg choosepanel" name="infoholder"></i>';
print '<i title="'.language::gettext('button_history').'" class="icon-versions tooltip topimg choosepanel" name="historyholder"></i>';
print '<i title="'.language::gettext('button_plugins').'" class="icon-menu topimg tooltip choosepanel" name="specialplugins"></i>';
?>
	</div>
</div>

<div id="sources" class="column noborder fixed">

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

	<!-- Extra Plugins -->

	<div id="specialplugins" class="invisible noborder"></div>

	<!-- History -->

	<div id="historyholder" class="invisible noborder">
		<?php
		print uibits::ui_config_header([
			'label' => 'button_history',
			'icon_size' => 'smallicon'
		]);
		?>
		<div id="historypanel"></div>
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
		include("player/".prefs::get_pref('player_backend')."/search.php");
		?>
		<div id="searchresultholder" class="noborder selecotron is-albumlist"></div>
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

	<div id="podcastslist" class="helpfulholder noselection invisible">
		<?php
		print uibits::ui_config_header([
			'lefticon' => 'icon-menu clickicon fixed openmenu',
			'lefticon_name' => 'podcastbuttons',
			'label' => 'label_podcasts',
			'icon_size' => 'smallicon'
		]);
		include("includes/podcast_base.php");
		?>
	</div>

</div>

<!-- Info Panel -->

<div id="infopane" class="cmiddle noborder infowiki expand">

	<!-- Saved Playlists -->

	<div id="playlistslist" class="invisible">
		<?php
		print uibits::ui_config_header([
			'label' => 'button_loadplaylist',
			'icon_size' => 'smallicon'
		]);
		?>
		<div id="storedplaylists" class="helpfulholder noselection containerbox wrap is-albumlist"></div>
	</div>

	<!-- Smart Radio -->

	<div id="pluginplaylistslist" class="invisible noselection">
		<?php
		uibits::pluginplaylists_base(['class' => 'helpfulholder noselection containerbox wrap']);
		?>
	</div>

	<!-- Info Panel Proper -->

	<div id="infoholder" class="collectionpanel invisible">
	<div id="infopanecontrols">
		<div id="chooserbuttons">
<?php
		print '<i title="'.language::gettext('button_back').'" id="backbutton" class="icon-left-circled topimg tooltip button-disabled"></i>';
		print '<i title="'.language::gettext('button_forward').'" id="forwardbutton" class="icon-right-circled tooltip topimg button-disabled"></i>';
?>
		</div>
	</div>
<?php
	uibits::infopane_default_contents();
?>
</div>
<div id="pluginholder" class="collectionpanel invisible">
</div>
</div>

</div>

<!-- Floating Add tags Popup -->

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

