function spotifyRecommendationsRadio() {

	var self = this;
	var tracks = new Array();

	this.populate = function(params, numtracks) {
		params.limit = 100;
		debug.log("SPOTIFY RECC","Getting recommendations",params);
		spotify.recommendations.getRecommendations(params,
			function(data) {
				self.gotRecommendations(data, numtracks)
			},
			self.spotiError
		);
	}

	this.spotiError = function(data) {
		debug.warn("SPOTIFY RECC","Error!",data);
		infobar.error(language.gettext('label_gotnotracks'));
        playlist.radioManager.stop(null);
	}

	this.sendTracks = function(num) {
		var t = new Array();
		while (num > 0 && tracks.length > 0) {
			t.push({type: 'uri', name: tracks.shift()});
			num--;
		}
        if (t.length > 0) {
            player.controller.addTracks(t, playlist.radioManager.playbackStartPos(), null);
        } else {
        	debug.warn("SPOTIFY RECC","Out of Tracks!");
        	infobar.notify('<i class="icon-spotify-circled modeimg"/></i>'+language.gettext('label_outoftracks'));
            playlist.radioManager.stop(null);
        }
	}

	this.gotRecommendations = function(data, numtracks) {
		debug.log("SPOTIFY RECC","Got Stuff!",data);
		if (data.tracks) {
			for (var i in data.tracks) {
				tracks.push(data.tracks[i].uri);
			}
		}
		if (tracks.length > 0) {
			tracks.sort(randomsort);
			self.sendTracks(numtracks);
		} else {
			infobar.error(language.gettext('label_gotnotracks'));
            playlist.radioManager.stop(null);
		}
	}

}
