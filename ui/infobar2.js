var infobar = function() {

    var playlistinfo = {};
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
                if (playlistinfo.Title != "" && playlistinfo.trackartist != "") {
                    var options = {
                                    timestamp: parseInt(starttime.toString()),
                                    track: (lfminfo.title === undefined) ? playlistinfo.Title : lfminfo.title,
                                    artist: (lfminfo.trackartist === undefined) ? playlistinfo.trackartist : lfminfo.trackartist,
                                    album: (lfminfo.album === undefined) ? playlistinfo.Album : lfminfo.album
                    };
                    options.chosenByUser = (playlistinfo.type == 'local' && !playlist.radioManager.isRunning()) ? 1 : 0;
                    if (playlistinfo.albumartist && playlistinfo.albumartist != "" && playlistinfo.albumartist.toLowerCase() != playlistinfo.trackartist.toLowerCase()) {
                         options.albumArtist = playlistinfo.albumartist;
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
            if (playlistinfo.Title != "" && playlistinfo.type && playlistinfo.type != "stream") {
                var opts = {
                    track: (lfminfo.title === undefined) ? playlistinfo.Title : lfminfo.title,
                    artist: (lfminfo.trackartist === undefined) ? playlistinfo.trackartist : lfminfo.trackartist,
                    album: (lfminfo.album === undefined) ? playlistinfo.Album : lfminfo.album
                };
                debug.trace("INFOBAR","is updating nowplaying",opts);
                lastfm.track.updateNowPlaying(opts);
                nowplaying_updated = true;
            }
        }
    }

    function setTheText(info) {
        var stuff = mungeplaylistinfo(info);
        setWindowTitle(stuff.doctitle);
        npinfo = stuff.textbits
        debug.debug("INFOBAR","Now Playing Info",npinfo);
        infobar.biggerize();
    }

    function mungeplaylistinfo(info) {
        var npinfo = {};
        var doctitle = "RompЯ";
        debug.log("INFOBAR", "Doing Track Things",info);
        if (info.Title != "") {
            npinfo.Title = info.Title;
            doctitle = info.Title;
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
            npinfo.Artist = s;
            doctitle = doctitle + " : " + s;
        }
        if (info.Album) {
            npinfo.Album = info.Album;
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
                if (npinfo.Artist && npinfo.Album) {
                    lines[1].text = '<i>'+frequentLabels.by+'</i>'+' '+npinfo.Artist+" "
                        +'<i>'+frequentLabels.on+'</i>'+" "+npinfo.Album;
                } else if (npinfo.stream) {
                    if (npinfo.stream != 'No Title') {
                        lines[1].text = npinfo.stream;
                    }
                } else if (npinfo.Album && npinfo.Title) {
                    lines[1].text = '<i>'+frequentLabels.on+'</i>'+" "+npinfo.Album;
                }
                break;

            case 3:
                lines = [
                    {text: " "},
                    {text: '<i>'+frequentLabels.by+'</i>'+" "+npinfo.Artist},
                    {text: '<i>'+frequentLabels.on+'</i>'+" "+npinfo.Album}
                ]
                break;

        }

        if (npinfo.Title) {
            lines[0].text = npinfo.Title;
        } else if (npinfo.Album) {
            lines[0].text = npinfo.Album;
        }

        return lines;

    }

    function put_text_in_area(output_lines, nptext) {
        nptext.empty();
        for (var i in output_lines) {
            // Just in case we have a long line with no spaces, insert some zero-width spaces
            // after -, _, or & to permit text wrapping
            var spaceCount = (output_lines[i].text.split(" ").length - 1);
            if (spaceCount <= 0) {
                output_lines[i].text = output_lines[i].text.replace(/(_|&amp;|-)/g, '$&\u200B');
            }
            nptext.append($('<p>', {class: 'line'+i}).html(output_lines[i].text));
        }
    }

    function doNotification(message, icontype) {
        notifycounter++;
        var div = $('<div>', {
            class: 'containerbox menuitem notification new',
            id: 'notify_'+notifycounter
        }).appendTo('#notifications');
        var icon = $('<div>', {class: 'fixed'}).appendTo(div);
        icon.append($('<i>', {
            class: icontype+' svg-square'
        }));
        div.append($('<div>', {
            class: 'expand indent'
        }).html(message));
        if ($('#notifications').is(':hidden')) {
            $('#notifications').slideToggle('slow');
        }
        div.removeClass('new');
        return div;
    }

    return {

        rejigTheText: function() {
            debug.log('INFOBAR', 'Rejig was called');
            clearTimeout(ftimer);
            ftimer = setTimeout(infobar.biggerize, 500);
        },

        biggerize: function() {
            debug.mark('INFOBAR','biggerizing');
            clearTimeout(ftimer);

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

            nptext.empty().css('font-size', fontsize+'px').css('padding-top', '0px').removeClass('ready').removeClass('calculating').addClass('calculating');

            if (two_lines[0] != ' ') {
                put_text_in_area(two_lines, nptext);

                // We can't simply calculate the font size based on the difference in height,
                // because we've got text wrapping onto multiple lines and we don't know how that will
                // change when we adjust the font size.
                while (fontsize > 4 && (nptext.outerHeight(true) > maxheight)) {
                    fontsize -= 1;
                    nptext.css('font-size', fontsize+'px');
                }

                if (npinfo.Title && npinfo.Album && npinfo.Artist) {
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

        albumImage: function() {
            var aImg = new Image();
            var current_image;
            const noimage = "newimages/compact-disc.png";
            const notafile = "newimages/thisdosntexist.png";

            aImg.onload = function() {
                debug.mark("ALBUMPICTURE","Image Loaded",$(this).attr("src"));
                $('#albumpicture').attr("src", $(this).attr("src")).fadeIn('fast', function() {
                    layoutProcessor.adjustLayout();
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
                        infobar.albumImage.setKey('notrack');
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
                            debug.log("ALBUMPICTURE","  OK, the criteria have been met");
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
                    infobar.error(language.gettext('error_imageupload'));
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
            $("#volume").volumeControl("displayVolume", player.status.volume);
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
                infobar.error(language.gettext("label_playererror")+": "+player.status.error);
                playlist.repopulate();
            }
        },

        markCurrentTrack: function() {
            if (playlistinfo.file) {
                $('[name="'+rawurlencode(playlistinfo.file)+'"]').not('.playlistcurrentitem').not('.podcastresume').addClass('playlistcurrentitem');
                $('[name="'+playlistinfo.file+'"]').not('.playlistcurrentitem').not('.podcastresume').addClass('playlistcurrentitem');
            }
        },

        forceTitleUpdate: function() {
            setTheText(playlistinfo);
        },

        setNowPlayingInfo: function(info) {
            //Now playing info
            debug.trace("INFOBAR","NPinfo",info);
            if (playlistinfo.file) {
                $('[name="'+rawurlencode(playlistinfo.file)+'"]').removeClass('playlistcurrentitem');
                $('[name="'+playlistinfo.file+'"]').removeClass('playlistcurrentitem');
            }
            playlistinfo = info;
            infobar.markCurrentTrack();
            lfminfo = {};
            scrobbled = false;
            nowplaying_updated = false;
            $("#progress").rangechooser("setOptions", {range: info.Time})
            setTheText(info);
            lastfm.showloveban((info.Title != ""));
            if (info.Title != "" && info.trackartist != "") {
                $("#stars").fadeIn('fast');
                $("#dbtags").fadeIn('fast');
                $("#ptagadd").fadeIn('fast');
                $("#playcount").fadeIn('fast');
                lastfm.showloveban(true);
            } else {
                $("#stars").fadeOut('fast');
                $("#dbtags").fadeOut('fast');
                $("#ptagadd").fadeOut('fast');
                $("#playcount").fadeOut('fast');
                lastfm.showloveban(false);
            }
            if (info.file != "") {
                var f = info.file.match(/^podcast[\:|\+](http.*?)\#/);
                if (f && f[1]) {
                    $("#nppodiput").val(f[1]);
                    $("#subscribe").fadeIn('fast');
                } else {
                    $("#subscribe").fadeOut('fast');
                }
            }
            if (info.type != 'stream') {
                $("#addtoplaylist").fadeIn('fast');
            } else {
                $("#saddtoplaylist").fadeOut('fast');
            }
            if (info.Id === -1) {
                $("#stars").fadeOut('fast');
                $("#dbtags").fadeOut('fast');
                $("#playcount").fadeOut('fast');
                $("#subscribe").fadeOut('fast');
                $("#addtoplaylist").fadeOut('fast');
                $("#ptagadd").fadeOut('fast');
                lastfm.showloveban(false);
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
            if (prefs.lastfm_autocorrect && playlistinfo.metadata.iscomposer == 'false' && playlistinfo.type != "stream" && playlistinfo.type != "podcast") {
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
            if (playlistinfo.type != "stream") {
                player.controller.seek(e.max);
            }
        },

        volumeKey: function(inc) {
            var volume = parseInt(player.status.volume);
            debug.trace("INFOBAR","Volume key with volume on",volume);
            volume = volume + inc;
            if (volume > 100) { volume = 100 };
            if (volume < 0) { volume = 0 };
            if (player.controller.volume(volume)) {
                $("#volume").volumeControl("displayVolume", volume);
                prefs.save({volume: parseInt(volume.toString())});
            }
        },

        notify: function(message) {
            debug.trace("INFOBAR","Creating notification",message);
            var div = doNotification(message, 'icon-info-circled');
            setTimeout($.proxy(infobar.removenotify, div, notifycounter), 5000);
            return notifycounter;
        },

        longnotify: function(message) {
            var div = doNotification(message, 'icon-info-circled');
            setTimeout($.proxy(infobar.removenotify, div, notifycounter), 10000);
            return notifycounter;
        },

        error: function(message) {
            var div = doNotification(message, 'icon-attention-1');
            setTimeout($.proxy(infobar.removenotify, div, notifycounter), 5000);
            return notifycounter;
        },

        permerror: function(message) {
            doNotification(message, 'icon-attention-1');
            return notifycounter;
        },

        permnotify: function(message) {
            doNotification(message, 'icon-info-circled');
            return notifycounter;
        },

        smartradio: function(message) {
            var div = doNotification(message, 'icon-wifi');
            setTimeout($.proxy(infobar.removenotify, div, notifycounter), 5000);
            return notifycounter;
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
                podcasts.checkMarkPodcastAsListened(playlist.getCurrent('file'));
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
        },

        addToPlaylist: function(element) {
            player.controller.addTracksToPlaylist(
                element.attr('name'),
                [{uri: playlistinfo.file}],
                null,
                0,
                function() {
                    player.controller.reloadPlaylists();
                    player.controller.checkProgress();
                    if (typeof(playlistManager) != 'undefined') {
                        playlistManager.checkToUpdateTheThing(element.attr('name'));
                    }
                }
            )
        }
    }

}();
