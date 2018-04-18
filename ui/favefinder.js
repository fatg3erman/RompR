function faveFinder(returnall) {

    // faveFinder is used to find tracks which have just been tagged or rated but don't have a URI.
    // These would be tracks from a radio station. It's also used by lastFMImporter.
    var self = this;
    var queue = new Array();
    var throttle = null;
    var withalbum = false;
    var priority = [];

    // Prioritize - local, beetslocal, beets, gmusic, spotify - in that order
    // Everything else can take its chance. These are the default priorities, but they can be changed
    // from the lastfm importer gui.
    // There's currently no way to change these for tracks that are rated from radio stations
    // which means that these are the only domains that will be searched.
    if (prefs.player_backend == 'mopidy') {
        priority = ["spotify", "gmusic", "beets", "beetslocal", "local"];
    }

    function brk(b) {
        if (b) {
            return '<br />';
        } else {
            return ' ';
        }
    }

    function getImageUrl(list) {
        var im = null;
        for (var i in list) {
            if (list[i] != "") {
                im = list[i];
                break;
            }
        }
        if (im && im.substr(0,4) == "http") {
            im = "getRemoteImage.php?url="+im;
        }
        return im;
    }

    function compare_tracks(lookingfor, found) {
        if (lookingfor.title.removePunctuation().toLowerCase() !=
                found.title.removePunctuation().toLowerCase()) {
            return false;
        }
        return true;
    }

    function foundNothing(req) {
        debug.log("FAVEFINDER","Nothing found",req);
        if (returnall) {
            req.callback([req.data]);
        } else {
            req.callback(req.data);
        }
    }

    this.getPriorities = function() {
        return priority.reverse();
    }

    this.setPriorities = function(p) {
        priority = p;
        debug.log("FAVEFINDER","Priorities Set To (reverse order)",priority);
    }

    this.queueLength = function() {
        return queue.length;
    }

    this.handleResults = function(data) {

        if (queue.length == 0) {
            return false;
        }
        var req = queue[0];

        debug.trace("FAVEFINDER","Raw Results for",req,data);

        $.each(priority,
            function(j,v) {
                var spot = null;
                for (var i in data) {
                    var match = new RegExp('^'+v+':');
                    if (match.test(data[i].uri)) {
                        spot = i;
                        break;
                    }
                }
                if (spot !== null) {
                    data.unshift(data.splice(spot, 1)[0]);
                }
            }
        );

        debug.log("FAVEFINDER","Sorted Search Results are",data);

        var results = new Array();
        var results_without_album = new Array();
        // Sort the results
        for (var i in data) {
            if (data[i].tracks) {
                for (var k = 0; k < data[i].tracks.length; k++) {
                    debug.log("FAVEFINDER","Found Track",data[i].tracks[k],req);
                    var r = cloneObject(req);
                    for (var g in data[i].tracks[k]) {
                        r.data[g] = data[i].tracks[k][g];
                    }
                    // Prioritise results with a matching album if the track name matches
                    // and it's not a compilation
                    if (req.data.album &&
                        r.data.album &&
                        r.data.album.toLowerCase() == req.data.album.toLowerCase() &&
                        r.data.albumartist != "Various Artists" &&
                        compare_tracks(r.data, req.data)) {
                        results.push(r.data);
                    } else {
                        if (r.data.albumartist != "Various Artists") {
                            if (compare_tracks(r.data, req.data)) {
                                // Exactly matching track titles are preferred...
                                results_without_album.unshift(r.data);
                            } else {
                                // .. over non-matching track titles ..
                                results_without_album.push(r.data);
                            }
                        } else {
                            // .. and compilation albums ..
                            results_without_album.push(r.data);
                        }
                    }
                }
            }
        }
        results = results.concat(results_without_album);
        debug.log("FAVEFINDER","Prioritised Results are",results);
        if (results.length == 0) {
            if (withalbum) {
                debug.log("FAVEFINDER", "Trying without album name");
                withalbum = false;
                queue[0].image = null;
                self.searchForTrack();
                return;
            } else {
                foundNothing(req);
            }
        } else {
            if (returnall) {
                req.callback(results);
            } else {
                var f = false;
                for (var i in results) {
                    if (results.length == 1 || compare_tracks(req.data, results[i])) {
                        for (var g in results[i]) {
                            req.data[g] = results[i][g];
                        }
                        f = true;
                        req.callback(req.data);
                        break;
                    }
                }
                if (!f) {
                    foundNothing(req);
                }
            }
        }

        throttle = setTimeout(self.next, 4000);
        queue.shift();
    }

    this.findThisOne = function(data, callback, withalbum) {
        debug.log("FAVEFINDER","New thing to look for",data);
        queue.push({data: data, callback: callback, withalbum: withalbum});
        if (throttle == null && queue.length == 1) {
            self.next();
        }
    }

    this.next = function() {
        var req = queue[0];
        clearTimeout(throttle);
        if (req) {
            withalbum = req.withalbum;
            self.searchForTrack();
        } else {
            throttle = null;
        }
    }

    this.stop = function() {
        clearTimeout(throttle)
        queue = new Array();
    }

    this.searchForTrack = function() {
        var req = queue[0];
        var st = {};
        if (req.data.title) {
            st.track_name = [req.data.title];
        }
        if (req.data.artist) {
            st.artist = [req.data.artist];
        }
        if (withalbum) {
            if (req.data.album) {
                st.album = [req.data.album];
            } else {
                withalbum = false;
            }
        }
        debug.log("FAVEFINDER","Performing search",st,priority);
        player.controller.rawsearch(st, priority, false, self.handleResults, true);
    }

    this.trackHtml = function(data, breaks) {
        var html = "";
        var u = data.uri;
        if (u.match(/spotify:/)) {
            html += '<i class="icon-spotify-circled smallicon"></i>';
        } else if (u.match(/soundcloud:/)) {
            html += '<i class="icon-soundcloud-circled smallicon"></i>';
        } else if (u.match(/youtube:/)) {
            html += '<i class="icon-youtube-circled smallicon"></i>';
        } else if (u.match(/gmusic:/)) {
            html += '<i class="icon-gmusic-circled smallicon"></i>';
        }
        html += '<b>'+data.title+'</b>'+brk(breaks)+'<i>by </i>';
        html += data.artist+brk(breaks)+'<i>on </i>';
        html += data.album;
        var arse = data.uri;
        if (arse.indexOf(":") > 0) {
            html += '  <i>(' + arse.substr(0, arse.indexOf(":")) + ')</i>';
        }
        return html;
    }


}


