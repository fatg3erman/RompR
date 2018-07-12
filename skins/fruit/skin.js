
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
                        var i = (prefs.sourceshidden) ? "icon-angle-double-right" : "icon-angle-double-left";
                        $("#expandleft").removeClass("icon-angle-double-right icon-angle-double-left").addClass(i);
                    }
                }
            }
        }
    );
}

function showHistory() {
    
}

var layoutProcessor = function() {

    function showPanel(source) {
        $('#'+source).fadeIn('fast', function() {
            switch (source) {
                case'searcher':
                    setSearchLabelWidth();
                    break;
                    
                case 'pluginplaylistslist':
                    setSpotiLabelWidth();
            }
        });
    }

    function flashTrack(uri, album) {
        infobar.markCurrentTrack();
        var thing = uri ?  uri : album;
        $('[name="'+thing+'"]').makeFlasher({flashtime: 0.5, repeats: 5});
        // The timeout is so that markCurrentTrack doesn't fuck it up - these often
        // have CSS transitions that affect the scrollbar size
        setTimeout(function() {
            layoutProcessor.scrollCollectionTo($('[name="'+thing+'"]'));
        }, 1000);
    }

    function setBottomPanelWidths() {
        var widths = getPanelWidths();
        $("#sources").css("width", widths.sources+"%");
        $("#sourcescontrols").css("width", widths.sources+"%");
        $("#infopane").css("width", widths.infopane+"%");
        $("#infopanecontrols").css("width", widths.infopane+"%");
    }

    function getPanelWidths() {
        var sourcesweight = (prefs.sourceshidden) ? 0 : 1;
        var browserweight = (prefs.hidebrowser) ? 0 : 1;
        var sourceswidth = prefs.sourceswidthpercent*sourcesweight;
        var browserwidth = (100 - sourceswidth)*browserweight;
        if (browserwidth < 0) browserwidth = 0;
        return ({infopane: browserwidth, sources: sourceswidth});
    }

    function animatePanels() {
        var widths = getPanelWidths();
        widths.speed = { sources: 400, infopane: 400 };
        $("#sources").animatePanel(widths);
        $("#sourcescontrols").animatePanel(widths);
        $("#infopane").animatePanel(widths);
        $("#infopanecontrols").animatePanel(widths);
    }

    var my_scrollers = [ "#sources", "#infopane", ".topdropmenu", ".drop-box" ];
    var rtime = '';
    var ptime = '';
    var headers = Array();
    var currheader = 0;
    var headertimer;

    return {

        supportsDragDrop: true,
        hasCustomScrollbars: true,
        usesKeyboard: true,
        sortFaveRadios: true,
        openOnImage: false,

        changeCollectionSortMode: function() {
            collectionHelper.forceCollectionReload();
        },

        bindSourcesClicks: function() {
            $("#sources").bindPlayClicks();
        },

        postAlbumActions: function() {

        },

        afterHistory: function() {
            setTimeout(function() { $("#infopane").mCustomScrollbar("scrollTo",0) }, 500);
        },

        addInfoSource: function(name, obj) {
            $("#chooserbuttons").append($('<i>', {
                onclick: "browser.switchsource('"+name+"')",
                title: language.gettext(obj.text),
                class: obj.icon+' topimg sep expand',
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

        notifyAddTracks: function() {
            if (!playlist.radioManager.isRunning()) {
                clearTimeout(headertimer);
                $('#plmode').fadeOut(500, function() {
                    $('#plmode').html(language.gettext('label_addingtracks')).fadeIn(500);
                });
            }
        },

        toggleAudioOutpts: function() {
            prefs.save({outputsvisible: !$('#outputbox').is(':visible')});
            $("#outputbox").animate({width: 'toggle'},'fast',function() {
                infobar.biggerize();
            });
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
            if ($('#'+source).length == 0) {
                prefs.save({chooser: 'albumlist'});
                source = 'albumlist';
            }
            if (source != prefs.chooser) {
                $('#'+prefs.chooser).fadeOut('fast', function() {
                    showPanel(source);
                    prefs.save({chooser: source});
                });
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
        },

        displayCollectionInsert: function(details) {

            debug.log("COLLECTION","Displaying New Insert",details);
            layoutProcessor.sourceControl('albumlist');
            if (prefs.sortcollectionby == "artist" && $('i[name="aartist'+details.artistindex+'"]').isClosed()) {
                debug.log("COLLECTION","Opening Menu","aartist"+details.artistindex);
                doAlbumMenu(null, $('i[name="aartist'+details.artistindex+'"]'), function() {
                    if ($('i[name="aalbum'+details.albumindex+'"]').isClosed()) {
                        debug.log("COLLECTION","Opening Menu","aalbum"+details.albumindex);
                        doAlbumMenu(null, $('i[name="aalbum'+details.albumindex+'"]'), function() {
                            flashTrack(details.trackuri, 'aalbum'+details.albumindex);
                        });
                    } else {
                        flashTrack(details.trackuri, 'aalbum'+details.albumindex);
                    }
                });
            } else if ($('i[name="aalbum'+details.albumindex+'"]').isClosed()) {
                debug.log("COLLECTION","Opening Menu","aalbum"+details.albumindex);
                doAlbumMenu(null, $('i[name="aalbum'+details.albumindex+'"]'), function() {
                    flashTrack(details.trackuri,'aalbum'+details.albumindex);
                });
            } else {
                flashTrack(details.trackuri,'aalbum'+details.albumindex);
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

        postAlbumMenu: function(element) {
            debug.trace("SKIN","Post Album Menu Thing",element.next());
            if (element.next().hasClass('smallcover')) {
                var imgsrc = element.next().children('img').attr('src');
                var aa = new albumart_translator(imgsrc);
                if (element.isClosed()) {
                    if (imgsrc) {
                        element.next().children('img').attr('src', aa.getSize('small'));
                    }
                    element.next().css('width','50%');
                    element.next().css('width','');
                    element.next().children('img').css('width', '');
                } else {
                    if (imgsrc) {
                        element.next().children('img').attr('src', aa.getSize('medium'));
                    }
                    element.next().css('width','50%');
                    element.next().children('img').css('width', '100%');
                }
            }
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

            // $('#upcontents').click(onPlaylistClicked);

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
                adjusticons: ['#sourcescontrols', '#infopanecontrols'],
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
            $('.open_albumart').click(openAlbumArtManager);
            $('#love').click(nowplaying.love);
            $("#ratingimage").click(nowplaying.setRating);
            $('.icon-rss.npicon').click(function(){podcasts.doPodcast('nppodiput')});
            $('#expandleft').click(function(){layoutProcessor.expandInfo('left')});
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

        createPluginHolder: function(icon, title) {
            var d = $('<div>', {class: 'topdrop'}).prependTo('#righthandtop');
            var i = $('<i>', {class: 'tooltip', title: title}).appendTo(d);
            i.addClass(icon);
            i.addClass('smallpluginicon clickicon');
            return d;
        }
        
    }
}();
