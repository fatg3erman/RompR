var prefs = function() {

    var textSaveTimer = null;
    var deferredPrefs = null;

    const prefsInLocalStorage = [
        "sourceshidden",
        "playlisthidden",
        "infosource",
        "playlistcontrolsvisible",
        "sourceswidthpercent",
        "playlistwidthpercent",
        "downloadart",
        "clickmode",
        "chooser",
        "hide_albumlist",
        "hide_filelist",
        "hide_radiolist",
        "hide_playlistslist",
        "hide_audiobooklist",
        "hidebrowser",
        "shownupdatewindow",
        "scrolltocurrent",
        "alarmtime",
        "alarmon",
        "alarm_ramptime",
        "alarm_snoozetime",
        "lastfmlang",
        "user_lang",
        "synctags",
        "synclove",
        "synclovevalue",
        "alarmramp",
        "theme",
        "icontheme",
        "coversize",
        "fontsize",
        "fontfamily",
        "collectioncontrolsvisible",
        "displayresultsas",
        "crossfade_duration",
        "newradiocountry",
        "search_limit_limitsearch",
        "scrobblepercent",
        "lastfm_scrobbling",
        "lastfm_autocorrect",
        "updateeverytime",
        "fullbiobydefault",
        "mopidy_search_domains",
        "skin",
        "outputsvisible",
        "wheelscrollspeed",
        "searchcollectiononly",
        "displayremainingtime",
        "cdplayermode",
        "auto_discovembobulate",
        "ratman_sortby",
        "ratman_showletters",
        "sleeptime",
        "sleepon",
        "advanced_search_open",
        "mopidy_radio_domains",
        "tradsearch",
        "sortwishlistby",
        "player_in_titlebar",
        "communityradiocountry",
    	"communityradiolanguage",
    	"communityradiotag",
    	"communityradiolistby",
        "communityradioorderby",
        "browser_id",
        "playlistswipe",
        "podcastcontrolsvisible",
        "use_albumart_in_playlist",
        "bgimgparms",
        "collectionrange"
    ];

    const cookiePrefs = [
        'skin',
        'currenthost',
        'player_backend',
        "sortbydate",
        "notvabydate",
        "collectionrange"
    ];

	const jsonNode = document.querySelector("script[name='prefs']");
  	const jsonText = jsonNode.textContent;
  	const tags = JSON.parse(jsonText);

    var backgroundImages;
    var backgroundTimer;

    var timeouts = {
        '10 Seconds': 10000,
        '30 Seconds': 30000,
        'Minute': 60000,
        '5 Minutes': 300000,
        '10 Minutes': 600000,
        '20 Minutes': 1200000,
        '30 Minutes': 1800000,
        'Hour': 3600000,
        'Day': 86400000
    }

    function offerToTransferPlaylist() {
        var fnarkle = new popup({
            css: {
                width: 300,
                height: 200
            },
            title: language.gettext('label_transferplaylist'),
            hasclosebutton: false
        });
        var mywin = fnarkle.create();
        var d = $('<div>',{class: 'containerbox'}).appendTo(mywin);
        var yes = $('<button>', {class: 'expand'}).appendTo(d);
        var space = $('<div>', {class: 'fixed', style: 'width:3em'}).appendTo(d);
        var no = $('<button>', {class: 'expand'}).appendTo(d);
        yes.html(language.gettext('label_yes'));
        no.html(language.gettext('label_no'));
        fnarkle.useAsCloseButton(yes, transferPlaylist);
        fnarkle.useAsCloseButton(no, dontTransferPlaylist);
        fnarkle.open();
        fnarkle.setWindowToContentsSize();
    }

    function dontTransferPlaylist() {
        prefs.save(deferredPrefs, reloadWindow);
    }

    function transferPlaylist() {
        debug.shout("PREFS","Transferring Playlist from",prefs.currenthost,"to",deferredPrefs.currenthost);
        $.ajax({
            type: 'POST',
            url: 'player/transferplaylist.php',
            data: JSON.stringify(deferredPrefs),
            success: function() {
                prefs.save(deferredPrefs, reloadWindow);
            },
            error: function() {
                debug.error("PREFS","Playlist transfer failed");
                infobar.error(language.gettext('error_trfailed'));
            }
        });
    }

    function doTheSave() {
        var felakuti = new Object;
        var callback = null;
        $(".saveotron").each( function() {
            if ($(this).hasClass("arraypref")) {
                felakuti[$(this).attr("id")] = $(this).val().split(',');
            } else {
                felakuti[$(this).attr("id")] = $(this).val();
            }
            switch ($(this).attr("id")) {
                case "composergenrename":
                    if (!arraycompare(felakuti.composergenrename, prefs.composergenrename)) {
                        $('[name="donkeykong"]').makeFlasher({flashtime:0.5, repeats: 3});
                    }
                    break;

                case "artistsatstart":
                    if (!arraycompare(felakuti.artistsatstart, prefs.artistsatstart)) {
                        callback = collectionHelper.forceCollectionReload;
                    }
                    break;

                case "nosortprefixes":
                    if (!arraycompare(felakuti.nosortprefixes, prefs.nosortprefixes)) {
                        callback = collectionHelper.forceCollectionReload;
                    }
                    break;

                case "crossfade_duration":
                    if (felakuti.crossfade_duration != player.status.xfade &&
                        player.status.xfade !== undefined &&
                        player.status.xfade !== null &&
                        player.status.xfade > 0) {
                        callback = function() { player.controller.setCrossfade(felakuti.crossfade_duration) }
                    }
                    break;

                case "wheelscrollspeed":
                    if (felakuti.wheelscrollspeed != prefs.wheelscrollspeed) {
                        callback = reloadWindow;
                    }
                    break;
            }
        });
        prefs.save(felakuti, callback);
    }

    function setscrob(e) {
        prefs.save({scrobblepercent: e.max});
    }

    function loadBackgroundImages(theme) {
        $('#cusbgname').empty();
        $('#cusbgcontrols').empty();
        $('#backimageposition').hide();
        $.getJSON('backimage.php?getbackground='+theme+'&browser_id='+prefs.browser_id, function(data) {
            debug.log("PREFS","Custom Background Image",data);
            if (data.images) {
                if (typeof(prefs.bgimgparms[theme]) == 'undefined') {
                    debug.trace("PREFS","Init bgimgparms for",prefs.theme);
                    prefs.bgimgparms[theme] = {
                        landscape: 0,
                        portrait: 0,
                        timeout: 60000,
                        lastchange: Date.now(),
                        random: false,
                        position: 'top left'
                    }
                    prefs.save({bgimgparms: prefs.bgimgparms});
                } else if (typeof(prefs.bgimgparms[theme].position) == 'undefined') {
                    prefs.bgimgparms[theme].position = 'top left';
                    prefs.save({bgimgparms: prefs.bgimgparms});
                }
                setCustombackground(data.images);
                $('input[name="thisbrowseronly"]').prop('checked', data.thisbrowseronly);
                $('input[name="backgroundposition"][value="'+prefs.bgimgparms[theme].position+'"]').prop('checked', true);
                $('input[name="backgroundposition"]').off('click').on('click', changeBackgroundPosition);
            }
        });
    }

    function changeRandomMode() {
        var theme = prefs.theme;
        if (prefs.usertheme) {
            theme = prefs.usertheme;
        }
        if (typeof(prefs.bgimgparms[theme].random) == 'undefined') {
            prefs.bgimgparms[theme].random = false;
        }
        prefs.bgimgparms[theme].random = !prefs.bgimgparms[theme].random;
        prefs.save({bgimgparms: prefs.bgimgparms});
    }

    function changeBackgroundPosition() {
        var theme = prefs.theme;
        if (prefs.usertheme) {
            theme = prefs.usertheme;
        }
        var bgp = prefs.bgimgparms[theme];
        bgp.position = $('input[name="backgroundposition"]:checked').val();
        prefs.save({bgimgparms: prefs.bgimgparms});
        updateCustomBackground();
    }

    function removeAllBackgroundImages() {
        clearCustomBackground();
        var theme = prefs.theme;
        if (prefs.usertheme) {
            theme = prefs.usertheme;
        }
        $.getJSON('backimage.php?clearallbackgrounds='+theme+'&browser_id='+prefs.browser_id, function(data) {
            loadBackgroundImages(theme);
        });
    }

    function setCustombackground(images) {
        clearTimeout(backgroundTimer);
        debug.log("UI","Setting Custom Background To",images);
        backgroundImages = images;
        if (images.landscape.length > 1 || images.portrait.length > 1) {
            var jesus = $('<div>', {class: 'containerbox dropdown-container'}).appendTo('#cusbgcontrols');
            jesus.append('<div class="divlabel">'+language.gettext('label_changevery')+'</div>');
            var sh = $('<div>', {class: 'selectholder'}).appendTo(jesus);
            var s = $('<select>').appendTo(sh);
            $.each(timeouts, function(i, v){
                s.append('<option value="'+v+'">'+i+'</option>');
            });
            s.val(prefs.bgimgparms[prefs.theme].timeout.toString());
            s.on('change', function() {
                prefs.bgimgparms[prefs.theme].timeout = parseInt(s.val());
                prefs.save({bgimgparms: prefs.bgimgparms});
                updateCustomBackground();
            });
            var gibbon = $('<div>').appendTo('#cusbgcontrols');
            var ran = $('<input>', {type: 'checkbox', id: 'bgimagerandom'}).appendTo(gibbon);
            var lab = $('<label>', {for: 'bgimagerandom'}).appendTo(gibbon);
            lab.html('Random Order');
            ran.prop('checked', prefs.bgimgparms[prefs.theme].random);
            ran.on('click', changeRandomMode);

            var orangutan = $('<div>').appendTo('#cusbgcontrols');
            var rb = $('<button>').appendTo(orangutan);
            rb.html('Remove All');
            rb.on('click', removeAllBackgroundImages);
        }
        if (images.landscape.length > 0 || images.portrait.length > 0) {
            $('#backimageposition').show();
        } else {
            $('#backimageposition').hide();
        }
        $.each(images, function(x, p) {
            if (p.length > 0) {
                $('#cusbgname').append('<div class="spacer"></div>');
                var q = $('<div>').appendTo('#cusbgname');
                $('#cusbgname').append('<div class="spacer"></div>');
                q.html('<b>'+p.length+' '+x.capitalize()+' Images</b>');
            }
            $.each(p, function(i, v) {
                var n = $('<div>').appendTo('#cusbgname');
                var c = $('<i>', {class: 'icon-cancel-circled clickicon collectionicon'}).appendTo(n);
                var l = $('<span>', {class: 'bgimgname', name: i}).appendTo(n);
                var z = $('<input>', {class: 'bgimagefile', type: 'hidden', value: v}).appendTo(n);
                var nom = v.replace(/.*(\\|\/)/, '')+'&nbsp;'
                if (x == 'landscape') {
                    l.addClass('landscapeimage');
                    nom += '&#x25AD;';
                } else {
                    l.addClass('portraitimage');
                    nom += '&#x25AF;';
                }
                l.html(nom);
                l.on('click', changeBgImage);
                c.on('click', prefs.clearBgImage);
            });
        });
        updateCustomBackground();
    }

    function changeBgImage(event) {
        var el = $(event.target);
        var theme = prefs.theme;
        if (prefs.usertheme) {
            theme = prefs.usertheme;
        }
        var bgp = prefs.bgimgparms[theme];
        if (el.hasClass('landscapeimage')) {
            bgp.landscape = parseInt(el.attr('name'));
        } else if (el.hasClass('portraitimage')) {
            bgp.portrait = parseInt(el.attr('name'));
        }
        clearCustomBackground();
        setBackgroundCss(bgp);
        bgp.lastchange = Date.now();
        setBackgroundTimer(bgp.timeout);
        prefs.save({bgimgparms: prefs.bgimgparms});
    }

    function clearCustomBackground() {
        $('style[id="phoneback"]').remove();
        $('style[id="background"]').remove();
        $('style[id="phonebackl"]').remove();
        $('style[id="backgroundl"]').remove();
        $('style[id="phonebackp"]').remove();
        $('style[id="backgroundp"]').remove();
        clearTimeout(backgroundTimer);
    }

    function setBackgroundCss(bgp) {
        if (backgroundImages.portrait.length == 0) {
            $('<style id="background">html { background-image: url("'+backgroundImages.landscape[bgp.landscape]+'"); background-position: '+bgp.position+' }</style>').appendTo('head');
            $('<style id="phoneback">body.phone .dropmenu { background-image: url("'+backgroundImages.landscape[bgp.landscape]+'"); background-position: '+bgp.position+' }</style>').appendTo('head');
            $('span.bgimgname').removeClass('selected');
            $('input.bgimagefile[value="'+backgroundImages.landscape[bgp.landscape]+'"]').prev().addClass('selected');
        } else if (backgroundImages.landscape.length == 0) {
            $('<style id="background">html { background-image: url("'+backgroundImages.portrait[bgp.portrait]+'"); background-position: '+bgp.position+' }</style>').appendTo('head');
            $('<style id="phoneback">body.phone .dropmenu { background-image: url("'+backgroundImages.portrait[bgp.portrait]+'"); background-position: '+bgp.position+' }</style>').appendTo('head');
            $('span.bgimgname').removeClass('selected');
            $('input.bgimagefile[value="'+backgroundImages.portrait[bgp.portrait]+'"]').prev().addClass('selected');
        } else {
            $('<style id="backgroundl">@media screen and (orientation: landscape) { html { background-image: url("'+backgroundImages.landscape[bgp.landscape]+'"); background-position: '+bgp.position+' } }</style>').appendTo('head');
            $('<style id="phonebackl">@media screen and (orientation: landscape) { body.phone .dropmenu { background-image: url("'+backgroundImages.landscape[bgp.landscape]+'"); background-position: '+bgp.position+' } }</style>').appendTo('head');
            $('<style id="backgroundp">@media screen and (orientation: portrait) { html { background-image: url("'+backgroundImages.portrait[bgp.portrait]+'"); background-position: '+bgp.position+' } }</style>').appendTo('head');
            $('<style id="phonebackp">@media screen and (orientation: portrait) { body.phone .dropmenu { background-image: url("'+backgroundImages.portrait[bgp.portrait]+'"); background-position: '+bgp.position+' } }</style>').appendTo('head');
            $('span.bgimgname').removeClass('selected');
            $('input.bgimagefile[value="'+backgroundImages.landscape[bgp.landscape]+'"]').prev().addClass('selected');
            $('input.bgimagefile[value="'+backgroundImages.portrait[bgp.portrait]+'"]').prev().addClass('selected');
        }
    }

    function updateCustomBackground() {
        clearTimeout(backgroundTimer);
        clearCustomBackground();
        var theme = prefs.theme;
        if (prefs.usertheme) {
            theme = prefs.usertheme;
        }
        var bgp = prefs.bgimgparms[theme];
        var timeout = bgp.timeout;
        if (bgp.timeout + bgp.lastchange <= Date.now()) {
            bgp.lastchange = Date.now();
            if (bgp.random) {
                bgp.landscape = Math.floor(Math.random() * backgroundImages.landscape.length);
                bgp.portrait = Math.floor(Math.random() * backgroundImages.portrait.length);
            } else {
                bgp.landscape++;
                bgp.portrait++;
            }
            prefs.save({bgimgparms: prefs.bgimgparms});
        } else {
            timeout = bgp.timeout + bgp.lastchange - Date.now();
        }
        if (bgp.landscape >= backgroundImages.landscape.length) { bgp.landscape = 0 }
        if (bgp.portrait >= backgroundImages.portrait.length) { bgp.portrait = 0 }
        setBackgroundCss(bgp);
        setBackgroundTimer(timeout);
    }

    function setBackgroundTimer(timeout) {
        if (backgroundImages.portrait.length > 1 || backgroundImages.landscape.length > 1) {
            debug.debug("PREFS","Setting Slideshow Timeout For",timeout/1000,"seconds");
            backgroundTimer = setTimeout(updateCustomBackground, timeout);
        }
    }

    return {
        loadPrefs: function() {
            for (var p in tags) {
                if (prefsInLocalStorage.indexOf(p) > -1) {
                    if (localStorage.getItem("prefs."+p) != null && localStorage.getItem("prefs."+p) != "") {
                        try {
                            prefs[p] = JSON.parse(localStorage.getItem("prefs."+p));
                        }
                        catch (err) {
                            prefs[p] = tags[p];
                        }
                    } else {
                        prefs[p] = tags[p];
                    }
                } else {
                    prefs[p] = tags[p];
                }
            }

            for (var i in cookiePrefs) {
                var a = getCookie(cookiePrefs[i]);
                if (a != '') {
                    if (a === 'false') { a = false; }
                    if (a === 'true' ) { a = true; }
                    prefs[cookiePrefs[i]] = a;
                    if (prefs.debug_enabled > 7) {
                        console.log("PREFS      : "+cookiePrefs[i]+' = '+prefs[cookiePrefs[i]]);
                    }
                }
            }

            if (prefs.icontheme == 'IconFont') {
                prefs.icontheme = 'Colourful';
            }

        },

        checkSet: function(key) {
            if (prefsInLocalStorage.indexOf(key) > -1) {
                if (localStorage.getItem("prefs."+key) != null && localStorage.getItem("prefs."+key) != "") {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        },

        save: function(options, callback) {
            var prefsToSave = {};
            var postSave = false;
            for (var i in options) {
                prefs[i] = options[i];
                if (cookiePrefs.indexOf(i) > -1) {
                    var val = options[i];
                    setCookie(i, val, 3650);
                }
                if (prefsInLocalStorage.indexOf(i) > -1) {
                    debug.trace("PREFS", "Setting",i,"to",options[i],"in local storage");
                    localStorage.setItem("prefs."+i, JSON.stringify(options[i]));
                } else {
                    debug.trace("PREFS", "Setting",i,"to",options[i],"on backend");
                    prefsToSave[i] = options[i];
                    postSave = true;
                }
            }
            if (postSave) {
                debug.trace("PREFS",JSON.stringify(prefsToSave),prefsToSave);
                $.post('saveprefs.php', {prefs: JSON.stringify(prefsToSave)}, function() {
                    if (callback) {
                        callback();
                    }
                });
            } else if (callback) {
                callback();
            }
        },

        togglePref: function(event) {
            debug.log("PREFS","Toggling",event);
            var prefobj = new Object;
            var prefname = $(this).attr("id");
            if (event === null) {
                // Event will be null if we've called into this through
                // $.proxy - like we have to in a floatingMenu.
                prefobj[prefname] = !$(this).is(":checked");
            } else {
                prefobj[prefname] = $(this).is(":checked");
            }
            var callback = null;
            switch (prefname) {
                case 'downloadart':
                    coverscraper.toggle($("#"+prefname).is(":checked"));
                    break;

                case 'hide_albumlist':
                    callback = function() { hidePanel('albumlist') }
                    break;

                case 'hide_filelist':
                    callback = function() { hidePanel('filelist') }
                    break;

                case 'hide_radiolist':
                    callback = function() { hidePanel('radiolist') }
                    break;

                case 'hide_podcastslist':
                    callback = function() { hidePanel('podcastslist') }
                    break;

                case 'hide_audiobooklist':
                    callback = function() { hidePanel('audiobooklist') }
                    break;

                case 'hide_playlistslist':
                    callback = function() { hidePanel('playlistslist') }
                    break;

                case 'hide_pluginplaylistslist':
                    callback = function() { hidePanel('pluginplaylistslist') }
                    break;

                case 'hidebrowser':
                    callback = layoutProcessor.hideBrowser;
                    break;

                case 'searchcollectiononly':
                case "tradsearch":
                    callback = setAvailableSearchOptions;
                    break;

                case 'sortbycomposer':
                case 'composergenre':
                    $('[name="donkeykong"]').makeFlasher({flashtime: 0.5, repeats: 3});
                    break;

                case 'displaycomposer':
                    debug.log("PREFS","Display Composer Option was changed");
                    callback = player.controller.doTheNowPlayingHack;
                    break

                case "sortbydate":
                case "notvabydate":
                case "showartistbanners":
                    callback = layoutProcessor.changeCollectionSortMode;
                    break;

                case "alarmon":
                    callback = alarm.toggle;
                    break;

                case "sleepon":
                    callback = sleepTimer.toggle;
                    break;

                case 'player_in_titlebar':
                    callback = infobar.forceTitleUpdate;
                    break;

                case "playlistswipe":
                    callback = reloadWindow;
                    break;

                case "use_albumart_in_playlist":
                    callback = playlist.repopulate;
                    break;


            }
            prefs.save(prefobj, callback);
        },

        toggleRadio: function(event) {
            var defer = false;
            var prefobj = new Object;
            var prefname = $(this).attr("name");
            var prefsave = prefname.replace(/_duplicate\d+/, '');
            prefobj[prefsave] = $('[name='+prefname+']:checked').val();
            var callback = null;
            switch(prefsave) {
                case 'clickmode':
                    callback = setPlayClickHandlers;
                    break;

                case 'sortcollectionby':
                case 'collectionrange':
                    callback = layoutProcessor.changeCollectionSortMode;
                    break;

                case 'displayresultsas':
                    callback = function() {
                        player.controller.reSearch();
                    }
                    break;

                case 'currenthost':
                    defer = true;
                    offerToTransferPlaylist();
                    break;

            }
            if (defer) {
                deferredPrefs = cloneObject(prefobj);
            } else {
                prefs.save(prefobj, callback);
            }
        },

        setPrefs: function() {
            $("#langselector").val(interfaceLanguage);

            $("#scrobwrangler").rangechooser({
                range: 100,
                ends: ['max'],
                allowed_min: 0.5,
                onstop: setscrob,
                startmax: prefs.scrobblepercent/100
            });

            $.each($('.autoset'), function() {
                $(this).prop("checked", prefs[$(this).attr("id")]);
            });

            $.each($('.saveotron'), function() {
                if ($(this).hasClass('arraypref')) {
                    var a = prefs[$(this).attr("id")];
                    $(this).val(a.join());
                } else {
                    $(this).val(prefs[$(this).attr("id")]);
                }
            });

            $.each($('.saveomatic'), function() {
                var prefname = $(this).attr("id").replace(/selector/,'');
                $(this).val(prefs[prefname]);
            });

            $.each($('.savulon'), function() {
                var prefname = $(this).attr("name");
                var prefsave = prefname.replace(/_duplicate\d+/, '');
                $("[name="+prefname+"][value="+prefs[prefsave]+"]").prop("checked", true);
            });

        },

        saveSelectBoxes: function(event) {
            var prefobj = new Object();
            var prefname = $(event.target).attr("id").replace(/selector/,'');
            prefobj[prefname] = $(event.target).val();
            var callback = null;

            switch(prefname) {
                case "skin":
                    callback = reloadWindow;
                    break;

                case "theme":
                    prefs.setTheme($("#themeselector").val());
                    setTimeout(layoutProcessor.adjustLayout, 1000);
                    setTimeout(layoutProcessor.themeChange, 1000);
                    break;

                case "icontheme":
                    $("#icontheme-theme").attr("href", "iconsets/"+$("#iconthemeselector").val()+"/theme.css");
                    $("#icontheme-adjustments").attr("href","iconsets/"+$("#iconthemeselector").val()+"/adjustments.css");
                    setTimeout(layoutProcessor.adjustLayout, 1000);
                    break;

                case "fontsize":
                    $("#fontsize").attr({href: "sizes/"+$("#fontsizeselector").val()});
                    setTimeout(setSearchLabelWidth, 1000);
                    setTimeout(setSpotiLabelWidth, 1000);
                    setTimeout(infobar.biggerize, 1000);
                    break;

                case "fontfamily":
                    $("#fontfamily").attr({href: "fonts/"+$("#fontfamilyselector").val()});
                    setTimeout(setSearchLabelWidth, 1000);
                    setTimeout(setSpotiLabelWidth, 1000);
                    setTimeout(infobar.biggerize, 1000);
                    break;

                case "lastfm_country_code":
                    prefobj.country_userset = true;
                    break;

                case "coversize":
                    $("#albumcoversize").attr({href: "coversizes/"+$("#coversizeselector").val()});
                    setTimeout(browser.rePoint, 1000);
                    break;

                case 'podcast_sort_0':
                case 'podcast_sort_1':
                case 'podcast_sort_2':
                case 'podcast_sort_3':
                    callback = podcasts.reloadList;
                    break;

            }
            prefs.save(prefobj, callback);
        },

        changelanguage: function() {
            prefs.save({language: $("#langselector").val()}, function() {
                location.reload(true);
            });
        },

        saveTextBoxes: function() {
            clearTimeout(textSaveTimer);
            textSaveTimer = setTimeout(doTheSave, 1000);
        },

        setTheme: function(theme) {
            if (!theme) theme = prefs.theme;
            clearCustomBackground();
            // Use a different version every time to ensure the browser doesn't cache.
            // Browsers are funny about CSS.
            var t = Date.now();
            $("#theme").attr("href", "themes/"+theme+"?version="+t);
            $("#albumcoversize").attr("href", "coversizes/"+prefs.coversize+"?version="+t);
            $("#fontsize").attr("href", "sizes/"+prefs.fontsize+"?version="+t);
            $("#fontfamily").attr("href", "fonts/"+prefs.fontfamily+"?version="+t);
            $("#icontheme-theme").attr("href", "iconsets/"+prefs.icontheme+"/theme.css"+"?version="+t);
            $("#icontheme-adjustments").attr("href", "iconsets/"+prefs.icontheme+"/adjustments.css"+"?version="+t);
            loadBackgroundImages(theme);
            prefs.rgbs = null;
            if (typeof(layoutProcessor) != 'undefined') {
                setTimeout(prefs.postUIChange, 2000);
            }
        },

        postUIChange: function() {
            $('.rangechooser').rangechooser('fill');
            if (typeof charts !== 'undefined') {
                charts.reloadAll();
            }
            layoutProcessor.adjustLayout();
            setSearchLabelWidth();
            setSpotiLabelWidth();
            infobar.biggerize();
            browser.rePoint();
        },

        changeBackgroundImage: function() {
            $('#bgfileuploadbutton').fadeOut('fast');
            $('#bguploadspinner').addClass('spinner').parent().fadeIn('fast');
            $('[name="currbackground"]').val(prefs.theme);
            $('[name="browser_id"]').val(prefs.browser_id);
            var formElement = document.getElementById('backimageform');
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "backimage.php");
            xhr.responseType = "json";
            xhr.onload = function () {
                switch (xhr.status) {
                    case 200:
                        debug.log("BIMAGE", xhr.response);
                        prefs.setTheme();
                        $('#bguploadspinner').removeClass('spinner').parent().fadeOut('fast');
                        break;

                    case 400:
                        debug.fail("BIMAGE", "FAILED");
                        infobar.error(language.gettext('error_toomanyimages'));
                        // Fall Through

                    default:
                        debug.fail("BIMAGE", "FAILED");
                        infobar.error(language.gettext('error_imageupload'));
                        $('#bguploadspinner').removeClass('spinner').parent().fadeOut('fast');

                }
            };
            xhr.send(new FormData(formElement));
        },

        clearBgImage: function(event) {
            var clicked = $(event.target);
            var image = clicked.next().next().val();
            clicked.parent().remove();
            clearCustomBackground();
            $.getJSON('backimage.php?clearbackground='+image, function(data) {
                $('[name=imagefile').next().html(language.gettext('label_choosefile'));
                $('[name=imagefile').parent().next('input[type="button"]').fadeOut('fast');
                if (prefs.usertheme) {
                    loadBackgroundImages(prefs.usertheme);
                } else {
                    loadBackgroundImages(prefs.theme);
                }
            });
        },

        openBgImageBox: function() {
            $('#custombgdropper').slideToggle('fast');
        },

        clickBindType: function() {
            return prefs.clickmode == 'double' ? 'dblclick' : 'click';
        },

        rgbs: null

	}

}();

prefs.loadPrefs();
// Update old pre-JSON prefs
if (localStorage.getItem("prefs.prefversion") == null) {
    for (var i in window.localStorage) {
        if (i.match(/^prefs\.(.*)/)) {
            var val = localStorage.getItem(i);
            if (val === "true") {
                val = true;
            }
            if (val === "false") {
                val = false;
            }
            localStorage.setItem(i, JSON.stringify(val));
        }
    }
    localStorage.setItem('prefs.prefversion', JSON.stringify(2));
}
prefs.theme = prefs.theme.replace('_1080p','');
