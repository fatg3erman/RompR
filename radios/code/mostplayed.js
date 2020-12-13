var mostPlayed = function() {

	var param;
	var whattodo;
	var tracks;

	return {

		initialise: async function(p) {
			param = p;
			whattodo = 'getplaylist';
			tracks = new Array();
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
					debug.error('STARRADIOS', 'Error getting tracks',err);
					return false;
				}
			}
			whattodo = 'repopulate';
			return tracks.splice(0, numtracks);
		},

		modeHtml: function(p) {
			return '<i class="icon-music modeimg"></i><span class="modespan">'+language.gettext("label_mostplayed")+'</span>&nbsp;';
		},

		stop: function() {

		}

	}

}();

playlist.radioManager.register("mostPlayed", mostPlayed, null);