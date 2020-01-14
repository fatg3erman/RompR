<body class="desktop">
<div id="pset" class="invisible"></div>
<div id="pmaxset" class="invisible"></div>
<div id="pbgset" class="invisible"></div>
<div id="notifications"></div>

<div id="infobar" class="coloured containerbox">
	<div id="buttonbox" class="fixed">
		<div id="buttonholder" class="containerbox vertical bordered infobarlayout">
			<div id="buttons" class="fixed">
<?php
				print '<i title="'.get_int_text('button_previous').
					'" class="prev-button icon-fast-backward clickicon controlbutton-small tooltip"></i>';
				print '<i title="'.get_int_text('button_play').
					'" class="play-button icon-play-circled shiftleft clickicon controlbutton tooltip"></i>';
				print '<i title="'.get_int_text('button_stop').
					'" class="stop-button icon-stop-1 shiftleft2 clickicon controlbutton-small tooltip"></i>';
				print '<i title="'.get_int_text('button_stopafter').
					'" class="stopafter-button icon-to-end-1 shiftleft3 clickicon controlbutton-small tooltip"></i>';
				print '<i title="'.get_int_text('button_next').
					'" class="next-button icon-fast-forward shiftleft4 clickicon controlbutton-small tooltip"></i>';
?>
			</div>
			<div id="progress" class="fixed"></div>
			<div id="playbackTime" class="fixed">
			</div>
		</div>
	</div>

	<div id="volumebox" class="fixed">
		<div class="infobarlayout bordered containerbox vertical">
<?php
			print '<div title="'.get_int_text('button_volume').
				'" id="volumecontrol" class="tooltip expand containerbox vertical"><div id="volume" class="expand"></div></div>';
			include('player/mpd/outputs.php');
			if (count($outputdata) == 1) {
				// There's only one output so we'll treat it like a Mute button
				print '<div class="tooltip fixed" title="'.$outputdata[0]['outputname'].'" style="height:14px">';
				$f = ($outputdata[0]['outputname'] == "Mute") ? 0 : 1;
				$c = ($outputdata[0]['outputenabled'] == $f) ? 'icon-output' : 'icon-output-mute';
				print '<i id="mutebutton" onclick="player.controller.doMute()" class="'.$c.' outhack clickicon"></i>';
				print '</div>';
			} else {
				print '<div class="tooltip fixed" title="'.get_int_text('config_audiooutputs').'" style="height:14px">';
				print '<i id="mutebutton" onclick="layoutProcessor.toggleAudioOutpts()" class="icon-sliders outhack clickicon"></i>';
				print '</div>';
			}

?>
		</div>
	</div>

<?php
	if (count($outputdata) > 1) {
		print '<div id="outputbox" class="fixed" style="display:none">';
		print '<div class="infobarlayout bordered">';
		print '<div style="padding:4px">';
		printOutputCheckboxes();
		print '</div>';
		print '</div>';
		print '</div>';
	}

?>
	<div id="patrickmoore" class="infobarlayout bordered noselection expand containerbox">
		<div id="albumcover" class="fixed">
			<img id="albumpicture" />
		</div>
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
				<div id="addtoplaylist" class="invisible topstats">
					<?php
					print '<i title="'.get_int_text('button_addtoplaylist').
						'" class="icon-doc-text npicon clickicon tooltip topdrop">';
					?>
					<div class="topdropmenu dropshadow leftmenu normalmenu useasfixed">
						<?php
						print '<div class="configtitle dropdown-container"><div class="textcentre expand"><b>'.get_int_text('button_addtoplaylist').'</b></div></div>';
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
					print '<i title="'.get_int_text('button_love').
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
print '<i title="'.get_int_text('button_local_music').'" class="icon-music tooltip topimg choosepanel expand" name="albumlist"></i>';
print '<i title="'.get_int_text('button_searchmusic').'" class="icon-search topimg tooltip choosepanel expand" name="searcher"></i>';
print '<i title="'.get_int_text('button_file_browser').'" class="icon-folder-open-empty tooltip topimg choosepanel expand" name="filelist"></i>';
print '<i title="'.get_int_text('button_internet_radio').'" class="icon-radio-tower tooltip topimg choosepanel expand" name="radiolist"></i>';
print '<i title="'.get_int_text('label_podcasts').'" class="icon-podcast-circled tooltip topimg choosepanel expand" name="podcastslist"></i>';
print '<i title="'.get_int_text('label_audiobooks').'" class="icon-audiobook tooltip topimg choosepanel expand" name="audiobooklist"></i>';
print '<i title="'.get_int_text('button_loadplaylist').'" class="icon-doc-text tooltip topimg choosepanel expand" name="playlistslist"></i>';
print '<i title="'.get_int_text('label_pluginplaylists').'" class="icon-wifi tooltip topimg choosepanel expand" name="pluginplaylistslist"></i>';
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

<?php
print '<i class="icon-angle-double-right tooltip topimg expandslightly background-right" title="'.get_int_text('button_toggleplaylist').'" id="expandright"></i>';
?>
</div>

<div id="playlistcontrols" class="noborder tleft containerbox headercontainer topbox righthandtop">
<div class="expand" id="rightspacer"></div>

<?php
print '<i title="'.get_int_text('button_albumart').'" class="icon-cd tooltip topimg open_albumart expand"></i>';
print '<i class="icon-cog-alt topimg tooltip topdrop expand" title="'.get_int_text('button_prefs').'">';
?>
<div class="topdropmenu dropshadow rightmenu widemenu stayopen" id="configpanel">
<?php
include ("includes/prefspanel.php");
?>
</div>
</i>

<?php
print '<i class="icon-floppy topimg tooltip topdrop expand" title="'.get_int_text('button_saveplaylist').'">';
?>
<div class="topdropmenu dropshadow rightmenu widemenu stayopen" id="plsaver">
<?php
print '<div class="dropdown-container configtitle"><div class="textcentre expand"><b>'.get_int_text('button_saveplaylist').'</b></div></div>';
print '<div class="containerbox dropdown-container"><div class="expand">
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
	print '<div class="dropdown-container configtitle">';
	print '<i onclick="toggleCollectionButtons()" title="'.get_int_text('button_collectioncontrols').'" class="icon-menu playlisticon clickicon tooltip fixed"></i>';
	print '<div class="textcentre expand"><b>'.get_int_text('button_local_music').'</b></div>';
	print '</div>';
	collectionButtons();
?>
	<div id="collection" class="noborder selecotron"></div>
	</div>

	<div id="searcher" class="invisible noborder">
	<div class="dropdown-container configtitle">
	<?php
		print '<i onclick="toggleSearchButtons()" title="Advanced Search Options" class="icon-menu playlisticon clickicon tooltip fixed"></i>';
		print '<div class="textcentre expand"><b>'.get_int_text('label_searchfor').'</b></div>';
	?>
	</div>
<?php
include("player/".$prefs['player_backend']."/search.php");
?>
	<div id="searchresultholder" class="noborder selecotron"></div>
	</div>

	<div id="filelist" class="invisible">
		<div class="dropdown-container configtitle">
<?php
		print '<div class="textcentre expand"><b>'.get_int_text('button_file_browser').'</b></div>';
?>
		</div>
	<div id="filecollection" class="noborder selecotron"></div>
	</div>

	<div id="radiolist" class="invisible">
		<div class="dropdown-container configtitle">
<?php
	print '<div class="expand textcentre"><b>'.get_int_text('button_internet_radio').'</b></div>';
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
print '<div class="dropdown-container configtitle">';
print '<i onclick="podcasts.toggleButtons()" class="icon-menu playlisticon clickicon tooltip fixed" title="'.get_int_text('label_podcastcontrols').'"></i>';
print '<div class="textcentre expand"><b>'.get_int_text('label_podcasts').'</b></div>';
print '</div>';
include("podcasts/podcasts.php");
?>
	</div>
	<div id="audiobooklist" class="invisible noborder">
		<div class="dropdown-container configtitle">
<?php
		print '<div class="textcentre expand"><b>'.get_int_text('label_audiobooks').'</b></div>';
?>
		</div>
		<div id="audiobooks" class="noborder selecotron"></div>
	</div>
	<div id="playlistslist" class="invisible">
		<div class="dropdown-container configtitle">
<?php
		print '<div class="expand textcentre"><b>'.get_int_text('button_loadplaylist').'</b></div>';
?>
		</div>
		<div id="storedplaylists" class="noborder selecotron is-albumlist"></div>
	</div>

	<div id="pluginplaylistslist" class="invisible noselection">
<?php
print '<div class="dropdown-container configtitle">';
print '<div class="expand textcentre"><b>'.get_int_text('label_pluginplaylists').'</b></div>';
print '</div>';

if ($prefs['player_backend'] == "mopidy") {
	print '<div class="textcentre textunderline"><b>Music From Your Collection</b></div>';
}
?>
<div class="fullwidth padleft" id="pluginplaylists"></div>


<?php
if ($prefs['player_backend'] == "mopidy") {
	print '<div class="textcentre textunderline"><b>Music From Spotify</b></div>';
}
?>
<div class="fullwidth" id="pluginplaylists_spotify"></div>

<?php
if ($prefs['player_backend'] == "mopidy") {
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
print '<div id="artistinformation" class="infotext noselection"><h2 align="center">'.get_int_text('label_emptyinfo').'</h2></div>';
?>
<div id="albuminformation" class="infotext noselection"></div>
<div id="trackinformation" class="infotext"></div>
</div>

<div id="playlist" class="column noborder tright">
<?php
include("skins/playlist.php");
?>
</div>
</div>
<div id="tagadder" class="dropmenu dropshadow mobmenu">
	<div class="dropdown-container configtitle moveable" style="padding-top:4px"><div class="textcentre expand"><b>
<?php
print get_int_text("lastfm_addtags").'</b><i class="icon-cancel-circled clickicon playlisticonr tright" onclick="tagAdder.close()"></i></div></div>';
?>
	<div class="containerbox padright dropdown-container tagaddbox"></div>
</div>
