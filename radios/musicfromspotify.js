var spotiMixRadio = function() {

	return {
		setup: function() {
			if (player.canPlay('spotify')) {

				//
				// Spotify Weekly Mix
				// Spotify Swim
				// Spotify Surprise!
				//
				$('#pluginplaylists_spotify').append(playlist.radioManager.standardBox('spotiMixRadio', '7day', 'icon-spotify-circled', language.gettext('label_spotify_mix')));
				$('#pluginplaylists_spotify').append(playlist.radioManager.standardBox('spotiMixRadio', '1year', 'icon-spotify-circled', language.gettext('label_spotify_dj')));
				$('#pluginplaylists_spotify').append(playlist.radioManager.standardBox('spotiMixRadio', 'surprise', 'icon-spotify-circled', language.gettext('label_spottery_lottery')));

			}
		}

	}

}();

var mixRadio = function() {

	return {

		setup: function() {

			if (player.canPlay('spotify')) {
				//
				// Favourite Artists and Related Artists (Music from Spotify)
				//
				$('#pluginplaylists_spotify').append(playlist.radioManager.standardBox('mixRadio', null, 'icon-artist', language.gettext('label_radio_mix')));
			}
		}
	}
}();


var artistRadio = function() {

	return {

		setup: function() {
			if (player.canPlay('spotify')) {
				//
				// Artists Similar To (Music From Spotify)
				//
				$('#pluginplaylists_spotify').append(playlist.radioManager.textEntry('icon-spotify-circled', language.gettext('label_simar_radio'), 'simar_radio'));
				$('button[name="simar_radio"]').on('click', function() {
					var v = $('#simar_radio').val();
					if (v != '') {
						playlist.radioManager.load('artistRadio', v);
					}
				});
			}
		}
	}
}();

var spotiTrackRadio = function() {
	return {
		setup: function() {
			debug.trace("SPTR","Nothing to see here");
		}
	}
}();


playlist.radioManager.register("spotiMixRadio", spotiMixRadio, 'radios/code/spotimixradio.js');
playlist.radioManager.register("mixRadio", mixRadio, 'radios/code/mixradio.js');
playlist.radioManager.register("artistRadio", artistRadio, 'radios/code/artistradio.js');
playlist.radioManager.register("spotiTrackRadio", spotiTrackRadio, 'radios/code/spotiTrackRadio.js');
