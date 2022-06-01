var spotiCrazyRadio = function() {

	var tuner;
	var name;
	var medebug = 'CRAZYRADIO';
	const integer_values = ['popularity'];

	return {

		initialise: async function(p) {
			if (typeof(spotifyRecommendationsRadio) == 'undefined') {
				debug.info(medebug,"Loading Spotify Radio Tuner");
				try {
					await $.getScript('radios/code/spotifyrecommendationsradio.js?version='+rompr_version);
				} catch (err) {
					debug.error(medebug, 'Failed to load script', err);
					return false;
				}
			}
			tuner = new spotifyRecommendationsRadio(true);
			p = JSON.parse(p);
			crazyRadioManager.load(p);
			var params = {'seed_genres': p.genres};
			if (p.playlistname) {
				name = p.playlistname;
			} else {
				name = p.genres;
			}
			delete p.playlistname;
			delete p.genres;
			for (var param in p) {
				if (integer_values.indexOf(param) == -1) {
					var places = 2;
				} else {
					var places = 0;
				}
				for (var attr in p[param]) {
					params[attr+'_'+param] = parseFloat(p[param][attr]).toFixed(places);
				}
			}
			if (params.seed_genres != '') {
				debug.log(medebug, 'Loading with',params);
				tuner.getRecommendations(params);
			} else {
				debug.info(medebug, 'No Genres!');
				infobar.error(language.gettext('error_nogenres'));
				return false;
			}
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
			return '<i class="icon-spotify-circled modeimg"/></i><span class="alignmid bold">'+name+'</span>';
		},

		stop: function() {
			tuner = null;
			name = '';
		}

	}

}();

playlist.radioManager.register("spotiCrazyRadio",spotiCrazyRadio,null);
