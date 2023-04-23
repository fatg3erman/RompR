var spotiRecRadio = function() {

	return {
		setup: function() {
			if (player.canPlay('spotify')) {
				//
				// Spotify Weekly Mix
				//
				$('#pluginplaylists_spotify').append(playlist.radioManager.standardBox('spotiRecRadio', 'mix', 'icon-spotify-circled', language.gettext('label_spotify_mix')));
				//
				// Spotify Swim
				//
				$('#pluginplaylists_spotify').append(playlist.radioManager.standardBox('spotiRecRadio', 'swim', 'icon-spotify-circled', language.gettext('label_spotify_dj')));
				//
				// Spotify Surprise
				//
				$('#pluginplaylists_spotify').append(playlist.radioManager.standardBox('spotiRecRadio', 'surprise', 'icon-spotify-circled', language.gettext('label_spottery_lottery')));
			}
		}
	}
}();

playlist.radioManager.register("spotiRecRadio", spotiRecRadio, 'radios/code/spotirecradio.js');

