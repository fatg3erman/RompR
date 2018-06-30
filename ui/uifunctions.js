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
        },
        
        populateTagMenu: function(callback) {
            metaHandlers.genericAction(
                'gettags',
                callback,
                function() {
                    debug.error("DB TRACKS", "Failed to get tags");
                }
            );
        }
    }
}();

var pluginManager = function() {

    // Plugins (from the plugins directory) shoud call pluginManager.addPlugin at load time.
    // They must supply a label and either an action function, a setup function, or both.
    // The setup function will be called as soon as all the page scripts have loaded,
    // before the layout is initialised. If a plugin wishes to add icons to the layout,
    // or hotkeys, it should do it here.
    
    // If an action function is provided the plugin's label will be added to the dropdown list
    // above the info panel and the action function will be called when the label is clicked.

    // Alternatively, a script name can be provided. This script will be dynamically loaded
    // the first time the plugin is clicked on. The script MUST call setAction to set the
    // action function (for the next time it's clicked on), and call its own action function.

    var plugins = new Array();

    function openPlugin(index) {
        if (typeof plugins[index].action == 'function') {
            plugins[index].action();
        } else {
            debug.log("PLUGINS","Loading script",plugins[index].script,"for",plugins[index].label);
            $.getScript(plugins[index].script+'?version='+rompr_version).fail(function(data, settings, exception) {
                debug.error("PLUGINS","Failed Loading Script",exception);
            });
        }
    }

    return {
        addPlugin: function(label, action, setup, script, onmenu) {
            debug.log("PLUGINS","Adding Plugin",label,onmenu);
            plugins.push({label: label, action: action, setup: setup, script: script, onmenu: onmenu});
        },

        doEarlyInit: function() {
            for (var i in plugins) {
                if (plugins[i].setup) {
                    if (!only_plugins_on_menu || plugins[i].menu) {
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
                    if (only_plugins_on_menu) {
                        if (plugins[i].onmenu) {
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

function show_albumart_update_window() {
    var ws = getWindowSize();
    var fnarkle = new popup({
        width: 600,
        height: 400,
        ypos: ws.y/2,
        title: "Album Art Update",
        hasclosebutton: false});
    var mywin = fnarkle.create();
    mywin.append('<div id="artupdate" class="fullwdith"></div>');
    $('#artupdate').append('<div class="pref textcentre">Your Album Art needs to be updated. This process has now started. You can close this window to pause the process and it will continue the next time you open Rompr. Until you have updated all your art Rompr may run slowly and album art may look wierd</div>');
    $('#artupdate').append('<div id="albumart_update_bar" style="height:2em;width:100%"></div>');
    $('#artupdate').append('<div class="pref textcentre"><button id="artclosebutton">Close</button></div>');
    fnarkle.useAsCloseButton($('#artclosebutton'), stop_albumart_update);
    $('#albumart_update_bar').rangechooser({
        ends: ['max'],
        startmax: 0,
        range: 100
    });
    fnarkle.open();
    setTimeout(fnarkle.setWindowToContent, 2000);
    $('.open_albumart').hide();
    do_albumart_update();
}

function do_albumart_update() {
    $.getJSON('update_albumart.php', function(data) {
        $('#albumart_update_bar').rangechooser('setProgress', data.percent);
        if (data.percent < 100 && albumart_update) {
            setTimeout(do_albumart_update, 100);
        } else {
            $('#artclosebutton').click();
        }
    });
}

function stop_albumart_update() {
    debug.log("UI", "Cancelling album art update");
    $('.open_albumart').show();
    albumart_update = false;
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

function hidePanel(panel) {
    var is_hidden = $("#"+panel).is(':hidden');
    var new_state = prefs["hide_"+panel];
    debug.log("GENERAL","Hide Panel",panel,is_hidden,new_state);
    layoutProcessor.hidePanel(panel, is_hidden, new_state);
    setChooserButtons();
}

function doSomethingUseful(div,text) {
    var html = '<div class="containerbox bar">';
    if (typeof div == "string") {
        html = '<div class="containerbox bar menuitem" id="spinner_'+div+'">';
    }
    html += '<div class="fixed alignmid">'+
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
    layoutProcessor.adjustLayout();
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

function setSearchLabelWidth() {
    debug.trace("UI","Setting Search Label Widths");
    var w = 0;
    $.each($(".slt:visible"), function() {
        if ($(this).width() > w) {
            w = $(this).width();
        }
    });
    w += 8;
    $(".searchlabel:visible").css("width", w+"px");
    $(".searchlabel").not(':visible').css("width", "0px");
    if (prefs.search_limit_limitsearch) {
        $("#mopidysearchdomains").show();
    } else {
        $("#mopidysearchdomains").hide();
    }
    if (prefs.advanced_search_open) {
        $("#advsearchoptions").show();
        $('[name="advsearchoptions"]').toggleOpen();
    } else {
        $("#advsearchoptions").hide();
        $('[name="advsearchoptions"]').toggleClosed();
    }
}

function setSpotiLabelWidth() {
    debug.trace("UI","Setting Spotify Label Widths");
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
        if (typeof(prefs.shownupdatewindow) != 'string' || compare_version_numbers(prefs.shownupdatewindow, rompr_version)) {
            var fnarkle = new popup({
                width: 1600,
                height: 1600,
                title: 'RompЯ Version '+rompr_version,
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

function compare_version_numbers(ver1, ver2) {
    // Returns true if ver1 < ver2
    // Eg ver1 = '1.14.9' ver2 = '1.14.10'
    // We need to compare them as digits because as a string 10 < 9
    // The internet's collected wisdom says the only way to do it is to compare
    // digit by digit, so I came up with this.
    var ver1_split = ver1.split('.');
    var ver2_split = ver2.split('.');
    for (var i in ver1_split) {
        if (prefs.dev_mode && ver2_split[i].length > 4) {
            return false;
        }
        if (i > ver2_split.length) {
            // ver1 has more digits than ver2.
            // If we got here then we must have already compared the other digits, therefore ver1 must be > ver2 ie. 1.14.1 vs 1.14
            return false;
        }
        if (parseInt(ver1_split[i]) < parseInt(ver2_split[i])) {
            return true;
        } else if (parseInt(ver1_split[i]) > parseInt(ver2_split[i])) {
            return false;
        }
    }
    if (ver2_split.length > ver1_split.length) {
        // We got here with no differences, therefore ver2 has more digits than ver1 and must be the greater of the two
        return true;
    }
    return false;
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
    if (childSelector != '.collectionitem') {
        pixelwidth -= masonry_gutter;
    }
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
        beets: [{dir: "Beets library/Albums by Artist", label: "Beets Library"}],
        spotify: [{dir: "Spotify Playlists", label: "Spotify Playlists"}],
        spotifyweb: [{dir: "Spotify Web Browse/Your Music/Albums", label: "Spotify 'Your Music'"},
                     {dir: "Spotify Web Browse/Your Artists", label: "Spotify 'Your Artists'"}],
        gmusic: [{dir: "Google Music/Albums", label: "Google Music"}],
        soundcloud: [{dir: "SoundCloud/Liked", label: "SoundCloud Liked"},
                     {dir: "SoundCloud/Sets", label: "SoundCloud Sets"}],
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
    if (!player.canPlay('beets')) {
        $('#beets_server_location').parent().hide();
    }
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
                        imgobj.removeClass('nospin notexist notfound').addClass('spinner notexist').attr('src', 'notanimage.jpg');
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
                        imgobj.removeClass('nospin notexist notfound').addClass('spinner notexist').attr('src', 'notanimage.jpg');
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
        imgobj.removeClass('nospin notexist notfound').addClass('spinner notexist').attr('src', 'notanimage.jpg');
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

function setWindowTitle(t) {
    if (document.title != t) {
        document.title = t;
    }
}
