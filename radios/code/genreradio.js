var genreRadio = function() {

	var genre;
	var tracks = null;

	function searchForTracks() {
		var domains = new Array();
		if (prefs.player_backend == "mopidy") {
			domains = $("#radiodomains").makeDomainChooser("getSelection");
		}
		debug.log("GENRE RADIO","Searching for Genre",genre,"in domains",domains);
		// Generally, using faveFinder is better but that doesn't support Genres
		player.controller.rawsearch({genre: [genre]}, domains, false, genreRadio.checkResults, false);
	}

	return {

		initialise: async function(p) {
			genre = p;
			tracks = null;
			searchForTracks();
		},

		getURIs: async function(numtracks) {
			while (tracks === null) {
				await new Promise(t => setTimeout(t, 500));
			}
			return tracks.splice(0, numtracks);
		},

		checkResults: function(data) {
			debug.debug("GENRE RADIO","Search Results",data);
			tracks = [];
			for (let track of data) {
				if (!track.uri.match(/:artist:/)) {
					tracks.push({type: 'uri', name: track.file});
				}
			}
			debug.trace('GENRE RADIO', 'We have',tracks.length,'tracks');
			tracks.sort(randomsort);
		},

		stop: function() {
			tracks = null;
			genre = null;
		},

		modeHtml: function() {
			return '<i class="icon-wifi modeimg"/></i><span class="modespan ucfirst">'+genre+' '+language.gettext('label_radio')+'</span>';
		}

	}

}();

playlist.radioManager.register("genreRadio", genreRadio, null);
