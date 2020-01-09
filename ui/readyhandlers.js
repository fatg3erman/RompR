// We need a way to detect when the album image has finished *rendering*
// - on mobile devices this can be some time after the image has loaded
// and that fucks up the biggerizing of the nowplaying text.
// This method seems to work, called from the albumpicture's onload event
// and is less clunky than just using some random setTimeout
function rendered() {
	debug.debug('ALBUMPICTURE', 'Rendered');
	$('#albumpicture').fadeIn('fast');
	$('#albumpicture').removeClass('clickicon').addClass('clickicon').off('click').on('click', infobar.albumImage.displayOriginalImage);
	uiHelper.adjustLayout();
}

function startRender() {
	debug.debug('ALBUMPICTURE', 'Start Render');
	requestAnimationFrame(rendered);
}

function albumImageLoaded() {
	debug.debug('ALBUMPICTURE', 'Load event fired');
	requestAnimationFrame(startRender);
}

function inputFIleChanged() {
	var filenames = $.map($(this).prop('files'), function(val) {
		return val.name.replace(/.*(\/|\\)/, '')
	});
	if (filenames.length > 3) {
		$(this).next().html(filenames.length + ' files selected');
	} else {
		$(this).next().html(filenames.join('<br />'));
	}
	$(this).parent().next('input[type="button"]').fadeIn('fast');
}

function filterSpecialChars() {
	this.value = this.value.replace(/[\*&\+\s<>\[\]:;,\.\(\)]/g, '')
}

function showTagRemover() {
	$(this).children('i').show();
}

function hideTagRemover() {
	$(this).children('i').hide();
}

function closeMenus() {
	$('.albumbitsmenu').remove();
}

function setup_lastfm() {
	lastfm.wrangle().then(startBackgroundInitTasks.doNextTask);
}

function connect_to_player() {
	player.controller.initialise().then(startBackgroundInitTasks.doNextTask);
}

function load_playlists() {
	player.controller.reloadPlaylists().then(startBackgroundInitTasks.doNextTask)
}

function start_userinterface() {
	startBackgroundInitTasks.readytogo = true;
	uiHelper.adjustLayout();
	startBackgroundInitTasks.doNextTask();
}

function open_discoverator() {
	if (prefs.auto_discovembobulate) {
		pluginManager.autoOpen(language.gettext('button_infoyou'));
	}
	startBackgroundInitTasks.doNextTask();
}

async function refresh_podcasts() {
	// We want to wait until podcasts have been refreshed before we sync lastfm playcounts,
	// because the sync might mark some pdcast episodes as listened
	startBackgroundInitTasks.doNextTask();
	await new Promise(t => setTimeout(t, 15000));
	podcasts.checkRefresh().then(syncLastFMPlaycounts.start);
}

function clean_backend_cache() {
	debug.mark("INIT","Starting Backend Cache Clean");
	collectionHelper.disableCollectionUpdates();
	$.get('utils/cleancache.php', function() {
		debug.mark("INIT","Cache Has Been Cleaned");
		collectionHelper.enableCollectionUpdates();
		setTimeout(clean_backend_cache, 86400000);
		startBackgroundInitTasks.doNextTask();
	});
}

function check_unplayable_tracks() {
	spotifyLinkChecker.initialise();
	startBackgroundInitTasks.doNextTask();
}

var startBackgroundInitTasks = function() {

	var stufftodo = [
		setup_lastfm,
		connect_to_player,
		collectionHelper.checkCollection,
		load_playlists,
		start_userinterface,
		open_discoverator,
		refresh_podcasts,
		clean_backend_cache,
		check_unplayable_tracks
	];

	return {

		readytogo: false,

		doNextTask: function() {
			var nexttask = stufftodo.shift();
			if (typeof nexttask != 'undefined') {
				debug.mark('INIT', 'Starting init task', nexttask.name);
				nexttask.call();
			}
		}

	}

}();

$(document).ready(function(){
	debug.mark("INIT","Document Ready Event has fired");
	$('#albumpicture').on('load', albumImageLoaded);
	get_geo_country();
	if (prefs.do_not_show_prefs) {
		$('.choose_prefs').remove();
	}
	prefs.setTheme(prefs.theme);
	infobar.createProgressBar();
	pluginManager.doEarlyInit();
	createHelpLinks();
	layoutProcessor.initialise();
	$('.combobox').makeTagMenu({textboxextraclass: 'searchterm cleargroup', textboxname: 'tag', populatefunction: tagAdder.populateTagMenu});
	$('.tagaddbox').makeTagMenu({textboxname: 'newtags', populatefunction: tagAdder.populateTagMenu, buttontext: language.gettext('button_add'), buttonfunc: tagAdder.add, placeholder: language.gettext('lastfm_addtagslabel')});
	browser.createButtons();
	setPlayClickHandlers();
	bindClickHandlers();
	player.defs.replacePlayerOptions();
	// Checkbox and Radio buttons sadly can't be handled by delegated events
	// because a lot of them are in floatingMenus, which are handled by jQueryUI
	// which stops the events from propagating;
	$('.toggle').on('click', prefs.togglePref);
	$('.savulon').on('click', prefs.toggleRadio);
	$(document).on('keyup', ".saveotron", prefs.saveTextBoxes);
	$(document).on('change', ".saveomatic", prefs.saveSelectBoxes);
	$('.clickreplaygain').on('click', player.controller.replayGain);
	playlist.preventControlClicks(true);
	prefs.setPrefs();
	if (prefs.playlistcontrolsvisible) {
		$("#playlistbuttons").show();
	}
	if (prefs.collectioncontrolsvisible) {
		$("#collectionbuttons").show();
	}
	if (prefs.podcastcontrolsvisible) {
		$("#podcastbuttons").show();
	}
	showUpdateWindow();
	window.addEventListener("storage", onStorageChanged, false);
	bindPlaylistClicks();
	$(window).on('resize', uiHelper.adjustLayout);
	pluginManager.setupPlugins();
	setAvailableSearchOptions();
	// Note - the next function also calls adjustLayout
	setChooserButtons();
	// Some debugging info, saved to the backend so we can see it
	prefs.save({test_width: $(window).width(), test_height: $(window).height()});
	coverscraper = new coverScraper(0, false, false, prefs.downloadart);
	lastfm = new LastFM(prefs.lastfm_user);
	uiHelper.setupCollectionDisplay();
	layoutProcessor.sourceControl(prefs.chooser);
	if (prefs.browser_id == null) {
		prefs.save({browser_id: Date.now()});
	}
	$(document).on('click', '.clearbox.enter', makeClearWork);
	$(document).on('keyup', '.enter', onKeyUp);
	$(document).on('change', '.inputfile', inputFIleChanged);
	$(document).on('keyup', 'input.notspecial', filterSpecialChars);
	$(document).on('mouseenter', "#dbtags>.tag", showTagRemover);
	$(document).on('mouseleave', "#dbtags>.tag", hideTagRemover);
	$(document).on('click', 'body', closeMenus);
	$(document).on('click', '.tagremover:not(.plugclickable)', nowplaying.removeTag);
	if (prefs.mopidy_slave || (prefs.collection_player != prefs.player_backend && prefs.collection_player != null)) {
		$('[name="donkeykong"]').remove();
		$('[name="dinkeyking"]').remove();
	}
	snapcast.updateStatus();
	startBackgroundInitTasks.doNextTask();
});

function get_geo_country() {
	if (prefs.country_userset == false) {
		// It's helpful and important to get the country code set, as many users won't see it
		// and it's necessary for the Spotify info panel to return accurate data
		$.getJSON("utils/getgeoip.php", function(result) {
			debug.info("GET COUNTRY", 'Country:',result.country,'Code:',result.countryCode);
			if (result.country != 'ERROR') {
				$("#lastfm_country_codeselector").val(result.countryCode);
				prefs.save({lastfm_country_code: result.countryCode, country_userset: true});
			} else {
				debug.error("GET COUNTRY","Country code error",result);
			}
		});
	}
}

function createHelpLinks() {
	var helplinks = {};
	helplinks[language.gettext('button_local_music')] = 'https://fatg3erman.github.io/RompR/Music-Collection';
	helplinks[language.gettext('label_searchfor')] = 'https://fatg3erman.github.io/RompR/Searching-For-Music';
	helplinks[language.gettext('button_internet_radio')] = 'https://fatg3erman.github.io/RompR/Internet-Radio';
	helplinks[language.gettext('label_podcasts')] = 'https://fatg3erman.github.io/RompR/Podcasts';
	helplinks[language.gettext('label_audiobooks')] = 'https://fatg3erman.github.io/RompR/Spoken-Word';
	helplinks[language.gettext('label_pluginplaylists')] = 'https://fatg3erman.github.io/RompR/Personalised-Radio';
	helplinks[language.gettext('label_lastfm')] = 'https://fatg3erman.github.io/RompR/LastFM';
	helplinks[language.gettext('config_players')] = 'https://fatg3erman.github.io/RompR/Using-Multiple-Players';
	helplinks[language.gettext('config_snapcast')] = 'https://fatg3erman.github.io/RompR/Snapcast';

	for (var i in helplinks) {
		debug.debug("HELPLINKS","Appending Help Link For",i);
		$('b:contains("'+i+'")').each(function() {
			if ($(this).parent().hasClass('configtitle') && !$(this).parent().hasClass('nohelp')) {
				$(this).parent().append('<a href="'+helplinks[i]+'" target="_blank"><i class="icon-info-circled playlisticonr tright tooltip" title="'+language.gettext('label_gethelp')+'"></i></a>');
			} else if ($(this).parent().parent().hasClass('configtitle') && $(this).parent().parent().hasClass('dropdown-container')) {
				$(this).parent().parent().append('<div class="fixed"><a href="'+helplinks[i]+'" target="_blank"><i class="icon-info-circled playlisticon tooltip" title="'+language.gettext('label_gethelp')+'"></i></a></div>');
			}
		});
	}
}
