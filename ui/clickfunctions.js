var clickRegistry = function() {

	var clickHandlers = new Array();
	var menuLoaders = new Array();
	var loadCallbacks = new Array();

	function findMenuLoader(clickedElement, menutoopen) {
		for (var classname in menuLoaders) {
			if (clickedElement.hasClass(classname)) {
				debug.trace('DOMENU', 'Filling Menu using',menuLoaders[classname].name);
				return menuLoaders[classname](clickedElement, menutoopen);
			}
		}
		return false;
	}

	function findLoadCallback(clickedElement, menutoopen) {
		for (var classname in loadCallbacks) {
			if (clickedElement.hasClass(classname)) {
				debug.trace('DOMENU', 'Calling back using',loadCallbacks[classname].name);
				loadCallbacks[classname]();
			}
		}
		return false;
	}

	return {

		addClickHandlers: function(source, fn) {
			debug.log('CLICKHANDLER', 'Adding handler for', source);
			clickHandlers[source] = fn;
		},

		farmClick: function(event) {
			var clickedElement = $(this);
			debug.trace('CLICKREGISTRY', 'Clicked On',clickedElement);
			for (var classname in clickHandlers) {
				if (clickedElement.hasClass(classname)) {
					debug.trace('FARMCLICK', 'Farming click out to',clickHandlers[classname].name)
					event.stopImmediatePropagation();
					clickHandlers[classname](event, clickedElement);
				}
			}
		},

		addMenuHandlers: function(cls, loader) {
			menuLoaders[cls] = loader;
		},

		addLoadCallback: function(cls, loader) {
			loadCallbacks[cls] = loader;
		},

		doMenu: async function(event) {
			if (event) {
				event.stopImmediatePropagation();
			}
			var clickedElement = $(this);
			debug.trace('DOMENU', clickedElement);
			var menutoopen = clickedElement.attr('name');
			var target = $('#'+menutoopen);
			if (target.length == 0) {
				target = uiHelper.makeCollectionDropMenu(clickedElement, menutoopen);
			}
			debug.log('DOMENU', 'Doing Menu', menutoopen);
			if (clickedElement.isClosed()) {
				if (target.hasClass('notfilled')) {
					await clickRegistry.loadContentIntoTarget({target: target, clickedElement: clickedElement, scoot: false});
				}
				debug.trace('DOMENU', 'Revealing Menu',menutoopen);
				clickedElement.toggleOpen();
				await target.menuReveal();
				if (target.hasClass('is-albumlist')) {
					target.scootTheAlbums();
				}
			} else {
				clickedElement.toggleClosed();
				await target.menuHide();
				target.clearOut();
				if (target.hasClass('removeable')) {
					target.remove();
				}
			}
			prefs.save_prefs_for_open_menus(menutoopen);
			return false;
		},

		loadContentIntoTarget: async function(params) {
			var opts = {
				type: 'GET',
				target: null,
				clickedElement: null,
				scoot: true,
				uri: false,
				data: {}
			};
			opts = $.extend(opts, params);
			var menutoopen = opts.target.prop('id');
			var success = true;
			if (opts.clickedElement)
				opts.clickedElement.makeSpinner();
			opts.target.clearOut();
			if (!opts.uri) {
				opts.uri = findMenuLoader(opts.clickedElement, menutoopen);
			}
			if (opts.uri) {
				debug.trace('DOMENU', 'Loading from', opts.uri);
				try {
					var html = await $.ajax({
						type: opts.type,
						url: opts.uri,
						cache: false,
						dataType: 'html',
						data: opts.data
					});
					opts.target.html(html);
					data = null;
					opts.target.removeClass('notfilled');
				} catch(err) {
					let msg = language.gettext('label_general_error');
					if (err.responseText) {
						msg += ' '+err.responseText;
					}
					infobar.error(msg);
					success = false;
				}
			} else {
				debug.error('DOMENU', 'Unfilled menu element with no loader',opts.clickedElement);
			}
			if (opts.clickedElement)
				opts.clickedElement.stopSpinner();
			opts.target.updateTracklist();
			if (opts.target.hasClass('is-albumlist')) {
				opts.target.doThingsAfterDisplayingListOfAlbums();
				if (opts.scoot)
					opts.target.scootTheAlbums();

			}
			uiHelper.doCollectionStripyStuff();
			if (opts.clickedElement)
				findLoadCallback(opts.clickedElement, menutoopen);

			return success;
		}
	}
}();

jQuery.fn.updateTracklist = function() {
	return this.each(function() {
		uiHelper.makeResumeBar($(this));
		infobar.markCurrentTrack();
		if (prefs.clickmode == 'single') {
			$(this).find('.invisibleicon').removeClass('invisibleicon');
		}
	});
}

function getAlbumUrl(clickedElement, menutoopen) {
	return "api/collection/?item="+menutoopen;
}

function getDirectoryUrl(clickedElement, menutoopen) {
	return "api/dirbrowser/?path="+clickedElement.prev().val()+'&prefix='+menutoopen;
}

jQuery.fn.clearOut = function() {
	return this.each(function() {
		var self = $(this);
		if (!self.is(':empty')) {
			self.find('.selected').removeFromSelection();
			if ($(this).find('.menu_opened').length > 0) {
				// Although removeFromSelection does close the popup menu, it only does that
				// for tracks, not albums in the phone/skypotato skin
				closePopupMenu();
			}
			if (typeof(IntersectionObserver) == 'function' && self.hasClass('is-albumlist')) {
				self.find("img.lazy").get().forEach(img => imageLoader.unobserve(img));
			}
		}
	});
}

jQuery.fn.scootTheAlbums = function() {
	return this.each(function() {
		var self = $(this);
		if (prefs.downloadart) {
			debug.trace("COLLECTION", "Scooting albums in",self.attr('id'));
			self.find("img.notexist").each(function() {
				coverscraper.GetNewAlbumArt($(this));
			});
		}
		debug.trace("COLLECTION", "Loading Images In",self.attr('id'));
		if (typeof(IntersectionObserver) == 'function') {
			let starttime = Date.now();
			self.find("img.lazy").get().forEach(img => imageLoader.observe(img));
			let endtime = Date.now() - starttime;
			debug.info('SCOOTING', 'Scooting albums in',self.attr('id'),'took',endtime,'ms');
		} else {
			self.find("img.lazy").each(function() {
				var myself = $(this);
				myself.attr('src', myself.attr('data-src')).removeAttr('data-src').removeClass('lazy');
			});
		}
	});
}

/*

	Itemss which are playable (i.e can be added to the playlist) should have a class of 'playable'
	and NOT 'clickable'. Other attributes on those items should be set as per playlist.addItems

*/

function setPlayClickHandlers() {

	$(document).off('click', '.playable').off('dblclick', '.playable');
	if (prefs.clickmode == 'double') {
		$(document).on('click', '.playable', selectPlayable);
		$(document).on('dblclick', '.playable', playPlayable);
	} else {
		$(document).on('click', '.playable', playPlayable);
	}
	// Slightly misleading function name in this case - we're just using it
	// to bind the click handlers to the Update Collection Now buttons
	collectionHelper.enableCollectionUpdates();
	prefs.doClickCss();
}

/*

	Items which should respond to clicks in the main UI should have a class of 'clickable'
		These are passed to clickRegistry.farmClick
		Click handlers are provided by calling clickRegistry.addClickHandlers with a classname and a handler function
		handlerFunction takes 2 parameters - the event and the clicked element
		Items may have multiple matching classes, all will be acted upon but the order is undefined

	Items for where the click should open a dropdown menu should have a class of 'openmenu' and NOT 'clickable'.
		The item's name attribute should be the id attribute of the dropdown panel, which does not need to exist if
		the openmenu item has a class of album, artist, directory, playlist, or userplaylist
		To populate a menu on-the-fly give it the dropdown class of 'notfilled' and provide a populate function using
		clickRegistry.addMenuHandlers(classname, populateFunction)
		The populate function will be called before menuReveal with (clickedElement, menutoopen)
		To load data on-the-fly into a panel call
		clickRegistry.loadContentIntoTarget({}
			target: JQuery,
			clickedElement: JQuery,
			type: 'GET' or 'POST', default GET
			scoot: bool default true
			uri: uri (optional)
			data: data
		})
			scoot will alnost always be true in this case, and setting it to true won't hurt
			If the uri parameter is used clickedElement is ignored except for making it a spinner while the loading occurs.
		If the dropdown shoud be emptied when it is closed give it a class of 'removeable'
		If the dropdown contains album images that need to be scooted or lazyloaded give it a class of is-albumlist

	Info panel info plugins should use 'infoclick' and NOT 'clickable'. The info panel will pass these clicks
		through to the appropriate artist, album, or track child of the info collection

	Info Panel extra plugins should use 'infoclick plugclickable' and NOT 'clickable'. The info panel will
		pass these through to the plugin's handleClick method.

	Playable items in the Info panel should just use 'playable' and none of the other attributes

*/

function closePopupMenu() {
	$('.menu_opened').removeClass('menu_opened');
	$('#popupmenu').remove();
}

function removePopupMenuItem(e, element) {
	$(element).removeClass('backhi').removeClass('clickable').css({display: 'none'});
}

function bindClickHandlers() {

	// Set up all our click event listeners

	$('.infotext').on('click', '.infoclick',  onBrowserClicked);
	$(document).on('click', '.openmenu', clickRegistry.doMenu);
	$(document).on('click', '.clickable', clickRegistry.farmClick);

	$(document).on('dblclick', '.open_magic_div', function() {
		$('.magic_div').fadeIn('fast');
	});

	clickRegistry.addClickHandlers('clickalbummenu', makeAlbumMenu);
	clickRegistry.addClickHandlers('clicktrackmenu', makeTrackMenu);
	// clickRegistry.addClickHandlers('clickremovebookmark', removeBookmark);
	clickRegistry.addClickHandlers('addtollviabrowse', browseAndAddToListenLater);
	clickRegistry.addClickHandlers('removefromll', removeFromListenLater);
	clickRegistry.addClickHandlers('addtocollectionviabrowse', browseAndAddToCollection);
	clickRegistry.addClickHandlers('amendalbum', amendAlbumDetails);
	clickRegistry.addClickHandlers('setasaudiobook', setAsAudioBook);
	clickRegistry.addClickHandlers('setasmusiccollection', setAsAudioBook);
	clickRegistry.addClickHandlers('usetrackimages', useTrackImages);
	clickRegistry.addClickHandlers('unusetrackimages', useTrackImages);
	clickRegistry.addClickHandlers('fakedouble', playPlayable);
	clickRegistry.addClickHandlers('closepopup', closePopupMenu);
	clickRegistry.addClickHandlers('removeafter', removePopupMenuItem);
	clickRegistry.addClickHandlers('clickqueuetracks', playlist.draggedToEmpty);
	clickRegistry.addClickHandlers('clickplayfromhere', playlist.mimicCDPlayerMode);
	clickRegistry.addClickHandlers('clickremdb', metaHandlers.fromUiElement.removeTrackFromDb);
	clickRegistry.addClickHandlers('clickpltrack', metaHandlers.fromUiElement.tracksToPlaylist);
	clickRegistry.addClickHandlers('removealbum', metaHandlers.fromUiElement.removeAlbumFromDb);
	// clickRegistry.addClickHandlers('resetresume', metaHandlers.fromUiElement.resetResumePosition);
	clickRegistry.addClickHandlers('youtubedl', metaHandlers.fromUiElement.downloadYoutubeTrack);
	clickRegistry.addClickHandlers('youtubedl_all', metaHandlers.fromUiElement.downloadAllYoutubeTracks);
	clickRegistry.addClickHandlers('clickdeleteplaylisttrack', playlistManager.deletePlaylistTrack);
	clickRegistry.addClickHandlers('clickdeleteplaylist', playlistManager.deletePlaylist);
	clickRegistry.addClickHandlers('clickdeleteuserplaylist', playlistManager.deleteUserPlaylist);
	clickRegistry.addClickHandlers('clickrenameplaylist', playlistManager.renamePlaylist);
	clickRegistry.addClickHandlers('clickrenameuserplaylist', playlistManager.renameUserPlaylist);

	clickRegistry.addMenuHandlers('artist', getAlbumUrl);
	clickRegistry.addMenuHandlers('album', getAlbumUrl);
	clickRegistry.addMenuHandlers('playlist', playlistManager.browsePlaylist);
	clickRegistry.addMenuHandlers('userplaylist', playlistManager.browseUserPlaylist);
	clickRegistry.addMenuHandlers('directory', getDirectoryUrl);

	$('.open_albumart').on('click', openAlbumArtManager);
	$("#ratingimage").on('click', nowplaying.setRating);
	$('.icon-rss.npicon').on('click', function(){podcasts.doPodcast('nppodinput', $('.icon-rss.npicon'))});
	$('#expandleft').on('click', function(){layoutProcessor.expandInfo('left')});
	$('#expandright').on('click', function(){layoutProcessor.expandInfo('right')});
	$("#playlistname").parent().next('button').on('click', player.controller.savePlaylist);
	// Checkbox and Radio buttons sadly can't be handled by delegated events
	// because a lot of them are in floatingMenus, which are handled by jQueryUI
	// which stops the events from propagating;
	$('.toggle').on('click', prefs.togglePref);
	$('.savulon').on('click', prefs.toggleRadio);
	$('#ptagadd').on('click', tagAdder.show);
	$('.close-tagadder').on('click', tagAdder.close);
	$('#addtoplaylist').on('click', addToPlaylist.show);
	$('.close-pladd').on('click', addToPlaylist.close);
	$('#bookmark').on('click', bookmarkAdder.show);
	$('#ban').on('click', nowplaying.ban);
	$('.close-bookmark').on('click', bookmarkAdder.close);
	$(document).on('click', ".clickaddtoplaylist", addToPlaylist.close);
	$(document).on('change', ".saveotron", prefs.saveTextBoxes);
	$(document).on('keyup', ".saveotron", prefs.saveTextBoxes);
	$(document).on('change', ".saveomatic", prefs.saveSelectBoxes);
	$('.clickreplaygain').on('click', player.controller.replayGain);
	$(document).on('click', '.clearbox.enter', makeClearWork);
	$(document).on('keydown', 'input[name="lfmuser"]', checkLfmUser);
	$(document).on('keyup', '.enter', onKeyUp);
	$(document).on('change', '.inputfile', inputFIleChanged);
	$(document).on('keyup', 'input.notspecial', filterSpecialChars);
	$(document).on('mouseenter', "#dbtags>.tag", showTagRemover);
	$(document).on('mouseleave', "#dbtags>.tag", hideTagRemover);
	$(document).on('click', 'body', closeMenus);
	$(document).on('click', '.tagremover:not(.plugclickable)', nowplaying.removeTag);
	$(document).on('click', '.choosepanel', uiHelper.changePanel);
	$(document).on('click', '.clickaddtoplaylist', infobar.addToPlaylist);
	$(document).on('click', '.search-category', searchManager.save_categories);
	if (uiHelper.is_touch_ui) {
		var s_start = 'touchstart';
		var s_end = 'touchend';
	} else {
		var s_start = 'mousedown';
		var s_end = 'mouseup';
	}
	$('.skip-button').on(s_start, infobar.startSkip);
	$('.skip-button').on(s_end, infobar.stopSkip);
}

 function checkLfmUser() {
	if($('input[name="lfmuser"]').val() == '') {
		$('#lastfmloginbutton').removeClass('notenabled').addClass('notenabled');
	} else {
		$('#lastfmloginbutton').removeClass('notenabled');
	}
}

function bindPlaylistClicks() {
	$("#sortable").off('click');
	$("#sortable").on('click', '.clickplaylist', playlist.handleClick);
}

function unbindPlaylistClicks() {
	$("#sortable").off('click');
}

function setControlClicks() {
	$('i.prev-button').on('click', playlist.previous);
	$('i.next-button').on('click', playlist.next);
	setPlayClicks();
}

function setPlayClicks() {
	$('i.play-button').on('click', infobar.playbutton.clicked);
	$('i.stop-button').on('click', player.controller.stop);
	$('i.stopafter-button').on('click', playlist.stopafter);
}

function offPlayClicks() {
	$('i.play-button').off('click', infobar.playbutton.clicked);
	$('i.stop-button').off('click', player.controller.stop);
	$('i.stopafter-button').off('click', playlist.stopafter);
}

function onBrowserClicked(event) {
	debug.debug("BROWSER","Click Event",event);
	var clickedElement = $(this);
	var parentElement = $(event.delegateTarget).attr('id');
	var source = parentElement.replace('information', '');
	debug.log("BROWSER","A click has occurred in",parentElement,source);
	event.preventDefault();
	browser.handleClick(source, clickedElement, event);
	return false;
}

function selectPlayable(event, clickedElement) {
	if (event) {
		event.stopImmediatePropagation();
	}
	if (!clickedElement) {
		clickedElement = $(this);
	}
	if ((clickedElement.hasClass("clickalbum") || clickedElement.hasClass('clickloadplaylist') || clickedElement.hasClass('clickloaduserplaylist'))
		&& !clickedElement.hasClass('noselect')) {
		albumSelect(event, clickedElement);
	} else if (clickedElement.hasClass("clickdisc")) {
		discSelect(event, clickedElement);
	} else if (clickedElement.hasClass("clicktrack") ||
				clickedElement.hasClass("clickcue") ||
				clickedElement.hasClass("clickstream")) {
		trackSelect(event, clickedElement);
	}
}

function playPlayable(event, clickedElement) {
	if (event) {
		event.stopImmediatePropagation();
	}
	if (!clickedElement) {
		clickedElement = $(this);
	}
	if (clickedElement.hasClass('clickdisc')) {
		discSelect(event, clickedElement);
		playlist.addItems($('.selected'),null);
	} else if (clickedElement.hasClass('clicktracksearch')) {
		playlist.search_and_add(clickedElement);
	} else {
		playlist.addItems(clickedElement, null);
	}
}

var playlistManager = function() {

	function playlistLoadString(plname) {
		debug.debug('PLLOAD',plname);
		return "player/utils/loadplaylists.php?playlist="+plname+'&target='+playlistTargetString(plname);
	}

	function playlistTargetString(plname) {
		debug.debug('PLTARGET',plname);
		return 'pholder_'+hex_md5(decodeURIComponent(plname));
	}

	return {

		loadPlaylistIntoTarget: function(playlist) {
			var target = playlistTargetString(playlist);
			clickRegistry.loadContentIntoTarget({
				target: $('#'+target),
				clickedElement: $('.openmenu[name="'+target+'"]'),
				uri: playlistLoadString(playlist)
			});
		},

		browsePlaylist: function(clickedElement, menutoopen) {
			debug.log("CLICKFUNCTIONS","Browsing playlist",plname);
			var plname = clickedElement.prev().val();
			string = playlistLoadString(plname);
			if ($('[name="'+menutoopen+'"]').hasClass('canreorder')) {
				uiHelper.makeSortablePlaylist(menutoopen);
			}
			return string;
		},

		browseUserPlaylist: function(clickedElement, menutoopen) {
			var plname = clickedElement.prev().val();
			debug.log("CLICKFUNCTIONS","Browsing playlist",plname);
			string = "player/utils/loadplaylists.php?userplaylist="+plname+'&target='+menutoopen;
			return string;
		},

		addTracksToPlaylist: function(plname, tracks) {
			player.controller.addTracksToPlaylist(
				plname,
				tracks,
				null,
				0
			);
		},

		dropOnPlaylist: function(event, ui) {
			event.stopImmediatePropagation();
			var which_playlist = ui.next().children('input.playlistname').val();
			var nextitem = ui.next().children('input.playlistpos').val();
			if (typeof nextitem == 'undefined') {
				nextitem = null;
			}
			debug.log('PLMAN','Dropped on',which_playlist,'at position',nextitem);

			var tracks = new Array();
			$.each($('.selected').filter(removeOpenItems), function (index, element) {
				if ($(element).hasClass('directory')) {
					var uri = decodeURIComponent($(element).children('input').first().attr('name'));
					debug.log("PLAYLISTMANAGER","Dragged Directory",uri,"to",which_playlist);
					tracks.push({dir: uri});
				} else if ($(element).hasClass('playlistitem') || $(element).hasClass('playlistcurrentitem')) {
					var playlistinfo = playlist.getId($(element).attr('romprid'));
					debug.log("PLAYLISTMANAGER","Dragged Playist Track",playlistinfo.file,"to",which_playlist);
					tracks.push({uri: playlistinfo.file});
				} else if ($(element).hasClass('playlistalbum')) {
					var album = playlist.getAlbum($(element).attr('name'));
					debug.log("PLAYLISTMANAGER","Dragged Playist Album",$(element).attr('name'),"to",which_playlist);
					album.forEach(function(playlistinfo) {
						tracks.push({uri: playlistinfo.file});
					});
				} else {
					var uri = decodeURIComponent($(element).attr("name"));
					debug.log("PLAYLISTMANAGER","Dragged",uri,"to",which_playlist);
					tracks.push({uri: uri});
				}
			});
			$('.selected').removeClass('selected');
			var playlistlength = (nextitem == null) ? 0 : ui.parent().find('.playlisttrack').length;
			if (tracks.length > 0) {
				debug.log("PLAYLISTMANAGER","Dragged to position",nextitem,'length',playlistlength);
				player.controller.addTracksToPlaylist(which_playlist,tracks,nextitem,playlistlength);
			}
		},

		dragInPlaylist: function(event, ui) {
			event.stopImmediatePropagation();
			var playlist_name = ui.children('input.playlistname').val();
			debug.log('PLMAN', 'Dragged inside playlist',playlist_name);
			var dragged_pos = parseInt(ui.children('input.playlistpos').val());
			var next_pos = parseInt(ui.next().children('input.playlistpos').val());
			if (typeof next_pos == 'undefined') {
				next_pos = parseInt(ui.prev().children('input.playlistpos').val())+1;
			}
			// Oooh it's daft but the position we have to send is the position AFTER the track has been
			// taken out of the list but before it's been put back in.
			// Additionally, since our range of tracks may not be contiguous and we have to move them
			// one at a time,  we need to calculate the new position for each of our selected tracks
			// after the previous one has been moved
			if (next_pos > dragged_pos) next_pos--;
			var from = [dragged_pos];
			var to = [next_pos];
			ui = ui.prev();
			var offset = 0;
			while (ui.hasClass('selected')) {
				offset++;
				if (next_pos < dragged_pos) {
					from.push(parseInt(ui.children('input.playlistpos').val()) + offset);
					to.push(next_pos);
				} else {
					from.push(parseInt(ui.children('input.playlistpos').val()));
					to.push(next_pos - offset);
				}
				ui = ui.prev();
			}
			player.controller.movePlaylistTracks(
				playlist_name,
				from,
				to
			);
		},

		deletePlaylistTrack: function(event, element) {
			element.makeSpinner();
			player.controller.deletePlaylistTrack(element.next().val(), element.attr('name'));
		},

		deletePlaylist: function(event, clickedElement) {
			player.controller.deletePlaylist(clickedElement.next().val());
		},

		deleteUserPlaylist: function(event, clickedElement) {
			player.controller.deleteUserPlaylist(clickedElement.next().val());
		},

		renamePlaylist: function(event, clickedElement) {
			player.controller.renamePlaylist(clickedElement.next().val(), event, player.controller.doRenamePlaylist);
		},

		renameUserPlaylist: function(event, clickedElement) {
			player.controller.renamePlaylist(clickedElement.next().val(), event, player.controller.doRenameUserPlaylist);
		}
	}
}();

function setDraggable(selector) {
	if (!uiHelper.is_touch_ui) {
		$(selector).trackdragger();
	}
}

function onKeyUp(e) {
	e.stopPropagation();
	e.preventDefault();
	if (e.keyCode == 13) {
		debug.debug("KEYUP","Enter was pressed");
		fakeClickOnInput($(e.target));
	}
}

function fakeClickOnInput(jq) {
	if (jq.next("button").length > 0) {
		jq.next("button").trigger('click');
	} else if (jq.parent().siblings("button").length > 0) {
		jq.parent().siblings("button").trigger('click');
	} else if (jq.hasClass('cleargroup')) {
		var p = jq.parent();
		while (!p.hasClass('cleargroupparent')) {
			p = p.parent();
		}
		p.find('button.cleargroup').trigger('click');
	}
}

function setAvailableSearchOptions() {
	if (!prefs.tradsearch) {
		$('.searchitem').not('[name="any"]').fadeOut('fast').find('input').val('');
		$('.searchterm[name="any"]').parent().prop('colspan', '2');
	} else {
		$('.searchitem').not(':visible').fadeIn('fast');
		$('.searchterm[name="any"]').parent().prop('colspan', '');
	}
}

function checkMetaKeys(event, element) {
	// Is the clicked element currently selected?
	var is_currently_selected = element.hasClass("selected") ? true : false;

	// Unselect all selected items if Ctrl or Meta is not pressed
	if (!event.metaKey && !event.ctrlKey && !event.shiftKey) {
		$(".selected").removeFromSelection();
		// If we've clicked a selected item without Ctrl or Meta,
		// then all we need to do is unselect everything. Nothing else to do
		if (is_currently_selected) {
			return true;
		}
	}

	if (event.shiftKey && last_selected_element !== null) {
		selectRange(last_selected_element, element);
	}

	return is_currently_selected;
}

jQuery.fn.addToSelection = function() {
	return this.each(function() {
		$(this).addClass('selected');
		if (prefs.clickmode == 'double') {
			$(this).find('div.clicktrackmenu').removeClass('invisibleicon');
		}
	});
}

jQuery.fn.removeFromSelection = function() {
	return this.each(function() {
		$(this).removeClass('selected');
		if (prefs.clickmode == 'double') {
			$(this).find('div.clicktrackmenu').not('.invisibleicon').addClass('invisibleicon');
			if ($(this).find('div.clicktrackmenu.menu_opened').length > 0 || $(this).find('div.clickalbummenu.menu_opened').length) {
				closePopupMenu();
			}
		}
	});
}

function albumSelect(event, element) {
	var is_currently_selected = checkMetaKeys(event, element);
	if (element.hasClass('clickloadplaylist') || element.hasClass('clickloaduserplaylist')) {
		var div_to_select = $('#'+element.children('i.menu').first().attr('name'));
	} else {
		var div_to_select = $('#'+element.attr("name"));
	}
	debug.log("GENERAL","Albumselect Looking for div",div_to_select,is_currently_selected);
	if (is_currently_selected) {
		element.removeFromSelection();
		last_selected_element = element;
		div_to_select.find(".playable").filter(noActionButtons).each(function() {
			$(this).removeFromSelection();
			last_selected_element = $(this);
		});
	} else {
		element.addToSelection();
		last_selected_element = element;
		div_to_select.find(".playable").filter(noActionButtons).each(function() {
			$(this).addToSelection();
			last_selected_element = $(this);
		});
	}
}

function discSelect(event, element) {
	var is_currently_selected = checkMetaKeys(event, element);
	if (is_currently_selected) {
		return false;
	}
	var thing = element.html();
	var discno = thing.match(/\d+$/);
	var num = discno[0];
	debug.log("GENERAL","Selecting Disc",num);
	var clas = ".disc"+num;
	element.nextAll(clas).addToSelection();
	element.addToSelection();
	last_selected_element = element.nextAll(clas).last();
}

function noActionButtons(i) {
	// Don't select child tracks of albums that have URIs
	if ($(this).hasClass('clicktrack') && $(this).hasClass('ninesix') &&
		$(this).parent().prev().hasClass('clicktrack')) {
		return false;
	}
	if ($(this).hasClass('podcastresume'))
		return false;
	return true;
}

function trackSelect(event, element) {
	var is_currently_selected = checkMetaKeys(event, element);
	if (is_currently_selected) {
		element.removeFromSelection();
	} else {
		element.addToSelection();
	}
	last_selected_element = element;
}

function selectRange(first, last) {
	debug.log("GENERAL","Selecting a range between:",first.attr("name")," and ",last.attr("name"));

	// Which list are we selecting from?
	var it = first;
	while(  !it.hasClass('selecotron') &&
			!it.hasClass("menu") &&
			it.prop("id") != "sources" &&
			it.prop("id") != "sortable" &&
			it.prop("id") != "bottompage" &&
			!it.hasClass("mainpane") &&
			!it.hasClass("top_drop_menu") )
	{
		it = it.parent();
	}
	debug.debug("GENERAL","Selecting within",it);

	var target = null;
	var done = false;
	$.each(it.find('.playable').not('.noselect'), function() {
		if ($(this).attr("name") == first.attr("name") && target === null) {
			target = last;
		}
		if ($(this).attr("name") == last.attr("name") && target === null) {
			target = first;
		}
		if (target !== null && $(this).attr("name") == target.attr("name")) {
			done = true;
		}
		if (!done && target !== null && !$(this).hasClass('selected')) {
			$(this).addToSelection();
		}
	});
}

function popupMenu(event, element) {

	// Make the popup for the albumbits menu
	var mouseX = event.pageX;
	var mouseY = event.pageY;
	var max_size = uiHelper.maxAlbumMenuSize();
	$('.albumbitsmenu').remove();
	var attributes_to_clone = ['rompr_id', 'rompr_tags', 'name', 'why', 'spalbumid'];
	var maindiv;
	var holderdiv;
	var button = element;
	var bw = $(element).outerHeight(true) / 2;
	var self = this;
	var selection = [];
	var actions = [];
	var justclosed = false;

	function setTop() {
		var top = Math.min(
			maindiv.offset().top,
			Math.max(
				0,
				max_size.y - maindiv.outerHeight(true) + 8
			)
		);
		maindiv.css({
			top: top
		});
	}

	this.create = function() {

		if ($(button).hasClass('menu_opened')) {
			$(button).removeClass('menu_opened');
			justclosed = true;
			return;
		}
		closePopupMenu();
		$(button).addClass('menu_opened');
		justclosed = false;
		maindiv = $('<div>', {
			id: 'popupmenu',
			class:'top_drop_menu dropshadow normalmenu albumbitsmenu',
			style: 'opacity:0 !important; display:block;'}
		).appendTo($('body'));
		holderdiv = $('<div>', {class: 'fullwidth'}).appendTo(maindiv);
		// Copy the attributes from the button to a holder div so that .parent() still works
		// and we don't have faffing with do we/don't we have custom scrollbars
		attributes_to_clone.forEach(function(a) {
			holderdiv.attr(a, $(element).attr(a));
		});
		clickRegistry.addClickHandlers('clicksubmenu', self.openSubMenu);
		$('#popupmenu').addCustomScrollBar();
		return holderdiv;
	}

	this.open = function() {
		if (justclosed) {
			return;
		}
		var my_width = maindiv.outerWidth(true);
		var left = 0;
		// Add or subtract half the parent height from the position, to prevent the menu
		// from covering the icon and thereby preventing us from clicking the icon to
		// close the menu. Why height? Because that's the width of the icon - the parent
		// is a flex div and can have any width, the icon is its background image.
		if (mouseX + bw + my_width > max_size.x) {
			left = mouseX - my_width - bw;
		} else {
			left = mouseX + bw;
		}
		maindiv.css({
			left: left,
			top: mouseY+'px',
			'max-height': max_size.y
		});
		setTop();
		maindiv.css({opacity: 1});
	}

	this.openSubMenu = function(e, element) {
		debug.log('POPUPMENU', 'Opening Submenu',element);
		self.markTrackTags();
		$(element).next().slideToggle('fast', setTop);
	}

	// addAction permits you to set callbacks for clickable menu items
	// that will preserve the selection after they complete.
	// The callback supplied must accept a clickedElement and a callback
	// which it will call when all the operations have completed

	this.addAction = function(classname, callback) {
		actions[classname] = callback;
		clickRegistry.addClickHandlers(classname, self.performAction);
	}

	this.performAction = function(event, clickedElement) {
		selection = new Array();
		debug.log('POPUPMENU', 'Saving selection');
		$('.selected').each(function() {
			var item = {name: $(this).attr('name'), menu: false};
			if ($(this).find('.clicktrackmenu').hasClass('menu_opened')) {
				item.menu = true;
			}
			selection.push(item);
		});
		for (var i in actions) {
			if (clickedElement.hasClass(i)) {
				clickedElement.find('.inline-icon').makeSpinner();
				debug.log('POPUPMENU', 'Calling',actions[i].name,'for action',i);
				actions[i](clickedElement, self.restoreSelection);
			}
		}
	}

	this.restoreSelection = function() {
		debug.log('POPUPMENU', 'Restoring Selection', selection);
		holderdiv.find('.spinner').stopSpinner();
		selection.forEach(function(n) {
			$('[name="'+n.name+'"]').not('.selected').not('.noselect').addToSelection();
			if (n.menu) {
				$('[name="'+n.name+'"]').find('.clicktrackmenu').addClass('menu_opened');
			}
		});
		self.markTrackTags();
		if ($('.selected').hasClass('playlistcurrentitem')) {
			// Make sure nowplaying updates its data if one of the selected tracks is the current track
			nowplaying.refreshUserMeta();
		}
	}

	this.markTrackTags = function() {
		var track_tags = [];
		$('.selected').each(function() {
			var tags = decodeURIComponent($(this).find('.clicktrackmenu').attr('rompr_tags')).split(', ');
			// This is how to append one array to another in JS. Don't do this if the second array is very large
			Array.prototype.push.apply(track_tags, tags);
		});
		$('.clicktagtrack').removeClass('clicktagtrack');
		$('.clickuntagtrack').removeClass('clickuntagtrack');
		$('.tracktagger').each(function() {
			$(this).children('i').remove();
			var mytag = $(this).find('span').html();
			if (track_tags.indexOf(mytag) == -1) {
				$(this).addClass('clicktagtrack').prepend('<i class="icon-blank inline-icon spinable"></i>')
			} else {
				$(this).addClass('clickuntagtrack').prepend('<i class="icon-tick inline-icon spinable"></i>')
			}
		});
	}

}

async function makeTrackMenu(e, element) {
	if (prefs.clickmode == 'single') {
		if ($(element).parent().hasClass('selected')) {
			$(element).parent().removeFromSelection();
			if (!$(element).hasClass('menu_opened')) {
				return;
			}
		} else {
			$(element).parent().addToSelection();
		}
	}

	var menu = new popupMenu(e, element);
	var d = menu.create();
	if (!d) {
		return;
	}

	if (prefs.cdplayermode) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem clickqueuetracks closepopup',
		}).html(language.gettext('label_addtoqueue')));
	} else if ($('.selected').length == 1) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem clickplayfromhere closepopup',
		}).html(language.gettext('label_playfromhere')));
	}

	if ($(element).hasClass('clickyoutubedl')) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem youtubedl closepopup',
		}).html(language.gettext('label_youtubedl')));
	}

	if ($(element).hasClass('clickremovedb')) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem clickremdb closepopup',
		}).html(language.gettext('label_removefromcol')));
	}

	var rat = $('<div>', {
		class: 'backhi clickable menuitem clicksubmenu',
	}).html(language.gettext("label_rating")).appendTo(d);
	var ratsub = $('<div>', {class:'submenu invisible'}).appendTo(d);
	[0,1,2,3,4,5].forEach(function(r) {
		ratsub.append($('<div>', {
			class: 'backhi clickable menuitem clickratetrack rate_'+r,
		}).html('<i class="icon-blank inline-icon spinable"></i><i class="icon-'+r+'-stars rating-icon-small"></i>'));

	});

	var tag = $('<div>', {
		class: 'backhi clickable menuitem clicksubmenu',
	}).html(language.gettext("label_tag")).appendTo(d);
	var tagsub = $('<div>', {class:'submenu invisible'}).appendTo(d);
	metaHandlers.genericQuery(
		'gettags',
		function(data) {
			data.forEach(function(tag){
				tagsub.append('<div class="backhi clickable menuitem tracktagger"><span>'+tag+'</span></div>');
			});
			menu.markTrackTags();
		},
		function() { debug.error('SUBMENU', 'Failed to populate tag menu') }
	);

	var pls = $('<div>', {
		class: 'backhi clickable menuitem clicksubmenu',
	}).html(language.gettext("button_addtoplaylist")).appendTo(d);
	var plssub = $('<div>', {class:'submenu invisible submenuspacer'}).appendTo(d);

	// Do this out of band because it can be slow with some backends
	$.ajax({
		url: 'player/utils/loadplaylists.php?addtoplaylistmenu=1',
		type: 'GET',
		cache: false
	})
	.done(function(data) {
		finishPlaylistMenu(data, plssub);
	})

	var banana = $(element).parent().next();
	while (banana.hasClass('podcastresume')) {
		$('<div>', {
			class: 'backhi clickable menuitem resetresume removeafter'
		}).html('Remove Bookmark &quot;'+banana.attr('bookmark')+'&quot;').appendTo(d);
		$('<input>', {
			type: 'hidden'
		}).val(banana.attr('bookmark')).appendTo(d);
		banana = banana.next();
	}

	menu.addAction('clickratetrack', metaHandlers.fromUiElement.rateTrack);
	menu.addAction('clicktagtrack', metaHandlers.fromUiElement.tagTrack);
	menu.addAction('clickuntagtrack', metaHandlers.fromUiElement.untagTrack);
	menu.addAction('resetresume', metaHandlers.fromUiElement.resetResumePosition);

	menu.open();
}

function finishPlaylistMenu(data, menu) {
	data.forEach(function(p) {
		var h = $('<div>', {class: "backhi clickable menuitem clickpltrack closepopup", name: p.name }).html(p.html).appendTo(menu);
	});
}

function makeAlbumMenu(e, element) {

	var menu = new popupMenu(e, element);
	var d = menu.create();
	if (!d) {
		return;
	}

	if ($(element).hasClass('clickalbumoptions')) {
		var cl = 'backhi clickable menuitem fakedouble closepopup '
		d.append($('<div>', {
			class: cl+'clicktrack',
			name: $(element).attr('uri')
		}).html(language.gettext('label_play_whole_album')));
		d.append($('<div>', {
			class: cl+'clickalbum',
			name: $(element).attr('why')+'album'+$(element).attr('who')
		}).html(language.gettext('label_from_collection')));
	}
	if ($(element).hasClass('clickcolloptions')) {
		var cl = 'backhi clickable menuitem clickalbum fakedouble closepopup'
		d.append($('<div>', {
			class: cl,
			name: $(element).attr('why')+'album'+$(element).attr('who')
		}).html(language.gettext('label_from_collection')));
	}
	if ($(element).hasClass('clickratedtracks')) {
		var opts = {
			r: language.gettext('label_with_ratings'),
			t: language.gettext('label_with_tags'),
			y: language.gettext('label_with_tagandrat'),
			u: language.gettext('label_with_tagorrat')
		}
		$.each(opts, function(i, v) {
			d.append($('<div>', {
				class: 'backhi clickable menuitem clickalbum fakedouble closepopup',
				name: i+'album'+$(element).attr('db_album')
			}).html(v))
		});
	}

	if ($(element).hasClass('clickamendalbum')) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem amendalbum closepopup',
			name: $(element).attr('who')
		}).html(language.gettext('label_amendalbum')));
	}
	if ($(element).hasClass('clickremovealbum')) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem removealbum closepopup',
			name: $(element).attr('who')
		}).html(language.gettext('label_removealbum')));
	}
	if ($(element).hasClass('clicksetasaudiobook')) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem setasaudiobook closepopup',
			name: $(element).attr('who')
		}).html(language.gettext('label_move_to_audiobooks')));
	}
	if ($(element).hasClass('clicksetasmusiccollection')) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem setasmusiccollection closepopup',
			name: $(element).attr('who')
		}).html(language.gettext('label_move_to_collection')));
	}
	if ($(element).hasClass('clickaddtollviabrowse')) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem addtollviabrowse closepopup',
			name: $(element).attr('uri')
		}).html(language.gettext('label_addtolistenlater')));
	}
	if ($(element).hasClass('clickllremove')) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem removefromll closepopup',
			name: $(element).attr('rompr_index')
		}).html(language.gettext('label_removefromlistenlater')));
	}
	if ($(element).hasClass('clickaddtocollectionviabrowse')) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem addtocollectionviabrowse closepopup',
			name: $(element).attr('uri')
		}).html(language.gettext('label_addtocollection')));
	}
	if ($(element).hasClass('clickusetrackimages')) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem usetrackimages closepopup',
			name: $(element).attr('who')
		}).html(language.gettext('label_usetrackimages')));
	}
	if ($(element).hasClass('clickunusetrackimages')) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem unusetrackimages closepopup',
			name: $(element).attr('who')
		}).html(language.gettext('label_unusetrackimages')));
	}
	if ($(element).hasClass('clickytdownloadall')) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem youtubedl_all closepopup',
			who: $(element).attr('who'),
			why: $(element).attr('why'),
			aname: $(element).attr('aname')
		}).html(language.gettext('label_youtubedl_all')));
	}

	menu.open();
}

function useTrackImages(e, element) {
	var data = {
		action: 'usetrackimages',
		value: ($(element).hasClass('usetrackimages')) ? 1 : 0,
		album_index: $(element).attr('name')
	};
	debug.debug("UI","Setting usetrackimages",data);
	metaHandlers.genericAction(
		[data],
		collectionHelper.updateCollectionDisplay,
		function(rdata) {
			debug.warn("RATING PLUGIN","Failure to do bumfinger", rdata);
			infobar.error(language.gettext('label_general_error'));
		}
	);
	return true;
}

function setAsAudioBook(e, element) {
	var data = {
		action: 'setasaudiobook',
		value: ($(element).hasClass('setasaudiobook')) ? 2 : 0,
		album_index: $(element).attr('name')
	};
	debug.debug("UI","Setting as audiobook",data);
	metaHandlers.genericAction(
		[data],
		collectionHelper.updateCollectionDisplay,
		function(rdata) {
			debug.warn("RATING PLUGIN","Failure to set as audiobook", rdata);
			infobar.error(language.gettext('label_general_error'));
		}
	);
	return true;
}

function amendAlbumDetails(e, element) {
	$(element).parent().remove();
	var albumindex = $(element).attr('name');
	var fnarkle = new popup({
		width: 400,
		title: language.gettext("label_amendalbum"),
		atmousepos: true,
		mousevent: e,
		id: 'amotron'+albumindex,
		toggleable: true
	});
	var mywin = fnarkle.create();
	if (mywin === false) {
		return;
	}
	var width = (language.gettext('label_albumartist').length-4).toString() + 'em';

	var d = $('<div>',{class: 'containerbox vertical-centre'}).appendTo(mywin);
	d.append('<div class="fixed" style="width:'+width+'">'+language.gettext('label_albumartist')+'</div>');
	var e = $('<div>',{class: 'expand'}).appendTo(d);
	var i = $('<input>',{class: 'enter', id: 'amendname'+albumindex, type: 'text', size: '200'}).appendTo(e);

	d = $('<div>',{class: 'containerbox vertical-centre'}).appendTo(mywin);
	d.append('<div class="fixed" style="width:'+width+'">'+language.gettext('info_year')+'</div>');
	e = $('<div>',{class: 'expand'}).appendTo(d);
	i = $('<input>',{class: 'enter', id: 'amenddate'+albumindex, type: 'text', size: '200'}).appendTo(e);

	var b = $('<button>',{class: 'fixed'}).appendTo(d);
	b.html(language.gettext('button_save'));
	fnarkle.useAsCloseButton(b, function() {
		actuallyAmendAlbumDetails(albumindex);
	});
	fnarkle.open();
}

function actuallyAmendAlbumDetails(albumindex) {
	var data = {
		action: 'amendalbum',
		album_index: albumindex,
	};
	var newartist = $('#amendname'+albumindex).val();
	var newdate = $('#amenddate'+albumindex).val();
	if (newartist) {
		data.albumartist = newartist;
	}
	if (newdate) {
		data.year = newdate;
	}
	debug.info("UI","Amending Album Details",data);
	metaHandlers.genericAction(
		[data],
		function(rdata) {
			collectionHelper.updateCollectionDisplay(rdata);
			playlist.repopulate();
		},
		function(rdata) {
			debug.warn("RATING PLUGIN","Failure amending album details", rdata);
			infobar.error(language.gettext('label_general_error'));
		}
	);
	return true;
}

function browseAndAddToListenLater(event, clickedElement) {
	var albumuri = decodeURIComponent(clickedElement.attr('name'));
	var isspot = albumuri.match(/spotify:album:(.+)/);
	if (isspot == null) {
		debug.log('ADLL', 'Adding',albumuri,'via backend browse');
		metaHandlers.addToListenLater(
			{
				action: 'browsetoll',
				uri: albumuri
			}
		);
	} else {
		spotify.album.getInfo(
			isspot[1],
			function(data) {
				debug.debug('ADDLL', 'Success', data);
				metaHandlers.addToListenLater(
					{
						action: 'addtolistenlater',
						json: data
					}
				);
			},
			function(data) {
				debug.error('ADDLL', 'Failed', data);
			},
			false
		);
	}
}

function removeFromListenLater(event, clickedElement) {
	if (typeof('albumstolistento') == 'undefined') {
		debug.error('WHOOPS', 'Asking to remove an album from listen later when that plugin is not loaded');
		return;
	}
	albumstolistento.removeId(clickedElement.attr('name'));
}

// Adds an album to the Music Collection by using MPD's find file on the album Uri.
// Called in response to a click on an element with a class of clickaddtocollectionviabrowse
// The element's name attribute should be the rawurlencode-d Album Uri

function browseAndAddToCollection(event, clickedElement) {
	metaHandlers.addAlbumUriToCollection(decodeURIComponent(clickedElement.attr('name')));
}
