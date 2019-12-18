var clickRegistry = function() {

	var clickHandlers = new Array();

	return {
		addClickHandlers: function(source, single) {
			clickHandlers.push({source: source, single: single});
		},

		farmClick: function(event, clickedElement) {
			for (var i in clickHandlers) {
				if (clickedElement.hasClass(clickHandlers[i].source)) {
					clickHandlers[i].single(event, clickedElement);
					break;
				}
			}
		}

	}
}();

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

	collectionHelper.enableCollectionUpdates();
}

/*

	Items which should respond to clicks in the main UI should have a class of 'clickable'
		These are passed in the first instance to onSourcesClicked
		Plugins can provide their own single click handler by adding an extra 'pluginclass' to the items
		and calling clickRegistry.addClickHandlers('pluginclass', handlerFunction).
		handlerFunction takes 2 parameters - the event and the clicked element

	Items for where the click should open a dropdown menu should have a class of 'openmenu'
	and NOT 'clickable'. The item's name attribute should be the id attribute of the dropdown panel,
	which should have a class of 'toggledown'
		Plugins can provide a callback function to populate the dropdown panel
		menuOpeners['id attribute (no hash)'] = populateFunction
		or if you have id attributes like 'something_1' and 'something_2' then menuOpeners['something'] will
		call the function with the numeric part of the id attribute as a parameter.
		menuClosers[] is also a thing
		Note there are special built-in attributes for many of the dropdowns - eg album, artist, directory etc
		which are handled by specific functions. Don't use these attributes.

	Info panel info plugins should use 'infoclick' and NOT 'clickable'. The info panel will pass these clicks
		through to the appropriate artist, album, or track child of the info collection

	Info Panel extra plugins should use 'infoclick plugclickable' and NOT 'clickable'. The info panel will
		pass these through to the plugin's handleClick method.

	Playable items in the Info panel should just use 'playable' and none of the other attributes

*/

function bindClickHandlers() {

	// Set up all our click event listeners

	$('.infotext').on('click', '.infoclick',  onBrowserClicked);

	$(document).on('click', '.openmenu.artist, .openmenu.album', function(event) {
		doAlbumMenu(event, $(this), null);
	});

	$(document).on('click', '.openmenu.searchdir, .openmenu.directory, .openmenu.playlist, .openmenu.userplaylist', function(event) {
		doFileMenu(event, $(this));
	});

	$(document).on('click', '.openmenu:not(.artist):not(.album):not(.searchdir):not(.directory):not(.playlist):not(.userplaylist)', function(event) {
		doMenu(event, $(this));
	});

	$(document).on('click', '.clickable', function(event) {
		onSourcesClicked(event, $(this));
	});

	$(document).on('click', '.clickaddtoplaylist', function(event) {
		infobar.addToPlaylist($(this));
	});

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
	debug.log("BROWSER","Click Event",event);
	var clickedElement = $(this);
	var parentElement = $(event.delegateTarget).attr('id');
	var source = parentElement.replace('information', '');
	debug.log("BROWSER","A click has occurred in",parentElement,source);
	event.preventDefault();
	browser.handleClick(source, clickedElement, event);
	return false;
}

function onSourcesClicked(event, clickedElement) {
	event.stopImmediatePropagation();
	debug.log('UI','Clicked On',clickedElement);
	if (clickedElement.hasClass("clickremdb")) {
		metaHandlers.fromUiElement.removeTrackFromDb(clickedElement);
	} else if (clickedElement.hasClass("clickratetrack")) {
		metaHandlers.fromUiElement.rateTrack(clickedElement);
	} else if (clickedElement.hasClass("clicktagtrack")) {
		metaHandlers.fromUiElement.tagTrack(clickedElement);
	} else if (clickedElement.hasClass("clickpltrack")) {
		metaHandlers.fromUiElement.tracksToPlaylist(clickedElement);
		clickedElement.parent().parent().parent().remove();
	} else if (clickedElement.hasClass("removealbum")) {
		metaHandlers.fromUiElement.removeAlbumFromDb(clickedElement);
	} else if (clickedElement.hasClass("clickalbummenu")) {
		makeAlbumMenu(event, clickedElement);
	} else if (clickedElement.hasClass("clicktrackmenu")) {
		makeTrackMenu(event, clickedElement);
	} else if (clickedElement.hasClass("clicksubmenu")) {
		makeSubMenu(event, clickedElement);
	} else if (clickedElement.hasClass("addtollviabrowse")) {
		browseAndAddToListenLater(clickedElement.attr('spalbumid'));
	} else if (clickedElement.hasClass("addtocollectionviabrowse")) {
		browseAndAddToCollection(clickedElement.attr('spalbumid'));
	} else if (clickedElement.hasClass("amendalbum")) {
		amendAlbumDetails(event, clickedElement);
	} else if (clickedElement.hasClass("setasaudiobook") ||
				clickedElement.hasClass("setasmusiccollection")) {
		setAsAudioBook(event, clickedElement);
	} else if (clickedElement.hasClass("fakedouble")) {
		playPlayable.call(clickedElement, event);
		clickedElement.parent().remove();
	} else if (clickedElement.hasClass('clickdeleteplaylist')) {
		player.controller.deletePlaylist(clickedElement.next().val());
	} else if (clickedElement.hasClass('clickdeleteuserplaylist')) {
		player.controller.deleteUserPlaylist(clickedElement.next().val());
	} else if (clickedElement.hasClass('clickrenameplaylist')) {
		player.controller.renamePlaylist(clickedElement.next().val(), event, player.controller.doRenamePlaylist);
	} else if (clickedElement.hasClass('clickrenameuserplaylist')) {
		player.controller.renamePlaylist(clickedElement.next().val(), event, player.controller.doRenameUserPlaylist);
	} else if (clickedElement.hasClass('clickdeleteplaylisttrack')) {
		playlistManager.deletePlaylistTrack(
			clickedElement,
			clickedElement.next().val(),
			clickedElement.attr('name'));
	} else {
		clickRegistry.farmClick(event, clickedElement);
	}
}

function selectPlayable(event) {
	event.stopImmediatePropagation();
	var clickedElement = $(this);
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

function playPlayable(event) {
	var clickedElement = $(this);
	event.stopImmediatePropagation();
	if (clickedElement.hasClass('clickdisc')) {
		discSelect(event, clickedElement);
		playlist.addItems($('.selected'),null);
	} else {
		playlist.addItems(clickedElement, null);
	}
}

jQuery.fn.findPlParent = function() {
	var el = $(this).parent();
	while (!el.hasClass('track') && !el.hasClass('item') && !el.hasClass('booger')) {
		el = el.parent();
	}
	return el;
}

function doMenu(event, element) {

	if (event) {
		event.stopImmediatePropagation();
	}
	var menutoopen = element.attr("name");
	debug.log("UI","Doing Menu",menutoopen);
	if (element.isClosed()) {
		element.toggleOpen();
		if (menuOpeners[menutoopen]) {
			menuOpeners[menutoopen]();
		} else if (menuOpeners[getMenuType(menutoopen)]) {
			menuOpeners[getMenuType(menutoopen)](getMenuIndex(menutoopen));
		}
		$('#'+menutoopen).menuReveal();
	} else {
		element.toggleClosed();
		$('#'+menutoopen).menuHide();
		if (menuClosers[menutoopen]) {
			menuClosers[menutoopen]();
		} else if (menuClosers[getMenuType(menutoopen)]) {
			menuClosers[getMenuType(menutoopen)](getMenuIndex(menutoopen));
		}
	}
	if (menutoopen == 'advsearchoptions') {
		prefs.save({advanced_search_open: element.isOpen()});
	}
	if (menutoopen.match(/alarmpanel/)) {
		setTimeout(alarmclock.whatAHack, 400);
	}
	return false;
}

function getMenuType(m) {
	var i = m.indexOf('_');
	if (i !== -1) {
		return m.substr(0, i);
	} else {
		return 'none';
	}
}

function getMenuIndex(m) {
	var i = m.indexOf('_');
	if (i != -1) {
		return m.substr(i+1);
	} else {
		debug.error("CLICKFUNCTIONS","Could not find menu index of",m);
		return '0';
	}
}

function doAlbumMenu(event, element, callback) {

	if (event) {
		event.stopImmediatePropagation();
	}
	var menutoopen = element.attr("name");
	if (element.isClosed()) {
		layoutProcessor.makeCollectionDropMenu(element, menutoopen);
		if ($('#'+menutoopen).hasClass("notfilled")) {
			debug.log("CLICKFUNCTIONS","Opening and filling",menutoopen);
			$('#'+menutoopen).load("albums.php?item="+menutoopen, function() {
				var self = $(this);
				self.removeClass("notfilled");
				self.menuReveal(function() {
					collectionHelper.scootTheAlbums(self);
					if (callback) callback();
					infobar.markCurrentTrack();
					if (self.find('input.expandalbum').length > 0 ) {
						getAllTracksForAlbum(element, menutoopen);
					} else if (self.find('input.expandartist').length > 0) {
						getAllTracksForArtist(element, menutoopen)
					}
					uiHelper.makeResumeBar(self);
					if (prefs.clickmode == 'single') {
						self.find('.invisibleicon').removeClass('invisibleicon');
					}
				});
			});
		} else {
			debug.log("Opening",menutoopen);
			$('#'+menutoopen).menuReveal(callback);
		}
		element.toggleOpen();
	} else {
		debug.log("Closing",menutoopen);
		$('#'+menutoopen).menuHide(callback);
		element.toggleClosed();
	}
	return false;
}

function getAllTracksForAlbum(element, menutoopen) {
	debug.log("CLICKFUNCTIONS", "Album has link to get all tracks");
	element.makeSpinner();
	$.ajax({
		type: 'GET',
		url: 'albums.php?browsealbum='+menutoopen
	})
	.done(function(data) {
		debug.log("CLICKFUNCTIONS", "Got data. Inserting it into ",menutoopen);
		element.stopSpinner();
		infobar.markCurrentTrack();
		uiHelper.albumBrowsed(menutoopen, data);
	})
	.fail(function(data) {
		debug.error("CLICKFUNCTIONS", "Got NO data for ",menutoopen);
		element.stopSpinner();
	});
}

function getAllTracksForArtist(element, menutoopen) {
	debug.log("CLICKFUNCTIONS", "Album has link to get all tracks for artist",menutoopen);
	element.makeSpinner();
	$.ajax({
		type: 'GET',
		url: 'albums.php?browsealbum='+menutoopen
	})
	.done(function(data) {
		element.stopSpinner();
		var spunk = uiHelper.getArtistDestinationDiv(menutoopen);
		spunk.html(data);
		uiHelper.doThingsAfterDisplayingListOfAlbums(spunk);
		collectionHelper.scootTheAlbums(spunk);
		infobar.markCurrentTrack();
		uiHelper.fixupArtistDiv(spunk, menutoopen);
	})
	.fail(function(data) {
		element.stopSpinner();
	});
}

var playlistManager = function() {

	function playlistLoadString(plname) {
		debug.log('PLLOAD',plname);
		return "player/mpd/loadplaylists.php?playlist="+plname+'&target='+playlistTargetString(plname);
	}

	function playlistTargetString(plname) {
		debug.log('PLTARGET',plname);
		return 'pholder_'+hex_md5(decodeURIComponent(plname));
	}

	return {

		loadPlaylistIntoTarget: function(playlist) {
			var t = playlistTargetString(playlist);
			var x = uiHelper.preparePlaylistTarget(t);
			debug.log('PLNAME', 'Loading playlist into',t);
			$('#'+t).load(
				playlistLoadString(playlist), function() {
					infobar.markCurrentTrack();
					if (x !== false) uiHelper.postPlaylistTarget(t, x);
				}
			);
		},

		browsePlaylist: function(plname, menutoopen) {
			debug.log("CLICKFUNCTIONS","Browsing playlist",plname);
			string = playlistLoadString(plname);
			if ($('[name="'+menutoopen+'"]').hasClass('canreorder')) {
				uiHelper.makeSortablePlaylist(menutoopen);
			}
			return string;
		},

		browseUserPlaylist: function(plname, menutoopen) {
			debug.log("CLICKFUNCTIONS","Browsing playlist",plname);
			string = "player/mpd/loadplaylists.php?userplaylist="+plname+'&target='+menutoopen;
			return string;
		},

		addTracksToPlaylist: function(plname, tracks) {
			player.controller.addTracksToPlaylist(
				plname,
				tracks,
				null,
				0,
				playlistManager.loadPlaylistIntoTarget            );
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
				player.controller.addTracksToPlaylist(which_playlist,tracks,nextitem,playlistlength,playlistManager.loadPlaylistIntoTarget);
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
				to,
				playlistManager.loadPlaylistIntoTarget
			);
		},

		deletePlaylistTrack(element, name, songpos) {
			element.makeSpinner();
			player.controller.deletePlaylistTrack(name, songpos, playlistManager.loadPlaylistIntoTarget);
		}
	}
}();

function doFileMenu(event, element) {

	if (event) {
		event.stopImmediatePropagation();
	}
	var menutoopen = element.attr("name");
	debug.log("UI","File Menu",menutoopen);
	if (element.isClosed()) {
		layoutProcessor.makeCollectionDropMenu(element, menutoopen);
		element.toggleOpen();
		if ($('#'+menutoopen).hasClass("notfilled")) {
			element.makeSpinner();
			var string;
			var plname = element.prev().val();
			if (element.hasClass('playlist')) {
				string = playlistManager.browsePlaylist(plname, menutoopen);
			} else if (element.hasClass('userplaylist')) {
				string = playlistManager.browseUserPlaylist(plname, menutoopen);
			} else {
				string = "dirbrowser.php?path="+plname+'&prefix='+menutoopen;
			}
			$('#'+menutoopen).load(string, function() {
				$(this).removeClass("notfilled");
				$(this).menuReveal();
				infobar.markCurrentTrack();
				element.stopSpinner();
			});
		} else {
			$('#'+menutoopen).menuReveal();
		}
	} else {
		debug.log("UI","Hiding Menu");
		$('#'+menutoopen).menuHide(function() {
			element.toggleClosed();
			// Remove this dropdown - this is so that when we next open it
			// mopidy will rescan it. This makes things like soundcloud and spotify update
			// without us having to refresh the window
			$('#'+menutoopen).remove();
		});
	}
	return false;
}

function setDraggable(selector) {
	if (layoutProcessor.supportsDragDrop) {
		$(selector).trackdragger();
	}
}

function onKeyUp(e) {
	e.stopPropagation();
	e.preventDefault();
	if (e.keyCode == 13) {
		debug.log("KEYUP","Enter was pressed");
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
	} else if (prefs.searchcollectiononly) {
		$('.searchitem').not(':visible').not('[name="genre"]').not('[name="composer"]').not('[name="performer"]').fadeIn('fast');
		$('.searchitem[name="genre"]:visible,.searchitem[name="composer"]:visible,.searchitem[name="performer"]:visible').fadeOut('fast').find('input').val('');
		$('.searchterm[name="any"]').parent().prop('colspan', '');
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
			$(this).find('div.clicktrackmenu').addClass('invisibleicon');
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
	debug.log("GENERAL","Selecting Disc");
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
			!it.hasClass("topdropmenu") )
	{
		it = it.parent();
	}
	debug.log("GENERAL","Selecting within",it);

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

function checkServerTimeOffset() {
	$.ajax({
		type: "GET",
		url: "utils/checkServerTime.php",
		dataType: "json"
	})
	.done(function(data) {
		var time = Math.round(Date.now() / 1000);
		serverTimeOffset = time - data.time;
		debug.log("TIMECHECK","Browser Time is",time,". Server Time is",data.time,". Difference is",serverTimeOffset);
	})
	.fail(function(data) {
		debug.error("TIMECHECK","Failed to read server time");
	});
}

function makeTrackMenu(e, element) {
	if ($(element).children().last().hasClass('albumbitsmenu')) {
		$(element).children().last().remove();
		if (prefs.clickmode == 'single') {
			if ($(element).parent().hasClass('selected')) {
				$(element).parent().removeClass('selected');
			}
		}
		return true;
	}
	$('.albumbitsmenu').remove();
	if (prefs.clickmode == 'single') {
		if (!$(element).parent().hasClass('selected')) {
			$(element).parent().addClass('selected');
		}
	}
	var d = $('<div>', {class:'topdropmenu dropshadow rightmenu normalmenu albumbitsmenu'});
	if ($(element).hasClass('clickremovedb')) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem clickremdb',
		}).html(language.gettext('label_removefromcol')));
	}

	var rat = $('<div>', {
		class: 'backhi clickable menuitem clicksubmenu',
	}).html(language.gettext("label_rating")).appendTo(d);
	var ratsub = $('<div>', {class:'topdropmenu dropshadow normalmenu invisible'}).appendTo(rat);
	[0,1,2,3,4,5].forEach(function(r) {
		ratsub.append($('<div>', {
			class: 'backhi clickable menuitem clickratetrack rate_'+r,
		}).html('<i class="icon-'+r+'-stars rating-icon-small"></i>'));

	});

	var tag = $('<div>', {
		class: 'backhi clickable menuitem clicksubmenu',
	}).html(language.gettext("label_tag")).appendTo(d);
	var tagsub = $('<div>', {class:'topdropmenu dropshadow normalmenu invisible tracktagmenu'}).appendTo(tag);

	var pls = $('<div>', {
		class: 'backhi clickable menuitem clicksubmenu',
	}).html(language.gettext("button_addtoplaylist")).appendTo(d);
	var plssub = $('<div>', {class:'topdropmenu dropshadow normalmenu invisible trackplmenu'}).appendTo(pls);

	d.appendTo($(element));
	d.slideToggle('fast', function() {
		$(this).jiggleToFit();
	});
}

function makeSubMenu(e, element) {
	var parentmenu = $(element).parent();
	var menu = $(element).find('div.topdropmenu');
	if (menu.is(':visible')) {
		menu.css({display: ''});
	} else {
		if (menu.hasClass('tracktagmenu')) {
			metaHandlers.genericAction(
				'gettags',
				function(data) {
					data.forEach(function(tag){
						menu.append('<div class="backhi clickable menuitme clicktagtrack">'+tag+'</div>');
					});
					menu.slideToggle('fast', function() {
						parentmenu.jiggleToFit();
					});
				},
				function() { debug.error('SUBMENU', 'Failed to populate tag menu') }
			);
		} else if (menu.hasClass('trackplmenu')) {
			$.get('player/mpd/loadplaylists.php?addtoplaylistmenu', function(data) {
				data.forEach(function(p) {
					var h = $('<div>', {class: "backhi clickable menuitme clickpltrack", name: p.name }).html(p.html).appendTo(menu);
				});
				menu.slideToggle('fast', function() {
					parentmenu.jiggleToFit();
				});
			});
		} else {
			menu.slideToggle('fast', function() {
				parentmenu.jiggleToFit();
			});
		}
	}
}

jQuery.fn.jiggleToFit = function() {
	// Make sure the popup menu doesn't disappear below the bottom of the window
	return this.each(function() {
		// Reset CSS so offset() reports a value we can use.
		// Hopefully all browsers recalculate the position immediately
		// before we work out what botompos is
		var self = $(this);
		var ws = uiHelper.maxAlbumMenuSize(self);

		self.css({top: '', height: '', width: ''});
		var height = Math.min(self.height(), (ws.y - ws.top));
		self.css({height: height+'px'});

		var bottompos = self.offset().top + self.height();
		if (bottompos > ws.y) {
			self.css({top: '-'+(bottompos - ws.y)+'px', right: "24px"});
		}

		self.css({width: ''});
		var leftpos = self.offset().left;
		if (leftpos < ws.left) {
			self.css({width: $(this).offset().right+'px'});
		}

	});
}

function makeAlbumMenu(e, element) {
	if ($(element).children().last().hasClass('albumbitsmenu')) {
		$(element).children().last().remove();
		return true;
	}
	$('.albumbitsmenu').remove();
	var d = $('<div>', {class:'topdropmenu dropshadow rightmenu normalmenu albumbitsmenu'});
	if ($(element).hasClass('clickalbumoptions')) {
		var cl = 'backhi clickable menuitem fakedouble '
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
		var cl = 'backhi clickable menuitem clickalbum fakedouble'
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
				class: 'backhi clickable menuitem clickalbum fakedouble',
				name: i+'album'+$(element).attr('name')
			}).html(v))
		});
	}
	if ($(element).hasClass('clickamendalbum')) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem amendalbum',
			name: $(element).attr('name')
		}).html(language.gettext('label_amendalbum')));
	}
	if ($(element).hasClass('clickremovealbum')) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem removealbum',
			name: $(element).attr('name')
		}).html(language.gettext('label_removealbum')));
	}
	if ($(element).hasClass('clicksetasaudiobook')) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem setasaudiobook',
			name: $(element).attr('name')
		}).html(language.gettext('label_move_to_audiobooks')));
	}
	if ($(element).hasClass('clicksetasmusiccollection')) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem setasmusiccollection',
			name: $(element).attr('name')
		}).html(language.gettext('label_move_to_collection')));
	}
	if ($(element).hasClass('clickaddtollviabrowse')) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem addtollviabrowse',
			spalbumid: $(element).attr('spalbumid')
		}).html(language.gettext('label_addtolistenlater')));
	}
	if ($(element).hasClass('clickaddtocollectionviabrowse')) {
		d.append($('<div>', {
			class: 'backhi clickable menuitem addtocollectionviabrowse',
			spalbumid: $(element).attr('spalbumid')
		}).html(language.gettext('label_addtocollection')));
	}
	d.appendTo($(element));
	d.show(0, function() {
		$(this).jiggleToFit();
	});
}

function setAsAudioBook(e, element) {
	var data = {
		action: 'setasaudiobook',
		value: ($(element).hasClass('setasaudiobook')) ? 2 : 0,
		albumindex: $(element).attr('name')
	};
	debug.log("UI","Setting as audiobook",data);
	metaHandlers.genericAction(
		[data],
		collectionHelper.updateCollectionDisplay,
		function(rdata) {
			debug.warn("RATING PLUGIN","Failure");
			infobar.error(language.gettext('label_general_error'));
		}
	);
	return true;
}

function amendAlbumDetails(e, element) {
	$(element).parent().remove();
	var albumindex = $(element).attr('name');
	var fnarkle = new popup({
		css: {
			width: 400,
			height: 300
		},
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

	var d = $('<div>',{class: 'containerbox dropdown-container'}).appendTo(mywin);
	d.append('<div class="fixed padright" style="width:'+width+'">'+language.gettext('label_albumartist')+'</div>');
	var e = $('<div>',{class: 'expand'}).appendTo(d);
	var i = $('<input>',{class: 'enter', id: 'amendname'+albumindex, type: 'text', size: '200'}).appendTo(e);

	d = $('<div>',{class: 'containerbox dropdown-container'}).appendTo(mywin);
	d.append('<div class="fixed padright" style="width:'+width+'">'+language.gettext('info_year')+'</div>');
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
		albumindex: albumindex,
	};
	var newartist = $('#amendname'+albumindex).val();
	var newdate = $('#amenddate'+albumindex).val();
	if (newartist) {
		data.albumartist = newartist;
	}
	if (newdate) {
		data.date = newdate;
	}
	debug.log("UI","Amending Album Details",data);
	metaHandlers.genericAction(
		[data],
		function(rdata) {
			collectionHelper.updateCollectionDisplay(rdata);
			playlist.repopulate();
		},
		function(rdata) {
			debug.warn("RATING PLUGIN","Failure");
			infobar.error(language.gettext('label_general_error'));
		}
	);
	return true;
}

function browseAndAddToListenLater(albumid) {
	spotify.album.getInfo(
		albumid,
		function(data) {
			debug.log('ADDLL', 'Success', data);
			metaHandlers.addToListenLater(data);
		},
		function(data) {
			debug.error('ADDLL', 'Failed');
		}, false
	);
}

function browseAndAddToCollection(albumid) {
	spotify.album.getInfo(
		albumid,
		function(data) {
			debug.log('ADDALBUM', 'Success', joinartists(data.artists), data);
			metaHandlers.fromSpotifyData.addAlbumTracksToCollection(data, joinartists(data.artists))
		},
		function(data) {
			debug.error('ADDALBUM', 'Failed');
		}, false
	);
}
