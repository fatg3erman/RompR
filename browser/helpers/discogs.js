var discogs = function() {

	var baseURL = 'https://api.discogs.com/';
	var queue = new Array();
	var current_req;
	const THROTTLE_TIME = 1500;

	function handle_response(req, data, jqxhr) {
		var c = jqxhr.getResponseHeader('Pragma');
		debug.debug("DISCOGS","Request success",c, data, jqxhr);
		var throttle = (c == "From Cache") ? 50 : THROTTLE_TIME;
		if (data === null) {
			data = {error: format_remote_api_error('discogs_error', jqxhr)};
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
		var data, jqxhr, throttle;
		while (current_req = queue.shift()) {
			debug.debug("DISCOGS","New request",current_req);
			try {
				data = await (jqxhr = $.ajax({
					method: 'POST',
					url: "browser/backends/getdidata.php",
					data: current_req.data,
					dataType: "json",
				}));
				throttle = handle_response(current_req, data, jqxhr);
			} catch (err) {
				throttle = handle_error(current_req, err);
			}
			await new Promise(t => setTimeout(t, throttle));
		}
	}

	return {

		request: function(reqid, data, success, fail) {
			queue.push( {reqid: reqid, data: data, success: success, fail: fail } );
			if (typeof current_req == 'undefined')
				do_Request();
		},

		artist: {

			search: function(name, success, fail) {
				var data = {
					url: baseURL+'database/search',
					type: 'artist',
					q: name
				}
				discogs.request('', data, success, fail);
			},

			getInfo: function(reqid, id, success, fail) {
				var data = {url: baseURL+'artists/'+id};
				discogs.request(reqid, data, success, fail);
			},

			getReleases: function(name, page, reqid, success, fail) {
				var data = {
					url: baseURL+'artists/'+name+'/releases',
					per_page: 25,
					page: page
				};
				discogs.request(reqid, data, success, fail);
			}
		},

		album: {

			getInfo: function(reqid, id, success, fail) {
				// NOTE id must be either releases/id or masters/id
				var data = {url: baseURL+id};
				discogs.request(reqid, data, success, fail);
			},

			search: function(artist, album, success, fail) {
				var data = {
					url: baseURL+'database/search',
					type: 'release',
					artist: artist,
					release_title: album
				};
				discogs.request('', data, success, fail);
			}

		},

		track: {

			getInfo: function(reqid, id, success, fail) {
				// NOTE id must be either releases/id or masters/id
				var data = {url: baseURL+id};
				discogs.request(reqid, data, success, fail);
			},

			search: function(artist, track, success, fail) {
				var data = {
					url: baseURL+'database/search',
					type: 'release',
					artist: artist,
					track: track
				};
				discogs.request('', data, success, fail);
			}

		},

		label: {

			getInfo: function(reqid, id, success, fail) {
				// NOTE id must be either releases/id or masters/id
				var data = {url: baseURL+'labels/'+id};
				discogs.request(reqid, data, success, fail);
			}
		}

	}

}();
