var recentlyaddedtracks = function() {

	var param;
	var whattodo;
	var tracks;

	return {

		initialise: async function(p) {
			param = p;
			tracks = new Array();
			whattodo = 'getplaylist';
		},

		getURIs: async function(numtracks) {
			while (tracks.length < numtracks) {
				try {
					var t = await $.ajax({
						url: "api/metadata/",
						type: "POST",
						contentType: false,
						data: JSON.stringify([{action: whattodo, playlist: param, numtracks: prefs.smartradio_chunksize}]),
						dataType: 'json'
					});
					tracks = tracks.concat(t);
				} catch(err) {
					debug.error('RECENTLYADDED', 'Error getting tracks',err);
					return false;
				}
			}
			whattodo = 'repopulate';
			return tracks.splice(0, numtracks);
		},

		modeHtml: function() {
			return '<i class="icon-recentlyplayed modeimg"></i><span class="modespan">'+language.gettext("label_"+param)+'</span>&nbsp;';
		},

		stop: function() {

		}

	}

}();

playlist.radioManager.register("recentlyaddedtracks", recentlyaddedtracks, null);
