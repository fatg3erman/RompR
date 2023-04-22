var spotiRecRadio = function() {

	var mode;
	var param;
	var name;

	return {
		initialise: function(m, p) {
			mode = m;
			var pongothehorse = [];
			for (var i in p) {
				if (i.match(/seed_/)) {
					pongothehorse.push(i+':'+p[i]);
				} else if (i =='name') {
					name = p[i];
				}
			}
			param = pongothehorse.join(';');
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
			return '<i class="icon-spotify modeimg"/></i><span class="alignmid bold ucfirst">'+name+" "+language.gettext("label_radio")+'</span>';
		}

	}

}();

playlist.radioManager.register("spotiRecRadio", spotiRecRadio, null);
