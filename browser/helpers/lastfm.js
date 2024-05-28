function LastFM() {

	var self = this;
	var queue = new Array();
	var current_req;
	var throttleTime = 100;
	var backofftimer;
	var username;
	var token = "";
	var reqid_counter = 100000;

	// --------------------------------------------------------------------------------------------------
	//
	// Functions for logging in / out of LastFM
	//
	// --------------------------------------------------------------------------------------------------

	this.startlogin = function() {
		self.login($('#configpanel input[name="lfmuser"]').val());
	}

	this.logout = function() {
		prefs.save({
			lastfm_session_key: '',
			lastfm_user: '',
			lastfm_logged_in: false,
			sync_lastfm_playcounts: false,
			sync_lastfm_at_start: false,
			synctags: false
		});
		uiLoginBind();
		prefs.setPrefs();
	}

	this.login = function (user) {
		if (user == '') return;
		// Note we have a 'cache' param because params cannot be empty
		username = user;
		api_request(
			{
				method: 'start_login',
				params: {
					cache: false
				}
			},
			do_login,
			loginError
		);
	}

	function do_login(data) {
		token = data.token;
		debug.core("LASTFM","Token",token);
		var lfmlog = new popup({
			width: 600,
			title: language.gettext("lastfm_loginwindow")
		});
		var mywin = lfmlog.create();
		mywin.addClass('textcentre');
		mywin.append($('<p>').html(language.gettext("lastfm_login1")));
		mywin.append($('<p>').html(language.gettext("lastfm_login2")));
		mywin.append($('<a>', {href: data.url, target: '_blank'}).html(language.gettext("lastfm_loginbutton")));
		mywin.append($('<p>').html(language.gettext("lastfm_login3")));
		lfmlog.addCloseButton('OK',lastfm.finishlogin);
		lfmlog.open();
	}

	function loginError(data) {
		infobar.error(language.gettext("lastfm_loginfailed"));
		token = '';
	}

	this.finishlogin = function() {
		debug.log("LAST.FM","Finishing login");
		api_request(
			{
				method: 'get_session',
				params: {
					token: token
				}
			},
			function(data) {
				debug.debug("LASTFM","Got Session Key : ",data);
				prefs.save({
					lastfm_session_key: data.session.key,
					lastfm_user: username,
					lastfm_logged_in: true
				});
				uiLoginBind();
			},
			loginError
		);
		return true;
	}

	// --------------------------------------------------------------------------------------------------
	//
	// Useful Public Methods
	//
	// --------------------------------------------------------------------------------------------------

	this.isLoggedIn = function() {
		return prefs.lastfm_logged_in;
	}

	this.getLanguage = function() {
		switch (prefs.lastfmlang) {
			case "default":
				return null;
				break;
			case "interface":
				if (prefs.interface_language.substr(2,1) == '-') {
					// Just in case it's "pirate";
					return prefs.interface_language.substr(0,2);
				} else {
					return 'en';
				}
				break;
			case "browser":
				return browserLanguage;
				break;
			default:
				return prefs.lastfmlang;
				break;
		}
	}

	// this.flushReqids = function() {
	// 	for (var i = queue.length-1; i >= 0; i--) {
	// 		if (queue[i].reqid)
	// 			queue.splice(i, 1);
	// 	}
	// }

	this.formatBio = function(bio, link) {
		debug.debug("LASTFM","    Formatting Bio");
		if (bio) {
			bio = bio.replace(/\n/g, "</p><p>");
			bio = bio.replace(/(<a .*?href="http:\/\/.*?")/g, '$1 target="_blank"');
			bio = bio.replace(/(<a .*?href="https:\/\/.*?")/g, '$1 target="_blank"');
			bio = bio.replace(/(<a .*?href=")(\/.*?")/g, '$1https://www.last.fm$2 target="_blank"');
			bio = bio.replace(/\[url=(.*?) .*?\](.*?)\[\/url\]/g, '<a href="$1" target="_blank">$2</a>');
			return bio.fixDodgyLinks();
		} else {
			return "";
		}
	}

	// --------------------------------------------------------------------------------------------------
	//
	// API Request Queue
	//
	// --------------------------------------------------------------------------------------------------

	function api_request(options, success, fail, reqid) {
		options.module = 'lastfm';
		if (!reqid && reqid !== 0) {
			reqid = reqid_counter;
			reqid_counter++;
		}
		queue.push({data: options, success: success, fail: fail, reqid: reqid, retries: 0});
		if (typeof current_req == 'undefined')
			do_Request();
	}

	async function do_Request() {
		var data, throttle, response;
		while (current_req = queue.shift()) {
			debug.debug('LASTFM', 'Handling request '+current_req.reqid, current_req);
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
					// debug.error("LASTFM", 'Request failed '+current_req.reqid, response);
					throw new Error(response.status+' '+response.statusText);
				}
				var c = response.headers.get('Pragma');
				data = await response.json();
				debug.debug("LASTFM","Request success "+current_req.reqid, c, data);
				throttle = (c == "From Cache") ? 50 : throttleTime;
				if (data.error) {
					if (handle_error(current_req, data)) {
						current_req.fail({error: data.error, message: format_remote_api_error('lastfm_error', data)}, current_req.reqid);
					} else {
						throttle = throttleTime;
					}
				} else {
					current_req.success(data, current_req.reqid);
				}
			} catch (err) {
				if (handle_error(current_req, err)) {
					debug.log("LASTFM", "Calling req.fail in response to", err);
					current_req.fail({error: 1, message: format_remote_api_error('lastfm_error', err)}, current_req.reqid);
				}

				throttle = throttleTime;
			}
			await new Promise(t => setTimeout(t, throttle));
		}
	}

	function handle_error(req, data) {
		debug.warn("LASTFM","Request error "+req.reqid, data, typeof(data));
		if (data.error) {
			debug.error("LASTFM", 'Error Code',data.error,"Message",data.message);
			switch (data.error) {
				case 4:
				case 9:
				case 10:
				case 14:
				case 26:
					debug.error("LASTFM","We are not authenticated. Logging Out");
					logout();
					return true;
					break;

				case 6:
					// Track not found
					return true;
					break;

				case 29:
					debug.warn("LASTFM","Rate Limit Exceeded. Slowing Down");
					setThrottling(throttleTime+1000);
					// Fall through

				default:
					if (req.retries < 3) {
						debug.debug("LASTFM","Retrying...");
						req.retries++;
						queue.unshift(req);
						return false;
					} else {
						return true;
					}
			}
		}
		return true;
	}

	function speedBackUp() {
		throttleTime = 100;
	}

	function setThrottling(t) {
		clearTimeout(backofftimer);
		throttleTime = t;
		backofftimer = setTimeout(speedBackUp, 90000);
	}

	// --------------------------------------------------------------------------------------------------
	//
	// LastFM API methods
	//
	// --------------------------------------------------------------------------------------------------

	this.track = {

		love : function(options,callback) {
			if (!prefs.lastfm_logged_in) return;
			options.method = 'track.love';
			api_request(
				{
					method: 'signed_request',
					params: options
				},
				function() {
					infobar.notify(language.gettext("label_loved")+" "+options.track);
					callback(true);
				},
				function() {
					infobar.error(language.gettext("label_lovefailed"));
				}
			);
		},

		unlove : function(options,callback,callback2) {
			if (!prefs.lastfm_logged_in) return;
			options.method = 'track.unlove';
			api_request(
				{
					method: 'signed_request',
					params: options
				},
				function() {
					infobar.notify(language.gettext("label_unloved")+" "+options.track);
					callback(false);
				},
				function() {
					infobar.error(language.gettext("label_unlovefailed"));
				}
			);
		},

		getInfo : function(options, callback, failcallback, reqid) {
			// If we're logged in to Last.FM we don't use the cache for this request
			// because it contains 'userloved', which we might update
			if (prefs.lastfm_user != '')
				options.username = prefs.lastfm_user;
			if (self.getLanguage())
				options.lang = self.getLanguage();
			options.autocorrect = prefs.lastfm_autocorrect ? 1 : 0;
			options.method = 'track.getInfo';
			options.cache = !prefs.lastfm_logged_in;
			api_request(
				{
					method: 'get_request',
					params: options
				},
				callback,
				failcallback,
				reqid
			);
		},

		getTags: function(options, callback, failcallback) {
			if (prefs.lastfm_user != '')
				options.username = prefs.lastfm_user;
			options.method = 'track.getTags';
			options.cache = !prefs.lastfm_logged_in;
			api_request(
				{
					method: 'get_request',
					params: options
				},
				callback,
				failcallback,
			);
		},

		getSimilar: function(options, callback, failcallback) {
			options.method = "track.getSimilar";
			options.cache = true;
			api_request(
				{
					method: 'get_request',
					params: options
				},
				callback,
				failcallback,
			);
		},

		addTags : function(options, callback, failcallback) {
			if (!prefs.lastfm_logged_in) return;
			options.method = "track.addTags";
			api_request(
				{
					method: 'signed_request',
					params: options
				},
				function() { callback("track", options.tags) },
				function() { failcallback("track", options.tags) }
			);
		},

		removeTag: function(options, callback, failcallback) {
			if (!prefs.lastfm_logged_in) return;
			options.method = "track.removeTag";
			api_request(
				{
					method: 'signed_request',
					params: options
				},
				function() { callback("track", options.tag) },
				function() { failcallback("track", options.tag) }
			);
		},

		// updateNowPlaying : function(options) {
		// 	if (prefs.lastfm_logged_in && prefs.lastfm_scrobbling) {
		// 		api_request(
		// 			{
		// 				method: 'update_nowplaying',
		// 				params: options
		// 			},
		// 			function() {  },
		// 			function() { infobar.error(language.gettext("label_scrobblefailed")+" "+options.track) }
		// 		);
		// 	}
		// },

		// scrobble : function(options) {

		// 	//
		// 	// options:
		// 	//		timestamp
		// 	//		track
		// 	//		artist
		// 	//		album
		// 	//		[albumArtist]
		// 	//

		// 	if (prefs.lastfm_logged_in && prefs.lastfm_scrobbling) {
		// 		debug.debug("LAST FM","Last.FM is scrobbling");
		// 		api_request(
		// 			{
		// 				method: 'scrobble',
		// 				params: options
		// 			},
		// 			function() {  },
		// 			function() { infobar.error(language.gettext("label_scrobblefailed")+" "+options.track) }
		// 		);
		// 	}
		// },

	}

	this.album = {

		getInfo: function(options, callback, failcallback) {
			if (prefs.lastfm_user != '')
				options.username = prefs.lastfm_user;
			if (self.getLanguage())
				options.lang = self.getLanguage();
			options.autocorrect = prefs.lastfm_autocorrect ? 1 : 0;
			options.cache = true;
			api_request(
				{
					method: 'album_getInfo',
					params: options
				},
				callback,
				failcallback
			);
		},

		getTags: function(options, callback, failcallback) {
			if (prefs.lastfm_user != '')
				options.username = prefs.lastfm_user;
			options.cache = !prefs.lastfm_logged_in;
			options.method = 'album.getTags';
			api_request(
				{
					method: 'get_request',
					params: options
				},
				callback,
				failcallback
			);
		},

		addTags : function(options, callback, failcallback) {
			if (!prefs.lastfm_logged_in) return;
			options.method = "album.addTags";
			api_request(
				{
					method: 'signed_request',
					params: options
				},
				function() { callback("album", options.tags) },
				function() { failcallback("album", options.tags) }
			);
		},

		removeTag: function(options, callback, failcallback) {
			if (!prefs.lastfm_logged_in) return;
			options.method = "album.removeTag";
			api_request(
				{
					method: 'signed_request',
					params: options
				},
				function() { callback("album", options.tag) },
				function() { failcallback("album", options.tag) }
			);
		}
	}

	this.artist = {

		getInfo: function(options, callback, failcallback, reqid) {
			if (prefs.lastfm_user != '')
				options.username = prefs.lastfm_user;
			if (self.getLanguage())
				options.lang = self.getLanguage();
			options.autocorrect = prefs.lastfm_autocorrect ? 1 : 0;
			options.method = 'artist.getInfo';
			options.cache = true;
			api_request(
				{
					method: 'get_request',
					params: options
				},
				callback,
				failcallback,
				reqid
			);
		},

		getTags: function(options, callback, failcallback) {
			if (prefs.lastfm_user != '')
				options.username = prefs.lastfm_user;
			options.method = 'artist.getTags';
			options.cache = !prefs.lastfm_logged_in;
			api_request(
				{
					method: 'get_request',
					params: options
				},
				callback,
				failcallback,
			);
		},

		addTags : function(options, callback, failcallback) {
			if (!prefs.lastfm_logged_in) return;
			options.method = "artist.addTags";
			api_request(
				{
					method: 'signed_request',
					params: options
				},
				function() { callback("artist", options.tags) },
				function() { failcallback("artist", options.tags) }
			);
		},

		removeTag: function(options, callback, failcallback) {
			if (!prefs.lastfm_logged_in) return;
			options.method = "artist.removeTag";
			api_request(
				{
					method: 'signed_request',
					params: options
				},
				function() { callback("artist", options.tag) },
				function() { failcallback("artist", options.tag) }
			);
		},

		getSimilar: function(options, callback, failcallback) {
			options.method = 'artist.getSimilar';
			options.cache = true;
			api_request(
				{
					method: 'get_request',
					params: options
				},
				callback,
				failcallback,
			);
		}

	}

	this.user = {

		getTopArtists: function(options, callback, failcallback) {
			options.user = prefs.lastfm_user;
			options.cache = false;
			options.method = "user.getTopArtists";
			api_request(
				{
					method: 'get_request',
					params: options
				},
				callback,
				failcallback,
			);
		},

		getTopTracks: function(options, callback, failcallback) {
			options.user = prefs.lastfm_user;
			options.cache = false;
			options.method = "user.getTopTracks";
			api_request(
				{
					method: 'get_request',
					params: options
				},
				callback,
				failcallback,
			);
		},

		getRecentTracks: function(options, callback, failcallback) {
			options.user = prefs.lastfm_user;
			options.extended = 1;
			options.cache = false;
			options.method = "user.getRecentTracks";
			api_request(
				{
					method: 'get_request',
					params: options
				},
				callback,
				failcallback,
			);
		}

	}

}
