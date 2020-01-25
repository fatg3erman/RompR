var tagAdder = function() {

	var index = null;
	var lastelement = null;
	var callback = null;

	return {
		show: function(evt, idx, cb) {
			callback = cb;
			if (evt.target == lastelement) {
				tagAdder.close();
			}  else {
				index = idx;
				var position = getPosition(evt);
				layoutProcessor.setTagAdderPosition(position);
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
			metaHandlers.genericAction(
				'gettags',
				callback,
				function() {
					debug.error("DB TRACKS", "Failed to get tags");
				}
			);
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
							$("#specialplugins .sptext").append('<div class="backhi clickable clickicon noselection menuitem" name="'+i+'">'+plugins[i].label+'</div>');
						}
					} else {
						$("#specialplugins").append('<div class="backhi clickable clickicon noselection menuitem" name="'+i+'">'+plugins[i].label+'</div>');
					}
				}
			}
			$("#specialplugins").find(".clickicon").on('click', function() {
				var index = parseInt($(this).attr('name'));
				openPlugin(index);
			});
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
	var wikipopup = null;
	var imagecontainer = null;
	var mousepos = null;
	var clickedelement = null;
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
			debug.log("IMAGEPOPUP", "Current popup source is",image.src);
			if(wikipopup == null){
				wikipopup = $('<div>', { id: 'wikipopup', onclick: 'imagePopup.close()',
					class: 'dropshadow'}).appendTo($('body'));
				imagecontainer = $('<img>', { id: 'imagecontainer', onclick: 'imagePopup.close()',
					src: ''}).appendTo($('body'));
			} else {
				wikipopup.empty();
				imagecontainer.fadeOut('fast');
			}
			mousepos = getPosition(event);
			clickedelement = element;
			var top = (mousepos.y - 24);
			var left = (mousepos.x - 24);
			if (left < 0) {
				left = 0;
			}
			wikipopup.css({       width: '48px',
								  height: '48px',
								  top: top+'px',
								  left: left+'px'});
			wikipopup.append($('<i>', {class: 'icon-spin6 svg-square spinner',
				style: 'position:relative;top:8px;left:8px'}));
			wikipopup.fadeIn('fast');
			if (source !== undefined) {
				if (source == image.src) {
					imagePopup.show();
				} else {
					image.src = "";
					image.src = source;
				}
			}
		},

		show:function() {
			// Calculate popup size and position
			var imgwidth = image.width;
			var imgheight = image.height;
			debug.debug("POPUP","Image size is",imgwidth,imgheight);
			// Make sure it's not bigger than the window
			var winsize=getWindowSize();
			// hack to allow for vertical scrollbar
			winsize.x = winsize.x - 32;
			// Allow for popup border
			var w = winsize.x - 63;
			var h = winsize.y - 36;
			debug.debug("POPUP","Allowed size is",w,h);
			var scale = w/image.width;
			if (h/image.height < scale) {
				scale = h/image.height;
			}
			if (scale < 1) {
				imgheight = Math.round(imgheight * scale);
				imgwidth = Math.round(imgwidth * scale);
			}
			debug.debug("POPUP","Calculated Image size is",imgwidth,imgheight,(imgwidth/image.width),
				(imgheight/image.height));
			var popupwidth = imgwidth+36;
			var popupheight = imgheight+36;

			var top = (mousepos.y - (popupheight/2));
			var left = (mousepos.x - (popupwidth/2));
			if ((left+popupwidth) > winsize.x) {
				left = winsize.x - popupwidth;
			}
			if ((top+popupheight) > winsize.y) {
				top = winsize.y - popupheight;
			}
			if (top < 0) {
				top = 0;
			}
			if (left < 0) {
				left = 0;
			}
			wikipopup.empty();
			wikipopup.animate(
				{
					width: popupwidth+'px',
					height: popupheight+'px',
					top: top+'px',
					left: left+'px'
				},
				'fast',
				'swing',
				function() {
					imagecontainer.css({  top: (top+18)+'px',
										  left: (left+18)+'px'});

					imagecontainer.attr({ width: imgwidth+'px',
										  height: imgheight+'px',
										  src: image.src });

					imagecontainer.fadeIn('slow');
					wikipopup.append($('<i>', {class: 'icon-cancel-circled playlisticon tright clickicon',
						style: 'margin-top:4px;margin-right:4px'}));
				}
			);
		},

		close:function() {
			wikipopup.fadeOut('slow');
			imagecontainer.fadeOut('slow');
		}
	}
}();

function albumart_translator(source) {

	// This should be kept in step with class baseAlbumImgae in imagefunctions.php

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
	$.getJSON('update_albumart.php', function(data) {
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
			$('.choosepanel[name="'+value+'"]').fadeOut('fast');
		} else {
			$('.choosepanel[name="'+value+'"]').fadeIn('fast');
		}
	});
	uiHelper.adjustLayout();
}

function parsePsetCss(item, dflt) {
	// Save looking these up every time, it's quite slow
	// Note that if aplha is set to 1, it doesn't come back. So use 0.99
	var c = $(item).css('background-color');
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
		mincolours = parsePsetCss('#pset', mincolours);
		prefs.rgbs = mincolours;
		maxcolours = parsePsetCss('#pmaxset', maxcolours);
		prefs.maxrgbs = maxcolours;
		bgdcolours = parsePsetCss('#pbgset', bgdcolours);
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

function populateSpotiTagMenu(callback) {
	spotify.recommendations.getGenreSeeds(
		function(data) {
			debug.debug("SPOTIFY","Got Genre Seeds",data);
			callback(data.genres);
		},
		function(data) {
			debug.error("SPOTIFY","Got error requesting genre seeds",data);
		}
	);
}

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
	for (var i in ver1_split) {
		if (prefs.dev_mode && ver2_split[i].length > 4) {
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
	var r = element.find('.tagholder_wide');
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
			url: "getalbumcover.php",
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
		xhr.open('POST', 'getalbumcover.php');
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

var syncLastFMPlaycounts = function() {

	var limit = 100;
	var page = 1;
	var totalpages = 0;
	var currentdata = new Array();
	var notify = null;

	function failed(data) {
		debug.warn("LASTFMSYNC","It's all gone horribly wrong", data);
		removeNotify();
	}

	function gotPage(data) {
		debug.log('LASTFMSYNC', 'Got page', page);
		debug.debug("LASTFMSYNC", "Got Data", data);
		if (data.recenttracks) {
			totalpages = parseInt(data.recenttracks["@attr"].totalPages);
			if (data.recenttracks.track && data.recenttracks.track.length > 0) {
				if (notify === null) {
					notify = infobar.permnotify(
						'<div class="fullwidth">'+language.gettext('label_lfm_syncing')+'</div>'+
						'<div class="fullwidth" id="lfmsyncprogress" style="height:0.8em"></div>'
					);
					$('#lfmsyncprogress').rangechooser({
						range: (totalpages*2),
						interactive: false,
						startmax: 0,
						animate: true
					});
				}
				$('#lfmsyncprogress').rangechooser('setRange', {min: 0, max: page});
				let tracks = metaHandlers.fromLastFMData.setMeta(data.recenttracks.track, 'syncinc', [{attribute: 'Playcount', value: 1}], donePage, failed);
				podcasts.checkScrobbles(tracks);
			} else {
				debug.log('LASTFMSYNC', 'No tracks in page', page);
				removeNotify();
				prefs.save({last_lastfm_synctime: Math.floor(Date.now()/1000)});
				podcasts.doScrobbleCheck();
				prefs.addWakeHelper(syncLastFMPlaycounts.start);
			}
		} else {
			removeNotify();
		}
	}

	function donePage() {
		debug.log("LASTFMSYNC", "Done page", page);
		$('#lfmsyncprogress').rangechooser('setRange', {min: 0, max: (page*2)});
		page++;
		syncLastFMPlaycounts.start();
	}

	function removeNotify() {
		if (notify !== null) {
			infobar.removenotify(notify);
			notify = null;
		}
		page = 1;
	}

	return {

		start: function() {
			if (!lastfm.isLoggedIn()) {
				debug.log("LASTFMSYNC","Last.FM is not logged in");
				return;
			}
			debug.log("LASTFMSYNC","Getting recent tracks since ",prefs.last_lastfm_synctime);
			lastfm.user.getRecentTracks(
				{
					limit: limit,
					page: page,
					from: prefs.last_lastfm_synctime,
					extended: 1
				},
				gotPage,
				failed
			)
		}
	}

}();

var spotifyLinkChecker = function() {

	var timer = null;
	var tracks;

	function getNextUriToCheck() {
		metaHandlers.genericAction('getlinktocheck', gotLinkToCheck, goneTitsUp);
	}

	function gotLinkToCheck(data) {
		if (data.length > 0) {
			debug.debug('SPOTICHECKER',"Got next tracks to check", data);
			tracks = data;
			var ids = new Array();
			for (var i in data) {
				ids.push(data[i].Uri.replace(/spotify:track:/, ''));
			}
			spotify.tracks.checkLinking(ids, gotSpotiResponse, gotNoSpotiResponse, false);
		} else {
			debug.info("SPOTICHECKER","No more tracks to check");
			prefs.save({linkchecker_isrunning: false});
		}
	}

	function goneTitsUp(data) {
		debug.error('SPOTICHECKER',"Nothing", data);
	}

	function gotSpotiResponse(response) {
		debug.debug("SPOTICHECKER","Response from Spotify",response);
		var callback = spotifyLinkChecker.setTimer;
		var update_info = new Array();
		for (var i = 0; i < tracks.length; i++) {
			track = response.tracks[i];
			if (track) {
				if (track.is_playable) {
					uri = track.uri;
					debug.debug("SPOTICHECKER", "Track is playable");
					if (track.linked_from) {
						debug.trace("SPOTICHECKER", "Track was relinked",track);
						uri = track.linked_from.uri;
					}
					update_info.push({action: 'updatelinkcheck', ttindex: tracks[i]['TTindex'], uri: uri, status: 2});
				} else {
					debug.log("SPOTICHECKER", "Track is NOT playable", track.album.name, track.name);
					if (track.restrictions) {
						debug.trace("SPOTICHECKER", "Track restrictions :",track.restrictions.reason)
					}
					update_info.push({action: 'updatelinkcheck', ttindex: tracks[i]['TTindex'], uri: track.uri, status: 3});
				}
			} else {
				debug.warn("SPOTICHECKER","No data from Spotify for TTindex",tracks[i]['TTindex'])
				update_info.push({action: 'updatelinkcheck', ttindex: tracks[i]['TTindex'], uri: tracks[i]['Uri'], status: 3});
			}
		}
		metaHandlers.genericAction(update_info, callback, goneTitsUp);
	}

	function doneOK(data) {
		debug.debug("SPOTICHECKER","Link Checked OK");
	}

	function gotNoSpotiResponse(data) {
		debug.error("SPOTICHECKER","Error Response from Spotify",data);
	}

	function updateNextRunTime() {
		prefs.save({linkchecker_nextrun: Date.now() + prefs.linkchecker_frequency, linkchecker_isrunning: true});
	}

	return {

		setTimer: function() {
			clearTimeout(timer);
			timer = setTimeout(getNextUriToCheck, prefs.linkchecker_polltime);
		},

		initialise: function() {
			if (prefs.linkchecker_isrunning) {
				debug.info("SPOTICHECKER","Link Checker Continuing");
				updateNextRunTime();
				spotifyLinkChecker.setTimer();
			} else {
				if (Date.now() > prefs.linkchecker_nextrun) {
					debug.info("SPOTICHECKER","Link Checker Restarting",Date.now(),prefs.linkchecker_nextrun);
					updateNextRunTime();
					metaHandlers.genericAction('resetlinkcheck', spotifyLinkChecker.setTimer, goneTitsUp);
				} else {
					debug.info("SPOTICHECKER","Link Checker Not Starting Yet");
				}
			}
			prefs.addWakeHelper(spotifyLinkChecker.initialise);
		}

	}

}();
