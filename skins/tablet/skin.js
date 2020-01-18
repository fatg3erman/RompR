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
	return this.each(function() {
		if ($(this).is(':visible')) {
			$(this).css({height: ''});
			var top = $(this).offset().top;
			var height = $(this).outerHeight(true);
			var ws = getWindowSize();
			if (height > (ws.y - top)) {
				var nh = Math.min(height, ws.y - top);
				$(this).css({height: nh+'px'});
			}
		}
	});
}

jQuery.fn.addCustomScrollBar = function() {
	return this;
}

function showHistory() {
	$('#historypanel').slideToggle('fast');
}

var layoutProcessor = function() {

	function isLandscape() {
		if (window.innerHeight > window.innerWidth) {
			return false;
		} else {
			return true;
		}
	}

	return {

		supportsDragDrop: false,
		hasCustomScrollbars: false,
		usesKeyboard: false,
		sortFaveRadios: false,
		openOnImage: false,

		changeCollectionSortMode: function() {
			collectionHelper.forceCollectionReload();
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
			var newheight = $("#playlistm").height() - $("#horse").outerHeight(true);
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
			// hacky - set an irrelevant css parameter as a flag so we change behaviour
			var layoutflag = parseInt($('i.choosepanel[name="playlistm"]').css('font-weight'));
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
			// Work around crappy iOS Safari bug where it updates width css before height
			// and therefore doesn't get the album picture size right
			$('#albumpicture').css('width', '0px');
			$('#albumpicture').css('width', '');
			var np = $('#nowplaying');
			var nptop = np.offset().top;
			if (nptop > 0) {
				var t = infoheight - nptop + hh;
				np.css({height: t+"px"});
				infobar.biggerize();
			}
			layoutProcessor.setPlaylistHeight();
			browser.rePoint();
			$('.topdropmenu:visible').fanoogleTopMenus();
			if ($('i.choosepanel[name="playlistm"]').css('font-weight') == '1000'
				&& $('.mainpane:visible').not('#infobar').length == 0
				&& (prefs.chooser == 'playlistm' || prefs.chooser == 'infobar')) {
				uiHelper.sourceControl('albumlist');
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

		updateInfopaneScrollbars: function() {
		},

		setRadioModeHeader: function(html) {
			$("#plmode").html(html);
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
			$('#choose_history').on('click', showHistory);
			$('#volume').volumeControl({
				orientation: 'horizontal',
				command: player.controller.volume
			});
			$(document).on('click', '.clickaddtoplaylist', addToPlaylist.close);
		},

		postPlaylistLoad: function() {
			$('#pscroller').find('.icon-cancel-circled').each(function() {
				var d = $('<i>', {class: 'icon-updown playlisticonr fixed clickplaylist clickicon rearrange_playlist'}).insertBefore($(this));
			});
		},

		getElementPlaylistOffset: function(element) {
			return element.position().top;
		},

		createPluginHolder: function(icon, title, id, panel) {
			$('<i>', {class: 'onlywide topimg expand '+icon}).insertBefore('i[name="specialplugins"]').on('click', function() {uiHelper.sourceControl(panel)});
			return $('<i>', {class: 'noshrink topimg tright '+icon}).appendTo('#narrowscreenicons').on('click', function() {uiHelper.sourceControl(panel)});
		},

		makeDropHolder: function(name) {
			return $('<div>', {class: 'scroller mainpane invisible pright', id: name}).insertBefore('#playlistm');
		},

		makeSortablePlaylist: function(id) {
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

var shortcuts = function() {
	return {
		add: function(a,b,c) {

		}
	}
}();

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
