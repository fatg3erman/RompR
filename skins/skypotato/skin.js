// This skin works by taking what is basically a default layout
// and jQuery-ing it to feck to move things around.

// The biggest problem with this skin is that if we change stuff in the UI, it usually fucks it up.
// So be careful to test it.

jQuery.fn.menuReveal = async function() {

	// 'self' is the menu being opened, which will alresady have contents

	var self = $(this);
	var id = self.attr('id');
	var holder = $('.openmenu[name="'+id+'"]');
	var parent = holder.parent();
	var adjustboxes = true;
	var scrollto = true;

	switch (true) {
		case holder.hasClass('album'):
		case holder.hasClass('playlist'):
		case holder.hasClass('userplaylist'):
			// Albums and Playlists
			parent.addClass('masonry_opened dropshadow').insertDummySpacers();

			self.wrap('<div class="expand"></div>');

			// All this bit is just re-formating the domain icon/album name/artist name
			var titlediv = holder.find('div.albumthing').detach().insertBefore(self).find('.title-menu');
			titlediv.addClass('containerbox vertical-centre');
			titlediv.find('.inline-icon').removeClass('inline-icon').addClass('svg-square');
			var newwrapper = $('<div>', {class: 'containerbox vertical expand open-album-title'}).appendTo(titlediv);
			titlediv.find('div').not('.vertical').addClass('expand').detach().appendTo(newwrapper);

			holder.find('div.vertical-centre.configtitle').remove();
			var tt = self.find('input.albumtime').val();
			if (tt) {
				var d = $('<div>', {class: 'album-time'}).html(tt).appendTo(self);
				$('<i>', {class: 'icon-blank timerspacer'}).appendTo(d);
			}
			adjustboxes = false;
			break;

		case holder.hasClass('podcast'):
			// Podcasts
			parent.addClass('masonry_opened dropshadow').insertDummySpacers();
			parent.find('div.albumthing').detach().appendTo(parent);
			self.detach().addClass('minwidthed2').appendTo(parent);
			adjustboxes = false;
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

		case self.hasClass('collectionpanel'):
			// Albums panel, when an artist name is clicked on. Catch it because we DO need to appendDummySpacers
			scrollto = false;
			break;

		default:
			// Other dropdowns (eg podcast controls)
			adjustboxes = false;
			scrollto = false;
			break;
	}
	if (self.hasClass('toggledown')) {
		debug.log('SKIN', 'Sliding menu');
		await self.slideToggle('fast').promise();
	} else {
		var displaymode = self.hasClass('containerbox') ? 'flex' : 'block';
		self.css({display: displaymode});
	}
	if (scrollto) {
		layoutProcessor.scrollSourcesTo(parent);
	}
	if (adjustboxes) {
		self.appendDummySpacers();
		layoutProcessor.postAlbumMenu(holder);
	}
	return this;
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
		// Think this is for Albums By Artist mode, also for radio station browsers
		$(this).find('.configtitle').insertDummySpacers();
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

jQuery.fn.menuHide = async function() {
	var self = $(this);
	var id = self.attr('id');
	var holder = $('.openmenu[name="'+id+'"]');
	var parent = holder.parent();
	var adjustboxes = true;

	switch (true) {
		case holder.hasClass('album'):
		case holder.hasClass('playlist'):
		case holder.hasClass('userplaylist'):
			// Albums and Playlists
			parent.removeClass('masonry_opened dropshadow');
			parent.removeDummySpacers();
			var monkey = parent.find('.helpfulalbum.expand');
			var titlediv = self.parent().find('div.albumthing').detach().appendTo(monkey).find('.title-menu');
			titlediv.removeClass('containerbox vertical-centre');
			titlediv.find('.svg-square').removeClass('svg-square').addClass('inline-icon');
			titlediv.find('div').not('.vertical').removeClass('expand').detach().appendTo(titlediv);
			titlediv.find('.vertical').remove();
			self.parent().remove();
			// self.remove();
			break;

		case holder.hasClass('podcast'):
			// Podcasts
			self.prev('div.albumthing').detach().appendTo(self.prev().children('.helpfulalbum').first());
			parent.removeClass('masonry_opened dropshadow');
			parent.removeDummySpacers();
			self.removeClass('minwidthed2').css({display: 'none'});
			break;

		case holder.hasClass('radio'):
			// Radio Browsers
			self.addClass('closed').css({display: 'none'});
			break;

		default:
			// Other dropdowns (eg podcast controls)
			if (self.hasClass('toggledown')) {
				debug.log('SKIN', 'Sliding menu');
				await self.slideToggle('fast').promise();
			} else {
				self.css({display: 'none'});
			}
			adjustboxes = false;
			break;
	}
	if (adjustboxes) {
		layoutProcessor.scrollSourcesTo(parent);
	}
	return this;
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

jQuery.fn.animatePanel = function(options) {
	var settings = $.extend({},options);
	var panel = this.attr("id");
	debug.log('UI', 'Animating Panel',panel,settings);
	this.css('width', settings[panel]+'%');
}

jQuery.fn.removeCollectionDrodown = function() {
	this.each(function() {
		var self = $(this);
		self.removeDummySpacers();
		self.remove();
	});
}

jQuery.fn.removeCollectionItem = function() {
	this.each(function() {
		if ($(this).attr('name').match(/artist/)) {
			var self = $(this);
		} else {
			var self = $(this).parent();
		}
		self.removeDummySpacers();
		self.remove();
	});
}

jQuery.fn.insertAlbumAfter = function(albumindex, html, tracklist) {
	return this.each(function() {
		var me = $(this).parent();
		var isopen = $('#'+albumindex).is(':visible');
		var newthing = $(html);
		if (isopen) {
			var dropdown = $('#'+albumindex).detach().html(tracklist).updateTracklist().appendTo(newthing.find('.containerbox.openmenu'));
		}
		$('.openmenu[name="'+albumindex+'"]').removeCollectionItem();
		newthing.insertAfter(me).scootTheAlbums();
		if (isopen) {
			dropdown.menuReveal();
		}
	});
}

jQuery.fn.insertAlbumAtStart = function(albumindex, html, tracklist) {
	return this.each(function() {
		var me = $(this);
		var isopen = $('#'+albumindex).is(':visible');
		var newthing = $(html);
		if (isopen) {
			var dropdown = $('#'+albumindex).detach().html(tracklist).updateTracklist().appendTo(newthing.find('.containerbox.openmenu'));
		}
		$('.openmenu[name="'+albumindex+'"]').removeCollectionItem();
		if (me.children('.clickalbum').length > 0) {
			// In 'Artist' mode the first thing in the panel is a title followed by a Play All Link
			newthing.insertAfter(me.children('.clickalbum').last()).scootTheAlbums();
		} else {
			newthing.prependTo(me).scootTheAlbums();
		}
		if (isopen) {
			dropdown.menuReveal();
		}
	});
}

jQuery.fn.insertArtistAfter = function(html) {
	return this.each(function() {
		var me = $(this);
		if (me.hasClass('artist')) {
			$(html).insertAfter(me);
		} else {
			$(html).insertAfter(me.parent()).insertDummySpacers();
		}
	});
}

jQuery.fn.doThingsAfterDisplayingListOfAlbums = function() {
	return this.each(function() {
		$(this).appendDummySpacers();
	});
}

function showHistory() {
	uiHelper.sourceControl('infoholder');
}

var layoutProcessor = function() {

	var my_scrollers = [ "#sources", "#infopane", ".top_drop_menu:not(.noscroll)", ".drop-box" ];
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
					$('#infopane').romprScrollTo('#podcastslist');
					break;

				case 'historyholder':
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
		if (!$('#fruitbat').hasClass('containerbox')) {
			// $('#podholder').detach().insertBefore($('#infoholder'));
			$('#fruitbat').removeClass('fullwidth').addClass('containerbox wrap');
		}
		$('#fruitbat').appendDummySpacers();
	}

	function fanoogleSearcher() {
		$('.search_result_box').not('#searchresultholder').each(function() {
			if (!$(this).hasClass('containerbox')) {
				$(this).detach().empty()
					.removeClass('noborder')
					.addClass('containerbox wrap collectionpanel').css('display', '')
					.insertBefore($('#infoholder'));
			}
		});
	}

	function setBottomPanelWidths() {
		layoutProcessor.setPanelCss(getPanelWidths());
	}

	function getPanelWidths() {
		var sourcesweight = (prefs.sourceshidden) ? 0 : 1;
		var sourceswidth = prefs.sourceswidthpercent*sourcesweight;
		return ({sources: sourceswidth});
	}

	function animatePanels() {
		var widths = getPanelWidths();
		$("#sources").animatePanel(widths);
	}

	function makeNewPanel(element, name) {
		const classes = {
			collection: 'albumlist',
			audiobooks: 'audiobooklist',
			searchresultholder: 'searcher',
			filelist: 'filelist'
		};
		$('.collectionpanel.'+classes[element.parent().prop('id')]).remove();
		element.parent().find('.highlighted').removeClass('highlighted');
		if ($('#'+name).length == 0) {
			var t = $('<div>', {id: name, class: 'collectionpanel '+classes[element.parent().prop('id')]+' containerbox wrap noselection notfilled is-albumlist'}).insertBefore($('#infoholder'));
		}
		$('.collectionpanel').css({display: 'none'});
		element.addClass('highlighted');
		setDraggable('#'+name);
		return t;
	}

	function findParentScroller(jq) {
		var p = jq.parent();
		while (!p.is('body') && !p.hasClass('mCustomScrollbar') && !p.hasClass('collectionpanel')) {
			p = p.parent();
		}
		if (p.is('body')) {
			return false;
		}
		return p;
	}

	function setupCollectionDisplay() {
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
		if (prefs.actuallysortresultsby.substr(0,5) == 'album') {
			if (!$('#searchresultholder').hasClass('containerbox')) {
				$('#searchresultholder').detach().empty()
					.removeClass('noborder')
					.addClass('containerbox wrap collectionpanel').css('display', '')
					.prependTo($('#infoholder').parent());
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
	}

	return {

		sortFaveRadios: false,
		openOnImage: true,
		playlist_scroll_parent: '#phacker',

		setPanelCss: function(widths) {
			$("#sources").css("width", widths.sources+"%");
		},

		changeCollectionSortMode: function() {
			setupCollectionDisplay();
			// The above makes collection, search, and audiobook panels all visible
			// if we're in one of the 'album' modes, so call into sourceControl again
			// to hide the ones we don't need
			loading_ui = true;
			uiHelper.sourceControl(prefs.chooser);
			collectionHelper.forceCollectionReload();
		},

		addInfoSource: function(name, obj) {
			$("#chooserbuttons").append($('<i>', {
				onclick: "browser.switch_source('"+name+"')",
				title: language.gettext(obj.text),
				class: obj.icon+' topimg sep fixed tooltip',
				id: "button_source"+name
			}));
		},

		setupInfoButtons: function() {
			$("#button_source"+prefs.infosource).addClass("currentbun");
		},

		goToBrowserPlugin: function(panel) {
			uiHelper.sourceControl('pluginholder');
			setTimeout(function() {
				uiHelper.goToBrowserPanel(panel);
			}, 500);
		},

		toggleAudioOutpts: function() {
			prefs.save({outputsvisible: !$('#outputbox').is(':visible')});
			$("#outputbox").animate({width: 'toggle'},'fast',function() {
				infobar.rejigTheText();
			});
		},

		hidePanel: function(panel, is_hidden, new_state) {
			if (is_hidden != new_state) {
				if (new_state && prefs.chooser == panel) {
					$("#"+panel).fadeOut('fast');
					var s = ["albumlist", "specialplugins", "searcher", "filelist", "radiolist", "audiobooklist", "playlistslist", "podcastslist", "pluginplaylistslist"];
					for (var i in s) {
						if (s[i] != panel && !prefs["hide_"+s[i]]) {
							uiHelper.sourceControl(s[i]);
							break;
						}
					}
				}
				if (!new_state && prefs.chooser == panel) {
					$("#"+panel).fadeIn('fast');
				}
			}
		},

		playlistControlHotKey: function(button) {
			if (!$("#playlistbuttons").is(':visible')) {
				togglePlaylistButtons()
			}
			$("#"+button).trigger('click');
		},

		hideBrowser: function() {

		},

		scrollCollectionTo: function(jq) {
			if (jq.length > 0)
				$('#sources').romprScrollTo(jq);
		},

		scrollSourcesTo: function(jq) {
			$('#infopane').romprScrollTo(jq);
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
						$('#infopane .search-section').hide(0);
						$('#infopane #collection').show(0);
						if (prefs.sourceshidden) {
							layoutProcessor.expandInfo('left');
						}
						break;

					case 'searcher':
						$('.collectionpanel').hide(0);
						$('#infopane .search_result_box').show(0);
						$('#infopane .search-section').show(0);
						fanoogleSearcher();
						if (prefs.sourceshidden) {
							layoutProcessor.expandInfo('left');
						}
						break;

					case 'audiobooklist':
						$('.collectionpanel').hide(0);
						$('#infopane .search-section').hide(0);
						$('#infopane #audiobooks').show(0);
						if (prefs.sourceshidden) {
							layoutProcessor.expandInfo('left');
						}
						break;

					case 'podcastslist':
						$('.collectionpanel').hide(0);
						$('#infopane .search-section').hide(0);
						$('#podholder').show(0);
						if (!prefs.sourceshidden) {
							layoutProcessor.expandInfo('left');
						}
						break;

					case 'infoholder':
					case 'pluginholder':
					case 'playlistslist':
					case 'pluginplaylistslist':
						$('.collectionpanel').hide(0);
						$('#infopane .search-section').hide(0);
						if (!prefs.sourceshidden) {
							layoutProcessor.expandInfo('left');
						}
						break;

					case 'historyholder':
						$('.collectionpanel').not('#infoholder').hide(0);
						$('#infopane .search-section').hide(0);
						if (prefs.sourceshidden) {
							layoutProcessor.expandInfo('left');
						}
						break;

					default:
						$('.collectionpanel').hide(0);
						$('#infopane .search-section').hide(0);
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
			infobar.rejigTheText();
			browser.rePoint();
			$('.top_drop_menu').fanoogleMenus();
		},

		displayCollectionInsert: function(details) {
			debug.mark("COLLECTION","Displaying New Insert");
			debug.debug('COLLECTION', details);
			infobar.notify((details.isaudiobook == 0) ? language.gettext('label_addedtocol') : language.gettext('label_addedtosw'));
			if (details.isaudiobook > 0 && prefs.chooser == 'audiobooklist') {
				layoutProcessor.scrollCollectionTo('.openmenu[name="zartist'+details.artistindex+'"]');
				layoutProcessor.scrollSourcesTo('.openmenu[name="zalbum'+details.albumindex+'"]');
			} else if (prefs.chooser == 'albumlist') {
				layoutProcessor.scrollCollectionTo('.openmenu[name="aartist'+details.artistindex+'"]');
				layoutProcessor.scrollSourcesTo('.openmenu[name="aalbum'+details.albumindex+'"]');
			}
		},

		playlistupdate: function(upcoming) {
			var time = 0;
			for(var track of upcoming) {
				time += track.Time;
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
			debug.core('DOFANCYHEADERS', 'Lines is',lines);
			if (lines.length == 0 && $('#plmode').html() != '') {
				currheader = 0;
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
				var c = 'notfilled minwidthed2 expand is-albumlist';
				if (
					element.hasClass('playlist') ||
					element.hasClass('userplaylist')
				) {
					c += ' removeable';
				}
				if ($('#'+name).length == 0) {
					return $('<div>', {id: name, class: c, style: 'display: none'}).appendTo(element.parent().find('.containerbox.openmenu'));
				}
			} else if (element.hasClass('directory')) {
				// This is for a directory in the file browser
				var n = element.attr('name');
				if (n.indexOf('_') == -1) {
					return makeNewPanel(element, name);
				} else {
					return $('<div>', {id: name, class: 'indent containerbox wrap fullwidth notfilled removeable is-albumlist', style: 'display: none'}).insertAfter(element);
				}
			} else if (element.hasClass('searchdir')) {

			} else {
				// This is for an artist clicked on in the artist list.
				return makeNewPanel(element, name);
			}
		},

		initialise: function() {
			$("#sortable").disableSelection();
			if (uiHelper.is_touch_ui) {

				$(document).touchStretch({});

			} else {
	            $("#sortable").acceptDroppedTracks({
	                scroll: true,
	                scrollparent: '#phacker'
	            });
	            $("#sortable").sortableTrackList({
	                items: '.sortable',
	                outsidedrop: playlist.dragstopped,
	                insidedrop: playlist.dragstopped,
	                scroll: true,
	                scrollparent: '#phacker',
	                scrollspeed: 80,
	                scrollzone: 120
	            });

	            $("#pscroller").acceptDroppedTracks({
	                ondrop: playlist.draggedToEmpty,
	                coveredby: '#sortable'
	            });
	        }
			animatePanels();
			for (let value of my_scrollers) {
				$(value).addCustomScrollBar();
			};
			$(".top_drop_menu").floatingMenu({
				handleClass: 'dragmenu',
				addClassTo: 'configtitle',
				siblings: '.top_drop_menu'
			});
			$("#tagadder").floatingMenu({
				handleClass: 'configtitle',
				handleshow: false
			});
			$("#pladddropdown").floatingMenu({
				handleClass: 'configtitle',
				handleshow: false
			});
			$("#bookmarkadddropdown").floatingMenu({
				handleClass: 'configtitle',
				handleshow: false
			});
			$(".stayopen").not('.dontstealmyclicks').on('click', function(ev) {ev.stopPropagation() });
			$("#sources").find('.mCSB_draggerRail').resizeHandle({
				side: 'left',
				donefunc: setBottomPanelWidths,
				offset: $('#headerbar').outerWidth(true)
			});
			$('#plmode').detach().appendTo('#nowplaying_icons').addClass('tright');
			$('#volume').volumeControl({
				orientation: 'vertical',
				command: player.controller.volume
			});
			setupCollectionDisplay();
			sleepHelper.addSleepHelper(layoutProcessor.stopHeaderTimer);
			sleepHelper.addWakeHelper(layoutProcessor.doFancyHeaderStuff);
		},

		stopHeaderTimer: function() {
 			clearTimeout(headertimer);
		},

		// Optional Additions

		prepareCollectionUpdate: function() {
			$('.collectionpanel.searcher').remove();
			$('.collectionpanel.albumlist').remove();
			$('.collectionpanel.audiobooklist').remove();
			$('#searchresultholder').empty();
		},

		createPluginHolder: function(icon, title, id, panel) {
			var d = $('<div>', {class: 'expand topdrop'}).prependTo('#gronky');
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
					// debug.log('POSTALBUMMENU', 'Artist', found[1], found[2]);
					var name = htmlspecialchars_decode($('#'+element.attr('name')).children('.configtitle').first().find('b').first().html());
					debug.log('POSTALBUMMENU', 'Artist name',name);
					if (prefs.artistsatstart.indexOf(name) == -1) {
						var divname = 'potato_'+found[1]+'artist_'+found[2];
						var destdiv = $('<div>',
							{   class: 'collectionitem fixed masonry_opened dropshadow invisible',
								style: 'width: 98%',
								id: divname
							}
						).appendTo($('#'+element.attr('name')));

						lastfm.artist.getInfo({artist: name},
							layoutProcessor.artistInfo,
							layoutProcessor.artistInfoError,
							divname
						);
					}
				}, 500);
			}
		},

		artistInfo: async function(data, reqid) {
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
