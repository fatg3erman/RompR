var singleArtistRadio = function() {

	var tuner;
	var artist;
	var medebug = 'SINGLEARTIST RADIO';

	return {

		initialise: async function(p) {
			artist = p;
			if (typeof(searchRadio) == 'undefined') {
				debug.info(medebug,"Loading Search Radio Tuner");
				try {
					await $.getScript('radios/code/searchRadio.js?version='+rompr_version);
				} catch(err) {
					debug.error(medebug,'Failed to load script',err);
					return false;
				}
			}
			tuner = new searchRadio();
			tuner.newArtist(artist);
		},

		getURIs: async function(numtracks) {
			var t = await tuner.getTracks(numtracks);
			return t;
		},

		stop: function() {
			tuner = null;
			artist = null;
		},

		modeHtml: function() {
			return '<i class="icon-artist modeimg"/></i><span class="modespan ucfirst">'+artist+" "+language.gettext("label_radio")+'</span>';
		}
	}
}();

playlist.radioManager.register("singleArtistRadio", singleArtistRadio);
