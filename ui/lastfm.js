function LastFM(user) {

	var lak = null;
	var lfms = null;
	var logged_in = false;
	var username = user;
	var token = "";
	var self=this;
	var queue = new Array();
	var current_req = null;
	var throttleTime = 100;
	var nextThrottle;
	var backofftimer;

	function startlogin() {
		self.login($('#configpanel input[name="lfmuser"]').val());
	}

	function logout() {
		prefs.save({lastfm_session_key: ''});
		logged_in = false;
		uiLoginBind();
	}

	function uiLoginBind() {
		if (!logged_in) {
			$('.lastfmlogin-required').removeClass('notenabled').addClass('notenabled');
			$('#lastfmloginbutton').off('click').on('click', startlogin).html(language.gettext('config_loginbutton'));
		} else {
			$('.lastfmlogin-required').removeClass('notenabled');
			$('#lastfmloginbutton').off('click').on('click', logout).html(language.gettext('button_logout'));
		}
	}

	if (prefs.lastfm_session_key !== "" || typeof lastfm_session_key !== 'undefined') {
		logged_in = true;
	}

	uiLoginBind();

	this.wrangle = async function() {
		debug.debug('LASTFM', 'Doing the wrangling');
		try {
			var data = await $.ajax({
				method: 'GET',
				url: 'includes/strings.php?getcheese=1',
				dataType: 'json'
			});
			debug.core('LASTFM', 'Done the wrangling',data);
			lak = data.k;
			lfms = data.s;
		} catch (err) {
			debug.warn("LASTFM", "Big Setup Failure",err);
			logged_in = false;
		}
	}

	function speedBackUp() {
		throttleTime = 100;
	}

	function setThrottling(t) {
		clearTimeout(backofftimer);
		throttleTime = t;
		backofftimer = setTimeout(speedBackUp, 90000);
	}

	this.showloveban = function(flag) {
		if (logged_in && flag) {
			$("#lastfm").show();
		} else {
			$("#lastfm").hide();
		}
	}

	this.isLoggedIn = function() {
		return logged_in;
	}

	this.getLanguage = function() {
		switch (prefs.lastfmlang) {
			case "default":
				return null;
				break;
			case "interface":
				return interfaceLanguage;
				break;
			case "browser":
				return browserLanguage;
				break;
			default:
				return prefs.lastfmlang;
				break;
		}
	}

	this.username = function() {
		return username;
	}

	this.login = function (user, pass) {

		username = user;
		var options = {api_key: lak, method: "auth.getToken"};
		var keys = getKeys(options);
		var it = "";

		for(var key in keys) {
			it = it+keys[key]+options[keys[key]];
		}
		it = it+lfms;
		options.api_sig = hex_md5(it);
		options.format = 'json';
		var url = "https://ws.audioscrobbler.com/2.0/";
		var adder = "?";
		var keys = getKeys(options);
		for(var key in keys) {
			url=url+adder+keys[key]+"="+options[keys[key]];
			adder = "&";
		}
		$.get(url, function(data) {
			token = data.token;
			debug.core("LASTFM","Token",token);
			var lfmlog = new popup({
				css: {
					width: 600,
					height: 400
				},
				fitheight: true,
				title: language.gettext("lastfm_loginwindow")
			});
			var mywin = lfmlog.create();
			mywin.append('<table align="center" cellpadding="2" id="lfmlogintable" width="90%"></table>');
			$("#lfmlogintable").append('<tr><td>'+language.gettext("lastfm_login1")+'</td></tr>');
			$("#lfmlogintable").append('<tr><td>'+language.gettext("lastfm_login2")+'</td></tr>');
			$("#lfmlogintable").append('<tr><td align="center"><a href="http://www.last.fm/api/auth/?api_key='+lak+'&token='+token+'" target="_blank">'+
										'<button>'+language.gettext("lastfm_loginbutton")+'</button></a></td></tr>');
			$("#lfmlogintable").append('<tr><td>'+language.gettext("lastfm_login3")+'</td></tr>');
			lfmlog.addCloseButton('OK',lastfm.finishlogin);
			lfmlog.setWindowToContentsSize();
			lfmlog.open();
		});
	}

	this.finishlogin = function() {
		debug.log("LAST.FM","Finishing login");
		LastFMSignedRequest(
			{
				token: token,
				format: 'json',
				api_key: lak,
				method: "auth.getSession"
			},
			function(data) {
				debug.debug("LASTFM","Got Session Key : ",data);
				var lastfm_session_key = data.session.key;
				logged_in = true;
				prefs.save({
					lastfm_session_key: lastfm_session_key,
					lastfm_user: username
				});
				uiLoginBind();
			},
			function(data) {
				alert(language.gettext("lastfm_loginfailed"));
			}
		);
		return true;
	}

	this.flushReqids = function() {
		for (var i = queue.length-1; i >= 0; i--) {
			if (queue[i].reqid)
				queue.splice(i, 1);
		}
	}

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

	function LastFMGetRequest(options, cache, success, fail, reqid) {
		options.format = "json";
		options.cache = cache;
		queue.push({url: options, success: success, fail: fail, cache: cache, reqid: reqid, retries: 0});
		if (current_req == null)
			do_Request();
	}

	function addGetOptions(options, method) {
		options.api_key = lak;
		options.autocorrect = prefs.lastfm_autocorrect ? 1 : 0;
		options.method = method;
	}

	var getKeys = function(obj) {
		var keys = [];
		for(var key in obj){
			keys.push(key);
		}
		keys.sort();
		return keys;
	}

	function LastFMSignedRequest(options, success, fail) {

		// We've passed an object but we need the properties to be in alphabetical order
		var keys = getKeys(options);
		var it = "";
		for(var key in keys) {
			if (keys[key] != 'format' && keys[key] != 'callback') {
				it = it+keys[key]+options[keys[key]];
			}
		}
		it = it+lfms;
		options.api_sig = hex_md5(it);
		queue.push({
			url: "POST",
			options: options,
			success: success,
			fail: fail,
			retries : 0
		});
		if (current_req == null)
			do_Request();
	}

	function addSetOptions(options, method) {
		options.format = 'json';
		options.api_key = lak;
		options.sk = prefs.lastfm_session_key;
		options.method = method;
	}

	async function do_Request() {
		var req;
		if (lak === null) {
			debug.error('LASTFM', 'Fatal Error');
			return;
		}
		while (req = queue.shift()) {
			current_req = req;
			if (req.url == "POST") {
				await handle_post_request(req);
			} else {
				await handle_get_request(req);
			}
			await new Promise(t => setTimeout(t, nextThrottle));
		}
		current_req = null;
	}

	async function handle_post_request(req) {
		var data, jqxhr;
		debug.debug("LASTFM", "Handling POST request via queue", req);
		try {
			data = await (jqxhr = $.ajax({
				method: "POST",
				url: "https://ws.audioscrobbler.com/2.0/",
				data: req.options,
				dataType: 'json',
				timeout: 5000
			}));
			debug.debug("LASTFM", req.options.method,"request success");
			if (data.error) {
				debug.warn("LASTFM","Last FM signed request failed with status",data.error.message);
				req.fail(data);
			} else {
				req.success(data);
			}
		} catch (err) {
			if (handle_error(req, err))
				req.fail(null);
		}
		nextThrottle = throttleTime;
	}

	async function handle_get_request(req) {
		var data, jqxhr;
		debug.debug('LASTFM', 'Handling get request', req);
		try {
			data = await (jqxhr = $.ajax({
				method: 'POST',
				url: 'browser/backends/getlfmdata.php',
				data: req.url,
				dataType: 'json'
			}));
			var c = jqxhr.getResponseHeader('Pragma');
			debug.debug("LASTFM","GET Request success",c, data, jqxhr);
			nextThrottle = (c == "From Cache") ? 50 : throttleTime;
			if (req.reqid || req.reqid === 0) {
				req.success(data, req.reqid);
			} else {
				req.success(data);
			}
		} catch (err) {
			debug.warn("LASTFM", "Get Request Failed",err);
			if (handle_error(req, err)) {
				if (req.reqid || req.reqid === 0) {
					req.fail(null, req.reqid);
				} else {
					let errormessage = language.gettext('lastfm_error');
					if (err.responseJSON)
						errormessage += ' ('+err.responseJSON.message+')';
					req.fail({error: 1, message: errormessage});
				}
			}
			nextThrottle = throttleTime;
		}
	}

	function handle_error(req, xhr) {
		debug.error("LASTFM",req,"request error",xhr);
		if (xhr.responseJSON) {
			var errorcode = xhr.responseJSON.error;
			var errortext = xhr.responseJSON.message;
			debug.error("LASTFM", 'Error Code',errorcode,"Message",errortext);
			switch (errorcode) {
				case 4:
				case 9:
				case 10:
				case 14:
				case 26:
					debug.error("LASTFM","We are not authenticated. Logging Out");
					logout();
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

	this.track = {

		love : function(options,callback) {
			if (logged_in) {
				addSetOptions(options, "track.love");
				LastFMSignedRequest(
					options,
					function() {
						infobar.notify(language.gettext("label_loved")+" "+options.track);
						callback(true);
					},
					function() {
						infobar.error(language.gettext("label_lovefailed"));
					}
				);
			}
		},

		unlove : function(options,callback,callback2) {
			if (logged_in) {
				addSetOptions(options, "track.unlove");
				LastFMSignedRequest(
					options,
					function() {
						infobar.notify(language.gettext("label_unloved")+" "+options.track);
						callback(false);
					},
					function() {
						infobar.error(language.gettext("label_unlovefailed"));
					}
				);
			}
		},

		getInfo : function(options, callback, failcallback, reqid) {
			// If we're logged in to Last.FM we don't use the cache for this request
			// because it contains 'userloved', which we might update
			if (username != "") { options.username = username; }
			addGetOptions(options, "track.getInfo");
			if (self.getLanguage())
				options.lang = self.getLanguage();

			LastFMGetRequest(
				options,
				!logged_in,
				callback,
				failcallback,
				reqid
			);
		},

		getTags: function(options, callback, failcallback, reqid) {
			if (username != "") { options.user = username; }
			addGetOptions(options, "track.getTags");
			LastFMGetRequest(
				options,
				!logged_in,
				callback,
				failcallback,
				reqid
			);
		},

		getSimilar: function(options, callback, failcallback) {
			addGetOptions(options, "track.getSimilar");
			LastFMGetRequest(
				options,
				true,
				callback,
				failcallback
			);
		},

		addTags : function(options, callback, failcallback) {
			if (logged_in) {
				addSetOptions(options, "track.addTags");
				LastFMSignedRequest(
					options,
					function() { callback("track", options.tags) },
					function() { failcallback("track", options.tags) }
				);
			}
		},

		removeTag: function(options, callback, failcallback) {
			if (logged_in) {
				addSetOptions(options, "track.removeTag");
				LastFMSignedRequest(
					options,
					function() { callback("track", options.tag); },
					function() { failcallback("track", options.tag); }
				);
			}
		},

		updateNowPlaying : function(options) {
			if (logged_in && prefs.lastfm_scrobbling) {
				addSetOptions(options, "track.updateNowPlaying");
				LastFMSignedRequest(
					options,
					function() {  },
					function() { debug.warn("LAST FM","Failed to update Now Playing",options) }
				);
			}
		},

		scrobble : function(options) {
			if (logged_in && prefs.lastfm_scrobbling) {
				debug.debug("LAST FM","Last.FM is scrobbling");
				addSetOptions(options, "track.scrobble");
				LastFMSignedRequest(
					options,
					function() {  },
					function() { infobar.error(language.gettext("label_scrobblefailed")+" "+options.track) }
				);
			}
		},

	}

	this.album = {

		getInfo: function(options, callback, failcallback) {
			addGetOptions(options, "album.getInfo");
			if (username != "")
				options.username = username;
			options.autocorrect = prefs.lastfm_autocorrect ? 1 : 0;
			if (self.getLanguage())
				options.lang = self.getLanguage();
			debug.debug("LASTFM","album.getInfo",options);
			LastFMGetRequest(
				options,
				true,
				callback,
				failcallback
			);
		},

		getTags: function(options, callback, failcallback) {
			addGetOptions(options, "album.getTags");
			if (username != "")
				options.user = username;
			debug.debug("LASTFM","album.getTags",options);
			LastFMGetRequest(
				options,
				!logged_in,
				callback,
				failcallback
			);
		},

		addTags : function(options, callback, failcallback) {
			if (logged_in) {
				addSetOptions(options, "album.addTags");
				LastFMSignedRequest(
					options,
					function() { callback("album", options.tags) },
					function() { failcallback("album", options.tags) }
				);
			}
		},

		removeTag: function(options, callback, failcallback) {
			if (logged_in) {
				addSetOptions(options, "album.removeTag");
				LastFMSignedRequest(
					options,
					function() { callback("album", options.tag); },
					function() { failcallback("album", options.tag); }
				);
			}
		}
	}

	this.artist = {

		getInfo: function(options, callback, failcallback, reqid) {
			addGetOptions(options, "artist.getInfo");
			if (username != "")
				options.username = username;
			if (self.getLanguage())
				options.lang = self.getLanguage();

			LastFMGetRequest(
				options,
				true,
				callback,
				failcallback,
				reqid
			);
		},

		getTags: function(options, callback, failcallback) {
			if (username != "")
				options.user = username;
			addGetOptions(options, "artist.getTags");
			LastFMGetRequest(
				options,
				!logged_in,
				callback,
				failcallback
			);
		},

		addTags : function(options, callback, failcallback) {
			if (logged_in) {
				addSetOptions(options, "artist.addTags");
				LastFMSignedRequest(
					options,
					function() { callback("artist", options.tags) },
					function() { failcallback("artist", options.tags) }
				);
			}
		},

		removeTag: function(options, callback, failcallback) {
			if (logged_in) {
				addSetOptions(options, "artist.removeTag");
				LastFMSignedRequest(
					options,
					function() { callback("artist", options.tag); },
					function() { failcallback("artist", options.tag); }
				);
			}
		},

		getSimilar: function(options, callback, failcallback) {
			addGetOptions(options, "artist.getSimilar");
			LastFMGetRequest(
				options,
				true,
				callback,
				failcallback,
				1
			)
		}

	}

	this.user = {

		getArtistTracks: function(artist, perpage, page, callback, failcallback) {
			var options = { user: username,
							page: page,
							limit: perpage,
							artist: artist
						  };
			addGetOptions(options, "user.getArtistTracks");
			LastFMGetRequest(
				options,
				false,
				callback,
				failcallback,
				1
			)
		},

		getTopArtists: function(options, callback, failcallback) {
			options.user = username;
			addGetOptions(options, "user.getTopArtists");
			LastFMGetRequest(
				options,
				false,
				callback,
				failcallback,
				1
			)
		},

		getTopTracks: function(options, callback, failcallback) {
			options.user = username;
			addGetOptions(options, "user.getTopTracks");
			LastFMGetRequest(
				options,
				false,
				callback,
				failcallback,
				1
			)
		},

		getRecentTracks: function(options, callback, failcallback) {
			options.user = username;
			options.extended = 1;
			addGetOptions(options, "user.getRecentTracks");
			LastFMGetRequest(
				options,
				false,
				callback,
				failcallback,
				1
			)
		}

	}

	this.library = {

		getArtists: function(perpage, page, callback, failcallback) {
			var options = { user: username,
							page: page,
							limit: perpage
						  };
			addGetOptions(options, "library.getArtists");
			LastFMGetRequest(
				options,
				false,
				callback,
				failcallback,
				1
			)
		}

	}
}
