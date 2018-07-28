var clickRegistry = function() {

    var clickHandlers = new Array();

    return {
        addClickHandlers: function(source, single, dbl) {
            clickHandlers.push({source: source, single: single, dbl: dbl});
        },

        bindClicks: function() {
            for (var i in clickHandlers) {
                debug.log("UI","Binding Click Handlers for ",clickHandlers[i].source);
                $(clickHandlers[i].source).click(clickHandlers[i].single);
                if (prefs.clickmode == 'double') {
                    $(clickHandlers[i].source).dblclick(clickHandlers[i].dbl);
                }
            }
        },

        unbindClicks: function() {
            for (var i in clickHandlers) {
                $(clickHandlers[i].source).off('click');
                $(clickHandlers[i].source).off('dblclick');
            }
        },

        reset: function() {
            clickRegistry.unbindClicks();
            clickRegistry.bindClicks();
        }
    }
}();

function setClickHandlers() {

    // Set up all our click event listeners

    layoutProcessor.bindSourcesClicks();

    clickRegistry.unbindClicks();
    clickRegistry.bindClicks();

    $('.infotext').off('click');
    $('.infotext').click(onBrowserClicked);

    $('.infotext').off('dblclick');
    $('.infotext').dblclick(onBrowserDoubleClicked);

    collectionHelper.enableCollectionUpdates();

}

function bindPlaylistClicks() {
    $("#sortable").off('click');
    $("#sortable").on('click', onPlaylistClicked);
}

function unbindPlaylistClicks() {
    $("#sortable").off('click');
}

function setControlClicks() {
    $('i.prev-button').on('click', playlist.previous);
    $('i.play-button').on('click', infobar.playbutton.clicked);
    $('i.stop-button').on('click', player.controller.stop);
    $('i.stopafter-button').on('click', playlist.stopafter);
    $('i.next-button').on('click', playlist.next);
}

function onBrowserClicked(event) {
    var clickedElement = findClickableBrowserElement(event);
    if (clickedElement.hasClass("infoclick")) {
        var parentElement = $(event.currentTarget.id).selector;
        var source = parentElement.replace('information', '');
        debug.log("BROWSER","A click has occurred in",parentElement,source);
        event.preventDefault();
        browser.handleClick(source, clickedElement, event);
        return false;
    } else {
        debug.log("BROWSER","Was clicked on non-infoclick element",event);
        return true;
    }
}

function onBrowserDoubleClicked(event) {
    var clickedElement = findClickableBrowserElement(event);
    if (clickedElement.hasClass("infoclick") && clickedElement.hasClass("draggable") && prefs.clickmode == "double") {
        debug.log("BROWSER","Was double clicked on element",clickedElement);
        debug.log("BROWSER","Track element was double clicked");
        event.preventDefault();
        playlist.addItems(clickedElement, null);
        return false;
    } else {
        return true;
    }
}

function findClickableBrowserElement(event) {
    var clickedElement = $(event.target);
    // Search upwards through the parent elements to find the clickable object
    while ( !clickedElement.hasClass("infoclick") &&
            !clickedElement.hasClass("infotext") &&
            clickedElement.prop("id") != "bottompage") {
        clickedElement = clickedElement.parent();
    }
    return clickedElement;
}

function onSourcesClicked(event) {
    var clickedElement = findClickableElement(event);
    debug.log('UI','Clicked On',clickedElement);
    if (clickedElement.hasClass("menu") || clickedElement.hasClass("podconf")) {
        if (clickedElement.hasClass('searchdir') ||
            clickedElement.hasClass('directory') ||
            clickedElement.hasClass('playlist') ||
            clickedElement.hasClass('userplaylist')) {
            doFileMenu(event, clickedElement);
        } else if (clickedElement.hasClass('album') || clickedElement.hasClass('artist')) {
            doAlbumMenu(event, clickedElement, null);
        } else {
            doMenu(event, clickedElement);
        }
    } else if (clickedElement.hasClass("clickremdb")) {
        event.stopImmediatePropagation();
        metaHandlers.fromUiElement.removeTrackFromDb(clickedElement);
    } else if (clickedElement.hasClass("clickalbummenu")) {
        event.stopImmediatePropagation();
        makeAlbumMenu(event, clickedElement);
    } else if (clickedElement.hasClass("amendalbum")) {
        event.stopImmediatePropagation();
        amendAlbumDetails(event, clickedElement);
    } else if (clickedElement.hasClass("fakedouble")) {
        onSourcesDoubleClicked(event);
    } else if (clickedElement.hasClass('clickdeleteplaylist')) {
        event.stopImmediatePropagation();
        player.controller.deletePlaylist(clickedElement.next().val());
    } else if (clickedElement.hasClass('clickdeleteuserplaylist')) {
        event.stopImmediatePropagation();
        player.controller.deleteUserPlaylist(clickedElement.next().val());
    } else if (clickedElement.hasClass('clickrenameplaylist')) {
        event.stopImmediatePropagation();
        player.controller.renamePlaylist(clickedElement.next().val(), event, player.controller.doRenamePlaylist);
    } else if (clickedElement.hasClass('clickrenameuserplaylist')) {
        event.stopImmediatePropagation();
        player.controller.renamePlaylist(clickedElement.next().val(), event, player.controller.doRenameUserPlaylist);
    } else if (clickedElement.hasClass('clickdeleteplaylisttrack')) {
        event.stopImmediatePropagation();
        player.controller.deletePlaylistTrack(
            clickedElement.next().val(),
            clickedElement.attr('name'),
            false);
    } else if (clickedElement.hasClass("podremove")) {
        event.stopImmediatePropagation();
        var n = clickedElement.attr('name');
        podcasts.removePodcast(n.replace(/podremove_/, ''));
    } else if (clickedElement.hasClass("podaction")) {
        event.stopImmediatePropagation();
        var n = clickedElement.attr('name').match('(.*)_(.*)');
        podcasts.channelAction(n[2],n[1]);
    } else if (clickedElement.hasClass("podglobal")) {
        event.stopImmediatePropagation();
        podcasts.globalAction(clickedElement.attr('name'), clickedElement);
    } else if (clickedElement.hasClass("podtrackremove")) {
        event.stopImmediatePropagation();
        var n = clickedElement.attr('name');
        var m = clickedElement.parent().attr('name');
        podcasts.removePodcastTrack(n.replace(/podtrackremove_/, ''), m.replace(/podcontrols_/,''));
    } else if (clickedElement.hasClass("clickpodsubscribe")) {
        event.stopImmediatePropagation();
        var index = clickedElement.next().val();
        podcasts.subscribe(index, clickedElement);
    } else if (clickedElement.hasClass("removepodsearch")) {
        event.stopImmediatePropagation();
        podcasts.removeSearch();
    } else if (clickedElement.hasClass("poddownload")) {
        event.stopImmediatePropagation();
        var n = clickedElement.attr('name');
        var m = clickedElement.parent().attr('name');
        podcasts.downloadPodcast(n.replace(/poddownload_/, ''), m.replace(/podcontrols_/,''));
    } else if (clickedElement.hasClass("podgroupload")) {
        event.stopImmediatePropagation();
        var n = clickedElement.attr('name');
        podcasts.downloadPodcastChannel(n.replace(/podgroupload_/, ''));
    } else if (clickedElement.hasClass("podmarklistened")) {
        event.stopImmediatePropagation();
        var n = clickedElement.attr('name');
        var m = clickedElement.parent().attr('name');
        podcasts.markEpisodeAsListened(n.replace(/podmarklistened_/, ''), m.replace(/podcontrols_/,''));
    } else if (clickedElement.hasClass('closepanel')) {
            event.stopImmediatePropagation();
            var id = clickedElement.parent().parent().attr('id');
            $('[name="'+id+'"]').removeClass('selected');
            clickedElement.parent().parent().remove();
    } else if (prefs.clickmode == "double") {
        if ((clickedElement.hasClass("clickalbum") && !clickedElement.hasClass('noselect')) ||
            clickedElement.hasClass("clickloadplaylist") ||
            clickedElement.hasClass("clickloaduserplaylist")) {
            event.stopImmediatePropagation();
            albumSelect(event, clickedElement);
        } else if (clickedElement.hasClass("clickdisc")) {
            event.stopImmediatePropagation();
            discSelect(event, clickedElement);
        } else if (clickedElement.hasClass("clicktrack") ||
                    clickedElement.hasClass("clickcue") ||
                    clickedElement.hasClass("clickstream")) {
            event.stopImmediatePropagation();
            trackSelect(event, clickedElement);
        }
    } else {
        onSourcesDoubleClicked(event);
    }
}

function onSourcesDoubleClicked(event) {
    debug.log("COLLECTION","Handling Click");
    var clickedElement = findClickableElement(event);
    if (clickedElement.hasClass('clickalbum') ||
        clickedElement.hasClass('clickartist') ||
        clickedElement.hasClass('searchdir') ||
        clickedElement.hasClass('clicktrack') ||
        clickedElement.hasClass('clickcue') ||
        clickedElement.hasClass("clickstream") ||
        clickedElement.hasClass("podcastresume") ||
        clickedElement.hasClass("clickloadplaylist") ||
        clickedElement.hasClass("clickloaduserplaylist")) {
        event.stopImmediatePropagation();
        playlist.addItems(clickedElement, null);
    } else if (clickedElement.hasClass('clickdisc')) {
        event.stopImmediatePropagation();
        discSelect(event, clickedElement);
        playlist.addItems($('.selected'),null);
    }
    if (clickedElement.hasClass('fakedouble' && clickedElement.hasClass('backhi'))) {
        clickedElement.parent().remove();
    }
}

function onPlaylistClicked(event) {
    var clickedElement = findClickableElement(event);
    if (clickedElement.hasClass("clickplaylist")) {
        event.stopImmediatePropagation();
        player.controller.playId(clickedElement.attr("romprid"));
    } else if (clickedElement.hasClass("clickremovetrack")) {
        event.stopImmediatePropagation();
        playlist.delete(clickedElement.attr("romprid"));
    } else if (clickedElement.hasClass("clickremovealbum")) {
        event.stopImmediatePropagation();
        playlist.deleteGroup(clickedElement.attr("name"));
    } else if (clickedElement.hasClass("clickaddwholealbum")) {
        event.stopImmediatePropagation();
        playlist.addAlbumToCollection(clickedElement.attr("name"));
    } else if (clickedElement.hasClass("clickrollup")) {
        event.stopImmediatePropagation();
        playlist.hideItem(clickedElement.attr("romprname"));
    } else if (clickedElement.hasClass("clickaddfave")) {
        event.stopImmediatePropagation();
        playlist.addFavourite(clickedElement.attr("name"));
    } else if (clickedElement.hasClass("playlistup")) {
        event.stopImmediatePropagation();
        playlist.moveTrackUp(clickedElement.findPlParent(), event);
    } else if (clickedElement.hasClass("playlistdown")) {
        event.stopImmediatePropagation();
        playlist.moveTrackDown(clickedElement.findPlParent(), event);
    } else if (clickedElement.hasClass('rearrange_playlist')) {
        clickedElement.findPlParent().addBunnyEars();
    }
}

jQuery.fn.findPlParent = function() {
    var el = $(this).parent();
    while (!el.hasClass('track') && !el.hasClass('item') && !el.hasClass('booger')) {
        el = el.parent();
    }
    return el;
}

function findClickableElement(event) {

    var clickedElement = $(event.target);
    // Search upwards through the parent elements to find the clickable object
    while (!clickedElement.hasClass("clickable") &&
            !clickedElement.hasClass("menu") &&
            !clickedElement.hasClass("mainpane") &&
            !clickedElement.hasClass("topdropmenu") &&
            clickedElement.prop("id") != "sources" &&
            clickedElement.prop("id") != "sortable" &&
            clickedElement.prop("id") != "bottompage" &&
            !(clickedElement.is(':button')) &&
            !(clickedElement.is(':input'))
        ) {
        clickedElement = clickedElement.parent();
    }
    return clickedElement;
}

function doMenu(event, element) {

    if (event) {
        event.stopImmediatePropagation();
    }
    var menutoopen = element.attr("name");
    debug.trace("UI","Doing Menu",menutoopen);
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
    }
    uiHelper.postAlbumMenu(element);
    if (menutoopen == 'advsearchoptions') {
        prefs.save({advanced_search_open: element.isOpen()});
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
    uiHelper.postAlbumMenu(element);
    return false;
}

function getAllTracksForAlbum(element, menutoopen) {
    debug.log("CLICKFUNCTIONS", "Album has link to get all tracks");
    element.makeSpinner();
    $.ajax({
        type: 'GET',
        url: 'albums.php?browsealbum='+menutoopen,
        success: function(data) {
            debug.log("CLICKFUNCTIONS", "Got data. Inserting it into ",menutoopen);
            element.stopSpinner();
            infobar.markCurrentTrack();
            $("#"+menutoopen).html(data);
            collectionHelper.scootTheAlbums($("#"+menutoopen));
        },
        error: function(data) {
            debug.error("CLICKFUNCTIONS", "Got NO data for ",menutoopen);
            element.stopSpinner();
        }
    });
}

function getAllTracksForArtist(element, menutoopen) {
    debug.log("CLICKFUNCTIONS", "Album has link to get all tracks for artist",menutoopen);
    element.makeSpinner();
    $.ajax({
        type: 'GET',
        url: 'albums.php?browsealbum='+menutoopen,
        success: function(data) {
            element.stopSpinner();
            var spunk = layoutProcessor.getArtistDestinationDiv(menutoopen);
            spunk.html(data);
            layoutProcessor.postAlbumActions();
            collectionHelper.scootTheAlbums(spunk);
            infobar.markCurrentTrack();
            uiHelper.fixupArtistDiv(spunk, menutoopen);
            layoutProcessor.postAlbumActions();
        },
        error: function(data) {
            element.stopSpinner();
        }
    });
}

function browsePlaylist(plname, menutoopen) {
    debug.log("CLICKFUNCTIONS","Browsing playlist",plname);
    string = "player/mpd/loadplaylists.php?playlist="+plname+'&target='+menutoopen;
    return string;
}

function browseUserPlaylist(plname, menutoopen) {
    debug.log("CLICKFUNCTIONS","Browsing playlist",plname);
    string = "player/mpd/loadplaylists.php?userplaylist="+plname+'&target='+menutoopen;
    return string;
}

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
                string = browsePlaylist(plname, menutoopen);
            } else if (element.hasClass('userplaylist')) {
                string = browseUserPlaylist(plname, menutoopen);
            } else {
                string = "dirbrowser.php?path="+plname+'&prefix='+menutoopen;
            }
            $('#'+menutoopen).load(string, function() {
                $(this).removeClass("notfilled");
                $(this).menuReveal();
                infobar.markCurrentTrack();
                element.stopSpinner();
                uiHelper.postAlbumMenu(element);
            });
        } else {
            $('#'+menutoopen).menuReveal();
        }
    } else {
        debug.log("UI","Hiding Menu");
        $('#'+menutoopen).menuHide(function() {
            element.toggleClosed();
            uiHelper.postAlbumMenu(element);
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
    if (e.keyCode == 13) {
        debug.log("KEYUP","Enter was pressed");
        if ($(e.target).next("button").length > 0) {
            $(e.target).next("button").click();
        } else {
            $(e.target).parent().siblings("button").click();
        }
    }
}

function setAvailableSearchOptions() {
    if (!prefs.tradsearch) {
        $('.searchitem').not('[name="any"]').fadeOut('fast').find('input').val('');
        $('.searchlabel').not('.nohide').hide(0, setSearchLabelWidth);
    } else if (prefs.searchcollectiononly) {
        $('.searchitem').not(':visible').not('[name="genre"]').not('[name="composer"]').not('[name="performer"]').fadeIn('fast');
        $('.searchitem[name="genre"]:visible,.searchitem[name="composer"]:visible,.searchitem[name="performer"]:visible').fadeOut('fast').find('input').val('');
        $('.searchlabel').show(0, setSearchLabelWidth);
    } else {
        $('.searchitem').not(':visible').fadeIn('fast');
        $('.searchlabel').show(0, setSearchLabelWidth);
    }
}

function checkMetaKeys(event, element) {
    // Is the clicked element currently selected?
    var is_currently_selected = element.hasClass("selected") ? true : false;

    // Unselect all selected items if Ctrl or Meta is not pressed
    if (!event.metaKey && !event.ctrlKey && !event.shiftKey) {
        $(".selected").removeClass("selected");
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

function albumSelect(event, element) {
    var is_currently_selected = checkMetaKeys(event, element);
    var div_to_select = element.hasClass('clickalbumname') ? element.next() : $('#'+element.attr("name"));
    debug.log("GENERAL","Albumselect Looking for div",div_to_select,is_currently_selected);
    if (is_currently_selected) {
        element.removeClass("selected");
        last_selected_element = element;
        div_to_select.find(".clickable").filter(noActionButtons).each(function() {
            $(this).removeClass("selected");
            last_selected_element = $(this);
        });
    } else {
        element.addClass("selected");
        last_selected_element = element;
        div_to_select.find(".clickable").filter(noActionButtons).each(function() {
            $(this).addClass("selected");
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
    element.nextAll(clas).addClass("selected");
    element.addClass('selected');
    last_selected_element = element.nextAll(clas).last();
}

function noActionButtons(i) {
    if ($(this).hasClass('clickdeleteplaylisttrack') ||
        $(this).hasClass('clickremdb') ||
        $(this).hasClass('clickalbummenu')) {
        return false;
    }
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
        element.removeClass("selected");
    } else {
        element.addClass("selected");
    }

    last_selected_element = element;

}

function checkNotSmallIcon() {
    if ($(this).hasClass('playlisticonr') || $(this).hasClass('podicon') || $(this).hasClass('noselect')) {
        return false;
    }
    return true;
}

function selectRange(first, last) {
    debug.log("GENERAL","Selecting a range between:",first.attr("name")," and ",last.attr("name"));

    // Which list are we selecting from?
    var it = first;
    while(!it.hasClass('selecotron') && !it.hasClass("menu") &&
            it.prop("id") != "sources" && it.prop("id") != "sortable" &&
            it.prop("id") != "bottompage" &&
            !it.hasClass("mainpane") && !it.hasClass("topdropmenu")) {
        it = it.parent();
    }
    debug.log("GENERAL","Selecting within",it);

    var target = null;
    var done = false;
    $.each(it.find('.clickable').filter(checkNotSmallIcon), function() {
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
            $(this).addClass('selected');
        }
    });
}

function checkServerTimeOffset() {
    $.ajax({
        type: "GET",
        url: "utils/checkServerTime.php",
        dataType: "json",
        success: function(data) {
            var time = Math.round(Date.now() / 1000);
            serverTimeOffset = time - data.time;
            debug.log("TIMECHECK","Browser Time is",time,". Server Time is",data.time,". Difference is",serverTimeOffset);
        },
        error: function(data) {
            debug.error("TIMECHECK","Failed to read server time");
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
    if ($(element).hasClass('clickamendalbum')) {
        d.append('<div class="backhi clickable clickicon noselection menuitem amendalbum" name="'+$(element).attr('name')+'">'+language.gettext('label_amendalbum')+'</div>');
    }
    if ($(element).hasClass('clickalbumoptions')) {
        d.append('<div class="backhi clickable clickicon noselection menuitem clicktrack fakedouble" name="'+$(element).parent().attr('name')+'">'+language.gettext('label_play_whole_album')+'</div>');
        d.append('<div class="backhi clickable clickicon noselection menuitem clickalbum fakedouble" name="aalbum'+$(element).attr('name')+'">'+language.gettext('label_from_collection')+'</div>');
    }
    if ($(element).hasClass('clickratedtracks')) {
        d.append('<div class="backhi clickable clickicon noselection menuitem clickalbum fakedouble" name="ralbum'+$(element).attr('name')+'">'+language.gettext('label_with_ratings')+'</div>');
        d.append('<div class="backhi clickable clickicon noselection menuitem clickalbum fakedouble" name="talbum'+$(element).attr('name')+'">'+language.gettext('label_with_tags')+'</div>');
        d.append('<div class="backhi clickable clickicon noselection menuitem clickalbum fakedouble" name="yalbum'+$(element).attr('name')+'">'+language.gettext('label_with_tagandrat')+'</div>');
        d.append('<div class="backhi clickable clickicon noselection menuitem clickalbum fakedouble" name="ualbum'+$(element).attr('name')+'">'+language.gettext('label_with_tagorrat')+'</div>');
    }
    d.appendTo($(element));
    d.slideToggle('fast');
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
    var i = $('<input>',{class: 'enter', id: 'amendname'+albumindex, type: 'text', size: '200'}).appendTo(e).keyup(onKeyUp);

    d = $('<div>',{class: 'containerbox dropdown-container'}).appendTo(mywin);
    d.append('<div class="fixed padright" style="width:'+width+'">'+language.gettext('info_year')+'</div>');
    e = $('<div>',{class: 'expand'}).appendTo(d);
    i = $('<input>',{class: 'enter', id: 'amenddate'+albumindex, type: 'text', size: '200'}).appendTo(e).keyup(onKeyUp);

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
            infobar.notify(infobar.ERROR,"Failed! Internal Error");
        }
    );
    return true;
}
