function spotifyRadio() {

	// Uses Spotify Web API

	var self = this;
	this.sending = 0;
	this.artists = new Array();
	this.running = false;
	this.artistindex = 0;

	function mixArtist(name, id, findrelated) {
		debug.trace("SPOTIRADIO ARTIST","Creating",name,id);
		var albums = null;
		var myself = this;

		this.gotSomeAlbums = function(data) {
			debug.trace("SPOTIRADIO ARTIST","Got albums for",name,data);
			albums = new mixAlbum(name, data.items);
			if (self.running && self.sending > 0) {
				albums.sendATrack();
			}
		}

		this.gotRelatedArtists = function(data) {
			debug.trace("SPOTIRADIO ARTIST","Got related artists for",name,data);
			for (var i in data.artists) {
				ac: {
					for (var j in self.artists) {
						if (self.artists[j].getName() == data.artists[i].name) {
							debug.shout("SPOTIRADIO", "Ignoring artist",data.artists[i].name,
								"because it already exists");
							break ac;
						}
					}
					self.artists.push(new mixArtist(data.artists[i].name, data.artists[i].id, false));
				}
			}
		}

		this.failQuiet = function(data) {
			debug.warn("SPOTIRADIO ARTIST", "Spotify Error On",name,data);
		}

		this.sendATrack = function() {
			if (albums === null && self.running) {
				spotify.artist.getAlbums(id, 'album', myself.gotSomeAlbums, myself.failQuiet);
				debug.mark("SPOTIRADIO ARTIST", "Getting Related Artists For",name);
				spotify.artist.getRelatedArtists(id, myself.gotRelatedArtists, myself.failQuiet);
			} else {
				albums.sendATrack();
			}
		}

		this.getName = function() {
			return name;
		}

	}

	function mixAlbum(name, items) {
		var myself = this;
		debug.mark("SPOTIRADIO ALBUM", "Getting tracks for artist",name);
		var tracks = new Array();
		var ids = new Array();
		for (var i in items) {
			ids.push(items[i].id);
		}

		this.gotTracks = function(data) {
			debug.log("SPOTIRADIO ALBUM", "Got Tracks For",name,data);
			for (var i in data.albums) {
				for (var j in data.albums[i].tracks.items) {
					tracks.push({type: 'uri', name: data.albums[i].tracks.items[j].uri});
				}
			}
			tracks.sort(randomsort);
			if (self.sending > 0 && self.running) {
				myself.sendATrack();
			}
		}

		this.failQuiet = function(data) {
			debug.warn("SPOTIRADIO ALBUM", "Spotify Error On",name,data);
		}

		this.sendATrack = function() {
			if (self.running && tracks.length > 0 && self.sending > 0) {
				self.sending--;
				debug.shout("SPOTIRADIO ALBUM",name,"is sending a track!",self.sending,"left");
				player.controller.addTracks([tracks.shift()], playlist.radioManager.playbackStartPos(), null, true);
			} else {
			// while (ids.length > 0) {
				ids.sort(randomsort);
				var temp = new Array();
				while (ids.length > 0 && temp.length < 20) {
					// Can only multi-query 20 albums at a time.
					temp.push(ids.shift());
				}
				spotify.album.getMultiInfo(temp, myself.gotTracks, myself.failQuiet, true);
			// }

			}
		}

	}

	this.startSending = function() {
		if (self.sending > 0) {
			self.artistindex = Math.floor(Math.random() * self.artists.length);
			debug.shout("MIX RADIO","Asking Artist",self.artistindex,"to send a track");
			self.artists[self.artistindex].sendATrack();
		}
	}

	this.newArtist = function(name, id, getrelated) {
		self.artists.push(new mixArtist(name, id, getrelated));
	}

}
