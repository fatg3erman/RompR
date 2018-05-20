var prefs = function() {

    var textSaveTimer = null;

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
        "ratman_smallart",
        "sleeptime",
        "sleepon",
        "advanced_search_open"
    ];
	
	const jsonNode = document.querySelector("script[name='prefs']");
  	const jsonText = jsonNode.textContent;
  	const tags = JSON.parse(jsonText);

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
                        callback = forceCollectionReload;
                    }
                    break;

                case "nosortprefixes":
                    if (!arraycompare(felakuti.nosortprefixes, prefs.nosortprefixes)) {
                        callback = forceCollectionReload;
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
                if (prefsInLocalStorage.indexOf(i) > -1) {
                    debug.log("PREFS", "Setting",i,"to",options[i],"in local storage");
                    localStorage.setItem("prefs."+i, JSON.stringify(options[i]));
                } else {
                    debug.log("PREFS", "Setting",i,"to",options[i],"on backend");
                    prefsToSave[i] = options[i];
                    postSave = true;
                }
            }
            if (postSave) {
                debug.log("PREFS",JSON.stringify(prefsToSave),prefsToSave);
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
            var prefobj = new Object;
            var prefname = $(event.target).attr("id");
            prefobj[prefname] = $("#"+prefname).is(":checked");
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

                case 'hide_playlistslist':
                    callback = function() { hidePanel('playlistslist') }
                    break;

                case 'hide_pluginplaylistslist':
                    callback = function() { hidePanel('pluginplaylistslist') }
                    break;

                case 'hidebrowser':
                    callback = hideBrowser;
                    break;

                case 'search_limit_limitsearch':
                    callback = weaselBurrow;
                    break;

                case 'searchcollectiononly':
                    callback = ferretMaster;
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
                    callback = forceCollectionReload;
                    break;

                case "alarmon":
                    callback = alarm.toggle;
                    break;

                case "sleepon":
                    callback = sleepTimer.toggle;
                    break;

                case "tradsearch":
                    callback = function() { setTimeout(reloadWindow, 500) }
                    break;

            }
            prefs.save(prefobj, callback);
        },

        toggleRadio: function(event) {
            var prefobj = new Object;
            var prefname = $(event.target).attr("name");
            prefobj[prefname] = $('[name='+prefname+']:checked').val();
            var callback = null;
            switch(prefname) {
                case 'clickmode':
                    callback = setClickHandlers;
                    break;

                case 'sortcollectionby':
                    callback = forceCollectionReload;
                    break;

                case 'displayresultsas':
                    callback = function() {
                        player.controller.reSearch();
                    }
                    break;

                case 'currenthost':
                    callback = function() {
                        setCookie('currenthost',prefs.currenthost,3650);
                        reloadWindow();
                    }

            }
            prefs.save(prefobj, callback);
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
                $("[name="+prefname+"][value="+prefs[prefname]+"]").prop("checked", true);
            });

        },

        saveSelectBoxes: function(event) {
            var prefobj = new Object();
            var prefname = $(event.target).attr("id").replace(/selector/,'');
            prefobj[prefname] = $(event.target).val();
            var callback = null;

            switch(prefname) {
                case "skin":
                    setCookie('skin', $("#skinselector").val(),3650);
                    callback = reloadWindow();
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
            $('html').css('background-image', '');
            $('html').css('background-size', '');
            $('html').css('background-repeat', '');
            $("#theme").attr("href", "themes/"+theme+"?version="+rompr_version);
            $.getJSON('backimage.php?getbackground='+theme, function(data) {
                if (data.image) {
                    $('html').css('background-image', 'url("'+data.image+"?version="+rompr_version+'")');
                    $('html').css('background-size', 'cover');
                    $('html').css('background-repeat', 'no-repeat');
                    $('#cusbgname').html(data.image.split(/[\\/]/).pop())
                } else {
                    $('#cusbgname').html('');
                }
            });
            $("#albumcoversize").attr("href", "coversizes/"+prefs.coversize);
            $("#fontsize").attr("href", "sizes/"+prefs.fontsize+"?version="+rompr_version);
            $("#fontfamily").attr("href", "fonts/"+prefs.fontfamily+"?version="+rompr_version);
            $("#icontheme-theme").attr("href", "iconsets/"+prefs.icontheme+"/theme.css"+"?version="+rompr_version);
            $("#icontheme-adjustments").attr("href", "iconsets/"+prefs.icontheme+"/adjustments.css"+"?version="+rompr_version);
            prefs.rgbs = null;
            setTimeout(function() {
                $('.rangechooser').rangechooser('fill');
                if (typeof charts !== 'undefined') {
                    charts.reloadAll();
                }
            }, 2000);
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

var google_api_key = "AIzaSyDAErKEr1g1J3yqHA0x6Ckr5jubNIF2YX4";
var googleSearchURL = "https://www.googleapis.com/customsearch/v1?key="+google_api_key+"&cx=013407992060439718401:d3vpz2xaljs&searchType=image&alt=json";
