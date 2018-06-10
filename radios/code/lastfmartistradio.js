var lastFMArtistRadio = function() {
	
	var populating = false;
	var tuner;
	var fartists;
	var currpage = 1;
	var totalpages = 1;
	var started;
	var simtimer;
	var minplays;
	var param = null;

	function getNextSimilars() {
		var art = fartists.shift();
		if (art) {
			lastfm.artist.getSimilar(
				{artist: art},
				lastFMArtistRadio.gotASimilar,
				lastFMArtistRadio.gotNoSimilar
			);
		}
	}

	function getTopArtists(page) {
		var period = null;
		period = param;
		debug.log("LASTFM MIX RADIO","Using parameter for period",period);
		switch (period) {
			case 'overall':
				minplays = 7;
				break

			case '12month':
				minplays = 5;
				break;

			case '6month':
				minplays = 4;
				break;

			case '3month':
				minplays = 3;
				break;

			case '1month':
				minplays = 2;
				break;

			default:
				minplays = 1;
				break;

		}
		lastfm.user.getTopArtists(
			{period: period,
			page: page},
			lastFMArtistRadio.gotTopArtists,
			lastFMArtistRadio.lfmerror
		);
	}

	return {

		populate: function(p, numtracks) {
			if (!populating) {
				param = p;
				if (typeof(searchRadio) == 'undefined') {
					debug.log("LASTFM MIX RADIO","Loading Search Radio Tuner");
					$.getScript('radios/code/searchRadio.js',function() {
						lastFMArtistRadio.actuallyGo(numtracks);
					});
				} else {
					lastFMArtistRadio.actuallyGo(numtracks);
				}
			} else {
				debug.log("LASTFM MIX RADIO","RePopulating",numtracks);
				tuner.sending += (numtracks - tuner.sending);
				tuner.startSending();
			}
		},

		actuallyGo: function(numtracks) {
			debug.log("LASTFM MIX RADIO","And we're off");
			tuner = new searchRadio();
			tuner.sending = numtracks;
			tuner.running = true;
			tuner.artistindex = 0;
			fartists = new Array();
			populating = true;
			started = false;
			lastfm.setThrottling(1500);
			getTopArtists(1);
		},

		lfmerror: function(data) {
			debug.warn("LASTFM MIX RADIO","Last.FM Error",data);
			if (currpage < totalpages) {
				getTopArtists(currpage+1);
			}
		},

		modeHtml: function(p) {
			return '<i class="icon-lastfm-1 modeimg"/></i><span class="modespan">'+language.gettext('label_lastfm_mix')+'</span>';
		},

		stop: function() {
			lastfm.setThrottling(500);
			populating = false;
			param = null;
			if (tuner) {
				tuner.sending = 0;
				tuner = null;
			}
		},

		gotTopArtists: function(data) {
			if (data.topartists.artist) {
				if (tuner) {
					currpage = parseInt(data.topartists['@attr'].page);
					totalpages = parseInt(data.topartists['@attr'].totalPages);
					debug.mark("LASTFM MIX RADIO","Got Page",currpage,"Of",totalpages,"Of Top Artists");
					for (var i in data.topartists.artist) {
						if (data.topartists.artist[i].playcount >= minplays) {
							fartists.push(data.topartists.artist[i].name);
						} else {
							debug.mark("LASTFM MIX RADIO","Ignoring Artist",data.topartists.artist[i].name,"because it only has",data.topartists.artist[i].playcount,"plays");
						}
					}
					for (var i in fartists) {
						tuner.newArtist(fartists[i]);
					}
					if (populating) {
						if (currpage < totalpages) {
							getTopArtists(currpage+1);
						}
						if (!started) {
							started = true;
							tuner.startSending();
							clearTimeout(simtimer);
							simtimer = setTimeout(getNextSimilars, 1000);
						}
					}
				}
			} else {
				if (currpage == 1) {
					infobar.notify(infobar.ERROR, "Got no data from Last.FM");
					playlist.radioManager.stop();
				}
			}
		},

		gotASimilar: function(data) {
			if (tuner) {
				debug.mark("LASTFM MIX RADIO","Got Similar Artists For",data.similarartists['@attr'].artist);
				if (data.similarartists.artist) {
					for (var i in data.similarartists.artist) {
						tuner.newArtist(data.similarartists.artist[i].name);
					}
				}
				if (populating) {
					clearTimeout(simtimer);
					simtimer = setTimeout(getNextSimilars, 1000);
				}
			}
		},

		gotNoSimilar: function() {
			if (populating) {
				clearTimeout(simtimer);
				simtimer = setTimeout(getNextSimilars, 1000);
			}
		}

	}

}();

playlist.radioManager.register("lastFMArtistRadio",lastFMArtistRadio,null);
