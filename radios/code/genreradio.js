var genreRadio = function() {

	var populating = false;
	var running = false;
	var genre;
	var tracks;
	var tracksneeded = 0;

	function searchForTracks(genre) {
		if (populating) {
			debug.warn("GENRE RADIO","Asked to populate but already doing so!");
			return false;
		}
		populating = true;
		var domains = new Array();
		if (prefs.player_backend == "mopidy") {
			domains = $("#radiodomains").makeDomainChooser("getSelection");
		}
		debug.shout("GENRE RADIO","Searching for Genre",genre,"in domains",domains);
		player.controller.rawsearch({genre: [genre]}, domains, false, genreRadio.checkResults, false);
	}

	function sendTracks() {
		if (running) {
			var ta = new Array();
			while (tracks.length > 0 && tracksneeded > 0) {
				ta.push(tracks.shift());
				tracksneeded--;
			}
			if (ta.length > 0) {
				player.controller.addTracks(ta, playlist.radioManager.playbackStartPos(), null);
			}
		}
	}

	return {

		populate: function(g,numtracks) {
			if (g && g != genre) {
				debug.log("GENRE RADIO","Populating Genre",g);
				running = true;
				tracks = new Array();
				genre = g;
				searchForTracks(g);
				tracksneeded = numtracks;
			} else {
				debug.log("GENRE RADIO","Repopulating");
				tracksneeded += (numtracks - tracksneeded);
				sendTracks();
			}
		},

		checkResults: function(data) {
			debug.log("GENRE RADIO","Search Results",data);
			running = true;
			for (var i in data) {
				if (data[i].tracks) {
					for (var k = 0; k < data[i].tracks.length; k++) {
						if (!data[i].tracks[k].uri.match(/:album:/) && !data[i].tracks[k].uri.match(/:artist:/)) {
							tracks.push({type: 'uri', name: data[i].tracks[k].uri});
						}
					}
				}
			}
			if (tracks.length == 0) {
				genreRadio.fail();
				return;
			}
			tracks.sort(randomsort);
			sendTracks();
		},

		fail: function() {
			debug.error("GENRE RADIO","Well, that didn't work");
            infobar.notify(infobar.NOTIFY,language.gettext('label_gotnotracks'));
            playlist.radioManager.stop();
		},

		stop: function() {
			populating = false;
			genre = null;
		},

		modeHtml: function(g) {
            return '<i class="icon-wifi modeimg"/></i><span class="modespan ucfirst">'+g+' '+language.gettext('label_radio')+'</span>';
		}

	}

}();

playlist.radioManager.register("genreRadio", genreRadio, null);

