var recentlyaddedtracks = function() {

	var mode;
	var param;

	return {

		initialise: async function(m, p) {
			mode = m;
			param = p;
			return true;
		},

		getURIs: async function(numtracks) {
			try {
				var t = await $.ajax({
					url: "radios/api/starRadios.php",
					type: "POST",
					contentType: false,
					data: JSON.stringify({radiomode: mode, radioparam: param}),
					dataType: 'json'
				});
			} catch(err) {
				debug.error('STARRADIOS', 'Error getting tracks',err);
				return false;
			}
			return true;
		},

		modeHtml: function() {
			return '<i class="icon-recentlyplayed modeimg"></i><span class="alignmid bold">'+language.gettext("label_"+param)+'</span>&nbsp;';
		}

	}

}();

playlist.radioManager.register("recentlyaddedtracks", recentlyaddedtracks, null);
