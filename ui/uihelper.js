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
		var self = $(this);
		if (self.is(':visible')) {
			var top = self.offset().top;
			var ws = getWindowSize();
			var avheight = ws.y - top;
			self.css('max-height', avheight+'px');
		}
	});
}

jQuery.fn.addBunnyEars = function() {
	return this.each(function() {
		if ($(this).hasBunnyEars()) {
			$(this).removeBunnyEars();
		} else {
			var w = $(this).outerWidth(true);
			var prepto = $(this);
			if ($(this).hasClass('item'))
				prepto = prepto.children().first();

			var up = $('<div>', { class: 'playlistup containerbox clickplaylist vertical-centre'}).prependTo(prepto);
			up.html('<i class="icon-increase medicon expand"></i>').css('width', Math.round(w/3)+'px');
			var down = $('<div>', { class: 'playlistdown containerbox clickplaylist vertical-centre'}).appendTo(prepto);
			down.html('<i class="icon-decrease medicon expand"></i>').css('width', Math.round(w/3)+'px');
			$(this).addClass('highlighted bunnyears');
			if ($(this).hasClass('item')) {
				$(this).next().addClass('highlighted').slideUp('fast');
			}
		}
	});
}

jQuery.fn.hasBunnyEars = function() {
	return ($(this).hasClass('bunnyears'));
}

jQuery.fn.removeBunnyEars = function() {
	this.each(function() {
		$(this).find('.playlistup').remove();
		$(this).find('.playlistdown').remove();
		$(this).removeClass('highlighted bunnyears');
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
		if (!uiHelper.is_touch_ui) {
			$(this).mCustomScrollbar({
				theme: "light-thick",
				scrollInertia: 300,
				// contentTouchScroll: 25,
				mouseWheel: {
					scrollAmount: parseInt(prefs.wheelscrollspeed),
					// normalizeDelta: false
				},
				alwaysShowScrollbar: 1,
				advanced: {
					updateOnContentResize: true,
					updateOnImageLoad: false,
					autoScrollOnFocus: false,
					autoUpdateTimeout: 500,
				}
			});
			// 4 pixel high fudge div to prevent mCustomScrollbar putting scroll bars
			// on divs that don't need them
			$(this).find('.mCustomScrollBox').append('<div style="height:4px"></div>');
		}
	});
}

jQuery.fn.doThingsAfterDisplayingListOfAlbums = function() {
	// debug.log('UIHELPER', 'functionwithlongname');
	return this;
}

jQuery.fn.doSomethingUseful = function(text) {
	return this.each(function() {
		var self = $(this);
		var useful = $('<div>', {class: 'bar fullwidth'});
		if (self.prop('id')) {
			useful.prop('id', 'spinner_'+self.prop('id'));
		}
		useful.append('<div class="menuitem textcentre">'+text+'</div>');
		useful.append('<div class="progressbar wafflything"><div class="wafflebanger wafflebanger-moving"></div></div>');
		useful.appendTo(self);
	});
}

jQuery.fn.romprScrollTo = function(target, speed = 250) {
	return this.each(function() {
		if (uiHelper.is_touch_ui) {
			$(this).scrollTo(target, speed, {easing: 'swing'});
		} else {
			$(this).mCustomScrollbar('update');
			$(this).mCustomScrollbar(
				'scrollTo',
				target,
				{
					scrollInertia: speed,
					easing: 'swing'
				}
			);
		}
	});
}

// Functions that could just be in layoutProcessor, but it makes maintenance easier
// if we have a proxy like this so we don't have to add new stuff to every single skin.

var uiHelper = function() {

	function doSwipeCss() {
		set_css_variable('--playlist-right-icon', 'none');
	}

	return {

		is_touch_ui: false,

		adjustLayout: async function() {
			if (startBackgroundInitTasks.readytogo) {
				await layoutProcessor.adjustLayout();
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
					debug.log('UIHELPER', "Insert Album At Start",v.where,v.html);
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
				if (stats.progressString != "&nbsp;" && stats.durationString != "&nbsp;") {
					$("#playbackTime").html(stats.progressString + " " + frequentLabels.of + " " + stats.durationString);
				} else if (stats.progressString != "&nbsp;" && stats.durationString == "&nbsp;") {
					$("#playbackTime").html(stats.progressString);
				} else if (stats.progressString == "&nbsp;" && stats.durationString != "&nbsp;") {
					$("#playbackTime").html("0:00 " + frequentLabels.of + " " + stats.durationString);
				} else if (stats.progressString == "&nbsp;" && stats.durationString == "&nbsp;") {
					$("#playbackTime").html("");
				}
			}
		},

		postPlaylistLoad: function() {
			if (uiHelper.is_touch_ui) {
				$('#sortable .track').playlistTouchWipe({});
				$('#sortable .item').playlistTouchWipe({});
			}
		},

		getElementPlaylistOffset: function(element) {
			var top = element.position().top;
			if (element.parent().hasClass('trackgroup')) {
				top += element.parent().position().top;
			}
			return top;
		},

		createPluginHolder: function(icon, title, id, panel) {
			try {
				return layoutProcessor.createPluginHolder(icon, title, id, panel);
			} catch (err) {
				return false;
			}
		},

		makeDropHolder: function(name, d, dontsteal, withscroll, wide) {
			try {
				return layoutProcessor.makeDropHolder(name, d, dontsteal, withscroll, wide);
			} catch (err) {
				var c = 'top_drop_menu dropshadow rightmenu stayopen';
				if (dontsteal)
					c += ' dontstealmyclicks';

				if (!withscroll)
					c += ' noscroll';

				if (wide) {
					c += ' widemenu';
				} else {
					c += ' normalmenu';
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
					var name = $(this).next().next().val();
					var uri = $(this).next().next().next().val();
					var type = $(this).next().next().next().next().val()
					debug.trace("UIHELPER", "Episode has a progress bar",name,pos,duration);
					var thething = $(
						'<div>',
						{
							class: 'fullwidth podcastresume playable noselect',
							name: uri,
							resume: pos,
							bookmark: name
						}
					).insertBefore($(this));

					var textholder = $('<div>', {class: 'resumeinfo containerbox vertical-centre'}).appendTo(thething);
					textholder.append('<div class="tracknumber fixed"></div>');
					textholder.append('<i class="icon-bookmark inline-icon"></i>');
					textholder.append('<div class="expand playlistrow2">'+name+'</div>');

					textholder.append('<div class="fixed playlistrow2"> ('+formatTimeString(pos)+')</div>');

					if (type == 'podcast') {
						$('<i>', {
							class: 'icon-cancel-circled inline-icon clickable clickicon clickremovebookmark tright',
							name: name,
							uri: decodeURIComponent(uri)
						}).appendTo(textholder);
					}

					var barholder = $('<div>', {class: 'resume-bar-holder containerbox vertical-centre'}).appendTo(thething);
					barholder.append('<div class="tracknumber fixed"></div>');

					var bar = $('<div>', {class: 'resumebar'}).appendTo(barholder);
					bar.rangechooser({range: duration, startmax: pos/duration, interactive: false});

					// Remove the inputs as we don't need them any more
					$(this).next().remove();
					$(this).next().remove();
					$(this).next().remove();
					$(this).next().remove();
					$(this).remove();

				});

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

		updateInfopaneScrollbars: function() {
			if (!uiHelper.is_touch_ui)
				$('#infopane').mCustomScrollbar('update');
		},

		goToBrowserPanel: function(panel) {
			$('#infopane').romprScrollTo('#'+panel+'information');
		},

		goToBrowserSection: function(section) {
			// This is for scrolling to Wikipedia Sections ONLY
			// Note $.escapeSelector, because Wikipedia returns anchors that
			// have special characters in them.
			$('#infopane').romprScrollTo('#'+$.escapeSelector(section));
		},

		goToBrowserPlugin: function(panel) {
			try {
				layoutProcessor.goToBrowserPlugin(panel);
			} catch (err) {
				setTimeout( function() { uiHelper.goToBrowserPanel(panel) }, 500);
			}
		},

		afterHistory: function() {
			try {
				layoutProcessor.afterHistory();
			} catch (err) {
				browser.rePoint();
				$('#infopane').romprScrollTo(0);
			}
		},

		scrollPlaylistToCurrentTrack: function(scrollto, flag) {
			if (flag && scrollto.length > 0) {
				debug.log("LAYOUT","Scrolling Playlist To Song:",player.status.songid);
				if (uiHelper.is_touch_ui) {
					var offset = 0 - ($(layoutProcessor.playlist_scroll_parent).outerHeight(true) / 2);
					$(layoutProcessor.playlist_scroll_parent).scrollTo(scrollto, 250, {offset: {top: offset}, easing: 'swing'});
				} else {
					$(layoutProcessor.playlist_scroll_parent).mCustomScrollbar("stop");
					$(layoutProcessor.playlist_scroll_parent).mCustomScrollbar("update");
					var pospixels = Math.round(scrollto.position().top - ($(layoutProcessor.playlist_scroll_parent).height()/2));
					pospixels = Math.min($("#sortable").parent().height(), Math.max(pospixels, 0));
					$(layoutProcessor.playlist_scroll_parent).romprScrollTo(pospixels);
				}
			}
		},

		initialise: function() {
			if (prefs.outputsvisible) {
				try {
					layoutProcessor.toggleAudioOutpts();
				} catch (err) {

				}
			}
			if (uiHelper.is_touch_ui) {
				// Remove the mouse/keyboard related options from Prefs
				$('.kbdbits').remove();
				$('.albumart-holder').remove();
				doSwipeCss();
			} else {
				$('.touchbits').remove();
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
				$(document).on('mouseenter', '.clearbox', makeHoverWork);
				$(document).on('mouseleave', '.clearbox', makeHoverWork);
				$(document).on('mousemove', '.clearbox', makeHoverWork);
				$(document).on('mouseenter', '.combobox-entry', makeHoverWork);
				$(document).on('mouseleave', '.combobox-entry', makeHoverWork);
				$(document).on('mousemove', '.combobox-entry', makeHoverWork);
				// shortcuts.load();
			}
			$("#tracktimess").on('click', layoutProcessor.toggleRemainTime);
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
				var c = 'dropmenu notfilled is-albumlist';
				if (
					element.hasClass('directory') ||
					element.hasClass('playlist') ||
					element.hasClass('userplaylist')
				) {
					c += ' removeable';
				}
				return $('<div>', {id: name, class: c}).insertAfter(element.parent());
			}
		},

		doCollectionStripyStuff: function() {
			try {
				return layoutProcessor.doCollectionStripyStuff();
			} catch (err) {

			}
		},

		setFloaterPosition: function(element, position) {
			try {
				return layoutProcessor.setFloaterPosition(element, position);
			} catch (err) {
				element.css({
					top: Math.min(position.y+8, $(window).height() - element.height()),
					left: Math.min($(window).width() - element.width(),  position.x-16)
				});

			}
		},

		ui_config_header: function(options) {
			var opts = $.extend({
				lefticon: null,
				lefticon_name: null,
				righticon: null,
				label: null,
				main_icon: null,
				class: '',
				icon_size: 'medicon',
				label_text: null,
				title_class: null,
				id: null
			}, options);

			var html = '<div class="configtitle';
			if (opts.title_class)
				html += ' '+opts.title_class;

			html += '"';
			if (opts.id)
				html += ' id="'+opts.id+'"';

			html += '>';
			html += '<i class="'+opts.icon_size;
			if (opts.lefticon)
				html += ' '+opts.lefticon;

			html += '"';
			if (opts.lefticon_name)
				html += ' name="'+opts.lefticon_name+'"';

			html += '></i>';
			if (opts.label) {
				html += '<div class="textcentre expand';
				if (opts.class != '')
					html += ' '.opts.class;

				html += '"><b>'+language.gettext(opts.label)+'</b></div>';
			} else if (opts.main_icon) {
				html += '<i class="expand alignmid '+opts.main_icon+'"></i>';
			} else if (opts.label_text) {
				html += '<div class="textcentre expand';
				if (opts.class != '')
					html += ' '+opts.class;

				html += '"><b>'+opts.label_text+'</b></div>';
			}

			html += '<i class="right-icon '+opts.icon_size;
			if (opts.righticon)
				html += ' '+opts.righticon;

			html += '"></i>';

			html += '</div>';
			return html;

		}
	}
}();


