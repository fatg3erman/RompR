var spotify = function() {

	var queue = new Array();
	const DEFAULT_RATE = 100;
	var rate = DEFAULT_RATE;
	var backofftimer;
	var current_req;
	var collectedobj = null;
	var pages = 0;


	async function do_Request() {
		var throttle, response;
		while (current_req = queue.shift()) {
			debug.debug("SPOTIFY","New request",current_req);
			try {
				response = await fetch(
					'browser/backends/api_handler.php',
					{
						signal: AbortSignal.timeout(30000),
						body: JSON.stringify(current_req.data),
						cache: 'no-store',
						method: 'POST',
						priority: 'low',
					}
				);
				if (response.ok) {
					var data = await response.json();
					throttle = handle_response(current_req, data, response);
				} else {
					switch (response.status) {

						case 401:
							debug.error("SPOTIFY", "Bad Token", response);
							break;

						case 403:
							debug.error('SPOTIFY', "Bad OAuth Request", response);
							break;

						case 429:
							debug.info("SPOTIFY","Too Many Requests. Slowing Request Rate", response);
							rate += 1000;
							clearTimeout(backofftimer);
							backofftimer = setTimeout(speedBackUp, 90000);
							break;

					}
					throw new Error(response.status+' '+response.statusText);
				}
			} catch (err) {
				throttle = handle_error(current_req, err);
			}
			await new Promise(t => setTimeout(t, throttle));
		}
	}

	function objFirst(obj) {
		for (var a in obj) {
			return a;
		}
	}

	// Don't make this async or mess with how it mixes req and current_req
	// I don't know why but it makes it sometimes not work when there are multi-page results
	function handle_response(req, data, response) {
		var d;
		var c = response.headers.get('Pragma');
		throttle = (c == "From Cache") ? 50 : rate;
		debug.debug("SPOTIFY","Request success",c);
		debug.debug('SPOTIFY', data);
		if (data.length == 0) {
			req.fail({error: format_remote_api_error("spotify_error", 'No Data')});
			return throttle;
		}
		try {
			var root = objFirst(data);
			if (data[root].next) {
				debug.debug("SPOTIFY","Got a response with a next page!");
				if (data[root].previous == null) {
					collectedobj = data;
					pages = 0;
				} else {
					collectedobj[root].items = collectedobj[root].items.concat(data[root].items);
					pages++;
				}
				if (pages > 10) {
					req.success(collectedobj);
				} else {
					queue.unshift({
						reqid: current_req.reqid,
						data: {
							module: 'spotify',
							method: 'get_url',
							params: {
								url: data[root].next
							}
						},
						success: req.success,
						fail: req.fail
					});
				}
			} else if (data[root].previous) {
				collectedobj[root].items = collectedobj[root].items.concat(data[root].items);
				debug.trace("SPOTIFY","Returning concatenated multi-page result");
				collectedobj.reqid = current_req.reqid;
				req.success(collectedobj);
			} else if (data.next) {
				debug.debug("SPOTIFY","Got a response with a next page!");
				if (data.previous == null) {
					collectedobj = data;
					pages = 0;
				} else {
					collectedobj.items = collectedobj.items.concat(data.items);
					pages++;
				}
				if (pages > 10) {
					collectedobj.reqid = current_req.reqid;
					req.success(collectedobj);
				} else {
					queue.unshift({
						reqid: current_req.reqid,
						data: {
							module: 'spotify',
							method: 'get_url',
							params: {
								url: data.next
							}
						},
						success: req.success,
						fail: req.fail
					});
				}
			} else if (data.previous) {
				collectedobj.items = collectedobj.items.concat(data.items);
				debug.trace("SPOTIFY","Returning concatenated multi-page result");
				collectedobj.reqid = current_req.reqid;
				req.success(collectedobj);
			} else {
				data.reqid = current_req.reqid;
				req.success(data);
			}
		} catch(err) {
			debug.warn('SPOTIFY', 'Summit went wrong', err);
			req.fail({error: format_remote_api_error("spotify_error", err)});
		}
		return throttle;
	}

	function handle_error(req, err) {
		debug.warn("SPOTIFY","Request failed",req,err);
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

		request: function(reqid, data, success, fail, prio) {
			data.module = 'spotify';
			if (prio) {
				queue.unshift( {reqid: reqid, data: data, success: success, fail: fail} );
			} else {
				queue.push( {reqid: reqid, data: data, success: success, fail: fail} );
			}
			if (typeof current_req == 'undefined')
				do_Request();
		},

		track: {

			getInfo: function(id, success, fail, prio) {
				var data = {
					method: 'track_getinfo',
					params: {
						id: id,
						cache: true
					}
				};
				spotify.request('', data, success, fail, prio);
			},

			checkLinking: function(id, success, fail, prio) {
				var data = {
					method: 'track_checklinking',
					params: {
						id: id,
						cache: false
					}
				};
				spotify.request('', data, success, fail, prio);
			},

			search: function(name, success, fail, prio) {
				var data = {
					method: 'search',
					params: {
						q: name,
						type: 'track',
						limit: 50,
						cache: true
					}
				};
				spotify.request('', data, success, fail, prio);
			}

		},

		album: {

			getInfo: function(id, success, fail, prio) {
				var data = {
					method: 'album_getinfo',
					params: {
						id: id,
						cache: true
					}
				};
				let reqid = (typeof(id) == 'string') ? id : '';
				spotify.request(reqid, data, success, fail, prio);
			}

		},

		artist: {

			getInfo: function(id, success, fail, prio) {
				var data = {
					method: 'artist_getinfo',
					params: {
						id: id,
						cache: true
					}
				};
				spotify.request('', data, success, fail, prio);
			},

			getRelatedArtists: function(id, success, fail, prio) {
				var data = {
					method: 'artist_getrelated',
					params: {
						id: id,
						cache: true
					}
				};
				spotify.request('', data, success, fail, prio);
			},

			getTopTracks: function(id, success, fail, prio) {
				var data = {
					method: 'artist_toptracks',
					params: {
						id: id,
						cache: true
					}
				};
				spotify.request('', data, success, fail, prio);
			},

			getAlbums: function(id, types, success, fail, prio) {
				var data = {
					method: 'artist_getalbums',
					params: {
						id: id,
						album_type: types,
						limit: 50,
						cache: true
					}
				};
				spotify.request(id, data, success, fail, prio);
			},

			search: function(name, success, fail, prio) {
				var data = {
					method: 'search',
					params: {
						q: name,
						type: 'artist',
						limit: 50,
						cache: true
					}
				};
				spotify.request('', data, success, fail, prio);
			},

			find_possibilities(name, success, fail, prio) {
				var data = {
					method: 'find_possibilities',
					params: {
						name: name,
						cache: true
					}
				};
				spotify.request('', data, success, fail, prio);
			}

		},

		recommendations: {

			getGenreSeeds: function(success, fail) {
				var data = {
					method: 'get_genreseeds',
					params: {
						cache: true
					}
				};
				spotify.request('', data, success, fail, true);
			},

			getRecommendations: function(param, success, fail) {
				var data = {
					method: 'get_recommendations',
					params: {
						param: param,
						cache: true
					}
				};
				spotify.request('', data, success, fail, true);
			}
		}
	}
}();
