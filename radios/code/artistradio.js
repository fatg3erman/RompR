var artistRadio = function() {

	var medebug = "ARTIST RADIO";
	var artistname = null;
	var artistindex = null;
	var tuner;

	function getArtistName(id) {
		spotify.artist.getInfo(id, artistRadio.gotArtistName, artistRadio.noName);
	}

	function searchForArtist(name) {
		spotify.artist.search(name, artistRadio.gotArtists, artistRadio.fail);
	}

	return {

		initialise: async function(artist) {
			if (typeof(spotifyRadio) == 'undefined') {
				debug.info(medebug,"Loading Spotify Radio Tuner");
				try {
					await $.getScript('radios/code/spotifyRadio.js?version='+rompr_version);
				} catch (err) {
					debug.error(medebug,'Failed to load script',err);
					return false;
				}
			}
			tuner = new spotifyRadio();
			if (artist.substr(0,15) == "spotify:artist:") {
				artistindex = artist;
				getArtistName(artist.substr(15,artist.length));
			} else {
				debug.trace(medebug,"Searching for artist",artist);
				artistname = artist;
				searchForArtist(artist);
			}
		},

		getURIs: async function(numtracks) {
			while (artistname === null && artistindex === null) {
				await new Promise(t => setTimeout(t, 500));
			}
			if (artistindex === false) {
				return false;
			}
			var t = await tuner.getTracks(numtracks);
			return t;
		},

		modeHtml: function() {
			if (artistname) {
				a = artistname;
			} else {
				a = '';
			}
			return '<i class="icon-wifi modeimg"/></i><span class="modespan">'+a+' '+language.gettext("label_radio")+'</span>';
		},

		stop: function() {
			tuner = null;
			artistname = null;
			artistindex = null;
		},

		gotArtists: function(data) {
			for (let artist of data.artists.items) {
				if (artist.name.removePunctuation().toLowerCase() == artistname.removePunctuation().toLowerCase()) {
					artistindex = artist.id;
					tuner.newArtist(artistname, artist.id, true);
					return;
				}
			};
			artistRadio.fail();
		},

		gotArtistName: function(data) {
			artistname = data.name;
			tuner.newArtist(artistname, data.id, true);
		},

		noName: function() {
			debug.warn(medebug,"Couldn't find artist name");
			artistname = false;
		},

		fail: function() {
			debug.error(medebug,"Failed to find artist id");
			infobar.notify(language.gettext('label_gotnotracks'));
			artistindex = false;
		}

	}

}();

playlist.radioManager.register("artistRadio", artistRadio, null);
