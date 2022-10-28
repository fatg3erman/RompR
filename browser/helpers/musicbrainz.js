var musicbrainz = function() {

	var queue = new Array();
	var current_req;
	const THROTTLE_TIME = 1500;

	async function do_Request() {
		var data, jqxhr, throttle;
		while (current_req = queue.shift()) {
			debug.debug("MUSICBRAINZ","New request",current_req);
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

	function handle_response(req, data, jqxhr) {
		var c = jqxhr.getResponseHeader('Pragma');
		debug.debug("MUSICBRAINZ","Request success",c, data, jqxhr);
		var throttle = (c == "From Cache") ? 50 : THROTTLE_TIME;
		if (data === null)
			data = {error: format_remote_api_error('musicbrainz_error', jqxhr)};

		if (req.reqid != '')
			data.id = req.reqid;

		if (data.error) {
			req.fail(data);
		} else {
			req.success(data);
		}
		return throttle;
	}

	function handle_error(req, err) {
		debug.warn("MUSICBRAINZ","Request failed",err);
		data = {error: format_remote_api_error('musicbrainz_noinfo', err)};
		if (req.reqid != '')
			data.id = req.reqid;

		req.fail(data);
		return THROTTLE_TIME;
	}

	return {

		request: function(reqid, data, success, fail) {
			data.module = 'musicbrainz';
			queue.push( {reqid: reqid, data: data, success: success, fail: fail } );
			if (typeof current_req == 'undefined')
				do_Request();

		},

		verify_data: function(data, success, fail) {
			var params = {
				method: 'verify_data',
				params: data
			}
			musicbrainz.request('', params, success, fail)
		},

		artist: {

			getInfo: function(mbid, success, fail) {
				var data = {
					method: 'artist_getinfo',
					params: {
						mbid: mbid
					}
				};
				musicbrainz.request('', data, success, fail);
			},

			getReleases: function(mbid, reqid, success, fail) {
				var result = { id: reqid };
				var data = {
					method: 'artist_releases',
					params: {
						mbid: mbid,
					}
				};
				musicbrainz.request(reqid, data, function(data) {
					if (data.error) {
						fail(data);
					} else {
						result['release-groups'] = data['release-groups'];
						success(result);
					}
				}, fail);
			},

			search: function(name, album, track, success, fail) {
				var data = {
					method: 'artist_search',
					params: {
						query: name,
						album: album
					}
				};
				musicbrainz.request('', data, success, fail);
			}

		},

		album: {

			getInfo: function(mbid, success, fail) {
				var data = {
					method: 'album_getinfo',
					params: {
						mbid: mbid
					}
				};
				musicbrainz.request('', data, success, fail);
			},

			getCoverArt: function(id, success, fail) {
				var data = {
					method: 'album_getcover',
					params: {
						mbid: id
					}
				};
				musicbrainz.request('', data, success, fail);
			},

		},

		releasegroup: {

			getInfo: function(mbid, reqid, success, fail) {
				var data = {
					method: 'releasegroup_getinfo',
					params: {
						mbid: mbid
					}
				};
				musicbrainz.request(reqid, data, success, fail);
			}
		},

		track: {

			getInfo: function(mbid, success, fail) {
				var data = {
					method: 'track_getinfo',
					params: {
						mbid: mbid
					}
				};
				var result = {};
				// For a track, although there might be some good stuff in the recording data, what we really want
				// is the associated work, if there is one, because that's where the wiki and discogs links will probably be.
				musicbrainz.request('', data,
					function(data) {
						result.recording = data;
						debug.debug("MUSICBRAINZ","Scanning recording for work data");
						for (var i in data.relations) {
							if (data.relations[i].work) {
								debug.debug("MUSICBRAINZ","Found work data",data.relations[i].work.id);
								var newdata = {
									method: 'work_getinfo',
									params: {
										mbid: data.relations[i].work.id
									}
								};
								musicbrainz.request('', newdata,
									function(workdata) {
										debug.debug("MUSICBRAINZ","Got work data",workdata);
										result.work = workdata;
										success(result);
									},
									function(workdata) {
										debug.debug("MUSICBRAINZ","Got NO work data",workdata);
										success(result);
									});
								return;
							}
						}
						success(result);
					},
				fail);
			}
		}

	}

}();
