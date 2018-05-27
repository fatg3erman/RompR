jQuery.fn.menuReveal = function(callback) {
    if (callback) {
        this.slideToggle('fast',callback);
    } else {
        this.slideToggle('fast');
    }
    return this;
}

jQuery.fn.menuHide = function(callback) {
    if (callback) {
        this.slideToggle('fast',callback);
    } else {
        this.slideToggle('fast');
    }
    return this;
}

jQuery.fn.isOpen = function() {
    return this.hasClass('icon-toggle-open');
}

jQuery.fn.isClosed = function() {
    return this.hasClass('icon-toggle-closed');
}

jQuery.fn.toggleOpen = function() {
    this.removeClass('icon-toggle-closed').addClass('icon-toggle-open');
    return this;
}

jQuery.fn.toggleClosed = function() {
    this.removeClass('icon-toggle-open').addClass('icon-toggle-closed');
    return this;
}

jQuery.fn.makeSpinner = function() {

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
}

jQuery.fn.stopSpinner = function() {

    return this.each(function() {
        $(this).removeClass('icon-spin6 spinner');
        if ($(this).attr("originalclass")) {
            $(this).addClass($(this).attr("originalclass"));
            $(this).removeAttr("originalclass");
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
        buttonclass: ""
    },options);

    this.each(function() {
        var tbc = "enter combobox-entry";
        if (settings.textboxextraclass) {
            tbc = tbc + " "+settings.textboxextraclass;
        }
        $(this).append(settings.labelhtml);
        var holder = $('<div>', { class: "expand"}).appendTo($(this));
        var textbox = $('<input>', { type: "text", class: tbc, name: settings.textboxname }).
            appendTo(holder);
        var dropbox = $('<div>', {class: "drop-box tagmenu dropshadow"}).appendTo(holder);
        var menucontents = $('<div>', {class: "tagmenu-contents"}).appendTo(dropbox);
        if (settings.buttontext !== null) {
            var submitbutton = $('<button>', {class: "fixed"+settings.buttonclass,
                style: "margin-left: 8px"}).appendTo($(this));
            submitbutton.html(settings.buttontext);
            if (settings.buttonfunc) {
                submitbutton.click(function() {
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
        textbox.hover(makeHoverWork);
        textbox.mousemove(makeHoverWork);
        textbox.click(function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            var position = getPosition(ev);
            var elemright = textbox.width() + textbox.offset().left;
            if (position.x > elemright - 24) {
                if (dropbox.is(':visible')) {
                    dropbox.slideToggle('fast');
                } else {
                    var data = settings.populatefunction(function(data) {
                        menucontents.empty();
                        for (var i in data) {
                            var d = $('<div>', {class: "backhi"}).appendTo(menucontents);
                            d.html(data[i]);
                            d.click(function() {
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

function getPanelWidths() {
    var sourcesweight = (prefs.sourceshidden) ? 0 : 1;
    var playlistweight = (prefs.playlisthidden) ? 0 : 1;
    var browserweight = (prefs.hidebrowser) ? 0 : 1;
    var sourceswidth = prefs.sourceswidthpercent*sourcesweight;
    var playlistwidth = prefs.playlistwidthpercent*playlistweight;
    var browserwidth = (100 - sourceswidth - playlistwidth)*browserweight;
    if (browserwidth < 0) browserwidth = 0;
    return ({infopane: browserwidth, sources: sourceswidth, playlist: playlistwidth});
}

function expandInfo(side) {
    switch(side) {
        case "left":
            var p = !prefs.sourceshidden;
            prefs.save({sourceshidden: p});
            break;
        case "right":
            var p = !prefs.playlisthidden;
            prefs.save({playlisthidden: p});
            break;
    }
    animatePanels();
    return false;
}

function setExpandIcons() {
    var i = (prefs.sourceshidden) ? "icon-angle-double-right" : "icon-angle-double-left";
    $("#expandleft").removeClass("icon-angle-double-right icon-angle-double-left").addClass(i);
    i = (prefs.playlisthidden) ? "icon-angle-double-left" : "icon-angle-double-right";
    $("#expandright").removeClass("icon-angle-double-right icon-angle-double-left").addClass(i);
}

function animatePanels() {
    var widths = getPanelWidths();
    widths.speed = { sources: 400, playlist: 400, infopane: 400 };
    // Ensure that the playlist and playlistcontrols don't get pushed off the edge
    if ($("#playlist").is(':hidden')) {
        var w = $("#infopane").width();
        w -= 12;
        $("#infopane").css({width: w+"px"});
        $("#infopanecontrols").css({width: w+"px"});
    } else {
        var w = $("#playlist").width();
        w -= 12;
        $("#playlist").css({width: w+"px"});
        $("#playlistcontrols").css({width: w+"px"});
    }
    $("#sources").animatePanel(widths);
    $("#sourcescontrols").animatePanel(widths);
    $("#playlist").animatePanel(widths);
    $("#playlistcontrols").animatePanel(widths);
    $("#infopane").animatePanel(widths);
    $("#infopanecontrols").animatePanel(widths);
}

jQuery.fn.animatePanel = function(options) {
    var settings = $.extend({},options);
    var panel = this.attr("id");
    var opanel = panel;
    panel = panel.replace(/controls/,'');
    if (settings[panel] > 0 && this.is(':hidden')) {
        this.show();
    }
    this.animate({width: settings[panel]+"%"},
        {
            duration: settings.speed[panel],
            always: function() {
                if (settings[panel] == 0) {
                    $(this).hide();
                } else {
                    if (opanel == "infopane") browser.rePoint();
                    if (opanel.match(/controls/)) {
                        setExpandIcons();
                        setTopIconSize(["#"+opanel]);
                    }
                }
            }
        }
    );
}

function doThatFunkyThang() {
    var widths = getPanelWidths();
    $("#sources").css("width", widths.sources+"%");
    $("#sourcescontrols").css("width", widths.sources+"%");
    $("#infopane").css("width", widths.infopane+"%");
    $("#infopanecontrols").css("width", widths.infopane+"%");
    $("#playlist").css("width", widths.playlist+"%");
    $("#playlistcontrols").css("width", widths.playlist+"%");
}

function hideBrowser() {
    if (prefs.hidebrowser) {
        prefs.save({playlistwidthpercent: 50, sourceswidthpercent: 50});
    } else {
        prefs.save({playlistwidthpercent: 25, sourceswidthpercent: 25});
    }
    animatePanels();
}

function setTopIconSize(panels) {
    var imw = (parseInt($('.topimg').first().css('margin-left')) +
        parseInt($('.topimg').first().css('margin-right')));
    panels.forEach( function(div) {
        if ($(div).is(':visible')) {
            var icons = $(div+" .topimg");
            var numicons = icons.length;
            var mw = imw*numicons;
            var iw = Math.floor(($(div).width() - mw)/numicons);
            if (iw > 24) iw = 24;
            if (iw < 2) iw = 2;
            icons.css({width: iw+"px", height: iw+"px", "font-size": (iw-2)+"px"});
        }
    });
}

function playlistControlButton(button) {
    if (!$("#playlistbuttons").is(':visible')) {
        togglePlaylistButtons()
    }
    $("#"+button).click();
}

function addCustomScrollBar(value) {
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
}

function flashTrack(uri, album) {
    infobar.markCurrentTrack();
    var thing = uri ? album : uri;
    $('[name="'+thing+'"]').makeFlasher({flashtime: 0.5, repeats: 5});
    // The timeout is so that markCurrentTrack doesn't fuck it up - these often
    // have CSS transitions that affect the scrollbar size
    setTimeout(function() {
        layoutProcessor.scrollCollectionTo($('[name="'+thing+'"]'));
    }, 1000);
}

var layoutProcessor = function() {

    function showPanel(source, callback) {
        if (callback) {
            $('#'+source).fadeIn('fast', callback);
        } else {
            $('#'+source).fadeIn('fast');
        }
    }

    my_scrollers = [ "#sources", "#infopane", "#pscroller", ".topdropmenu", ".drop-box" ];

    return {

        supportsDragDrop: true,
        hasCustomScrollbars: true,
        usesKeyboard: true,

        afterHistory: function() {
            browser.rePoint();
            setTimeout(function() { $("#infopane").mCustomScrollbar("scrollTo",0) }, 500);
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
            $("#chooserbuttons .topimg").tipTip({delay: 1000, edgeOffset: 8});
        },

        goToBrowserPanel: function(panel) {
            $("#infopane").mCustomScrollbar('update');
            $("#infopane").mCustomScrollbar("scrollTo","#"+panel+"information");
        },

        goToBrowserPlugin: function(panel) {
            setTimeout( function() { layoutProcessor.goToBrowserPanel(panel) }, 1000);
        },

        goToBrowserSection: function(section) {
            $("#infopane").mCustomScrollbar("scrollTo",section);
        },

        notifyAddTracks: function() { },

        maxPopupSize : function(winsize) {
            return {width: winsize.x - 32, height: winsize.y - 32};
        },

        playlistupdate: function(upcoming) {

        },

        updateInfopaneScrollbars: function() {
            $('#infopane').mCustomScrollbar('update');
        },

        hidePanel: function(panel, is_hidden, new_state) {
            if (is_hidden != new_state) {
                if (new_state && prefs.chooser == panel) {
                    $("#"+panel).fadeOut('fast');
                    var s = ["albumlist", "searcher", "filelist", "radiolist", "podcastslist", "playlistslist", "pluginplaylistslist"];
                    for (var i in s) {
                        if (s[i] != panel && !prefs["hide_"+s[i]]) {
                            layoutProcessor.sourceControl(s[i], null);
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
            var newheight = $("#bottompage").height() - $("#horse").outerHeight();
            if ($("#playlistbuttons").is(":visible")) {
                newheight -= $("#playlistbuttons").outerHeight();
            }
            $("#pscroller").css("height", newheight.toString()+"px");
            $('#pscroller').mCustomScrollbar("update");
        },

        playlistLoading: function() {
            infobar.notify(infobar.SMARTRADIO, "Preparing. Please Wait A Moment....");
        },

        scrollPlaylistToCurrentTrack: function() {
            if (prefs.scrolltocurrent && $('.track[romprid="'+player.status.songid+
                '"],.booger[romprid="'+player.status.songid+'"]').length > 0) {
                $('#pscroller').mCustomScrollbar("stop");
                $('#pscroller').mCustomScrollbar("update");
                var pospixels = Math.round($('div.track[romprid="'+player.status.songid+
                    '"],.booger[romprid="'+player.status.songid+'"]').position().top -
                    ($("#sortable").parent().parent().height()/2));
                if (pospixels < 0) { pospixels = 0 }
                if (pospixels > $("#sortable").parent().height()) {
                    pospixels = $("#sortable").parent().height();
                }
                debug.log("LAYOUT","Scrolling Playlist To Song:",player.status.songid);
                $('#pscroller').mCustomScrollbar(
                    "scrollTo",
                    pospixels,
                    { scrollInertia: 0 }
                );
            }
        },

        preHorse: function() {
            if (!$("#playlistbuttons").is(":visible")) {
                // Make the playlist scroller shorter so the window doesn't get a vertical scrollbar
                // while the buttons are being slid down
                var newheight = $("#pscroller").height() - 48;
                $("#pscroller").css("height", newheight.toString()+"px");
            }
        },

        scrollCollectionTo: function(jq) {
            if (jq) {
                debug.log("LAYOUT","Scrolling Collection To",jq, jq.position().top,$("#collection").parent().parent().parent().height()/2);
                var pospixels = Math.round(jq.position().top -
                    $("#collection").parent().parent().parent().height()/2);
                debug.log("LAYOUT","Scrolling Collection To",pospixels);
                $("#sources").mCustomScrollbar('update').mCustomScrollbar('scrollTo', pospixels,
                    { scrollInertia: 1000,
                      scrollEasing: 'easeOut' }
                );
            } else {
                debug.log("LAYOUT","Was asked to scroll collection to something non-existent",2);
            }
        },

        sourceControl: function(source, callback) {
            if ($('#'+source).length == 0) {
                prefs.save({chooser: 'albumlist'});
                source = 'albumlist';
            }
            if (source != prefs.chooser) {
                $('#'+prefs.chooser).fadeOut('fast', function() {
                    prefs.save({chooser: source});
                    showPanel(source, function() {
                        if (callback) { callback(); }
                    });
                });
            } else {
                showPanel(source, callback);
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
            layoutProcessor.setPlaylistHeight();
            setTopIconSize(["#sourcescontrols", "#infopanecontrols", "#playlistcontrols"]);
            infobar.rejigTheText();
            browser.rePoint();
        },

        fanoogleMenus: function(jq) {
            var avheight = $("#bottompage").height() - 16;
            var conheight = jq.children().first().children('.mCSB_container').height();
            var nh = Math.min(avheight, conheight);
            jq.css({height: nh+"px", "max-height":''});
            jq.mCustomScrollbar("update");
            if (jq.attr("id") == "hpscr") {
                $('#hpscr').mCustomScrollbar("scrollTo", '.current', {scrollInertia:0});
            }
        },

        displayCollectionInsert: function(details) {

            debug.log("COLLECTION","Displaying New Insert",details);
            layoutProcessor.sourceControl('albumlist');
            if (prefs.sortcollectionby == "artist" && $('i[name="aartist'+details.artistindex+'"]').isClosed()) {
                debug.log("COLLECTION","Opening Menu","aartist"+details.artistindex);
                doAlbumMenu(null, $('i[name="aartist'+details.artistindex+'"]'), false, function() {
                    if ($('i[name="aalbum'+details.albumindex+'"]').isClosed()) {
                        debug.log("COLLECTION","Opening Menu","aalbum"+details.albumindex);
                        doAlbumMenu(null, $('i[name="aalbum'+details.albumindex+'"]'), false, function() {
                            flashTrack(details.trackuri, 'aalbum'+details.albumindex);
                        });
                    } else {
                        flashTrack(details.trackuri, 'aalbum'+details.albumindex);
                    }
                });
            } else if ($('i[name="aalbum'+details.albumindex+'"]').isClosed()) {
                debug.log("COLLECTION","Opening Menu","aalbum"+details.albumindex);
                doAlbumMenu(null, $('i[name="aalbum'+details.albumindex+'"]'), false, function() {
                    flashTrack(details.trackuri,'aalbum'+details.albumindex);
                });
            } else {
                flashTrack(details.trackuri,'aalbum'+details.albumindex);
            }
        },

        setProgressTime: function(stats) {
            makeProgressOfString(stats);
        },

        setRadioModeHeader: function(html) {
            $("#plmode").html(html);
        },
        
        makeCollectionDropMenu: function(element, name) {
            var x = $('#'+name);
            // If the dropdown doesn't exist then create it
            if (x.length == 0) {
                if (element.parent().hasClass('album1')) {
                    var c = 'dropmenu notfilled album1';
                } else if (element.parent().hasClass('album2')) {
                    var c = 'dropmenu notfilled album2';
                } else {
                    var c = 'dropmenu notfilled';
                }
                var t = $('<div>', {id: name, class: c}).insertAfter(element.parent());
            }
        },

        initialise: function() {
            if (prefs.outputsvisible) {
                toggleAudioOutputs();
            }
            $("#sortable").disableSelection();
            setDraggable('#collection');
            setDraggable('#filecollection');
            setDraggable('#searchresultholder');
            setDraggable("#podcastslist");
            setDraggable("#somafmlist");
            setDraggable("#bbclist");
            setDraggable("#icecastlist");
            setDraggable('#artistinformation');
            setDraggable('#albuminformation');
            setDraggable('#storedplaylists');

            $("#sortable").acceptDroppedTracks({
                scroll: true,
                scrollparent: '#pscroller'
            });
            $("#sortable").sortableTrackList({
                items: '.sortable',
                outsidedrop: playlist.dragstopped,
                insidedrop: playlist.dragstopped,
                allowdragout: true,
                scroll: true,
                scrollparent: '#pscroller',
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
                addCustomScrollBar(value);
            });

            $("#sources").find('.mCSB_draggerRail').resizeHandle({
                adjusticons: ['#sourcescontrols', '#infopanecontrols'],
                side: 'left'
            });

            $("#infopane").find('.mCSB_draggerRail').resizeHandle({
                adjusticons: ['#playlistcontrols', '#infopanecontrols'],
                side: 'right'
            });

            shortcuts.load();
            $("#collectionsearcher input").keyup( function(event) {
                if (event.keyCode == 13) {
                    player.controller.search('search');
                }
            } );
            setControlClicks();
            $('.choose_albumlist').click(function(){layoutProcessor.sourceControl('albumlist')});
            $('.choose_searcher').click(function(){layoutProcessor.sourceControl('searcher',setSearchLabelWidth)});
            $('.choose_filelist').click(function(){layoutProcessor.sourceControl('filelist')});
            $('.choose_radiolist').click(function(){layoutProcessor.sourceControl('radiolist')});
            $('.choose_podcastslist').click(function(){layoutProcessor.sourceControl('podcastslist')});
            $('.choose_playlistslist').click(function(){layoutProcessor.sourceControl('playlistslist')});
            $('.choose_pluginplaylistslist').click(function(){layoutProcessor.sourceControl('pluginplaylistslist',setSpotiLabelWidth)});
            $('.open_albumart').click(openAlbumArtManager);
            $('#love').click(nowplaying.love);
            $("#ratingimage").click(nowplaying.setRating);
            $('.icon-rss.npicon').click(function(){podcasts.doPodcast('nppodiput')});
            $('#expandleft').click(function(){expandInfo('left')});
            $('#expandright').click(function(){expandInfo('right')});
            $('.clear_playlist').click(playlist.clear);
            $("#playlistname").parent().next('button').click(player.controller.savePlaylist);

            $(".lettuce,.tooltip").tipTip({delay: 1000, edgeOffset: 8});

            document.body.addEventListener('drop', function(e) {
                e.preventDefault();
            }, false);
            $('#albumcover').on('dragenter', infobar.albumImage.dragEnter);
            $('#albumcover').on('dragover', infobar.albumImage.dragOver);
            $('#albumcover').on('dragleave', infobar.albumImage.dragLeave);
            $("#albumcover").on('drop', infobar.albumImage.handleDrop);
            $("#volume").rangechooser({
                range: 100,
                ends: ['max'],
                onstop: infobar.volumeend,
                whiledragging: infobar.volumemoved,
                orientation: "vertical"
            });
        }
    }
}();
