var tagAdder = function() {

	var index = null;
	var lastelement = null;
	var callback = null;

	return {
		show: function(evt, idx, cb) {
			debug.log('TAGADDER', evt, idx);
			callback = cb;
			if (evt.target == lastelement) {
				tagAdder.close();
			}  else {
				index = idx;
				var position = getPosition(evt);
				uiHelper.setFloaterPosition($('#tagadder'), position);
				$("#tagadder").slideDown('fast');
				lastelement = evt.target;
			}
		},

		close: function() {
			$("#tagadder").slideUp('fast');
			lastelement = null;
			callback = null;
		},

		add: function(toadd) {
			debug.log("TAGADDER","New Tags :",toadd);
			if (index !== null) {
				nowplaying.addTags(index, toadd);
			} else if (callback !== null) {
				callback($(lastelement), toadd);
			}
			tagAdder.close();
		},

		populateTagMenu: function(callback) {
			metaHandlers.genericQuery(
				'gettags',
				callback,
				function() {
					debug.error("DB TRACKS", "Failed to get tags");
				}
			);
		}
	}
}();


var bookmarkAdder = function() {

	var lastelement = null;
	var npindex = null;
	var bookmark = null;
	var type = null;
	var file = null;

	return {
		show: function(evt) {
			if (evt.target == lastelement) {
				bookmarkAdder.close();
			} else {
				npindex = nowplaying.findCurrentTrackIndex();
				bookmark = infobar.getProgress();
				type = playlist.getCurrent('type');
				file = playlist.getCurrent('file');
				debug.log('BOOKMARK', 'Adding to npindex',npindex,'at',bookmark);
				if (!bookmark || (!npindex && npindex !== 0))
					return;

				$('#bookmarkaddinfo').html(
					'Adding Bookmark to '+playlist.getCurrent('Title')+' at '+formatTimeString(bookmark)
				);

				var position = getPosition(evt);
				uiHelper.setFloaterPosition($('#bookmarkadddropdown'), position);
				$("#bookmarkadddropdown").slideDown('fast');
				lastelement = evt.target;

			}

		},

		add: function() {
			var name = $('input[name="bookmarkname"]').val();
			if (!name)
				name = 'Untitled Bookmark';

			switch (type) {
				case 'podcast':
					podcasts.storePlaybackProgress({progress: bookmark, uri: file, name: name});
					break;

				default:
					nowplaying.storePlaybackProgress(bookmark, npindex, name);
					break;

			}
			bookmarkAdder.close();
		},

		close: function() {
			$("#bookmarkadddropdown").slideUp('fast');
			npindex = null;
			lastelement = null;
		}
	}

}();

var addToPlaylist = function(evt) {
	return {
		show: function() {
			if ($('#pladddropdown').is(':visible')) {
				$('#pladddropdown').slideUp('fast');
			} else {
				var position = getPosition(evt);
				uiHelper.setFloaterPosition($('#pladddropdown'), position);
				$("#pladddropdown").slideDown('fast');
			}
		},

		close: function() {
			$('#pladddropdown').slideUp('fast');
		}
	}
}();

var pluginManager = function() {

	// Plugins (from the plugins directory) shoud call pluginManager.addPlugin at load time.
	// They must supply a label and either an action function, a setup function, or both.
	// The setup function will be called as soon as all the page scripts have loaded,
	// before the layout is initialised. If a plugin wishes to add icons to the layout,
	// or hotkeys, it should do it here.

	// If an action function is provided the plugin's label will be added to the dropdown list
	// above the info panel and the action function will be called when the label is clicked.

	// Alternatively, a script name can be provided. This script will be dynamically loaded
	// the first time the plugin is clicked on. The script MUST call setAction to set the
	// action function (for the next time it's clicked on), and call its own action function.

	var plugins = new Array();

	function openPlugin(index) {
		if (typeof plugins[index].action == 'function') {
			plugins[index].action();
		} else {
			debug.info("PLUGINS","Loading script",plugins[index].script,"for",plugins[index].label);
			$.getScript(plugins[index].script+'?version='+rompr_version).fail(function(data, settings, exception) {
				debug.error("PLUGINS","Failed Loading Script",exception);
			});
		}
	}

	function plugin_open() {
		var index = parseInt($(this).attr('name'));
		openPlugin(index);
	}

	return {
		addPlugin: function(label, action, setup, script, onmenu) {
			debug.log("PLUGINS","Adding Plugin",label,onmenu);
			plugins.push({label: label, action: action, setup: setup, script: script, onmenu: onmenu});
		},

		doEarlyInit: function() {
			for (var i in plugins) {
				if (plugins[i].setup) {
					if (!only_plugins_on_menu || plugins[i].onmenu) {
						debug.log("PLUGINS","Setting up Plugin",plugins[i].label);
						plugins[i].setup();
					}
				}
			}
		},

		setupPlugins: function() {
			for (var i in plugins) {
				if (plugins[i].action || plugins[i].script) {
					debug.log("PLUGINS","Setting up Plugin",plugins[i].label);
					if (only_plugins_on_menu) {
						if (plugins[i].onmenu) {
							$("#specialplugins .sptext").append('<div class="open-plugin backhi clickable clickicon noselection menuitem" name="'+i+'">'+plugins[i].label+'</div>');
						}
					} else {
						$("#specialplugins").append('<div class="open-plugin backhi clickable clickicon noselection menuitem" name="'+i+'">'+plugins[i].label+'</div>');
					}
				}
			}
			$('.open-plugin').on('click', plugin_open);
		},

		setAction: function(label, action) {
			for (var i in plugins) {
				if (plugins[i].label == label) {
					debug.log("PLUGINS","Setting Action for",label);
					plugins[i].action = action;
				}
			}
		},

		autoOpen: function(label) {
			for (var i in plugins) {
				if (plugins[i].label == label) {
					openPlugin(i);
					break;
				}
			}
		}
	}
}();

var imagePopup = function() {
	var popup_image = null;
	var waiting_spinner = null;
	var mousepos = null;
	var image = new Image();
	image.onload = function() {
		debug.debug("IMAGEPOPUP", "Image has loaded");
		imagePopup.show();
	}
	image.onerror = function() {
		debug.warn("IMAGEPOPUP", "Image has NOT loaded");
		imagePopup.close();
	}

	return {
		create:function(element, event, source){
			debug.log("IMAGEPOPUP", "Creating new popup",source);
			imagePopup.close();
			if (source === undefined)
				return;

			waiting_spinner = $('<i>', {class: 'icon-spin6 svg-square spinner notthere', style: 'position: absolute'}).appendTo($('body'));
			waiting_spinner.on('click', imagePopup.close)
			var spinw = waiting_spinner.outerWidth(true) / 2;
			var spinh = waiting_spinner.outerHeight(true) / 2;

			mousepos = getPosition(event);
			waiting_spinner.css({
				left: Math.max(0, (mousepos.x - spinw)) + 'px',
				top: Math.max(0, (mousepos.y - spinh)) + 'px'
			}).removeClass('notthere');
			image.src = "";
			image.src = source;
		},

		show:function() {

			var winsize = getWindowSize();
			if (popup_image === null)
				popup_image = $('<img>', {id: 'popup_image', class: 'dropshadow'});

			popup_image.off('transitionend').attr('src', image.src);

			popup_image.css({
				left: mousepos.x + 'px',
				top: mousepos.y + 'px',
				width: '0px',
				height: '0px',
				opacity: 0
			}).appendTo($('body'));

			var border_size = parseInt(popup_image.css('border-width')) * 2;
			popup_image.on('click', imagePopup.close)

			// We could just set width and height to auto and max-width and max-height to 100vw and 100vh
			// but I want to calculate the specific size because (a) this method would lose the borders
			// if the image was as big as the screen and (b) transitions on width and height don't work
			// with auto, and we need a way to ensure we keep the image within the viewport.
			// Because, as I think I've said before, CSS is annoying.

			var image_width = image.width;
			var image_height = image.height;

			var scale = Math.min(
				1,
				(winsize.x - border_size)/image.width,
				(winsize.y - border_size)/image.height
			);
			var final_width = Math.round(image.width * scale);
			var final_height = Math.round(image.height * scale);

			var final_top = Math.max(
				0,
				Math.min(
					mousepos.y - final_height/2,
					winsize.y - final_height - border_size
				)
			);
			var final_left = Math.max(
				0,
				Math.min(
					mousepos.x - final_width/2,
					winsize.x - final_width - border_size
				)
			);

			waiting_spinner.remove();
			waiting_spinner = null;
			popup_image.css({
				top: final_top + 'px',
				left: final_left + 'px',
				width: final_width + 'px',
				height: final_height + 'px',
				opacity: 1
			});

		},

		close:function() {
			if (waiting_spinner !== null)
				waiting_spinner.remove();

			if (popup_image !== null) {
				popup_image.on('transitionend', () => { $('#popup_image').remove(); popup_image = null });
				popup_image.css('opacity', 0);
			}

			waiting_spinner = null;

		}
	}
}();

function albumart_translator(source) {

	// This should be kept in step with class baseAlbumImgae

	// Given an album image of any size, return any other size
	this.source = source;

	this.getSize = function(size) {
		if (/albumart\/small\//.test(this.source)) {
			return this.source.replace('albumart/small/', 'albumart/'+size+'/');
		} else if (/albumart\/medium\//.test(this.source)) {
			return this.source.replace('albumart/medium/', 'albumart/'+size+'/');
		} else if (/albumart\/asdownloaded\//.test(this.source)) {
			return this.source.replace('albumart/asdownloaded/', 'albumart/'+size+'/');
		} else {
			return this.source.replace(/\&rompr_resize_size=.+/, '&rompr_resize_size='+size);
		}
	}

	// Get an image key
	this.getKey = function(type, artist, album) {
		switch (type) {
			case 'stream':
				artist = 'STREAM'
				break;

			case 'podcast':
				artist = 'podcast';
				break;
		}
		return hex_md5(artist.toLowerCase()+album.toLowerCase());
	}
}

function show_albumart_update_window() {
	if (old_style_albumart == 0) {
		return true;
	}
	var fnarkle = new popup({
		css: {
			width: 600,
			height: 400
		},
		fitheight: true,
		title: "Album Art Update",
		hasclosebutton: false
	});
	var mywin = fnarkle.create();
	mywin.append('<div id="artupdate" class="fullwdith"></div>');
	$('#artupdate').append('<div class="pref textcentre">Your Album Art needs to be updated. This process has now started. You can close this window to pause the process and it will continue the next time you open Rompr. Until you have updated all your art Rompr may run slowly and album art may look wierd</div>');
	$('#artupdate').append('<div id="albumart_update_bar" style="height:2em;width:100%"></div>');
	$('#artupdate').append('<div class="pref textcentre"><button id="artclosebutton">Close</button></div>');
	fnarkle.useAsCloseButton($('#artclosebutton'), stop_albumart_update);
	$('#albumart_update_bar').rangechooser({
		ends: ['max'],
		startmax: 0,
		range: 100
	});
	fnarkle.open();
	fnarkle.setWindowToContentsSize();
	$('.open_albumart').hide();
	do_albumart_update();
	return true;
}

function do_albumart_update() {
	$.getJSON('utils/update_albumart.php', function(data) {
		$('#albumart_update_bar').rangechooser('setProgress', data.percent);
		if (data.percent < 100 && albumart_update) {
			setTimeout(do_albumart_update, 100);
		} else {
			$('#artclosebutton').trigger('click');
		}
	});
}

function stop_albumart_update() {
	debug.log("UI", "Cancelling album art update");
	$('.open_albumart').show();
	albumart_update = false;
	return true;
}

function hidePanel(panel) {
	var is_hidden = $("#"+panel).is(':hidden');
	var new_state = prefs["hide_"+panel];
	debug.log("GENERAL","Hide Panel",panel,is_hidden,new_state);
	layoutProcessor.hidePanel(panel, is_hidden, new_state);
	setChooserButtons();
	collectionHelper.rejigDoodahs(panel, is_hidden);
}

function setChooserButtons() {
	var s = uiHelper.panelMapping();
	$.each(s, function(key, value) {
		if (prefs["hide_"+key]) {
			$('.choosepanel[name="'+value+'"]').addClass('invisible');
		} else {
			$('.choosepanel[name="'+value+'"]').removeClass('invisible');
		}
	});
	uiHelper.adjustLayout();
}

function parsePsetCss(item, dflt) {
	// Save looking these up every time, it's quite slow
	// Note that if aplha is set to 1, it doesn't come back. So use 0.99
	var c = get_css_variable(item);
	var regexp = /rgba\((\d+),\s*(\d+),\s*(\d+),\s*(.*)\s*\)/;
	var match = regexp.exec(c);
	// If no style is set it comes back as 0,0,0 so we must catch that
	// if you want black progress bars use 1,1,1
	if (match && match[1] && match[2] && match[3] && match[4] && (parseInt(match[1])+parseInt(match[2])+parseInt(match[3]) > 0)) {
		if (match[4] == '0.99') {
			match[4] = 1;
		}
		return {r: parseInt(match[1]), g: parseInt(match[2]), b: parseInt(match[3]), a: match[4]};
	} else {
		return dflt;
	}
}

function getrgbs(percent,min) {

	var mincolours = {r: 100, g: 50, b: 1, a: 1};
	var maxcolours = {r: 255, g: 75, b: 1, a: 1};
	var bgdcolours = {r: 0,   g: 0,  b: 0, a: 0};
	if (prefs.rgbs == null) {
		mincolours = parsePsetCss('--min-progress-colour', mincolours);
		prefs.rgbs = mincolours;
		maxcolours = parsePsetCss('--max-progress-colour', maxcolours);
		prefs.maxrgbs = maxcolours;
		bgdcolours = parsePsetCss('--progress-bg-colour', bgdcolours);
		prefs.bgdrgbs = bgdcolours;
	} else {
		mincolours = prefs.rgbs;
		maxcolours = prefs.maxrgbs;
		bgdcolours = prefs.bgdrgbs;
	}
	if (typeof percent != "number") {
		percent = parseFloat(percent);
	}
	if (typeof min != "number") {
		min = parseFloat(min);
	}
	percent = Math.min(percent, 100);
	min = Math.max(min, 0);
	var maxfraction = percent/100;
	var minfraction = min/100;
	var variance = {r: maxcolours.r - mincolours.r, g: maxcolours.g - mincolours.g, b: maxcolours.b - mincolours.b};
	var lowr = Math.round(mincolours.r + variance.r*minfraction);
	var lowg = Math.round(mincolours.g + variance.g*minfraction);
	var lowb = Math.round(mincolours.b + variance.b*minfraction);
	var highr = Math.round(mincolours.r + variance.r*maxfraction);
	var highg = Math.round(mincolours.g + variance.g*maxfraction);
	var highb = Math.round(mincolours.b + variance.b*maxfraction);
	// debug.log('COLOURS','dr',variance.r,'dg',variance.g,'db',variance.b,'lowr',lowr,'lowg',lowg,'lowb',lowb,'highr',highr,'highg',highg,'highb',highb,'minf',minfraction,'maxf',maxfraction);
	var bgd = "rgba("+bgdcolours.r+","+bgdcolours.g+","+bgdcolours.b+", "+bgdcolours.a+")";
	var lowalpha = mincolours.a;
	var highalpha = maxcolours.a;
	if (min == 0) {
		return  "rgba("+lowr+","+lowg+","+lowb+","+lowalpha+") 0%,"+
				"rgba("+highr+","+highg+","+highb+","+highalpha+") "+percent+"%,"+
				bgd+" "+percent+"%,"+bgd+" 100%)";
	} else {
		return  bgd+" 0%, "+
				bgd+" "+min+"%, "+
				"rgba("+lowr+","+lowg+","+lowb+","+lowalpha+") "+min+"%,"+
				"rgba("+highr+","+highg+","+highb+","+highalpha+") "+percent+"%,"+
				bgd+" "+percent+"%,"+
				bgd+" 100%)";
	}

}

// function populateSpotiTagMenu(callback) {
// 	spotify.recommendations.getGenreSeeds(
// 		function(data) {
// 			debug.debug("SPOTIFY","Got Genre Seeds",data);
// 			callback(data.genres);
// 		},
// 		function(data) {
// 			debug.error("SPOTIFY","Got error requesting genre seeds",data);
// 		}
// 	);
// }

function displayRating(where, what) {
	$(where).removeClass("icon-0-stars icon-1-stars icon-2-stars icon-3-stars icon-4-stars icon-5-stars");
	if (what !== false) {
		$(where).addClass('icon-'+what+'-stars');
	}
}

function showUpdateWindow() {
	if (typeof(prefs.shownupdatewindow) != 'string' || compare_version_numbers(prefs.shownupdatewindow, rompr_version)) {
		var fnarkle = new popup({
			css: {
				width: 1200,
				height: 1600
			},
			fitheight: true,
			title: 'RompЯ Version '+rompr_version,
			hasclosebutton: false
		});
		var mywin = fnarkle.create();
		mywin.append('<div id="version"></div>');
		mywin.append('<div id="begging"></div>');
		mywin.append('<div id="license"></div>');
		mywin.append('<div id="about"></div>');
		$('#version').load('utils/versioninfo.php');
		$('#begging').load('includes/begging.html', function() {
			$('#license').load('includes/license.html', function(){
				$('#about').load('includes/about.html', function() {
					fnarkle.addCloseButton('OK', show_albumart_update_window);
					prefs.save({shownupdatewindow: rompr_version});
					fnarkle.open();
				});
			});
		});
	} else if (prefs.lastversionchecktime < Date.now() - 604800000) {
		debug.mark('INIT', 'Doing Upgrade Check');
		$.ajax({
			method: 'GET',
			dataType: 'json',
			url: 'https://api.github.com/repos/fatg3erman/RompR/releases'
		})
		.done(function(data) {
			debug.debug('INIT', 'Got release data',data);
			var newest = '1.00';
			data.forEach(function(v) {
				if (compare_version_numbers(newest, v.tag_name)) {
					debug.trace('INIT', 'Found release',v.tag_name,'We are version',rompr_version);
					newest = v.tag_name;
				}
			});
			if (compare_version_numbers(rompr_version, newest) && compare_version_numbers(prefs.lastversionchecked, newest)) {
				debug.mark('INIT', 'New Version is available!');
				showNewVersionWindow(newest);
			} else {
				debug.log('INIT', 'Not doing anything about update');
				updateRemindLater();
			}
		})
		.fail(function(xhr,status,err) {
			debug.warn('INIT','Upgrade Check Failed',xhr,status,err);
		});
	}
}

function showNewVersionWindow(version) {
		var fnarkle = new popup({
			css: {
				width: 400,
				height: 200
			},
			fitheight: true,
			title: 'A New Version Is Available',
			hasclosebutton: false
		});
		var mywin = fnarkle.create();
		var d1 = $('<div>', {class: 'textcentre'}).appendTo(mywin);
		d1.html('Version '+version+' is now available. You have version '+rompr_version);
		var d2 = $('<div>', {class: 'textcentre'}).appendTo(mywin);
		d2.html('<a href="https://github.com/fatg3erman/RompR/releases" target="_blank">Download The Latest Release Here</a>');
		fnarkle.addCloseButton('Remind Me Later', updateRemindLater);
		fnarkle.addCloseButton('Never Remind Me', function() {
			prefs.save({lastversionchecked: version, lastversionchecktime: Date.now()});
		});
		fnarkle.open();
}

function updateRemindLater() {
	prefs.save({lastversionchecktime: Date.now()});
}

function compare_version_numbers(ver1, ver2) {
	// Returns true if ver1 < ver2
	// Eg ver1 = '1.14.9' ver2 = '1.14.10'
	// We need to compare them as digits because as a string 10 < 9
	// The internet's collected wisdom says the only way to do it is to compare
	// digit by digit, so I came up with this.
	var ver1_split = ver1.split('.');
	var ver2_split = ver2.split('.');
	debug.log('VERSIONS',ver1,ver2);
	for (var i in ver1_split) {
		if (prefs.dev_mode && typeof(ver2_split[i]) != 'undefined' && ver2_split[i].length > 4) {
			return false;
		}
		if (i > ver2_split.length) {
			// ver1 has more digits than ver2.
			// If we got here then we must have already compared the other digits, therefore ver1 must be > ver2 ie. 1.14.1 vs 1.14
			return false;
		}
		if (parseInt(ver1_split[i]) < parseInt(ver2_split[i])) {
			return true;
		} else if (parseInt(ver1_split[i]) > parseInt(ver2_split[i])) {
			return false;
		}
	}
	if (ver2_split.length > ver1_split.length) {
		// We got here with no differences, therefore ver2 has more digits than ver1 and must be the greater of the two
		return true;
	}
	return false;
}

function removeOpenItems(index) {
	// Filter out artist and album items whose dropdowns have been populated -
	// In these cases the individual tracks will exist and will be selected
	// (and might only have partial selections even if the header is selected)
	var self = $(this);
	if (self.hasClass('clicktrack') ||
		self.hasClass('clickcue') ||
		self.hasClass('clickstream')) {
		return true;
	}
	var element = (self.hasClass('clickloadplaylist') || self.hasClass('clickloaduserplaylist')) ? $('#'+self.children('i.menu').first().attr('name')) : $("#"+self.attr('name'));
	if (element.length == 0) {
		return true;
	} else if (element.hasClass('notfilled')) {
		return true;
	} else {
		return false;
	}
}

function onlyAlbums(index) {
	var self = $(this);
	if (self.hasClass('clicktrack') && self.parent().prev().hasClass('selected')) {
		return false;
	} else {
		return true;
	}
}

function calcPercentWidth(element, childSelector, targetWidth, parentWidth) {
	if (parentWidth < targetWidth) { return 100; }
	var t = element.find(childSelector);
	var r = element.find('.masonry_opened');
	var numElements = t.length + r.length;
	if (numElements == 0) { return 100; }
	var first_row = Math.round(parentWidth/targetWidth);
	var pixelwidth = parentWidth/first_row;
	if (parentWidth/numElements > pixelwidth) {
		pixelwidth = targetWidth;
	}
	if (childSelector != '.collectionitem') {
		pixelwidth -= masonry_gutter;
	}
	return (pixelwidth/parentWidth)*100;
}

function makeHoverWork(ev) {
	var jq = $(this);
	if (calcComboPos(ev, jq)) {
		jq.css('cursor','pointer');
	} else {
		jq.css('cursor','auto');
	}
}

function makeClearWork(ev) {
	var jq = $(this);
	if (calcComboPos(ev, jq)) {
		jq.val("");
		fakeClickOnInput(jq);
	}
}

function calcComboPos(ev, jq) {
	// This function relies on the fact that the size of the background image
	// that provides the icon we want to click on is 50% of the height of the element,
	// as defined in the icon theme css
	ev.preventDefault();
	ev.stopPropagation();
	var elh = jq.height()/2+2;
	var position = getPosition(ev);
	var elemright = jq.width() + jq.offset().left;
	return (position.x > elemright - elh);
}

function checkSearchDomains() {
	$("#mopidysearchdomains").makeDomainChooser({
		default_domains: prefs.mopidy_search_domains,
	});
	$("#mopidysearchdomains").find('input.topcheck').each(function() {
		$(this).on('click', function() {
			prefs.save({mopidy_search_domains: $("#mopidysearchdomains").makeDomainChooser("getSelection")});
		});
	});
}

function doMopidyCollectionOptions() {

	// Mopidy Folders to browse when building the collection

	// spotifyweb folders are SLOW, but that's to be expected.
	// We use 'Albums' to get 'Your Music' because, although it requires more requests than 'Songs',
	// each response will be small enough to handle easily and there's less danger of timeouts or
	// running into memory issues or pagination.

	var domains = {
		local: [{dir: "Local media", label: "Local Media"}],
		beetslocal: [{dir: "Local (beets)", label: "Local (beets)"}],
		beets: [{dir: "Beets library/Albums by Artist", label: "Beets Library"}],
		spotify: [{dir: "Spotify Playlists", label: "Spotify Playlists"},
				  {dir: "Spotify/Your music/Your tracks", label: "Spotify 'Your Tracks'"},
				  {dir: "Spotify/Your music/Your albums", label: "Spotify 'Your Albums'"}],
		gmusic: [{dir: "Google Music/Albums", label: "Google Music"}],
		soundcloud: [{dir: "SoundCloud/Liked", label: "SoundCloud Liked"},
					 {dir: "SoundCloud/Sets", label: "SoundCloud Sets"},
					 {dir: "SoundCloud/Stream", label: "SoundCloud Stream"}],
		vkontakte: [{dir: "VKontakte", label: "VKontakte" }]
	}

	for (var i in domains) {
		if (player.canPlay(i)) {
			for (var j in domains[i]) {
				var fum =
					'<div class="styledinputs indent">'+
					'<input class="mopocol" type="checkbox" id="mopcol_'+i+j+'"';
					if (prefs.mopidy_collection_folders.indexOf(domains[i][j].dir) > -1) {
						fum += ' checked';
					}
					fum += '>'+
					'<label for="mopcol_'+i+j+'">'+domains[i][j].label+'</label>'+
					'<input type="hidden" name="'+domains[i][j].dir+'" />'+
					'</div>';

				$("#mopidycollectionoptions").append(fum);
			}
		}
	}
	$('.mopocol').on('click', function() {
		var opts = new Array();
		$('.mopocol:checked').each(function() {
			opts.push($(this).next().next().attr('name'));
		});
		debug.debug("MOPIDY","Collection Options Are",opts);
		prefs.save({mopidy_collection_folders: opts});
	});
	if (!player.canPlay('beets')) {
		$('#beets_server_location').parent().hide();
	}
}

function fileUploadThing(formData, options, success, fail) {
	if (formData === null) {
		$.ajax({
			url: "utils/getalbumcover.php",
			type: "POST",
			data: options,
			cache:false
		})
		.done(success)
		.fail(fail);
	} else {
		$.each(options, function(i, v) {
			formData.append(i, v);
		})
		var xhr = new XMLHttpRequest();
		xhr.open('POST', 'utils/getalbumcover.php');
		xhr.responseType = "json";
		xhr.onload = function () {
			if (xhr.status === 200) {
				success(xhr.response);
			} else {
				fail();
			}
		};
		xhr.send(formData);
	}
}

function dropProcessor(evt, imgobj, coverscraper, success, fail) {

	evt.stopPropagation();
	evt.preventDefault();
	var options = coverscraper.getImageSearchParams(imgobj);
	var formData = new FormData();
	if (evt.dataTransfer.types) {
		for (var i in evt.dataTransfer.types) {
			type = evt.dataTransfer.types[i];
			debug.log("ALBUMART","Checking...",type);
			var data = evt.dataTransfer.getData(type);
			switch (type) {

				case "text/html":       // Image dragged from another browser window (Chrome and Firefox)
					var srces = data.match(/src\s*=\s*"(.*?)"/);
					if (srces && srces[1]) {
						src = srces[1];
						debug.log("ALBUMART","Image Source",src);
						imgobj.removeClass('nospin notexist notfound').addClass('spinner notexist').removeAttr('src');
						if (src.match(/image\/.*;base64/)) {
							debug.log("ALBUMART","Looks like Base64");
							formData.append('base64data', src);
							fileUploadThing(formData, options, success, fail);
						} else {
							options.source = src;
							fileUploadThing(null, options, success, fail);
						}
						return false;
					}
					break;

				case "Files":       // Local file upload
					debug.log("ALBUMART","Found Files");
					var files = evt.dataTransfer.files;
					if (files[0]) {
						imgobj.removeClass('nospin notexist notfound').addClass('spinner notexist').removeAttr('src');
						formData.append('ufile', files[0]);
						fileUploadThing(formData, options, success, fail);
						return false;
					}
					break;
			}

		}
	}
	// IF we get here, we didn't find anything. Let's try the basic text, which might give us something if we're lucky.
	// Safari returns a plethora of MIME types, but none seem to be useful.
	var data = evt.dataTransfer.getData('Text');
	var src = data;
	debug.log("ALBUMART","Trying last resort methods",src);
	if (src.match(/^https*:\/\//)) {
		debug.log("ALBUMART","Appears to be a URL");
		imgobj.removeClass('nospin notexist notfound').addClass('spinner notexist').removeAttr('src');
		var u = src.match(/images.google.com.*imgurl=(.*?)&/)
		if (u && u[1]) {
			src = u[1];
			debug.log("ALBUMART","Found possible Google Image Result",src);
		}
		options.source = src;
		fileUploadThing(null, options, success, fail);
	}
	return false;
}

function spotifyTrackListing(data) {
	var h = '';
	for(var i in data.tracks.items) {
		if (player.canPlay('spotify')) {
			h += '<div class="playable draggable clickable clicktrack fullwidth" name="'+rawurlencode(data.tracks.items[i].uri)+'">';
		} else {
			h += '<div class="fullwidth">';
		}
		h += '<div class="containerbox line">'+
			'<div class="tracknumber fixed">'+data.tracks.items[i].track_number+'</div>'+
			'<div class="expand">'+data.tracks.items[i].name+'</div>'+
			'<div class="fixed playlistrow2 tracktime">'+formatTimeString(data.tracks.items[i].duration_ms/1000)+'</div>'+
			'</div>'+
			'</div>';
	}
	return h;
}

function ratingCalc(element, event) {
	var position = getPosition(event);
	var width = element.width();
	var starsleft = element.offset().left;
	var rating = Math.ceil(((position.x - starsleft - 6)/width)*5);
	if (element.hasClass('icon-'+rating+'-stars')) {
		rating = 0;
	}
	return rating;
}

// var spotifyLinkChecker = function() {

// 	var timer = null;
// 	var tracks;

// 	function getNextUriToCheck() {
// 		metaHandlers.genericQuery('getlinktocheck', gotLinkToCheck, goneTitsUp);
// 	}

// 	function gotLinkToCheck(data) {
// 		debug.log('SPOTICHECKER', data);
// 		if (data.more) {
// 			spotifyLinkChecker.setTimer();
// 		} else {
// 			debug.info("SPOTICHECKER","No more tracks to check");
// 			prefs.save({linkchecker_isrunning: false});
// 		}
// 	}

// 	function goneTitsUp(data) {
// 		debug.error('SPOTICHECKER',"Nothing", data);
// 		prefs.save({linkchecker_isrunning: false});
// 	}

// 	function updateNextRunTime() {
// 		prefs.save({linkchecker_nextrun: Date.now() + prefs.linkchecker_frequency, linkchecker_isrunning: true});
// 	}

// 	return {

// 		setTimer: function() {
// 			clearTimeout(timer);
// 			timer = setTimeout(getNextUriToCheck, prefs.linkchecker_polltime);
// 		},

// 		initialise: function() {
// 			if (prefs.linkchecker_isrunning) {
// 				debug.info("SPOTICHECKER","Link Checker Continuing");
// 				updateNextRunTime();
// 				spotifyLinkChecker.setTimer();
// 			} else {
// 				if (Date.now() > prefs.linkchecker_nextrun) {
// 					debug.info("SPOTICHECKER","Link Checker Restarting",Date.now(),prefs.linkchecker_nextrun);
// 					updateNextRunTime();
// 					metaHandlers.genericQuery('resetlinkcheck', spotifyLinkChecker.setTimer, goneTitsUp);
// 				} else {
// 					debug.info("SPOTICHECKER","Link Checker Not Starting Yet");
// 				}
// 			}
// 			sleepHelper.addWakeHelper(spotifyLinkChecker.initialise);
// 		}

// 	}

// }();

function format_remote_api_error(msg, err) {
	let errormessage = language.gettext(msg);
	if (err.statusText)
		errormessage += ' ('+err.status+' '+err.statusText+')';
	if (err.responseJSON && err.responseJSON.error && err.responseJSON.error.message) {
		errormessage += ' ('+err.responseJSON.error.message+')';
	} else if (err.responseJSON && err.responseJSON.message) {
		errormessage += ' ('+err.responseJSON.message+')';
	}
	return errormessage;
}

function objFirst(obj) {
	// Return the first key of any object
    var retval;
    $.each(obj, function(i, v) { retval = i; return false; });
    return retval;
}
