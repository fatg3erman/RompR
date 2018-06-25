function coverScraper(size, useLocalStorage, sendUpdates, enabled) {

    var self = this;
    var timer_running = false;
    var formObjects = [];
    var numAlbums = 0;
    var albums_without_cover = 0;
    var imgobj = null;
    var infotext = $('#infotext');
    var statusobj = $('#status');
    var imgparams = null;
    var covertimer = null;
    var scrolling = false;
    var ignorelocal = false;

    // I need to try and limit the number of lookups per second I do to last.fm
    // Otherwise they will set the lions on me - hence the use of setTimeout

    // Pass the img name to this function
    this.GetNewAlbumArt = function(params) {
        debug.log("COVERSCRAPER","getNewAlbumArt",params);
        if (enabled && params.imgkey !== undefined) {
            formObjects.push(params);
            numAlbums = (formObjects.length)-1;
            if (timer_running == false) {
                doNextImage(1);
            }
        }
    }

    this.toggle = function(o) {
        enabled = o;
    }

    this.reset = function(awc) {
        numAlbums = 0;
        if (awc > -1) {
            albums_without_cover = awc;
        }
        formObjects = [];
        timer_running = false;
        self.updateInfo(0);
        if (typeof aADownloadFinished == 'function') {
            aADownloadFinished();
        }
    }

    this.updateInfo = function(n) {
        if (sendUpdates) {
            albums_without_cover = albums_without_cover - n;
            infotext.html(albums_without_cover+" "+language.gettext("albumart_nocovercount"));
        }
    }

    this.toggleScrolling = function(s) {
        scrolling = s;
    }

    this.toggleLocal = function(s) {
        ignorelocal = s;
    }

    // Is there something else I could be doing?

    function doNextImage(time) {
        clearTimeout(covertimer);
        if (formObjects.length > 0) {
            debug.log("COVERSCRAPER","Next Image, delay time is",time);
            timer_running = true;
            covertimer = setTimeout(processForm, time);
        } else {
            $(statusobj).empty();
            timer_running = false;
            if (typeof aADownloadFinished == 'function') {
                aADownloadFinished();
            }
        }
    }

    function processForm() {

        if (formObjects.length > 0) {
            imgparams = formObjects.shift();
            if (imgparams.imgkey === null || $('[name="'+imgparams.imgkey+'"]').length == 0) {
                doNextImage(1);
                return 0;
            }
        } else {
            return 0;
        }

        debug.log("COVERSCRAPER","Getting Cover for", imgparams.imgkey);
        var stream = "";
        $.each($('[name="'+imgparams.imgkey+'"]'), function() {
            if (stream == "") {
                stream = $(this).attr('romprstream') || "";
            }
        });
        imgparams.stream = stream;
        debug.log("COVERSCRAPER","Stream is", stream);

        if (sendUpdates) {
            var x = $('img[name="'+imgparams.imgkey+'"]').prev('input').val();
            statusobj.empty().html(language.gettext("albumart_getting")+" "+decodeURIComponent(x));
            var percent = ((numAlbums - formObjects.length)/numAlbums)*100;
            progress.rangechooser('setProgress', percent.toFixed(2));
            if (scrolling) {
                $('#coverslist').mCustomScrollbar("scrollTo",$('img[name="'+imgparams.imgkey+'"]').parent().parent().parent());
            }
         }

        animateWaiting();

        // Munge params here as we can't pass the 'callback' (cb) parameter to $.post
        $.post("getalbumcover.php",
            {imgkey : imgparams.imgkey,
                artist: imgparams.artist,
                album: imgparams.album,
                mbid: imgparams.mbid,
                dir: imgparams.dir,
                albumuri: imgparams.albumuri,
                stream: imgparams.stream,
                ignorelocal: ignorelocal}
        )
        .done( gotImage )
        .fail( revertCover );

    }

    function animateWaiting() {
        $('img.notexist[name="'+imgparams.imgkey+'"]').removeClass('nospin').addClass('spinner');
    }

    function stopAnimation() {
        $('img[name="'+imgparams.imgkey+'"]').removeClass('spinner').addClass('nospin');
    }

    // Hello

    this.archiveImage = function(imgkey, url) {
        $.post("getalbumcover.php", {key: imgkey, src: url})
        .done( )
        .fail( );
    }

    function gotImage(data) {
        debug.log("COVERSCRAPER","Retrieved Image", data);
        finaliseImage(data);
   }

   function finaliseImage(data) {
        debug.log("COVERSCRAPER","Source is",data.url);
        if (data.url == "" || data.url === null) {
            revertCover(data.delaytime);
        } else {
            angle = 0;
            stopAnimation();
            $.each($('img[name="'+imgparams.imgkey+'"]'), function() {
                // Use large images for playlist and nowplaying
                if ($(this).hasClass("clickrollup") || $(this).attr("id") == "albumpicture") {
                    $(this).attr("src", data.origimage);
                } else if ($(this).hasClass('jalopy')) {
                    $(this).attr("src", data.origimage.replace(/albumart\/asdownloaded/, 'albumart/medium'));
                } else {
                    $(this).attr("src", data.url);
                }
                $(this).removeClass("notexist");
                $(this).removeClass("notfound");
            });
            self.updateInfo(1);
            if (useLocalStorage) {
                sendLocalStorageEvent(imgparams.imgkey, data);
            }
            if (typeof (imgparams.cb) == 'function') {
                debug.log("COVERSCRAPER","calling back for",imgparams.imgkey,data);
                imgparams.cb(data);
            }
            for (var j in formObjects) {
                if (formObjects[j].imgkey == imgparams.imgkey && typeof (formObjects[j].cb) == 'function') {
                    debug.log("COVERSCRAPER","Also calling back for",formObjects[j].imgkey);
                    formObjects[j].cb(data);
                    formObjects[j].imgkey = null;
                }
            }
            doNextImage(data.delaytime);
        }
    }

    function revertCover(delaytime) {
        if (!delaytime) {
            delaytime = 800;
        }
        stopAnimation();
        debug.log("COVERSCRAPER","No Cover Found. Reverting to the blank icon");
        $.each($('img.notexist[name="'+imgparams.imgkey+'"]'), function() {
            // Remove this class to prevent it being searched again
            $(this).removeClass("notexist");
            $(this).removeClass("notfound").addClass("notfound");
        });
        if (useLocalStorage) {
            sendLocalStorageEvent("!"+imgparams.imgkey);
        }
        doNextImage(delaytime);
    }

    this.clearCallbacks = function() {
        for (var j in formObjects) {
            formObjects[j].cb = null;
        }
    }

}

function sendLocalStorageEvent(key, data) {
    var firefoxcrapnesshack = Math.floor(Date.now());
    if (data && data.origimage) {
        localStorage.setItem("albumimg_"+key, data.origimage+'?version='+firefoxcrapnesshack.toString());
    } else if (data && data.url) {
        localStorage.setItem("albumimg_"+key, data.url+'?version='+firefoxcrapnesshack.toString());
    }
    debug.log("COVERSCRAPER","Sending local storage event",key);
    // Event only fires when the key value actually CHANGES
    localStorage.setItem("key", "Blerugh");
    localStorage.setItem("key", key);
}
