var starRadios = function() {

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
						url: "backends/sql/userRatings.php",
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

		modeHtml: function() {
			if (param.match(/^\dstars/)) {
				var cn = param.replace(/(\d)/, 'icon-$1-');
				return '<i class="'+cn+' rating-icon-small"></i>';
			} else if (param == "neverplayed" || param == "allrandom" || param == "recentlyplayed") {
				return '<i class="icon-'+param+' modeimg"/></i><span class="modespan">'+
					language.gettext('label_'+param)+'</span>';
			} else {
				return '<i class="icon-tags modeimg"/><span class="modespan">'+param.replace(/^tag\+/, '')+'</span>';
			}
		},

		stop: function() {
		},

		tagPopulate: function(tags) {
			playlist.radioManager.load('starRadios', tags);
		}
	}
}();

playlist.radioManager.register("starRadios", starRadios, null);
