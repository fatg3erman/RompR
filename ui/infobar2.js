var infobar = function() {

    var notifytimer = null;
    var mousepos;
    var sliderclamps = 0;
    var vtimer = null;
    var trackinfo = {};
    var lfminfo = {};
    var npinfo = {};
    var starttime = 0;
    var scrobbled = false;
    var nowplaying_updated = false;
    var fontsize = 8;
    var ftimer = null;
    var canvas = null;
    var context = null;
    var singling = false;

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
        debug.log("INFOBAR","Now Playing Info",npinfo);
        infobar.biggerize(2);
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

    function getWidth(text, fontsize) {

        if (canvas == null) {
            canvas = document.createElement("canvas");
            context = canvas.getContext("2d");
        }
        var font = $("#nowplaying").css("font-family");
        context.font = "bold "+fontsize+"px "+font;
        var metrics = context.measureText(text);
        return metrics.width;
    }

    function checkLines(lines, maxwidth) {
        for (var i in lines) {
            if (lines[i].width > maxwidth) return true;
        }
    }

    function splitLongLine(lines, tosplit) {
        var middle = Math.floor(lines[tosplit].text.length / 2);
        var before = lines[tosplit].text.lastIndexOf(', ', middle);
        var after = lines[tosplit].text.indexOf(', ', middle + 1);

        if (before == -1 || (after != -1 && middle - before >= after - middle)) {
            middle = after;
        } else {
            middle = before;
        }
        var retval = lines[tosplit].text.substr(middle + 1);
        var spliggo = lines[tosplit].text.substr(0, middle)+",";
        if (spliggo.length < 6) {
            return null;
        } else {
            lines[tosplit].text = spliggo;
            return retval;
        }
    }

    return {
        NOTIFY: 0,
        ERROR: 1,
        PERMERROR: 2,
        PERMNOTIFY: 3,
        SMARTRADIO: 4,

        biggerize: function(numlines) {

            // Fit the nowplaying text in the panel, always trying to make the best use of the available height
            // We adjust by checking width, since we don't wrap lines as that causes hell with the layout
            if (Object.keys(npinfo).length == 0 || $("#nptext").is(':hidden') || $("#infobar").is(':hidden')) {
                debug.log("INFOBAR","Not biggerizing because", Object.keys(npinfo).length, $("#nptext").is(':hidden'), $("#infobar").is(':hidden'));
                $("#nptext").html("");
                return;
            }
            debug.debug("INFOBAR","Biggerizing",npinfo,numlines);
            // Start by trying with two lines:
            // Track Name
            // by Artist on Album
            if (!numlines) numlines = 2;
            var maxlines =  (npinfo.artist && npinfo.album && npinfo.title) ? 3 : 2;
            var parent = $("#nptext").parent();
            var maxheight = parent.height();
            var splittext = null;
            var maxwidth = parent.width() - 8;
            // debug.trace("INFOBAR","Maxwidth is",maxwidth);
            if (maxwidth <= 8) {
                debug.warn("INFOBAR", "Insufficient Space for text");
                $("#nptext").html("");
                return;
            }
            var lines = [
                {weight: (numlines == 2 ? 62 : 46), width: maxwidth+1, text: " "},
                {weight: (numlines == 2 ? 38 : 26), width: maxwidth+1, text: " "}
            ];

            if (npinfo.title) {
                lines[0].text = npinfo.title;
            } else if (npinfo.album) {
                lines[0].text = npinfo.album;
            }

            switch (numlines) {
                case 2:
                    if (npinfo.artist && npinfo.album) {
                        lines[1].text = frequentLabels.by+" "+npinfo.artist+" "+frequentLabels.on+" "+npinfo.album;
                    } else if (npinfo.stream) {
                        lines[0].weight = 65;
                        lines[1].weight = 35;
                        lines[1].text = npinfo.stream;
                    } else if (npinfo.album && npinfo.title) {
                        lines[1].text = frequentLabels.on+" "+npinfo.album;
                    }
                    if (lines[1].text == " ") {
                        lines.pop();
                        lines[0].weight = 100;
                    }
                    break;

                case 3:
                    lines.push({weight: 26, width: maxwidth+1, text: " "});
                    lines[1].text = frequentLabels.by+" "+npinfo.artist;
                    lines[2].text = frequentLabels.on+" "+npinfo.album;
                    if (lines[1].text.length >= lines[2].text.length*2) {
                        splittext = splitLongLine(lines,1);
                    }
                    break;

                default:
                    debug.warn("INFOBAR","Invalid numlines",numlines);
                    break;

            }

            var totalheight = 0;
            while( checkLines(lines, maxwidth) ) {
                var factor = 100;
                totalheight = 0;
                for (var i in lines) {
                    var f = maxwidth/lines[i].width;
                    if (f < factor) factor = f;
                }
                for (var i in lines) {
                    lines[i].weight = lines[i].weight * factor;
                    // The 0.6666 comes in because the font height is 2/3rds of the line height,
                    // or to put it another way the line height is 1.5x the font height
                    lines[i].height = Math.round((maxheight/100)*lines[i].weight*0.6666);
                    if (i == 0 || numlines == 2 || splittext == null) {
                        lines[i].width = getWidth(lines[i].text, lines[i].height);
                    } else {
                        if (i == 1) {
                            lines[i].width = getWidth(lines[i].text, lines[i].height);
                        } else {
                            lines[i].width = getWidth(splittext+" "+lines[2].text, lines[i].height);
                        }
                    }
                    totalheight += Math.round(lines[i].height*1.5);
                }
            }

            // If this leaves enough space to split it into 3 lines, do that
            // Track Name
            // by Artist
            // on Album
            if (numlines < maxlines && totalheight < (maxheight - Math.round(lines[1].height*1.5))) {
                infobar.biggerize(numlines+1);
                return;
            }

            // Now, if there's still vertical space, we can make the title bigger.
            // Making one of the other two lines bigger looks bad
            if (totalheight < maxheight) {
                lines[0].height = Math.round(lines[0].height*(maxheight/totalheight));
                lines[0].width = getWidth(lines[0].text, lines[0].height);
                if (lines[0].width > maxwidth) {
                    lines[0].height = Math.round(lines[0].height*(maxwidth/lines[0].width));
                    lines[0].width = getWidth(lines[0].text, lines[0].height);
                }
            }

            // Min line neight is 7 pixels. This isn't completely safe but it'll just run off the edge if it's too long
            totalheight = 0;
            for (var i in lines) {
                if (lines[i].height < 7) {
                    lines[i].height = 7;
                }
                totalheight += Math.round(lines[i].height*1.5);
            }

            // Now adjust the text so it has appropriate italic and bold markup.
            // We measured it all in bold, because canvas doesn't support html tags,
            // but normal-italic is usally around the same width as bold.
            lines[0].text = '<b>'+lines[0].text+'</b>';
            if (numlines == 2) {
                if (npinfo.artist && npinfo.album) {
                    lines[1].text = '<i>'+frequentLabels.by+"</i> <b>"+npinfo.artist+"</b> <i>"+frequentLabels.on+"</i> <b>"+npinfo.album+'</b>';
                } else if (npinfo.stream) {
                    lines[1].text = '<i>'+npinfo.stream+'</i>';
                } else if (npinfo.album && npinfo.title) {
                    lines[1].text = "<i>"+frequentLabels.on+"</i> <b>"+npinfo.album+'</b>';
                }
            } else {
                if (splittext) {
                    var n = [{text: npinfo.artist}];
                    splittext = splitLongLine(n,0);
                    lines[1].text = '<i>'+frequentLabels.by+"</i> <b>"+n[0].text+'</b>';
                    lines[2].text = '<b>'+splittext+'</b> <i>'+frequentLabels.on+"</i> <b>"+npinfo.album+'</b>';
                } else {
                    lines[1].text = '<i>'+frequentLabels.by+"</i> <b>"+npinfo.artist+'</b>';
                    lines[2].text = '<i>'+frequentLabels.on+"</i> <b>"+npinfo.album+'</b>';
                }
            }

            var html = "";
            for (var i in lines) {
                html += '<span style="font-size:'+lines[i].height+'px;line-height:'+Math.round(lines[i].height*1.5)+'px">'+lines[i].text+'</span>';
                if (i < lines.length-1) {
                    html += '<br />';
                }
            }

            var top = Math.floor((maxheight - totalheight)/2);
            if (top < 0) top = 0;
            $("#nptext").css("padding-top", top+"px");

            // Make sure the line spacing caused by the <br> is consistent
            if (lines[1]) {
                $("#nptext").css("font-size", lines[1].height+"px");
            } else {
                $("#nptext").css("font-size", lines[0].height+"px");
            }
            debug.debug("INFOBAR","Biggerized",numlines);
            $("#nptext").empty().html(html);
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
                $('#albumpicture').unbind('click').bind('click', infobar.albumImage.displayOriginalImage);
            }
            
            aImg.onerror = function() {
                debug.trace("ALBUMPICTURE","Image Failed To Load",$(this).attr("src"));
                $('img[name="'+$(this).attr('name')+'"]').addClass("notfound");
                $('#albumpicture').fadeOut('fast',layoutProcessor.adjustLayout);
            }

            return {
                setSource: function(data) {
                    debug.trace("ALBUMPICTURE","New source",data.image,"current is",aImg.src);
                    if (data.key && data.key != aImg.name) {
                        return false;
                    }
                    if (data.image === null) {
                        // null means playlist.emptytrack. Set the source to a file that doesn't exist
                        // and let the onerror handler do the stuff. Then if we start playing the same
                        // album sa previously again the simage src will change and the image will be re-displayed.
                        aImg.src = notafile;
                    } else if (data.image == "") {
                        // No album image was supplied
                        aImg.src = noimage;
                    } else {
                        debug.trace("ALBUMPICTURE","Source is being set to ",data.image);
                        aImg.src = data.image;
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
                    imagekey = $('#albumpicture').attr("name");
                    current_image = aImg.src;
                    aImg.src = noimage;
                    $('#albumpicture').attr('src', noimage);
                    dropProcessor(ev.originalEvent, $('#albumpicture'), imagekey, null, infobar.albumImage.uploaded, infobar.albumImage.uploadfail);
                },

                uploaded: function(data) {
                    if (!data.url || data.url == "") {
                        infobar.albumimage.uploadfail();
                        return;
                    }
                    debug.log("INFOBAR","Album Image Updated Successfully",aImg.name);
                    $('#albumpicture').removeClass('spinner').addClass('nospin');
                    var firefoxcrapnesshack = Math.floor(Date.now());
                    infobar.albumImage.setSource({image: "albumart/asdownloaded/"+aImg.name+'.jpg?version='+firefoxcrapnesshack.toString()});
                    $('img[name="'+aImg.name+'"]').attr("src", "albumart/asdownloaded/"+aImg.name+'.jpg?version='+firefoxcrapnesshack.toString());
                },

                uploadfail: function() {
                    $('#albumpicture').removeClass('spinner').addClass('nospin');
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
                                $(".icon-play-circled:not(.choose_nowplaying)").removeClass("icon-play-circled").addClass("icon-pause-circled");
                                break;
                            default:
                                $(".icon-pause-circled:not(.choose_nowplaying)").removeClass("icon-pause-circled").addClass("icon-play-circled");
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
                $('[name="'+rawurlencode(trackinfo.location)+'"]:not(.playlistcurrentitem)').addClass('playlistcurrentitem');
                $('[name="'+trackinfo.location+'"]:not(.playlistcurrentitem)').addClass('playlistcurrentitem');
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
                infobar.albumImage.setSource({ image: null });
            } else {
                infobar.albumImage.setKey(info.key);
                infobar.albumImage.setSource({ image: info.bigimage });
            }
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
            var html = '<div class="containerbox menuitem">';
            if (type == infobar.NOTIFY || type == infobar.PERMNOTIFY) {
                html += '<div class="fixed"><i class="icon-info-circled svg-square"></i></div>';
            } else if (type == infobar.ERROR || type == infobar.PERMERROR) {
                html += '<div class="fixed"><i class="icon-attention-1 svg-square"></i></div>';
            } else if (type == infobar.SMARTRADIO) {
                html += '<div class="fixed"><i class="icon-wifi svg-square"></i></div>';
            }
            html += '<div class="expand indent">'+message+'</div></div>';
            $('#notifications').empty().html(html);
            html = null;
            clearTimeout(notifytimer);
            $('#notifications').slideDown('slow');
            if (type !== infobar.PERMERROR && type !== infobar.PERMNOTIFY) {
                notifytimer = setTimeout(this.removenotify, 5000);
            }
        },

        removenotify: function() {
            $('#notifications').slideUp('slow');
        },

        createProgressBar: function() {
            $("#progress").rangechooser({
                ends: ['max'],
                onstop: infobar.seek,
                startmax: 0
            });
        },

        setProgress: function(progress, duration) {
            if (progress < 3) {
                scrobbled = false;
                nowplaying_updated = false;
            }
            if (progress > 4) { updateNowPlaying() };
            var percent = (duration == 0) ? 0 : (progress/duration) * 100;
            if (percent >= prefs.scrobblepercent) {
                scrobble();
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
