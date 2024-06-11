// We need a way to detect when the album image has finished *rendering*
// - on mobile devices this can be some time after the image has loaded
// and that fucks up the biggerizing of the nowplaying text.
// This method seems to work, called from the albumpicture's onload event
// and is less clunky than just using some random setTimeout
function rendered() {
	debug.debug('ALBUMPICTURE', 'Rendered');
	$('#albumpicture').fadeIn('fast');
	$('#albumpicture').removeClass('clickicon').addClass('clickicon').off(prefs.click_event).on(prefs.click_event, infobar.albumImage.displayOriginalImage);
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

function get_spotify_genreseeds() {
	spotify.recommendations.getGenreSeeds(
		function(data) {
			debug.log('SEEDS', 'Got Spotify Genre Seeds', data);
			if (data.genres) {
				player.genreseeds = data.genres;
			}
		},
		function() {
			debug.warn('SEEDS', "Failed to get Spotify Genre Seeds");
		}
	);
	startBackgroundInitTasks.doNextTask();
}

function open_discoverator() {
	if (prefs.auto_discovembobulate) {
		pluginManager.autoOpen(language.gettext('button_infoyou'));
	}
	startBackgroundInitTasks.doNextTask();
}

var startBackgroundInitTasks = function() {

	var stufftodo = [
		connect_to_player,
		get_spotify_genreseeds,
		start_userinterface,
		collectionHelper.checkCollection,
		load_podcasts,
		open_discoverator,
		load_playlists,
		uiLoginBind
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
	// This is to work around the case where iOS and iPadOS use 'Request Desktop Site'
	// - this will casue MobileDetect to set desktopbrowser. This attempts to work around that.
	// iPadOS 13 'platform' is macIntel (!) - if Apple ever introuduce a touch macbook then we're
	// probably OK because that'll be an Apple Silicon device and you'd hope it'll use a different string.
	if (navigator.userAgent.includes("Mac")
		&& window.navigator.platform.match(/iPhone|iPod|iPad|MacIntel/)
		&& "ontouchend" in document
		&& $('body').hasClass('desktopbrowser')) {
		debug.mark('MOUSE', 'Seems we are on an apple Mobile Device', window.navigator.userAgent, window.navigator.platform, navigator.maxTouchPoints);
		$('body').removeClass('desktopbrowser').addClass('mobilebrowser');
	}

	// So by now, if we're on a mobile device body should have class 'mobilebrowser'.
	// If we're on a desktop device it'll have 'desktopbrowser', in that case we also
	// check to see if touch is supported so we can enable touch events and mouse events.
	if ($('body').hasClass('mobilebrowser')) {
		prefs.use_touch_interface = true;
		prefs.use_mouse_interface = false;
		prefs.has_custom_scrollbars = false;
		prefs.click_event = 'pointerup';
	} else if ('ontouchend' in document) {
		prefs.use_touch_interface = true;
		prefs.use_mouse_interface = true;
		prefs.has_custom_scrollbars = true;
		prefs.click_event = 'click';
		$('body').addClass('customscroll');
	} else {
		prefs.use_touch_interface = false;
		prefs.use_mouse_interface = true;
		prefs.has_custom_scrollbars = true;
		prefs.click_event = 'click';
		$('body').addClass('customscroll');
	}
	if (prefs.use_touch_interface)
		debug.mark('MOUSE', 'Touch interface is enabled');

	if (prefs.use_mouse_interface)
		debug.mark('MOUSE', 'Mouse interface is enabled');
}

function check_version_refresh() {
	fetch('utils/check_version.php')
	.then(response => response.text())
	.then(v => {
		debug.trace('INIT', 'Backend version is',v,'we are',prefs.backend_version);
		if (v != prefs.backend_version) {
			debug.mark('INIT', 'Backend version has changed. Reloading window');
			reloadWindow();
		}
	});
}

function carry_on_starting() {
	debug.mark("INIT","Prefs Have Been Loaded");
	if (typeof(IntersectionObserver) == 'function') {
		debug.info('UI', 'IntersectionObserver is present and being used');
	}
	set_mouse_touch_flags();
	sleepHelper.init();
	$('#albumpicture').on('load', albumImageLoaded);
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
	searchManager.setup_categories();
	sleepHelper.addWakeHelper(check_version_refresh);
}
