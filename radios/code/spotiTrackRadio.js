var spotiTrackRadio = function() {

	var tuner;
	var param;
	var medebug = 'SPOTITRACK';

	return {

		initialise: async function(p) {
			param = p;
			if (typeof(spotifyRecommendationsRadio) == 'undefined') {
				debug.info(medebug,"Loading Spotify Radio Tuner");
				try {
					await $.getScript('radios/code/spotifyrecommendationsradio.js?version='+rompr_version);
				} catch (err) {
					debug.error(medebug, 'Failed to load script', err);
					return false;
				}
			}
			tuner = new spotifyRecommendationsRadio();
			var r = {};
			for (var i in param) {
				if (i.match(/seed_/)) {
					r[i] = param[i];
				}
			}
			tuner.getRecommendations(r)
		},

		getURIs: async function(numtracks) {
			var tracks = await tuner.getTracks(numtracks);
			var retval = new Array();
			tracks.forEach(function(uri) {
				retval.push({type: 'uri', name: uri});
			});
			return retval;
		},

		modeHtml: function() {
			return '<i class="icon-spotify-circled modeimg"/></i><span class="modespan">'+param.name+'</span>';
		},

		stop: function() {

		}

	}

}();

playlist.radioManager.register("spotiTrackRadio", spotiTrackRadio, null);
