// We need a way to detect when the album image has finished *rendering*
// - on mobile devices this can be some time after the image has loaded
// and that fucks up the biggerizing of the nowplaying text.
// This method seems to work, called from the albumpicture's onload event
// and is less clunky than just using some random setTimeout
function rendered() {
	debug.debug('ALBUMPICTURE', 'Rendered');
	$('#albumpicture').fadeIn('fast');
	$('#albumpicture').removeClass('clickicon').addClass('clickicon').off('click').on('click', infobar.albumImage.displayOriginalImage);
	// uiHelper.adjustLayout();
	infobar.rejigTheText();
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
		debug.log('FILE', val);
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

function connect_to_player() {
	player.controller.initialise().then(startBackgroundInitTasks.doNextTask);
}

function load_playlists() {
	player.controller.reloadPlaylists().then(startBackgroundInitTasks.doNextTask)
}

function load_podcasts() {
	podcasts.reloadList().then(startBackgroundInitTasks.doNextTask)
}

function start_userinterface() {
	startBackgroundInitTasks.readytogo = true;
	uiHelper.adjustLayout();
	startBackgroundInitTasks.doNextTask();
}

function open_discoverator() {
	// if (prefs.auto_discovembobulate) {
	// 	pluginManager.autoOpen(language.gettext('button_infoyou'));
	// }
	startBackgroundInitTasks.doNextTask();
}

var startBackgroundInitTasks = function() {

	var stufftodo = [
		connect_to_player,
		start_userinterface,
		collectionHelper.checkCollection,
		load_podcasts,
		load_playlists,
		open_discoverator
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

if (typeof(IntersectionObserver) == 'function') {

	// Use IntersectionObserver API to load album images as they come into view
	// - makes sortby Album modes work, otherwise it loads every single image at
	// page load time. IntersectionObserver is relatively new, so check for support.
	// scootTheAlbums is called every time we load something with album
	// images and that takes care of loading them

	const imageLoadConfig = {
		rootMargin: '0px 0px 50px 0px',
		threshold: 0
	}

	var imageLoader = new IntersectionObserver(function(entries, self) {
	  entries.forEach(entry => {
	    if(entry.isIntersecting) {
	      preloadImage(entry.target);
	      self.unobserve(entry.target);
	    }
	  });
	}, imageLoadConfig);

	function preloadImage(img) {
		var self = $(img);
		self.attr('src', self.attr('data-src')).removeAttr('data-src').removeClass('lazy');
	}
}

$(document).ready(function(){
	debug.mark("INIT","Document Ready Event has fired");
	prefs.loadPrefs(carry_on_starting);
});

function set_mouse_touch_flags() {
	// Work around iPadOS announcing itself as a desktop browser
	if (navigator.userAgent.includes("Mac") && "ontouchend" in document && $('body').hasClass('mouseclick')) {
		$('body').removeClass('mouseclick').addClass('touchclick');
	}
	// Through a combination of the above hack (to catch the iPadOS stupidity case)
	// and the initial setting done by Mobile_Detect when the body tag was created,
	// If we have a touch-UI the body should have a class of touchclick.
	// (If it doesn't then it should have mouseclick)
	// Note that the historical reasons the Phone skin uses a caless of phone as well as touchclick
	if ($('body').hasClass('touchclick') || $('body').hasClass('phone')) {
		// Adjust desktop-oriented skins to run on touch devices
		uiHelper.is_touch_ui = true;
	}
}

function carry_on_starting() {
	debug.mark("INIT","Prefs Have Been Loaded");
	if (typeof(IntersectionObserver) == 'function') {
		debug.info('UI', 'IntersectionObserver is present and being used');
	}
	set_mouse_touch_flags();
	sleepHelper.init();
	$('#albumpicture').on('load', albumImageLoaded);
	get_geo_country();
	if (prefs.do_not_show_prefs) {
		$('.choose_prefs').remove();
	}
	prefs.setTheme();
	infobar.createProgressBar();
	pluginManager.doEarlyInit();
	browser.createButtons();
	uiHelper.initialise();
	player.defs.replacePlayerOptions();
	playlist.preventControlClicks(true);
	prefs.setPrefs();
	window.addEventListener("storage", onStorageChanged, false);
	pluginManager.setupPlugins();
	setAvailableSearchOptions();
	setChooserButtons();
	coverscraper = new coverScraper(0, false, false, prefs.downloadart);
	lastfm = new LastFM();
	uiHelper.sourceControl(prefs.chooser);
	if (prefs.mopidy_remote || (prefs.collection_player != prefs.player_backend && prefs.collection_player != null)) {
		$('[name="donkeykong"]').remove();
		$('[name="dinkeyking"]').remove();
	}
	snapcast.initialise();
	startBackgroundInitTasks.doNextTask();

	// Disable autocomplete for all text input elements, otherwise Chrome overrdies the background
	// colour and background image with its own internal ones and there's nothing we can do in css
	// to override it. What is happening to Chrome? They're the new Internet Explorer.
	$(document).on('focus', 'input[type="text"]', function() {
		$(this).attr('autocomplete', 'off');
	});
	$(document).on('focus', 'input[type="number"]', function() {
		$(this).attr('autocomplete', 'off');
	});
	//
	// Hide the Music from Spotify Panels
	// These are not currently used while we refactor those playlists
	// And in the absence of SPotify support in Mopidy
	// I'm not going to work on them
	//
	if (prefs.player_backend == 'mopidy') {
		$('#pluginplaylists_spotify').prev().hide();
		// $('#pluginplaylists_everywhere').prev().hide();
		// $('#pluginplaylists_everywhere').prev().prev().hide();
	}
	searchManager.setup_categories();
}

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
