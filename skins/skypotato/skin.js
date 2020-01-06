// This skin works by taking what is basically a default layout
// and jQuery-ing it to feck to move things around.

// The biggest problem with this skin is that if we change stuff in the UI, it usually fucks it up.
// So be careful to test it.

jQuery.fn.menuReveal = function(callback) {

	// 'self' is the menu being opened, which will alresady have contents

	var self = this;
	var id = this.attr('id');
	var holder = $('.openmenu[name="'+id+'"]');
	var parent = holder.parent();
	var adjustboxes = true;

	switch (true) {
		case holder.hasClass('album'):
		case holder.hasClass('playlist'):
		case holder.hasClass('userplaylist'):
			// Albums and Playlists
			parent.addClass('tagholder_wide dropshadow').insertDummySpacers();

			// All this bit is just re-formating the domain icon/album name/artist name
			var titlediv = holder.find('div.albumthing').detach().prependTo(self).find('.title-menu');
			titlediv.addClass('containerbox dropdown-container');
			titlediv.find('.collectionicon').removeClass('collectionicon').addClass('svg-square');
			var newwrapper = $('<div>', {class: 'containerbox vertical expand'}).appendTo(titlediv);
			titlediv.find('div').not('.vertical').addClass('expand').detach().appendTo(newwrapper);

			holder.find('div.dropdown-container.configtitle').remove();
			var tt = self.find('input.albumtime').val();
			if (tt) {
				var d = $('<div>', {class: 'tgtl podcastitem', style: 'padding-top: 4px'}).html(tt).appendTo(self);
				$('<i>', {class: 'icon-blank timerspacer'}).appendTo(d);
			}
			break;

		case holder.hasClass('podcast'):
			// Podcasts
			parent.addClass('tagholder_wide dropshadow').insertDummySpacers();
			parent.find('div.albumthing').detach().appendTo(parent);
			self.detach().addClass('minwidthed2').appendTo(parent);
			break;

		case holder.hasClass('radiochannel'):
			// Radio Stations
			parent.addClass('tagholder_wide dropshadow').insertDummySpacers();
			self.detach().appendTo(parent);
			holder.find('div.albumthing').detach().prependTo(self);
			break;

		case holder.hasClass('radio'):
			// Radio Browsers
			// We can't remove the radio plugin panels, but we need to mark which ones are closed
			// Otherwise showPanel will reopen ALL the ones that have been opened if we switch away and
			// back to the radio stations panel.
			$('.collectionpanel').hide();
			$('.collectionpanel.radiolist').addClass('closed');
			if (self.hasClass('dropmenu')) {
				self.detach().removeClass('dropmenu').addClass('collectionpanel radiolist containerbox wrap noselection').insertBefore($('#infoholder'));
				setDraggable('#'+id);
			}
			self.removeClass('closed');
			break;

		case $(this).hasClass('collectionpanel'):
			// Albums panel, when an artist name is clicked on. Catch it because we DO need to adjustBoxSizes
			break;

		default:
			// Other dropdowns (eg podcast controls)
			adjustboxes = false;
			break;
	}
	var displaymode = self.hasClass('containerbox') ? 'flex' : 'block';
	self.css({display: displaymode});
	if (callback) callback.call(self);
	if (adjustboxes) {
		$(this).appendDummySpacers();
		layoutProcessor.scrollSourcesTo(parent);
		layoutProcessor.postAlbumMenu(holder);
	}
	return self;
}

// The number 4 in the following functions arises because we have
// the css set to 20%

// Insert empty collectionitem divs at end of the box, to make sure
// any rows that aren't full keep the widths the same as the other rows.

jQuery.fn.appendDummySpacers = function() {
	return this.each(function() {
		var lastitem = $(this).children('.collectionitem').last()
		if (!lastitem.hasClass('collection_spacer')) {
			for (var i = 0; i < 4; i++) {
				$('<div>',  {class: "collectionitem collection_spacer"}).insertAfter(lastitem);
			}
		}
		$(this).find('.configtitle.brick_wide').insertDummySpacers();
	});
}

// Insert empty collectionitem divs before the one we're opening to prevent the
// previous ones from expanding.

jQuery.fn.insertDummySpacers = function() {
	return this.each(function() {
		if (!$(this).prev().hasClass('collection_spacer')) {
			for (var i = 0; i < 4; i++) {
				$('<div>',  {class: "collectionitem collection_spacer"}).insertBefore($(this));
			}
		}
	});
}

jQuery.fn.removeDummySpacers = function() {
	return this.each(function() {
		while ($(this).prev().hasClass('collection_spacer')) {
			$(this).prev().remove();
		}
	});
}

jQuery.fn.menuHide = function(callback) {
	var self = this;
	var id = this.attr('id');
	var holder = $('.openmenu[name="'+id+'"]');
	var parent = holder.parent();
	var adjustboxes = true;

	switch (true) {
		case holder.hasClass('album'):
		case holder.hasClass('playlist'):
		case holder.hasClass('userplaylist'):
			// Albums and Playlists
			parent.removeClass('tagholder_wide dropshadow');
			parent.removeDummySpacers();
			var monkey = parent.find('.helpfulalbum.expand');

			var titlediv = self.find('div.albumthing').detach().appendTo(monkey).find('.title-menu');
			titlediv.removeClass('containerbox dropdown-container');
			titlediv.find('.svg-square').removeClass('svg-square').addClass('collectionicon');
			titlediv.find('div').not('.vertical').removeClass('expand').detach().appendTo(titlediv);
			titlediv.find('.vertical').remove();

			self.remove();
			break;

		case holder.hasClass('podcast'):
			// Podcasts
			self.prev('div.albumthing').detach().appendTo(self.prev().children('.helpfulalbum').first());
			parent.removeClass('tagholder_wide dropshadow').removeDummySpacers();
			self.removeClass('minwidthed2').css({display: 'none'});
			break;

		case holder.hasClass('radiochannel'):
			// Radio Stations
			var monkey = parent.find('.helpfulalbum.expand');
			parent.removeClass('tagholder_wide dropshadow').removeDummySpacers();
			parent.find('div.albumthing').detach().appendTo(monkey)
			self.css({display: 'none'});
			break;

		case holder.hasClass('radio'):
			// Radio Browsers
			self.addClass('closed').css({display: 'none'});
			break;

		default:
			// Other dropdowns (eg podcast controls)
			self.css({display: 'none'});
			adjustboxes = false;
			break;
	}
	if (callback) callback.call(self);
	if (adjustboxes) {
		layoutProcessor.scrollSourcesTo(parent);
	}
	return self;
}

jQuery.fn.isOpen = function() {
	if ($('#'+this.attr('name')).is(':visible')) {
		return true;
	} else {
		return false;
	}
}

jQuery.fn.isClosed = function() {
	if ($('#'+this.attr('name')).is(':visible')) {
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

jQuery.fn.animatePanel = function(options) {
	var settings = $.extend({},options);
	var panel = this.attr("id");
	this.css('width', settings[panel]+'%');
}

function showHistory() {
	layoutProcessor.sourceControl('infoholder');
}

var layoutProcessor = function() {

	var my_scrollers = [ "#sources", "#infopane", ".topdropmenu", ".drop-box" ];
	var rtime = '';
	var ptime = '';
	var headers = Array();
	var currheader = 0;
	var headertimer;
	var loading_ui = true;
	var artistinfotimer;

	function showPanel(source) {
		debug.log("UI","Showing Panel",source);
		$('#'+source).show(0, function() {
			$('.collectionpanel.'+source).not('.closed').show();
			switch (source) {
				case 'podcastslist':
					fanooglePodcasts();
					$('#infopane').mCustomScrollbar('scrollTo', '#podholder');
					break;

				case 'historypanel':
					$('#infoholder').show(0, browser.rePoint);
					break;

				case 'infoholder':
					browser.rePoint();
					break;

				case 'pluginholder':
					browser.rePoint();
					break;

			}
		});
	}

	function fanooglePodcasts() {
		if (!$('#fruitbat').hasClass('contaierbox')) {
			$('#podholder').detach().insertBefore($('#infoholder'));
			$('#fruitbat').removeClass('fullwidth').addClass('containerbox wrap');
			$('#podcast_search').removeClass('fullwidth').addClass('containerbox wrap');
		}
		$('#fruitbat').appendDummySpacers();
	}

	function setBottomPanelWidths() {
		var widths = getPanelWidths();
		$("#sources").css("width", widths.sources+"%");
		$("#infopane").css("width", widths.infopane+"%");
	}

	function getPanelWidths() {
		var sourcesweight = (prefs.sourceshidden) ? 0 : 1;
		var browserweight = (prefs.hidebrowser) ? 0 : 1;
		var sourceswidth = prefs.sourceswidthpercent*sourcesweight;
		var ws = getWindowSize();
		var percenttofill = (ws.x - $('#headerbar').outerWidth(true))/ws.x;
		var browserwidth = ((100*percenttofill) - sourceswidth)*browserweight;
		if (browserwidth < 0) browserwidth = 0;
		return ({infopane: browserwidth, sources: sourceswidth});
	}

	function animatePanels() {
		var widths = getPanelWidths();
		widths.speed = { sources: 400, infopane: 400 };
		$("#sources").animatePanel(widths);
		$("#infopane").animatePanel(widths);
	}

	function makeNewPanel(element, name) {
		var classes = {
			collection: 'albumlist',
			audiobooks: 'audiobooklist',
			searchresultholder: 'searcher',
			filelist: 'filelist'
		};
		$('.collectionpanel.'+classes[element.parent().prop('id')]).remove();
		element.parent().find('.highlighted').removeClass('highlighted');
		if ($('#'+name).length == 0) {
			var t = $('<div>', {id: name, class: 'collectionpanel '+classes[element.parent().prop('id')]+' containerbox wrap noselection notfilled'}).insertBefore($('#infoholder'));
		}
		$('.collectionpanel').css({display: 'none'});
		element.addClass('highlighted');
		setDraggable('#'+name);
	}

	function findParentScroller(jq) {
		var p = jq.parent();
		while (!p.is('body') && !p.hasClass('mCustomScrollbar')) {
			p = p.parent();
		}
		if (p.is('body')) {
			return false;
		}
		return p;
	}

	return {

		supportsDragDrop: true,
		hasCustomScrollbars: true,
		usesKeyboard: true,
		sortFaveRadios: false,
		openOnImage: true,

		setupCollectionDisplay: function() {
			$('.collectionpanel.albumlist').remove();
			$('.collectionpanel.searcher').remove();
			$('.collectionpanel.audiobooklist').remove();
			// To work with this skin, sortcollectionby should begin with 'album'
			// if there is to be no list in the left-hand pane (where the artists list normally is)
			if (prefs.sortcollectionby.substr(0,5) == 'album') {
				if (!$('#collection').hasClass('containerbox')) {
					$('.collectionpanel').hide(0);
					$('#collection').empty().detach()
						.removeClass('noborder')
						.addClass('containerbox wrap collectionpanel').css('display', '')
						.insertBefore($('#infoholder'));
					$('#audiobooks').empty().detach()
						.removeClass('noborder')
						.addClass('containerbox wrap collectionpanel').css('display', '')
						.insertBefore($('#infoholder'));
				}
			} else {
				if ($('#collection').hasClass('containerbox')) {
					$('.collectionpanel').hide(0);
					$('#collection').empty().detach()
						.removeClass('containerbox wrap collectionpanel').css('display', '')
						.addClass('noborder')
						.appendTo($('#albumlist'));
					// We must be on the collection to change this option, but #collection may be hidden,
					// so make sure we show it
					if (prefs.chooser == 'albumlist') {
						$('#collection').show();
					}
					$('#audiobooks').detach().empty()
						.removeClass('containerbox wrap collectionpanel').css('display', '')
						.addClass('noborder')
						.appendTo($('#audiobooklist'));
					$('#collection, #audiobooks').off('click').off('dblclick');
				}
			}
			if (prefs.sortresultsby.substr(0,5) == 'album') {
				if (!$('#searchresultholder').hasClass('containerbox')) {
					$('#searchresultholder').detach().empty()
						.removeClass('noborder')
						.addClass('containerbox wrap collectionpanel').css('display', '')
						.insertBefore($('#infoholder'));
				}
			} else {
				if ($('#searchresultholder').hasClass('containerbox')) {
					$('#searchresultholder').detach().empty()
						.removeClass('containerbox wrap collectionpanel').css('display', '')
						.addClass('noborder')
						.appendTo($('#searcher'));
					if (prefs.chooser == 'searcher') {
						$('#searchresultholder').show();
					}
					$('#searchresultholder').off('click').off('dblclick');
				}
			}
		},

		changeCollectionSortMode: function() {
			layoutProcessor.setupCollectionDisplay();
			// The above makes collection, search, and audiocook panels all visible
			// if we're in one of the 'album' modes, so call into sourceControl again
			// to hide the ones we don't need
			loading_ui = true;
			layoutProcessor.sourceControl(prefs.chooser);
			collectionHelper.forceCollectionReload();
		},

		doThingsAfterDisplayingListOfAlbums: function(panel) {
			panel.appendDummySpacers();
		},

		afterHistory: function() {
			setTimeout(function() { $("#infoholder").mCustomScrollbar("scrollTo",0) }, 500);
		},

		addInfoSource: function(name, obj) {
			$("#chooserbuttons").append($('<i>', {
				onclick: "browser.switchsource('"+name+"')",
				title: language.gettext(obj.text),
				class: obj.icon+' topimg sep fixed tooltip',
				id: "button_source"+name
			}));
		},

		setupInfoButtons: function() {
			$("#button_source"+prefs.infosource).addClass("currentbun");
		},

		goToBrowserPanel: function(panel) {
			$("#infopane").mCustomScrollbar('update');
			$("#infopane").mCustomScrollbar("scrollTo","#"+panel+"information");
		},

		goToBrowserPlugin: function(panel) {
			layoutProcessor.sourceControl('pluginholder');
			setTimeout(function() {
				layoutProcessor.goToBrowserPanel(panel);
			}, 500);
		},

		goToBrowserSection: function(section) {
			layoutProcessor.sourceControl('pluginholder');
			$("#infopane").mCustomScrollbar("scrollTo",section);
		},

		toggleAudioOutpts: function() {
			prefs.save({outputsvisible: !$('#outputbox').is(':visible')});
			$("#outputbox").animate({width: 'toggle'},'fast',function() {
				infobar.biggerize();
			});
		},

		hidePanel: function(panel, is_hidden, new_state) {
			if (is_hidden != new_state) {
				if (new_state && prefs.chooser == panel) {
					$("#"+panel).fadeOut('fast');
					var s = ["albumlist", "specialplugins", "searcher", "filelist", "radiolist", "audiobooklist", "playlistslist", "podcastslist", "pluginplaylistslist"];
					for (var i in s) {
						if (s[i] != panel && !prefs["hide_"+s[i]]) {
							layoutProcessor.sourceControl(s[i]);
							break;
						}
					}
				}
				if (!new_state && prefs.chooser == panel) {
					$("#"+panel).fadeIn('fast');
				}
			}
		},

		setTagAdderPosition: function(position) {
			$("#tagadder").css({top: Math.min(position.y+8, $(window).height() - $('#tagadder').height()),
				left: Math.min($(window).width() - $('#tagadder').width(),  position.x-16)});
		},

		setPlaylistHeight: function() {
			$('#phacker').fanoogleMenus();
		},

		playlistControlHotKey: function(button) {
			if (!$("#playlistbuttons").is(':visible')) {
				togglePlaylistButtons()
			}
			$("#"+button).trigger('click');
		},

		updateInfopaneScrollbars: function() {
			$('#infopane').mCustomScrollbar('update');
		},

		playlistLoading: function() {
			infobar.smartradio(language.gettext('label_smartsetup'));
		},

		scrollPlaylistToCurrentTrack: function() {
			if (prefs.scrolltocurrent) {
				var scrollto = playlist.getCurrentTrackElement();;
				if (scrollto.length > 0) {
					debug.log("LAYOUT","Scrolling Playlist To Song:",player.status.songid);
					$('#phacker').mCustomScrollbar("stop");
					$('#phacker').mCustomScrollbar("update");
					var pospixels = Math.round(scrollto.position().top - ($("#sortable").parent().parent().height()/2));
					pospixels = Math.min($("#sortable").parent().height(), Math.max(pospixels, 0));
					$('#phacker').mCustomScrollbar(
						"scrollTo",
						pospixels,
						{ scrollInertia: 0 }
					);
				}
			}
		},

		preHorse: function() {

		},

		hideBrowser: function() {

		},

		addCustomScrollBar: function(value) {
			$(value).mCustomScrollbar({
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
		},

		scrollCollectionTo: function(jq) {
			if (jq.length > 0) {
				debug.log("LAYOUT","Scrolling Collection To",jq);
				scroller = findParentScroller(jq);
				if (scroller !== false) {
					scroller.mCustomScrollbar('update').mCustomScrollbar('scrollTo', jq,
						{ scrollInertia: 1000,
						  scrollEasing: 'easeOut' }
					);
				} else {
					debug.warn('LAYOUT', 'Was asked to scroll to something without a parent scroller');
				}
			} else {
				debug.warn("LAYOUT","Was asked to scroll collection to something non-existent",2);
			}
		},

		scrollSourcesTo: function(jq) {
			$("#infopane").mCustomScrollbar('update').mCustomScrollbar('scrollTo', jq,
				{ scrollInertia: 500,
				  scrollEasing: 'easeOut' }
			);
		},

		expandInfo: function(side) {
			switch(side) {
				case "left":
					var p = !prefs.sourceshidden;
					prefs.save({sourceshidden: p});
					break;
			}
			animatePanels();
			return false;
		},

		sourceControl: function(source) {
			debug.log("LAYOUT","Source Control",source);
			if ($('#'+source).length == 0) {
				prefs.save({chooser: 'albumlist'});
				source = 'albumlist';
			}
			if (loading_ui && source == 'pluginholder') {
				source = 'specialplugins';
			}
			if (loading_ui || source != prefs.chooser) {
				switch (source) {
					case 'albumlist':
						$('.collectionpanel').hide(0);
						$('#infopane #collection').show(0);
						if (prefs.sourceshidden) {
							layoutProcessor.expandInfo('left');
						}
						break;

					case 'searcher':
						$('.collectionpanel').hide(0);
						$('#infopane #searchresultholder').show(0);
						if (prefs.sourceshidden) {
							layoutProcessor.expandInfo('left');
						}
						break;

					case 'audiobooklist':
						$('.collectionpanel').hide(0);
						$('#infopane #audiobooks').show(0);
						if (prefs.sourceshidden) {
							layoutProcessor.expandInfo('left');
						}
						break;

					case 'podcastslist':
						$('.collectionpanel').hide(0);
						$('#podholder').show(0);
						if (prefs.sourceshidden) {
							layoutProcessor.expandInfo('left');
						}
						break;

					case 'infoholder':
					case 'pluginholder':
					case 'playlistslist':
					case 'pluginplaylistslist':
						$('.collectionpanel').hide(0);
						if (!prefs.sourceshidden) {
							layoutProcessor.expandInfo('left');
						}
						break;

					case 'historypanel':
						$('.collectionpanel').not('#infoholder').hide(0);
						if (prefs.sourceshidden) {
							layoutProcessor.expandInfo('left');
						}
						break;

					default:
						$('.collectionpanel').hide(0);
						if (prefs.sourceshidden) {
							layoutProcessor.expandInfo('left');
						}
						break;
				}
				loading_ui = false;
				$('#'+prefs.chooser).hide(0);
				showPanel(source);
				prefs.save({chooser: source});
			} else {
				showPanel(source);
			}
			return false;
		},

		adjustLayout: async function() {
			var ws = getWindowSize();
			// Height of the bottom pane (chooser, info, playlist container)
			var newheight = ws.y - $("#bottompage").offset().top;
			$("#bottompage").css("height", newheight+"px");
			var newwidth = ws.x - $('#infobar').offset().left;
			$('#infobar').css('width', newwidth+'px');
			infobar.biggerize();
			browser.rePoint();
			$('.topdropmenu').fanoogleMenus();
			setBottomPanelWidths();
		},

		displayCollectionInsert: function(details) {
			debug.shout("COLLECTION","Displaying New Insert");
			debug.debug('COLLECTION', details);
			infobar.notify(
				(details.isaudiobook == 0) ? language.gettext('label_addedtocol') : language.gettext('label_addedtosw')
			);
			infobar.markCurrentTrack();
			var prefix = null;
			if (details.isaudiobook > 0 && prefs.chooser == 'audiobooklist') {
				prefix = 'z';
			} else if (prefs.chooser == 'albumlist') {
				prefix = 'a';
			}
			if (prefix !== null) {
				layoutProcessor.scrollCollectionTo($('[name="'+prefix+'artist'+details.artistindex+'"]'));
				layoutProcessor.scrollCollectionTo($('[name="'+prefix+'album'+details.albumindex+'"]'));
			}
		},

		playlistupdate: function(upcoming) {
			var time = 0;
			for(var i in upcoming) {
				time += upcoming[i].Time;
			}
			if (time > 0) {
				headers['upcoming'] = "Up Next : "+upcoming.length+" tracks, "+formatTimeString(time);
			} else {
				headers['upcoming'] = '';
			}
			layoutProcessor.doFancyHeaderStuff();
		},

		notifyAddTracks: function() {
			clearTimeout(headertimer);
			$('#plmode').fadeOut(500, function() {
				$('#plmode').html(language.gettext('label_addingtracks')).fadeIn(500);
			});
		},

		doFancyHeaderStuff: function() {
			clearTimeout(headertimer);
			var lines = Array();
			for (var i in headers) {
				if (headers[i] != '') {
					lines.push(headers[i]);
				}
			}
			if (lines.length == 0 && $('#plmode').html() != '') {
				$('#plmode').fadeOut(500, function() {
					$('#plmode').html('').fadeIn(500);
				});
			} else if (lines.length == 1 && $('#plmode').html() != lines[0]) {
				$('#plmode').fadeOut(500, function() {
					$('#plmode').html(lines[0]).fadeIn(500);
				});
			} else {
				currheader++;
				if (currheader >= lines.length) {
					currheader = 0;
				}
				if ($('#plmode').html() != lines[currheader]) {
					$('#plmode').fadeOut(500, function() {
						$('#plmode').html(lines[currheader]).fadeIn(500, function() {
							headertimer = setTimeout(layoutProcessor.doFancyHeaderStuff, 5000);
						});
					});
				} else {
					headertimer = setTimeout(layoutProcessor.doFancyHeaderStuff, 5000);
				}
			}
		},

		setProgressTime: function(stats) {
			if (stats !== null) {
				rtime = stats.remainString;
				ptime = stats.durationString;
				$("#playposss").html(stats.progressString);
			}
			if (prefs.displayremainingtime) {
				$("#tracktimess").html(rtime);
			} else {
				$("#tracktimess").html(ptime);
			}
		},

		toggleRemainTime: function() {
			prefs.save({displayremainingtime: !prefs.displayremainingtime});
			layoutProcessor.setProgressTime(null);
		},

		setRadioModeHeader: function(html) {
			if (html != headers['radiomode']) {
				headers['radiomode'] = html;
				layoutProcessor.doFancyHeaderStuff();
			}
		},

		makeCollectionDropMenu: function(element, name) {

			// Creates a drop menu to hold contents.
			// 'element' is the PARENT menu element that has been clicked on.

			if (element.hasClass('album') || element.hasClass('playlist') || element.hasClass('userplaylist')) {
				// This is for an album clicked on in the album browser pane.
				if ($('#'+name).length == 0) {
					element.parent().find('.containerbox.openmenu').append($('<div>', {id: name, class: 'notfilled minwidthed2 expand', style: 'display: none'}));
				}
			} else if (element.hasClass('directory')) {
				// This is for a directory in the file browser
				var n = element.attr('name');
				if (n.indexOf('_') == -1) {
					makeNewPanel(element, name);
				} else {
					var t= ($('<div>', {id: name, class: 'indent containerbox wrap brick_wide notfilled'})).insertAfter(element);
				}
			} else if (element.hasClass('searchdir')) {

			} else {
				// This is for an artist clicked on in the artist list.
				makeNewPanel(element, name);
			}
		},

		getArtistDestinationDiv: function(menutoopen) {
			return $('[name="'+menutoopen+'"]').parent();
		},

		initialise: function() {
			debug.log("SKIN","Initialising...");
			if (prefs.outputsvisible) {
				layoutProcessor.toggleAudioOutpts();
			}
			$("#sortable").disableSelection();
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

			$("#sortable").acceptDroppedTracks({
				scroll: true,
				scrollparent: '#phacker'
			});
			$("#sortable").sortableTrackList({
				items: '.sortable',
				outsidedrop: playlist.dragstopped,
				insidedrop: playlist.dragstopped,
				allowdragout: true,
				scroll: true,
				scrollparent: '#phacker',
				scrollspeed: 80,
				scrollzone: 120
			});

			$("#pscroller").acceptDroppedTracks({
				ondrop: playlist.draggedToEmpty,
				coveredby: '#sortable'
			});

			animatePanels();

			$(".topdropmenu").floatingMenu({
				handleClass: 'dragmenu',
				addClassTo: 'configtitle',
				siblings: '.topdropmenu'
			});

			$("#tagadder").floatingMenu({
				handleClass: 'configtitle',
				handleshow: false
			});

			$(".stayopen").not('.dontstealmyclicks').on('click', function(ev) {ev.stopPropagation() });

			$.each(my_scrollers, function(index, value) {
				layoutProcessor.addCustomScrollBar(value);
			});

			$("#sources").find('.mCSB_draggerRail').resizeHandle({
				side: 'left',
				donefunc: setBottomPanelWidths,
				offset: $('#headerbar').outerWidth(true)
			});

			shortcuts.load();
			setControlClicks();
			$('.choose_albumlist').on('click', function(){layoutProcessor.sourceControl('albumlist')});
			$('.choose_searcher').on('click', function(){layoutProcessor.sourceControl('searcher')});
			$('.choose_filelist').on('click', function(){layoutProcessor.sourceControl('filelist')});
			$('.choose_radiolist').on('click', function(){layoutProcessor.sourceControl('radiolist')});
			$('.choose_podcastslist').on('click', function(){layoutProcessor.sourceControl('podcastslist')});
			$('.choose_audiobooklist').on('click', function(){layoutProcessor.sourceControl('audiobooklist')});
			$('.choose_playlistslist').on('click', function(){layoutProcessor.sourceControl('playlistslist')});
			$('.choose_pluginplaylistslist').on('click', function(){layoutProcessor.sourceControl('pluginplaylistslist')});
			$('.choose_specialplugins').on('click', function(){layoutProcessor.sourceControl('specialplugins')});
			$('.choose_infopanel').on('click', function(){layoutProcessor.sourceControl('infoholder')});
			$('.choose_history').on('click', function(){layoutProcessor.sourceControl('historypanel')});
			$('.open_albumart').on('click', openAlbumArtManager);
			$("#ratingimage").on('click', nowplaying.setRating);
			$('.icon-rss.npicon').on('click', function(){podcasts.doPodcast('nppodiput')});
			$('#expandleft').on('click', function(){layoutProcessor.expandInfo('left')});
			$("#playlistname").parent().next('button').on('click', player.controller.savePlaylist);
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
			$('#plmode').detach().appendTo('#amontobin').addClass('tright');
			$('#volume').volumeControl({
				orientation: 'vertical',
				command: player.controller.volume
			});

			$('#radiolist').children().not('.menuitem').each(function() {
				var c = $(this).find('.artistnamething').html();
				$(this).find('.openmenu').addClass('menuitem').html(c).detach().prependTo($(this));
				$(this).find('.collectionitem').remove();
			});
			$('#radiolist>div:nth-child(even)').addClass('album1');
			$('#radiolist>div:nth-child(odd)').not('.configtitle').addClass('album2');
			$(document).on('click', '.clickaddtoplaylist', function() {
				$('#addtoplaylistmenu').parent().parent().parent().hide();
			});

			// $(document).on('ready', '.collectionpanel', function() {
			// 	debug.shout('Weeee', 'Woooo');
			// });

		},

		// Optional Additions

		findAlbumDisplayer: function(key) {
			if (key == 'fothergill' || key == 'mingus') {
				return $('#'+key);
			} else {
				return $('.containerbox[name="'+key+'"]').parent();
			}
		},

		findArtistDisplayer: function(key) {
			return $('div.menu[name="'+key+'"]');
		},

		removeArtist: function(v) {
			var banner = $("#"+v);
			if (banner.length > 0) {
				banner.removeDummySpacers();
				banner.remove();
			}
			layoutProcessor.findArtistDisplayer(v).remove();
		},

		insertArtist: function(v) {
			switch (v.type) {
				case 'insertAfter':
					debug.log("Insert After",v.where);
					var type = v.where.match(/.([a-z]+)\d/)[1];
					if (type === null) {
						// Regexp won't match if v.where is 'fothergill' or 'mingus'
						type = 'album';
					}
					switch (type) {
						case 'album':
							var a = $(v.html).insertAfter(layoutProcessor.findAlbumDisplayer(v.where));
							a.insertDummySpacers();
							break;

						default:
							$(v.html).insertAfter(layoutProcessor.findArtistDisplayer(v.where));
							break;
					}
					break;

				case 'insertAtStart':
					debug.log("Insert At Start",v.where);
					$(v.html).prependTo($('#'+v.where));
					break;
			}
		},

		albumBrowsed: function(menutoopen, data) {
			var titlebit = $('#'+menutoopen).find('.tagh.albumthing').detach();
			$("#"+menutoopen).html(data);
			$("#"+menutoopen).prepend(titlebit);
		},

		removeAlbum: function(key) {
			$('#'+key).remove();
			layoutProcessor.findAlbumDisplayer(key).removeDummySpacers().remove();
		},

		insertAlbum: function(v) {
			var albumindex = v.id;
			var dropdown = $('#'+albumindex).is(':visible');
			layoutProcessor.findAlbumDisplayer(albumindex).removeDummySpacers().remove();
			switch (v.type) {
				case 'insertAfter':
					debug.log("Insert After",v.where);
					$(v.html).insertAfter(uiHelper.findAlbumDisplayer(v.where));
					break;

				case 'insertAtStart':
					debug.log("Insert At Start",v.where);
					$(v.html).insertAfter($('#'+v.where).find('.clickalbum.playable.brick_wide'));
					break;
			}
			if (dropdown) {
				layoutProcessor.makeCollectionDropMenu($('[name="'+albumindex+'"]'), albumindex);
				$('#'+albumindex).removeClass('notfilled').html(v.tracklist);
				$('#'+albumindex).menuReveal();
				uiHelper.makeResumeBar($('#'+albumindex));
				infobar.markCurrentTrack();
				if (prefs.clickmode == 'single') {
					$('#'+albumindex).find('.invisibleicon').removeClass('invisibleicon');
				}
			}
		},

		preparePlaylistTarget: function(t) {
			var d = $('#'+t).find('.tagh.albumthing').clone();
			return d;
		},

		postPlaylistTarget: function(t, x) {
			$('#'+t).prepend(x);
			$('#'+t).find('div.dropdown-container.configtitle').remove();
		},

		prepareCollectionUpdate: function() {
			$('.collectionpanel.searcher').remove();
			$('.collectionpanel.albumlist').remove();
			$('.collectionpanel.audiobooklist').remove();
			$('#searchresultholder').empty();
		},

		fixupArtistDiv(jq, name) {
			// This is used ONLY aftere've done a spotify:artist: browse and
			// inserted a load of new albums.
			jq.addClass('containerbox wrap');
			jq.prev().remove();
			jq.prev().remove();
		},

		postPodcastSubscribe: function(data, index) {
			$('.menu[name="podcast_'+index+'"]').parent().fadeOut('fast', function() {
				$('.menu[name="podcast_'+index+'"]').parent().removeDummySpacers().remove();
				$('#podcast_'+index).remove();
				$("#fruitbat").html(data);
				podcasts.doNewCount();
				layoutProcessor.doThingsAfterDisplayingListOfAlbums($("#fruitbat"));
			});
		},

		createPluginHolder: function(icon, title, id, panel) {
			var d = $('<div>', {class: 'topdrop'}).prependTo('#righthandtop');
			var i = $('<i>', {class: 'tooltip', title: title, id: id}).appendTo(d);
			i.addClass(icon);
			i.addClass('smallpluginicon clickicon');
			return d;
		},

		postAlbumMenu: function(element) {
			debug.debug('POSTALBUMMENU', element);
			var found = element.attr('name').match(/([abz])artist(\d+)/);
			if (found !== null) {
				clearTimeout(artistinfotimer);
				artistinfotimer = setTimeout(function() {
					// Get artist info to display in the drop-down.
					// Do it on a timer so (a) We don't spam last.fm with requests if we're clcking rapidly through artists
					// (b) When we get here the div we're looking for isn't visible and so it can't be found
					// (I'm not even sure it's been created by this point, I've forgotten how this works)
					debug.log('POSTALBUMMENU', 'Artist', found[1], found[2]);
					var name = $('#'+element.attr('name')).children('.configtitle').first().find('b').first().html();
					debug.log('POSTALBUMMENU', 'Artist name',htmlspecialchars_decode(name));
					var divname = 'potato_'+found[1]+'artist_'+found[2];
					var destdiv = $('<div>',
						{   class: 'collectionitem fixed tagholder_wide dropshadow invisible',
							style: 'width: 98%',
							id: divname
						}).appendTo($('#'+element.attr('name')));
					if (prefs.artistsatstart.indexOf(name) == -1) {
						lastfm.artist.getInfo({artist: name},
							layoutProcessor.artistInfo,
							layoutProcessor.artistInfoError,
							divname
						);
					}
				}, 500);
			}
		},

		artistInfo: function(data, reqid) {
			if (data && !data.error) {
				var lfmdata = new lfmDataExtractor(data.artist);
				$('#'+reqid).html(lastfm.formatBio(lfmdata.bio(), lfmdata.url())).fadeIn('fast');
			} else {
				$('#'+reqid).remove();
			}
		},

		artistInfoError: function(data, reqid) {

		},

		makeSortablePlaylist: function(id) {
			$('#'+id).sortableTrackList({
				items: '.playable',
				outsidedrop: playlistManager.dropOnPlaylist,
				insidedrop: playlistManager.dragInPlaylist,
				allowdragout: true,
				scroll: true,
				scrollparent: '#infopane',
				scrollspeed: 80,
				scrollzone: 120
			});
			$('#'+id).acceptDroppedTracks({
				scroll: true,
				scrollparent: '#infopane'
			});
		},

		setupPersonalRadio: function() {
			// Don't append dummy spacers to the spotify panel, because we append
			// saved crazy playlists here and it fucks up unless we do nasty skin-dependent
			// shit in the crazy plugin, which is not nice.
			$('#pluginplaylistslist .helpfulholder').not('#pluginplaylists_spotify').appendDummySpacers();
			$('#pluginplaylistslist .fullwidth').not('.tagmenu').insertDummySpacers();
		}

	}
}();
