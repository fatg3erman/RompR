var discogs = function() {

	var baseURL = 'http://api.discogs.com/';
	var queue = new Array();
	var throttle = null;

	return {

		request: function(reqid, data, success, fail) {

			queue.push( {flag: false, reqid: reqid, data: data, success: success, fail: fail } );
			debug.debug("DISCOGS","New request",data.url,"throttle is",throttle,"length is",queue.length);
			if (throttle == null && queue.length == 1) {
				discogs.getrequest();
			}

		},

		getrequest: function() {

			var req = queue[0];
			clearTimeout(throttle);

            if (req !== undefined) {
            	if (req.flag) {
            		debug.error("DISCOGS","Request just pulled from queue is already being handled",req.data.url);
            		return;
            	}
				queue[0].flag = true;
				debug.debug("DISCOGS","Taking next request from queue",req.data);
	            var getit = $.ajax({
					method: 'POST',
					url: "browser/backends/getdidata.php",
					data: req.data,
	                dataType: "json"
				})
		        .done(function(data) {
                	var c = getit.getResponseHeader('Pragma');
	            	debug.debug("DISCOGS", "Request Success",c,data);
                	if (c == "From Cache") {
	                	throttle = setTimeout(discogs.getrequest, 100);
                	} else {
	                	throttle = setTimeout(discogs.getrequest, 1500);
	                }
                	req = queue.shift();
                	if (data === null) {
	                	data = {error: language.gettext("discogs_error")}
                	} else if (!data.error) {
                		// info_discogs.js was written to accept jsonp data passed back from $.jsonp
                		// However as Discogs now seem to be refusing to respond to those requests
                		// we're using a php script to get it instead. So here we bodge the response
                		// into the form that info_discogs.js is expecting.
                		data = {data: data};
                	}
                	if (req.reqid != '') {
                		data.id = req.reqid;
                	}
	                if (data.error) {
	                    req.fail(data);
	                } else {
	                    req.success(data);
	                }
	            })
				.fail(function(xhr,status,err) {
                	throttle = setTimeout(discogs.getrequest, 1500);
                	req = queue.shift();
                	debug.warn("DISCOGS","Request failed",req,xhr);
					data = {error: language.gettext("discogs_error") + ' ('+xhr.status+' '+err+')'};
                	if (req.reqid != '') {
                		data.id = req.reqid;
                	}
                	req.fail(data);
		        });
	        } else {
				throttle = null;
	        }
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
				debug.log("DISCOGS","Get Artist Releases",name,page);
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
				// NOTE id must be either release/id or master/id
				var data = {url: baseURL+id+'?'};
				discogs.request(reqid, data, success, fail);
			},

			search: function(term, success, fail) {
				var data = {
					url: baseURL+'database/search',
					type: 'master',
					q: term
				};
				discogs.request('', data, success, fail);
			}

		},

		track: {

			getInfo: function(reqid, id, success, fail) {
				// NOTE id must be either release/id or master/id
				var data = {url: baseURL+id+'?'};
				discogs.request(reqid, data, success, fail);
			},

			search: function(term, success, fail) {
				var data = {
					url: baseURL+'database/search',
					type: 'master',
					q: term
				};
				discogs.request('', data, success, fail);
			}

		}


	}

}();
