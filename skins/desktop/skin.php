<body class="desktop">
<div id="notifications"></div>

<div id="infobar" class="coloured containerbox">
	<div id="buttonbox" class="fixed">
		<div id="buttonholder" class="containerbox vertical bordered infobarlayout">
			<div id="buttons" class="fixed">
<?php
				print '<i title="'.language::gettext('button_previous').
					'" class="prev-button icon-fast-backward clickicon controlbutton-small tooltip"></i>';
				print '<i title="'.language::gettext('button_play').
					'" class="play-button icon-play-circled shiftleft clickicon controlbutton tooltip"></i>';
				print '<i title="'.language::gettext('button_stop').
					'" class="stop-button icon-stop-1 shiftleft2 clickicon controlbutton-small tooltip"></i>';
				print '<i title="'.language::gettext('button_stopafter').
					'" class="stopafter-button icon-to-end-1 shiftleft3 clickicon controlbutton-small tooltip"></i>';
				print '<i title="'.language::gettext('button_next').
					'" class="next-button icon-fast-forward shiftleft4 clickicon controlbutton-small tooltip"></i>';
?>
			</div>
			<div id="progress" class="fixed"></div>
			<div id="playbackTime" class="fixed">
			</div>
		</div>
	</div>

<?php
	include('player/utils/outputs.php');
	if (prefs::$prefs['hide_master_volume']) {
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
		if (prefs::$prefs['hide_master_volume']) {
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
				<div id="subscribe" class="invisible topstats">
					<?php
					print '<i title="'.language::gettext('button_subscribe').
						'" class="icon-rss npicon clickicon tooltip"></i>';
					?>
					<input type="hidden" id="nppodiput" value="" />
				</div>
				<div id="addtoplaylist" class="invisible topstats">
					<?php
					print '<i title="'.language::gettext('button_addtoplaylist').
						'" class="icon-doc-text npicon clickicon tooltip topdrop">';
					?>
					<div class="top_drop_menu dropshadow leftmenu normalmenu useasfixed">
						<?php
						print '<div class="configtitle vertical-centre"><div class="textcentre expand"><b>'.language::gettext('button_addtoplaylist').'</b></div></div>';
						?>
						<div id="addtoplaylistmenu" class="clearfix">
						</div>
					</div>
					</i>
				</div>
				<div id="stars" class="invisible topstats">
					<i id="ratingimage" class="icon-0-stars rating-icon-big"></i>
					<input type="hidden" value="-1" />
				</div>
				<div id="lastfm" class="invisible topstats">
					<?php
					print '<i title="'.language::gettext('button_love').
						'" class="icon-heart npicon clickicon tooltip" id="love"></i>';
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

<div id="sourcescontrols" class="noborder tleft containerbox headercontainer topbox">
<?php
print '<i title="'.language::gettext('button_local_music').'" class="icon-music tooltip topimg choosepanel expand" name="albumlist"></i>';
print '<i title="'.language::gettext('button_searchmusic').'" class="icon-search topimg tooltip choosepanel expand" name="searcher"></i>';
print '<i title="'.language::gettext('button_file_browser').'" class="icon-folder-open-empty tooltip topimg choosepanel expand" name="filelist"></i>';
print '<i title="'.language::gettext('button_internet_radio').'" class="icon-radio-tower tooltip topimg choosepanel expand" name="radiolist"></i>';
print '<i title="'.language::gettext('label_podcasts').'" class="icon-podcast-circled tooltip topimg choosepanel expand" name="podcastslist"></i>';
print '<i title="'.language::gettext('label_audiobooks').'" class="icon-audiobook tooltip topimg choosepanel expand" name="audiobooklist"></i>';
print '<i title="'.language::gettext('button_loadplaylist').'" class="icon-doc-text tooltip topimg choosepanel expand" name="playlistslist"></i>';
print '<i title="'.language::gettext('label_pluginplaylists').'" class="icon-wifi tooltip topimg choosepanel expand" name="pluginplaylistslist"></i>';
?>
<div class="expand"></div>
</div>

<div id="infopanecontrols" class="noborder tleft containerbox headercontainer topbox">
<?php
 print '<i title="'.language::gettext('button_togglesources').'" class="icon-angle-double-left tooltip topimg expandslightly background-left" id="expandleft"></i>';
 ?>

<div id="chooserbuttons" class="noborder expandalot center containerbox headercontainer">
<?php
print '<i class="icon-menu topimg tooltip topdrop expand" title="'.language::gettext('button_plugins').'">';
?>
<div class="top_drop_menu dropshadow leftmenu normalmenu">
	<div id="specialplugins" class="clearfix"></div>
</div>
</i>
<?php
print '<i class="icon-versions topimg tooltip topdrop expand" title="'.language::gettext('button_history').'">';
?>
<div class="top_drop_menu dropshadow leftmenu widemenu stayopen" id="hpscr">
	<div class="vertical-centre configtitle">
		<div class="textcentre expand">
			<b>
<?php
	print language::gettext('button_history');
?>
			</b>
		</div>
	</div>
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

<div id="playlistcontrols" class="noborder tleft containerbox headercontainer topbox righthandtop">
<div class="expand" id="rightspacer"></div>

<?php
print '<i title="'.language::gettext('button_albumart').'" class="icon-cd tooltip topimg open_albumart expand"></i>';
print '<i class="icon-cog-alt topimg tooltip topdrop expand choose_prefs" title="'.language::gettext('button_prefs').'">';
?>
<div class="top_drop_menu dropshadow rightmenu widemenu stayopen" id="configpanel">
<?php
include ("includes/prefspanel.php");
?>
</div>
</i>

<?php
print '<i class="icon-floppy topimg tooltip topdrop expand" title="'.language::gettext('button_saveplaylist').'">';
?>
<div class="top_drop_menu dropshadow rightmenu widemenu stayopen" id="plsaver">
<?php
print '<div class="vertical-centre configtitle"><div class="textcentre expand"><b>'.language::gettext('button_saveplaylist').'</b></div></div>';
print '<div class="containerbox vertical-centre"><div class="expand">
<input class="enter clearbox" id="playlistname" type="text" size="200"/></div>';
print '<button class="fixed iconbutton savebutton"></button></div>';
?>
</div>
</i>

</div>
</div>

<div id="bottompage" class="clearfix">

<div id="sources" class="column noborder tleft">

	<div id="albumlist" class="invisible noborder">
<?php
	print '<div class="vertical-centre configtitle">';
	print '<i title="'.language::gettext('button_collectioncontrols').'" class="icon-menu smallicon clickicon tooltip fixed openmenu" name="collectionbuttons"></i>';
	print '<div class="textcentre expand"><b>'.language::gettext('button_local_music').'</b></div>';
	print '</div>';
	collectionButtons();
?>
	<div id="collection" class="noborder selecotron"></div>
	</div>

	<div id="searcher" class="invisible noborder">
	<div class="vertical-centre configtitle">
	<?php
		print '<i title="'.language::gettext('setup_advanced').'" class="icon-menu smallicon clickicon tooltip fixed openmenu" name="advsearchoptions"></i>';
		print '<div class="textcentre expand"><b>'.language::gettext('label_searchfor').'</b></div>';
	?>
	</div>
<?php
include("player/".prefs::$prefs['player_backend']."/search.php");
?>
	<div id="searchresultholder" class="noborder selecotron is-albumlist"></div>
	</div>

	<div id="filelist" class="invisible">
		<div class="vertical-centre configtitle">
<?php
		print '<div class="textcentre expand"><b>'.language::gettext('button_file_browser').'</b></div>';
?>
		</div>
	<div id="filecollection" class="noborder selecotron"></div>
	</div>

	<div id="radiolist" class="invisible">
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
	<div id="podcastslist" class="invisible selecotron">
<?php
print '<div class="vertical-centre configtitle">';
print '<i class="icon-menu smallicon clickicon tooltip fixed openmenu" name="podcastbuttons" title="'.language::gettext('label_podcastcontrols').'"></i>';
print '<div class="textcentre expand"><b>'.language::gettext('label_podcasts').'</b></div>';
print '</div>';
include("includes/podcast_base.php");
?>
	</div>
	<div id="audiobooklist" class="invisible noborder">
		<div class="vertical-centre configtitle">
<?php
		print '<div class="textcentre expand"><b>'.language::gettext('label_audiobooks').'</b></div>';
?>
		</div>
		<div id="audiobooks" class="noborder selecotron"></div>
	</div>
	<div id="playlistslist" class="invisible">
		<div class="vertical-centre configtitle">
<?php
		print '<div class="expand textcentre"><b>'.language::gettext('button_loadplaylist').'</b></div>';
?>
		</div>
		<div id="storedplaylists" class="noborder selecotron is-albumlist"></div>
	</div>

	<div id="pluginplaylistslist" class="invisible noselection">
<?php
print '<div class="vertical-centre configtitle">';
print '<div class="expand textcentre"><b>'.language::gettext('label_pluginplaylists').'</b></div>';
print '</div>';

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

<div id="infopane" class="cmiddle noborder infowiki tleft">
	<div id="artistchooser" class="infotext noselection invisible"></div>
<?php
print '<div id="artistinformation" class="infotext noselection"><h2 class="infobanner soangly" align="center">'.language::gettext('label_emptyinfo').'</h2></div>';
?>
<div id="albuminformation" class="infotext noselection"></div>
<div id="trackinformation" class="infotext"></div>
</div>

<div id="playlist" class="column noborder tright containerbox vertical">
<?php
include("skins/playlist.php");
?>
</div>
</div>
<div id="tagadder" class="dropmenu dropshadow mobmenu">
	<div class="vertical-centre configtitle moveable"><div class="textcentre expand"><b>
<?php
print language::gettext("lastfm_addtags").'</b></div><i class="icon-cancel-circled clickicon smallicon tright" onclick="tagAdder.close()"></i></div>';
?>
	<div class="containerbox vertical-centre tagaddbox"></div>
</div>
