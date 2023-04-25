jQuery.fn.menuReveal = async function() {
	var self = $(this);
	if (self.hasClass('toggledown') || self.hasClass('search_result_box')) {
		await self.slideToggle('fast').promise();
	} else {
		self.findParentScroller().saveScrollPos();
		self.siblings().addClass('menu-covered');
		if (self.parent().hasClass('search_result_box')) {
			self.parent().siblings('.search_result_box').addClass('menu-covered');
		}
		self.makeTimerSpan();
		await self.show(0).promise();
	}
	return this;
}

jQuery.fn.makeTimerSpan = function() {
	var self = $(this);
	var tt = self.find('input.albumtime').val();
	if (tt) {
		var d = $('<div>', {class: 'album-time'}).html('<span class="timer-time">'+tt+'</span>').appendTo(self);
		$('<i>', {class: 'icon-blank timerspacer'}).appendTo(d);
	}
	return this;
}

jQuery.fn.menuHide = async function() {
	var self = $(this);
	if (self.hasClass('toggledown') || self.hasClass('search_result_box')) {
		await self.slideToggle('fast').promise();
	} else {
		self.siblings().removeClass('menu-covered');
		if (self.parent().hasClass('search_result_box')) {
			self.parent().siblings('.search_result_box').removeClass('menu-covered');
		}
		await self.hide(0).promise();
		self.findParentScroller().restoreScrollPos();

		if (self.hasClass('removeable'))
			self.remove();

	}
	return this;
}

jQuery.fn.isOpen = function() {
	if (this.hasClass('icon-toggle-open')) {
		return true;
	} else if (this.hasClass('icon-toggle-closed')) {
		return false;
	} else if (this.hasClass('backmenu') || $('#'+this.attr('name')).is(':visible')) {
		return true;
	} else {
		return false;
	}
}

jQuery.fn.isClosed = function() {
	if (this.hasClass('icon-toggle-open')) {
		return false;
	} else if (this.hasClass('icon-toggle-closed')) {
		return true;
	} else if (this.hasClass('backmenu') || $('#'+this.attr('name')).is(':visible')) {
		return false;
	} else {
		return true;
	}
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
		var holder = $('<div>', { class: "expand dropdown-menu-holder"}).appendTo($(this));
		var dropbutton = $('<i>', { class: 'fixed phone-dropdown-button'}).appendTo($(this));
		var textbox = $('<input>', { type: "text", autocomplete: 'off', class: tbc, name: settings.textboxname, placeholder: unescapeHtml(settings.placeholder) }).appendTo(holder);
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
		self.siblings().removeClass('menu-covered');
		self.clearOut().remove();
	});
}

jQuery.fn.removeCollectionItem = function() {
	this.each(function() {
		// $(this).siblings().removeClass('menu-covered');
		$(this).clearOut().remove();
	});
}

jQuery.fn.insertAlbumAfter = function(albumindex, html, tracklist) {
	// Somewhat easy in the phone skin - if the dropdown (#albumindex)
	// exists then it must be open. And it doesn't matter if we insert the
	// album before or after the dropdown as it gets removed when we close it
	return this.each(function() {
		var me = $(this);
		var is_covered = $('.openmenu[name="'+albumindex+'"]').hasClass('menu-covered');
		$('.openmenu[name="'+albumindex+'"]').removeCollectionItem();
		$('#'+albumindex).html(tracklist).updateTracklist().scootTheAlbums().makeTimerSpan();
		$(html).insertAfter(me).scootTheAlbums();
		if (is_covered)
			$('.openmenu[name="'+albumindex+'"]').not('.backmenu').addClass('menu-covered');
	});
};

jQuery.fn.insertAlbumAtStart = function(albumindex, html, tracklist) {
	return this.each(function() {
		var me = $(this);
		var is_covered = $('.openmenu[name="'+albumindex+'"]').hasClass('menu-covered');
		$('.openmenu[name="'+albumindex+'"]').removeCollectionItem();
		$('#'+albumindex).html(tracklist).updateTracklist().scootTheAlbums();
		$(html).insertAfter(me.children('.vertical-centre.configtitle').next()).scootTheAlbums().makeTimerSpan();
		if (is_covered)
			$('.openmenu[name="'+albumindex+'"]').not('.backmenu').addClass('menu-covered');
	});
}

jQuery.fn.insertArtistAfter = function(html) {
	return this.each(function() {
		$(html).insertAfter($(this));
	});
}

function showHistory() {
	$('#historyholder').slideToggle('fast');
}

var layoutProcessor = function() {

	var oldchooser = 'albumlist';

	var infobarObserver = new IntersectionObserver(function(entries, me) {
		entries.forEach(entry => {
			if (entry.isIntersecting) {
				infobar.rejigTheText();
			}
		});
	});

	return {

		sortFaveRadios: false,
		openOnImage: true,
		playlist_scroll_parent: '#pscroller',
		needs_playlist_help: false,

		changeCollectionSortMode: function() {
			collectionHelper.forceCollectionReload();
		},

		afterHistory: function() {
			browser.rePoint();
		},

		addInfoSource: function(name, obj) {
			$("#chooserbuttons").append($('<i>', {
				onclick: "browser.switch_source('"+name+"')",
				class: obj.icon+' topimg expand',
				id: "button_source"+name
			}));
		},

		setupInfoButtons: function() { },

		goToBrowserPlugin: function(panel) {
			uiHelper.sourceControl('infopane');
			uiHelper.goToBrowserPanel(panel);
		},

		notifyAddTracks: function() {
			var display_mode = get_css_variable('--display-mode');
			if (display_mode != 2)
				infobar.notify(language.gettext("label_addingtracks"));
		},

		hidePanel: function(panel, is_hidden, new_state) { },

		panelMapping: function() {
			return {
				"albumlist": 'albumlist',
				"searcher": 'searchpane',
				"filelist": 'filelist',
				"radiolist": 'radiolist',
				"audiobooklist": "audiobooklist",
				"podcastslist": 'podcastslist',
				"playlistslist": 'playlistman',
				"pluginplaylistslist": 'pluginplaylistholder'
			}
		},

		sourceControl: function(source) {
			debug.mark('LAYOUT','Switching source to',source);

			// NOTE. The css is rigged as follows:
			// Infopane is first in the layout. If it's visible all its siblings are hidden in CSS
			// infobar has a display of flex set to override the invisible class, except on small screens
			// where we do want to hide it.
			// playlistm is not a mainpane so in 3 column layout it never gets hidden, in other
			// layouts it's last so it just gets pushed off the bottom.

			if (source == 'infopane') {
				$('#infopane').removeClass('invisible');
				browser.rePoint();
			} else {
				var display_mode = get_css_variable('--display-mode');
				if (display_mode == 2 && (source == 'infobar' || source == 'playlistm')) {
					source = oldchooser;
					debug.mark('LAYOUT','Actually Switching source to',source);
				}
				$('.mainpane:not(.invisible):not(#'+source+')').removeClass('invisible').addClass('invisible');
				$('#'+source).removeClass('invisible');
				oldchooser = source;
				// Need to do this here - at the very least we need to reig the text
				// because we might be switching to infobar from another panel
				// We're now using intersectionObserver to do this, as tere's some timing thing
				// that means doing it here doesn't always work.
				// infobar.rejigTheText();
			}
			prefs.save({chooser: source});
		},

		mobile_browser_shitness: function() {
			// This exists because Safari on iOS hides it address bar and button bar
			// but still gives us a height that suggests they're visible, so this forces it to
			// re-show them, or hide them, or something, it's not clear :)
			window.scrollTo(0,1);
		},

		adjustLayout: async function() {
			// When rotating from portrait to landscape in 3 column mode, if we're viewing
			// the tracklist in porttrait mode we need to re-display a chooser panel  or
			// we end up with only 2 columns
			var display_mode = get_css_variable('--display-mode');
			if (display_mode == 2 && $('.mainpane:visible').not('#infobar').length == 0)
				layoutProcessor.sourceControl('albumlist');

			browser.rePoint();
			infobar.rejigTheText();
			setTimeout(layoutProcessor.mobile_browser_shitness, 500);
		},

		displayCollectionInsert: function(details) {
			infobar.notify(
				(details.isaudiobook == 0) ? language.gettext('label_addedtocol') : language.gettext('label_addedtosw')
			);
		},

		setRadioModeHeader: function(html) {
			$("#plmode").html(html);
		},

		setFloaterPosition: function(e, p) {

		},

		makeCollectionDropMenu: function(element, name) {
			var c = 'dropmenu notfilled is-albumlist';
			if (
				/^[abz](album|artist|genre|rating|tag)/.test(name) ||
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
			$('.topbarmenu').on('click', function(event) {
				event.stopPropagation();
				$('.autohide:visible').not('#'+$(this).attr('name')).slideToggle('fast');
				$('#'+$(this).attr('name')).slideToggle('fast');
			});
			$('.autohide').not('.notonclick').on('click', function() {
				$(this).slideToggle('fast');
			});
			$('#choose_history').on('click', showHistory);
			$('#volume').volumeControl({
				orientation: 'horizontal',
				command: player.controller.volume
			});
			$('#infobar').get().forEach(d => infobarObserver.observe(d));
		},

		makeSortablePlaylist: function(id) {
		},

		maxAlbumMenuSize: function(element) {
			var ws = getWindowSize();
			ws.left = 0;
			ws.top = 0;
			ws.y -= $('#headerbar').outerHeight(true);
			return ws;
		},

		createPluginHolder: function(icon, title, id, panel) {
			var i = $('<i>', {class: 'topimg expand topbarmenu', name: panel, id: id}).appendTo('#pluginicons');
			i.addClass(icon);
			var j = $('<i>', {class: 'topimg tright topbarmenu', name: panel}).appendTo('#narrowscreenicons');
			j.addClass(icon);
			return i;
		},

		makeDropHolder: function(name, d, dontsteal, withscroll, wide) {
			return $('<div>', {class: 'top_drop_menu widemenu rightmenu dropshadow autohide notonclick', 'id': name}).appendTo($('#sourcescontrols'));
		}

	}

}();

// Dummy functions standing in for widgets we don't use in this version -
// custom scroll bars, and drag/drop stuff
// jQuery.fn.acceptDroppedTracks = function() {
// 	return this;
// }

jQuery.fn.sortableTrackList = function() {
	return this;
}

jQuery.fn.trackDragger = function() {
	return this;
}

var addToPlaylist = function() {
	return {
		open: function() {
			if ($('#pladddropdown').is(':visible')) {
				$('#pladddropdown').slideUp('fast');
			} else {
				$('#pladddropdown').slideDown('fast');
			}
		},
		close: function() {
			$('#pladddropdown').slideUp('fast');
		}
	}
}();
