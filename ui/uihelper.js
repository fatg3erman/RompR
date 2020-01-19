// skin.js may redefine these jQuery functionss if necessary

jQuery.fn.menuReveal = async function() {
	await $(this).slideToggle('fast').promise();
	return this;
}

jQuery.fn.menuHide = async function() {
	await $(this).slideToggle('fast').promise();
	return this;
}

jQuery.fn.isOpen = function() {
	return this.hasClass('icon-toggle-open');
}

jQuery.fn.isClosed = function() {
	return this.hasClass('icon-toggle-closed');
}

jQuery.fn.toggleOpen = function() {
	if (this.hasClass('icon-toggle-closed')) {
		this.removeClass('icon-toggle-closed').addClass('icon-toggle-open');
	}
	return this;
}

jQuery.fn.toggleClosed = function() {
	if (this.hasClass('icon-toggle-open')) {
		this.removeClass('icon-toggle-open').addClass('icon-toggle-closed');
	}
	return this;
}

jQuery.fn.makeSpinner = function() {
	return this.each(function() {
		var self = $(this);
		if (self.find('.wafflything').length > 0) {
			var waffler = self.find('.wafflything');
			if (!waffler.children('.wafflebanger').first().hasClass("wafflebanger-moving")) {
				waffler.fadeIn(100).children('.wafflebanger').addClass('wafflebanger-moving');
			}
		} else {
			if (self.hasClass('icon-spin6') || $(this).hasClass('spinner')) {
				debug.debug('UIHELPER', 'Trying to create spinner on already spinning element');
				return;
			}
			var originalclasses = new Array();
			var classes = '';
			if (self.attr("class")) {
				var classes = self.attr("class").split(/\s/);
			}
			for (let c of classes) {
				if (c == "invisible" || (/^icon/.test(c))) {
					originalclasses.push(c);
					self.removeClass(c);
				}
			}
			self.attr("originalclass", originalclasses.join(" "));
			self.addClass('icon-spin6 spinner');
		}
	});
}

jQuery.fn.stopSpinner = function() {
	return this.each(function() {
		var self = $(this);
		if (self.find('.wafflything').length > 0) {
			var waffler = self.find('.wafflything');
			waffler.hide().children('.wafflebanger').removeClass('wafflebanger-moving');
		} else {
			self.removeClass('icon-spin6 spinner');
			if (self.attr("originalclass")) {
				self.addClass(self.attr("originalclass"));
				self.removeAttr("originalclass");
			}
		}
	});
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
		var tbc = "enter combobox-entry";
		if (settings.textboxextraclass) {
			tbc = tbc + " "+settings.textboxextraclass;
		}
		if (settings.labelhtml != '') {
			$(this).append(settings.labelhtml);
		}
		var holder = $('<div>', { class: "expand"}).appendTo($(this));
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

		dropbox.mCustomScrollbar({
		theme: "light-thick",
		scrollInertia: 120,
		contentTouchScroll: 25,
		advanced: {
			updateOnContentResize: true,
			updateOnImageLoad: false,
			autoScrollOnFocus: false,
			autoUpdateTimeout: 500,
		}
		});
		textbox.on('click', function(ev) {
			ev.preventDefault();
			ev.stopPropagation();
			var position = getPosition(ev);
			// This function relies on the fact that the size of the background image
			// that provides the icon we want to click on is 50% of the height of the element,
			// as defined in the icon theme css
			var elemright = textbox.width() + textbox.offset().left;
			var elh = textbox.height()/2+2;
			if (position.x > elemright - elh) {
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
						dropbox.slideToggle('fast', function() {
							dropbox.mCustomScrollbar("update");
						});
					});
				}
			}
		});
	});
}

jQuery.fn.fanoogleMenus = function() {
	return this.each( function() {
		if ($(this).is(':visible')) {
			// Fucking css, what a pile of shit
			debug.log('FANOOGLING', $(this).prop('id'));
			var pt = parseInt($(this).css('padding-top')) + parseInt($(this).css('padding-bottom')) +
				parseInt($(this).css('border-top-width')) + parseInt($(this).css('border-bottom-width'));
			var top = $(this).children().first().children('.mCSB_container').offset().top;
			var conheight = $(this).children().first().children('.mCSB_container').height();
			var ws = getWindowSize();
			var avheight = ws.y - top;
			var nh = (conheight+pt+8);
			if (nh > avheight) {
				$(this).css({height: avheight+"px"});
			} else {
				// Seems like we need an 8 pixel fudge factor to stop scollbars appearing on
				// menus that don't need scrollbars. Not sure why this is. If it wasn't for that
				// we could just unset the css height attribute like we do on the phone skin
				$(this).css({height: nh+"px"});
				// $(this).css({height: ''});
			}
			$(this).mCustomScrollbar("update");
		}
	});
}

jQuery.fn.addBunnyEars = function() {
	return this.each(function() {
		if ($(this).hasBunnyEars()) {
			$(this).removeBunnyEars();
		} else {
			var w = $(this).outerWidth(true);
			var up = $('<div>', { class: 'playlistup containerbox clickplaylist'}).prependTo($(this));
			up.html('<i class="icon-increase medicon expand"></i>').css('width', w+'px');
			var down = $('<div>', { class: 'playlistdown containerbox clickplaylist'}).appendTo($(this));
			down.html('<i class="icon-decrease medicon expand"></i>').css('width', w+'px');
			$(this).addClass('highlighted');
			if ($(this).hasClass('item')) {
				$(this).next().addClass('highlighted').slideUp('fast');
			}
		}
	});
}

jQuery.fn.hasBunnyEars = function() {
	if ($(this).find('.playlistup').length > 0) {
		return true;
	} else {
		return false;
	}
}

jQuery.fn.removeBunnyEars = function() {
	this.each(function() {
		$(this).find('.playlistup').remove();
		$(this).find('.playlistdown').remove();
		$(this).removeClass('highlighted');
		if ($(this).hasClass('item')) {
			$(this).next().removeClass('highlighted');
		}
	});
	playlist.doPopMove();
	return this;
}

jQuery.fn.removeCollectionDropdown = function() {
	this.each(function() {
		$(this).clearOut().remove();
	});
}

jQuery.fn.removeCollectionItem = function() {
	this.each(function() {
		$(this).parent().clearOut().remove();
	});
}

jQuery.fn.insertAlbumAfter = function(albumindex, html, tracklist) {
	// We don't just insert and then fake a click if it was open because we use
	// animations and that looks shit.
	return this.each(function() {
		// albumindex is the index of the NEW album
		var me = $(this).parent();
		var isopen = $('#'+albumindex).is(':visible');
		$('.openmenu[name="'+albumindex+'"]').removeCollectionItem();
		var location = me.next().hasClass('dropmenu') ? me.next() : me;
		var newthing = $(html).insertAfter(location).scootTheAlbums();
		if (isopen) {
			var dropdown = $('#'+albumindex).detach().insertAfter(newthing).html(tracklist).updateTracklist();
			newthing.find('.openmenu').toggleOpen();
			debug.trace('UIHELPER', 'Album contents have been replaced');
		} else {
			$('#'+albumindex).removeCollectionDropdown();
		}
	});
}

jQuery.fn.insertAlbumAtStart = function(albumindex, html, tracklist) {
	return this.each(function() {
		var me = $(this);
		var isopen = $('#'+albumindex).is(':visible');
		$('.openmenu[name="'+albumindex+'"]').removeCollectionItem();
		var newthing = $(html).prependTo(me).scootTheAlbums();
		if (isopen) {
			var dropdown = $('#'+albumindex).detach().insertAfter(newthing).html(tracklist).updateTracklist();
			newthing.find('.openmenu').toggleOpen();
			debug.trace('UIHELPER', 'Album contents have been replaced');
		} else {
			$('#'+albumindex).removeCollectionDropdown();
		}
	});
}

jQuery.fn.insertArtistAfter = function(html) {
	return this.each(function() {
		var me = $(this).parent();
		var location = me.next().hasClass('dropmenu') ? me.next() : me;
		$(html).insertAfter(location);
	});
}

jQuery.fn.addCustomScrollBar = function() {
	return this.each(function() {
		$(this).mCustomScrollbar({
			theme: "light-thick",
			scrollInertia: 300,
			contentTouchScroll: 25,
			mouseWheel: {
				scrollAmount: parseInt(prefs.wheelscrollspeed),
			},
			alwaysShowScrollbar: 1,
			advanced: {
				updateOnContentResize: true,
				updateOnImageLoad: false,
				autoScrollOnFocus: false,
				autoUpdateTimeout: 500,
			}
		});
	});
}

jQuery.fn.doThingsAfterDisplayingListOfAlbums = function() {
	return this;
}

jQuery.fn.doSomethingUseful = function(text) {
	return this.each(function() {
		var self = $(this);
		var useful = $('<div>', {class: 'bar brick_wide fullwidth'});
		if (self.prop('id')) {
			useful.prop('id', 'spinner_'+self.prop('id'));
		}
		useful.append('<div class="menuitem textcentre">'+text+'</div>');
		useful.append('<div class="progressbar wafflything"><div class="wafflebanger wafflebanger-moving"></div></div>');
		useful.appendTo(self);
	});
}


// Functions that could just be in layoutProcessor, but it makes maintenance easier
// if we have a proxy like this so we don't have to add new stuff to every single skin.

var uiHelper = function() {

	return {

		adjustLayout: function() {
			if (startBackgroundInitTasks.readytogo) {
				layoutProcessor.adjustLayout();
			}
		},

		insertAlbum: function(v) {
			debug.info('UIHELPER', 'Inserting Album', v.id);
			switch (v.type) {
				case 'insertAfter':
					debug.log('UIHELPER', "Insert After",v.where);
					$('.openmenu[name="'+v.where+'"]').insertAlbumAfter(v.id, v.html, v.tracklist);
					break;

				case 'insertAtStart':
					debug.log('UIHELPER', "Insert At Start",v.where);
					$('#'+v.where).insertAlbumAtStart(v.id, v.html, v.tracklist);
					break;
			}
		},

		insertArtist: function(v) {
			debug.info('UIHELPER', 'Inserting Artist', v.id);
			switch (v.type) {
				case 'insertAfter':
					debug.log('UIHELPER', 'Insert After', v.where);
					$('.openmenu[name="'+v.where+'"]').insertArtistAfter(v.html);
					break;

				case 'insertAtStart':
					debug.log('UIHELPER', 'Insert At Start', v.where);
					$(v.html).prependTo($('#'+v.where));
					break;

			}
		},

		removeFromCollection: function(key) {
			debug.info('UIHELPER', 'Removing',key);
			$('#'+key).removeCollectionDropdown();
			$('.openmenu[name="'+key+'"]').removeCollectionItem();
		},

		prepareCollectionUpdate: function() {
			try {
				return layoutProcessor.prepareCollectionUpdate();
			} catch (err) {
				$('#searchresultholder').empty();
			}
		},

		setProgressTime: function(stats) {
			try {
				layoutProcessor.setProgressTime(stats);
			} catch (err) {
				if (stats.progressString != "" && stats.durationString != "") {
					$("#playbackTime").html(stats.progressString + " " + frequentLabels.of + " " + stats.durationString);
				} else if (stats.progressString != "" && stats.durationString == "") {
					$("#playbackTime").html(stats.progressString);
				} else if (stats.progressString == "" && stats.durationString != "") {
					$("#playbackTime").html("0:00 " + frequentLabels.of + " " + stats.durationString);
				} else if (stats.progressString == "" && stats.durationString == "") {
					$("#playbackTime").html("");
				}
			}
		},

		postPlaylistLoad: function() {
			try {
				return layoutProcessor.postPlaylistLoad();
			} catch (err) {

			}
		},

		getElementPlaylistOffset: function(element) {
			try {
				return layoutProcessor.getElementPlaylistOffset(element);
			} catch (err) {

			}
		},

		createPluginHolder: function(icon, title, id, panel) {
			try {
				return layoutProcessor.createPluginHolder(icon, title, id, panel);
			} catch (err) {
				return false;
			}
		},

		makeDropHolder: function(name, d, dontsteal) {
			try {
				return layoutProcessor.makeDropHolder(name);
			} catch (err) {
				var c = 'topdropmenu dropshadow rightmenu normalmenu stayopen';
				if (dontsteal) {
					c += ' dontstealmyclicks';
				}
				return $('<div>', {class: c, id: name}).appendTo(d);
			}
		},

		panelMapping: function() {
			try {
				return layoutProcessor.panelMapping();
			} catch(err) {
				return {
					"albumlist": 'albumlist',
					"searcher": 'searcher',
					"filelist": 'filelist',
					"radiolist": 'radiolist',
					"podcastslist": 'podcastslist',
					"audiobooklist": 'audiobooklist',
					"playlistslist": 'playlistslist',
					"pluginplaylistslist": 'pluginplaylistslist'
				}
			}
		},

		makeResumeBar: function(target) {
			try {
				layoutProcessor.makeResumeBar(target);
			} catch(err) {
				target.find('input.resumepos').each(function() {
					var pos = parseInt($(this).val());
					var duration = parseInt($(this).next().val());
					debug.trace("UIHELPER", "Episode has a progress bar",pos,duration);
					var thething = $(
						'<div>',
						{
							class: 'containerbox fullwidth playlistrow2 dropdown-container podcastresume playable ',
							name: $(this).prev().attr('name')
						}
					).insertBefore($(this));
					thething.append('<div class="fixed padright">'+language.gettext('label_resume')+'</div>');
					var bar = $('<div>', {class: 'expand', style: "height: 0.5em"}).appendTo(thething);
					bar.rangechooser({range: duration, startmax: pos/duration, interactive: false});
				});

			}
		},

		showTagButton: function() {
			try {
				return layoutProcessor.showTagButton();
			} catch (err) {
				return true;
			}
		},

		makeSortablePlaylist: function(id) {
			try {
				return layoutProcessor.makeSortablePlaylist(id);
			} catch (err) {
				$('#'+id).sortableTrackList({
					items: '.playable',
					outsidedrop: playlistManager.dropOnPlaylist,
					insidedrop: playlistManager.dragInPlaylist,
					allowdragout: true,
					scroll: true,
					scrollparent: '#sources',
					scrollspeed: 80,
					scrollzone: 120
				});
				$('#'+id).acceptDroppedTracks({
					scroll: true,
					scrollparent: '#sources'
				});
			}
		},

		maxAlbumMenuSize: function(element) {
			try {
				return layoutProcessor.maxAlbumMenuSize(element);
			} catch (err) {
				var ws = getWindowSize();
				ws.left = 0;
				ws.top = $('#sources').offset().top;
				return ws;
			}
		},

		setupPersonalRadio: function() {
			try {
				return layoutProcessor.setupPersonalRadio();
			} catch (err) {

			}
		},

		playlistupdate: function(upcoming) {
			try {
				return layoutProcessor.playlistupdate(upcoming);
			} catch (err) {

			}
		},

		initialise: function() {
			if (prefs.outputsvisible) {
				layoutProcessor.toggleAudioOutpts();
			}
			if (layoutProcessor.supportsDragDrop) {
				setDraggable('#collection');
				setDraggable('#filecollection');
				setDraggable('#searchresultholder');
				setDraggable("#podcastslist");
				setDraggable("#audiobooks");
				setDraggable("#somafmlist");
				setDraggable("#communityradiolist");
				setDraggable("#icecastlist");
				setDraggable("#tuneinlist");
				setDraggable('#artistinformation');
				setDraggable('#albuminformation');
				setDraggable('#storedplaylists');
				document.body.addEventListener('drop', function(e) {
					e.preventDefault();
				}, false);
				$('#albumcover').on('dragenter', infobar.albumImage.dragEnter);
				$('#albumcover').on('dragover', infobar.albumImage.dragOver);
				$('#albumcover').on('dragleave', infobar.albumImage.dragLeave);
				$("#albumcover").on('drop', infobar.albumImage.handleDrop);
				$("#tracktimess").on('click', layoutProcessor.toggleRemainTime);
				$(document).on('mouseenter', '.clearbox', makeHoverWork);
				$(document).on('mouseleave', '.clearbox', makeHoverWork);
				$(document).on('mousemove', '.clearbox', makeHoverWork);
				$(document).on('mouseenter', '.combobox-entry', makeHoverWork);
				$(document).on('mouseleave', '.combobox-entry', makeHoverWork);
				$(document).on('mousemove', '.combobox-entry', makeHoverWork);
				// $(document).on('mouseenter', '.tooltip', makeToolTip);
				// $(document).on('mouseleave', '.tooltip', stopToolTip);
			}
			if (layoutProcessor.usesKeyboard) {
				shortcuts.load();
			}
			$('.combobox').makeTagMenu({textboxextraclass: 'searchterm cleargroup', textboxname: 'tag', populatefunction: tagAdder.populateTagMenu});
			$('.tagaddbox').makeTagMenu({textboxname: 'newtags', populatefunction: tagAdder.populateTagMenu, buttontext: language.gettext('button_add'), buttonfunc: tagAdder.add, placeholder: language.gettext('lastfm_addtagslabel')});
			$(window).on('resize', uiHelper.adjustLayout);
			layoutProcessor.initialise();
			setControlClicks();
			bindClickHandlers();
			setPlayClickHandlers();
			bindPlaylistClicks();
			showUpdateWindow();
		},

		changePanel: function() {
			uiHelper.sourceControl($(this).attr('name'));
		},

		sourceControl: function(panel) {
			layoutProcessor.sourceControl(panel);
				// HACK HACK HACK
			if (panel == 'radiolist') {
				$('#radiolist').scootTheAlbums();
			}
		},

		makeCollectionDropMenu: function(element, name) {
			try {
				return layoutProcessor.makeCollectionDropMenu(element, name);
			} catch (err) {
				if (element.parent().hasClass('album1')) {
					var c = 'dropmenu notfilled album1 is-albumlist';
				} else if (element.parent().hasClass('album2')) {
					var c = 'dropmenu notfilled album2 is-albumlist';
				} else {
					var c = 'dropmenu notfilled is-albumlist';
				}
				if (
					element.hasClass('directory') ||
					element.hasClass('playlist') ||
					element.hasClass('userplaylist')
				) {
					c += ' removeable';
				}
				return $('<div>', {id: name, class: c}).insertAfter(element.parent());
			}
		}

	}
}();
