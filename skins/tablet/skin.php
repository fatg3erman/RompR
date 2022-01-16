<body class="mobile">
<div id="notifications"></div>
<div id="headerbar" class="noborder fullwidth containerbox">
	<div id="sourcescontrols" class="expand center containerbox noborder">
		<div id="volumedropper" class="top_drop_menu rightmenu widemenu">
<?php
	include('player/utils/outputs.php');
	if (prefs::$prefs['hide_master_volume']) {
			print '<div class="configtitle nohelp invisible" id="snapheader"><div class="textcentre expand"><b>'.language::gettext('label_volume').'</b></div></div>';
			print '<div class="pref" id="snapcastgroups"></div>';
			if (count($outputdata) > 1) {
				print '<div class="configtitle"><div class="textcentre expand"><b>'.language::gettext('config_audiooutputs').'</b></div></div>';
				print '<div class="pref">';
				printOutputCheckboxes();
				print '</div>';
			}
	} else {
			print '<div class="configtitle"><div class="textcentre expand"><b>'.language::gettext('label_volume').'</b></div></div>';
			print '<div id="volumecontrol" class="containerbox fullwidth menuitem">';
			print '<div id="volume" class="expand"></div>';
			if (count($outputdata) == 1) {
				$f = ($outputdata[0]['outputname'] == "Mute") ? 0 : 1;
				$c = ($outputdata[0]['outputenabled'] == $f) ? 'icon-output' : 'icon-output-mute';
				print '<i id="mutebutton" onclick="player.controller.doMute()" class="'.$c.' fixed podicon clickicon"></i>';
			}
			print '</div>';
			if (count($outputdata) > 1) {
				print '<div class="configtitle"><div class="textcentre expand"><b>'.language::gettext('config_audiooutputs').'</b></div></div>';
				print '<div class="pref">';
				printOutputCheckboxes();
				print '</div>';
			}
	}
			print '<div class="configtitle nohelp player-title"><div class="textcentre expand"><b>'.language::gettext('config_players').'</b></div></div>';
			print '<div class="pref styledinputs" name="playerdefs"></div>';
	if (!prefs::$prefs['hide_master_volume']) {
			print '<div class="configtitle nohelp invisible" id="snapheader"><div class="textcentre expand"><b>Snapcast</b></div></div>';
			print '<div class="pref" id="snapcastgroups"></div>';
	}
?>
		</div>
		<div id="specialplugins" class="top_drop_menu rightmenu autohide">
			<div class="sptext"></div>
		</div>
		<div id="narrowscreenicons" class="top_drop_menu rightmenu autohide clearfix">
			<i class="noshrink icon-folder-open-empty topimg choosepanel tright" name="filelist"></i>
			<i class="noshrink choosepanel icon-audiobook topimg tright" name="audiobooklist"></i>
			<i class="noshrink choosepanel icon-doc-text topimg tright" name="playlistman"></i>
			<i class="noshrink icon-info-circled topimg choosepanel tright" name="infopane"></i>
			<i class="noshrink choosepanel icon-cog-alt topimg tright" name="prefsm"></i>
		</div>
		<i class="icon-no-response-playbutton topimg choosepanel expand" name="infobar"></i>
		<i class="icon-music topimg choosepanel expand" name="albumlist"></i>
		<i class="icon-search topimg choosepanel expand" name="searchpane"></i>
		<i class="icon-folder-open-empty onlywide topimg choosepanel expand" name="filelist"></i>
		<i class="icon-radio-tower topimg choosepanel expand" name="radiolist"></i>
		<i class="icon-podcast-circled topimg choosepanel expand" name="podcastslist"></i>
		<i class="choosepanel onlywide icon-audiobook topimg expand" name="audiobooklist"></i>
		<i class="choosepanel onlywide icon-doc-text topimg expand" name="playlistman"></i>
		<i class="choosepanel icon-wifi topimg expand" name="pluginplaylistholder"></i>
		<div class="onlyverywide containerbox expandabit topbarplaycontrols">
			<i class="prev-button icon-fast-backward topimg expand"></i>
			<i class="play-button icon-play-circled topimg expand"></i>
			<i class="stop-button icon-stop-1 topimg expand"></i>
			<i class="stopafter-button icon-to-end-1 topimg expand"></i>
			<i class="next-button icon-fast-forward topimg expand"></i>
		</div>
		<i class="icon-volume-up topimg expand topbarmenu" name="volumedropper"></i>
		<i class="icon-doc-text topimg choosepanel expand" name="playlistm"></i>
		<i class="onlywide icon-info-circled topimg choosepanel expand" name="infopane"></i>
		<i class="onlywide choosepanel icon-cog-alt topimg expand" name="prefsm"></i>
		<i class="icon-menu topimg ninety expand topbarmenu" name="specialplugins"></i>
		<i class="icon-menu topimg expand onlynarrow topbarmenu" name="narrowscreenicons"></i>
	</div>
</div>

<div id="loadsawrappers">

<div id="infobar" class="mainpane invisible">
	<div id="toomanywrappers">
		<div id="albumcover"><img id="albumpicture" /></div>
		<div id="nowplaying_icons" class="clearfix">
			<div id="subscribe" class="invisible">
				<i class="icon-rss npicon clickicon"></i>
				<input type="hidden" id="nppodiput" value="" />
			</div>
			<div id="stars" class="invisible">
				<i id="ratingimage" class="icon-0-stars rating-icon-big"></i>
				<input type="hidden" value="-1" />
			</div>
			<div id="addtoplaylist" class="invisible" onclick="addToPlaylist.open()">
				<i class="icon-doc-text npicon clickicon"></i>
			</div>
			<div id="lastfm" class="invisible">
				<i class="icon-heart npicon clickicon" id="love"></i>
			</div>
			<div id="ptagadd" class="invisible">
				<i class="icon-plus npicon clickicon" onclick="tagAdder.show(event)"></i>
			</div>
			<div id="dbtags" class="invisible">
			</div>
		</div>
		<div id="nowplaying">
			<div id="nptext"></div>
		</div>
	</div>
	<div id="cssisshit">
		<div id="buttonholder" class="containerbox vertical">
			<div id="buttons" class="fixed">
<?php
				print '<i title="'.language::gettext('button_previous').
					'" class="prev-button icon-fast-backward clickicon controlbutton-small"></i>';
				print '<i title="'.language::gettext('button_play').
					'" class="play-button icon-play-circled shiftleft clickicon controlbutton"></i>';
				print '<i title="'.language::gettext('button_stop').
					'" class="stop-button icon-stop-1 shiftleft2 clickicon controlbutton-small"></i>';
				print '<i title="'.language::gettext('button_stopafter').
					'" class="stopafter-button icon-to-end-1 shiftleft3 clickicon controlbutton-small"></i>';
				print '<i title="'.language::gettext('button_next').
					'" class="next-button icon-fast-forward shiftleft4 clickicon controlbutton-small"></i>';
?>
			</div>
			<div id="progress" class="fixed"></div>
			<div id="playbackTime" class="fixed">
			</div>
		</div>
	</div>
</div>

<div id="albumlist" class="scroller mainpane invisible pright">
<?php
	print '<div class="vertical-centre configtitle">';
	print '<i title="'.language::gettext('button_collectioncontrols').'" class="icon-menu playlisticon clickicon tooltip fixed openmenu" name="collectionbuttons"></i>';
	print '<div class="textcentre expand"><b>'.language::gettext('button_local_music').'</b></div>';
	print '</div>';
	collectionButtons();
?>
	<div id="collection" class="noborder selecotron">
	</div>
</div>

<div id='searchpane' class="scroller mainpane invisible pright">
<div id="search" class="noborder">
<div class="vertical-centre configtitle">
<?php
	print '<i title="'.language::gettext('setup_advanced').'" class="icon-menu playlisticon clickicon tooltip fixed openmenu" name="advsearchoptions"></i>';
	print '<div class="textcentre expand"><b>'.language::gettext('label_searchfor').'</b></div>';
?>
</div>
<?php
include("player/".prefs::$prefs['player_backend']."/search.php");
?>
</div>
<div id="searchresultholder" class="selecotron is-albumlist"></div>
</div>

<div id="filelist" class="scroller mainpane invisible pright">
	<div class="vertical-centre configtitle">
<?php
	print '<div class="textcentre expand"><b>'.language::gettext('button_file_browser').'</b></div>';
?>
	</div>
	<div id="filecollection" class="noborder selecotron"></div>
</div>

<div id="infopane" class="infowiki scroller mainpane invisible">
	<div class="fullwidth buttonbar noborder containerbox">
		<div id="chooserbuttons" class="noborder expand center topbox containerbox fullwidth headercontainer">
			<i id="choose_history" class="icon-versions topimg expand"></i>
			<i id="backbutton" class="icon-left-circled topimg button-disabled expand onlywide"></i>
			<i id="forwardbutton" class="icon-right-circled topimg button-disabled expand onlywide"></i>
		</div>
	</div>
	<div id="artistchooser" class="infotext invisible"></div>
	<div id="historyholder" class="fullwidth invisible">
		<div class="vertical-centre configtitle">
			<div class="textcentre expand">
				<b>
	<?php
		print language::gettext('button_history');
	?>
				</b>
			</div>
		</div>
		<div id="historypanel"></div>
	</div>
<?php
	print '<div id="artistinformation" class="infotext"><h2 class="infobanner soangly" align="center">'.
		language::gettext('label_emptyinfo').'</h2></div>';
?>
	<div id="albuminformation" class="infotext"></div>
	<div id="trackinformation" class="infotext"></div>
</div>

<div id="radiolist" class="scroller mainpane invisible pright">
	<div class="vertical-centre configtitle">
<?php
print '<div class="expand textcentre"><b>'.language::gettext('button_internet_radio').'</b></div>';
?>
	</div>
<?php
$sp = glob("streamplugins/*.php");
foreach($sp as $p) {
include($p);
}
?>
</div>

<div id="podcastslist" class="scroller mainpane invisible pright">
<?php
print '<div class="vertical-centre configtitle">';
print '<i class="icon-menu playlisticon clickicon tooltip fixed openmenu" name="podcastbuttons" title="'.language::gettext('label_podcastcontrols').'"></i>';
print '<div class="textcentre expand"><b>'.language::gettext('label_podcasts').'</b></div>';
print '</div>';
include("includes/podcast_base.php");
?>
</div>

<div id="audiobooklist" class="scroller mainpane invisible pright">
	<div class="vertical-centre configtitle">
<?php
	print '<div class="textcentre expand"><b>'.language::gettext('label_audiobooks').'</b></div>';
?>
	</div>
	<div id="audiobooks" class="noborder selecotron"></div>
</div>

<?php
if ($use_smartradio) {
?>
<div id="pluginplaylistholder" class="containerbox vertical scroller mainpane invisible pright">
<?php
print '<div class="vertical-centre configtitle">';
print '<div class="expand textcentre"><b>'.language::gettext('label_pluginplaylists').'</b></div>';
print '</div>';
?>
<div class="pref">
<?php
if (prefs::$prefs['player_backend'] == "mopidy") {
	print '<div class="textcentre textunderline"><b>Music From Your Collection</b></div>';
}
?>
<div class="fullwidth" id="pluginplaylists"></div>

<?php
if (prefs::$prefs['player_backend'] == "mopidy") {
	print '<div class="textcentre textunderline"><b>Music From Spotify</b></div>';
}
?>
<div class="fullwidth" id="pluginplaylists_spotify"></div>

<?php
if (prefs::$prefs['player_backend'] == "mopidy") {
	print '<div class="textcentre textunderline"><b>Music From Everywhere</b></div>';
	print '<div id="radiodomains" class="pref" style="padding-left:8px"><b>Play From These Sources:</b></div>';
}
?>
<div class="fullwidth" id="pluginplaylists_everywhere"></div>
<div class="clearfix containerbox vertical" id="pluginplaylists_crazy"></div>
</div>
</div>
<?php
}
?>

<div id="playlistman" class="scroller mainpane invisible pright">
	<?php
		print '<div class="configtitle"><div class="textcentre expand"><b>'.language::gettext('button_saveplaylist').'</b></div></div>';
	?>
		<div class="containerbox vertical-centre"><div class="fixed padright">
		</div><div class="expand"><input class="enter clearbox" id="playlistname" type="text" size="200"/></div>
	<?php
			print '<button class="fixed iconbutton savebutton"></button>';
	?>
		</div>
		<div class="vertical-centre configtitle">
	<?php
		print '<div class="expand textcentre"><b>'.language::gettext('button_loadplaylist').'</b></div>';
	?>
		</div>
		<div class="pref">
			<div id="playlistslist">
				<div id="storedplaylists" class="is-albumlist"></div>
			</div>
		</div>
	</div>
<div id="prefsm" class="scroller mainpane invisible pright">
<?php
include("includes/prefspanel.php")
?>
</div>

<div id="playlistm">
<?php
include('skins/playlist.php');
?>
</div>

</div>

<div id="tagadder" class="dropmenu dropshadow mobmenu">
	<div class="vertical-centre configtitle moveable" style="padding-top:4px"><div class="textcentre expand"><b>
<?php
print language::gettext("lastfm_addtags").'</b><i class="icon-cancel-circled clickicon playlisticonr tright" onclick="tagAdder.close()"></i></div></div>';
?>
	<div class="containerbox padright vertical-centre tagaddbox"></div>
</div>

<div id="pladddropdown" class="top_drop_menu dropmenu dropshadow mobmenu">
	<?php
	print '<div class="configtitle moveable" style="padding-top:4px"><div class="textcentre expand"><b>'.language::gettext('button_addtoplaylist').'</b><i class="icon-cancel-circled clickicon playlisticonr tright" onclick="addToPlaylist.close()"></i></div></div>';
		?>
	<div id="addtoplaylistmenu" class="clearfix">
	</div>
</div>
