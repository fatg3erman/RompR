var mixRadio = function() {

	var fartists = new Array();
	var idhunting = -1;
	var populating = false;
	var tuner;
	var going = false;

	function getFaveArtists() {
		if (populating) {
			debug.warn("MIX RADIO","Asked to populate but already doing so!");
			return false;
		}
		populating = true;
		idhunting = -1;
		metaHandlers.genericAction(
			'getfaveartists',
			function(data) {
                if (data.length > 0) {
                    debug.mark("MIX RADIO","Got Fave Artists",data);
                    data.sort(randomsort);
                    for (var i in data) {
                    	if (data[i].name && data[i] !== "" && fartists.length <= 10) {
                    		fartists.push({name: data[i].name});
                    	}
                	}
                	searchForNextArtist();
                } else {
	                infobar.notify(language.gettext('label_gotnotracks'));
                	debug.warn("MIX RADIO", "No Fave Artists Returned", data);
                    playlist.radioManager.stop(null);
                }
            },
            function(data) {
            	debug.error("MIX RADIO", "Failed to get artists", data);
                infobar.notify(language.gettext('label_gotnotracks'));
                playlist.radioManager.stop(null);
            }
        );
	}

	function searchForNextArtist() {
		idhunting++;
		if (idhunting < fartists.length) {
			debug.shout("MIX RADIO","Searching for spotify ID for",idhunting,fartists.length,fartists[idhunting].name);
			spotify.artist.search(fartists[idhunting].name, mixRadio.gotArtists,
				mixRadio.lookupFail);
		}
	}

	return {

		populate: function(p, numtracks) {
			if (!populating) {
				debug.shout("MIX RADIO","Populating");
				if (typeof(spotifyRadio) == 'undefined') {
					debug.log("ARTIST RADIO","Loading Spotify Radio Tuner");
					$.getScript('radios/code/spotifyRadio.js?version='+rompr_version,function() {
						mixRadio.actuallyGo(numtracks)
					});
				} else {
					mixRadio.actuallyGo(numtracks);
				}
			} else {
				debug.shout("MIX RADIO","RePopulating");
				tuner.sending += (numtracks - tuner.sending);
				tuner.startSending();
			}
		},

		actuallyGo: function(numtracks) {
			fartists = new Array();
			tuner = new spotifyRadio();
			tuner.sending = numtracks;
			tuner.running = true;
			tuner.artistindex = 0;
			numfartists = 0;
			going = false;
			getFaveArtists();
		},

		stop: function() {
			if (tuner) {
				tuner.sending = 0;
				tuner.running = false;
			}
			populating = false;
		},

		modeHtml: function(p) {
			return '<i class="icon-artist modeimg"/></i><span class="modespan">'+language.gettext("label_radio_mix")+'</span>';
		},

		lookupFail: function() {
			debug.warn("MIX RADIO","Failed to lookup artist");
			searchForNextArtist();
		},

        gotArtists: function(data) {
        	debug.shout("MIX RADIO","Got artist search results",data);
        	var found = false;
        	for (var i in data.artists.items) {
        		check: {
	        		for (var j in tuner.artists) {
	        			if (tuner.artists[j].getName() == data.artists.items[i].name) {
	        				debug.shout("MIX RADIO", "Ignoring artist",data.artists.items[i].name,"because it already exists");
	        				found = true;
	        				break check;
	        			}
	        		}
	        		if (data.artists.items[i].name.toLowerCase() ==
	        				fartists[idhunting].name.toLowerCase()) {
	        			debug.shout("MIX RADIO","Found Spotify ID for artist",idhunting,fartists[idhunting].name);
	        			tuner.newArtist(data.artists.items[i].name, data.artists.items[i].id, true);
	    				found = true;
	    				if (!going) {
	    					going = true;
	    					tuner.startSending();
	    				}
	    				break;
	    			}
    			}
        	}
        	if (!found) {
        		debug.shout("MIX RADIO","Failed to find Spotify ID for artist",
        			fartists[idhunting].name);
        	}
        	searchForNextArtist();
        },
	}
}();

playlist.radioManager.register("mixRadio", mixRadio, null);
