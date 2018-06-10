function faveFinder(returnall) {

    // faveFinder is used to find tracks which have just been tagged or rated but don't have a URI.
    // These would be tracks from a radio station. It's also used by lastFMImporter.
    var self = this;
    var queue = new Array();
    var throttle = null;
    var priority = [];
    var checkdb = true;
    var exact = false;

    // Prioritize - local, beetslocal, beets, spotify, gmusic - in that order
    // There's currently no way to change these for tracks that are rated from radio stations
    // which means that these are the only domains that will be searched.
    if (prefs.player_backend == 'mopidy') {
        priority = ["gmusic", "spotify", "beets", "beetslocal", "local"];
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
        if (found.title == null) {
            return false;
        } else if (lookingfor.title == null || lookingfor.title.removePunctuation().toLowerCase() == found.title.removePunctuation().toLowerCase()) {
            return true;
        }
        return false;
    }

    function compare_tracks_with_artist(lookingfor, found) {
        if (lookingfor.title !== null && lookingfor.artist !== null) {
            if (lookingfor.title.removePunctuation().toLowerCase() == found.title.removePunctuation().toLowerCase() &&
                lookingfor.artist.removePunctuation().toLowerCase() == found.artist.removePunctuation().toLowerCase()) {
                return true;
            }
        } else if (lookingfor.title === null) {
            if (lookingfor.artist.removePunctuation().toLowerCase() == found.artist.removePunctuation().toLowerCase()) {
                return true;
            }
        } else if (lookingfor.artist === null) {
            if (lookingfor.title.removePunctuation().toLowerCase() == found.title.removePunctuation().toLowerCase()) {
                return true;
            }
        }
        return false;
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
                    if (data[i].tracks[k].uri.isArtistOrAlbum()) {
                        debug.trace("FAVEFINDER", "Ignoring non-track ",data[i].tracks[k].uri);
                    } else {
                        debug.debug("FAVEFINDER","Found Track",data[i].tracks[k],req);
                        var r = cloneObject(req);
                        for (var g in data[i].tracks[k]) {
                            r.data[g] = data[i].tracks[k][g];
                        }
                        // Prioritise results with a matching album if the track name matches
                        // and it's not a compilation
                        // NOTE. There is nowhere anywhere in the code where we pass an album into here
                        // but at some point there was - it was for the old Last.FM importer - so I've left this
                        // in in case it becomes useful
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
        }
        results = results.concat(results_without_album);
        debug.debug("FAVEFINDER","Prioritised Results are",results);
        if (results.length == 0) {
            foundNothing(req);
        } else {
            if (returnall) {
                req.callback(results);
            } else {
                var f = false;
                for (var i in results) {
                    if (results.length == 1 || compare_tracks_with_artist(req.data, results[i])) {
                        for (var g in results[i]) {
                            req.data[g] = results[i][g];
                        }
                        f = true;
                        req.callback(req.data);
                        debug.log("FAVEFINDER","Single track asked for, returning",req.data);
                        break;
                    }
                }
                if (!f) {
                    foundNothing(req);
                }
            }
        }

        throttle = setTimeout(self.next, 1000);
        queue.shift();
    }

    this.findThisOne = function(data, callback) {
        debug.log("FAVEFINDER","New thing to look for",data);
        queue.push({data: data, callback: callback});
        if (throttle == null && queue.length == 1) {
            self.next();
        }
    }

    this.next = function() {
        var req = queue[0];
        clearTimeout(throttle);
        if (req) {
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
        if (req.data.album) {
            st.album = [req.data.album];
        }
        debug.log("FAVEFINDER","Performing search",st,priority);
        player.controller.rawsearch(st, priority, exact, self.handleResults, checkdb);
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

    this.setCheckDb = function(d) {
        checkdb = d;
    }
    
    this.setExact = function(e) {
        exact = e;
    }

}
