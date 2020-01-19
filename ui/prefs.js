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
		"clickmode",
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
		"user_lang",
		"synctags",
		"synclove",
		"synclovevalue",
		"theme",
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
		"mopidy_radio_domains",
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
		"sortresultsby"
	];

	const menus_to_save_state_for = [
		'podcastbuttons',
		'advsearchoptions',
		'collectionbuttons',
		'playlistbuttons'
	];

	const jsonNode = document.querySelector("script[name='prefs']");
	const jsonText = jsonNode.textContent;
	const tags = JSON.parse(jsonText);

	var backgroundImages = new Array();
	var backgroundTimer;
	var portraitImage = new Image();
	var landscapeImage = new Image();
	var bgImagesLoaded = 0;

	var timeouts = {
		'10 Seconds': 10000,
		'30 Seconds': 30000,
		'Minute': 60000,
		'5 Minutes': 300000,
		'10 Minutes': 600000,
		'20 Minutes': 1200000,
		'30 Minutes': 1800000,
		'Hour': 3600000,
		'Day': 86400000
	}

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
				felakuti[$(this).attr("id")] = $(this).val().split(',');
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
						felakuti.snapcast_port != prefs.snapcast_port) {
							if (felakuti.snapcast_server == '') {
								snapcast.clearEverything();
							} else {
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

	function loadBackgroundImages(theme) {
		clearTimeout(backgroundTimer);
		$('#cusbgname').empty();
		$('#cusbgcontrols').empty();
		$('#backimageposition').hide();
		$.getJSON('backimage.php?getbackground='+theme+'&browser_id='+prefs.browser_id, function(data) {
			debug.debug("PREFS","Custom Background Image",data);
			if (data.images) {
				if (typeof(prefs.bgimgparms[theme]) == 'undefined') {
					debug.log("PREFS","Init bgimgparms for",theme);
					prefs.bgimgparms[theme] = {
						landscape: 0,
						portrait: 0,
						timeout: 60000,
						lastchange: Date.now(),
						random: false,
						position: 'top left'
					}
					prefs.save({bgimgparms: prefs.bgimgparms});
				} else if (typeof(prefs.bgimgparms[theme].position) == 'undefined') {
					prefs.bgimgparms[theme].position = 'top left';
					prefs.save({bgimgparms: prefs.bgimgparms});
				}
				setCustombackground(data.images);
				$('input[name="thisbrowseronly"]').prop('checked', data.thisbrowseronly);
				$('input[name="backgroundposition"][value="'+prefs.bgimgparms[theme].position+'"]').prop('checked', true);
				$('input[name="backgroundposition"]').off('click').on('click', changeBackgroundPosition);
			} else {
				backgroundImages = new Array();
			}
		});
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
		updateCustomBackground(false);
	}

	function removeAllBackgroundImages() {
		clearCustomBackground();
		$.getJSON('backimage.php?clearallbackgrounds='+prefs.theme+'&browser_id='+prefs.browser_id, function(data) {
			loadBackgroundImages(prefs.theme);
		});
	}

	function setCustombackground(images) {
		clearTimeout(backgroundTimer);
		debug.debug("UI","Setting Custom Background To",images);
		backgroundImages = images;
		if (images.landscape.length > 1 || images.portrait.length > 1) {
			var jesus = $('<div>', {class: 'containerbox dropdown-container'}).appendTo('#cusbgcontrols');
			jesus.append('<div class="divlabel">'+language.gettext('label_changevery')+'</div>');
			var sh = $('<div>', {class: 'selectholder'}).appendTo(jesus);
			var s = $('<select>').appendTo(sh);
			$.each(timeouts, function(i, v){
				s.append('<option value="'+v+'">'+i+'</option>');
			});
			s.val(prefs.bgimgparms[prefs.theme].timeout.toString());
			s.on('change', function() {
				prefs.bgimgparms[prefs.theme].timeout = parseInt(s.val());
				prefs.save({bgimgparms: prefs.bgimgparms});
				updateCustomBackground(false);
			});
			var gibbon = $('<div>').appendTo('#cusbgcontrols');
			var ran = $('<input>', {type: 'checkbox', id: 'bgimagerandom'}).appendTo(gibbon);
			var lab = $('<label>', {for: 'bgimagerandom'}).appendTo(gibbon);
			lab.html('Random Order');
			ran.prop('checked', prefs.bgimgparms[prefs.theme].random);
			ran.on('click', changeRandomMode);

			var orangutan = $('<div>').appendTo('#cusbgcontrols');
			var rb = $('<button>').appendTo(orangutan);
			rb.html('Remove All');
			rb.on('click', removeAllBackgroundImages);
		}
		if (images.landscape.length > 0 || images.portrait.length > 0) {
			$('#backimageposition').show();
		} else {
			$('#backimageposition').hide();
		}
		$.each(images, function(x, p) {
			if (p.length > 0) {
				$('#cusbgname').append('<div class="spacer"></div>');
				var q = $('<div>').appendTo('#cusbgname');
				$('#cusbgname').append('<div class="spacer"></div>');
				q.html('<b>'+p.length+' '+x.capitalize()+' Images</b>');
			}
			$.each(p, function(i, v) {
				var n = $('<div>').appendTo('#cusbgname');
				var c = $('<i>', {class: 'icon-cancel-circled clickicon collectionicon'}).appendTo(n);
				var l = $('<span>', {class: 'bgimgname', name: i}).appendTo(n);
				var z = $('<input>', {class: 'bgimagefile', type: 'hidden', value: v}).appendTo(n);
				var nom = v.replace(/.*(\\|\/)/, '')+'&nbsp;'
				if (x == 'landscape') {
					l.addClass('landscapeimage');
					// nom += '&#x25AD;';
				} else {
					l.addClass('portraitimage');
					// nom += '&#x25AF;';
				}
				l.html(nom);
				l.on('click', changeBgImage);
				c.on('click', prefs.clearBgImage);
			});
		});
		updateCustomBackground(false);
	}

	function changeBgImage(event) {
		var el = $(event.target);
		var bgp = prefs.bgimgparms[prefs.theme];
		bgp.lastchange = Date.now();
		if (el.hasClass('landscapeimage')) {
			bgp.landscape = parseInt(el.attr('name'));
			bgImagesLoaded = 1;
			landscapeImage.src = backgroundImages.landscape[bgp.landscape];
		} else if (el.hasClass('portraitimage')) {
			bgp.portrait = parseInt(el.attr('name'));
			bgImagesLoaded = 1;
			portraitImage.src = backgroundImages.portrait[bgp.landscape];
		}
		prefs.save({bgimgparms: prefs.bgimgparms});
	}

	function clearCustomBackground() {
		debug.log('PREFS', 'Clearing Custom background');
		$('style[id="phoneback"]').remove();
		$('style[id="background"]').remove();
		$('style[id="phonebackl"]').remove();
		$('style[id="backgroundl"]').remove();
		$('style[id="phonebackp"]').remove();
		$('style[id="backgroundp"]').remove();
	}

	function setBackgroundCss(bgp) {
		if (backgroundImages.length == 0) {
			return;
		}
		// Trying to reduce flickering using all kinds of stuff - pre-load images, update (not remove/recreate) the css
		['background', 'phoneback', 'backgroundl', 'phonebackl', 'backgroundp', 'phonebackp'].forEach(function(i) {
			if ($('style[id="'+i+'"]').length == 0) {
				debug.debug('PREFS', 'Creating background style', i);
				$('<style>', {id: i}).appendTo('head');
			}
		});
		if (backgroundImages.portrait.length == 0) {
			$('style[id="background"]').html('html { background-image: url("'+backgroundImages.landscape[bgp.landscape]+'"); background-position: '+bgp.position+' }');
			$('style[id="phoneback"]').html('body.phone .dropmenu { background-image: url("'+backgroundImages.landscape[bgp.landscape]+'"); background-position: '+bgp.position+' }');
			$('style[id="backgroundl"]').html('');
			$('style[id="phonebackl"]').html('');
			$('style[id="backgroundp"]').html('');
			$('style[id="phonebackp"]').html('');
			$('span.bgimgname').removeClass('selected');
			$('input.bgimagefile[value="'+backgroundImages.landscape[bgp.landscape]+'"]').prev().addClass('selected');
		} else if (backgroundImages.landscape.length == 0) {
			$('style[id="background"]').html('html { background-image: url("'+backgroundImages.portrait[bgp.portrait]+'"); background-position: '+bgp.position+' }');
			$('style[id="phoneback"]').html('body.phone .dropmenu { background-image: url("'+backgroundImages.portrait[bgp.portrait]+'"); background-position: '+bgp.position+' }');
			$('style[id="backgroundl"]').html('');
			$('style[id="phonebackl"]').html('');
			$('style[id="backgroundp"]').html('');
			$('style[id="phonebackp"]').html('');
			$('span.bgimgname').removeClass('selected');
			$('input.bgimagefile[value="'+backgroundImages.portrait[bgp.portrait]+'"]').prev().addClass('selected');
		} else {
			$('style[id="backgroundl"]').html('@media screen and (orientation: landscape) { html { background-image: url("'+backgroundImages.landscape[bgp.landscape]+'"); background-position: '+bgp.position+' } }');
			$('style[id="phonebackl"]').html('@media screen and (orientation: landscape) { body.phone .dropmenu { background-image: url("'+backgroundImages.landscape[bgp.landscape]+'"); background-position: '+bgp.position+' } }');
			$('style[id="backgroundp"]').html('@media screen and (orientation: portrait) { html { background-image: url("'+backgroundImages.portrait[bgp.portrait]+'"); background-position: '+bgp.position+' } }');
			$('style[id="phonebackp"]').html('@media screen and (orientation: portrait) { body.phone .dropmenu { background-image: url("'+backgroundImages.portrait[bgp.portrait]+'"); background-position: '+bgp.position+' } }');
			$('style[id="background"]').html('');
			$('style[id="phoneback"]').html('');
			$('span.bgimgname').removeClass('selected');
			$('input.bgimagefile[value="'+backgroundImages.landscape[bgp.landscape]+'"]').prev().addClass('selected');
			$('input.bgimagefile[value="'+backgroundImages.portrait[bgp.portrait]+'"]').prev().addClass('selected');
		}
	}

	function updateCustomBackground(force) {
		debug.debug('PREFS', 'Updating custom background');
		clearTimeout(backgroundTimer);
		var bgp = prefs.bgimgparms[prefs.theme];
		if (force || bgp.timeout + bgp.lastchange <= Date.now()) {
			if (bgp.random) {
				bgp.landscape = Math.floor(Math.random() * backgroundImages.landscape.length);
				bgp.portrait = Math.floor(Math.random() * backgroundImages.portrait.length);
			} else {
				bgp.landscape++;
				bgp.portrait++;
			}
			bgp.lastchange = Date.now();
			prefs.save({bgimgparms: prefs.bgimgparms});
		}
		if (bgp.landscape >= backgroundImages.landscape.length) { bgp.landscape = 0 }
		if (bgp.portrait >= backgroundImages.portrait.length) { bgp.portrait = 0 }
		bgImagesLoaded = 0;
		if (backgroundImages.portrait.length == 0) {
			bgImagesLoaded++;
			landscapeImage.src = backgroundImages.landscape[bgp.landscape];
		} else if (backgroundImages.landscape.length == 0) {
			bgImagesLoaded++;
			portraitImage.src = backgroundImages.portrait[bgp.portrait];
		} else {
			landscapeImage.src = backgroundImages.landscape[bgp.landscape];
			portraitImage.src = backgroundImages.portrait[bgp.portrait];
		}
		debug.log('PREFS','Backgrounds set to',landscapeImage.src,portraitImage.src);
	}

	function setBackgroundTimer(timeout) {
		if (backgroundImages.portrait.length > 1 || backgroundImages.landscape.length > 1) {
			debug.debug("PREFS","Setting Slideshow Timeout For",timeout/1000,"seconds");
			backgroundTimer = setTimeout(updateCustomBackground, timeout);
		}
	}

	function bgImageLoaded() {
		debug.debug('PREFS', 'background Image Loaded');
		bgImagesLoaded++;
		if (bgImagesLoaded == 2) {
			var bgp = prefs.bgimgparms[prefs.theme];
			setBackgroundCss(bgp);
			prefs.save({bgimgparms: prefs.bgimgparms});
			var timeout = Math.max(0, (bgp.timeout + bgp.lastchange - Date.now()));
			setBackgroundTimer(timeout);
		}
	}

	function bgImageError() {
		var bgp = prefs.bgimgparms[prefs.theme];
		debug.warn('PREFS', 'Background image failed to load', bgp.landscape,bgp.portrait,backgroundImages.landscape.length,backgroundImages.portrait.length,portraitImage.src,landscapeImage.src);
		updateCustomBackground(true);
	}

	portraitImage.onload = bgImageLoaded;
	landscapeImage.onload = bgImageLoaded;
	portraitImage.onerror = bgImageError;
	landscapeImage.onerror = bgImageError;

	return {
		loadPrefs: function() {
			for (var p in tags) {
				if (prefsInLocalStorage.indexOf(p) > -1) {
					if (localStorage.getItem("prefs."+p) != null && localStorage.getItem("prefs."+p) != "") {
						try {
							prefs[p] = JSON.parse(localStorage.getItem("prefs."+p));
						}
						catch (err) {
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

			if (prefs.icontheme == 'IconFont') {
				// Removed icon theme
				prefs.icontheme = 'Colourful';
			}
			if (prefs.lastfmlang == 'user') {
				// Pre- 1.40 lastfmlang could be 'user' to use a separate pref, now combined
				prefs.lastfmlang = prefs.user_lang;
			}
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
				await $.post('saveprefs.php', {prefs: JSON.stringify(prefsToSave)});
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
					callback = reloadWindow;
					break;

				case "use_albumart_in_playlist":
					callback = playlist.repopulate;
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

				case 'collectionrange':
				case 'sortcollectionby':
				case 'sortresultsby':
					callback = layoutProcessor.changeCollectionSortMode;
					break;


			}
			prefs.save(prefobj, callback);
		},

		changelanguage: function() {
			prefs.save({language: $("#langselector").val()}, function() {
				location.reload(true);
			});
		},

		saveTextBoxes: function() {
			clearTimeout(textSaveTimer);
			textSaveTimer = setTimeout(doTheSave, 1000);
		},

		setTheme: function(theme) {
			clearTimeout(uichangetimer);
			themeManager.teardown();
			themeManager.init = emptyfunction;
			themeManager.teardown = emptyfunction;
			if (!theme) theme = prefs.theme;
			// These 2 themes were removed
			if (theme == 'PlasmaPortrait.css') {
				theme = 'Plasma.css';
				$("#themeselector").val(theme);
				prefs.save({theme: 'Plasma.css'});
			}
			if (theme == 'Storm.css') {
				theme = 'Mountains.css';
				$("#themeselector").val(theme);
				prefs.save({theme: 'Mountains.css'});
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
			loadBackgroundImages(theme);
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
			browser.rePoint();
		},

		changeBackgroundImage: function() {
			$('#bgfileuploadbutton').fadeOut('fast');
			$('#bguploadspinner').addClass('spinner').parent().fadeIn('fast');
			$('[name="currbackground"]').val(prefs.theme);
			$('[name="browser_id"]').val(prefs.browser_id);
			var formElement = document.getElementById('backimageform');
			var xhr = new XMLHttpRequest();
			xhr.open("POST", "backimage.php");
			xhr.responseType = "json";
			xhr.onload = function () {
				switch (xhr.status) {
					case 200:
						debug.debug("BIMAGE", xhr.response);
						prefs.setTheme();
						$('#bguploadspinner').removeClass('spinner').parent().fadeOut('fast');
						break;

					case 400:
						debug.warn("BIMAGE", "FAILED");
						infobar.error(language.gettext('error_toomanyimages'));
						// Fall Through

					default:
						debug.warn("BIMAGE", "FAILED");
						infobar.error(language.gettext('error_imageupload'));
						$('#bguploadspinner').removeClass('spinner').parent().fadeOut('fast');

				}
			};
			xhr.send(new FormData(formElement));
		},

		clearBgImage: function(event) {
			var clicked = $(event.target);
			var image = clicked.next().next().val();
			clicked.parent().remove();
			clearCustomBackground();
			$.getJSON('backimage.php?clearbackground='+image, function(data) {
				$('[name=imagefile').next().html(language.gettext('label_choosefile'));
				$('[name=imagefile').parent().next('input[type="button"]').fadeOut('fast');
				loadBackgroundImages(prefs.theme);
			});
		},

		openBgImageBox: function() {
			$('#custombgdropper').slideToggle('fast');
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

		rgbs: null

	}

}();

prefs.loadPrefs();
// Update old pre-JSON prefs
if (localStorage.getItem("prefs.prefversion") == null) {
	for (var i in window.localStorage) {
		if (i.match(/^prefs\.(.*)/)) {
			var val = localStorage.getItem(i);
			if (val === "true") {
				val = true;
			}
			if (val === "false") {
				val = false;
			}
			localStorage.setItem(i, JSON.stringify(val));
		}
	}
	localStorage.setItem('prefs.prefversion', JSON.stringify(2));
}
prefs.theme = prefs.theme.replace('_1080p','');
prefs.fontfamily = prefs.fontfamily.replace('_', ' ');

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

