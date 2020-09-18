var spotify = function() {

	var baseURL = 'https://api.spotify.com';
	var queue = new Array();
	const DEFAULT_RATE = 100;
	var rate = DEFAULT_RATE;
	var backofftimer;
	var current_req;

	async function do_Request() {
		var data, jqxhr, throttle;
		while (current_req = queue.shift()) {
			debug.debug("SPOTIFY","Taking next request from queue",current_req);
			try {
				data = await (jqxhr = $.ajax({
					type: 'POST',
					url: "browser/backends/getspdata.php",
					dataType: "json",
					data: {
						url: current_req.url,
						cache: current_req.cache
					}
				}));
				throttle = handle_response(current_req, data, jqxhr);
			} catch (err) {
				throttle = handle_error(current_req, err);
			}
			await new Promise(t => setTimeout(t, throttle));
		}
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
		req.success(data);
		return throttle;
	}

	function handle_error(req, err) {
		debug.warn("SPOTIFY","Request failed",req,err);
		if (err.responseJSON) {
			if (err.responseJSON.error == 429) {
				debug.info("SPOTIFY","Too Many Requests. Slowing Request Rate");
				rate += 1000;
				clearTimeout(backofftimer);
				backofftimer = setTimeout(speedBackUp, 90000);
			}
		}
		data = {error: format_remote_api_error('spotify_noinfo', err)}
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
				var url = baseURL + '/v1/search?q='+name.replace(/&|%|@|:|\+|'|\\|\*|"|\?|\//g,'').replace(/\s+/g,'+')+'&type=artist&limit=50';
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
