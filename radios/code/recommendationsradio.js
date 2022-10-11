var recommendationsRadio = function() {

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
			return '<i class="icon-wifi modeimg"/></i><span class="alignmid bold">'+language.gettext("label_radio_recommended")+'</span>';
		}
	}
}();

playlist.radioManager.register("recommendationsRadio", recommendationsRadio, null);
