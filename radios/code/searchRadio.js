function searchRadio() {

	var self = this;
	this.artists = new Array();
	this.running = false;
	this.sending = 0;
	this.artistindex = 0;
	var populatetimer = null;

	function searchArtist(name) {
		debug.trace("SEARCHRADIO ARTIST","Creating",name);
		var tracks = null;
		var myself = this;
		this.populated = false;

		this.populate = function() {
			if (!myself.populated) {
				debug.log("SEARCHRADIO ARTIST","Getting tracks for",name);
				// Don't limit domains at the search stage - this allows the user to
				// change the dmoain selection afterwards and we'll honour it.
				player.controller.rawsearch({artist: [name]}, [], false, myself.gotTracks, false);
				myself.populated = true;
			}
		}

		this.gotTracks = function(data) {
			debug.trace("SEARCHRADIO ARTIST","Got Tracks",data);
			tracks = new Array();
			for (var j in data) {
				for (var k in data[j].tracks) {
					if (!data[j].tracks[k].uri.match(/:album:/) &&
						!data[j].tracks[k].uri.match(/:artist:/)) {
						tracks.push({type: 'uri', name: data[j].tracks[k].uri});
					}
				}
			}
			if (tracks.length > 0) {
				tracks = tracks.sort(randomsort);
				debug.log("SEARCHRADIO ARTIST","Got",tracks.length,"tracks for",name);
				if (self.sending > 0) {
					myself.sendATrack();
				}
			}
		}

		this.sendATrack = function() {
			if (myself.populated) {
				var sent = false;
				while (self.running && tracks && tracks.length > 0) {
					var t = tracks.shift();
					if (prefs.player_backend == "mopidy") {
						var n = $("#radiodomains").makeDomainChooser("getSelection");
						var d = t.name.substr(0, t.name.indexOf(':'));
						if (n.indexOf(d) == -1) {
							continue;
						}
					}
					debug.log("SEARCHRADIO ARTIST",name,"is sending a track");
					sent = true;
					self.sending--;
	        		player.controller.addTracks([t], playlist.radioManager.playbackStartPos(), null);
	        		break;
	        	}
	        	if (!sent) {
	        		debug.shout("SEARCHRADIO ARTIST",name,"doesn't have any tracks");
	        		// For playlist to repopulate to make someone else have a go
	        		playlist.repopulate();
	        	}
	        } else {
	        	myself.populate();
	        }
		}

		this.getName = function() {
			return name;
		}
	}

	this.newArtist = function(name) {
		ac: {
			for (var j in self.artists) {
				if (self.artists[j].getName == name) {
					debug.mark("SEARCHRADIO","Ignoring artist",name,"because it already exists");
					break ac;
				}
			}
			self.artists.push(new searchArtist(name));
		}
	}

	this.startSending = function() {
		if (self.sending > 0) {
			self.artistindex = Math.floor(Math.random() * self.artists.length);
			self.artists[self.artistindex].sendATrack();
		}
	}
}