function spotifyRecommendationsRadio(can_repopulate) {

	// Acdepts seed tracks via addSedTracks, in the form of arrays of
	// Spotify track URIs (without the spotify:track: bit)
	// And/Or will directly accept via getRecommendations
	// {seed_tracks: [csv list of up to 5 ids]}
	// {seed_artists: [csv list of up to 5]}
	// {seed_genres: [blah]}
	// The data passed to getRecommendations can additionally contain any other
	// parameters accepted by the spotify web API.

	var self = this;
	var tracks = new Array();
	var seeds = new Array();
	var error = false;
	var requests_outstanding = 0;
	var last_query;

	this.moreseedscoming = false;

	this.addSeedTracks = function(uris) {
		seeds = seeds.concat(uris);
		while (seeds.length > 0) {
			var sods = seeds.splice(0,5);
			self.getRecommendations({seed_tracks: sods.join(',')});
		}
	}

	this.getRecommendations = function(data) {
		// This queues a bunch of getrecommendations in the spotify request queue, it doesn't
		// block while waiting for the responses
		requests_outstanding++;
		last_query = data;
		data.limit = 50;
		spotify.recommendations.getRecommendations(data, self.gotRecommendations, self.spotiError);
	}

	this.spotiError = function(data) {
		debug.warn("SPOTIRECC","Error!",data);
		requests_outstanding--;
	}

	this.gotRecommendations = function(data) {
		debug.debug("SPOTIRECC","Got Stuff!",data);
		requests_outstanding--;
		if (data.tracks) {
			for (var i in data.tracks) {
				tracks.push(data.tracks[i].uri);
			}
		}
		tracks.sort(randomsort);
		debug.log('SPOTIRECC', 'We now have',tracks.length,'tracks');
	}

	this.getTracks = async function(numtracks) {
		if (tracks.length == 0 && can_repopulate && requests_outstanding == 0) {
			debug.log('SPOTIRECC', 'Getting more tracks')
			self.getRecommendations(last_query);
		}
		while (tracks.length < numtracks && (requests_outstanding > 0 || self.moreseedscoming)) {
			await new Promise(t => setTimeout(t, 500));
		}
		return tracks.splice(0, numtracks);
	}

}
