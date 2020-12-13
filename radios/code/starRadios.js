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
						url: "api/metadata/",
						type: "POST",
						contentType: false,
						data: JSON.stringify([{action: whattodo, playlist: param, numtracks: numtracks}]),
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
			} else if (/^tag\+/.test(param)) {
				return '<i class="icon-tags modeimg"/><span class="modespan">'+param.replace(/^tag\+|^genre\+|^artist\+/, '')+'</span>';
			} else if (/^genre\+/.test(param)) {
				return '<i class="icon-music modeimg"/><span class="modespan">'+param.replace(/^tag\+|^genre\+|^artist\+/, '')+'</span>';
			} else if (/^artist\+/.test(param)) {
				return '<i class="icon-artist modeimg"/><span class="modespan">'+param.replace(/^tag\+|^genre\+|^artist\+/, '')+'</span>';
			} else if (/^custom\+/.test(param)) {
				return '<i class="icon-wifi modeimg"/><span class="modespan">'+param.replace(/^custom\+/, '')+'</span>';
			} else {
				return '<i class="icon-wifi modeimg"/>';
			}
		},

		stop: function() {
		},

		// tagPopulate: function(tags) {
		// 	playlist.radioManager.load('starRadios', tags);
		// }
	}
}();

playlist.radioManager.register("starRadios", starRadios, null);
