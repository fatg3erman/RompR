var spotiTrackRadio = function() {
	
	var populated = false;
	var trackparams;;
	var tuner;
	var tags;

	return {

		populate: function(p, numtracks) {
			if (typeof(spotifyRecommendationsRadio) == 'undefined') {
				debug.log("CRAZY RADIO","Loading Spotify Radio Tuner");
				$.getScript('radios/code/spotifyrecommendationsradio.js',function() {
					spotiTrackRadio.actuallyGo(p, numtracks)
				});
			} else {
				spotiTrackRadio.actuallyGo(p, numtracks)
			}
		},

		actuallyGo: function(p, numtracks) {
			if (!populated) {
				trackparams = p;
				populated = true;
				var params = {};
				for (var i in trackparams) {
					if (i.match(/seed_/)) {
						params[i] = trackparams[i];
					}
				}
				tuner = new spotifyRecommendationsRadio();
				tuner.populate(params, 5);
			} else {
				tuner.sendTracks(numtracks);
			}
		},

		modeHtml: function(p) {
			if (trackparams) {
				return '<i class="icon-spotify-circled modeimg"/></i><span class="modespan">'+trackparams.name+'</span>';
			} else {
				return false;
			}
		},

		stop: function() {
			populated = false;
		}

	}

}();

playlist.radioManager.register("spotiTrackRadio",spotiTrackRadio,null);