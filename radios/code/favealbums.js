var faveAlbums = function() {

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
						url: "radios/api/starRadios.php",
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
			return '<i class="icon-music modeimg"></i><span class="alignmid bold">'+
				language.gettext("label_favealbums")+'</span>&nbsp;';
		},

		stop: function() {
		},

	}

}();

playlist.radioManager.register("faveAlbums", faveAlbums, null);