jQuery.fn.menuReveal = function(callback) {
    if (this.hasClass('toggledown')) {
        if (callback) {
            this.slideToggle('fast',callback);
        } else {
            this.slideToggle('fast');
        }
    } else {
        this.findParentScroller().saveScrollPos();
        if (callback) {
            this.show(0, callback);
        } else {
            this.show();
        }
    }
    return this;
}

jQuery.fn.menuHide = function(callback) {
    if (this.hasClass('toggledown')) {
        if (callback) {
            this.slideToggle('fast',callback);
        } else {
            this.slideToggle('fast');
        }
    } else {
        if (callback) {
            this.hide(0, callback);
        } else {
            this.hide();
        }
        this.findParentScroller().restoreScrollPos();
    }
    return this;
}

jQuery.fn.isOpen = function() {
    if (this.hasClass('backmenu') || this.hasClass('icon-toggle-open')) {
        return true;
    } else {
        return false;
    }
}

jQuery.fn.isClosed = function() {
    if (this.hasClass('backmenu') || this.hasClass('icon-toggle-open')) {
        return false;
    } else {
        return true;
    }
}

jQuery.fn.toggleOpen = function() {
    if (this.hasClass('icon-toggle-closed')) {
        this.removeClass('icon-toggle-closed').addClass('icon-toggle-open');
    }
    return this;
}

jQuery.fn.toggleClosed = function() {
    if (this.hasClass('icon-toggle-open')) {
        this.removeClass('icon-toggle-open').addClass('icon-toggle-closed');
    }
    return this;
}

jQuery.fn.makeSpinner = function() {
    this.addClass('clickflash');
    return this;
}

jQuery.fn.stopSpinner = function() {
    this.removeClass('clickflash');
    return this;
}

jQuery.fn.findParentScroller = function() {
    var parentScroller = this.parent();
    while (!parentScroller.hasClass('scroller') && !parentScroller.hasClass('dropmenu') && !parentScroller.hasClass('phone')) {
        parentScroller = parentScroller.parent();
    }
    return parentScroller;
}

jQuery.fn.saveScrollPos = function() {
    this.prepend('<input type="hidden" name="restorescrollpos" value="'+this.scrollTop()+'" />');
    this.scrollTo(0);
    this.css('overflow-y', 'hidden');
}

jQuery.fn.restoreScrollPos = function() {
    var a = this.find('input[name="restorescrollpos"]');
    this.css('overflow-y', 'scroll');
    this.scrollTop(a.val());
    a.remove();
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
            var iw = Math.min(Math.floor(($(div).width()-16)/numicons), imh);
            jq.css({width: iw+"px", height: iw+"px"});
            var cw = iw*numicons;
            var mar = Math.floor(((($(div).width()-16) - cw)/2)/numicons);
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
            setTopIconSize(['#headerbar', '#chooserbuttons']);
            infobar.updateWindowValues();
            var ws = getWindowSize();
            var newheight = ws.y-$("#headerbar").outerHeight(true);
            var v = newheight - 32;
            $("#loadsawrappers").css({height: newheight+"px"});
            if ($('#nowplaying').offset().top > 0) {
                var t = $('#toomanywrappers').height() - $('#nowplaying').offset().top + $("#headerbar").outerHeight(true);
                $("#nowplaying").css({height: t+"px"});
                infobar.rejigTheText();
            }
            layoutProcessor.setPlaylistHeight();
            browser.rePoint();
            // Very very wierd thing happeneing, where this button, and only this button
            // gets an inlive css style of display: inline set sometime after page load
            // on a narrow screen. Non of the other onlywides do. Can't figure it out
            // so just clear it here.
            $('.choose_filelist').css('display','');
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

        updateInfopaneScrollbars: function() {
        },

        setRadioModeHeader: function(html) {
            $("#plmode").html(html);
        },

        makeCollectionDropMenu: function(element, name) {
            var x = $('#'+name);
            // If the dropdown doesn't exist then create it
            if (x.length == 0) {
                if (element.hasClass('album1')) {
                    var c = 'dropmenu notfilled album1';
                } else if (element.hasClass('album2')) {
                    var c = 'dropmenu notfilled album2';
                } else {
                    var c = 'dropmenu notfilled';
                }
                var t = $('<div>', {id: name, class: c}).insertAfter(element);
            }
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
            $("#volume").rangechooser({
                range: 100,
                ends: ['max'],
                onstop: infobar.volumeend,
                whiledragging: infobar.volumemoved,
                orientation: "horizontal"
            });
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
        helplink: null,
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
        if (options.helplink !== null) {
            tit.append('<a href="'+options.helplink+'" target="_blank"><i class="icon-info-circled playlisticonr clickicon tright"></i></a>');
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
