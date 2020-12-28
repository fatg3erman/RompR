function faveFinder(returnall) {

	// faveFinder is used to find tracks which have just been tagged or rated but don't have a URI.
	// These would be tracks from a radio station. It's also used by lastFMImporter.
	var self = this;
	var queue = new Array();
	var throttle = null;
	var priority = [];
	var checkdb = true;
	var exact = false;

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
			im = "getRemoteImage.php?url="+rawurlencode(im);
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
		debug.trace("FAVEFINDER","Nothing found",req);
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
		debug.log("FAVEFINDER","Domains We Will Search",priority);
	}

	this.queueLength = function() {
		return queue.length;
	}

	this.handleResults = function(data) {

		if (queue.length == 0) {
			return false;
		}
		var req = queue[0];
		debug.debug("FAVEFINDER","Raw Results for",req,data);

		var results = new Array();
		var best_matches = new Array();
		var medium_matches = new Array();
		var worst_matches = new Array();
		// Sort the results
		for (let track of data) {
			if (track.uri.isArtistOrAlbum()) continue;
			var r = {...req.data, ...track};
			debug.trace('FAVEFINDER','Found', r);
			if (r.title == null && r.artist == null) continue;
			if (r.albumartist != "Various Artists") {
				if (compare_tracks_with_artist(req.data, r)) {
					// Exactly matching track and artist are preferred...
					best_matches.push(r);
				} else if (compare_tracks(req.data, r)) {
					// .. over matching track title only ...
					medium_matches.push(r);
				} else {
					// .. over non-matching track titles ..
					worst_matches.unshift(r);
				}
			} else {
				// .. and compilation albums ..
				worst_matches.push(r);
			}

		}
		results = results.concat(best_matches, medium_matches, worst_matches);
		debug.debug("FAVEFINDER","Prioritised Results are",results);
		if (results.length == 0) {
			foundNothing(req);
		} else {
			if (returnall) {
				req.callback(results);
			} else {
				var f = false;
				for (let track of results) {
					if (results.length == 1 || compare_tracks_with_artist(req.data, track)) {
						// for (var g in results[i]) {
						// 	req.data[g] = results[i][g];
						// }
						f = true;
						req.callback(track);
						debug.trace('FAVEFINDER', 'Returning single track as requested');
						debug.debug("FAVEFINDER", track);
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
		debug.log("FAVEFINDER","New thing to look for",data.title);
		debug.debug('FAVEFINDER', data);
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
			st.title = [req.data.title];
		}
		if (req.data.artist) {
			st.artist = [req.data.artist];
		}
		if (req.data.album) {
			st.album = [req.data.album];
		}
		debug.debug("FAVEFINDER","Performing search",st,priority);
		player.controller.rawsearch(st, priority, exact, self.handleResults, checkdb);
	}

	this.trackHtml = function(data, breaks) {
		var html = "";
		// html += '<i class="icon-no-response-playbutton clickicon playable collectionicon" name="'+data.uri+'"></i>';
		var u = data.uri;
		if (u.match(/spotify:/)) {
			html += '<i class="icon-spotify-circled collectionicon"></i>';
		} else if (u.match(/soundcloud:/)) {
			html += '<i class="icon-soundcloud-circled collectionicon"></i>';
		} else if (u.match(/youtube:/)) {
			html += '<i class="icon-youtube-circled collectionicon"></i>';
		} else if (u.match(/gmusic:/)) {
			html += '<i class="icon-gmusic-circled collectionicon"></i>';
		} else if (u.match(/^podcast/)) {
			html += '<i class="icon-podcast-circled collectionicon"></i>';
		}
		html += '<b>'+data.title+'</b>'+brk(breaks);
		if (data.artist) {
			html += '<i>by </i>'+data.artist+brk(breaks);
		}
		html += '<i>on </i>'+data.album;
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
