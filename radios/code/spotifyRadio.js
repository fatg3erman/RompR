function spotifyRadio() {

	// Accetps artist name and id then looks for all related artists
	//  - therefore gets a much broader range of stuff than
	// spotifyRecommendationsRadio

	// This is mildly more complex than it appears it needs to be
	// because coding it nice and tidily makes it spaff a shitload
	// of requests to spotify which not only makes us look bad but also
	// slows down the UI and uses a shed ton of RAM.

	var self = this;
	var artists = new Array();
	var medebug = 'SPOTIRADIO';

	function mixArtist(name, id) {
		debug.log(medebug,"Creating Artist",name,id);
		var albums = null;
		var myself = this;

		this.gotSomeAlbums = function(data) {
			debug.trace(medebug,"Got albums for",name);
			albums = new mixAlbum(name, data.items);
		}

		this.failQuiet = function(data) {
			debug.warn(medebug, "Spotify Error On",name);
			albums = new mixAlbum(name, null);
		}

		this.sendATrack = async function() {
			if (albums === null) {
				debug.trace(medebug,'Getting albums for',name);
				spotify.artist.getAlbums(id, 'album', myself.gotSomeAlbums, myself.failQuiet, true);
			}
			while (albums === null) {
				await new Promise(t => setTimeout(t, 500));
			}
			var track = await albums.sendATrack();
			return track;
		}

		this.getName = function() {
			return name;
		}

	}

	function mixAlbum(name, items) {
		var myself = this;
		debug.log(medebug, 'Creating album for',name);
		var tracks = null;
		var ids = new Array();
		items.forEach(function(album) {
			ids.push(album.id);
		})
		ids.sort(randomsort);
		ids = ids.splice(0, 20);
		if (ids.length == 0) {
			debug.log(medebug,'Got no albums for',name);
			tracks = [];
		}

		this.gotTracks = function(data) {
			debug.log(medebug, "Got Tracks For",name);
			tracks = [];
			data.albums.forEach(function(album) {
				album.tracks.items.forEach(function(track) {
					tracks.push({type: 'uri', name: track.uri});
				});
			});
			tracks.sort(randomsort);
		}

		this.failQuiet = function(data) {
			debug.warn(medebug, "Spotify Error On",name);
			tracks = [];
		}

		this.sendATrack = async function() {
			if (tracks === null) {
				debug.trace(medebug, "Getting tracks for artist",name);
				spotify.album.getMultiInfo(ids, myself.gotTracks, myself.failQuiet, true);
			}
			while (tracks === null) {
				await new Promise(t => setTimeout(t, 500));
			}
			return tracks.shift();			
		}

	}

	this.getTracks = async function (numtracks) {
		while (artists.length == 0) {
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
				debug.log(medebug, 'Deleting artist',artistindex);
				artists.splice(artistindex, 1);
			}
		}
		return [];
	}

	this.newArtist = function(name, id, get_related) {
		for (let artist of artists) {
			if (artist.getName() == name) {
				debug.log(medebug, 'Ignoring artist',name,'as it already exists');
				return;
			}
		};
		artists.push(new mixArtist(name, id, get_related));
		if (get_related) {
			debug.log(medebug, "Getting Related Artists For",name);
			spotify.artist.getRelatedArtists(id, self.gotRelatedArtists, self.gotNoArtists);
		}
	}

	this.gotRelatedArtists = function(data) {
		debug.trace(medebug,"Got related artists for",name);
		data.artists.forEach(function(artist) {
			self.newArtist(artist.name, artist.id, false);
		});
	}

	this.gotNoArtists = function() {

	}

}
