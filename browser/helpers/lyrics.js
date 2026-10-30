var lyrics = function() {

	var self = this;
	var queue = new Array();
	var current_req;
	const THROTTLE_TIME = 1000;

	async function handle_response(req, response) {
		var c = response.headers.get('Pragma');
		var data = await response.json();
		debug.debug("LYRICS","Request success",c, data);
		var throttle = (c == "From Cache") ? 50 : THROTTLE_TIME;
		if (data === null) {
			data = {error: format_remote_api_error('lyrics_nonefound', 'No Data')};
		}

		if (data.error) {
			req.fail(data);
		} else {
			req.success(data);
		}
		return throttle;
	}

	function handle_error(req, err) {
		debug.warn("LYRICS","Request failed",err);
		data = {error: format_remote_api_error('lyrics_nonefound', err)};
		req.fail(data);
		return THROTTLE_TIME;
	}

	async function do_Request() {
		var data, throttle, response;
		while (current_req = queue.shift()) {
			debug.debug("LYRICS","New request",current_req);
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
			data.module = 'lyrics';
			queue.push( {data: data, success: success, fail: fail } );
			if (typeof current_req == 'undefined')
				do_Request();
		},

		get_lyrics: function(file, artist, song, duration, success, fail) {
			var data = {
				method: 'get_lyrics',
				params: {
					file: file,
					artist_name: artist,
					track_name: song,
					duration: duration
				}
			}
			debug.trace("LYRICS", "Trying to find lyrics", data);
			lyrics.request(data, success, fail);
		}
	}
}();
