var mixRadio = function() {

	var medebug = 'MIX RADIO';
	var tuner;

	return {

		initialise: async function(p) {
			if (typeof(spotifyRadio) == 'undefined') {
				debug.info(medebug,"Loading Spotify Radio Tuner");
				try {
					await $.getScript('radios/code/spotifyRadio.js?version='+rompr_version);
				} catch (err) {
					debug.error(medebug,'Failed to load script',err);
					return false;
				}
			}
			tuner = new spotifyRadio();
			try {
				var fartists = await $.ajax({
					url: "api/metadata/query/",
					type: "POST",
					contentType: false,
					data: JSON.stringify({action: 'getfaveartists'}),
					dataType: 'json'
				});
				if (fartists.length == 0) {
					debug.warn(medebug, 'Got no fartists');
					return false;
				}
				fartists.sort(randomsort);
				fartists = fartists.splice(0,10);
				fartists.forEach(function(artist) {
					debug.trace(medebug,"Searching for spotify ID for",artist.name);
					spotify.artist.search(artist.name, mixRadio.gotArtists, mixRadio.lookupFail);
				});
			} catch(err) {
				debug.error(medebug, 'Error getting fartists',err);
				return false;
			}
		},

		getURIs: async function(numtracks) {
			var t = await tuner.getTracks(numtracks);
			return t;
		},

		stop: function() {
			tuner = null;
		},

		modeHtml: function(p) {
			return '<i class="icon-artist modeimg"/></i><span class="modespan">'+language.gettext("label_radio_mix")+'</span>';
		},

		lookupFail: function() {
			debug.warn(medebug, "Failed to lookup artist");
		},

		gotArtists: function(data) {
			debug.debug(medebug,"Got artist search results",data);
			// To see which search result artist matches the one we actually searched for,
			// check the original query string. This was encoded according to spotify.js
			// name.replace(/&|%|@|:|\+|'|\\|\*|"|\?|\//g,'').replace(/\s+/g,'+')
			var orig_search = data.artists.href.match(/query=(.+?)&/);
			var to_match = 'fuckedup';
			if (orig_search && orig_search[1]) {
				to_match = orig_search[1].replace(/\++/g, ' ').toLowerCase();
			}
			for (let artist of data.artists.items) {
				var match_against = artist.name.replace(/&|%|@|:|\+|'|\\|\*|"|\?|\//g,'').toLowerCase();
				if (to_match == match_against) {
					debug.trace(medebug, 'Found artist match for',to_match,artist.name);
					tuner.newArtist(artist.name, artist.id, true);
					return;
				}
			};
		}
	}
}();

playlist.radioManager.register("mixRadio", mixRadio, null);
