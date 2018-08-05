var infobar = function() {

    var mousepos;
    var sliderclamps = 0;
    var vtimer = null;
    var trackinfo = {};
    var lfminfo = {};
    var npinfo = {};
    var starttime = 0;
    var scrobbled = false;
    var nowplaying_updated = false;
    var markedaslistened = false;
    var fontsize = 8;
    var ftimer = null;
    var singling = false;
    var notifycounter = 0;

    function scrobble() {
        if (!scrobbled) {
            debug.trace("INFOBAR","Track is not scrobbled");
            scrobbled = true;
            if (lastfm.isLoggedIn()) {
                if (trackinfo.title != "" && trackinfo.trackartist != "") {
                    var options = {
                                    timestamp: parseInt(starttime.toString()),
                                    track: (lfminfo.title === undefined) ? trackinfo.title : lfminfo.title,
                                    artist: (lfminfo.trackartist === undefined) ? trackinfo.trackartist : lfminfo.trackartist,
                                    album: (lfminfo.album === undefined) ? trackinfo.album : lfminfo.album
                    };
                    options.chosenByUser = (trackinfo.type == 'local' && !playlist.radioManager.isRunning()) ? 1 : 0;
                     if (trackinfo.albumartist && trackinfo.albumartist != "" && trackinfo.albumartist.toLowerCase() != trackinfo.trackartist.toLowerCase()) {
                         options.albumArtist = trackinfo.albumartist;
                     }
                    debug.log("INFOBAR","Scrobbling", options);
                    lastfm.track.scrobble( options );
                }
            }
            debug.log("INFOBAR","Track playcount being updated");
            nowplaying.incPlaycount(null);
        }
    }

    function updateNowPlaying() {
        if (!nowplaying_updated && lastfm.isLoggedIn()) {
            if (trackinfo.title != "" && trackinfo.type && trackinfo.type != "stream") {
                var opts = {
                    track: (lfminfo.title === undefined) ? trackinfo.title : lfminfo.title,
                    artist: (lfminfo.trackartist === undefined) ? trackinfo.trackartist : lfminfo.trackartist,
                    album: (lfminfo.album === undefined) ? trackinfo.album : lfminfo.album
                };
                debug.trace("INFOBAR","is updating nowplaying",opts);
                lastfm.track.updateNowPlaying(opts);
                nowplaying_updated = true;
            }
        }
    }

    function setTheText(info) {
        var stuff = mungeTrackInfo(info);
        setWindowTitle(stuff.doctitle);
        npinfo = stuff.textbits
        debug.debug("INFOBAR","Now Playing Info",npinfo);
        infobar.biggerize();
    }

    function mungeTrackInfo(info) {
        var npinfo = {};
        var doctitle = "RompЯ";
        debug.log("INFOBAR", "Doing Track Things",info);
        if (info.title != "") {
            npinfo.title = info.title;
            doctitle = info.title;
        }
        var s = info.trackartist;
        if (info.type != "stream" || s != "") {
            if (info.metadata && info.metadata.artists) {
                s = "";
                var prevtype = "";
                for (var i in info.metadata.artists) {
                    var joinstring = ", ";
                    var afterstring = "";
                    if (info.metadata.artists[i].type == "performer" && prevtype != "performer" && prevtype != "composer") {
                        joinstring = " : ";
                    }
                    if (i == info.metadata.artists.length - 1) {
                        joinstring = (info.metadata.artists.length == 2 && prevtype == "artist" && info.metadata.artists[i].type == "albumartist") ? " / " : " & ";
                    }
                    if (info.metadata.artists[i].type == "composer") {
                        if (!info.metadata.artists[i].name.match(/composer/i)) {
                            afterstring = " ("+language.gettext('label_composer')+")";
                        }
                        if (prevtype == "composer") {
                            joinstring = ", "
                        } else {
                            joinstring = " : "
                        }
                    }
                    if (i == 0) {
                        joinstring = "";
                    }
                    s = s + joinstring + info.metadata.artists[i].name + afterstring;
                    prevtype = info.metadata.artists[i].type;
                }
            }
        }
        if (s != "") {
            npinfo.artist = s;
            doctitle = doctitle + " : " + s;
        }
        if (info.album) {
            npinfo.album = info.album;
        }
        npinfo.stream = info.stream;
        if (prefs.player_in_titlebar) {
            doctitle = prefs.currenthost+' - RompЯ';
        }

        return {doctitle: doctitle, textbits: npinfo};

    }

    function getLines(numlines) {

        var lines;
        switch (numlines) {
            case 2:
                lines = [
                    {text: " "},
                    {text: " "}
                ];
                if (npinfo.artist && npinfo.album) {
                    lines[1].text = '<i>'+frequentLabels.by+'</i>'+' '+npinfo.artist+" "
                        +'<i>'+frequentLabels.on+'</i>'+" "+npinfo.album;
                } else if (npinfo.stream) {
                    lines[1].text = npinfo.stream;
                } else if (npinfo.album && npinfo.title) {
                    lines[1].text = '<i>'+frequentLabels.on+'</i>'+" "+npinfo.album;
                }
                break;

            case 3:
                lines = [
                    {text: " "},
                    {text: '<i>'+frequentLabels.by+'</i>'+" "+npinfo.artist},
                    {text: '<i>'+frequentLabels.on+'</i>'+" "+npinfo.album}
                ]
                break;

        }

        if (npinfo.title) {
            lines[0].text = npinfo.title;
        } else if (npinfo.album) {
            lines[0].text = npinfo.album;
        }

        return lines;

    }

    function put_text_in_area(output_lines, nptext) {
        nptext.empty();
        for (var i in output_lines) {
            nptext.append($('<p>', {class: 'line'+i}).html(output_lines[i].text));
        }
    }

    return {
        NOTIFY: 0,
        ERROR: 1,
        PERMERROR: 2,
        PERMNOTIFY: 3,
        SMARTRADIO: 4,
        LONGNOTIFY: 5,

        biggerize: function() {

            if (Object.keys(npinfo).length == 0 || $("#nptext").is(':hidden') || $("#infobar").is(':hidden')) {
                debug.log("INFOBAR","Not biggerizing because", Object.keys(npinfo).length, $("#nptext").is(':hidden'), $("#infobar").is(':hidden'));
                $("#nptext").html("");
                return;
            }
            debug.debug("INFOBAR","Biggerizing",npinfo);

            var nptext = $('#nptext');
            var parent = nptext.parent();
            var maxheight = parent.height();

            // Start with a font size that will fill the height if no text wraps
            var fontsize = Math.floor((maxheight/1.75)/1.25);
            var two_lines = getLines(2);

            nptext.empty().css('font-size', fontsize+'px').css('padding-top', '0px').removeClass('ready').addClass('calculating');

            if (two_lines[0] != ' ') {
                put_text_in_area(two_lines, nptext);

                // We can't simply calculate the font size based on the difference in height,
                // because we've got text wrapping onto multiple lines and we don't know how that will
                // change when we adjust the font size
                while (nptext.outerHeight(true) > maxheight) {
                    fontsize -= 1;
                    nptext.css('font-size', fontsize+'px');
                }

                if (npinfo.title && npinfo.album && npinfo.artist) {
                    /* Does it still fit if we use 3 lines -  this is because
                        Title
                        by Artist
                        on Album Has A Name
                    Looks better than
                        Title
                        by Artist on Album Has
                        A Name
                    */
                    var three_lines = getLines(3);
                    put_text_in_area(three_lines, nptext);
                    if (nptext.outerHeight(true) > maxheight) {
                        put_text_in_area(two_lines, nptext);
                    }

                }

                var top = Math.max(0, Math.floor((maxheight - nptext.height())/2));
                nptext.css("padding-top", top+"px").removeClass('calculating').addClass('ready');

            }
        },

        rejigTheText: function() {
            clearTimeout(ftimer);
            ftimer = setTimeout(infobar.biggerize, 500);
        },

        albumImage: function() {
            var aImg = new Image();
            var current_image;
            const noimage = "newimages/compact-disc.png";
            const notafile = "newimages/thisdosntexist.png";

            aImg.onload = function() {
                debug.trace("ALBUMPICTURE","Image Loaded",$(this).attr("src"));
                $('#albumpicture').attr("src", $(this).attr("src")).fadeIn('fast', function() {
                    layoutProcessor.adjustLayout();
                    infobar.biggerize();
                });
                if (!$('#albumpicture').hasClass('clickicon')) {
                    $('#albumpicture').addClass('clickicon');
                }
                $('#albumpicture').off('click').on('click', infobar.albumImage.displayOriginalImage);
            }

            aImg.onerror = function() {
                debug.trace("ALBUMPICTURE","Image Failed To Load",$(this).attr("src"));
                $('img[name="'+$(this).attr('name')+'"]').addClass("notfound");
                $('#albumpicture').fadeOut('fast',layoutProcessor.adjustLayout);
            }

            return {
                setSource: function(data) {
                    debug.trace("ALBUMPICTURE","New source",data,"current is",aImg.src);
                    if (data.key && data.key != aImg.name) {
                        return false;
                    }
                    if (data.images === null) {
                        // null means playlist.emptytrack. Set the source to a file that doesn't exist
                        // and let the onerror handler do the stuff. Then if we start playing the same
                        // album again the image src will change and the image will be re-displayed.
                        infobar.albumImage.setKey('none');
                        aImg.src = notafile;
                    } else if (data.images.asdownloaded == "") {
                        // No album image was supplied
                        aImg.src = noimage;
                    } else {
                        debug.trace("ALBUMPICTURE","Source is being set to ",data.images.asdownloaded);
                        aImg.src = data.images.asdownloaded;
                    }
                },

                setSecondarySource: function(data) {
                    if (data.key === undefined || data.key == aImg.getAttribute('name')) {
                        debug.log("ALBUMPICTURE","Secondary Source is being set to ",data.image,aImg);
                        if (data.image != "" && data.image !== null && (aImg.src.match(noimage) !== null || aImg.src.match(notafile) !== null)) {
                            debug.trace("ALBUMPICTURE","  OK, the criteria have been met");
                            aImg.src = data.image;
                        }
                    }
                },

                setKey: function(key) {
                    if (aImg.name != key) {
                        debug.trace("ALBUMPICTURE","Setting Image Key to ",key);
                        $(aImg).attr('name', key);
                    }
                },

                displayOriginalImage: function(event) {
                    debug.log("ALBUMNPICTURE","Display Original Image");
                    imagePopup.create($(event.target), event, aImg.src);
                },

                dragEnter: function(ev) {
                    evt = ev.originalEvent;
                    evt.stopPropagation();
                    evt.preventDefault();
                    $(ev.target).parent().addClass("highlighted");
                    return false;
                },

                dragOver: function(ev) {
                    evt = ev.originalEvent;
                    evt.stopPropagation();
                    evt.preventDefault();
                    return false;
                },

                dragLeave: function(ev) {
                    evt = ev.originalEvent;
                    evt.stopPropagation();
                    evt.preventDefault();
                    $(ev.target).parent().removeClass("highlighted");
                    return false;
                },

                handleDrop: function(ev) {
                    debug.mark("INFOBAR","Something dropped onto album image");
                    $(ev.target).parent().removeClass("highlighted");
                    $('#albumpicture').attr("name", aImg.name).removeAttr('src');
                    current_image = aImg.src;
                    aImg.src = noimage;
                    dropProcessor(ev.originalEvent, $('#albumpicture'), coverscraper, infobar.albumImage.uploaded, infobar.albumImage.uploadfail);
                },

                uploaded: function(data) {
                    if (data.asdownlaoded) {
                        infobar.albumimage.uploadfail();
                        return;
                    }
                    debug.log("INFOBAR","Album Image Updated Successfully",aImg.name);
                    $('#albumpicture').removeClass('spinner').addClass('nospin').removeAttr('name');
                    update_ui_images(aImg.name, data);
                },

                uploadfail: function() {
                    $('#albumpicture').removeClass('spinner').addClass('nospin').removeAttr('name');
                    aImg.src = current_image;
                    infobar.notify(infobar.ERROR, "Image Upload Failed!");
                }

            }

        }(),

        playbutton: function() {
            state = 0;

            return {
                clicked: function() {
                    switch (player.status.state) {
                        case "play":
                            player.controller.pause();
                            break;
                        case "pause":
                        case "stop":
                            player.controller.play();
                            break;
                    }
                },

                setState: function(s) {
                    if (s != state) {
                        debug.debug("INFOBAR","Setting Play Button State");
                        state = s;
                        switch (state) {
                            case "play":
                                $(".icon-play-circled").removeClass("icon-play-circled").addClass("icon-pause-circled");
                                break;
                            default:
                                $(".icon-pause-circled").removeClass("icon-pause-circled").addClass("icon-play-circled");
                                break;
                        }
                    }
                }
            }
        }(),

        updateWindowValues: function() {
            $("#volume").rangechooser("setProgress", player.status.volume);
            infobar.playbutton.setState(player.status.state);
            playlist.setButtons();
            if (player.status.single == 0 && singling) {
                $('.icon-to-end-1').stopFlasher();
                singling = false;
            }
            if (player.status.single == 1 && !singling) {
                $('.icon-to-end-1').makeFlasher({flashtime: 5});
                singling = true;
            }
            if (player.status.error && player.status.error != null) {
                infobar.notify(infobar.ERROR, language.gettext("label_playererror")+": "+player.status.error);
                playlist.repopulate();
            }
        },

        markCurrentTrack: function() {
            if (trackinfo.location) {
                $('[name="'+rawurlencode(trackinfo.location)+'"]').not('.playlistcurrentitem').addClass('playlistcurrentitem');
                $('[name="'+trackinfo.location+'"]').not('.playlistcurrentitem').not('.podcastresume').addClass('playlistcurrentitem');
            }
        },

        forceTitleUpdate: function() {
            setTheText(trackinfo);
        },

        setNowPlayingInfo: function(info) {
            //Now playing info
            debug.trace("INFOBAR","NPinfo",info);
            if (trackinfo.location) {
                $('[name="'+rawurlencode(trackinfo.location)+'"]').removeClass('playlistcurrentitem');
                $('[name="'+trackinfo.location+'"]').removeClass('playlistcurrentitem');
            }
            trackinfo = info;
            infobar.markCurrentTrack();
            lfminfo = {};
            scrobbled = false;
            nowplaying_updated = false;
            $("#progress").rangechooser("setOptions", {range: info.duration})
            setTheText(info);
            lastfm.showloveban((info.title != ""));
            if (info.title != "" && info.trackartist != "") {
                $("#stars").fadeIn('fast');
                $("#dbtags").fadeIn('fast');
                $("#playcount").fadeIn('fast');
            } else {
                $("#stars").fadeOut('fast');
                $("#dbtags").fadeOut('fast');
                $("#playcount").fadeOut('fast');
            }
            if (info.location != "") {
                var f = info.location.match(/^podcast[\:|\+](http.*?)\#/);
                if (f && f[1]) {
                    $("#nppodiput").val(f[1]);
                    $("#subscribe").fadeIn('fast');
                } else {
                    $("#subscribe").fadeOut('fast');
                }
            }
            if (info.backendid === -1) {
                $("#stars").fadeOut('fast');
                $("#dbtags").fadeOut('fast');
                $("#playcount").fadeOut('fast');
                $("#subscribe").fadeOut('fast');
            } else {
                infobar.albumImage.setKey(info.key);
            }
            infobar.albumImage.setSource(info);
            layoutProcessor.adjustLayout();
        },

        stopped: function() {
            scrobbled = false;
            nowplaying_updated = false;
        },

        setLastFMCorrections: function(info) {
            lfminfo = info;
            if (prefs.lastfm_autocorrect && trackinfo.metadata.iscomposer == 'false' && trackinfo.type != "stream" && trackinfo.type != "podcast") {
                setTheText(info);
            }
            infobar.albumImage.setSecondarySource(info);
        },

        setStartTime: function(elapsed) {
            starttime = (Date.now())/1000 - parseFloat(elapsed);
        },

        progress: function() {
            return (player.status.state == "stop") ? 0 : (Date.now())/1000 - starttime;
        },

        seek: function(e) {
            if (trackinfo.type != "stream") {
                player.controller.seek(e.max);
            }
        },

        volumemoved: function(v) {
            if (sliderclamps == 0) {
                // Double interlock to prevent hammering mpd:
                // We don't send another volume request until two things happen:
                // 1. The previous volume command returns
                // 2. The timer expires
                sliderclamps = 2;
                if (v.max != player.status.volume) {
                    debug.log("INFOBAR","Setting volume",v.max);
                    player.controller.volume(v.max, infobar.releaseTheClamps);
                    clearTimeout(vtimer);
                    vtimer = setTimeout(infobar.releaseTheClamps, 500);
                }
            }
        },

        volumeend: function(v) {
            clearTimeout(vtimer);
            sliderclamps = 0;
            debug.log("INFOBAR","Setting volume",v.max);
            player.controller.volume(v.max, infobar.releaseTheClamps);
        },

        releaseTheClamps: function() {
            sliderclamps--;
        },

        volumeKey: function(inc) {
            var volume = parseInt(player.status.volume);
            debug.trace("INFOBAR","Volume key with volume on",volume);
            volume = volume + inc;
            if (volume > 100) { volume = 100 };
            if (volume < 0) { volume = 0 };
            if (player.controller.volume(volume)) {
                $("#volume").rangechooser("setRange", {min: 0, max: volume});
                prefs.save({volume: parseInt(volume.toString())});
            }
        },

        notify: function(type, message) {
            var div = $('<div>', {
                class: 'containerbox menuitem notification new',
                id: 'notify_'+notifycounter
            }).appendTo('#notifications');
            var icon = $('<div>', {class: 'fixed'}).appendTo(div);
            switch (type) {
                case infobar.NOTIFY:
                case infobar.PERMNOTIFY:
                case infobar.LONGNOTIFY:
                    icon.append($('<i>', {
                        class: 'icon-info-circled svg-square'
                    }));
                    break;

                case infobar.ERROR:
                case infobar.PERMERROR:
                    icon.append($('<i>', {
                        class: 'icon-attention-1 svg-square'
                    }));
                    break;

                case infobar.SMARTRADIO:
                    icon.append($('<i>', {
                        class: 'icon-wifi svg-square'
                    }));
                    break;
            }
            div.append($('<div>', {
                class: 'expand indent'
            }).html(message));
            if ($('#notifications').is(':hidden')) {
                $('#notifications').slideToggle('slow');
            }
            div.removeClass('new');
            if (type !== infobar.PERMERROR && type !== infobar.PERMNOTIFY) {
                setTimeout($.proxy(infobar.removenotify, div, notifycounter), type == infobar.LONGNOTIFY ? 10000 : 5000);
            }
            notifycounter++;
            return notifycounter-1;
        },

        removenotify: function(data) {
            if ($('#notifications>div').length == 1) {
                debug.log("INFOBAR","Removing single notification");
                if ($('#notifications').is(':visible')) {
                    $('#notifications').slideToggle('slow', function() {
                        $('#notifications').empty();
                    });
                } else {
                    $('#notifications').empty();
                }
            } else {
                debug.log("INFOBAR","Removing notification", data);
                $('#notify_'+data).fadeOut('slow', function() {
                    $('#notify_'+data).remove();
                });
            }
        },

        createProgressBar: function() {
            $("#progress").rangechooser({
                ends: ['max'],
                onstop: infobar.seek,
                startmax: 0,
                animate: true
            });
        },

        setProgress: function(progress, duration) {
            if (progress < 3) {
                scrobbled = false;
                nowplaying_updated = false;
                markedaslistened = false;
            }
            if (progress > 4) { updateNowPlaying() };
            var percent = (duration == 0) ? 0 : (progress/duration) * 100;
            if (percent >= prefs.scrobblepercent) {
                scrobble();
            }
            if (!markedaslistened && percent >= 95 && playlist.getCurrent('type') == 'podcast') {
                podcasts.checkMarkPodcastAsListened(playlist.getCurrent('location'));
                markedaslistened = true;
            }
            $("#progress").rangechooser("setRange", {min: 0, max: progress});
            var remain = duration - progress;
            layoutProcessor.setProgressTime({
                progress: progress,
                duration: duration,
                remain: remain,
                progressString: formatTimeString(progress),
                durationString: formatTimeString(duration),
                remainString: '-'+formatTimeString(remain)
            });
            nowplaying.progressUpdate(percent);
        }
    }

}();
