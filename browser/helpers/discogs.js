var discogs = function() {

	var baseURL = 'https://api.discogs.com/';
	var queue = new Array();
	var current_req;
	const THROTTLE_TIME = 1500;

	async function handle_response(req, response) {
		var c = response.headers.get('Pragma');
		var data = await response.json();
		debug.debug("DISCOGS","Request success",c, data);
		var throttle = (c == "From Cache") ? 50 : THROTTLE_TIME;
		if (data === null) {
			data = {error: format_remote_api_error('discogs_error', 'No Data')};
		} else if (!data.error) {
			// info_discogs.js was written to accept jsonp data passed back from $.jsonp
			// However as Discogs now seem to be refusing to respond to those requests
			// we're using a php script to get it instead. So here we bodge the response
			// into the form that info_discogs.js is expecting.
			// It's important we do this because otherwise we overwrite discogs' id field with our own
			data = {data: data};
		}
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
		debug.warn("DISCOGS","Request failed",err);
		data = {error: format_remote_api_error('discogs_error', err)};
		if (req.reqid != '')
			data.id = req.reqid;

		req.fail(data);
		return THROTTLE_TIME;
	}

	async function do_Request() {
		var data, throttle, response;
		while (current_req = queue.shift()) {
			debug.debug("DISCOGS","New request",current_req);
			try {
				response = await fetch(
					'browser/backends/api_handler.php',
					{
						signal: AbortSignal.timeout(90000),
						body: JSON.stringify(current_req.data),
						cache: 'no-store',
						method: 'POST',
						priority: 'low',
					}
				);
				if (!response.ok) {
					throw new Error(response.status+' '+response.statusText);
				}
				throttle = handle_response(current_req, response);
			} catch (err) {
				throttle = handle_error(current_req, err);
			}
			await new Promise(t => setTimeout(t, throttle));
		}
	}

	return {

		request: function(reqid, data, success, fail) {
			data.module = 'discogs';
			queue.push( {reqid: reqid, data: data, success: success, fail: fail } );
			if (typeof current_req == 'undefined')
				do_Request();
		},

		verify_data: function(data, success, fail) {
			var params = {
				method: 'verify_data',
				params: data
			}
			discogs.request('', params, success, fail)
		},

		artist: {

			getInfo: function(reqid, id, success, fail) {
				var data = {
					method: 'artist_getinfo',
					params: {
						id: id
					}
				};
				discogs.request(reqid, data, success, fail);
			},

			getReleases: function(name, page, reqid, success, fail) {
				var data = {
					method: 'artist_getreleases',
					params: {
						id: name,
						page: page
					}
				};
				discogs.request(reqid, data, success, fail);
			}
		},

		album: {

			getInfo: function(reqid, id, success, fail) {
				// NOTE id must be either releases/id or masters/id
				var data = {
					method: 'album_getinfo',
					params: {
						id: id,
					}
				};
				discogs.request(reqid, data, success, fail);
			},

		},

		track: {

			getInfo: function(reqid, id, success, fail) {
				// NOTE id must be either releases/id or masters/id
				// and this IS the same as album.getInfo
				var data = {
					method: 'album_getinfo',
					params: {
						id: id,
					}
				};
				discogs.request(reqid, data, success, fail);
			},

		},

		label: {

			getInfo: function(reqid, id, success, fail) {
				var data = {
					method: 'label_getinfo',
					params: {
						id: id,
					}
				};
				discogs.request(reqid, data, success, fail);
			}
		}

	}

}();
