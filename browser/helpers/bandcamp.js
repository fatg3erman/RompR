var bandcamp = function() {

	var self = this;
	var queue = new Array();
	var current_req;
	const THROTTLE_TIME = 1500;

	async function handle_response(req, response) {
		var c = response.headers.get('Pragma');
		var data = await response.json();
		debug.debug("BANDCAMP","Request success",c, data);
		var throttle = (c == "From Cache") ? 50 : THROTTLE_TIME;
		if (data === null) {
			data = {error: format_remote_api_error('bandcamp_error', 'No Data')};
		}

		if (data.error) {
			req.fail(data);
		} else {
			req.success(data);
		}
		return throttle;
	}

	function handle_error(req, err) {
		debug.warn("BANDCAMP","Request failed",err);
		data = {error: format_remote_api_error('bandcamp_error', err)};
		req.fail(data);
		return THROTTLE_TIME;
	}

	async function do_Request() {
		var data, throttle, response;
		while (current_req = queue.shift()) {
			debug.debug("BANDCAMP","New request",current_req);
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

		request: function(data, success, fail) {
			data.module = 'bandcamp';
			queue.push( {data: data, success: success, fail: fail } );
			if (typeof current_req == 'undefined')
				do_Request();
		},

		get_artist: function(artist, success, fail) {
			debug.trace("BANDCAMP", "Trying to find artst", artist);
			var data = {
				method: 'get_artist',
				params: {
					artistname: artist
				}
			}
			bandcamp.request(data, success, fail);
		},

		get_album: function(id, success, fail) {
			debug.trace("BANDCAMP", "Getting album", id);
			var data = {
				method: 'get_album',
				params: {
					id: id
				}
			}
			bandcamp.request(data, success, fail);
		}

	}
}();
