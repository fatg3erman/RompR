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
        var tbc = "enter";
        if (settings.textboxextraclass) {
            tbc = tbc + " "+settings.textboxextraclass;
        }
        $(this).append(settings.labelhtml);
        var holder = $('<div>', { class: "expand"}).appendTo($(this));
        var dropbutton = $('<i>', { class: 'fixed combo-button'}).appendTo($(this));
        var textbox = $('<input>', { type: "text", class: tbc, name: settings.textboxname }).appendTo(holder);
        var dropbox = $('<div>', {class: "drop-box tagmenu dropshadow"}).appendTo(holder);
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

        dropbutton.on('click', function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
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
                    dropbox.slideToggle('fast');
                });
            }
        });
    });
}

jQuery.fn.fanoogleMenus = function() {
    return this;
}

jQuery.fn.fanoogleTopMenus = function() {
    this.each(function() {
        $(this).css({height: ''});
        var top = $(this).offset().top;
        var height = $(this).outerHeight(true);
        var ws = getWindowSize();
        debug.log('FANOOGLE',$(this).attr('id'), top, height, ws.y);
        var nh = Math.min(height, ws.y - top);
        $(this).css({height: nh+'px'});
    });
    return this;
}

function showHistory() {
    $('#historypanel').slideToggle('fast');
}

var layoutProcessor = function() {

    function isLandscape() {
        if (window.innerHeight > window.innerWidth) {
            return false;
        } else {
            return true;
        }
    }

    return {

        supportsDragDrop: false,
        hasCustomScrollbars: false,
        usesKeyboard: false,
        sortFaveRadios: false,
        openOnImage: false,

        changeCollectionSortMode: function() {
            collectionHelper.forceCollectionReload();
        },

        postAlbumActions: function() {

        },

        afterHistory: function() {
            browser.rePoint();
            showHistory();
        },

        addInfoSource: function(name, obj) {
            $("#chooserbuttons").append($('<i>', {
                onclick: "browser.switchsource('"+name+"')",
                class: obj.icon+' topimg expand',
                id: "button_source"+name
            }));
        },

        setupInfoButtons: function() { },

        goToBrowserPanel: function(panel) {
            $('#infopane').scrollTo('#'+panel+'information',800,{easing: 'swing'});
        },

        goToBrowserPlugin: function(panel) {
            layoutProcessor.sourceControl('infopane');
            layoutProcessor.goToBrowserPanel(panel);
        },

        goToBrowserSection: function(section) {
            // Wikipedia mobile does not return contents
        },

        notifyAddTracks: function() {
            if (!playlist.radioManager.isRunning()) {
                infobar.notify(language.gettext("label_addingtracks"));
            }
        },

        hidePanel: function(panel, is_hidden, new_state) { },

        panelMapping: function() {
            return {
                "albumlist": 'albumlist',
                "searcher": 'searcher',
                "filelist": 'filelist',
                "radiolist": 'radiolist',
                "audiobooklist": "audiobooklist",
                "podcastslist": 'podcastslist',
                "playlistslist": 'playlistman',
                "pluginplaylistslist": 'pluginplaylists'
            }
        },

        setTagAdderPosition: function(position) {

        },

        setPlaylistHeight: function() {
            var newheight = $("#playlistm").height() - $("#horse").outerHeight(true);
            if ($("#playlistbuttons").is(":visible")) {
                newheight = newheight - $("#playlistbuttons").outerHeight(true) - 2;
            }
            $("#pscroller").css("height", newheight.toString()+"px");
        },

        playlistLoading: function() {
            infobar.smartradio(language.gettext('label_preparing'));
        },

        preHorse: function() {
            if (!$("#playlistbuttons").is(":visible")) {
                // Make the playlist scroller shorter so the window doesn't get a vertical scrollbar
                // while the buttons are being slid down
                var newheight = $("#pscroller").height() - 48;
                $("#pscroller").css("height", newheight.toString()+"px");
            }
        },

        scrollPlaylistToCurrentTrack: function() {
            var scrollto = playlist.getCurrentTrackElement();
            if (prefs.scrolltocurrent && scrollto.length > 0) {
                var offset = 0 - ($('#pscroller').outerHeight(true) / 2);
                $('#pscroller').scrollTo(scrollto, 800, {offset: {top: offset}, easing: 'swing'});
            }
        },

        playlistupdate: function(upcoming) {

        },

        addCustomScrollBar: function(value) {

        },

        sourceControl: function(source) {
            // hacky - set an irrelevant css parameter as a flag so we change behaviour
            var layoutflag = parseInt($('.choose_playlist').css('font-weight'));
            if ((source == 'playlistm' || source == 'infobar') && prefs.chooser != 'infopane' && layoutflag == 1000) {
                return;
            }
            if (source == 'infopane') {
                $('#infobar').css('display', 'none');
                if (layoutflag == 1000) {
                    $('#playlistm').css('display', 'none');
                }
            } else {
                $('#infobar').css('display', '');
                if (layoutflag == 1000) {
                    $('#playlistm').css('display', '');
                }
            }
            if (source == "playlistm" && layoutflag >= 900) {
                source = "infobar";
            }
            $('.mainpane:not(.invisible):not(#'+source+')').addClass('invisible');
            if (layoutflag == 1000) {
                $('#playlistm').removeClass('invisible');
            }
            $('#'+source).removeClass('invisible');
            prefs.save({chooser: source});
            layoutProcessor.adjustLayout();
            switch (source) {
                case'searchpane':
                    setSearchLabelWidth();
                    break;

                case 'pluginplaylistholder':
                    setSpotiLabelWidth();
                    break;

            }
        },

        adjustLayout: function() {
            infobar.updateWindowValues();
            // var ws = getWindowSize();
            var hh = $("#headerbar").outerHeight(true);
            // var mainheight = ws.y - hh;
            // $("#loadsawrappers").css({height: mainheight+"px"});
            var infoheight = $('#infobar').outerHeight(true) - $('#cssisshit').outerHeight(true);
            $('#toomanywrappers').css({height: infoheight+"px"});
            var np = $('#nowplaying');
            var nptop = np.offset().top;
            if (nptop > 0) {
                var t = infoheight - nptop + hh;
                np.css({height: t+"px"});
                infobar.biggerize();
            }
            layoutProcessor.setPlaylistHeight();
            browser.rePoint();
            $('.topdropmenu:visible').fanoogleTopMenus();
            if ($('.choose_playlist').css('font-weight') == '1000'
                && $('.mainpane:visible').not('#infobar').length == 0
                && (prefs.chooser == 'playlistm' || prefs.chooser == 'infobar')) {
                layoutProcessor.sourceControl('albumlist');
            }

        },

        showTagButton: function() {
            return false;
        },

        displayCollectionInsert: function(d) {
            infobar.notify(language.gettext('label_addedtocol'));
            infobar.markCurrentTrack();
        },

        setProgressTime: function(stats) {
            makeProgressOfString(stats);
        },

        updateInfopaneScrollbars: function() {
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

        getArtistDestinationDiv: function(menutoopen) {
            if (prefs.sortcollectionby == "artist") {
                return $("#"+menutoopen).parent();
            } else {
                return $("#"+menutoopen);
            }
        },

        initialise: function() {

            if (!prefs.checkSet('clickmode')) {
                prefs.clickmode = 'single';
            }
            $(".dropdown").floatingMenu({ });
            $('.topbarmenu').on('click', function() {
                $('.autohide:visible').not('#'+$(this).attr('name')).slideToggle('fast');
                $('#'+$(this).attr('name')).slideToggle('fast', function() {
                    $(this).fanoogleTopMenus();
                });
            });
            $('.autohide').on('click', function() {
                $(this).slideToggle('fast');
            });
            setControlClicks();
            $('.choose_nowplaying').on('click', function(){layoutProcessor.sourceControl('infobar')});
            $('.choose_albumlist').on('click', function(){layoutProcessor.sourceControl('albumlist')});
            $('.choose_searcher').on('click', function(){layoutProcessor.sourceControl('searchpane')});
            $('.choose_filelist').on('click', function(){layoutProcessor.sourceControl('filelist')});
            $('.choose_radiolist').on('click', function(){layoutProcessor.sourceControl('radiolist')});
            $('.choose_podcastslist').on('click', function(){layoutProcessor.sourceControl('podcastslist')});
            $('.choose_audiobooklist').on('click', function(){layoutProcessor.sourceControl('audiobooklist')});
            $('.choose_infopanel').on('click', function(){layoutProcessor.sourceControl('infopane')});
            $('.choose_playlistman').on('click', function(){layoutProcessor.sourceControl('playlistman')});
            $('.choose_pluginplaylists').on('click', function(){layoutProcessor.sourceControl('pluginplaylistholder')});
            $('.choose_prefs').on('click', function(){layoutProcessor.sourceControl('prefsm')});
            $('#choose_history').on('click', showHistory);
            $('.icon-rss.npicon').on('click', function(){podcasts.doPodcast('nppodiput')});
            $('.choose_playlist').on('click', function(){layoutProcessor.sourceControl('playlistm')});
            $("#ratingimage").on('click', nowplaying.setRating);
            $("#playlistname").parent().next('button').on('click', player.controller.savePlaylist);
            $('.clear_playlist').on('click', playlist.clear);
            $('#volume').volumeControl({
                orientation: 'horizontal',
                command: player.controller.volume
            });
            $(document).on('click', '.clickaddtoplaylist', addToPlaylist.close);
        },

        postPlaylistLoad: function() {
            $('#pscroller').find('.icon-cancel-circled').each(function() {
                var d = $('<i>', {class: 'icon-updown playlisticonr fixed clickplaylist clickicon rearrange_playlist'}).insertBefore($(this));
            });
        },

        getElementPlaylistOffset: function(element) {
            return element.position().top;
        }

    }

}();

// Dummy functions standing in for widgets we don't use in this version -
// custom scroll bars, and drag/drop stuff
jQuery.fn.acceptDroppedTracks = function() {
    return this;
}

jQuery.fn.sortableTrackList = function() {
    return this;
}

jQuery.fn.trackDragger = function() {
    return this;
}

var addToPlaylist = function() {
    return {
        open: function() {
            $('#pladddropdown').slideDown('fast');
        },
        close: function() {
            $('#pladddropdown').slideUp('fast');
        }
    }
}();
