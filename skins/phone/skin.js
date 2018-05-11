jQuery.fn.menuReveal = function(callback) {
    if (callback) {
        this.show(0, callback);
    } else {
        this.show();
    }
    return this;
}

jQuery.fn.menuHide = function(callback) {
    if (callback) {
        this.hide(0, callback);
    } else {
        this.hide();
    }
    return this;
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
                submitbutton.click(function() {
                    settings.buttonfunc(textbox.val());
                });
            }
        }

        dropbutton.click(function(ev) {
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
                        d.click(function() {
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

function setTopIconSize(panels) {
    panels.forEach( function(div) {
        if ($(div).is(':visible')) {
            var jq = $(div+' .topimg:not(.noshrink):visible');
            var imh = parseInt(jq.first().css('max-height'))
            var numicons = jq.length+1;
            var iw = Math.min(Math.floor(($(div).width())/numicons), imh);
            jq.css({width: iw+"px", height: iw+"px", "font-size": iw+"px"});
            var cw = iw*numicons;
            var mar = Math.floor(((($(div).width()-8) - cw)/2)/numicons);
            jq.css({"margin-left": mar+"px", "margin-right": mar+"px"});
        }
    });
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

        afterHistory: function() {
            browser.rePoint();
            showHistory();
        },

        addInfoSource: function(name, obj) {
            $("#chooserbuttons").append($('<i>', {
                onclick: "browser.switchsource('"+name+"')",
                class: obj.icon+' topimg fixed',
                id: "button_source"+name
            }));
        },

        setupInfoButtons: function() { },

        goToBrowserPanel: function(panel) {
            $('#infopane').scrollTo('#'+panel+'information',800,{easing: 'swing'});
        },

        goToBrowserPlugin: function(panel) {
            layoutProcessor.sourceControl('infopane', function() {
                    layoutProcessor.goToBrowserPanel(panel)
            });
        },

        goToBrowserSection: function(section) {
            // Wikipedia mobile does not return contents
        },

        notifyAddTracks: function() {
            if (!playlist.radioManager.isRunning()) {
                infobar.notify(infobar.NOTIFY, language.gettext("label_addingtracks"));
            }
        },

        maxPopupSize: function(winsize) {
            return {width: winsize.x - 16, height: winsize.y - 16};
        },

        hidePanel: function(panel, is_hidden, new_state) { },

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
            infobar.notify(infobar.SMARTRADIO, "Preparing. Please Wait A Moment....");
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
            if (prefs.scrolltocurrent && $('playlistcurrentitem').length > 0) {
                $('#pscroller').scrollTo('.playlistcurrentitem',800,{offset: {top: -32}, easing: 'swing'});
            }
        },

        playlistupdate: function(upcoming) {

        },

        sourceControl: function(source, callback) {
            if (source == 'infopane') {
                $('#infobar').css('display', 'none');
            } else {
                $('#infobar').css('display', '');
            }
            if (source == "playlistm" && $('.choose_playlist').css('font-weight') == '900') {
                // hacky - set an irrelevant css parameter as a flag so we change behaviour
                source = "infobar";
            }
            $('.mainpane:not(.invisible):not(#'+source+')').addClass('invisible');
            $('#'+source).removeClass('invisible');
            prefs.save({chooser: source});
            layoutProcessor.adjustLayout();
            if (callback) {
                callback();
            }
        },

        adjustLayout: function() {
            if ((isLandscape() && prefs.chooser != 'infopane') || (!isLandscape() && prefs.chooser == 'infobar')) {
                $('#extraplaycontrols').css('display','none');
            } else {
                $('#extraplaycontrols').css('display','');
            }
            setTopIconSize(['#headerbar', '#chooserbuttons']);
            infobar.updateWindowValues();
            var ws = getWindowSize();
            var newheight = ws.y-$("#headerbar").outerHeight(true);
            var v = newheight - 32;
            $("#volumecontrol").css("height", v+"px");
            $("#loadsawrappers").css({height: newheight+"px"});
            if ($('#nowplaying').offset().top > 0) {
                var t = $('#toomanywrappers').height() - $('#nowplaying').offset().top + $("#headerbar").outerHeight(true);
                $("#nowplaying").css({height: t+"px"});
                infobar.rejigTheText();
            }
            layoutProcessor.setPlaylistHeight();
            browser.rePoint();
            setFunkyBoxSize();
        },

        fanoogleMenus: function(jq) {

        },

        displayCollectionInsert: function(d) {
            infobar.notify(infobar.NOTIFY,"Added track to Collection");
            infobar.markCurrentTrack();
        },

        setProgressTime: function(stats) {
            makeProgressOfString(stats);
        },

        themeChange: function() {
            $('.rangechooser').rangechooser('fill');
        },

        updateInfopaneScrollbars: function() {
        },

        setRadioModeHeader: function(html) {
            $("#plmode").html(html);
        },
        
        initialise: function() {

            if (!prefs.checkSet('clickmode')) {
                prefs.clickmode = 'single';
            }
            $(".dropdown").floatingMenu({ });
            setControlClicks();
            $('.choose_nowplaying').click(function(){layoutProcessor.sourceControl('infobar')});
            $('.choose_albumlist').click(function(){layoutProcessor.sourceControl('albumlist')});
            $('.choose_searcher').click(function(){layoutProcessor.sourceControl('searchpane', setSearchLabelWidth)});
            $('.choose_filelist').click(function(){layoutProcessor.sourceControl('filelist')});
            $('.choose_radiolist').click(function(){layoutProcessor.sourceControl('radiolist')});
            $('.choose_podcastslist').click(function(){layoutProcessor.sourceControl('podcastslist')});
            $('.choose_infopanel').click(function(){layoutProcessor.sourceControl('infopane')});
            $('.choose_playlistman').click(function(){layoutProcessor.sourceControl('playlistman')});
            $('.choose_pluginplaylists').click(function(){layoutProcessor.sourceControl('pluginplaylistholder', setSpotiLabelWidth)});
            $('.choose_prefs').click(function(){layoutProcessor.sourceControl('prefsm')});
            $('#choose_history').click(showHistory);
            $('.icon-rss.npicon').click(function(){podcasts.doPodcast('nppodiput')});
            $('#love').click(nowplaying.love);
            $('.choose_playlist').click(function(){layoutProcessor.sourceControl('playlistm')});
            $("#ratingimage").click(nowplaying.setRating);
            $("#playlistname").parent().next('button').click(player.controller.savePlaylist);
            $('.clear_playlist').click(playlist.clear);
        }

    }

}();

function popup(opts) {

    var self = this;
    var returnTo;
    var contents;

    var options = {
        width: 100,
        height: 100,
        title: "Popup",
        xpos: null,
        ypos : null,
        id: null,
        toggleable: false,
        hasclosebutton: true
    }

    for (var i in opts) {
        options[i] = opts[i];
    }

    this.create = function() {
        $('#popupwindow').empty();
        var titlebar = $('<div>', { class: "cheese" }).appendTo($("#popupwindow"));
        var tit = $('<div>', { class: "configtitle textcentre"}).appendTo(titlebar)
        tit.html('<b>'+options.title+'</b>');
        if (options.hasclosebutton) {
            tit.append('<i class="icon-cancel-circled playlisticonr clickicon tright"></i></div>');
        }
        titlebar.find('.icon-cancel-circled').click( function() {self.close(false)});
        contents = $('<div>',{class: 'popupcontents'}).appendTo($("#popupwindow"));
        return contents;
    }

    this.open = function() {
        $('#popupwindow').slideDown('fast');
    }

    this.close = function(callback) {
        if (callback) {
            callback();
        }
        $('#popupwindow').slideUp('fast');
    }

    this.addCloseButton = function(text, func) {
        var button = $('<button>',{class: 'tright'}).appendTo(contents);
        button.html(text);
        button.click(function() { self.close(func) });
    }

    this.useAsCloseButton = function(elem, func) {
        elem.click(function() { self.close(func) });
    }

    this.setContentsSize = function() {

    }

}

// Dummy functions standing in for widgets we don't use in this version -
// custom scroll bars, tipTip, and drag/drop stuff
jQuery.fn.tipTip = function() {
    return this;
}

function addCustomScrollBar(value) {

}

jQuery.fn.acceptDroppedTracks = function() {
    return this;
}

jQuery.fn.sortableTrackList = function() {
    return this;
}

jQuery.fn.trackDragger = function() {
    return this;
}
