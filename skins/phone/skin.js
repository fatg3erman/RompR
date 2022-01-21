jQuery.fn.menuReveal = async function() {
	var self = $(this);
	if (self.hasClass('toggledown')) {
		await self.slideToggle('fast').promise();
	} else {
		self.findParentScroller().saveScrollPos();
		self.siblings().addClass('menu-covered');
		// GOD, CSS is so dumb. This would work IF there was an 'all siblings' selector, but no.
		// CSS, in it sinfinite "wisdom" decided that you only ever need to select
		// siblings that come AFTER the current element. We can't put the holder at the start of the div
		// because that breaks the logic for updating and inserting albums
		// self.addClass('menu-current');
		if (self.prev().hasClass('subscribed-podcast'))
			$('#podcast_search').addClass('menu-covered');
		if (self.prev().hasClass('unsubscribed-podcast'))
			$('#podholder').addClass('menu-covered');
		var tt = self.find('input.albumtime').val();
		if (tt) {
			var d = $('<div>', {class: 'album-time'}).html(tt).appendTo(self);
			$('<i>', {class: 'icon-blank timerspacer'}).appendTo(d);
		}
		await self.show(0).promise();
	}
	return this;
}

jQuery.fn.menuHide = async function() {
	var self = $(this);
	if (self.hasClass('toggledown')) {
		await self.slideToggle('fast').promise();
	} else {
		self.siblings().removeClass('menu-covered');
		// self.removeClass('menu-current');
		if (self.prev().hasClass('subscribed-podcast'))
			$('#podcast_search').removeClass('menu-covered');
		if (self.prev().hasClass('unsubscribed-podcast'))
			$('#podholder').removeClass('menu-covered');
		await self.hide(0).promise();
		self.findParentScroller().restoreScrollPos();
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
		if (self.find('.wafflything').length > 0) {
			var waffler = self.find('.wafflything');
			if (!waffler.children('.wafflebanger').first().hasClass("wafflebanger-moving")) {
				waffler.fadeIn(100).children('.wafflebanger').addClass('wafflebanger-moving');
			}
		} else if (self.hasClass('icon-toggle-closed') || self.hasClass('icon-toggle-open') || self.hasClass('spinable')) {
			if (self.hasClass('icon-spin6') || self.hasClass('spinner')) {
				debug.trace('UIHELPER', 'Trying to create spinner on already spinning element');
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
		} else if (self.find('.wafflything').length > 0) {
			var waffler = self.find('.wafflything');
			waffler.hide().children('.wafflebanger').removeClass('wafflebanger-moving');
			self.removeClass('clickflash');
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
	// this.children('.is-coverable').addClass('menu-covered');
	this.addClass('menu-opened');
}

jQuery.fn.restoreScrollPos = function() {
	var a = this.find('input[name="restorescrollpos"]');
	if (a.length > 0) {
		this.css('overflow-y', 'scroll');
		this.scrollTop(a.val());
		this.children('.backmenu').css({position: ''});
		// this.children('.menu-covered').removeClass('menu-covered');
		this.removeClass('menu-opened');
		a.remove();
	}
	// $('#popupmenu').remove();
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
		var dropbutton = $('<i>', { class: 'fixed phone-dropdown-button'}).appendTo($(this));
		var textbox = $('<input>', { type: "text", class: tbc, name: settings.textboxname, placeholder: unescapeHtml(settings.placeholder) }).appendTo(holder);
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

jQuery.fn.removeCollectionDropdown = function() {
	this.each(function() {
		var self = $(this);
		if (!self.hasClass('configtitle')) {
			self.findParentScroller().restoreScrollPos();
		}
		self.siblings().show();
		self.clearOut().remove();
	});
}

jQuery.fn.removeCollectionItem = function() {
	this.each(function() {
		$(this).clearOut().remove();
	});
}

jQuery.fn.insertAlbumAfter = function(albumindex, html, tracklist) {
	// Somewhat easy in the phone skin - if the dropdown (#albumindex)
	// exists then it must be open. And it doesn't matter if we insert the
	// album before or after the dropdown as it gets removed when we close it
	return this.each(function() {
		var me = $(this);
		$('.openmenu[name="'+albumindex+'"]').removeCollectionItem();
		$('#'+albumindex).html(tracklist).updateTracklist().scootTheAlbums();
		$(html).insertAfter(me).scootTheAlbums();
	});
};

jQuery.fn.insertAlbumAtStart = function(albumindex, html, tracklist) {
	return this.each(function() {
		var me = $(this);
		$('.openmenu[name="'+albumindex+'"]').removeCollectionItem();
		$('#'+albumindex).html(tracklist).updateTracklist().scootTheAlbums();
		$(html).insertAfter(me.children('.vertical-centre.configtitle').next()).scootTheAlbums();
	});
}

jQuery.fn.insertArtistAfter = function(html) {
	return this.each(function() {
		$(html).insertAfter($(this));
	});
}

jQuery.fn.addCustomScrollBar = function() {
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
			debug.info("TOUCHWIPE","Long Press");
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
	$('#historyholder').slideToggle('fast');
}

var layoutProcessor = function() {

	// var oldwindowsize = {x: 0, y: 0};
	var oldchooser = '';

	function isLandscape() {
		if (window.innerHeight > window.innerWidth) {
			return false;
		} else {
			return true;
		}
	}

	function doSwipeCss() {
		$('style[id="playlist_swipe"]').remove();
		if (prefs.playlistswipe) {
			$('<style id="playlist_swipe">#sortable .playlisticonr.icon-cancel-circled { display: none }</style>').appendTo('head');
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

		afterHistory: function() {
			browser.rePoint();
			// showHistory();
		},

		addInfoSource: function(name, obj) {
			$("#chooserbuttons").append($('<i>', {
				onclick: "browser.switch_source('"+name+"')",
				class: obj.icon+' topimg expand',
				id: "button_source"+name
			}));
		},

		setupInfoButtons: function() { },

		goToBrowserPanel: function(panel) {
			$('#infopane').scrollTo('#'+panel+'information',800,{easing: 'swing'});
		},

		goToBrowserPlugin: function(panel) {
			uiHelper.sourceControl('infopane');
			layoutProcessor.goToBrowserPanel(panel);
		},

		goToBrowserSection: function(section) {
			// Wikipedia mobile does not return contents
		},

		notifyAddTracks: function() {
			infobar.notify(language.gettext("label_addingtracks"));
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
			var newheight = $("#playlistm").height() - $("#playlist_top").outerHeight(true);
			if ($("#playlistbuttons").is(":visible")) {
				newheight = newheight - $("#playlistbuttons").outerHeight(true) - 2;
			}
			$("#pscroller").css("height", newheight.toString()+"px");
		},

		scrollPlaylistToCurrentTrack: function() {
			var scrollto = playlist.getCurrentTrackElement();
			if (prefs.scrolltocurrent && scrollto.length > 0) {
				var offset = 0 - ($('#pscroller').outerHeight(true) / 2);
				$('#pscroller').scrollTo(scrollto, 800, {offset: {top: offset}, easing: 'swing'});
			}
		},

		sourceControl: function(source) {
			debug.mark('LAYOUT','Switching source to',source);
			var display_mode = get_css_variable('--display-mode');
			// var layoutflag = parseInt($('i.choosepanel[name="playlistm"]').css('font-weight'));
			if (display_mode == 2) {
				if (source == "playlistm")
					source = "infobar";

				if (source == 'infobar' && prefs.chooser != 'infopane')
					return;
			}

			// if (source == 'infopane') {
			// 	$('#infobar').css('display', 'none');
			// 	if (display_mode > 0) {
			// 		$('#playlistm').css('display', 'none');
			// 	}
			// } else {
			// 	$('#infobar').css('display', '');
			// 	if (display_mode > 0) {
			// 		$('#playlistm').css('display', '');
			// 	}
			// }
			$('.mainpane:not(.invisible):not(#'+source+')').addClass('invisible');
			// if (display_mode > 0) {
			// 	$('#playlistm').removeClass('invisible');
			// }
			$('#'+source).removeClass('invisible');
			prefs.save({chooser: source});
			uiHelper.adjustLayout();
		},

		adjustLayout: async function() {
			infobar.updateWindowValues();
			var ws = getWindowSize();
			var hh = $("#headerbar").outerHeight(true);
			var mainheight = ws.y - hh;
			$("#loadsawrappers").css({height: mainheight+"px"});
			var infoheight = $('#infobar').outerHeight(true) - $('#cssisshit').outerHeight(true);
			$('#toomanywrappers').css({height: infoheight+"px"});
			oldchooser = prefs.chooser;
			layoutProcessor.setPlaylistHeight();
			browser.rePoint();

			// don't do this if we're on a mobile device and the window is being hidden or device is going to sleep
			if (get_css_variable('--display-mode') == 2
				&& $('.mainpane:visible').not('#infobar').length == 0
				&& (prefs.chooser == 'playlistm' || prefs.chooser == 'infobar')
				&& sleepHelper.isVisible()) {
					uiHelper.sourceControl('albumlist');
			}

			var np = $('#nowplaying');
			var nptop = np.offset().top;
			debug.debug('LAYOUT', 'nptop is',nptop);
			if (nptop > 0) {
				var t = Math.min(250, (infoheight - nptop + hh));
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
		},

		updateInfopaneScrollbars: function() {
		},

		setRadioModeHeader: function(html) {
			$("#plmode").html(html);
		},

		makeCollectionDropMenu: function(element, name) {
			var c = 'dropmenu notfilled is-albumlist';
			if (
				/^[abz](album|artist)/.test(name) ||
				element.hasClass('directory') ||
				element.hasClass('playlist') ||
				element.hasClass('userplaylist')
			) {
				c += ' removeable';
			}
			return $('<div>', {id: name, class: c}).insertAfter(element);
		},

		initialise: function() {
			$(".dropdown").floatingMenu({ });
			$('.topbarmenu').on('click', function() {
				$('.autohide:visible').not('#'+$(this).attr('name')).slideToggle('fast');
				$('#'+$(this).attr('name')).slideToggle('fast');
			});
			$('.autohide').on('click', function() {
				$(this).slideToggle('fast');
			});
			$('#choose_history').on('click', showHistory);
			$('#volume').volumeControl({
				orientation: 'horizontal',
				command: player.controller.volume
			});
			doSwipeCss();
			$(document).on('click', '.clickaddtoplaylist', addToPlaylist.close);
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
