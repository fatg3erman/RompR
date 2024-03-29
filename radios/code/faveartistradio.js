var faveArtistRadio = function() {

	var mode;
	var param;

	return {
		initialise: function(m, p) {
			mode = m;
			param = p;
		},

		getURIs: async function() {
			try {
				var t = await $.ajax({
					url: "radios/api/starRadios.php",
					type: "POST",
					contentType: false,
					data: JSON.stringify({radiomode: mode, radioparam: param}),
					dataType: 'json'
				});
			} catch (err) {
				debug.error('STARRADIOS', 'Error getting tracks', err);
				return false;
			}
			return true;
		},

		modeHtml: function(p) {
			if (param) {
				return '<i class="icon-artist modeimg"/></i><span class="alignmid bold">'+param+' '+language.gettext("label_radio")+'</span>';
			} else {
				return '<i class="icon-artist modeimg"/></i><span class="alignmid bold">'+language.gettext("label_radio_fartist")+'</span>';
			}
		}
	}
}();

playlist.radioManager.register("faveArtistRadio", faveArtistRadio, null);
