jQuery.fn.revealImage = function() {
	// We don't return album images with the src attribute set
	// because if we insert them into a div that is not visible, mobile Safari
	// doesn't get the size right. So we set an 'asrc' attribute and set the src
	// attribute when the image is visible.
	$(this).each(function() {
		$(this).attr('src', $(this).attr('asrc'));
	});
	return this;
}

jQuery.fn.menuReveal = function(callback) {
	debug.log("UI", "Revealing",$(this).attr('id'));
	var self = this;
	if (this.hasClass('toggledown')) {
		if (callback) {
			this.slideToggle('fast',callback);
		} else {
			this.slideToggle('fast');
		}
	} else {
		this.findParentScroller().saveScrollPos();
		this.show(0, function() {
			layoutProcessor.postAlbumMenu();
			if (callback) {
				callback();
			}
		});
	}
	return this;
}

jQuery.fn.menuHide = function(callback) {
	debug.log("UI", "Hiding",$(this).attr('id'));
	var self = this;
	if (this.hasClass('toggledown')) {
		if (callback) {
			this.slideToggle('fast',callback);
		} else {
			this.slideToggle('fast');
		}
	} else {
		this.hide(0, function() {
			if (callback) {
				callback();
			}
			self.findParentScroller().restoreScrollPos();
			if (self.hasClass('removeable')) {
				self.remove();
			} else {
				debug.log("UI", "Hiding Image",$(this).attr('id'));
				var i = self.find('.album_menu_image');
				i.removeAttr('src');
			}
		});
	}
	return this;
}

jQuery.fn.isOpen = function() {
	if (this.hasClass('backmenu') || $('#'+this.attr('name')).is(':visible')) {
		return true;
	} else {
		return false;
	}
}

jQuery.fn.isClosed = function() {
	if (this.hasClass('backmenu') || $('#'+this.attr('name')).is(':visible')) {
		return false;
	} else {
		return true;
	}
}

jQuery.fn.makeSpinner = function() {
	return this.each(function() {
		var self = $(this);
		if (self.hasClass('icon-toggle-closed') || self.hasClass('icon-toggle-open') || self.hasClass('spinable')) {
			if (self.hasClass('icon-spin6') || self.hasClass('spinner')) {
				debug.warn('UIHELPER', 'Trying to create spinner on already spinning element');
				return;
			}
			var originalclasses = new Array();
			var classes = '';
			if (self.attr("class")) {
				var classes = self.attr("class").split(/\s/);
			}
			for (var i = 0, len = classes.length; i < len; i++) {
				if (classes[i] == "invisible" || (/^icon/.test(classes[i]))) {
					originalclasses.push(classes[i]);
					self.removeClass(classes[i]);
				}
			}
			self.attr("originalclass", originalclasses.join(" "));
			self.addClass('icon-spin6 spinner');
		} else {
			self.addClass('clickflash');
		}
		return this;
	});
}

jQuery.fn.stopSpinner = function() {
	return this.each(function() {
		var self = $(this);
		if (self.hasClass('spinner')) {
			self.removeClass('icon-spin6 spinner');
			if (self.attr("originalclass")) {
				self.addClass(self.attr("originalclass"));
				self.removeAttr("originalclass");
			}
		} else {
			self.removeClass('clickflash');
		}
		return this;
	});
}

jQuery.fn.findParentScroller = function() {
	var parentScroller = this.parent();
	while (!parentScroller.hasClass('scroller') && !parentScroller.hasClass('dropmenu') && !parentScroller.hasClass('phone')) {
		parentScroller = parentScroller.parent();
	}
	return parentScroller;
}

jQuery.fn.saveScrollPos = function() {
	this.prepend('<input type="hidden" name="restorescrollpos" value="'+this.scrollTop()+'" />');
	this.scrollTo(0);
	this.css('overflow-y', 'hidden');
	// Backmenu with position of sticky: if we don't reset that the parent backmenu sits above the child one
	// meaning we can't go back in appropriate steps. Note '.children' is essential - '.find' will reset the css
	// for all the submenus too
	this.children('.backmenu').css({position: 'static'});
}

jQuery.fn.restoreScrollPos = function() {
	var a = this.find('input[name="restorescrollpos"]');
	if (a.length > 0) {
		this.css('overflow-y', 'scroll');
		this.scrollTop(a.val());
		this.children('.backmenu').css({position: ''});
		a.remove();
	}
	$('#popupmenu').remove();
}

jQuery.fn.makeTagMenu = function(options) {
	var settings = $.extend({
		textboxname: "",
		textboxextraclass: "",
		labelhtml: "",
		populatefunction: null,
		buttontext: null,
		buttonfunc: null,
		buttonclass: "",
		placeholder: 'Tag'
	},options);

	this.each(function() {
		var tbc = "enter";
		if (settings.textboxextraclass) {
			tbc = tbc + " "+settings.textboxextraclass;
		}
		if (settings.labelhtml != '') {
			$(this).append(settings.labelhtml);
		}
		var holder = $('<div>', { class: "expand"}).appendTo($(this));
		var dropbutton = $('<i>', { class: 'fixed combo-button'}).appendTo($(this));
		var textbox = $('<input>', { type: "text", class: tbc, name: settings.textboxname, placeholder: settings.placeholder }).appendTo(holder);
		var dropbox = $('<div>', {class: "drop-box tagmenu dropshadow fullwidth"}).insertAfter($(this));
		var menucontents = $('<div>', {class: "tagmenu-contents"}).appendTo(dropbox);
		if (settings.buttontext !== null) {
			var submitbutton = $('<button>', {class: "fixed"+settings.buttonclass,
				style: "margin-left: 8px"}).appendTo($(this));
			submitbutton.html(settings.buttontext);
			if (settings.buttonfunc) {
				submitbutton.on('click', function() {
					settings.buttonfunc(textbox.val());
				});
			}
		}

		dropbutton.on('click', function(ev) {
			ev.preventDefault();
			ev.stopPropagation();
			if (dropbox.is(':visible')) {
				dropbox.slideToggle('fast');
			} else {
				var data = settings.populatefunction(function(data) {
					menucontents.empty();
					for (var i in data) {
						var d = $('<div>', {class: "backhi"}).appendTo(menucontents);
						d.html(data[i]);
						d.on('click', function() {
							var cv = textbox.val();
							if (cv != "") {
								cv += ",";
							}
							cv += $(this).html();
							textbox.val(cv);
						});
					}
					dropbox.slideToggle('fast');
				});
			}
		});
	});
}

jQuery.fn.fanoogleMenus = function() {
	return this;
}

jQuery.fn.fanoogleTopMenus = function() {
	this.each(function() {
		$(this).css({height: ''});
		var top = $(this).offset().top;
		var height = $(this).outerHeight(true);
		var ws = getWindowSize();
		debug.log('FANOOGLE',$(this).attr('id'), top, height, ws.y);
		var nh = Math.min(height, ws.y - top);
		$(this).css({height: nh+'px'});
	});
	return this;
}

/* Touchwipe for playlist only, based on the more general jquery touchwipe */
/*! jquery.touchwipe - v1.3.0 - 2015-01-08
* Copyright (c) 2015 Josh Stafford; Licensed MIT */

/* This version ignores all vertical swipes but adds a long press function
   and uses the touchend event instead of a timer to make the action happen */

jQuery.fn.playlistTouchWipe = function(settings) {

	var config = {
		min_move_x: 20,
		min_move_y: 20,
		swipeSpeed: 300,
		swipeDistance: 120,
		longPressTime: 1000,
		preventDefaultEvents: false, // prevent default on swipe
		preventDefaultEventsX: true, // prevent default is touchwipe is triggered on horizontal movement
		preventDefaultEventsY: true // prevent default is touchwipe is triggered on vertical movement
	};

	if (settings) {
		$.extend(config, settings);
	}

	this.each(function() {
		var startX;
		var startY;
		var isMoving = false;
		var touchesX = [];
		var touchesY = [];
		var self = this;
		var starttime = 0;
		var longpresstimer = null;
		var pressing = false;

		function cancelTouch() {
			clearTimeout(longpresstimer);
			this.removeEventListener('touchmove', onTouchMove);
			this.removeEventListener('touchend', onTouchEnd);
			startX = null;
			startY = null;
			isMoving = false;
			pressing = false;
		}

		function onTouchEnd(e) {
			var time = Date.now();
			clearTimeout(longpresstimer);
			if (pressing) {
				e.stopImmediatePropagation();
				e.stopPropagation();
				e.preventDefault();
				pressing = false;
				setTimeout(bindPlaylistClicks, 500);
			} else if (isMoving) {
				var dx = touchesX.pop();
				touchesX.push(dx);
				if (time - starttime < config.swipeSpeed && dx > config.swipeDistance) {
					touchesX.push($(self).outerWidth(true));
					if ($(self).hasClass('item')) {
						$(self).next().animate({left: 0 - $(self).outerWidth(true)}, 'fast', 'swing');
					}
					$(self).animate({left: 0 - $(self).outerWidth(true)}, 'fast', 'swing', doAction);
				} else {
					doAction();
				}
			}
		}

		function doAction() {
			var dxFinal, dyFinal;
			cancelTouch();
			dxFinal = touchesX.pop();
			touchesX = [];
			if (dxFinal > ($(self).outerWidth(true)*0.75)) {
				if ($(self).hasClass('track')) {
					playlist.delete($(self).attr('romprid'));
				} else if ($(self).hasClass('item')) {
					playlist.deleteGroup($(self).attr('name'));
				}
			} else {
				$(self).animate({left: 0}, 'fast', 'swing');
				if ($(self).hasClass('item')) {
					$(self).next().animate({left: 0}, 'fast', 'swing');
				}
			}
		}

		function onTouchMove(e) {
			clearTimeout(longpresstimer);
			if(config.preventDefaultEvents) {
				e.preventDefault();
			}

			if (isMoving) {
				var x = e.touches[0].pageX;
				var y = e.touches[0].pageY;
				var dx = startX - x;
				var dy = startY - y;

				if (Math.abs(dx) >= config.min_move_x) {
					if (config.preventDefaultEventsX) {
						e.preventDefault();
					}
					var newpos = 0 - dx;
					if (newpos < 0) {
						$(self).css('left', newpos.toString()+'px');
						if ($(self).hasClass('item')) {
							$(self).next().css('left', newpos.toString()+'px');
						}
						touchesX.push(dx);
					}
				}
			}
		}

		function longPress() {
			debug.log("TOUCHWIPE","Long Press");
			pressing = true;
			// Unbind click handler from playlist, otherwise the touchend
			// event makes it start playing the clicked track.
			// Don't seem to be able to prevent the event propagating.
			$(self).addBunnyEars();
			unbindPlaylistClicks();
		}

		function onTouchStart(e) {
			starttime = Date.now();
			if (e.touches.length === 1) {
				startX = e.touches[0].pageX;
				startY = e.touches[0].pageY;
				isMoving = true;
				this.addEventListener('touchmove', onTouchMove, false);
				this.addEventListener('touchend', onTouchEnd, false);
				longpresstimer = setTimeout(longPress, config.longPressTime);
			}
		}

		this.addEventListener('touchstart', onTouchStart, false);

	});

	return this;
};


function showHistory() {
	if ($('#historypanel').find('.configtitle').length > 0) {
		$('#historypanel').slideToggle('fast');
	}
}

var layoutProcessor = function() {

	function isLandscape() {
		if (window.innerHeight > window.innerWidth) {
			return false;
		} else {
			return true;
		}
	}

	function doSwipeCss() {
		if (prefs.playlistswipe) {
			$('<style id="playlist_swipe">#sortable .playlisticonr.icon-cancel-circled { display: none }</style>').appendTo('head');
		} else {
			$('style[id="playlist_swipe"]').remove();
		}
	}

	return {

		supportsDragDrop: false,
		hasCustomScrollbars: false,
		usesKeyboard: false,
		sortFaveRadios: false,
		openOnImage: true,
		swipewason: prefs.playlistswipe,

		changeCollectionSortMode: function() {
			collectionHelper.forceCollectionReload();
		},

		postAlbumMenu: function(menu) {
			$('.album_menu_image:visible').revealImage();
		},

		afterHistory: function() {
			browser.rePoint();
			showHistory();
		},

		addInfoSource: function(name, obj) {
			$("#chooserbuttons").append($('<i>', {
				onclick: "browser.switchsource('"+name+"')",
				class: obj.icon+' topimg expand',
				id: "button_source"+name
			}));
		},

		setupInfoButtons: function() { },

		goToBrowserPanel: function(panel) {
			$('#infopane').scrollTo('#'+panel+'information',800,{easing: 'swing'});
		},

		goToBrowserPlugin: function(panel) {
			layoutProcessor.sourceControl('infopane');
			layoutProcessor.goToBrowserPanel(panel);
		},

		goToBrowserSection: function(section) {
			// Wikipedia mobile does not return contents
		},

		notifyAddTracks: function() {
			if (!playlist.radioManager.isRunning()) {
				infobar.notify(language.gettext("label_addingtracks"));
			}
		},

		hidePanel: function(panel, is_hidden, new_state) { },

		panelMapping: function() {
			return {
				"albumlist": 'albumlist',
				"searcher": 'searcher',
				"filelist": 'filelist',
				"radiolist": 'radiolist',
				"audiobooklist": "audiobooklist",
				"podcastslist": 'podcastslist',
				"playlistslist": 'playlistman',
				"pluginplaylistslist": 'pluginplaylists'
			}
		},

		setTagAdderPosition: function(position) {

		},

		setPlaylistHeight: function() {
			var newheight = $("#playlistm").height() - $("#horse").outerHeight(true);
			if ($("#playlistbuttons").is(":visible")) {
				newheight = newheight - $("#playlistbuttons").outerHeight(true) - 2;
			}
			$("#pscroller").css("height", newheight.toString()+"px");
		},

		playlistLoading: function() {
			infobar.smartradio(language.gettext('label_preparing'));
		},

		preHorse: function() {
			if (!$("#playlistbuttons").is(":visible")) {
				// Make the playlist scroller shorter so the window doesn't get a vertical scrollbar
				// while the buttons are being slid down
				var newheight = $("#pscroller").height() - 48;
				$("#pscroller").css("height", newheight.toString()+"px");
			}
		},

		scrollPlaylistToCurrentTrack: function() {
			var scrollto = playlist.getCurrentTrackElement();
			if (prefs.scrolltocurrent && scrollto.length > 0) {
				var offset = 0 - ($('#pscroller').outerHeight(true) / 2);
				$('#pscroller').scrollTo(scrollto, 800, {offset: {top: offset}, easing: 'swing'});
			}
		},

		playlistupdate: function(upcoming) {

		},

		addCustomScrollBar: function(value) {

		},

		sourceControl: function(source) {
			debug.shout('LAYOUT','Switching source to',source);
			// hacky - set an irrelevant css parameter as a flag so we change behaviour
			var layoutflag = parseInt($('.choose_playlist').css('font-weight'));
			if ((source == 'playlistm' || source == 'infobar') && prefs.chooser != 'infopane' && layoutflag == 1000) {
				return;
			}
			if (source == 'infopane') {
				$('#infobar').css('display', 'none');
				if (layoutflag >= 900) {
					$('#playlistm').css('display', 'none');
				}
			} else {
				$('#infobar').css('display', '');
				if (layoutflag >= 900) {
					$('#playlistm').css('display', '');
				}
			}
			if (source == "playlistm" && layoutflag >= 900) {
				source = "infobar";
			}
			$('.mainpane:not(.invisible):not(#'+source+')').addClass('invisible');
			if (layoutflag >= 900) {
				$('#playlistm').removeClass('invisible');
			}
			$('#'+source).removeClass('invisible');
			prefs.save({chooser: source});
			layoutProcessor.adjustLayout();
		},

		adjustLayout: async function() {
			infobar.updateWindowValues();
			var ws = getWindowSize();
			var hh = $("#headerbar").outerHeight(true);
			var mainheight = ws.y - hh;
			$("#loadsawrappers").css({height: mainheight+"px"});
			var infoheight = $('#infobar').outerHeight(true) - $('#cssisshit').outerHeight(true);
			$('#toomanywrappers').css({height: infoheight+"px"});
			// Work around crappy iOS Safari bug where it updates width css before height
			// and therefore doesn't get the album picture size right
			$('#albumpicture').css('width', '0px');
			await new Promise(r => setTimeout(r, 100));
			$('#albumpicture').css('width', '');
			layoutProcessor.setPlaylistHeight();
			browser.rePoint();
			$('.topdropmenu:visible').fanoogleTopMenus();
			if ($('.choose_playlist').css('font-weight') == '1000'
				&& $('.mainpane:visible').not('#infobar').length == 0
				&& (prefs.chooser == 'playlistm' || prefs.chooser == 'infobar')) {
				layoutProcessor.sourceControl('albumlist');
			}
			var np = $('#nowplaying');
			var nptop = np.offset().top;
			debug.debug('LAYOUT', 'nptop is',nptop);
			if (nptop > 0) {
				var t = infoheight - nptop + hh;
				np.css({height: t+"px"});
				infobar.rejigTheText();
			}
		},

		showTagButton: function() {
			return false;
		},

		displayCollectionInsert: function(details) {
			infobar.notify(
				(details.isaudiobook == 0) ? language.gettext('label_addedtocol') : language.gettext('label_addedtosw')
			);
			infobar.markCurrentTrack();
		},

		setProgressTime: function(stats) {
			makeProgressOfString(stats);
		},

		updateInfopaneScrollbars: function() {
		},

		setRadioModeHeader: function(html) {
			$("#plmode").html(html);
		},

		makeCollectionDropMenu: function(element, name) {
			var x = $('#'+name);
			// If the dropdown doesn't exist then create it
			if (x.length == 0) {
				if (element.hasClass('album1')) {
					var c = 'dropmenu notfilled album1';
				} else if (element.hasClass('album2')) {
					var c = 'dropmenu notfilled album2';
				} else {
					var c = 'dropmenu notfilled';
				}
				var ec = '';
				if (/aalbum/.test(name) || /aartist/.test(name)) {
					ec = ' removeable';
				}
				var t = $('<div>', {id: name, class: c+ec}).insertAfter(element);
			}
		},

		initialise: function() {

			if (!prefs.checkSet('clickmode')) {
				prefs.clickmode = 'single';
			}
			$(".dropdown").floatingMenu({ });
			$('.topbarmenu').on('click', function() {
				$('.autohide:visible').not('#'+$(this).attr('name')).slideToggle('fast');
				$('#'+$(this).attr('name')).slideToggle('fast', function() {
					$(this).fanoogleTopMenus();
				});
			});
			$('.autohide').on('click', function() {
				$(this).slideToggle('fast');
			});
			setControlClicks();
			$('.choose_nowplaying').on('click', function(){layoutProcessor.sourceControl('infobar')});
			$('.choose_albumlist').on('click', function(){layoutProcessor.sourceControl('albumlist')});
			$('.choose_searcher').on('click', function(){layoutProcessor.sourceControl('searchpane')});
			$('.choose_filelist').on('click', function(){layoutProcessor.sourceControl('filelist')});
			$('.choose_radiolist').on('click', function(){layoutProcessor.sourceControl('radiolist')});
			$('.choose_podcastslist').on('click', function(){layoutProcessor.sourceControl('podcastslist')});
			$('.choose_audiobooklist').on('click', function(){layoutProcessor.sourceControl('audiobooklist')});
			$('.choose_infopanel').on('click', function(){layoutProcessor.sourceControl('infopane')});
			$('.choose_playlistman').on('click', function(){layoutProcessor.sourceControl('playlistman')});
			$('.choose_pluginplaylists').on('click', function(){layoutProcessor.sourceControl('pluginplaylistholder')});
			$('.choose_prefs').on('click', function(){layoutProcessor.sourceControl('prefsm')});
			$('#choose_history').on('click', showHistory);
			$('.icon-rss.npicon').on('click', function(){podcasts.doPodcast('nppodiput')});
			$('.choose_playlist').on('click', function(){layoutProcessor.sourceControl('playlistm')});
			$("#ratingimage").on('click', nowplaying.setRating);
			$("#playlistname").parent().next('button').on('click', player.controller.savePlaylist);
			$('.clear_playlist').on('click', playlist.clear);
			$('#volume').volumeControl({
				orientation: 'horizontal',
				command: player.controller.volume
			});
			doSwipeCss();
			$(document).on('click', '.clickaddtoplaylist', addToPlaylist.close);
		},

		findAlbumDisplayer: function(key) {
			if (key == 'fothergill' || key == 'mingus') {
				return $('#'+key);
			} else {
				return $('.containerbox.album[name="'+key+'"]');
			}
		},

		findArtistDisplayer: function(key) {
			return $('div.menu[name="'+key+'"]');
		},

		insertAlbum: function(v) {
			debug.log('PHONE', 'Insert Album', v);
			var albumindex = v.id;
			var displayer = $('#'+albumindex);
			displayer.html(v.tracklist);
			uiHelper.makeResumeBar(displayer);
			layoutProcessor.findAlbumDisplayer(albumindex).remove();
			switch (v.type) {
				case 'insertAfter':
					debug.log("Insert After",v.where);
					$(v.html).insertAfter(layoutProcessor.findAlbumDisplayer(v.where));
					break;

				case 'insertAtStart':
					debug.log("Insert At Start",v.where);
					$(v.html).insertAfter($('#'+v.where).find('div.clickalbum[name="'+v.where+'"]'));
					break;
			}
			layoutProcessor.postAlbumMenu();
		},

		removeAlbum: function(key) {
			if ($('#'+key).length > 0) {
				$('#'+key).findParentScroller().restoreScrollPos();
				$('#'+key).remove();
			}
			layoutProcessor.findAlbumDisplayer(key).remove();
		},

		removeArtist: function(key) {
			if ($('#'+key).length > 0) {
				if (!$('#'+key).hasClass('configtitle')) {
					$('#'+key).findParentScroller().restoreScrollPos();
				}
				$('#'+key).remove();
			}
			layoutProcessor.findArtistDisplayer(key).remove();
		},

		albumBrowsed: function(menutoopen, data) {
			$('#'+menutoopen).html(data);
			layoutProcessor.postAlbumMenu();
		},

		fixupArtistDiv: function(jq, name) {
			jq.find('.menu.backmenu').attr('name', jq.attr('id'));
		},

		getElementPlaylistOffset: function(element) {
			var top = element.position().top;
			if (element.parent().hasClass('trackgroup')) {
				top += element.parent().position().top;
			}
			return top;
		},

		postPlaylistLoad: function() {
			if (prefs.playlistswipe) {
				$('#sortable .track').playlistTouchWipe({});
				$('#sortable .item').playlistTouchWipe({});
			} else {
				$('#pscroller').find('.icon-cancel-circled').each(function() {
					var d = $('<i>', {class: 'icon-updown playlisticonr fixed clickplaylist clickicon rearrange_playlist'}).insertBefore($(this));
				});
			}
		},

		postPodcastSubscribe: function(data, index) {
			$('.menuitem[name="podcast_'+index+'"]').fadeOut('fast', function() {
				$('.menuitem[name="podcast_'+index+'"]').remove();
				$('#podcast_'+index).remove();
				$("#fruitbat").html(data);
				podcasts.doNewCount();
			});
		},

		makeSortablePlaylist: function(id) {
		},

		maxAlbumMenuSize: function(element) {
			var ws = getWindowSize();
			ws.left = 0;
			ws.top = $('#headerbar').outerHeight(true);
			return ws;
		}

	}

}();

// Dummy functions standing in for widgets we don't use in this version -
// custom scroll bars, and drag/drop stuff
jQuery.fn.acceptDroppedTracks = function() {
	return this;
}

jQuery.fn.sortableTrackList = function() {
	return this;
}

jQuery.fn.trackDragger = function() {
	return this;
}

var addToPlaylist = function() {
	return {
		open: function() {
			$('#pladddropdown').slideDown('fast');
		},
		close: function() {
			$('#pladddropdown').slideUp('fast');
		}
	}
}();