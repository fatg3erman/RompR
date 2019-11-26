// This skin works by taking what is basically a default layout
// and jQuery-ing it to feck to move things around.

// The biggest problem with this skin is that if we change stuff in the UI, it usually fucks it up.
// So be careful to test it.

// It's pretty fucking messy TBH. Need to do this better.

jQuery.fn.menuReveal = function(callback) {

    // 'self' is the menu being opened, which will alresady have contents

    var self = this;
    var id = this.attr('id');
    debug.trace("UI","Revealing",'#'+id);
    if ($('[name="'+id+'"]').hasClass('podcast')) {
        // Podcasts
        var p = $('[name="'+id+'"]').parent();
        p.addClass('tagholder_wide dropshadow').find('.containerbox.vertical').addClass('tleft bumpad');
        p.find('.helpfulalbum.expand').removeClass('expand').addClass('fixed');
        p.find('.helpfulalbum').css({'background-color': 'unset', 'background-image': 'unset'});
        p.find('div.albumthing').detach().appendTo(p);
        this.detach().addClass('minwidthed2').appendTo(p);
    } else if ($('[name="'+id+'"]').hasClass('radio')) {
        // Radio Browsers
        $('.collectionpanel').hide();
        // We can't remove the radio plugin panels, but we need to mark which ones are closed
        // Otherwise showPanel will reopen ALL the ones that have been opened if we switch away and
        // back to the radio stations panel.
        $('.collectionpanel.radiolist').addClass('closed');
        if (self.hasClass('dropmenu')) {
            self.find('.holderthing').removeClass('holderthing').addClass('containerbox wrap');
            self.detach().removeClass('dropmenu').addClass('collectionpanel radiolist containerbox wrap noselection').insertBefore($('#infoholder'));
            setDraggable('#'+id);
        }
        self.removeClass('closed');
    } else if ($('[name="'+id+'"]').hasClass('radiochannel')) {
        // Radio Stations
        var p = $('[name="'+id+'"]').parent();
        p.addClass('tagholder_wide dropshadow').find('.containerbox.radiochannel').addClass('tleft bumpad');
        p.find('.helpfulalbum.expand').removeClass('expand').addClass('fixed').css({'background-color': 'unset', 'background-image': 'unset'});
        this.detach().addClass('minwidthed2').appendTo(p);
        $('[name="'+id+'"]').find('div.albumthing').detach().prependTo(self);
    } else {
        // Albums and Playlists
        $('[name="'+id+'"]').find('div.helpfulalbum').css({'background-color': 'unset', 'background-image': 'unset'});
        $('[name="'+id+'"]').find('div.albumthing').detach().prependTo(self).find('.collectionicon').hide();
        $('[name="'+id+'"]').find('div.menuitem.configtitle').remove();
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
        if (self.parent().hasClass('album') || self.parent().hasClass('playlist') || self.parent().hasClass('userplaylist')) {
            // Albums and playlists
            debug.log("UI","Hiding album");
            self.parent().parent().removeClass('tagholder_wide dropshadow');
            var monkey = self.parent().parent().find('.helpfulalbum.fixed');
            monkey.removeClass('fixed').addClass('expand').css({'background-color': '', 'background-image': ''});
            self.parent().parent().find('.containerbox.wrap').children('.minwidthed2').remove();
            self.find('div.albumthing').detach().appendTo(monkey).find('.collectionicon').show();
        } else if (self.prev().prev().hasClass('podcast')) {
            // Podcasts
            self.parent().find('.containerbox.vertical').removeClass('tleft bumpad');
            self.parent().find('.helpfulalbum.fixed').not('.podcastcounts').removeClass('fixed').addClass('expand');
            self.parent().find('.helpfulalbum').css({'background-color': '', 'background-image': ''});
            self.prev('div.albumthing').detach().appendTo(self.prev().children('.helpfulalbum').first());
            self.parent().removeClass('tagholder_wide dropshadow');
            self.removeClass('minwidthed2');
        } else if (self.prev().hasClass('radiochannel')) {
            // Radio stations
            self.parent().find('.containerbox.radiochannel').removeClass('tleft bumpad');
            var monkey = self.parent().find('.helpfulalbum.fixed');
            monkey.removeClass('fixed').addClass('expand').css({'background-color': '', 'background-image': ''});
            self.parent().removeClass('tagholder_wide dropshadow');
            self.parent().find('div.albumthing').detach().appendTo(monkey)
            self.removeClass('minwidthed2');
        } else if (self.hasClass('radiolist')) {
            // Radio Browsers
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

jQuery.fn.adjustBoxSizes = function() {
    this.each(function() {
        var h = $(this);
        var width = calcPercentWidth(h, '.collectionitem', 220, h.width());
        h.find(".collectionitem").css('width', width.toString()+'%');
        h.find(".tagholder_wide").css("width", "98%");
        h.find(".brick_wide").css("width", "98%");
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
            $('.collectionpanel.'+source).not('.closed').show(0, function() {
                layoutProcessor.adjustBoxSizes();
            });
            switch (source) {
                case 'pluginplaylistslist':
                    layoutProcessor.adjustBoxSizes();
                    break;

                case 'albumlist':
                case 'audiobooklist':
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

        setupCollectionDisplay: function() {
            $('.collectionpanel.albumlist').remove();
            $('.collectionpanel.searcher').remove();
            $('.collectionpanel.audiobooklist').remove();
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
                        $('#audiobooks').detach().empty()
                            .removeClass('containerbox wrap collectionpanel').css('display', '')
                            .addClass('noborder')
                            .appendTo($('#audiobooklist'));
                        $('#collection, #searchresultholder, #audiobooks').off('click').off('dblclick');
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
                        $('#audiobooks').empty().detach()
                            .removeClass('noborder')
                            .addClass('containerbox wrap collectionpanel').css('display', '')
                            .insertBefore($('#infoholder'));
                    }
                    break;
            }
        },

        changeCollectionSortMode: function() {
            layoutProcessor.setupCollectionDisplay();
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
                debug.warn("LAYOUT","Was asked to scroll collection to something non-existent",2);
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

                    case 'audiobooklist':
                        $('.collectionpanel').hide(0);
                        $('#infopane #audiobooks').show(0);
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
            var newwidth = ws.x - $('#infobar').offset().left;
            $('#infobar').css('width', newwidth+'px');
            infobar.biggerize();
            browser.rePoint();
            $('.topdropmenu').fanoogleMenus();
            setBottomPanelWidths();
            layoutProcessor.adjustBoxSizes();
        },

        displayCollectionInsert: function(details) {
            debug.log("UI","Displaying Collection Insert",details);
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
                layoutProcessor.postAlbumActions();
                switch (prefs.sortcollectionby) {
                    case 'artist':
                        layoutProcessor.scrollCollectionTo($('[name="'+prefix+'artist'+details.artistindex+'"]'));
                        break;

                    default:
                        layoutProcessor.scrollCollectionTo($('[name="'+prefix+'album'+details.albumindex+'"]'));
                        break;

                }
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

            if (element.hasClass('album') || element.hasClass('playlist') || element.hasClass('userplaylist')) {
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
                    }
                } else if (element.parent().prop('id') == 'audiobooks') {
                    $('.collectionpanel.audiobooklist').remove();
                    $('#audiobooks .highlighted').removeClass('highlighted');
                    if (x.length == 0) {
                        t = $('<div>', {id: name, class: 'collectionpanel audiobooklist containerbox wrap noselection notfilled'}).insertBefore($('#infoholder'));
                    }
                } else {
                    $('.collectionpanel.searcher').remove();
                    $('#searchresultholder .highlighted').removeClass('highlighted');
                    if (x.length == 0) {
                        t = $('<div>', {id: name, class: 'collectionpanel searcher containerbox wrap noselection notfilled'}).insertBefore($('#infoholder'));
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
            $('#pluginplaylistslist .menuitem').not('.dropdown').not('.spacer').wrap('<div class="collectionitem fixed"></div>');
            $('#pluginplaylistslist .combobox-entry').parent().parent().parent().parent().addClass('brick_wide helpfulalbum');
            $('#pluginplaylistslist .collectionitem').not('.brick_wide').children('.menuitem.containerbox').addClass('vertical helpfulalbum');
            $('#pluginplaylistslist div[class$="-stars"]').removeClass('svg-square').addClass('rating-icon-big').css('height', '32px');
        },

        setupPersonalRadioAdditions: function() {
            $('#pluginplaylistslist [name^="spotiCrazyRadio"]').addClass('vertical helpfulalbum').wrap('<div class="collectionitem fixed"></div>');
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

            // $(".enter").on('keyup',  onKeyUp );
            $.each(my_scrollers,
                function( index, value ) {
                layoutProcessor.addCustomScrollBar(value);
            });

            $("#sources").find('.mCSB_draggerRail').resizeHandle({
                side: 'left',
                donefunc: setBottomPanelWidths
            });

            shortcuts.load();
            $("#collectionsearcher input").on('keyup',  function(event) {
                if (event.keyCode == 13) {
                    player.controller.search('search');
                }
            } );
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
            $('.clear_playlist').on('click', playlist.clear);
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
            $(document).on('mouseenter', '.tooltip', makeToolTip);
            $(document).on('mouseleave', '.tooltip', stopToolTip);
            $('#plmode').detach().appendTo('#amontobin').addClass('tright');
            $('#volume').volumeControl({
                orientation: 'vertical',
                command: player.controller.volume
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
            var dropdown = $('#'+v.why+'album'+albumindex).is(':visible');
            uiHelper.findAlbumDisplayer(v.why+'album'+albumindex).remove();
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
                uiHelper.findAlbumDisplayer(v.why+'album'+albumindex).find('.menu').trigger('click');
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
                infobar.notify(language.gettext('label_subscribed'));
                podcasts.doNewCount();
                layoutProcessor.postAlbumActions();
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
            debug.log('POSTALBUMMENU', element);
            var found = element.attr('name').match(/([abz])artist(\d+)/);
            if (found !== null) {
                clearTimeout(artistinfotimer);
                artistinfotimer = setTimeout(function() {
                    // Get artist info to display in the drop-down.
                    // Do it on a timer so (a) We don't spam last.fm with requests if we're clcking rapidly through artists
                    // (b) When we get here the div we're looking for isn't visible and so it can't be found
                    // (I'm not even sure it's been created by this point, I've forgotten how this works)
                    debug.log('POSTALBUMMENU', 'Artist', found[1], found[2]);
                    var name = $('#'+element.attr('name')).children('.configtitle').first().children('b').html();
                    debug.log('POSTALBUMMENU', 'Artist name',htmlspecialchars_decode(name));
                    var divname = 'potato_'+found[1]+'artist_'+found[2];
                    var destdiv = $('<div>', 
                        {   class: 'collectionitem fixed tagholder_wide invisible', 
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
                }, 1000);
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

        }

    }
}();
