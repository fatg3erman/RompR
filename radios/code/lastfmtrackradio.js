var lastFMTrackRadio = function() {

	var populating = false;
	var currpage = 1;
	var totalpages = 1;
	var topTracks = new Array();
	var tosend = 0;
	var started = false;
	var param = null;
	var periodmap = ['7day', '1month', '3month', '6month', '12month', 'overall'];

	function topTrack(track, artist) {
		var self = this;
		var tracks = new Array();
		this.populated = false;

		this.sendATrack = function() {
			debug.log("LASTFM TRACK RADIO",track,artist,"sending a track",self.populated);
			if (!self.populated) {
				debug.log("LASTFM TRACK RADIO","Getting Similar Tracks For",track,artist);
				tracks.push({track_name: [track], artist: [artist]});
				lastfm.track.getSimilar({
						track: track,
						artist: artist,
						limit: 100
					},
					self.gotSimilar,
					self.gotNoSimilar
				);
			} else {
				getAUri();
			}
		}

		function getAUri() {
			if (tosend > 0) {
				if (tracks.length > 0) {
					var t = tracks.shift();
					debug.log("LASTFM TRACK RADIO","Getting a URI For",t);
					player.controller.rawsearch(t, [], false, self.gotAUri, false);
				} else {
					// This isn't ideal. What we need to do is inform the parent that we're out
					// of tracks so he longer asks us to send any.
					lastFMTrackRadio.trackAddFailed();
				}
			}
		}

		this.gotSimilar = function(data) {
			debug.log("LASTFM TRACK RADIO","Got Similar Tracks For",track,artist,data);
			self.populated = true;
			if (data.similartracks.track) {
				for (var i in data.similartracks.track) {
					if (data.similartracks.track[i].name && data.similartracks.track[i].artist) {
						tracks.push({
							track_name: [data.similartracks.track[i].name],
							artist: [data.similartracks.track[i].artist.name]
						});
					}
				}
			}
			tracks = tracks.sort(randomsort);
			getAUri();
		}

		this.gotNoSimilar = function(data) {
			getAUri();
		}

		this.gotAUri = function(data) {
			var track = null;
			st: {
				for (var j in data) {
					for (var k in data[j].tracks) {
						if (!data[j].tracks[k].uri.match(/:album:/) &&
							!data[j].tracks[k].uri.match(/:artist:/)) {
							if (prefs.player_backend == "mopidy") {
								var n = $("#radiodomains").makeDomainChooser("getSelection");
								var d = data[j].tracks[k].uri.substr(0, data[j].tracks[k].uri.indexOf(':'));
								if (n.indexOf(d) == -1) {
									continue;
								}
							}
							track = {type: 'uri', name: data[j].tracks[k].uri};
							break st;
						}
					}
				}
			}
			if (track !== null) {
				tosend--;
        		player.controller.addTracks([track], playlist.radioManager.playbackStartPos(), null);
			} else {
				debug.warn("LASTFM TRACK RADIO","Failed to get a URI");
				lastFMTrackRadio.trackAddFailed();
			}
		}
	}

	function getTopTracks(page) {
		var period = null;
		if (param === null) {
			var p = $('#lastfmtimerange').rangechooser('getRange');
			period = periodmap[Math.round(p.max)];
			debug.log("LASTFM TRACK RADIO","Getting listen period from selector",period,p);
		} else {
			period = param;
			debug.log("LASTFM TRACK RADIO","Using parameter for period",period);
		}
		lastfm.user.getTopTracks(
			{period: period,
			page: page,
			limit: 100},
			lastFMTrackRadio.gotTopTracks,
			lastFMTrackRadio.lfmerror
		);
	}

	function sendATrack() {
		debug.log("LASTFM TRACK RADIO","Sending a track",tosend);
		if (tosend > 0) {
			var index = Math.floor(Math.random() * topTracks.length);
			debug.log("LASTFM TRACK RADIO","Using Index",index);
			topTracks[index].sendATrack();
		}
	}
	
	return {

		populate: function(p, numtracks) {
			if (populating) {
				tosend += (numtracks - tosend);
				sendATrack();
			} else {
				param = p;
				topTracks = new Array();
				tosend = numtracks;
				populating = true;
				started = false;
				lastfm.setThrottling(1500);
				getTopTracks(1);
			}
		},

		lfmerror: function(data) {
			debug.warn("LASTFM MIX RADIO","Last.FM Error",data);
		},

		trackAddFailed: function() {
			sendATrack();
		},

		gotTopTracks: function(data) {
			debug.log("LASTFM TRACK RADIO","Got Top Tracks",data);
			if (data.toptracks.track) {
				currpage = parseInt(data.toptracks['@attr'].page);
				totalpages = parseInt(data.toptracks['@attr'].totalPages);
				debug.mark("LASTFM TRACK RADIO","Got Page",currpage,"Of",totalpages,"Of Top Tracks");
				for (var i in data.toptracks.track) {
					if (data.toptracks.track[i].name && data.toptracks.track[i].artist) {
						topTracks.push(new topTrack(data.toptracks.track[i].name, data.toptracks.track[i].artist.name));
					}
				}
				if (populating) {
					if (currpage < totalpages) {
						getTopTracks(currpage+1);
					}
					if (!started) {
						started = true;
						sendATrack();
					}
				}
			} else {
				if (currpage == 1) {
					infobar.notify(infobar.ERROR, "Got no data from Last.FM");
					playlist.radioManager.stop();
				}
			}
		},

		stop: function() {
			populating = false;
			lastfm.setThrottling(500);
			param = null;
		},

		modeHtml: function(p) {
			return '<i class="icon-lastfm-1 modeimg"/></i><span class="modespan">'+language.gettext('label_lastfm_track')+'</span>';
		},

	}


}();

playlist.radioManager.register("lastFMTrackRadio",lastFMTrackRadio,null);
