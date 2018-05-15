// Mostly general purpose stuff, all in global scope
// Some could be tidied up

function forceCollectionReload() {
    collection_status = 0;
    checkCollection(false, false);
}

function togglePlaylistButtons() {
    layoutProcessor.preHorse();
    $("#playlistbuttons").slideToggle('fast', layoutProcessor.setPlaylistHeight);
    var p = !prefs.playlistcontrolsvisible;
    prefs.save({ playlistcontrolsvisible: p });
    return false;
}

function toggleCollectionButtons() {
    $("#collectionbuttons").slideToggle('fast');
    var p = !prefs.collectioncontrolsvisible;
    prefs.save({ collectioncontrolsvisible: p });
    return false;
}

function lastfmlogin() {
    var user = $("#configpanel").find('input[name|="user"]').val();
    lastfm.login(user);
    $("#configpanel").fadeOut(1000);
}

function outputswitch(id) {
    player.controller.doOutput(id, !$('#outputbutton_'+id).is(':checked'));
}

function toggleAudioOutputs() {
    prefs.save({outputsvisible: !$('#outputbox').is(':visible')});
    $("#outputbox").animate({width: 'toggle'},'fast',function() {
        infobar.biggerize();
    });
}

function changeBackgroundImage() {
    $('[name="currbackground"]').val(prefs.theme);
    var formElement = document.getElementById('backimageform');
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "backimage.php");
    xhr.responseType = "json";
    xhr.onload = function () {
        if (xhr.status === 200) {
            debug.log("BIMAGE", xhr.response);
            if (xhr.response.image) {
                $('html').css('background-image', 'url("'+xhr.response.image+'")');
                $('html').css('background-size', 'cover');
                $('html').css('background-repeat', 'no-repeat');
                $('#cusbgname').html(xhr.response.image.split(/[\\/]/).pop());
            }
        } else {
            debug.fail("BIMAGE", "FAILED");
        }
    };
    xhr.send(new FormData(formElement));

}

var imagePopup = function() {
    var wikipopup = null;
    var imagecontainer = null;
    var mousepos = null;
    var clickedelement = null;
    var image = new Image();
    image.onload = function() {
        debug.log("IMAGEPOPUP", "Image has loaded");
        imagePopup.show();
    }
    image.onerror = function() {
        debug.log("IMAGEPOPUP", "Image has NOT loaded");
        imagePopup.close();
    }

    return {
        create:function(element, event, source){
            debug.log("IMAGEPOPUP", "Creating new popup",source);
            debug.log("IMAGEPOPUP", "Current popup source is",image.src);
            if(wikipopup == null){
                wikipopup = $('<div>', { id: 'wikipopup', onclick: 'imagePopup.close()',
                    class: 'dropshadow'}).appendTo($('body'));
                imagecontainer = $('<img>', { id: 'imagecontainer', onclick: 'imagePopup.close()',
                    src: ''}).appendTo($('body'));
            } else {
                wikipopup.empty();
                imagecontainer.fadeOut('fast');
            }
            mousepos = getPosition(event);
            clickedelement = element;
            var top = (mousepos.y - 24);
            var left = (mousepos.x - 24);
            if (left < 0) {
                left = 0;
            }
            wikipopup.css({       width: '48px',
                                  height: '48px',
                                  top: top+'px',
                                  left: left+'px'});
            wikipopup.append($('<i>', {class: 'icon-spin6 svg-square spinner',
                style: 'position:relative;top:8px;left:8px'}));
            wikipopup.fadeIn('fast');
            if (source !== undefined) {
                if (source == image.src) {
                    imagePopup.show();
                } else {
                    image.src = "";
                    image.src = source;
                }
            }
        },

        show:function() {
            // Calculate popup size and position
            var imgwidth = image.width;
            var imgheight = image.height;
            debug.log("POPUP","Image size is",imgwidth,imgheight);
            // Make sure it's not bigger than the window
            var winsize=getWindowSize();
            // hack to allow for vertical scrollbar
            winsize.x = winsize.x - 32;
            // Allow for popup border
            var w = winsize.x - 63;
            var h = winsize.y - 36;
            debug.log("POPUP","Allowed size is",w,h);
            var scale = w/image.width;
            if (h/image.height < scale) {
                scale = h/image.height;
            }
            if (scale < 1) {
                imgheight = Math.round(imgheight * scale);
                imgwidth = Math.round(imgwidth * scale);
            }
            debug.log("POPUP","Calculated Image size is",imgwidth,imgheight,(imgwidth/image.width),
                (imgheight/image.height));
            var popupwidth = imgwidth+36;
            var popupheight = imgheight+36;

            var top = (mousepos.y - (popupheight/2));
            var left = (mousepos.x - (popupwidth/2));
            if ((left+popupwidth) > winsize.x) {
                left = winsize.x - popupwidth;
            }
            if ((top+popupheight) > winsize.y) {
                top = winsize.y - popupheight;
            }
            if (top < 0) {
                top = 0;
            }
            if (left < 0) {
                left = 0;
            }
            wikipopup.empty();
            wikipopup.animate(
                {
                    width: popupwidth+'px',
                    height: popupheight+'px',
                    top: top+'px',
                    left: left+'px'
                },
                'fast',
                'swing',
                function() {
                    imagecontainer.css({  top: (top+18)+'px',
                                          left: (left+18)+'px'});

                    imagecontainer.attr({ width: imgwidth+'px',
                                          height: imgheight+'px',
                                          src: image.src });

                    imagecontainer.fadeIn('slow');
                    wikipopup.append($('<i>', {class: 'icon-cancel-circled playlisticon tright clickicon',
                        style: 'margin-top:4px;margin-right:4px'}));
                }
            );
        },

        close:function() {
            wikipopup.fadeOut('slow');
            imagecontainer.fadeOut('slow');
        }
    }
}();

function setPlaylistButtons() {
    c = (player.status.xfade === undefined || player.status.xfade === null || player.status.xfade == 0) ? "off" : "on";
    $("#crossfade").switchToggle(c);
    $.each(['random', 'repeat', 'consume'], function(i,v) {
        $("#"+v).switchToggle(player.status[v]);
    });
    if (player.status.replay_gain_mode) {
        $.each(["off","track","album","auto"], function(i,v) {
            if (player.status.replay_gain_mode == v) {
                $("#replaygain_"+v).switchToggle("on");
            } else {
                $("#replaygain_"+v).switchToggle("off");
            }
        });
    }
    if (player.status.xfade !== undefined && player.status.xfade !== null &&
        player.status.xfade > 0 && player.status.xfade != prefs.crossfade_duration) {
        prefs.save({crossfade_duration: player.status.xfade});
        $("#crossfade_duration").val(player.status.xfade);
    }
}

function prepareForLiftOff(text) {
    infobar.notify(infobar.PERMNOTIFY,text);
    $("#collection").empty();
    doSomethingUseful('collection', text);
    var x = $('<div>',{ id: 'updatemonitor', class: 'tiny', style: 'padding-left:1em;margin-right:1em'}).insertAfter($('#spinner_collection'));
}

function prepareForLiftOff2(text) {
    $("#filecollection").empty();
    doSomethingUseful("filecollection", text);
}

/* This is called when the page loads. It checks to see if the albums/files cache exists
    and builds them, if necessary. If they are there, it loads them
*/

function checkCollection(forceup, rescan) {
    if (forceup && player.updatingcollection) {
        infobar.notify(infobar.ERROR, "Already Updating Collection!");
        return;
    }
    var update = forceup;
    if (prefs.updateeverytime) {
        debug.mark("GENERAL","Updating Collection due to preference");
        update = true;
    } else {
        if (!prefs.hide_albumlist && collection_status == 1) {
            debug.mark("GENERAL","Updating Collection because it is out of date");
            collection_status = 0;
            update = true;
        }
    }
    if (update) {
        player.updatingcollection = true;
        $("#searchresultholder").html('');
        player.controller.scanFiles(rescan ? 'rescan' : 'update');
    } else {
        if (prefs.hide_filelist && !prefs.hide_albumlist) {
            loadCollection('albums.php?item='+collectionKey('a'), null);
        } else if (prefs.hide_albumlist && !prefs.hide_filelist) {
            loadCollection(null, 'dirbrowser.php');
        } else if (!prefs.hide_albumlist && !prefs.hide_filelist) {
            loadCollection('albums.php?item='+collectionKey('a'), 'dirbrowser.php');
        }
    }
}

function collectionKey(w) {
    return w+prefs.sortcollectionby+'root';
}

function loadCollection(albums, files) {
    if (albums != null) {
        debug.log("GENERAL","Loading Collection from URL",albums);
        player.controller.loadCollection(albums);
    }
    if (files != null) {
        debug.log("GENERAL","Loading File Browser from URL",files);
        player.controller.reloadFilesList(files);
    }
}

function checkPoll(data) {
    if (data.updating_db) {
        update_load_timer = setTimeout( pollAlbumList, 1000);
        update_load_timer_running = true;
    } else {
        if (prefs.hide_filelist && !prefs.hide_albumlist) {
            loadCollection('albums.php?rebuild=yes&dump='+collectionKey('a'), null);
        } else if (prefs.hidealbumlist && !prefs.hide_filelist) {
            loadCollection(null, 'dirbrowser.php');
        } else if (!prefs.hidealbumlist && !prefs.hide_filelist) {
            loadCollection('albums.php?rebuild=yes&dump='+collectionKey('a'), 'dirbrowser.php');
        }
    }
}

function pollAlbumList() {
    if(update_load_timer_running) {
        clearTimeout(update_load_timer);
        update_load_timer_running = false;
    }
    $.getJSON("player/mpd/postcommand.php", checkPoll);
}

function scootTheAlbums(jq) {
    if (prefs.downloadart) {
        $.each(jq.find("img.notexist"), function() {
            coverscraper.GetNewAlbumArt({imgkey: $(this).attr('name')});
        });
    }
}

function hidePanel(panel) {
    var is_hidden = $("#"+panel).is(':hidden');
    var new_state = prefs["hide_"+panel];
    debug.log("GENERAL","Hide Panel",panel,is_hidden,new_state);
    layoutProcessor.hidePanel(panel, is_hidden, new_state);
    if (new_state) {
        switch (panel) {
            case "albumlist":
                if (update_load_timer_running == false) {
                    $("#collection").empty();
                    $("#collection").prev().hide();
                    $("#collection").prev().prev().hide();
                }
                break;
            case "filelist":
                if (update_load_timer_running == false) {
                    $("#filecollection").empty();
                }
                break;
        }
    } else {
        switch (panel) {
            case "albumlist":
                if (update_load_timer_running == false) {
                    loadCollection('albums.php?item='+collectionKey('a'), null);
                }
                $("#collection").prev().show();
                $("#collection").prev().prev().show();
                break;
            case "filelist":
                if (update_load_timer_running == false) {
                    loadCollection(null, 'dirbrowser.php?item=adirroot');
                }
                break;
        }
    }
    setChooserButtons();
}

function doSomethingUseful(div,text) {
    var html = '<div class="containerbox bar">';
    if (typeof div == "string") {
        html = '<div class="containerbox bar" id="spinner_'+div+'">';
    }
    html += '<div class="fixed alignmid padleft">'+
        '<i class="icon-spin6 svg-square spinner"></i></div>';
    html += '<h3 class="expand ucfirst label">'+text+'</h3>';
    html += '</div>';
    if (typeof div == "object") {
        div.append(html);
    } else if (typeof div == "string") {
        $("#"+div).append(html);
    }
}

function setChooserButtons() {
    var s = ["albumlist", "filelist", "radiolist", "podcastslist", "playlistslist", "pluginplaylistslist"];
    for (var i in s) {
        if (prefs["hide_"+s[i]]) {
            $(".choose_"+s[i]).fadeOut('fast');
        } else {
            $(".choose_"+s[i]).fadeIn('fast');
        }
    }
}

function getrgbs(percent,min) {

    var colours = {r: 155, g: 75, b: 0};
    if (prefs.rgbs == null) {
        // Save looking these up every time, it's quite slow
        var c = $('#pset').css('background-color');
        var regexp = /rgba\((\d+),\s*(\d+),\s*(\d+),\s*(\d+)\)/;
        var match = regexp.exec(c);
        // If no style is set it comes back as 0,0,0 so we must catch that
        // if you want black progress bars use 1,1,1
        if (match[1] && match[2] && match[3] && (match[1]+match[2]+match[3] > 0)) {
            colours = {r: match[1], g: match[2], b: match[3]};
            prefs.rgbs = colours;
        }
    } else {
        colours = prefs.rgbs;
    }

    if (typeof percent != "number") {
        percent = parseFloat(percent);
    }
    
    if (typeof min != "number") {
        min = parseFloat(min);
    }

    percent = Math.min(percent, 100);
    min = Math.max(min, 0);
    var highr = Math.min(255, Math.round(colours.r + percent));
    var highg = Math.min(255, Math.round(colours.g + percent));
    var highb = Math.min(255, Math.round(colours.b + percent));
    var lowr = Math.round(colours.r + min);
    var lowg = Math.round(colours.g + min);
    var lowb = Math.round(colours.b + min);
    var lowalpha = 0.8;
    var highalpha = 1;
    if (min == 0) {
        return "rgba("+colours.r+","+colours.g+","+colours.b+","+lowalpha+") 0%,rgba("+highr+","+highg+","+highb+","+highalpha+") "+percent+
            "%,rgba(0,0,0,0.1) "+percent+"%,rgba(0,0,0,0.1) 100%)";
    } else {
        return "rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.1) "+min+"%, rgba("+lowr+","+lowg+","+lowb+","+lowalpha+") "+min+"%,"+
                "rgba("+highr+","+highg+","+highb+","+highalpha+") "+percent+
                "%,rgba(0,0,0,0.1) "+percent+"%,rgba(0,0,0,0.1) 100%)";
    }

}

function populateTagMenu(callback) {
    metaHandlers.genericAction(
        'gettags',
        callback,
        function() {
            debug.error("DB TRACKS", "Failed to get tags");
        }
    );
}

function populateSpotiTagMenu(callback) {
    spotify.recommendations.getGenreSeeds(
        function(data) {
            debug.log("SPOTIFY","Got Genre Seeds",data);
            callback(data.genres);
        },
        function(data) {
            debug.error("SPOTIFY","Got error requesting genre seeds",data);
        }
    );
}

var tagAdder = function() {

    var index = null;
    var lastelement = null;
    var callback = null;

    return {
        show: function(evt, idx, cb) {
            callback = cb;
            if (evt.target == lastelement) {
                tagAdder.close();
            }  else {
                index = idx;
                var position = getPosition(evt);
                layoutProcessor.setTagAdderPosition(position);
                $("#tagadder").slideDown('fast');
                lastelement = evt.target;
            }
        },

        close: function() {
            $("#tagadder").slideUp('fast');
            lastelement = null;
            callback = null;
        },

        add: function(toadd) {
            debug.log("TAGADDER","New Tags :",toadd);
            if (index !== null) {
                nowplaying.addTags(index, toadd);
            } else if (callback !== null) {
                callback($(lastelement), toadd);
            }
            tagAdder.close();
        }
    }
}();

var pluginManager = function() {

    // Plugins (from the plugins directory) shoud call pluginManager.addPlugin at load time.
    // They must supply a label and either an action function, a setup function, or both.
    // The setup function will be called as soon as all the page scripts have loaded,
    // before the layout is initialised. If a plugin wishes to add icons to the layout,
    // or hotkeys, it should do it here.
    // Alternatively, a script name can be provided. This script will be dynamically loaded
    // the first time the plugin is clicked on. The script MUST call setAction to set the
    // action function (for the next time it's clicked on), and call its own action function.
    // If an action function is provided the plugin's label will be added to the dropdown list
    // above the info panel and the action function will be called when the label is clicked.

    var plugins = new Array();

    function openPlugin(index) {
        if (typeof plugins[index].action == 'function') {
            plugins[index].action();
        } else {
            debug.log("PLUGINS","Loading script",plugins[index].script,"for",plugins[index].label);
            $.getScript(plugins[index].script).fail(function(data, settings, exception) {
                debug.error("PLUGINS","Failed Loading Script",exception);
            });
        }
    }

    return {
        addPlugin: function(label, action, setup, script, icon) {
            debug.log("PLUGINS","Adding Plugin",label,icon);
            plugins.push({label: label, action: action, setup: setup, script: script, icon: icon});
        },

        doEarlyInit: function() {
            for (var i in plugins) {
                if (plugins[i].setup) {
                    if (!only_plugins_with_icons || plugins[i].icon) {
                        debug.log("PLUGINS","Setting up Plugin",plugins[i].label);
                        plugins[i].setup();
                    }
                }
            }
        },

        setupPlugins: function() {
            for (var i in plugins) {
                if (plugins[i].action || plugins[i].script) {
                    debug.log("PLUGINS","Setting up Plugin",plugins[i].label);
                    if (only_plugins_with_icons) {
                        if (plugins[i].icon !== null) {
                            $("#specialplugins .spicons").append('<i class="noshrink clickable clickicon topimg '+plugins[i].icon+'" name="'+i+'"></i>');
                            $("#specialplugins .sptext").append('<div class="backhi clickable clickicon noselection menuitem" name="'+i+'">'+plugins[i].label+'</div>');
                        }
                    } else {
                        $("#specialplugins").append('<div class="backhi clickable clickicon noselection menuitem" name="'+i+'">'+plugins[i].label+'</div>');
                    }
                }
            }
            $("#specialplugins").find(".clickicon").click(function() {
                var index = parseInt($(this).attr('name'));
                openPlugin(index);
            });
        },

        setAction: function(label, action) {
            for (var i in plugins) {
                if (plugins[i].label == label) {
                    debug.log("PLUGINS","Setting Action for",label);
                    plugins[i].action = action;
                }
            }
        },

        autoOpen: function(label) {
            for (var i in plugins) {
                if (plugins[i].label == label) {
                    openPlugin(i);
                    return true;
                }
            }
        }
    }
}();

function setSearchLabelWidth() {
    debug.log("UI","Setting Search Label Widths");
    var w = 0;
    $.each($(".slt"), function() {
        if ($(this).width() > w) {
            w = $(this).width();
        }
    });
    w += 8;
    $(".searchlabel").css("width", w+"px");
    if (prefs.search_limit_limitsearch) {
        $("#mopidysearchdomains").show();
    } else {
        $("#mopidysearchdomains").hide();
    }
    if (prefs.advanced_search_open) {
        $("#advsearchoptions").show();
        $('[name="advsearchoptions"]').toggleOpen()();
    } else {
        $("#advsearchoptions").hide();
        $('[name="advsearchoptions"]').toggleClosed()();
    }
}

function setSpotiLabelWidth() {
    debug.log("UI","Setting Spotify Label Widths");
    var w = 0;
    $.each($(".bacon"), function() {
        if ($(this).width() > w) {
            w = $(this).width();
        }
    });
    w += 8;
    $(".spl").css("width", w+"px");
}

function displayRating(where, what) {
    $(where).removeClass("icon-0-stars icon-1-stars icon-2-stars icon-3-stars icon-4-stars icon-5-stars");
    if (what !== false) {
        $(where).addClass('icon-'+what+'-stars');
    }
}

function showUpdateWindow() {
    if (mopidy_is_old) {
        alert(language.gettext("mopidy_tooold", [mopidy_min_version]));
    } else {
        if (prefs.shownupdatewindow === true || prefs.shownupdatewindow < rompr_version) {
            var fnarkle = new popup({
                width: 800,
                height: 1100,
                title: language.gettext("intro_title"),
                hasclosebutton: false});
            var mywin = fnarkle.create();
            mywin.append('<div id="begging"></div>');
            mywin.append('<div id="license"></div>');
            mywin.append('<div id="about"></div>');
            $('#begging').load('includes/begging.html', function() {
                $('#license').load('includes/license.html', function(){
                    $('#about').load('includes/about.html', function() {
                        fnarkle.addCloseButton('OK');
                        prefs.save({shownupdatewindow: rompr_version});
                        fnarkle.open();
                    });
                });
            });
        }
    }

}

function removeOpenItems(index) {
    // Filter out artist and album items whose dropdowns have been populated -
    // In these cases the individual tracks will exist and will be selected
    // (and might only have partial selections even if the header is selected)
    if ($(this).hasClass('clicktrack') ||
        $(this).hasClass('clickcue') ||
        $(this).hasClass('clickstream')) {
        return true;
    }
    if (!$(this).hasClass('clickalbumname') && $("#"+$(this).attr('name')).length == 0) {
        return true;
    } else if ($("#"+$(this).attr('name')).hasClass('notfilled')) {
        return true;
    } else {
        return false;
    }
}

function calcPercentWidth(element, childSelector, targetWidth, parentWidth) {
    if (parentWidth < targetWidth) { return 100; }
    var t = element.find(childSelector);
    var r = element.find('.tagholder_wide');
    var numElements = t.length + r.length;
    if (numElements == 0) { return 100; }
    var first_row = Math.round(parentWidth/targetWidth);
    var pixelwidth = parentWidth/first_row;
    if (parentWidth/numElements > pixelwidth) {
        pixelwidth = targetWidth;
    }
    pixelwidth -= masonry_gutter;
    return (pixelwidth/parentWidth)*100;
}

function makeHoverWork(ev) {
    ev.preventDefault();
    ev.stopPropagation();
    var jq = $(ev.target);
    var position = getPosition(ev);
    var elemright = jq.width() + jq.offset().left;
    if (position.x > elemright - 14) {
        jq.css('cursor','pointer');
    } else {
        jq.css('cursor','auto');
    }
}

function checkSearchDomains() {
    $("#mopidysearchdomains").makeDomainChooser({
        default_domains: prefs.mopidy_search_domains,
    });
    $("#mopidysearchdomains").find('input.topcheck').each(function() {
        $(this).click(function() {
            prefs.save({mopidy_search_domains: $("#mopidysearchdomains").makeDomainChooser("getSelection")});
        });
    });
}

function doMopidyCollectionOptions() {

    // Mopidy Folders to browse when building the collection

    // spotifyweb folders are SLOW, but that's to be expected.
    // We use 'Albums' to get 'Your Music' because, although it requires more requests than 'Songs',
    // each response will be small enough to handle easily and there's less danger of timeouts or
    // running into memory issues or pagination.

    var domains = {
        local: [{dir: "Local media", label: "Local Media"}],
        beetslocal: [{dir: "Local (beets)", label: "Local (beets)"}],
        spotify: [{dir: "Spotify Playlists", label: "Spotify Playlists"}],
        spotifyweb: [{dir: "Spotify Web Browse/Your Music/Albums", label: "Spotify 'Your Music'"},
                     {dir: "Spotify Web Browse/Your Artists", label: "Your Spotify Artists (Slow!)"}],
        gmusic: [{dir: "Google Music", label: "Google Music"}],
        soundcloud: [{dir: "SoundCloud/Liked", label: "SoundCloud Liked"}],
        vkontakte: [{dir: "VKontakte", label: "VKontakte" }]
    }

    for (var i in domains) {
        if (player.canPlay(i)) {
            for (var j in domains[i]) {
                var fum =
                    '<div class="styledinputs indent">'+
                    '<input class="mopocol" type="checkbox" id="mopcol_'+i+j+'"';
                    if (prefs.mopidy_collection_folders.indexOf(domains[i][j].dir) > -1) {
                        fum += ' checked';
                    }
                    fum += '>'+
                    '<label for="mopcol_'+i+j+'">'+domains[i][j].label+'</label>'+
                    '<input type="hidden" name="'+domains[i][j].dir+'" />'+
                    '</div>';
                $("#mopidycollectionoptions").append(fum);
            }
        }
    }
    $('.mopocol').click(function() {
        var opts = new Array();
        $('.mopocol:checked').each(function() {
            opts.push($(this).next().next().attr('name'));
        });
        debug.log("MOPIDY","Collection Options Are",opts);
        prefs.save({mopidy_collection_folders: opts});
    });
}

function editPlayerDefs() {
    $("#configpanel").slideToggle('fast');
    var playerpu = new popup({
        width: 600,
        height: 600,
        title: "Players"});
    var mywin = playerpu.create();
    mywin.append('<div class="pref textcentre"><p>You can define as many players as '+
        'you like and switch between them or use them all simultaneously from different browsers. '+
        'All the players will share the same Collection database.</p>'+
        '<p><b>Do NOT access multiple players from the same browser simultaneously.</b></p></div>');

    mywin.append('<table align="center" cellpadding="2" id="playertable" width="96%"></table>');
    $("#playertable").append('<tr><th>NAME</th><th>HOST</th><th>PORT</th><th>PASSWORD</th><th>UNIX SOCKET</th></tr>');
    for (var i in prefs.multihosts) {
        $("#playertable").append('<tr class="hostdef" name="'+escape(i)+'">'+
            '<td><input type="text" size="30" name="name" value="'+i+'"/></td>'+
            '<td><input type="text" size="30" name="host" value="'+prefs.multihosts[i]['host']+'"/></td>'+
            '<td><input type="text" size="30" name="port" value="'+prefs.multihosts[i]['port']+'"/></td>'+
            '<td><input type="text" size="30" name="password" value="'+prefs.multihosts[i]['password']+'"/></td>'+
            '<td><input type="text" size="30" name="socket" value="'+prefs.multihosts[i]['socket']+'"/></td>'+
            '<td><i class="icon-cancel-circled smallicon clickicon clickremhost"></i></td>'+
            '</tr>'
        );
    }
    var buttons = $('<div>',{class: "pref"}).appendTo(mywin);
    var add = $('<i>',{class: "icon-plus smallicon clickicon tleft"}).appendTo(buttons);
    add.click(function() {
        addNewPlayerRow();
        playerpu.setContentsSize();
    });
    var c = $('<button>',{class: "tright"}).appendTo(buttons);
    c.html(language.gettext('button_cancel'));
    playerpu.useAsCloseButton(c, false);

    var d = $('<button>',{class: "tright"}).appendTo(buttons);
    d.html(language.gettext('button_OK'));
    playerpu.useAsCloseButton(d, updatePlayerChoices);

    $('.clickremhost').unbind('click');
    $('.clickremhost').click(removePlayerDef);

    playerpu.open();
}

function removePlayerDef(event) {
    if (decodeURIComponent($(event.target).parent().parent().attr('name')) == prefs.currenthost) {
        infobar.notify(infobar.ERROR, "You cannot delete the player you're currently using");
    } else {
        $(event.target).parent().parent().remove();
    }
}

function updatePlayerChoices() {
    var newhosts = new Object();
    var reloadNeeded = false;
    var error = false;
    $("#playertable").find('tr.hostdef').each(function() {
        var currentname = decodeURIComponent($(this).attr('name'));
        var newname = "";
        var temp = new Object();
        $(this).find('input').each(function() {
            if ($(this).attr('name') == 'name') {
                newname = $(this).val();
            } else {
                temp[$(this).attr('name')] = $(this).val();
            }
        });

        newhosts[newname] = temp;
        if (currentname == prefs.currenthost) {
            if (newname != currentname) {
                debug.log("Current Player renamed to "+newname,"PLAYERS");
                reloadNeeded = newname;
            }
            if (temp.host != prefs.mpd_host || temp.port != prefs.mpd_port
                || temp.socket != prefs.unix_socket || temp.password != prefs.mpd_password) {
                debug.log("Current Player connection details changed","PLAYERS");
                reloadNeeded = newname;
            }
        }
    });
    debug.log("PLAYERS",newhosts);
    if (reloadNeeded !== false) {
        prefs.save({currenthost: reloadNeeded}, function() {
            prefs.save({multihosts: newhosts}, function() {
                setCookie('currenthost',reloadNeeded,3650);
                reloadWindow();
            });
        });
    } else {
        prefs.save({multihosts: newhosts});
        replacePlayerOptions();
        setPrefs();
        $("#playerdefs > .savulon").click(prefs.toggleRadio);
    }
}

function replacePlayerOptions() {
    $("#playerdefs").empty();
    for (var i in prefs.multihosts) {
        $("#playerdefs").append('<input type="radio" class="topcheck savulon" name="currenthost" value="'+
            i+'" id="host_'+escape(i)+'">'+
            '<label for="host_'+escape(i)+'">'+i+'</label><br/>');
    }
}

function addNewPlayerRow() {
    $("#playertable").append('<tr class="hostdef" name="New">'+
        '<td><input type="text" size="30" name="name" value="New"/></td>'+
        '<td><input type="text" size="30" name="host" value=""/></td>'+
        '<td><input type="text" size="30" name="port" value=""/></td>'+
        '<td><input type="text" size="30" name="password" value=""/></td>'+
        '<td><input type="text" size="30" name="socket" value=""/></td>'+
        '<td><i class="icon-cancel-circled smallicon clickicon clickremhost"></i></td>'+
        '</tr>'
    );
    $('.clickremhost').unbind('click');
    $('.clickremhost').click(removePlayerDef);
}

function dropProcessor(evt, imgobj, imagekey, stream, success, fail) {

    evt.stopPropagation();
    evt.preventDefault();
    if (evt.dataTransfer.types) {
        for (var i in evt.dataTransfer.types) {
            type = evt.dataTransfer.types[i];
            debug.log("ALBUMART","Checking...",type);
            var data = evt.dataTransfer.getData(type);
            switch (type) {

                case "text/html":       // Image dragged from another browser window (Chrome and Firefox)
                    var srces = data.match(/src\s*=\s*"(.*?)"/);
                    if (srces && srces[1]) {
                        src = srces[1];
                        debug.log("ALBUMART","Image Source",src);
                        imgobj.removeClass('nospin notexist notfound').addClass('spinner notexist');
                        if (src.match(/image\/.*;base64/)) {
                            debug.log("ALBUMART","Looks like Base64");
                            // For some reason I no longer care about, doing this with jQuery.post doesn't work
                            var formData = new FormData();
                            formData.append('base64data', src);
                            formData.append('imgkey', imagekey);
                            if (stream !== null) {
                                formData.append('stream', stream);
                            }
                            var xhr = new XMLHttpRequest();
                            xhr.open('POST', 'getalbumcover.php');
                            xhr.responseType = "json";
                            xhr.onload = function () {
                                if (xhr.status === 200) {
                                    success(xhr.response);
                                } else {
                                    fail();
                                }
                            };
                            xhr.send(formData);
                        } else {
                            var data = { imgkey: imagekey,
                                        src: src
                                };
                            if (stream !== null) {
                                data.stream = stream;
                            }
                            $.ajax({
                                url: "getalbumcover.php",
                                type: "POST",
                                data: data,
                                cache:false,
                                success: success,
                                error: fail,
                            });
                        }
                        return false;
                    }
                    break;

                case "Files":       // Local file upload
                    debug.log("ALBUMART","Found Files");
                    var files = evt.dataTransfer.files;
                    if (files[0]) {
                        imgobj.removeClass('nospin notexist notfound').addClass('spinner notexist');
                        // For some reason I no longer care about, doing this with jQuery.post doesn't work
                        var formData = new FormData();
                        formData.append('ufile', files[0]);
                        formData.append('imgkey', imagekey);
                        if (stream !== null) {
                            formData.append('stream', stream);
                        }
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', 'getalbumcover.php');
                        xhr.responseType = "json";
                        xhr.onload = function () {
                            if (xhr.status === 200) {
                                success(xhr.response);
                            } else {
                                fail();
                            }
                        };
                        xhr.send(formData);
                        return false;
                    }
                    break;
            }

        }
    }
    // IF we get here, we didn't find anything. Let's try the basic text,
    // which might give us something if we're lucky.
    // Safari returns a plethora of MIME types, but none seem to be useful.
    var data = evt.dataTransfer.getData('Text');
    var src = data;
    debug.log("ALBUMART","Trying last resort methods",src);
    if (src.match(/^http:\/\//)) {
        debug.log("ALBUMART","Appears to be a URL");
        var u = src.match(/images.google.com.*imgurl=(.*?)&/)
        if (u && u[1]) {
            src = u[1];
            debug.log("ALBUMART","Found possible Google Image Result",src);
        }
        var data = { imgkey: imagekey,
                    src: src
            };
        if (stream !== null) {
            data.stream = stream;
        }
        $.ajax({
            url: "getalbumcover.php",
            type: "POST",
            data: data,
            cache:false,
            success: success,
            error: fail,
        });
    }
    return false;
}

function findPosition(key) {
    // The key is the id of a dropdown div.  But that div won't exist if the dropdown hasn't been
    // opened. So we see if it does, and if it doesn't then we use the name attribute of the
    // toggle arrow button to locate the position.
    if ($("#"+key).length > 0) {
        return $("#"+key);
    } else {
        return $('i[name="'+key+'"]').parent()
    }
}
 
function updateCollectionDisplay(rdata) {
    // rdata contains HTML fragments to insert into the collection
    // Otherwise we would have to reload the entire collection panel every time,
    // which would cause any opened dropdowns to be mysteriously closed,
    // which would just look shit.
    debug.trace("RATING PLUGIN","Update Display",rdata);

    if (!rdata) {
        return;
    }

    if (rdata.hasOwnProperty('deletedalbums')) {
        $.each(rdata.deletedalbums, function(i, v) {
            debug.log("REMOVING", "Album", v);
            $("#aalbum"+v).remove();
            // w - for wishlist. Each wishlist track is in a separate album.
            //     We check each album flagged as modified to see if it has any visible tracks and
            //     add it to deletedalbums if it doesn't.
            //     - wishlist tracks are invisible anyway but the only modification that can happen
            //       to a wishlist album is that its track has been deleted
            $("#walbum"+v).remove();
            findPosition('aalbum'+v).remove();
        });
    }

    if (rdata.hasOwnProperty('deletedartists')) {
        $.each(rdata.deletedartists, function(i, v) {
            debug.log("REMOVING", "Artist", v);
            $("#aartist"+v).remove();
            findPosition('aartist'+v).remove();
        });
    }

    if (rdata.hasOwnProperty('modifiedalbums')) {
        $('#emptycollection').remove();
        $.each(rdata.modifiedalbums, function(i,v) {
            // We remove and replace any modified albums, as they may have a new date or albumartist which would cause
            // them to appear elsewhere in the collection. First remove the dropdown if it exists and replace its contents
            var albumindex = v.id;
            debug.log("MODIFIED","Album",albumindex);
            $('#aalbum'+albumindex).html(v.tracklist);
            var dropdown = $('#aalbum'+albumindex).is(':visible');
            var dc = $('#aalbum'+albumindex).remove()[0];
            findPosition('aalbum'+albumindex).remove();
            insert_new_thing(v);
            if (dropdown) {
                debug.log("UPDATING","Album",albumindex);
                $(dc).insertAfter(findPosition('aalbum'+albumindex));
                $('i[name="aalbum'+albumindex+'"]').toggleOpen();
            }
        });
    }

    if (rdata.hasOwnProperty('modifiedartists')) {
        $('#emptycollection').remove();
        $.each(rdata.modifiedartists, function(i,v) {
            // The only thing to do with artists is to add them in if they don't exist
            // NOTE. Do this AFTER inserting new albums, because if we're doing albumbyartist with banners showing
            // then the insertAfter logic will be wrong if we've already inserted the artist banner. We also need
            // to remove and replace the banner when that sort option is used, because we only insertAfter an album ID
            if (prefs.sortcollectionby == 'albumbyartist') {
                $("#aartist"+v.id).remove();
            }
            var x = findPosition('aartist'+v.id);
            if (x.length == 0) {
                insert_new_thing(v);
            }
        });
    }

    if (rdata.hasOwnProperty('addedtracks') && rdata.addedtracks.length > 0) {
        $.each(rdata.addedtracks, function(i, v) {
            if (v.albumindex !== null && v.trackuri != '') {
                // (Ignore if it went into the wishlist)
                debug.log("INSERTED","Displaying",v);
                layoutProcessor.displayCollectionInsert(v);
            }
        });
    } else {
        infobar.markCurrentTrack();
    }

    if (rdata && rdata.hasOwnProperty('stats')) {
        // stats is another html fragment which is the contents of the
        // statistics box at the top of the collection
        $("#fothergill").html(rdata.stats);
    }

    scootTheAlbums($("#collection"));
}

function insert_new_thing(data) {
    switch (data.type) {
        case 'insertAfter':
            debug.log("Insert After",data.where);
            $(data.html).insertAfter(findPosition(data.where));
            break;

        case 'insertAtStart':
            debug.log("Insert At Start",data.where);
            $(data.html).prependTo($('#'+data.where));
            break;

    }
}

function makeProgressOfString(stats) {
    if (stats.progressString != "" && stats.durationString != "") {
        $("#playbackTime").html(stats.progressString + " " + frequentLabels.of + " " + stats.durationString);
    } else if (stats.progressString != "" && stats.durationString == "") {
        $("#playbackTime").html(stats.progressString);
    } else if (stats.progressString == "" && stats.durationString != "") {
        $("#playbackTime").html("0:00 " + frequentLabels.of + " " + stats.durationString);
    } else if (stats.progressString == "" && stats.durationString == "") {
        $("#playbackTime").html("");
    }
}

function setPlaylistControlClicks(t) {
    if (t) {
        $('#random').click(player.controller.toggleRandom).parent().removeClass('thin');
        $('#repeat').click(player.controller.toggleRepeat).parent().removeClass('thin');
        $('#consume').click(player.controller.toggleConsume).parent().removeClass('thin');
    } else {
        $('#random').unbind('click').parent().addClass('thin');
        $('#repeat').unbind('click').parent().addClass('thin');
        $('#consume').unbind('click').parent().addClass('thin');
    }
}

function spotifyTrackListing(data) {
    var h = '';
    for(var i in data.tracks.items) {
        if (player.canPlay('spotify')) {
            h += '<div class="infoclick draggable clickable clicktrack fullwidth" name="'+encodeURIComponent(data.tracks.items[i].uri)+'">';
        } else {
            h += '<div class="fullwidth">';
        }
        h += '<div class="containerbox line">'+
            '<div class="tracknumber fixed">'+data.tracks.items[i].track_number+'</div>'+
            '<div class="expand">'+data.tracks.items[i].name+'</div>'+
            '<div class="fixed playlistrow2 tracktime">'+formatTimeString(data.tracks.items[i].duration_ms/1000)+'</div>'+
            '</div>'+
            '</div>';
    }
    return h;
}

function clickBindType() {
    return prefs.clickmode == 'double' ? 'dblclick' : 'click';
}

function setFunkyBoxSize() {

    $('#pluginplaylistholder .pipl:visible').each(function() {
        var h = $(this);
        var width = calcPercentWidth(h, '.radioplugin_normal', 180, h.width());
        h.find(".radioplugin_normal").css('width', width.toString()+'%');
    });

    $('#pluginplaylistslist .pipl:visible').each(function() {
        var h = $(this);
        var width = calcPercentWidth(h, '.radioplugin_normal', 180, h.width());
        h.find(".radioplugin_normal").css('width', width.toString()+'%');
    });

}

function clearBgImage() {
    $('html').css('background-image', '');
    $.getJSON('backimage.php?clearbackground='+prefs.theme, function(data) {
        $('[name=imagefile').val('');
    });
    $('#cusbgname').html('');
}
