var emptyfunction = function() {

}

var themeManager = function() {

	return {

		init: function() {

		},

		teardown: function() {

		}

	}

}();

var sleepHelper = function() {

	var windowActivationTimer = null;
	var wakeHelpers = new Array();
	var sleepHelpers = new Array();
	var visibilityHidden;
	var visibilityChange;

	return {

		// Event handlers to assist with doing things when the device wakes or goes to sleep
		// addWakeHelper(callback) to add a function to call when the device wakes
		// addSleepHelper(callback) to add a function to call when the device sleeps
		// We react to online, offline, and visibility events but will only call the callback ONCE
		// even if both events occur

		init: function() {
			if (typeof document.hidden !== "undefined") { // Opera 12.10 and Firefox 18 and later support
				visibilityHidden = "hidden";
				visibilityChange = "visibilitychange";
			} else if (typeof document.msHidden !== "undefined") {
				visibilityHidden = "msHidden";
				visibilityChange = "msvisibilitychange";
			} else if (typeof document.webkitHidden !== "undefined") {
				visibilityHidden = "webkitHidden";
				visibilityChange = "webkitvisibilitychange";
			}
			document.addEventListener(visibilityChange, sleepHelper.deviceHasWoken);
			window.addEventListener('online', sleepHelper.deviceHasWoken);
			window.addEventListener('offline', sleepHelper.deviceHasWoken);
		},

		deviceHasWoken: function(event) {
			clearTimeout(windowActivationTimer);
			switch (event.type) {
				case 'visibilitychange':
					if (document[visibilityHidden]) {
						debug.log('SLEEPHELPER', 'Browser tab is hidden');
						windowActivationTimer = setTimeout(sleepHelper.goToSleepMode, 1000);
					} else {
						debug.log('SLEEPHELPER', 'Browser tab is visible');
						windowActivationTimer = setTimeout(sleepHelper.goToWakeMode, 1000);
					}
					break;

				case 'online':
					debug.log('SLEEPHELPER', 'Device is back online');
					windowActivationTimer = setTimeout(sleepHelper.goToWakeMode, 1000);
					break;

				case 'offline':
					debug.log('SLEEPHELPER', 'Device is offline');
					windowActivationTimer = setTimeout(sleepHelper.goToSleepMode, 1000);
					break;
			}
		},

		isVisible: function() {
			return !document[visibilityHidden];
		},

		goToWakeMode: function() {
			clearTimeout(windowActivationTimer);
			for (var f of wakeHelpers) {
				debug.trace('SLEEPHELPER', 'Calling Wake Mode Helper',f.name);
				f.call();
			}
		},

		goToSleepMode: function() {
			clearTimeout(windowActivationTimer);
			for (var f of sleepHelpers) {
				debug.trace('SLEEPHELPER', 'Calling Sleep Mode Helper',f.name);
				f.call();
			}
		},

		addWakeHelper: function(callback) {
			if (wakeHelpers.indexOf(callback) == -1) {
				debug.log('SLEEPHELPER', 'Adding Wake Helper', callback.name);
				wakeHelpers.push(callback);
			}
		},

		addSleepHelper: function(callback) {
			if (sleepHelpers.indexOf(callback) == -1) {
				sleepHelpers.push(callback);
			}
		},

		removeWakeHelper: function(callback) {
			var i = wakeHelpers.indexOf(callback);
			if (i > -1) {
				wakeHelpers.splice(i, 1);
			}
		},

		removeSleepHelper: function(callback) {
			var i = sleepHelpers.indexOf(callback);
			if (i > -1) {
				sleepHelpers.splice(i, 1);
			}
		}

	}

}();

var prefs = function() {

	var textSaveTimer = null;
	var deferredPrefs = null;
	var uichangetimer = null;

	const prefsInLocalStorage = [
		"sourceshidden",
		"playlisthidden",
		"infosource",
		"sourceswidthpercent",
		"playlistwidthpercent",
		"downloadart",
		"chooser",
		"hide_albumlist",
		"hide_filelist",
		"hide_radiolist",
		"hide_playlistslist",
		"hide_audiobooklist",
		"hide_searcher",
		"hidebrowser",
		"shownupdatewindow",
		"scrolltocurrent",
		"alarm_ramptime",
		"alarm_snoozetime",
		"lastfmlang",
		"synctags",
		"synclove",
		"synclovevalue",
		"theme",
		'clickmode',
		"icontheme",
		"coversize",
		"fontsize",
		"fontfamily",
		"crossfade_duration",
		"newradiocountry",
		"search_limit_limitsearch",
		"scrobblepercent",
		"lastfm_scrobbling",
		"lastfm_autocorrect",
		"updateeverytime",
		"fullbiobydefault",
		"mopidy_search_domains",
		"skin",
		"outputsvisible",
		"wheelscrollspeed",
		"searchcollectiononly",
		"displayremainingtime",
		"cdplayermode",
		"auto_discovembobulate",
		"ratman_sortby",
		"sleeptime",
		"sleepon",
		"tradsearch",
		"sortwishlistby",
		"player_in_titlebar",
		"browser_id",
		"playlistswipe",
		"use_albumart_in_playlist",
		"bgimgparms",
		"collectionrange",
		"alarms",
		'playlistbuttons_isopen',
		'collectionbuttons_isopen',
		'advsearchoptions_isopen',
		'podcastbuttons_isopen'
	];

	const cookiePrefs = [
		'skin',
		'currenthost',
		'player_backend',
		"sortbydate",
		"notvabydate",
		"collectionrange",
		"sortcollectionby",
		"sortresultsby",
		"actuallysortresultsby"
	];

	const menus_to_save_state_for = [
		'podcastbuttons',
		'advsearchoptions',
		'collectionbuttons',
		'playlistbuttons'
	];

	const removed_themes = ['PlasmaPortrait.css', 'Storm.css', 'TheBlues.css', 'Kernsary.css', 'Leaves.css', 'NightClouds.css', 'Light.css'];

	var backgroundImages = false;
	var backgroundTimer;
	var portraitImage = new Image();
	var landscapeImage = new Image();
	var bgImagesLoaded = 0;

	function offerToTransferPlaylist() {
		var fnarkle = new popup({
			css: {
				width: 300,
				height: 200
			},
			title: language.gettext('label_transferplaylist'),
			hasclosebutton: false
		});
		var mywin = fnarkle.create();
		var d = $('<div>',{class: 'containerbox'}).appendTo(mywin);
		var yes = $('<button>', {class: 'expand'}).appendTo(d);
		var space = $('<div>', {class: 'fixed', style: 'width:3em'}).appendTo(d);
		var no = $('<button>', {class: 'expand'}).appendTo(d);
		yes.html(language.gettext('label_yes'));
		no.html(language.gettext('label_no'));
		fnarkle.useAsCloseButton(yes, transferPlaylist);
		fnarkle.useAsCloseButton(no, dontTransferPlaylist);
		fnarkle.open();
		fnarkle.setWindowToContentsSize();
	}

	function dontTransferPlaylist() {
		setCookie('player_backend', 'none', 0);
		prefs.save(deferredPrefs).then(reloadWindow);
	}

	function transferPlaylist() {
		debug.mark("PREFS","Transferring Playlist from",prefs.currenthost,"to",deferredPrefs.currenthost);
		$.ajax({
			type: 'POST',
			url: 'player/transferplaylist.php',
			data: JSON.stringify(deferredPrefs)
		})
		.done(function() {
			prefs.save(deferredPrefs).then(reloadWindow);
		})
		.fail(function(data) {
			debug.error("PREFS","Playlist transfer failed",data);
			infobar.error(language.gettext('error_trfailed')+'<br>'+data.responseText);
			prefs.setPrefs();
		});
	}

	function doTheSave() {
		var felakuti = new Object;
		var callback = null;
		$(".saveotron").each( function() {
			if ($(this).hasClass("arraypref")) {
				felakuti[$(this).attr("id")] = $(this).val().split(',').map(f => f.trim());
			} else {
				felakuti[$(this).attr("id")] = $(this).val();
			}
			switch ($(this).attr("id")) {
				case "composergenrename":
					if (!arraycompare(felakuti.composergenrename, prefs.composergenrename)) {
						$('[name="donkeykong"]').makeFlasher({flashtime:0.5, repeats: 3});
					}
					break;

				case "artistsatstart":
					if (!arraycompare(felakuti.artistsatstart, prefs.artistsatstart)) {
						callback = collectionHelper.forceCollectionReload;
					}
					break;

				case "nosortprefixes":
					if (!arraycompare(felakuti.nosortprefixes, prefs.nosortprefixes)) {
						callback = collectionHelper.forceCollectionReload;
					}
					break;

				case "crossfade_duration":
					if (felakuti.crossfade_duration != player.status.xfade &&
						player.status.xfade !== undefined &&
						player.status.xfade !== null &&
						player.status.xfade > 0) {
						callback = function() { player.controller.setCrossfade(felakuti.crossfade_duration) }
					}
					break;

				case "wheelscrollspeed":
					if (felakuti.wheelscrollspeed != prefs.wheelscrollspeed) {
						callback = reloadWindow;
					}
					break;

				case 'snapcast_server':
				case 'snapcast_port':
					if (felakuti.snapcast_server != prefs.snapcast_server ||
						felakuti.snapcast_http != prefs.snapcast_http) {
							if (felakuti.snapcast_server == '') {
								snapcast.clearEverything();
							} else {
								snapsocket.close();
								callback = snapcast.updateStatus;
							}
					}
					break;
			}
		});
		prefs.save(felakuti, callback);
	}

	function setscrob(e) {
		prefs.save({scrobblepercent: e.max});
	}

	function loadBackgroundImages() {
		clearTimeout(backgroundTimer);
		if (typeof(prefs.bgimgparms[prefs.theme]) == 'undefined') {
			debug.log("PREFS","Init bgimgparms for",prefs.theme);
			prefs.bgimgparms[prefs.theme] = {
				timeout: 60000,
				lastchange: Date.now(),
				random: false,
				position: 'top left'
			}
			prefs.save({bgimgparms: prefs.bgimgparms});
		} else if (typeof(prefs.bgimgparms[prefs.theme].position) == 'undefined') {
			prefs.bgimgparms[theme].position = 'top left';
			prefs.save({bgimgparms: prefs.bgimgparms});
		}
		backgroundImages = false;
		make_background_selector(prefs.theme);
		$.getJSON('api/userbackgrounds/?get_next_background='+prefs.theme+'&browser_id='+prefs.browser_id+'&random='+prefs.bgimgparms[prefs.theme].random, function(data) {
			debug.debug("PREFS","Custom Background Image",data);
			if (data.landscape) {
				portraitImage.onload = bgImageLoaded;
				landscapeImage.onload = bgImageLoaded;
				portraitImage.onerror = bgImageError;
				landscapeImage.onerror = bgImageError;
				sleepHelper.addWakeHelper(backOnline);
				sleepHelper.addSleepHelper(goneOffline);
				set_backimage_urls(data);
				$('#cusbgoptions').show();
			} else {
				$('#cusbgoptions').hide();
			}
		});
	}

	function set_backimage_urls(images) {
		debug.log('BACKIMAGE',images.portrait,images.landscape);
		var bgp = prefs.bgimgparms[prefs.theme];
		bgp.lastchange = Date.now();
		prefs.save({bgimgparms: prefs.bgimgparms});
		backgroundImages = true;
		bgImagesLoaded = 0;
		// If they're both the same the onload event won't fire for both images
		if (images.landscape == images.portrait)
			bgImagesLoaded++;

		landscapeImage.src = images.landscape;
		portraitImage.src = images.portrait;
	}

	function goneOffline() {
		clearTimeout(backgroundTimer);
	}

	function backOnline() {
		if (backgroundImages !== false)
			setBackgroundCss();
	}

	function changeRandomMode() {
		if (typeof(prefs.bgimgparms[prefs.theme].random) == 'undefined') {
			prefs.bgimgparms[prefs.theme].random = false;
		}
		prefs.bgimgparms[prefs.theme].random = !prefs.bgimgparms[prefs.theme].random;
		prefs.save({bgimgparms: prefs.bgimgparms});
	}

	function changeBackgroundPosition() {
		var bgp = prefs.bgimgparms[prefs.theme];
		bgp.position = $('input[name="backgroundposition"]:checked').val();
		prefs.save({bgimgparms: prefs.bgimgparms});
		document.documentElement.style.setProperty('--background-position', prefs.bgimgparms[prefs.theme].position);
	}

	function make_background_selector(theme) {
		clearTimeout(backgroundTimer);
		var s = $('#changeeveryselector');
		s.val(prefs.bgimgparms[prefs.theme].timeout.toString());
		s.off('change').on('change', function() {
			prefs.bgimgparms[prefs.theme].timeout = parseInt(s.val());
			prefs.save({bgimgparms: prefs.bgimgparms});
			setBackgroundTimer();
		});

		var ran = $('#cus_bg_random');
		ran.prop('checked', prefs.bgimgparms[prefs.theme].random);
		ran.off('click').on('click', changeRandomMode);

		$('input[name="backgroundposition"][value="'+prefs.bgimgparms[theme].position+'"]').prop('checked', true);
		$('input[name="backgroundposition"]').off('click').on('click', changeBackgroundPosition);
	}

	function doNothing() {

	}

	function clearCustomBackground() {
		debug.log('PREFS', 'Clearing Custom background');
		// Clear the image onload/onerror handlers because we also need to
		// clear the image source, otherwise if we change some other aspect of
		// the theme - eg font, the background image load event doesn't fire
		// and the background images don't get loaded (at least on Safari)
		portraitImage.onload = doNothing;
		landscapeImage.onload = doNothing;
		portraitImage.onerror = doNothing;
		landscapeImage.onerror = doNothing;
		document.documentElement.style.removeProperty('--landscape-bg-image');
		document.documentElement.style.removeProperty('--portrait-bg-image');
		portraitImage.src = '';
		landscapeImage.src = '';
	}

	function setBackgroundCss() {
		document.documentElement.style.setProperty('--landscape-bg-image', 'url("'+landscapeImage.src+'")');
		document.documentElement.style.setProperty('--portrait-bg-image', 'url("'+portraitImage.src+'")');
		document.documentElement.style.setProperty('--background-position', prefs.bgimgparms[prefs.theme].position);
		prefs.updateImageManager();
		setBackgroundTimer();
	}

	async function updateCustomBackground() {
		debug.info('PREFS', 'Updating custom background');
		clearTimeout(backgroundTimer);

		var images = await $.ajax({
			method: 'GET',
			url: 'api/userbackgrounds/?get_next_background='+prefs.theme+'&browser_id='+prefs.browser_id+'&random='+prefs.bgimgparms[prefs.theme].random,
			dataType: 'json',
			cache: false
		});

		set_backimage_urls(images);
	}

	function setBackgroundTimer() {
		clearTimeout(backgroundTimer);
		var bgp = prefs.bgimgparms[prefs.theme];
		var timeout = bgp.timeout + bgp.lastchange - Date.now();
		if (timeout > 0) {
			debug.trace("PREFS","Setting Slideshow Timeout For",timeout/1000,"seconds");
			backgroundTimer = setTimeout(updateCustomBackground, timeout);
		} else {
			updateCustomBackground();
		}
	}

	function bgImageLoaded() {
		debug.debug('PREFS', 'background Image Loaded');
		bgImagesLoaded++;
		if (bgImagesLoaded >= 2)
			setBackgroundCss();

	}

	function bgImageError() {
		debug.warn('PREFS', 'Background image failed to load', portraitImage.src,landscapeImage.src);
		updateCustomBackground();
	}

	return {
		loadPrefs: async function(callback) {

			var tags = await $.ajax({
				method: 'GET',
				url: 'includes/loadprefs.php',
				dataType: 'json',
				cache: false
			});

			for (var p in tags) {
				if (prefsInLocalStorage.indexOf(p) > -1) {
					if (localStorage.getItem("prefs."+p) != null && localStorage.getItem("prefs."+p) != "") {
						try {
							prefs[p] = JSON.parse(localStorage.getItem("prefs."+p));
						} catch (err) {
							prefs[p] = tags[p];
						}
					} else {
						prefs[p] = tags[p];
					}
				} else {
					prefs[p] = tags[p];
				}
			}

			for (var i in cookiePrefs) {
				var a = getCookie(cookiePrefs[i]);
				if (a != '') {
					if (a === 'false') { a = false; }
					if (a === 'true' ) { a = true; }
					prefs[cookiePrefs[i]] = a;
					if (prefs.debug_enabled > 7) {
						console.log("PREFS      : "+cookiePrefs[i]+' = '+prefs[cookiePrefs[i]]);
					}
				}
			}

			// When we auto-choose a skin, we set clickmode as a cookie. Set our local value, save it, then clear the cookie
			if (getCookie('clickmode') != '') {
				prefs.clickmode = getCookie('clickmode');
				debug.log('PREFS', 'Setting clickmode from cookie to',prefs.clickmode);
				localStorage.setItem("prefs.clickmode", JSON.stringify(prefs.clickmode));
				setCookie('clickmode', '', 1);
			}

			prefs.fontfamily = prefs.fontfamily.replace('_', ' ');

			if (callback)
				callback();

		},

		checkSet: function(key) {
			if (prefsInLocalStorage.indexOf(key) > -1) {
				if (localStorage.getItem("prefs."+key) != null && localStorage.getItem("prefs."+key) != "") {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		},

		save: async function(options, callback) {
			var prefsToSave = {};
			for (var i in options) {
				prefs[i] = options[i];
				if (cookiePrefs.indexOf(i) > -1) {
					debug.trace("PREFS", "Setting",i,"to",options[i],"as a cookie");
					var val = options[i];
					setCookie(i, val, 3650);
				}
				if (prefsInLocalStorage.indexOf(i) > -1) {
					debug.trace("PREFS", "Setting",i,"to",options[i],"in local storage");
					localStorage.setItem("prefs."+i, JSON.stringify(options[i]));
				} else {
					debug.debug("PREFS", "Setting",i,"to",options[i],"on backend");
					prefsToSave[i] = options[i];
				}
			}
			if (Object.keys(prefsToSave).length > 0) {
				debug.trace("PREFS",'Saving to backend', JSON.stringify(prefsToSave));
				await $.post('api/saveprefs/', {prefs: JSON.stringify(prefsToSave)});
			}
			if (callback) callback();
		},

		togglePref: function(event) {
			debug.debug("PREFS","Toggling",event);
			var prefobj = new Object;
			var prefname = $(this).attr("id");
			if (event === null) {
				// Event will be null if we've called into this through
				// $.proxy - like we have to in a floatingMenu.
				prefobj[prefname] = !$(this).is(":checked");
			} else {
				prefobj[prefname] = $(this).is(":checked");
			}
			var callback = null;
			switch (prefname) {
				case 'downloadart':
					coverscraper.toggle($("#"+prefname).is(":checked"));
					break;

				case 'hide_albumlist':
					callback = function() { hidePanel('albumlist') }
					break;

				case 'hide_searcher':
					callback = function() { hidePanel('searcher') }
					break;

				case 'hide_filelist':
					callback = function() { hidePanel('filelist') }
					break;

				case 'hide_radiolist':
					callback = function() { hidePanel('radiolist') }
					break;

				case 'hide_podcastslist':
					callback = function() { hidePanel('podcastslist') }
					break;

				case 'hide_audiobooklist':
					callback = function() { hidePanel('audiobooklist') }
					break;

				case 'hide_playlistslist':
					callback = function() { hidePanel('playlistslist') }
					break;

				case 'hide_pluginplaylistslist':
					callback = function() { hidePanel('pluginplaylistslist') }
					break;

				case 'hidebrowser':
					callback = layoutProcessor.hideBrowser;
					break;

				case 'searchcollectiononly':
				case "tradsearch":
					callback = setAvailableSearchOptions;
					break;

				case 'sortbycomposer':
				case 'composergenre':
					$('[name="donkeykong"]').makeFlasher({flashtime: 0.5, repeats: 3});
					break;

				case 'displaycomposer':
					debug.log("PREFS","Display Composer Option was changed");
					callback = player.controller.doTheNowPlayingHack;
					break

				case "sortbydate":
				case "notvabydate":
					callback = layoutProcessor.changeCollectionSortMode;
					break;

				case "sleepon":
					callback = sleepTimer.toggle;
					break;

				case 'player_in_titlebar':
					callback = infobar.forceTitleUpdate;
					break;

				case "playlistswipe":
				case "hide_master_volume":
					callback = reloadWindow;
					break;

				case "use_albumart_in_playlist":
					callback = playlist.repopulate;
					break;

				case "consume_workaround":
				debug.log('ARSE',player.status.consume,prefobj[prefname]);
					if (player.status.consume == 1 && prefobj[prefname]) {
						infobar.notify(language.gettext('warn_consumearound'));
						prefobj[prefname] = false;
						$('#'+prefname).prop('checked', false);
					} else if (prefobj['prefname']) {
						infobar.notify(language.gettext('msg_consumearound'));
					}
					break;

			}
			prefs.save(prefobj, callback);
		},

		toggleRadio: function(event) {
			debug.trace('PREFS', 'Toggling radio', $(this));
			var defer = false;
			var prefobj = new Object;
			var prefname = $(this).attr("name");
			var prefsave = prefname.replace(/_duplicate\d+/, '');
			prefobj[prefsave] = $('[name='+prefname+']:checked').val();
			var callback = null;
			switch(prefsave) {
				case 'clickmode':
					callback = setPlayClickHandlers;
					break;

				case 'currenthost':
					defer = true;
					offerToTransferPlaylist();
					break;

			}
			if (defer) {
				deferredPrefs = cloneObject(prefobj);
			} else {
				prefs.save(prefobj, callback);
			}
		},

		setPrefs: async function() {
			$("#langselector").val(interfaceLanguage);

			$("#scrobwrangler").rangechooser({
				range: 100,
				ends: ['max'],
				allowed_min: 0.5,
				onstop: setscrob,
				startmax: prefs.scrobblepercent/100
			});

			$.each($('.autoset'), function() {
				$(this).prop("checked", prefs[$(this).attr("id")]);
			});

			$.each($('.saveotron'), function() {
				if ($(this).hasClass('arraypref')) {
					var a = prefs[$(this).attr("id")];
					$(this).val(a.join());
				} else {
					$(this).val(prefs[$(this).attr("id")]);
				}
			});

			$.each($('.saveomatic'), function() {
				var prefname = $(this).attr("id").replace(/selector/,'');
				$(this).val(prefs[prefname]);
			});

			$.each($('.savulon'), function() {
				var prefname = $(this).attr("name");
				var prefsave = prefname.replace(/_duplicate\d+/, '');
				$("[name="+prefname+"][value="+prefs[prefsave]+"]").prop("checked", true);
			});

			for (var menu of menus_to_save_state_for) {
				if (prefs[menu+'_isopen']) {
					$('#'+menu).show();
				}
			}

		},

		saveSelectBoxes: function(event) {
			var prefobj = new Object();
			var prefname = $(event.target).attr("id").replace(/selector/,'');
			prefobj[prefname] = $(event.target).val();
			var callback = null;

			switch(prefname) {
				case "skin":
					// Because some skins require the info panel
					prefobj.hidebrowser = false;
					prefobj.sourceswidthpercent = 25;
					prefobj.playlistwidthpercent = 25;
					callback = reloadWindow;
					break;

				case "theme":
				case "icontheme":
				case "fontsize":
				case "fontfamily":
				case "coversize":
					callback = prefs.setTheme;
					break;

				case "lastfm_country_code":
					prefobj.country_userset = true;
					break;

				case 'podcast_sort_0':
				case 'podcast_sort_1':
				case 'podcast_sort_2':
				case 'podcast_sort_3':
					callback = podcasts.reloadList;
					break;

				case 'sortresultsby':
					prefobj.actuallysortresultsby = (prefobj.sortresultsby == 'sameas') ? prefs.sortcollectionby : prefobj.sortresultsby;
					callback = function() {
						layoutProcessor.changeCollectionSortMode();
						player.controller.reSearch();
					}
					break;

				case 'sortcollectionby':
					prefobj.actuallysortresultsby = (prefs.sortresultsby == 'sameas') ? prefobj.sortcollectionby : prefs.actuallysortresultsby;
					callback = layoutProcessor.changeCollectionSortMode;
					break;

				case 'collectionrange':
					callback = layoutProcessor.changeCollectionSortMode;
					break;


			}
			prefs.save(prefobj, callback);
		},

		changelanguage: function() {
			prefs.save({interface_language: $("#langselector").val()}, function() {
				location.reload(true);
			});
		},

		saveTextBoxes: function() {
			clearTimeout(textSaveTimer);
			textSaveTimer = setTimeout(doTheSave, 1000);
		},

		setTheme: function() {
			clearTimeout(uichangetimer);
			themeManager.teardown();
			themeManager.init = emptyfunction;
			themeManager.teardown = emptyfunction;
			var theme = prefs.theme;
			if (removed_themes.indexOf(theme) > -1) {
				theme = 'Numismatist.css';
				$("#themeselector").val(theme);
				prefs.save({theme: 'Numismatist.css'});
			}
			clearCustomBackground();
			// Use a different version every time to ensure the browser doesn't cache.
			// Browsers are funny about CSS.
			var t = Date.now();
			// Some browsers (Chrome, Safari) don't fire a load event on the theme element unless we delete and re-create it
			// Even then we have a fudge timer, just in case
			uichangetimer = setTimeout(prefs.postUIChange, 3000);
			$('#theme').remove();
			$('<link>', {id: 'theme', rel: 'stylesheet', type: 'text/css', href: "gettheme.php?version="+t
				+'&theme='+theme+'&fontsize='+prefs.fontsize+'&fontfamily='+prefs.fontfamily
				+'&coversize='+prefs.coversize+'&icontheme='+prefs.icontheme}).on('load', prefs.postUIChange).appendTo('head');
			try {
				$.getScript('themes/'+theme+'.js')
					.done(function() {
						debug.log('PREFS','Loaded theme script for',theme);
						themeManager.init();
					})
					.fail(function(jqxhr, settings, exception) {
						debug.debug('PREFS', 'Theme',theme,'does not have a manager script');
					});
			} catch(err) {
				debug.error('PREFS','Error loading theme script',err);
			}
			loadBackgroundImages();
		},

		postUIChange: function() {
			clearTimeout(uichangetimer);
			debug.debug('PREFS','Post UI Change actions');
			$('#theme').off('load');
			prefs.rgbs = null;
			prefs.maxrgbs = null;
			$('.rangechooser').rangechooser('fill');
			if (typeof charts !== 'undefined') {
				charts.reloadAll();
			}
			if (typeof(layoutProcessor) != 'undefined') {
				uiHelper.adjustLayout();
			}
			if ($('#backimunger').length > 0) {
				backimagemanager.populate();
			}
		},

		updateImageManager: function() {
			if ($('#backimunger').length > 0) {
				$('.back-image.highlighted').removeClass('highlighted');
				var u = portraitImage.src.substr(portraitImage.src.indexOf('prefs/'));
				$('input.back-filename[value="'+u+'"]').parent().addClass('highlighted');
				u = landscapeImage.src.substr(landscapeImage.src.indexOf('prefs/'));
				$('input.back-filename[value="'+u+'"]').parent().addClass('highlighted');
			}
		},

		setBgImage: function(orientation, url) {
			switch (orientation) {
				case 'portrait':
					portraitImage.src = url;
					break;

				case 'landscape':
					landscapeImage.src = url;
					break;
			}
		},

		clickBindType: function() {
			return prefs.clickmode == 'double' ? 'dblclick' : 'click';
		},

		save_prefs_for_open_menus: function(menu) {
			if (menus_to_save_state_for.indexOf(menu) != -1) {
				let tosave = {};
				tosave[menu+'_isopen'] = !prefs[menu+'_isopen'];
				prefs.save(tosave);
			}
		},

		manage_bg_images: function() {
			pluginManager.autoOpen(language.gettext("manage_bgs"));
		},

		rgbs: null

	}

}();


