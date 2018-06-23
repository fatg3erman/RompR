var spotiMixRadio = function() {
	
	var populated = false;
	var tuner;
	var trackseeds;
	var nonspotitracks;
	var medebug = "SPOTIMIXRADIO";
	var param;
	var trackfinder = new faveFinder(false);
	trackfinder.setCheckDb(false);
	trackfinder.setExact(true);
	trackfinder.setPriorities(['spotify']);

	function populateTuner(numtracks) {
		var sods = trackseeds.splice(0,5);
		var params = {seed_tracks: sods.join(',')}
		tuner.populate(params, numtracks);
	}

	return {

		populate: function(p, numtracks) {
			if (p === null) {
				debug.error(medebug,"Called with NULL parameter");
				return false;
			} else {
				param = p;
			}
			if (typeof(spotifyRecommendationsRadio) == 'undefined') {
				debug.log("CRAZY RADIO","Loading Spotify Radio Tuner");
				$.getScript('radios/code/spotifyrecommendationsradio.js?version='+rompr_version,function() {
					spotiMixRadio.actuallyGo(numtracks);
				});
			} else {
				spotiMixRadio.actuallyGo(numtracks);
			}
		},

		actuallyGo: function(numtracks) {
			if (!populated) {
				trackseeds = new Array();
				nonspotitracks = new Array();
				tuner = new spotifyRecommendationsRadio();
				var searchparms;
				switch (param) {
					case 'surprise':
						searchparms = [{action: 'getrecommendationseeds', days: 7, limit: 50, top: 1}];
						break;

					case '7day':
						searchparms = [{action: 'getrecommendationseeds', days: 7, limit: 30, top: 20}];
						break;

					case '1year':
						searchparms = [{action: 'getrecommendationseeds', days: 365, limit: 100, top: 50}];
						break;

					default:
						debug.error(medebug,"UNKNOWN SEARCH PARAMETERS!");
						break;
				}
				metaHandlers.genericAction(
					searchparms,
					function(data) {
						for (var i in data) {
							var m = data[i].Uri.match(/spotify:track:(.*)$/);
							if (m && m[1]) {
								trackseeds.push(m[1]);
							} else {
								debug.log(medebug,"Didn't match Uri",data[i].Uri);
								nonspotitracks.push(data[i]);
							}
						}
						populated = true;
						spotiMixRadio.doStageTwo();
		        	},
		        	function() {
		        		debug.error(medebug,"Error Getting Seeds");
		        		infobar.notify(infobar.ERROR,"Sorry, something went wrong");
		        		playlist.radioManager.stop();
		        	}
		        );
			} else {
				if (trackseeds.length > 0) {
					populateTuner(numtracks);
				} else {
					tuner.sendTracks(numtracks);
				}
			}
		},

		modeHtml: function(p) {
			switch (p) {
				case 'surprise':
					return '<i class="icon-spotify-circled modeimg"/></i><span class="modespan">'+language.gettext('label_spottery_lottery')+'</span>';
					break;

				case '7day':
					return '<i class="icon-spotify-circled modeimg"/></i><span class="modespan">'+language.gettext('label_spotify_mix')+'</span>';
					break;

				case '1year':
					return '<i class="icon-spotify-circled modeimg"/></i><span class="modespan">'+language.gettext('label_spotify_dj')+'</span>';
					break;

				default:
					return '<i class="icon-spotify-circled modeimg"/></i><span class="modespan">Spotify Mix</span>';
					break;

			}
		},

		stop: function() {
			populated = false;
		},

		doStageTwo: function() {
			if (nonspotitracks.length > 0) {
				var t = nonspotitracks.shift();
				debug.log(medebug, "Searching For Spotify ID for",t);
				trackfinder.findThisOne(
					{
						title: t.Title,
						artist: t.Artistname,
						duration: 0,
						albumartist: t.Artistname,
						date: 0
					},
					spotiMixRadio.gotTrackResults
				);
			} else if (trackseeds.length > 0) {
				populateTuner(5);
			} else {
				infobar.notify(infobar.ERROR, "Insufficient Data To Get Recommendations. Play Some More Music!");
        		playlist.radioManager.stop();
			}
		},

		gotTrackResults: function(data) {
			debug.log(medebug,"Got Track Results",data);
			if (data.uri) {
				var m = data.uri.match(/spotify:track:(.*)$/);
				if (m && m[1]) {
					debug.log(medebug,"Found Spotify Track Uri",m[1]);
					trackseeds.push(m[1]);
				}
			}
			spotiMixRadio.doStageTwo();
		}

	}

}();

playlist.radioManager.register("spotiMixRadio",spotiMixRadio,null);
