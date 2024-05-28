var bing = function() {

	var queue = new Array();
	var current_req;
	const THROTTLE_TIME = 1000;

	async function handle_response(req, response) {
		var c = response.headers.get('Pragma');
		var data = await response.json();
		debug.debug("BING","Request success",c, data);
		var throttle = (c == "From Cache") ? 50 : THROTTLE_TIME;
		if (data === null) {
			data = {error: format_remote_api_error('albumart_googleproblem', 'No Data')};
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
		debug.warn("BING","Request failed",err);
		data = {error: format_remote_api_error('albumart_googleproblem', err)};
		if (req.reqid != '')
			data.id = req.reqid;

		req.fail(data);
		return THROTTLE_TIME;
	}

	async function do_Request() {
		var data, throttle, response;
		while (current_req = queue.shift()) {
			debug.debug("BING","New request",current_req);
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
			queue.push( {reqid: reqid, data: data, success: success, fail: fail } );
			if (typeof current_req == 'undefined')
				do_Request();
		},

		image: {
			search: function(query, offset, success, fail) {
				var data = {
					module: 'bing',
					method: 'image_search',
					params: {
						offset: offset,
						q: query
					}
				}
				bing.request('', data, success, fail);
			}
		}

	}

}();
