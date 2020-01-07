var lastFMArtistRadio = function() {

	var medebug = 'LFM ARTIST RADIO';
	var param = null;
	var minplays;
	var tuner;
	var artistcount = 0;

	function getTopArtists(page) {
		switch (param) {
			case 'overall':
				minplays = 7;
				break

			case '12month':
				minplays = 5;
				break;

			case '6month':
				minplays = 4;
				break;

			case '3month':
				minplays = 3;
				break;

			case '1month':
				minplays = 2;
				break;

			default:
				minplays = 1;
				break;

		}
		lastfm.user.getTopArtists(
			{period: param,
			page: page},
			lastFMArtistRadio.gotTopArtists,
			lastFMArtistRadio.lfmerror
		);
	}

	return {

		initialise: async function(p) {
			if (typeof(searchRadio) == 'undefined') {
				debug.log(medebug,"Loading Search Radio Tuner");
				try {
					await $.getScript('radios/code/searchRadio.js?version='+rompr_version);
				} catch(err) {
					debug.error(medebug,'Failed to load script',err);
					return false;
				}
			}
			tuner = new searchRadio();
			param = p;
			getTopArtists(1);
		},

		getURIs: async function(numtracks) {
			while (artistcount === 0) {
				await new Promise(t => setTimeout(t, 500));
			}
			if (artistcount === false) {
				return false;
			}
			var t = await tuner.getTracks(numtracks);
			return t;
		},

		lfmerror: function(data) {
			debug.warn(medebug,"Last.FM Error",data);
			if (artistcount == 0) {
				artistcount = false;
			}
		},

		modeHtml: function() {
			return '<i class="icon-lastfm-1 modeimg"/></i><span class="modespan">'+language.gettext('label_lastfm_dip_'+param)+'</span>';
		},

		stop: function() {
			param = null;
			tuner = null;
		},

		gotTopArtists: function(data) {
			if (data.topartists.artist && tuner.hasSpace()) {
				currpage = parseInt(data.topartists['@attr'].page);
				totalpages = parseInt(data.topartists['@attr'].totalPages);
				debug.log(medebug,"Got Page",currpage,"Of",totalpages,"Of Top Artists");
				for (let artist of data.topartists.artist) {
					if (artist.playcount >= minplays) {
						artistcount++;
						tuner.newArtist(artist.name);
						lastfm.artist.getSimilar(
							{artist: artist.name},
							lastFMArtistRadio.gotASimilar,
							lastFMArtistRadio.gotNoSimilar
						);
					} else {
						debug.log(medebug, 'Ignoring artist',artist.name,'as playcount of',artist.playcount,'is less than',minplays);
					}
				}
				if (currpage < totalpages) {
					getTopArtists(currpage+1);
				}
				return;
			}
			if (artistcount == 0) {
				artistcount = false;
			}
		},

		gotASimilar: function(data) {
			if (data.similarartists) {
				debug.info(medebug,"Got Similar Artists For",data.similarartists['@attr'].artist);
				if (data.similarartists.artist) {
					for (var artist of data.similarartists.artist) {
						tuner.newArtist(artist.name);
					}
				}
			}
		},

		gotNoSimilar: function() {

		}

	}

}();

playlist.radioManager.register("lastFMArtistRadio",lastFMArtistRadio,null);
