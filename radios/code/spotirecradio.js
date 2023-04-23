var spotiRecRadio = function() {

	var mode;
	var param;
	var stationname;

	return {
		initialise: function(m, p) {
			mode = m;
			var pongothehorse = [];
			switch (p) {
				case 'mix':
				case 'swim':
				case 'surprise':
					param = p;
					break;

				default:
					for (var i in p) {
						if (i.match(/seed_/)) {
							pongothehorse.push(i+':'+p[i]);
						} else if (i == 'name') {
							stationname = p[i]+" "+language.gettext("label_radio");
							prefs.save({stationname: stationname});
						}
					}
					param = pongothehorse.join(';');
					break;

			}
			debug.log('PONGO', mode, param);
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

		modeHtml: function() {
			var name;
			switch (param) {
				case 'mix':
					name = language.gettext('label_spotify_mix');
					break;

				case 'swim':
					name = language.gettext('label_spotify_dj');
					break;

				case 'surprise':
					name = language.gettext('label_spottery_lottery');
					break;

				default:
					if (stationname) {
						name = stationname;
					} else {
						name = player.status.smartradio.stationname;
					}
					break;

			}
			return '<i class="icon-spotify modeimg"/></i><span class="alignmid bold ucfirst">'+name+'</span>';
		}

	}

}();

playlist.radioManager.register("spotiRecRadio", spotiRecRadio, null);
