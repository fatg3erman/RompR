var artistRadio = function() {

	// Uses Spotify Web API

	var artistname;
	var artistindex;
	var tuner;

	function getArtistName(id) {
		spotify.artist.getInfo(id, artistRadio.gotArtistName, artistRadio.fail);
	}

	function searchForArtist(name) {
		spotify.artist.search(name, artistRadio.gotArtists, artistRadio.fail);
	}

	return {

		populate: function(artist, numtracks) {
			if (artist && artist != artistname && artist != artistindex) {
				debug.shout("ARTIST RADIO","Populating with",artist,artistname);
				if (typeof(spotifyRadio) == 'undefined') {
					debug.log("ARTIST RADIO","Loading Spotify Radio Tuner");
					$.getScript('radios/code/spotifyRadio.js?version='+rompr_version,function() {
						artistRadio.actuallyGo(artist, numtracks)
					});
				} else {
					artistRadio.actuallyGo(artist, numtracks);
				}
			} else {
				debug.log("ARTIST RADIO", "Repopulating");
				tuner.sending += (numtracks - tuner.sending);
				tuner.startSending();
			}
		},

		actuallyGo: function(artist, numtracks) {
			tuner = new spotifyRadio();
			tuner.sending = numtracks;
			tuner.running = true;
			tuner.artistindex = 0;
			if (artist.substr(0,15) == "spotify:artist:") {
				artistindex = artist;
				getArtistName(artist.substr(15,artist.length));
			} else {
				debug.shout("ARTIST RADIO","Searching for artist",artist);
				artistname = artist;
				searchForArtist(artist);
			}
		},

		modeHtml: function(a) {
			if (a.substr(0,15) == "spotify:artist:") {
				if (artistname) {
					a = artistname;
				} else {
					a= '';
				}
			}
			return '<i class="icon-wifi modeimg"/></i><span class="modespan">'+a+' '+
				language.gettext("label_radio")+'</span>';
		},

		stop: function() {
			tuner.sending = 0;
			tuner.running = false;
			artistname = "";
			artistindex = "";
		},

		gotArtists: function(data) {
			for (var i in data.artists.items) {
				if (data.artists.items[i].name.removePunctuation().toLowerCase() == artistname.removePunctuation().toLowerCase()) {
					artistindex = data.artists.items[i].id;
					tuner.newArtist(artistname, data.artists.items[i].id, true);
					tuner.startSending();
					return;
				}
			}
			artistRadio.fail();
		},

		gotArtistName: function(data) {
			artistname = data.name;
			tuner.newArtist(artistname, data.id, true);
			tuner.startSending();
		},

		fail: function(data) {
            debug.error("ARTIST RADIO","Failed to create playlist",data);
            infobar.notify(language.gettext('label_gotnotracks'));
            playlist.radioManager.stop(null);
		}

	}

}();

playlist.radioManager.register("artistRadio", artistRadio, null);
