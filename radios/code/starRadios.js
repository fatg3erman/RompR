var starRadios = function() {

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
			if (param.match(/^\dstars/)) {
				var cn = param.replace(/(\d)/, 'icon-$1-');
				return '<i class="'+cn+' rating-icon-small"></i>';
			} else if (param == "neverplayed" || param == "allrandom" || param == "recentlyplayed" || param == "randomalbums") {
				return '<i class="icon-'+param+' modeimg"/></i><span class="alignmid bold">'+language.gettext('label_'+param)+'</span>';
			} else if (param == 'mostplayed' || param == 'favealbums') {
				return '<i class="icon-music modeimg"/></i><span class="alignmid bold">'+language.gettext('label_'+param)+'</span>';
			} else if (param == 'recentlyadded_random' || param == 'recentlyadded_byalbum') {
				return '<i class="icon-recentlyplayed modeimg"/></i><span class="alignmid bold">'+language.gettext('label_'+param)+'</span>';
			} else if (/^tag\+/.test(param)) {
				return '<i class="icon-tags modeimg"></i><span class="alignmid bold">'+param.replace(/^tag\+|^genre\+|^artist\+/, '')+'</span>';
			} else if (/^genre\+/.test(param)) {
				return '<i class="icon-music modeimg"></i><span class="alignmid bold">'+param.replace(/^tag\+|^genre\+|^artist\+/, '')+'</span>';
			} else if (/^artist\+/.test(param)) {
				return '<i class="icon-artist modeimg"></i><span class="alignmid bold">'+param.replace(/^tag\+|^genre\+|^artist\+/, '')+'</span>';
			} else if (/^custom\+/.test(param)) {
				return '<i class="icon-wifi modeimg"></i><span class="alignmid bold">'+param.replace(/^custom\+/, '')+'</span>';
			} else {
				return '<i class="icon-wifi modeimg"></i>';
			}
		}

	}
}();

playlist.radioManager.register("starRadios", starRadios, null);
