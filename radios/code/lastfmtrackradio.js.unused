var lastFMTrackRadio = function() {

	var medebug = 'LFM TRACK RADIO';
	var param = null;
	var trackfinder = new faveFinder(false);
	trackfinder.setCheckDb(false);
	var toptracks = null;

	function topTrack(track, artist) {
		var self = this;
		var to_find = null;
		var track_uri = null;

		this.sendATrack = async function() {
			if (to_find === null) {
				lastfm.track.getSimilar({
						track: track,
						artist: artist,
						limit: 100
					},
					self.gotSimilar,
					self.gotNoSimilar
				);
			}
			while (to_find === null) {
				await new Promise(t => setTimeout(t, 500));
			}
			var retval = false;
			while (to_find.length > 0 && track_uri === null) {
				searchForTrack(to_find.shift());
				while (track_uri === null) {
					await new Promise(t => setTimeout(t, 500));
				}
				if (track_uri !== false) {
					retval = {type: 'uri', name: track_uri};
				} else {
					track_uri = null;
				}
			}
			track_uri = null;
			return retval;
		}

		this.gotSimilar = function(data) {
			debug.debug(medebug,"Got Similar Tracks For",track,artist,data);
			to_find = [{title: track, artist: artist}];
			if (data.similartracks && data.similartracks.track) {
				for (let track of data.similartracks.track) {
					if (track.name && track.artist) {
						to_find.push({
							title: track.name,
							artist: track.artist.name
						});
					}
				}
			}
			to_find.sort(randomsort);
		}

		this.gotNoSimilar = function(data) {
			to_find = [{title: track, artist: artist}];
		}

		function searchForTrack(t) {
			debug.debug(medebug,"Getting a URI For",t);
			if (prefs.player_backend == "mopidy") {
				trackfinder.setPriorities($("#radiodomains").makeDomainChooser("getSelection"));
			}
			trackfinder.findThisOne(
				{
					Title: t.title,
					trackartist: t.artist
					// duration: 0,
					// albumartist: t.artist,
					// date: 0
				},
				self.gotAUri
			);
		}

		this.gotAUri = function(data) {
			if (data.file) {
				track_uri = data.file;
			} else {
				track_uri = false;
			}
		}
	}

	function getTopTracks(page) {
		lastfm.user.getTopTracks(
			{period: param,
			page: page,
			limit: 100},
			lastFMTrackRadio.gotTopTracks,
			lastFMTrackRadio.lfmerror
		);
	}

	return {

		initialise: async function(p) {
			param = p;
			getTopTracks(1);
		},

		getURIs: async function(numtracks) {
			while (toptracks === null) {
				await new Promise(t => setTimeout(t, 500));
			}
			debug.log(medebug, 'We have',toptracks.length,'toptracks');
			while (toptracks.length > 0) {
				var trackindex = Math.floor(Math.random() * (toptracks.length - 1));
				var track = await toptracks[trackindex].sendATrack();
				if (track) {
					// Return tracks one at a time, otherwise there's a really long wait when we first start up
					return [track];
				} else {
					debug.log(medebug, 'Deleting toptrack',trackindex);
					toptracks.splice(trackindex, 1);
				}
			}
			return [];
		},

		lfmerror: function(data) {
			debug.warn(medebug,"Last.FM Error",data);
		},

		gotTopTracks: function(data) {
			debug.debug(medebug,"Got Top Tracks",data);
			if (toptracks === null) {
				toptracks = [];
			}
			if (data.toptracks.track) {
				currpage = parseInt(data.toptracks['@attr'].page);
				totalpages = parseInt(data.toptracks['@attr'].totalPages);
				debug.log(medebug,"Got Page",currpage,"Of",totalpages,"Of Top Tracks");
				for (var track of data.toptracks.track) {
					if (track.name && track.artist) {
						toptracks.push(new topTrack(track.name, track.artist.name));
					}
				}
				if (currpage < totalpages) {
					getTopTracks(currpage+1);
				}
			}
		},

		stop: function() {
			param = null;
			toptracks = null;
		},

		modeHtml: function() {
			return '<i class="icon-lastfm-1 modeimg"/></i><span class="alignmid bold">'+language.gettext('label_lastfm_mix_'+param)+'</span>';
		},

	}


}();

playlist.radioManager.register("lastFMTrackRadio",lastFMTrackRadio,null);
