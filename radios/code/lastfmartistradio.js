var lastFMArtistRadio = function() {

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
					url: "radios/api/lastFmTrackRadio.php",
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

		modeHtml: function() {
			return '<i class="icon-lastfm-1 modeimg"/></i><span class="alignmid bold">'+language.gettext('label_lastfm_dip_'+param)+'</span>';
		},

	}

}();

playlist.radioManager.register("lastFMArtistRadio",lastFMArtistRadio,null);
