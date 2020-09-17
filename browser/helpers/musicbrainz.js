var musicbrainz = function() {

	var baseURL = 'http://musicbrainz.org/ws/2/';
	var coverURL = 'http://coverartarchive.org/release/';
	var queue = new Array();
	var current_req = null;
	const THROTTLE_TIME = 1500;

	function handle_response(req, data, jqxhr) {
		var c = jqxhr.getResponseHeader('Pragma');
		debug.debug("MUSICBRAINZ","Request success",c, data, jqxhr);
		throttle = (c == "From Cache") ? 50 : THROTTLE_TIME;
		if (data === null)
			data = {error: format_error('musicbrainz_error', jqxhr)};

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
		data = {error: format_error('musicbrainz_noinfo', err)};
		if (req.reqid != '')
			data.id = req.reqid;

		req.fail(data);
		return THROTTLE_TIME;
	}

	function format_error(msg, jqxhr) {
		debug.debug("MUSICBRAINZ","Formatting error",jqxhr);
		var errormessage = language.gettext(msg);
		if (jqxhr.responseJSON && jqxhr.responseJSON.message)
			errormessage += ' ('+jqxhr.responseJSON.message+')';

		return errormessage;
	}

	async function do_Request() {
		var data, jqxhr, throttle, req;
		while (req = queue.shift()) {
			current_req = req;
			debug.debug("MUSICBRAINZ","New request",req);
			try {
				data = await (jqxhr = $.ajax({
					method: 'POST',
					url: "browser/backends/getmbdata.php",
					data: req.data,
					dataType: "json",
				}));
				throttle = handle_response(req, data, jqxhr);
			} catch (err) {
				throttle = handle_error(req, err);
			}
			await new Promise(t => setTimeout(t, throttle));
		}
		current_req = null;
	}

	return {

		request: function(reqid, data, success, fail) {
			queue.push( {reqid: reqid, data: data, success: success, fail: fail } );
			if (current_req == null)
				do_Request();

		},

		artist: {

			getInfo: function(mbid, success, fail) {
				var data = {
					url: baseURL+'artist/'+mbid,
					inc: 'aliases+tags+ratings+release-groups+artist-rels+label-rels+url-rels+release-group-rels+annotation',
					fmt: 'json'
				};
				musicbrainz.request('', data, success, fail);
			},

			getReleases: function(mbid, reqid, success, fail) {
				var result = { id: reqid };
				result['release-groups'] = new Array();
				(function getAllReleaseGroups() {
					var data = {
						url: baseURL+'release-group',
						artist: mbid,
						limit: 100,
						fmt: 'json',
						inc: 'artist-credits+tags+ratings+url-rels+annotation',
						offset: result['release-groups'].length
					};
					musicbrainz.request(reqid, data, function(data) {
						debug.debug("MUSICBRAINZ","Release group data:",data);
						if (data.error) {
							if (result['release-groups'].length > 0) {
								success(result);
							} else {
								fail(data);
							}
						} else {
							for (var i in data['release-groups']) {
								result['release-groups'].push(data['release-groups'][i]);
							}
							if (result['release-groups'].length == data['release-group-count']) {
								success(result);
							} else {
								getAllReleaseGroups();
							}
						}
					}, fail);
				})();
			}

		},

		album: {

			getInfo: function(mbid, success, fail) {
				var data = {
					url: baseURL+'release/'+mbid,
					inc: 'annotation+tags+ratings+artists+labels+recordings+release-groups+artist-credits+url-rels+release-group-rels+recording-rels+artist-rels',
					fmt: 'json'
				};
				musicbrainz.request('', data, success, fail);

			},

			getCoverArt: function(id, success, fail) {
				var data = {url: coverURL + id + "/" };
				musicbrainz.request('', data, success, fail);
			},

		},

		releasegroup: {

			getInfo: function(mbid, reqid, success, fail) {
				var data = {
					url: baseURL+'release-group/'+mbid,
					inc: 'artists+releases+artist-rels+label-rels+url-rels',
					fmt: 'json'
				};
				musicbrainz.request(reqid, data, success, fail);
			}
		},

		track: {

			getInfo: function(mbid, success, fail) {
				var data = {
					url: baseURL+'recording/'+mbid,
					inc: 'annotation+tags+ratings+releases+url-rels+work-rels+release-rels+release-group-rels+artist-rels+label-rels+recording-rels',
					fmt: 'json'
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
									url: baseURL+'work/'+data.relations[i].work.id,
									inc: 'annotation+tags+ratings+url-rels+artist-rels',
									fmt: 'json'
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
