var faveArtistRadio = function() {

	var tuner;
	var medebug = "FAVE ARTIST RADIO";

	return {

		initialise: async function(p) {
			if (typeof(searchRadio) == 'undefined') {
				debug.log(medebug,"Loading Search Radio Tuner");
				try {
					await $.getScript('radios/code/searchRadio.js?version='+rompr_version);
				} catch(err) {
					debug.error(medebug,'Failed to load script',err);
					return false;
				}
			}
			tuner = new searchRadio();
			try {
				var fartists = await $.ajax({
					url: "backends/sql/userRatings.php",
					type: "POST",
					contentType: false,
					data: JSON.stringify([{action: 'getfaveartists'}]),
					dataType: 'json'
				});
				if (fartists.length == 0) {
					debug.warn(medebug, 'Got no fartists');
					return false;
				}
				fartists.forEach(function(artist) {
					tuner.newArtist(artist.name);
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
			return '<i class="icon-artist modeimg"/></i><span class="modespan">'+language.gettext("label_radio_fartist")+'</span>';
		}
	}
}();

playlist.radioManager.register("faveArtistRadio", faveArtistRadio, null);
