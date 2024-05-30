var lastFMTrackRadio = function() {

	var mode;
	var param;

	return {
		initialise: function(m, p) {
			mode = m;
			param = p;
		},

		getURIs: async function() {
			try {
				var response = await fetch(
					"radios/api/starRadios.php",
					{
						signal: AbortSignal.timeout(60000),
						body: JSON.stringify({radiomode: mode, radioparam: param}),
						cache: 'no-store',
						method: 'POST',
						priority: 'high',
					}
				);
				if (!response.ok) {
					var t = await response.text();
					var msg = t ? t : response.status+' '+response.statusText;
					throw new Error(msg)
				}
			} catch (err) {
				debug.error('STARRADIOS', 'Error getting tracks', err);
				return false;
			}
			return true;
		},

		modeHtml: function() {
			return '<i class="icon-lastfm-1 modeimg"/></i><span class="alignmid bold">'+language.gettext('label_lastfm_mix_'+param)+'</span>';
		},

	}

}();

playlist.radioManager.register("lastFMTrackRadio",lastFMTrackRadio,null);
