<body class="desktop">
<div id="pset" class="invisible"></div>
<div id="pmaxset" class="invisible"></div>
<div id="pbgset" class="invisible"></div>
<div id="notifications"></div>

<div class="fullwidth containerbox" id="thetopbit">
	<div id="groonburger" class="fixed containerbox vertical controlbutton-left">
<?php
		print '<i title="'.language::gettext('button_previous').'" class="prev-button fixed icon-fast-backward clickicon controlbutton-small tooltip"></i>';
		print '<i title="'.language::gettext('button_play').'" class="play-button fixed icon-play-circled clickicon controlbutton-main tooltip"></i>';
		print '<i title="'.language::gettext('button_stop').'" class="stop-button fixed icon-stop-1 clickicon controlbutton-small tooltip"></i>';
		print '<i title="'.language::gettext('button_stopafter').'" class="stopafter-button fixed icon-to-end-1 clickicon controlbutton-small tooltip"></i>';
		print '<i title="'.language::gettext('button_next').'" class="next-button fixed icon-fast-forward clickicon controlbutton-small tooltip"></i>';
?>
	</div>
	<div class="fixed">
		<div id="albumcover" class="fixed">
			<img id="albumpicture" />
		</div>
	</div>
	<!-- <div class="expand"> -->
<div id="infobar" class="fixed containerbox vertical">
	<div id="patrickmoore" class="infobarlayout noselection fixed containerbox">
		<div id="firefoxisshitwrapper" class="expand">
			<div id="nowplaying">
				<div id="nptext"></div>
			</div>
			<div id="amontobin" class="clearfix">
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
					<div class="topdropmenu dropshadow leftmenu normalmenu useasfixed">
						<?php
						print '<div class="configtitle"><div class="textcentre expand"><b>'.language::gettext('button_addtoplaylist').'</b></div></div>';
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
						'" class="icon-heart npicon clickicon tooltip spinable" id="love"></i>';
					?>
				</div>
				<div id="playcount" class="topstats"></div>
				<div id="dbtags" class="invisible topstats">
				</div>
			</div>
		</div>
		<div id="gronky" class="containerbox vertical">
			<div class="containerbox vertical fixed" id="righthandtop"></div>
<?php
			print '<div class="fixed topdrop"><i class="icon-floppy smallpluginicon clickicon"></i>';
?>
				<div class="topdropmenu dropshadow rightmenu widemenu stayopen" id="plsaver">
<?php
					print '<div class="dropdown-container configtitle"><div class="textcentre expand"><b>'.language::gettext('button_saveplaylist').'</b></div></div>';
					print '<div class="containerbox dropdown-container">
						<div class="expand">
							<input class="enter clearbox" id="playlistname" type="text" size="200"/>
						</div>';
						print '<button class="fixed iconbutton savebutton"></button>
					</div>';
?>
				</div>
			</div>
<?php
			print '<div class="fixed topdrop"><i title="'.language::gettext('button_albumart').'" class="icon-cd tooltip smallpluginicon clickicon open_albumart"></i></div>';
			print '<div class="fixed topdrop"><i class="icon-cog-alt smallpluginicon clickicon tooltip" title="'.language::gettext('button_prefs').'"></i>';
?>
				<div class="topdropmenu dropshadow rightmenu widemenu stayopen" id="configpanel">
<?php
include ("includes/prefspanel.php");
?>
				</div>
			</div>

			<div class="fixed topdrop"><i class="icon-menu smallpluginicon clickicon"></i>
				<div class="topdropmenu dropshadow rightmenu widemenu stayopen" id="phacker">
				<div class="configtitle"><div class="textcentre expand"><b>Play Queue</b></div></div>
					<?php
					include("skins/playlist.php");
					?>
				</div>
			</div>

		</div>

<?php
	include('player/utils/outputs.php');

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

	if (prefs::$prefs['hide_master_volume']) {
		print '<div id="snapcast-secondary" class="fixed"></div>';
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
	<div id="theotherthing" class="fixed infobarnoheight containerbox dropdown-container">
			<div id="playposss" class="fixed timebox tboxl"></div>
			<div class="expand"><div id="progress"></div></div>
			<div id="tracktimess" class="fixed timebox tboxr clickicon"></div>
	</div>
</div>

</div>

<div id="bottompage" class="clearfix">

<div id="headerbar" class="noborder tleft">
	<div id="sourcescontrols">
<?php
print '<i title="'.language::gettext('button_local_music').'" class="icon-music tooltip topimg choosepanel" name="albumlist"></i>';
print '<i title="'.language::gettext('button_searchmusic').'" class="icon-search topimg tooltip choosepanel" name="searcher"></i>';
print '<i title="'.language::gettext('button_file_browser').'" class="icon-folder-open-empty tooltip topimg choosepanel" name="filelist"></i>';
print '<i title="'.language::gettext('button_internet_radio').'" class="icon-radio-tower tooltip topimg choosepanel" name="radiolist"></i>';
print '<i title="'.language::gettext('label_podcasts').'" class="icon-podcast-circled tooltip topimg choosepanel" name="podcastslist"></i>';
print '<i title="'.language::gettext('label_audiobooks').'" class="icon-audiobook tooltip topimg choosepanel" name="audiobooklist"></i>';
print '<i title="'.language::gettext('button_loadplaylist').'" class="icon-doc-text tooltip topimg choosepanel" name="playlistslist"></i>';
print '<i title="'.language::gettext('label_pluginplaylists').'" class="icon-wifi tooltip topimg choosepanel" name="pluginplaylistslist"></i>';
print '<i title="'.language::gettext('button_infopanel').'" class="icon-info-circled tooltip topimg choosepanel" name="infoholder"></i>';
print '<i title="'.language::gettext('button_history').'" class="icon-versions tooltip topimg choosepanel" name="historyholder"></i>';
print '<i title="'.language::gettext('button_plugins').'" class="icon-menu topimg tooltip choosepanel" name="specialplugins"></i>';
?>
	</div>
</div>

<div id="sources" class="column noborder tleft">

	<div id="albumlist" class="invisible noborder">
<?php
	print '<div class="dropdown-container configtitle">';
	print '<i title="'.language::gettext('button_collectioncontrols').'" class="icon-menu playlisticon clickicon tooltip fixed openmenu" name="collectionbuttons"></i>';
	print '<div class="textcentre expand"><b>'.language::gettext('button_local_music').'</b></div>';
	print '</div>';
	collectionButtons();
?>
	<div id="collection" class="noborder selecotron"></div>
	</div>

	<div id="audiobooklist" class="invisible noborder">
		<div class="dropdown-container configtitle">
<?php
		print '<div class="textcentre expand"><b>'.language::gettext('label_audiobooks').'</b></div>';
?>
		</div>
		<div id="audiobooks" class="noborder selecotron"></div>
	</div>

	<div id="specialplugins" class="invisible noborder"></div>

	<div id="historyholder" class="invisible noborder">
		<div class="dropdown-container configtitle">
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

	<div id="searcher" class="invisible noborder">
	<div class="dropdown-container configtitle">
	<?php
		print '<i title="'.language::gettext('setup_advanced').'" class="icon-menu playlisticon clickicon tooltip fixed openmenu" name="advsearchoptions"></i>';
		print '<div class="textcentre expand"><b>'.language::gettext('label_searchfor').'</b></div>';
	?>
	</div>
<?php
include("player/".prefs::$prefs['player_backend']."/search.php");
?>
	<div id="searchresultholder" class="noborder selecotron is-albumlist"></div>
	</div>

	<div id="filelist" class="invisible">
		<div class="dropdown-container configtitle">
<?php
		print '<div class="textcentre expand"><b>'.language::gettext('button_file_browser').'</b></div>';
?>
		</div>
	<div id="filecollection" class="noborder selecotron"></div>
	</div>

	<div id="radiolist" class="invisible">
		<div class="dropdown-container configtitle">
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

	<div id="podcastslist" class="helpfulholder noselection dropmenu invisible">
<?php
print '<div class="dropdown-container configtitle">';
print '<i class="icon-menu playlisticon clickicon tooltip fixed openmenu" name="podcastbuttons" title="'.language::gettext('label_podcastcontrols').'"></i>';
print '<div class="textcentre expand"><b>'.language::gettext('label_podcasts').'</b></div>';
print '</div>';
include("podcasts/podcasts.php");
?>
	</div>

</div>

<div id="infopane" class="cmiddle noborder infowiki tleft">
	<div id="playlistslist" class="invisible">
		<div class="dropdown-container configtitle">
<?php
		print '<div class="expand textcentre"><b>'.language::gettext('button_loadplaylist').'</b></div>';
?>
		</div>
		<div id="storedplaylists" class="helpfulholder noselection containerbox wrap is-albumlist"></div>
	</div>
	<div id="pluginplaylistslist" class="invisible padleft noselection">
<?php
print '<div class="containerbox configtitle">';
print '<div class="expand textcentre"><b>'.language::gettext('label_pluginplaylists').'</b></div>';
print '</div>';

if (prefs::$prefs['player_backend'] == "mopidy") {
	print '<div class="textcentre textunderline"><b>Music From Your Collection</b></div>';
}
?>
<div class="helpfulholder noselection containerbox wrap" id="pluginplaylists"></div>


<?php
if (prefs::$prefs['player_backend'] == "mopidy") {
	print '<div class="textcentre textunderline"><b>Music From Spotify</b></div>';
}
?>
<div class="helpfulholder noselection containerbox wrap" id="pluginplaylists_spotify"></div>

<?php
if (prefs::$prefs['player_backend'] == "mopidy") {
	print '<div class="textcentre textunderline"><b>Music From Everywhere</b></div>';
	print '<div id="radiodomains" class="pref"><b>Play From These Sources:</b></div>';
}
?>
<div class="helpfulholder noselection containerbox wrap" id="pluginplaylists_everywhere"></div>

<div class="clearfix containerbox vertical" id="pluginplaylists_crazy">
</div>
</div>

	<div id="infoholder" class="collectionpanel invisible">
	<div id="infopanecontrols">
		<div id="chooserbuttons">
<?php
		print '<i title="'.language::gettext('button_back').'" id="backbutton" class="icon-left-circled topimg tooltip button-disabled"></i>';
		print '<i title="'.language::gettext('button_forward').'" id="forwardbutton" class="icon-right-circled tooltip topimg button-disabled"></i>';
?>
		</div>
	</div>
	<div id="artistchooser" class="infotext noselection invisible"></div>
<?php
print '<div id="artistinformation" class="infotext noselection"><h2 class="infobanner" align="center">'.language::gettext('label_emptyinfo').'</h2></div>';
?>
<div id="albuminformation" class="infotext noselection"></div>
<div id="trackinformation" class="infotext"></div>
</div>
<div id="pluginholder" class="collectionpanel invisible">
</div>
</div>

</div>

<div id="tagadder" class="dropmenu dropshadow mobmenu">
	<div class="dropdown-container configtitle moveable" style="padding-top:4px"><div class="textcentre expand"><b>
<?php
print language::gettext("lastfm_addtags").'</b><i class="icon-cancel-circled clickicon playlisticonr tright" onclick="tagAdder.close()"></i></div></div>';
?>
	<div class="containerbox padright dropdown-container tagaddbox"></div>
</div>
