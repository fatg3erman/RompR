var musicbrainz = function() {

	var baseURL = 'http://musicbrainz.org/ws/2/';
	var coverURL = 'http://coverartarchive.org/release/';
	var queue = new Array();
	var throttle = null;

	return {

		request: function(reqid, url, success, fail) {

			queue.push( {flag: false, reqid: reqid, url: url, success: success, fail: fail } );
			debug.debug("MUSICBRAINZ","New request",url);
			if (throttle == null && queue.length == 1) {
				musicbrainz.getrequest();
			}

		},

		getrequest: function() {

			var req = queue[0];
			clearTimeout(throttle);

            if (req) {
            	if (req.flag) {
            		debug.error("MUSICBRAINZ","Request just pulled from queue is already being handled");
            		return;
            	}
				queue[0].flag = true;
				debug.debug("MUSICBRAINZ","Taking next request from queue",req.url);
	            var getit = $.ajax({
	                dataType: "json",
	                url: "browser/backends/getmbdata.php?uri="+encodeURIComponent(req.url),
	                success: function(data) {
	                	var c = getit.getResponseHeader('Pragma');
	                	debug.debug("MUSICBRAINZ","Request success",c,data);
	                	if (c == "From Cache") {
	                		throttle = setTimeout(musicbrainz.getrequest, 100);
	                	} else {
	                		throttle = setTimeout(musicbrainz.getrequest, 1500);
	                	}
	                	req = queue.shift();
	                	if (data === null) {
	                		data = {error: language.gettext("musicbrainz_error")};
	                	}
	                	if (req.reqid != '') {
	                		data.id = req.reqid;
	                	}
		                if (data.error) {
		                    req.fail(data);
		                } else {
		                    req.success(data);
		                }
		            },
	                error: function(data) {
	                	throttle = setTimeout(musicbrainz.getrequest, 1500);
	                	req = queue.shift();
	                	debug.warn("MUSICBRAINZ","Request failed",req,data);
	                	data = {error: language.gettext("musicbrainz_noinfo")}
	                	if (req.reqid != '') {
	                		data.id = req.reqid;
	                	}
	                	req.fail(data);
	                }
	            });
	        } else {
            	throttle = null;
	        }
		},

		artist: {

			getInfo: function(mbid, success, fail) {

				var url = baseURL+'artist/'+mbid+'?inc=aliases+tags+ratings+release-groups+artist-rels+label-rels+url-rels+release-group-rels+annotation&fmt=json';
				musicbrainz.request('', url, success, fail);

			},

			getReleases: function(mbid, reqid, success, fail) {

				var result = { id: reqid };
				result['release-groups'] = new Array();
				(function getAllReleaseGroups() {
					var url = baseURL+'release-group?artist='+mbid+'&limit=100&fmt=json&inc=artist-credits+tags+ratings+url-rels+annotation&offset='+result['release-groups'].length;
					musicbrainz.request(reqid, url, function(data) {
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
				var url = baseURL+'release/'+mbid+'?inc=annotation+tags+ratings+artists+labels+recordings+release-groups+artist-credits+url-rels+release-group-rels+recording-rels+artist-rels&fmt=json';
				musicbrainz.request('', url, success, fail);

			},

			getCoverArt: function(id, success, fail) {
				var url = coverURL + id + "/";
				musicbrainz.request('', url, success, fail);
			},

		},

		releasegroup: {

			getInfo: function(mbid, reqid, success, fail) {
				var url = baseURL+'release-group/'+mbid+'?inc=artists+releases+artist-rels+label-rels+url-rels&fmt=json';
				musicbrainz.request(reqid, url, success, fail);
			}
		},

		track: {

			getInfo: function(mbid, success, fail) {
				var url = baseURL+'recording/'+mbid+'?inc=annotation+tags+ratings+releases+url-rels+work-rels+release-rels+release-group-rels+artist-rels+label-rels+recording-rels&fmt=json';
				var result = {};
				// For a track, although there might be some good stuff in the recording data, what we really want
				// is the associated work, if there is one, because that's where the wiki and discogs links will probably be.
				musicbrainz.request('', url,
					function(data) {
						result.recording = data;
						debug.debug("MUSICBRAINZ","Scanning recording for work data");
						for (var i in data.relations) {
							if (data.relations[i].work) {
								debug.debug("MUSICBRAINZ","Found work data",data.relations[i].work.id);
								url = baseURL+'work/'+data.relations[i].work.id+'?inc=annotation+tags+ratings+url-rels+artist-rels&fmt=json';
								musicbrainz.request('', url,
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