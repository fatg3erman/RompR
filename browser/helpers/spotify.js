var spotify = function() {

	var baseURL = 'https://api.spotify.com';
	var queue = new Array();
	var collectedobj = null;
	const DEFAULT_RATE = 100;
	var rate = DEFAULT_RATE;
	var backofftimer;
	var current_req = null;

	function objFirst(obj) {
		for (var a in obj) {
			return a;
		}
	}

	async function do_Request() {
		var data, jqxhr, throttle, req;
		while (req = queue.shift()) {
			current_req = req;
			debug.debug("SPOTIFY","Taking next request from queue",req);
			try {
				data = await (jqxhr = $.ajax({
					type: 'POST',
					url: "browser/backends/getspdata.php",
					dataType: "json",
					data: {
						url: req.url,
						cache: req.cache
					}
				}));
				throttle = handle_response(req, data, jqxhr);
			} catch (err) {
				throttle = handle_error(req, err);
			}
			await new Promise(t => setTimeout(t, throttle));
		}
		current_req = null;
	}

	function handle_response(req, data, jqxhr) {
		var c = jqxhr.getResponseHeader('Pragma');
		throttle = (c == "From Cache") ? 50 : rate;
		debug.debug("SPOTIFY","Request success",c,req,data,jqxhr);
		if (data === null) {
			debug.warn("SPOTIFY","No data in response");
			data = {error: language.gettext("spotify_error")};
		}
		if (req.reqid != '') {
			data.reqid = req.reqid;
		}
		var root = objFirst(data);
		// Bit messy this.
		// Might prefer something like if (a = (data[root].next || data.next))
		// but I can't remember whay this is the way it is so I'm not currently inclined to fuck with it
		if (data[root].next) {
			debug.debug("SPOTIFY","Got a response with a next page!");
			if (data[root].previous == null) {
				collectedobj = data;
			} else {
				collectedobj[root].items = collectedobj[root].items.concat(data[root].items);
			}
			queue.unshift({flag: false, reqid: '', url: data[root].next, success: req.success, fail: req.fail});
		} else if (data[root].previous) {
			collectedobj[root].items = collectedobj[root].items.concat(data[root].items);
			debug.trace("SPOTIFY","Returning concatenated multi-page result");
			req.success(collectedobj);
		} else if (data.next) {
			debug.debug("SPOTIFY","Got a response with a next page!");
			if (data.previous == null) {
				collectedobj = data;
			} else {
				collectedobj.items = collectedobj.items.concat(data.items);
			}
			queue.unshift({flag: false, reqid: '', url: data.next, success: req.success, fail: req.fail});
		} else if (data.previous) {
			collectedobj.items = collectedobj.items.concat(data.items);
			debug.trace("SPOTIFY","Returning concatenated multi-page result");
			req.success(collectedobj);
		} else {
			req.success(data);
		}
		return throttle;
	}

	function handle_error(req, err) {
		debug.warn("SPOTIFY","Request failed",req,err);
		if (err.responseJSON && err.responseJSON.error == 429) {
			debug.info("SPOTIFY","Too Many Requests. Slowing Request Rate");
			rate += 1000;
			clearTimeout(backofftimer);
			backofftimer = setTimeout(speedBackUp, 90000);
		}
		data = {error: language.gettext("spotify_noinfo") + ' ('+xhr.responseJSON.message+')'}
		if (req.reqid != '') {
			data.reqid = req.reqid;
		}
		req.fail(data);
		return rate;
	}

	function speedBackUp() {
		rate = DEFAULT_RATE;
	}

	return {

		request: function(reqid, url, success, fail, prio, cache) {
			if (prio) {
				queue.unshift( {reqid: reqid, url: url, success: success, fail: fail, cache: cache } );
			} else {
				queue.push( {reqid: reqid, url: url, success: success, fail: fail, cache: cache } );
			}
			if (current_req == null)
				do_Request();
		},

		track: {

			getInfo: function(id, success, fail, prio) {
				var url = baseURL + '/v1/tracks/' + id;
				spotify.request('', url, success, fail, prio, true);
			},

			checkLinking: function(id, success, fail, prio) {
				var url = baseURL + '/v1/tracks/' + id + '?market='+prefs.lastfm_country_code;
				spotify.request('', url, success, fail, prio, false);
			}

		},

		tracks: {

			checkLinking: function(ids, success, fail, prio) {
				var url = baseURL + '/v1/tracks?ids='+ids.join(',')+'&market='+prefs.lastfm_country_code;
				spotify.request('', url, success, fail, prio, false);
			}

		},

		album: {

			getInfo: function(id, success, fail, prio) {
				var url = baseURL + '/v1/albums/' + id;
				spotify.request(id, url, success, fail, prio, true);
			},

			getMultiInfo: function(ids, success, fail, prio) {
				var url = baseURL + '/v1/albums/?ids=' + ids.join(',');
				spotify.request('', url, success, fail, prio, true);
			}

		},

		artist: {

			getInfo: function(id, success, fail, prio) {
				var url = baseURL + '/v1/artists/' + id;
				spotify.request('', url, success, fail, prio, true);
			},

			getRelatedArtists: function(id, success, fail, prio) {
				var url = baseURL + '/v1/artists/' + id + '/related-artists'
				spotify.request('', url, success, fail, prio, true);
			},

			getTopTracks: function(id, success, fail, prio) {
				var url = baseURL + '/v1/artists/' + id + '/top-tracks'
				spotify.request('', url, success, fail, prio, true);
			},

			getAlbums: function(id, types, success, fail, prio) {
				var url = baseURL + '/v1/artists/'+id+'/albums?album_type='+types+'&limit=50';
				if (prefs.lastfm_country_code) {
					url += '&market='+prefs.lastfm_country_code;
				}
				spotify.request(id, url, success, fail, prio, true);
			},

			search: function(name, success, fail, prio) {
				var url = baseURL + '/v1/search?q='+name.replace(/&|%|@|:|\+|'|\\|\*|"|\?|\//g,'').replace(/\s+/g,'+')+'&type=artist';
				spotify.request('', url, success, fail, prio, true);
			}

		},

		recommendations: {

			getGenreSeeds: function(success, fail) {
				var url = baseURL + '/v1/recommendations/available-genre-seeds';
				spotify.request('', url, success, fail, true, true);
			},

			getRecommendations: function(param, success, fail) {
				var p = new Array();
				if (prefs.lastfm_country_code) {
					param.market = prefs.lastfm_country_code;
				}
				for (var i in param) {
					p.push(i+'='+encodeURIComponent(param[i]));
				}
				var paramstring = p.join('&');
				var url = baseURL + '/v1/recommendations?'+paramstring;
				spotify.request('', url, success, fail, false, false);
			}

		}

	}
}();
