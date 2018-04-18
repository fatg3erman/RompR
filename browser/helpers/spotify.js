var spotify = function() {

	var baseURL = 'https://api.spotify.com';
	var queue = new Array();
	var throttle = null;
	var collectedobj = null;
	var getit;

	function objFirst(obj) {
		for (var a in obj) {
			return a;
		}
	}

	return {

		request: function(reqid, url, success, fail, prio) {

			if (prio && queue.length > 1) {
				queue.splice(1, 0, {flag: false, reqid: reqid, url: url, success: success, fail: fail } );
			} else {
				queue.push( {flag: false, reqid: reqid, url: url, success: success, fail: fail } );
			}
			debug.debug("SPOTIFY","New request",url,throttle,queue.length);
			if (throttle == null && queue.length == 1) {
				spotify.getrequest();
			}

		},

		requestSuccess: function(data) {
        	var c = getit.getResponseHeader('Pragma');
        	debug.debug("SPOTIFY","Request success",c,data);
        	req = queue.shift();
        	if (data === null) {
            	debug.warn("SPOTIFY","No data in response",req);
        		data = {error: language.gettext("spotify_error")};
        	}
        	if (req.reqid != '') {
        		data.reqid = req.reqid;
        	}
            if (data.error) {
            	debug.warn("SPOTIFY","Request failed",req,data);
                req.fail(data);
            } else {
            	var root = objFirst(data);
            	if (data[root].next) {
            		debug.log("SPOTIFY","Got a response with a next page!");
            		if (data[root].previous == null) {
            			collectedobj = data;
            		} else {
            			collectedobj[root].items = collectedobj[root].items.concat(data[root].items);
            		}
            		queue.unshift({flag: false, reqid: '', url: data[root].next, success: req.success, fail: req.fail});
            	} else if (data[root].previous) {
        			collectedobj[root].items = collectedobj[root].items.concat(data[root].items);
            		debug.log("SPOTIFY","Returning concatenated multi-page result");
        			req.success(collectedobj);
        		} else if (data.next) {
            		debug.log("SPOTIFY","Got a response with a next page!");
            		if (data.previous == null) {
            			collectedobj = data;
            		} else {
            			collectedobj.items = collectedobj.items.concat(data.items);
            		}
            		queue.unshift({flag: false, reqid: '', url: data.next, success: req.success, fail: req.fail});
            	} else if (data.previous) {
        			collectedobj.items = collectedobj.items.concat(data.items);
            		debug.log("SPOTIFY","Returning concatenated multi-page result");
        			req.success(collectedobj);
            	} else {
                	req.success(data);
                }
            }

        	if (c == "From Cache") {
        		throttle = setTimeout(spotify.getrequest, 100);
        	} else {
        		throttle = setTimeout(spotify.getrequest, 1500);
        	}

		},

		requestFail: function(data) {
        	throttle = setTimeout(spotify.getrequest, 1500);
        	req = queue.shift();
        	debug.warn("SPOTIFY","Request failed",req,data);
        	data = {error: language.gettext("spotify_noinfo")}
        	if (req.reqid != '') {
        		data.reqid = req.reqid;
        	}
        	req.fail(data);
		},

		getrequest: function() {

			var req = queue[0];
			clearTimeout(throttle);

            if (req) {
            	if (req.flag) {
            		debug.warn("SPOTIFY","Request just pulled from queue is already being handled",req,throttle);
            		return;
            	}
				queue[0].flag = true;
				debug.debug("SPOTIFY","Taking next request from queue",req.url);
	            getit = $.ajax({
	                dataType: "json",
	                url: "browser/backends/getspdata.php?uri="+encodeURIComponent(req.url),
	                success: spotify.requestSuccess,
	                error: spotify.requestFail
	            });
	        } else {
            	throttle = null;
	        }
		},

		track: {

			getInfo: function(id, success, fail, prio) {
				var url = baseURL + '/v1/tracks/' + id;
				spotify.request('', url, success, fail, prio);
			}

		},

		album: {

			getInfo: function(id, success, fail, prio) {
				var url = baseURL + '/v1/albums/' + id;
				spotify.request(id, url, success, fail, prio);
			},

			getMultiInfo: function(ids, success, fail, prio) {
				var url = baseURL + '/v1/albums/?ids=' + ids.join();
				spotify.request('', url, success, fail, prio);
			}

		},

		artist: {

			getInfo: function(id, success, fail, prio) {
				var url = baseURL + '/v1/artists/' + id;
				spotify.request('', url, success, fail, prio);
			},

			getRelatedArtists: function(id, success, fail, prio) {
				var url = baseURL + '/v1/artists/' + id + '/related-artists'
				spotify.request('', url, success, fail, prio);
			},

			getTopTracks: function(id, success, fail, prio) {
				var url = baseURL + '/v1/artists/' + id + '/top-tracks'
				spotify.request('', url, success, fail, prio);
			},

			getAlbums: function(id, types, success, fail, prio) {
				var url = baseURL + '/v1/artists/'+id+'/albums?album_type='+types+'&market='+prefs.lastfm_country_code+'&limit=50';
				spotify.request(id, url, success, fail, prio);
			},

			search: function(name, success, fail, prio) {
				var url = baseURL + '/v1/search?q='+name.replace(/&|%|@|:|\+|'|\\|\*|"|\?|\//g,'').replace(/\s+/g,'+')+'&type=artist';
				spotify.request('', url, success, fail, prio);
			}

		},

		recommendations: {

			getGenreSeeds: function(success, fail) {
				var url = baseURL + '/v1/recommendations/available-genre-seeds';
				spotify.request('', url, success, fail, true);
			},

			getRecommendations: function(param, success, fail) {
				var p = new Array();
				for (var i in param) {
					p.push(i+'='+encodeURIComponent(param[i]));
				}
				var paramstring = p.join('&');
				var url = baseURL + '/v1/recommendations?'+paramstring;
				spotify.request('', url, success, fail, false);
			}

		}

	}
}();
