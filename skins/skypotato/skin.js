// This skin works by taking what is basically a default layout
// and jQuery-ing it to feck to move things around.

// The biggest problem with this skin is that if we change stuff in the UI, itnusually fucks it up.
// So be careful to test it.

jQuery.fn.menuReveal = function(callback) {

    // 'self' is the menu being opened, which will alresady have contents

    var self = this;
    var id = this.attr('id');
    debug.trace("UI","Revealing",'#'+id);
    if ($('[name="'+id+'"]').hasClass('podcast')) {
        var p = $('[name="'+id+'"]').parent();
        p.addClass('tagholder_wide dropshadow').find('.containerbox.vertical').addClass('tleft bumpad');
        p.find('.helpfulalbum.expand').removeClass('expand').addClass('fixed');
        this.detach().addClass('minwidthed2').appendTo(p);
    } else if ($('[name="'+id+'"]').hasClass('radio')) {
        $('.collectionpanel').hide();
        // We can't remove the radio plugin panels, but we need to mark which ones are closed
        // Otherwise showPanel will reopen ALL the ones that have been opened if we switch away and
        // back to the radio stations panel.
        $('.collectionpanel.radiolist').addClass('closed');
        if (self.hasClass('dropmenu')) {
            self.find('.holderthing').removeClass('holderthing').addClass('containerbox wrap');
            self.detach().removeClass('dropmenu').addClass('collectionpanel radiolist containerbox wrap noselection').insertBefore($('#infoholder'));
            self.bindPlayClicks();
            clickRegistry.reset();
            setDraggable('#'+id);
        }
        self.removeClass('closed');
    } else if ($('[name="'+id+'"]').hasClass('radiochannel')) {
        var p = $('[name="'+id+'"]').parent();
        p.addClass('tagholder_wide dropshadow').find('.containerbox.radiochannel').addClass('tleft bumpad');
        p.find('.helpfulalbum.expand').removeClass('expand').addClass('fixed');
        this.detach().addClass('minwidthed2').appendTo(p);
    }
    this.show(0, function() {
        if (callback) callback.call(self);
        if (self.hasClass('containerbox')) {
            self.css('display', 'flex');
        }
        layoutProcessor.adjustBoxSizes();
    });
    return self;
}

jQuery.fn.menuHide = function(callback) {
    var self = this;
    debug.trace('UI',"Menu Hide",self);
    this.hide(0, function() {
        // Revert back to their default state so the open functions work
        debug.trace("UI","Hidden",self.parent());
        if (self.parent().hasClass('album')) {
            debug.log("UI","Hiding album");
            self.parent().parent().removeClass('tagholder_wide dropshadow');
            self.parent().parent().find('.helpfulalbum.fixed').removeClass('fixed').addClass('expand');
            self.parent().parent().find('.containerbox.wrap').children('.minwidthed2').remove();
        } else if (self.parent().hasClass('playlist')) {
            debug.log("UI","Hiding playlist");
            self.parent().parent().removeClass('tagholder_wide dropshadow');
            self.parent().parent().find('.helpfulalbum.fixed').removeClass('fixed').addClass('expand');
            self.parent().parent().find('.containerbox.wrap').children('.minwidthed2').remove();
        } else if (self.prev().hasClass('podcast')) {
            self.parent().find('.containerbox.vertical').removeClass('tleft bumpad');
            self.parent().find('.helpfulalbum.fixed').removeClass('fixed').addClass('expand');
            self.parent().removeClass('tagholder_wide dropshadow');
            self.removeClass('minwidthed2');
        } else if (self.prev().hasClass('radiochannel')) {
            self.parent().find('.containerbox.radiochannel').removeClass('tleft bumpad');
            self.parent().find('.helpfulalbum.fixed').removeClass('fixed').addClass('expand');
            self.parent().removeClass('tagholder_wide dropshadow');
            self.removeClass('minwidthed2');
        } else if (self.hasClass('radiolist')) {
            self.addClass('closed');
        }
        if (callback) callback.call(self);
        layoutProcessor.adjustBoxSizes();
    });
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

jQuery.fn.makeSpinner = function() {
    if (this.hasClass('icon-toggle-closed') ||
        this.hasClass('icon-toggle-open') ||
        this.hasClass('podicon')) {
        return this.each(function() {
            var originalclasses = new Array();
            var classes = '';
            if ($(this).attr("class")) {
                var classes = $(this).attr("class").split(/\s/);
            }
            for (var i = 0, len = classes.length; i < len; i++) {
                if (classes[i] == "invisible" || (/^icon/.test(classes[i]))) {
                    originalclasses.push(classes[i]);
                    $(this).removeClass(classes[i]);
                }
            }
            $(this).attr("originalclass", originalclasses.join(" "));
            $(this).addClass('icon-spin6 spinner');
        });
    } else {
        this.addClass('clickflash');
        return this;
    }
}

jQuery.fn.stopSpinner = function() {
    if (this.hasClass('spinner')) {
        return this.each(function() {
            $(this).removeClass('icon-spin6 spinner');
            if ($(this).attr("originalclass")) {
                $(this).addClass($(this).attr("originalclass"));
                $(this).removeAttr("originalclass");
            }
        });

    } else {
        this.removeClass('clickflash');
        return this;
    }
}

jQuery.fn.adjustBoxSizes = function() {
    this.each(function() {
        var h = $(this);
        var width = calcPercentWidth(h, '.collectionitem', 260, h.width());
        h.find(".collectionitem").css('width', width.toString()+'%');
        h.find(".tagholder_wide").css("width", "100%");
        h.find(".brick_wide").css("width", "100%");
    });
}

jQuery.fn.animatePanel = function(options) {
    var settings = $.extend({},options);
    var panel = this.attr("id");
    this.css('width', settings[panel]+'%');
}

function showHistory() {

}

var layoutProcessor = function() {

    var my_scrollers = [ "#sources", "#infopane", ".topdropmenu", ".drop-box" ];
    var rtime = '';
    var ptime = '';
    var headers = Array();
    var currheader = 0;
    var headertimer;
    var loading_ui = true;

    function showPanel(source) {
        debug.log("UI","Showing Panel",source);
        $('#'+source).show(0, function() {
            $('.collectionpanel.'+source).not('.closed').show(0, function() {
                layoutProcessor.adjustBoxSizes();
            });
            switch (source) {
                case'searcher':
                    setSearchLabelWidth();
                    break;

                case 'pluginplaylistslist':
                    setSpotiLabelWidth();
                    layoutProcessor.adjustBoxSizes();
                    break;

                case 'albumlist':
                    if (prefs.sortcollectionby != 'artist') {
                        layoutProcessor.adjustBoxSizes();
                    }
                    break;

                case 'playlistslist':
                    layoutProcessor.adjustBoxSizes();
                    break;

                case 'podcastslist':
                    fanooglePodcasts();
                    $('#infopane').mCustomScrollbar('scrollTo', '#podcastslist');
                    break;

                case 'historypanel':
                    $('#infoholder').show(0, browser.rePoint);
                    break;

                case 'infoholder':
                    browser.rePoint();
                    break;

                case 'pluginholder':
                    browser.rePoint();
                    layoutProcessor.adjustBoxSizes();
                    break;
            }
        });
    }

    function fanooglePodcasts() {
        if (!$('#fruitbat').hasClass('contaierbox')) {
            $('#fruitbat').removeClass('fullwidth').addClass('containerbox wrap');
            $('#podcast_search').removeClass('fullwidth').addClass('containerbox wrap');
        }
        layoutProcessor.adjustBoxSizes();
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

    return {

        supportsDragDrop: true,
        hasCustomScrollbars: true,
        usesKeyboard: true,
        sortFaveRadios: false,
        openOnImage: true,

        changeCollectionSortMode: function() {
            $('.collectionpanel.albumlist').remove();
            $('.collectionpanel.searcher').remove();
            switch (prefs.sortcollectionby) {
                case 'artist':
                    if ($('#collection').hasClass('containerbox')) {
                        $('.collectionpanel').hide(0);
                        $('#collection').empty().detach()
                            .removeClass('containerbox wrap collectionpanel').css('display', '')
                            .addClass('noborder')
                            .appendTo($('#albumlist'));
                        $('#searchresultholder').detach().empty()
                            .removeClass('containerbox wrap collectionpanel').css('display', '')
                            .addClass('noborder')
                            .appendTo($('#searcher'));
                        $('#collection, #searchresultholder').off('click').off('dblclick');
                    }
                    break;

                case 'album':
                case 'albumbyartist':
                    if (!$('#collection').hasClass('containerbox')) {
                        $('.collectionpanel').hide(0);
                        $('#collection').empty().detach()
                            .removeClass('noborder')
                            .addClass('containerbox wrap collectionpanel').css('display', '')
                            .insertBefore($('#infoholder'));
                        $('#searchresultholder').detach().empty()
                            .removeClass('noborder')
                            .addClass('containerbox wrap collectionpanel').css('display', '')
                            .insertBefore($('#infoholder'));
                    }
                    $('#collection, #searchresultholder').bindPlayClicks();
                    break;
            }
            collectionHelper.forceCollectionReload();
        },

        adjustBoxSizes: function() {
            debug.log("UI", "adjusting Box Sizes");
            $('.collectionpanel').adjustBoxSizes();
            $('#fruitbat:visible').adjustBoxSizes();
            $('#podcast_search:visible').adjustBoxSizes();
            $('#infopane #collection').adjustBoxSizes();
            $('#infopane #searchresultholder').adjustBoxSizes();
            $('#storedplaylists:visible').adjustBoxSizes();
            $('#pluginplaylistslist:visible').adjustBoxSizes();
        },

        bindSourcesClicks: function() {
            $('#sources, #podcastslist, #playlistslist').bindPlayClicks();
        },

        postAlbumActions: function(panel) {
            layoutProcessor.adjustBoxSizes();
        },

        hackForSkinsThatModifyStuff: function(id) {
            $(id+'.holderthing').removeClass('holderthing').addClass('containerbox wrap');
        },

        afterHistory: function() {
            setTimeout(function() { $("#infoholder").mCustomScrollbar("scrollTo",0) }, 500);
        },

        addInfoSource: function(name, obj) {
            $("#chooserbuttons").append($('<i>', {
                onclick: "browser.switchsource('"+name+"')",
                title: language.gettext(obj.text),
                class: obj.icon+' topimg sep fixed',
                id: "button_source"+name
            }));
        },

        setupInfoButtons: function() {
            $("#button_source"+prefs.infosource).addClass("currentbun");
            $("#chooserbuttons .topimg").tipTip({delay: 500, edgeOffset: 8});
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
                    var s = ["albumlist", "specialplugins", "searcher", "filelist", "radiolist", "playlistslist", "podcastslist", "pluginplaylistslist"];
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
            var w = getWindowSize();

        },

        playlistControlHotKey: function(button) {
            if (!$("#playlistbuttons").is(':visible')) {
                togglePlaylistButtons()
            }
            $("#"+button).click();
        },

        updateInfopaneScrollbars: function() {
            $('#infopane').mCustomScrollbar('update');
        },

        playlistLoading: function() {
            infobar.notify(infobar.SMARTRADIO, language.gettext('label_smartsetup'));
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
            if (jq) {
                debug.log("LAYOUT","Scrolling Collection To",jq);
                if (prefs.sortcollectionby == 'artist') {
                    $("#sources").mCustomScrollbar('update').mCustomScrollbar('scrollTo', jq,
                        { scrollInertia: 1000,
                          scrollEasing: 'easeOut' }
                    );
                } else {
                    $("#infopane").mCustomScrollbar('update').mCustomScrollbar('scrollTo', jq,
                        { scrollInertia: 1000,
                          scrollEasing: 'easeOut' }
                    );
                }
            } else {
                debug.log("LAYOUT","Was asked to scroll collection to something non-existent",2);
            }
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

                    case 'infoholder':
                    case 'pluginholder':
                    case 'podcastslist':
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

        adjustLayout: function() {
            var ws = getWindowSize();
            // Height of the bottom pane (chooser, info, playlist container)
            var newheight = ws.y - $("#bottompage").offset().top;
            $("#bottompage").css("height", newheight+"px");
            if (newheight < 540) {
                $('.topdropmenu').css('height',newheight+"px");
            } else {
                $('.topdropmenu').css('height', "");
            }
            var newwidth = ws.x - $('#infobar').offset().left;
            $('#infobar').css('width', newwidth+'px');
            infobar.rejigTheText();
            browser.rePoint();
            $('.topdropmenu').fanoogleMenus();
            setBottomPanelWidths();
            layoutProcessor.adjustBoxSizes();
        },

        displayCollectionInsert: function(details) {
            debug.log("UI","Displaying Collection Insert",details);
            infobar.notify(infobar.NOTIFY,"Added track to Collection");
            infobar.markCurrentTrack();
            if (prefs.chooser == 'albumlist') {
                layoutProcessor.postAlbumActions();
                switch (prefs.sortcollectionby) {
                    case 'artist':
                        layoutProcessor.scrollCollectionTo($('[name="aartist'+details.artistindex+'"]'));
                        break;

                    case 'album':
                    case 'albumbyartist':
                        layoutProcessor.scrollCollectionTo($('[name="aalbum'+details.albumindex+'"]'));
                        break;

                }
            }
        },

        playlistupdate: function(upcoming) {
            var time = 0;
            for(var i in upcoming) {
                time += upcoming[i].duration;
            }
            if (time > 0) {
                headers['upcoming'] = "Up Next : "+upcoming.length+" tracks, "+formatTimeString(time);
            } else {
                headers['upcoming'] = '';
            }
            layoutProcessor.doFancyHeaderStuff();
        },

        notifyAddTracks: function() {
            if (!playlist.radioManager.isRunning()) {
                clearTimeout(headertimer);
                $('#plmode').fadeOut(500, function() {
                    $('#plmode').html(language.gettext('label_addingtracks')).fadeIn(500);
                });
            }
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

            // Creates a nonexisted drop menu to hold contents.
            // 'element' is the PARENT menu element that has been clicked on.

            if (element.hasClass('album') || element.hasClass('playlist')) {
                // This is for an album clicked on in the album browser pane.
                var x = $('#'+name);
                element.parent().addClass('tagholder_wide dropshadow');
                element.parent().find('.helpfulalbum.expand').removeClass('expand').addClass('fixed');
                if (x.length == 0) {
                    element.parent().find('.containerbox.wrap').append($('<div>', {id: name, class: 'notfilled minwidthed2 expand'}));
                }
            } else if (element.hasClass('directory')) {
                var n = element.attr('name');
                if (n.indexOf('_') == -1) {
                    $('.collectionpanel.filelist').remove();
                    $('.collectionpanel').hide(0);
                    $('#filelist .highlighted').removeClass('highlighted');
                    element.addClass('highlighted');
                    var t = $('<div>', {id: name, class: 'collectionpanel filelist containerbox wrap noselection notfilled'}).insertBefore($('#infoholder'));
                    t.bindPlayClicks();
                    setDraggable('#'+name);
                } else {
                    var t= ($('<div>', {id: name, class: 'indent containerbox wrap notfilled'})).insertAfter(element);
                }
            } else if (element.hasClass('searchdir')) {

            } else {
                // This is for an artist clicked on in the artist list.
                var t;
                var x = $('#'+name);
                if (element.parent().prop('id') == 'collection') {
                    $('.collectionpanel.albumlist').remove();
                    $('#collection .highlighted').removeClass('highlighted');
                    if (x.length == 0) {
                        t = $('<div>', {id: name, class: 'collectionpanel albumlist containerbox wrap noselection notfilled'}).insertBefore($('#infoholder'));
                        t.bindPlayClicks();
                    }
                } else {
                    $('.collectionpanel.searcher').remove();
                    $('#searchresultholder .highlighted').removeClass('highlighted');
                    if (x.length == 0) {
                        t = $('<div>', {id: name, class: 'collectionpanel searcher containerbox wrap noselection notfilled'}).insertBefore($('#infoholder'));
                        t.bindPlayClicks();
                    }
                }
                $('.collectionpanel').hide(0);
                element.addClass('highlighted');
                setDraggable('#'+name);
            }
        },

        getArtistDestinationDiv: function(menutoopen) {
            switch (prefs.sortcollectionby) {
                case 'artist':
                    return $("#"+menutoopen).parent().parent().parent();
                    break;

                default:
                    return $('[name="'+menutoopen+'"]').parent();
                    break;
            }
        },

        setupPersonalRadio: function() {
            $('#pluginplaylistslist .menuitem').not('.dropdown').wrap('<div class="collectionitem fixed"></div>');
            $('#pluginplaylistslist .combobox-entry').parent().parent().parent().parent().addClass('brick_wide helpfulalbum');
            $('#pluginplaylistslist .enter').not('.combobox-entry').parent().parent().parent().parent().parent().addClass('brick_wide helpfulalbum');
            $('#pluginplaylistslist .collectionitem').not('.brick_wide').children('.menuitem.containerbox').addClass('vertical helpfulalbum');
            $('#pluginplaylistslist .collectionitem i.icon-toggle-closed').parent().removeClass('vertical');
            $('#pluginplaylistslist .toggledown').each(function() {
                var s = $(this).prev();
                $(this).detach().addClass('helpfulalbum').appendTo(s);
            });
        },

        setupPersonalRadioAdditions: function() {
            $('#pluginplaylistslist .crazyradio').addClass('vertical helpfulalbum').wrap('<div class="collectionitem fixed"></div>');
            layoutProcessor.adjustBoxSizes();
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
            setDraggable("#somafmlist");
            setDraggable("#bbclist");
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

            $(".stayopen").click(function(ev) {ev.stopPropagation() });

            $(".enter").keyup( onKeyUp );
            $.each(my_scrollers,
                function( index, value ) {
                layoutProcessor.addCustomScrollBar(value);
            });

            $("#sources").find('.mCSB_draggerRail').resizeHandle({
                side: 'left',
                donefunc: setBottomPanelWidths
            });

            shortcuts.load();
            $("#collectionsearcher input").keyup( function(event) {
                if (event.keyCode == 13) {
                    player.controller.search('search');
                }
            } );
            setControlClicks();
            $('.choose_albumlist').click(function(){layoutProcessor.sourceControl('albumlist')});
            $('.choose_searcher').click(function(){layoutProcessor.sourceControl('searcher')});
            $('.choose_filelist').click(function(){layoutProcessor.sourceControl('filelist')});
            $('.choose_radiolist').click(function(){layoutProcessor.sourceControl('radiolist')});
            $('.choose_podcastslist').click(function(){layoutProcessor.sourceControl('podcastslist')});
            $('.choose_playlistslist').click(function(){layoutProcessor.sourceControl('playlistslist')});
            $('.choose_pluginplaylistslist').click(function(){layoutProcessor.sourceControl('pluginplaylistslist')});
            $('.choose_specialplugins').click(function(){layoutProcessor.sourceControl('specialplugins')});
            $('.choose_infopanel').click(function(){layoutProcessor.sourceControl('infoholder')});
            $('.choose_history').click(function(){layoutProcessor.sourceControl('historypanel')});
            $('.open_albumart').click(openAlbumArtManager);
            $('#love').click(nowplaying.love);
            $("#ratingimage").click(nowplaying.setRating);
            $('.icon-rss.npicon').click(function(){podcasts.doPodcast('nppodiput')});
            $('#expandleft').click(function(){layoutProcessor.expandInfo('left')});
            $('.clear_playlist').click(playlist.clear);
            $("#playlistname").parent().next('button').click(player.controller.savePlaylist);

            $(".lettuce,.tooltip").tipTip({delay: 500, edgeOffset: 8});

            document.body.addEventListener('drop', function(e) {
                e.preventDefault();
            }, false);
            $('#albumcover').on('dragenter', infobar.albumImage.dragEnter);
            $('#albumcover').on('dragover', infobar.albumImage.dragOver);
            $('#albumcover').on('dragleave', infobar.albumImage.dragLeave);
            $("#albumcover").on('drop', infobar.albumImage.handleDrop);
            $("#tracktimess").click(layoutProcessor.toggleRemainTime);
            $('#plmode').detach().appendTo('#amontobin').addClass('tright');
            $("#volume").rangechooser({
                range: 100,
                ends: ['max'],
                onstop: infobar.volumeend,
                whiledragging: infobar.volumemoved,
                orientation: "vertical"
            });
        },

        // Optional Additions

        findAlbumDisplayer: function(key) {
            return $('.containerbox.wrap[name="'+key+'"]').parent();
        },

        findArtistDisplayer: function(key) {
            return $('div.menu[name="'+key+'"]');
        },

        insertAlbum: function(v) {
            var albumindex = v.id;
            $('#aalbum'+albumindex).html(v.tracklist);
            var dropdown = $('#aalbum'+albumindex).is(':visible');
            uiHelper.findAlbumDisplayer('aalbum'+albumindex).remove();
            switch (v.type) {
                case 'insertAfter':
                    debug.log("Insert After",v.where);
                    $(v.html).insertAfter(uiHelper.findAlbumDisplayer(v.where));
                    break;

                case 'insertAtStart':
                    debug.log("Insert At Start",v.where);
                    $(v.html).insertAfter($('#'+v.where).find('.clickalbum.ninesix.tagholder_wide'));
                    break;
            }
            if (dropdown) {
                uiHelper.findAlbumDisplayer('aalbum'+albumindex).find('.menu').click();
                infobar.markCurrentTrack();
            }
            layoutProcessor.postAlbumActions();
        },

        insertArtist: function(v) {
            switch (v.type) {
                case 'insertAfter':
                    debug.log("Insert After",v.where);
                    switch (prefs.sortcollectionby) {
                        case 'album':
                        case 'albumbyartist':
                            $(v.html).insertAfter(uiHelper.findAlbumDisplayer(v.where));
                            break;

                        case 'artist':
                            $(v.html).insertAfter(uiHelper.findArtistDisplayer(v.where));
                            break;
                    }
                    break;

                case 'insertAtStart':
                    debug.log("Insert At Start",v.where);
                    $(v.html).prependTo($('#'+v.where));
                    break;
            }
            layoutProcessor.postAlbumActions();
        },

        emptySearchResults: function() {
            $('.collectionpanel.searcher').remove();
            $('#searchresultholder').empty();
        },

        fixupArtistDiv(jq, name) {
            jq.addClass('containerbox wrap');
        },

        postPodcastSubscribe: function(data, index) {
            $('.menu[name="podcast_'+index+'"]').parent().fadeOut('fast', function() {
                $('.menu[name="podcast_'+index+'"]').parent().remove();
                $('#podcast_'+index).remove();
                $("#fruitbat").html(data);
                $("#fruitbat .fridge").tipTip({delay: 500, edgeOffset: 8});
                infobar.notify(infobar.NOTIFY, "Subscribed to Podcast");
                podcasts.doNewCount();
                layoutProcessor.postAlbumActions();
            });
        },

        createPluginHolder: function(icon, title) {
            var d = $('<div>', {class: 'topdrop'}).prependTo('#righthandtop');
            var i = $('<i>', {class: 'tooltip', title: title}).appendTo(d);
            i.addClass(icon);
            i.addClass('smallpluginicon clickicon');
            return d;
        }

    }
}();
