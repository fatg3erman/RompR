var spotify = function() {

	var queue = new Array();
	const DEFAULT_RATE = 100;
	var rate = DEFAULT_RATE;
	var backofftimer;
	var current_req;
	var collectedobj = null;
	var pages = 0;

	async function do_Request() {
		var data, jqxhr, throttle;
		while (current_req = queue.shift()) {
			debug.debug("SPOTIFY","Taking next request from queue",current_req);
			try {
				data = await (jqxhr = $.ajax({
					method: 'POST',
					url: "browser/backends/api_handler.php",
					data: JSON.stringify(current_req.data),
					dataType: "json",
				}));
				throttle = handle_response(current_req, data, jqxhr);
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

	function handle_response(req, data, jqxhr) {
		var c = jqxhr.getResponseHeader('Pragma');
		var d;
		throttle = (c == "From Cache") ? 50 : rate;
		debug.debug("SPOTIFY","Request success",c,req,data,jqxhr);
		// if (data === null) {
		// 	debug.warn("SPOTIFY","No data in response");
		// 	req.fail({error: language.gettext("spotify_error")});
		// }
		debug.log('SPOTIFY', data);
		var root = objFirst(data);
		try {
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
						reqid: '',
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
					req.success(collectedobj);
				} else {
					queue.unshift({
						reqid: '',
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
				req.success(collectedobj);
			} else {
				req.success(data);
			}
		} catch(err) {
			debug.warn('SPOTIFY', 'Summit went wrong', err);
			req.fail({error: language.gettext("spotify_error")});
		}
		return throttle;
	}

	function handle_error(req, err) {
		debug.warn("SPOTIFY","Request failed",req,err);
		if (err.responseJSON) {
			if (err.responseJSON.error && err.responseJSON.error.status == 429) {
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

			// getGenreSeeds: function(success, fail) {
			// 	var data = {
			// 		method: 'get_genreseeds',
			// 		params: {
			// 			cache: true
			// 		}
			// 	};
			// 	spotify.request('', data, success, fail, true);
			// },

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
