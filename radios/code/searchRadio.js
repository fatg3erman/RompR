function searchRadio() {

	var self = this;
	var artists = new Array();
	var medebug = 'SEARCHRADIO';
	var trackfinder = new faveFinder(true);
	trackfinder.setCheckDb(false);

	function searchArtist(name) {
		debug.debug(medebug,"Creating",name);
		var tracks = null;
		var myself = this;

		this.sendATrack = async function() {
			if (tracks === null) {
				debug.debug(medebug, 'Getting tracks for',name);
				if (prefs.player_backend == "mopidy") {
					// Do this here in case they get changed after the radio is started
					trackfinder.setPriorities($("#radiodomains").makeDomainChooser("getSelection"));
				}
				trackfinder.findThisOne(
					{
						Title: null,
						trackartist: name
						// duration: 0,
						// albumartist: name,
						// date: 0
					},
					myself.gotTracks
				);
			}
			while (tracks === null) {
				await new Promise(t => setTimeout(t, 500));
			}
			return tracks.shift();
		}


		this.gotTracks = function(data) {
			debug.debug(medebug,"Got Tracks",data);
			tracks = new Array();
			for (let track of data) {
				if (track.file) {
					tracks.push({type: 'uri', name: track.file});
				}
			}
			tracks.sort(randomsort);
			debug.trace(medebug,"Got",tracks.length,"tracks for",name);
		}

		this.getName = function() {
			return name;
		}
	}

	this.getTracks = async function(numtracks) {
		while (artists.length === 0) {
			await new Promise(t => setTimeout(t, 500));
		}
		debug.log(medebug, 'We have',artists.length,'artists');
		while (artists.length > 0) {
			var artistindex = Math.floor(Math.random() * (artists.length - 1));
			debug.debug(medebug,'artistindex is',artistindex,'length is',artists.length);
			var track = await artists[artistindex].sendATrack();
			if (track) {
				// Return tracks one at a time, otherwise there's a really long wait when we first start up
				return [track];
			} else {
				debug.trace(medebug, 'Deleting artist',artistindex);
				artists.splice(artistindex, 1);
			}
		}
		return [];
	}

	this.newArtist = function(name) {
		if (artists.length > 500) {
			debug.info(medebug, 'We have enough artists');
			return false;
		}
		for (let artist of artists) {
			if (artist.getName() == name) {
				debug.debug(medebug, 'Ignoring artist',name,'as it already exists');
				return false;
			}
		}
		artists.push(new searchArtist(name));
	}

	this.hasSpace = function() {
		return (artists.length < 500);
	}

}
